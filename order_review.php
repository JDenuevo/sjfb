<?php
session_start();
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/functions/paymongo_helper.php';

// Get order by CODE
$orderCode = $_GET['order_code'] ?? $_SESSION['order_code'] ?? null;
$status    = $_GET['status']     ?? null;
$sessionId = $_GET['session_id'] ?? null;

if (!$orderCode) {
    $_SESSION['error'] = "Invalid access to order page.";
    header("Location: index.php");
    exit();
}

$pageTitle = 'Order Review';

try {
    // ── Fetch order ────────────────────────────────────────────────────────────
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        header("Location: index.php");
        exit();
    }

    $orderId    = $order['order_id'];
    $isPickup   = ($order['order_type'] ?? 'delivery') === 'pickup';

    // ── Fetch latest payment ───────────────────────────────────────────────────
    $paymentStmt = $conn->prepare("SELECT payment_id, payment_status FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $paymentStmt->bind_param("i", $orderId);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result()->fetch_assoc();
    $paymentStatus = $paymentResult['payment_status'] ?? null;
    $paymentId     = $paymentResult['payment_id']     ?? null;

    // ── Handle PayMongo redirect callback ──────────────────────────────────────
    if ($status && $paymentId && in_array($order['payment_method'], ['gcash','paymaya','grab_pay','card','qrph'])) {
        $newPaymentStatus = ($status === 'success') ? 'Paid' : 'Failed';
        $updateStmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
        $updateStmt->bind_param("si", $newPaymentStatus, $paymentId);
        if ($updateStmt->execute()) {
            $paymentStatus = $newPaymentStatus;
            if ($status === 'success') {
                $_SESSION['success'] = "Payment successful! Your order has been confirmed.";
                unset($_SESSION['cart'], $_SESSION['current_order_id'],
                      $_SESSION['current_order_code'], $_SESSION['pending_payment_order']);
            } else {
                $_SESSION['error'] = "Payment was cancelled or failed. Please try again.";
            }
        } else {
            error_log("Simple payment status update error: " . $updateStmt->error);
        }
    }

    // ── COD — create payments row if missing ───────────────────────────────────
    if ($order['payment_method'] === 'cod' || $order['payment_method'] === 'cop') {
        $codCheck = $conn->prepare("SELECT payment_id FROM payments WHERE order_id = ?");
        $codCheck->bind_param("i", $orderId);
        $codCheck->execute();
        if ($codCheck->get_result()->num_rows === 0) {
            // Use placeholder billing fields for pickup (no address stored)
            $billingLine1  = $isPickup ? 'For Pickup'           : ($order['recipient_address'] ?? '');
            $billingCity   = $isPickup ? 'N/A'                    : ($order['city']              ?? '');
            $billingPostal = $isPickup ? '0000'                   : ($order['postal_code']        ?? '');

            $codStmt = $conn->prepare("
                INSERT INTO payments (
                    order_id, currency, gross_amount, payment_status,
                    mode, billing_name, billing_email, billing_phone,
                    billing_line1, billing_city, billing_postal_code, billing_country,
                    source_type, created_at
                ) VALUES (?, 'PHP', ?, 'Pending', 'live', ?, ?, ?, ?, ?, ?, 'PH', ?, NOW())
            ");
            $billingName = $order['recipient_first_name'] . ' ' . $order['recipient_last_name'];
            $codStmt->bind_param("idsssssss",
                $orderId, $order['total_price'],
                $billingName, $order['recipient_email'], $order['recipient_phone'],
                $billingLine1, $billingCity, $billingPostal
            );
            if (!$codStmt->execute()) error_log("COD payment insert error: " . $codStmt->error);
            $paymentStatus = 'Pending';
        }
    }

    // ── Order items ────────────────────────────────────────────────────────────
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.product_name, v.variant_price, v.variant_name
        FROM order_items oi
        LEFT JOIN products p         ON oi.product_id = p.product_id
        LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ── Formatted date ─────────────────────────────────────────────────────────
    $orderDate = date('F j, Y', strtotime($order['order_date']));

    // ── Payment method display ─────────────────────────────────────────────────
    $method = strtolower($order['payment_method']);
    switch ($method) {
        case 'gcash':    $methodLabel = 'GCash';             $methodClass = 'bg-blue-100 text-blue-800';    break;
        case 'paymaya':  $methodLabel = 'Maya';              $methodClass = 'bg-green-100 text-green-800';  break;
        case 'grab_pay': $methodLabel = 'GrabPay';           $methodClass = 'bg-green-100 text-green-800';  break;
        case 'card':     $methodLabel = 'Credit/Debit Card'; $methodClass = 'bg-purple-100 text-purple-800';break;
        case 'qrph':     $methodLabel = 'QR Ph';             $methodClass = 'bg-indigo-100 text-indigo-800';break;
        case 'cod':      $methodLabel = 'Cash on Delivery';  $methodClass = 'bg-orange-100 text-orange-800';break;
        case 'cop':      $methodLabel = 'Cash on Pickup';  $methodClass = 'bg-orange-100 text-orange-800';break;
        default:         $methodLabel = ucfirst($method);    $methodClass = 'bg-gray-100 text-gray-800';
    }

    // ── Status pill maps ───────────────────────────────────────────────────────
    $orderStatus = $order['order_status'] ?? 'Pending';
    $payPillMap = [
        'Pending'  => 'bg-amber-100 text-amber-700',
        'Paid'     => 'bg-green-100 text-green-700',
        'Failed'   => 'bg-red-100 text-red-700',
        'Refunded' => 'bg-gray-100 text-gray-700',
    ];

    $isCOD    = $method === 'cod' || $method === 'cop';
    $isOnline = in_array($method, ['gcash','paymaya','grab_pay','card','qrph']);

} catch (Exception $e) {
    error_log("Order receipt page error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while processing your order.";
    header("Location: index.php");
    exit();
}
// Replace the tracker steps section:
$isCashPayment = in_array($method, ['cod', 'cop']);

if ($isPickup) {
    $firstStepLabel = $isCashPayment ? 'Order Placed'     : 'Payment Received';
    $firstStepSub   = $isCashPayment ? 'We received your order' : 'Payment confirmed';
    $firstStepDone  = $isCashPayment
        ? ['Pending','Processing','Completed','Delivered']
        : ['Paid','Processing','Completed','Delivered'];

    $trackerSteps = [
        ['label' => $firstStepLabel,  'sub' => $firstStepSub,                          'done' => $firstStepDone],
        ['label' => 'Processing',     'sub' => 'Preparing your items for pickup',       'done' => ['Processing','Completed','Delivered']],
        ['label' => 'Ready for Pickup','sub' => 'Your order is ready at the store',     'done' => ['Completed','Delivered']],
        ['label' => 'Picked Up',      'sub' => 'You collected your order',              'done' => ['Delivered']],
    ];
} else {
    $firstStepLabel = $isCashPayment ? 'Order Placed'     : 'Payment Received';
    $firstStepSub   = $isCashPayment ? 'We received your order' : 'Payment confirmed';
    $firstStepDone  = $isCashPayment
        ? ['Pending','Processing','OutForDelivery','Delivered']
        : ['Paid','Processing','OutForDelivery','Delivered'];

    $trackerSteps = [
        ['label' => $firstStepLabel,   'sub' => $firstStepSub,          'done' => $firstStepDone],
        ['label' => 'Processing',      'sub' => 'Preparing your items', 'done' => ['Processing','OutForDelivery','Delivered']],
        ['label' => 'Out for Delivery','sub' => 'Rider is on the way',  'done' => ['OutForDelivery','Delivered']],
        ['label' => 'Delivered',       'sub' => 'Order complete',        'done' => ['Delivered']],
    ];
}

$isCancelled = $orderStatus === 'Cancelled';
$activeIdx   = 0;
foreach ($trackerSteps as $i => $s) {
    if (in_array($orderStatus, $s['done'])) $activeIdx = $i;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Review | St. Joseph Fish Brokerage Inc.</title>

  <meta property="og:type"        content="website">
  <meta property="og:url"         content="https://fishbrokers.net/">
  <meta property="og:title"       content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image"       content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow">
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image"       content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
</head>

<style>
  /* Only what Tailwind's utilities genuinely can't express is kept here:
     the site-wide display font and the SVG stroke-draw keyframes for the
     success / failure hero icon (dash-offset animation has no utility equivalent). */
  body { font-family: 'Lexend', sans-serif; }
  .font-display { font-family: 'Playfair Display', serif; }

  @keyframes dash { to { stroke-dashoffset: 0; } }
  .anim-circle { stroke-dasharray: 166; stroke-dashoffset: 166; animation: dash .9s ease forwards .2s; }
  .anim-tick   { stroke-dasharray: 48;  stroke-dashoffset: 48;  animation: dash .4s ease forwards .9s; }
  .anim-line   { stroke-dasharray: 30;  stroke-dashoffset: 30;  animation: dash .3s ease forwards .8s; }
</style>

<body>
<?php include('./components/preloaders.php'); ?>

<section id="order-success-section" class="flex-grow">
  <?php include('./components/navigation.php'); ?>
  <?php include('./components/nav_crumb.php'); ?>

  <div class="px-4 sm:px-6 lg:px-8 mx-auto my-4 sm:my-10">

    <!-- Page header -->
    <div class="bg-white border-b border-gray-100 py-6 px-4">
      <div class="max-w-6xl mx-auto flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Order Completed</h1>
          <p class="text-sm text-gray-500 mt-0.5">Please check your order details.</p>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-xs font-semibold">
          <span class="flex items-center gap-1.5 text-gray-400"><span class="size-5 rounded-full bg-gray-200 text-white flex items-center justify-center text-[10px]">1</span>Details</span>
          <span class="text-gray-300">——</span>
          <span class="flex items-center gap-1.5 text-gray-400"><span class="size-5 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-[10px]">2</span>Payment</span>
          <span class="text-gray-300">——</span>
          <span class="flex items-center gap-1.5 text-orange-500"><span class="size-5 rounded-full bg-orange-500 text-white flex items-center justify-center text-[10px]">3</span>Order Completed</span>
        </div>
      </div>
    </div>

    <!-- Animated hero status -->
    <div class="text-center mb-8 pt-10" data-aos="fade-up">

      <?php if ($paymentStatus === 'Paid'): ?>
        <div class="inline-flex items-center justify-center mb-5">
          <svg class="size-24" viewBox="0 0 52 52">
            <circle class="anim-circle" cx="26" cy="26" r="25" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>
            <path   class="anim-tick"   fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27 l8 8 l16-16"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Success</h1>
        <p class="text-gray-600 mb-2">Your payment was received. Our team will review your order before processing. Thank you!</p>

      <?php elseif ($paymentStatus === 'Pending'): ?>
        <div class="inline-flex items-center justify-center mb-5">
          <svg class="size-24" viewBox="0 0 52 52">
            <circle class="anim-circle" cx="26" cy="26" r="25" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>
            <path   class="anim-tick"   fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27 l8 8 l16-16"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Success</h1>
        <p class="text-gray-600 mb-2">Your order was received. Our team will review your order before processing. Thank you!</p>
        <?php if ($isOnline): ?>
        <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-100 text-blue-700 text-xs font-medium px-4 py-2 rounded-full mt-2 mb-2">
          <svg class="size-3.5 animate-spin shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
          Verifying your <?= htmlspecialchars($methodLabel) ?> payment…
        </div>
        <?php endif; ?>

      <?php elseif ($paymentStatus === 'Failed'): ?>
        <div class="inline-flex items-center justify-center mb-5">
          <svg class="size-24" viewBox="0 0 52 52">
            <circle class="anim-circle" cx="26" cy="26" r="25" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>
            <line   class="anim-line" x1="16" y1="16" x2="36" y2="36" stroke="#dc2626" stroke-width="3" stroke-linecap="round"/>
            <line   class="anim-line" x1="36" y1="16" x2="16" y2="36" stroke="#dc2626" stroke-width="3" stroke-linecap="round" style="animation-delay:.9s"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Failed</h1>
        <p class="text-gray-600 mb-2">It seems like your payment has failed or cancelled during the process. Please try again.</p>

      <?php else: ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-5">
          <svg class="w-12 h-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 6h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Status Unknown</h1>
        <p class="text-gray-600 mb-2">We couldn't retrieve your payment status. Please contact support.</p>
      <?php endif; ?>

      <!-- Order code pill -->
      <div class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-5 py-2 shadow-sm mt-3">
        <span class="text-xs text-gray-400">Order Code</span>
        <span class="font-mono font-bold text-orange-600 tracking-widest text-sm"><?= htmlspecialchars($orderCode) ?></span>
        <button onclick="copyOrderCode()" id="copyBtn" title="Copy order code" class="text-gray-300 hover:text-orange-500 transition-colors print:hidden">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </button>
      </div>

      <?php if ($isPickup): ?>
      <!-- Pickup order type badge -->
      <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold px-4 py-2 rounded-full mt-3 ml-2">
        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        For Pickup
      </div>
      <?php endif; ?>
    </div>

    <!-- COD / Pickup reminder banner -->
    <?php if ($isCOD && $paymentStatus !== 'Failed'): ?>
    <div class="flex items-start gap-3 <?= $isPickup ? 'bg-blue-50 border-blue-200' : 'bg-amber-50 border-amber-200' ?> border rounded-2xl p-4 mb-6 print:hidden" data-aos="fade-up" data-aos-delay="70">
      <div class="size-9 rounded-xl <?= $isPickup ? 'bg-blue-100' : 'bg-amber-100' ?> flex items-center justify-center shrink-0">
        <?php if ($isPickup): ?>
        <svg class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <?php else: ?>
        <svg class="size-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M7 15h-3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/>
          <path d="M7 9m0 1a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1z"/>
          <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/>
        </svg>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($isPickup): ?>
        <p class="text-sm font-bold text-blue-800">For Pickup Reminder</p>
        <p class="text-xs text-blue-700 mt-0.5">
          Please bring your order code <strong><?= htmlspecialchars($orderCode) ?></strong> when you visit us to collect your order.
          We'll notify you once it's ready for pickup.
        </p>
        <?php else: ?>
        <p class="text-sm font-bold text-amber-800">Cash on Delivery Reminder</p>
        <p class="text-xs text-amber-700 mt-0.5">
          Please prepare the exact amount of <strong>₱<?= number_format($order['total_price'], 2) ?></strong> when our rider arrives.
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <div class="sm:w-11/12 lg:w-full mx-auto">
      <div class="grid lg:grid-cols-4 gap-6">

        <!-- LEFT — receipt card -->
        <div class="lg:col-span-3 space-y-5">

          <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl" id="orderReceipt" data-aos="fade-up" data-aos-delay="100">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
              <div>
                <img src="./assets/icons/logo.svg" class="w-16 h-16 sm:w-20 sm:h-20" alt="St. Joseph Fish Brokerage Inc.">
                <p class="mt-2 text-sm font-semibold text-orange-600">St. Joseph Fish Brokerage Inc.</p>
              </div>
              <div class="sm:text-right">
                <div class="inline-flex items-center gap-3 bg-gray-50 border rounded-xl px-4 py-3 w-full sm:w-auto">
                  <div class="flex-1 sm:flex-none">
                    <p class="text-xs uppercase text-gray-400 tracking-wide">Order Code</p>
                    <p class="font-bold text-base text-gray-900 font-mono break-all"><?= htmlspecialchars($order['order_code']) ?></p>
                  </div>
                  <a href="./functions/export_receipt.php?order_code=<?= urlencode($orderCode) ?>"
                    target="_blank"
                    title="Download receipt"
                    class="print:hidden shrink-0 inline-flex items-center gap-2 py-2 px-4 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                      <path d="M7 11l5 5 5-5M12 4v12"/>
                    </svg>
                  </a>
                </div>
                <address class="not-italic text-xs text-gray-500 mt-3 leading-relaxed">
                  Bulungan Ave corner HACCP St., NFPC NBBS<br>
                  Boulevard South Proper, Navotas, Philippines
                </address>
              </div>
            </div>

            <!-- Shipping + meta -->
            <div class="my-8 grid sm:grid-cols-2 gap-3">
              <div>
                <?php if ($isPickup): ?>
                <!-- Pickup: show contact info instead of delivery address -->
                <h3 class="text-lg font-semibold text-gray-800">Pickup Information:</h3>
                <h3 class="text-lg font-semibold text-gray-500"><?= htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']) ?></h3>
                <div class="mt-2 inline-flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2">
                  <svg class="size-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                  <div>
                    <p class="text-xs font-semibold text-blue-800">For Pickup</p>
                    <p class="text-xs text-blue-600">Bulungan Ave., Navotas, Philippines</p>
                  </div>
                </div>
                <?php else: ?>
                <h3 class="text-lg font-semibold text-gray-800">Shipping Information:</h3>
                <h3 class="text-lg font-semibold text-gray-500"><?= htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']) ?></h3>
                <h3 class="mt-2 text-lg font-semibold text-gray-800">Address:</h3>
                <address class="not-italic text-gray-500">
                  <?= htmlspecialchars($order['recipient_address']) ?><br>
                  <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['postal_code']) ?>
                </address>
                <?php endif; ?>
              </div>
              <div class="sm:text-end space-y-2">
                <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800">Order Type:</dt>
                    <dd class="col-span-2 text-gray-500">
                      <span class="inline-block px-2 py-1 rounded text-xs font-medium <?= $isPickup ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' ?>">
                        <?= $isPickup ? '🏠 For Pickup' : '🚚 Delivery' ?>
                      </span>
                    </dd>
                  </dl>
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800">Payment Method:</dt>
                    <dd class="col-span-2 text-gray-500">
                      <span class="inline-block px-2 py-1 rounded text-xs font-medium <?= $methodClass ?>">
                        <?= htmlspecialchars($methodLabel) ?>
                      </span>
                    </dd>
                  </dl>
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800">Order date:</dt>
                    <dd class="col-span-2 text-gray-500 text-wrap"><?= $orderDate ?></dd>
                  </dl>
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800">Payment Status:</dt>
                    <dd class="col-span-2 text-gray-500">
                      <span class="inline-block px-2 py-1 rounded text-xs font-medium <?= $payPillMap[$paymentStatus] ?? 'bg-gray-100 text-gray-700' ?>">
                        <?= htmlspecialchars($paymentStatus ?? 'Unknown') ?>
                      </span>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>

            <!-- Items table -->
            <div class="mt-6">
              <div class="border border-gray-200 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 items-center">
                  <div class="col-span-full sm:col-span-2"><h5 class="text-start text-xs font-medium text-black uppercase">Item Name</h5></div>
                  <div><h5 class="text-start text-xs font-medium text-black uppercase">Variant</h5></div>
                  <div><h5 class="text-start text-xs font-medium text-black uppercase">Price</h5></div>
                  <div><h5 class="text-start text-xs font-medium text-black uppercase">Qty</h5></div>
                  <div><h5 class="text-start text-xs font-medium text-black uppercase">Amount</h5></div>
                </div>
                <hr>
                <?php foreach ($items as $item): ?>
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                  <div class="col-span-full sm:col-span-2"><p class="font-medium text-gray-800"><?= htmlspecialchars($item['product_name']) ?></p></div>
                  <div><p class="text-gray-800"><?= htmlspecialchars($item['variant_name']) ?></p></div>
                  <div><p class="text-gray-800">₱<?= number_format($item['variant_price'], 2) ?></p></div>
                  <div><p class="text-gray-800"><?= htmlspecialchars($item['quantity']) ?></p></div>
                  <div><p class="text-gray-800">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Totals -->
            <div class="mt-8 p-4 border-t border-gray-100 space-y-2">
              <?php if (!empty($order['subtotal']) || !empty($order['delivery_fee']) || !empty($order['discount_amount'])): ?>
              <div class="flex justify-between text-sm text-gray-500">
                <dt>Subtotal</dt>
                <dd>₱<?= number_format((float)($order['subtotal'] ?? $order['total_price']), 2) ?></dd>
              </div>
              <?php if (!empty($order['discount_amount']) && (float)$order['discount_amount'] > 0): ?>
              <div class="flex justify-between text-sm text-green-600">
                <dt class="flex items-center gap-1.5">
                  Discount
                  <?php if (!empty($order['voucher_code'])): ?>
                  <span class="text-xs font-mono bg-green-100 text-green-700 px-1.5 py-0.5 rounded"><?= htmlspecialchars($order['voucher_code']) ?></span>
                  <?php endif; ?>
                </dt>
                <dd>−₱<?= number_format((float)$order['discount_amount'], 2) ?></dd>
              </div>
              <?php endif; ?>
              <div class="flex justify-between text-sm text-gray-500">
                <dt><?= $isPickup ? 'Delivery Fee' : 'Delivery Fee' ?></dt>
                <dd>
                  <?php if ($isPickup || (float)($order['delivery_fee'] ?? 0) === 0.0): ?>
                    <span class="text-green-600 font-semibold"><?= $isPickup ? 'FREE (Pickup)' : 'FREE' ?></span>
                  <?php else: ?>
                    ₱<?= number_format((float)$order['delivery_fee'], 2) ?>
                  <?php endif; ?>
                </dd>
              </div>
              <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
                <dt>Total Amount</dt>
                <dd class="text-orange-600">₱<?= number_format($order['total_price'], 2) ?></dd>
              </div>
              <?php else: ?>
              <div class="flex justify-between text-base font-bold text-gray-900">
                <dt>Total Amount</dt>
                <dd>₱<?= number_format($order['total_price'], 2) ?></dd>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <!-- /receipt -->

        </div>
        <!-- /left col -->

        <!-- RIGHT sidebar -->
        <div class="space-y-5 print:hidden">

          <!-- Delivery / Pickup progress tracker -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="110">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50/60">
              <?php if ($isPickup): ?>
              <svg class="size-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
              <h3 class="text-sm font-bold text-gray-800">Pickup Progress</h3>
              <?php else: ?>
              <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/>
                <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
              <h3 class="text-sm font-bold text-gray-800">Delivery Progress</h3>
              <?php endif; ?>
            </div>
            <div class="px-5 py-5">
              <div class="relative">
                <div class="absolute left-[11px] top-4 bottom-4 w-0.5 bg-gray-100 z-0"></div>
                <ol class="relative space-y-6 z-10">
                  <?php foreach ($trackerSteps as $i => $step):
                    $isDone    = in_array($orderStatus, $step['done']) && !$isCancelled;
                    $isCurrent = ($i === $activeIdx) && !$isCancelled;
                  ?>
                  <li class="flex items-start gap-3">
                    <?php if ($isDone): ?>
                    <div class="size-6 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 bg-green-600 border-green-600">
                      <svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div>
                      <p class="text-sm leading-tight font-semibold text-gray-900"><?= $step['label'] ?></p>
                      <p class="text-xs text-gray-400 mt-0.5"><?= $step['sub'] ?></p>
                    </div>
                    <?php elseif ($isCurrent): ?>
                    <div class="size-6 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 bg-orange-500 border-orange-500 shadow-[0_0_0_4px_rgba(249,115,22,0.2)]">
                      <span class="size-2 rounded-full bg-white animate-pulse"></span>
                    </div>
                    <div>
                      <p class="text-sm leading-tight font-bold text-orange-600"><?= $step['label'] ?></p>
                      <p class="text-xs text-gray-400 mt-0.5"><?= $step['sub'] ?></p>
                    </div>
                    <?php else: ?>
                    <div class="size-6 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 bg-white border-gray-300"></div>
                    <div>
                      <p class="text-sm leading-tight text-gray-400"><?= $step['label'] ?></p>
                      <p class="text-xs text-gray-400 mt-0.5"><?= $step['sub'] ?></p>
                    </div>
                    <?php endif; ?>
                  </li>
                  <?php endforeach; ?>

                  <?php if ($isCancelled): ?>
                  <li class="flex items-start gap-3">
                    <div class="size-6 rounded-full border-2 border-red-400 bg-red-50 flex items-center justify-center shrink-0 mt-0.5">
                      <svg class="size-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </div>
                    <div>
                      <p class="text-sm font-bold text-red-600">Cancelled</p>
                      <p class="text-xs text-gray-400 mt-0.5">This order has been cancelled</p>
                    </div>
                  </li>
                  <?php endif; ?>
                </ol>
              </div>
            </div>
          </div>

          <!-- Customer info -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="120">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50/60">
              <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
              <h3 class="text-sm font-bold text-gray-800">Customer</h3>
              <?php if ($order['is_guest_order']): ?>
                <span class="ml-auto text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Guest</span>
              <?php endif; ?>
            </div>
            <div class="px-5 py-4 space-y-2">
              <p class="font-semibold text-gray-800 text-sm">
                <?= htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']) ?>
              </p>
              <p class="flex items-center gap-1.5 text-gray-500 text-xs">
                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <?= htmlspecialchars($order['recipient_email']) ?>
              </p>
              <p class="flex items-center gap-1.5 text-gray-500 text-xs">
                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= htmlspecialchars($order['recipient_phone']) ?>
              </p>
              <a href="track.php?order_code=<?= urlencode($orderCode) ?>"
                 class="mt-2 flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-600 transition-colors border border-gray-200">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                <?= $isPickup ? 'Track my Pickup Order' : 'Track my Order' ?>
              </a>
            </div>
          </div>

          <!-- Save order code reminder -->
          <div class="bg-orange-50 border border-orange-100 rounded-2xl px-5 py-4" data-aos="fade-up" data-aos-delay="130">
            <p class="text-sm font-bold text-orange-800 mb-1">Save your order code!</p>
            <p class="text-xs text-orange-700">
              Use this when contacting us about your order:<br>
              <span class="font-mono font-bold tracking-widest"><?= htmlspecialchars($orderCode) ?></span>
            </p>
          </div>

        </div>
        <!-- /right sidebar -->

      </div>

      <!-- What happens next — pickup-aware -->
      <div class="bg-white rounded-lg shadow-md p-6" data-aos="fade-up" data-aos-delay="150">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">What happens next?</h3>
        <?php if ($isPickup): ?>
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-blue-600">1</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Confirmation</h4>
            <p class="text-sm text-gray-600">We'll contact you within 24 hours to confirm your order details.</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-blue-600">2</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Preparation</h4>
            <p class="text-sm text-gray-600">We'll pack your items and notify you when everything is ready for pickup.</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-blue-600">3</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Pickup at Store</h4>
            <p class="text-sm text-gray-600">Visit our store with your order code to collect your items. <?= $isCOD ? 'Payment is due at pickup.' : '' ?></p>
          </div>
        </div>
        <?php else: ?>
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">1</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Confirmation</h4>
            <p class="text-sm text-gray-600">We'll call or text you within 24 hours to confirm your order details.</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">2</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Processing</h4>
            <p class="text-sm text-gray-600">Once confirmed, we'll prepare your order for delivery.</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">3</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Delivery</h4>
            <p class="text-sm text-gray-600">
              <?= $isCOD
                ? "We'll deliver and collect payment in cash at your doorstep."
                : "Sit back and relax. We'll deliver your order to your address." ?>
            </p>
          </div>
        </div>
        <?php endif; ?>

        <div class="mt-4 sm:mt-8 p-4">
          <h4 class="text-lg font-semibold text-gray-800">Thank you!</h4>
          <p class="text-gray-500">If you have any questions concerning this receipt, use the following contact information:</p>
          <div class="mt-2">
            <p class="block text-sm font-medium text-gray-800">fisbrokers.net</p>
            <p class="block text-sm font-medium text-gray-800">(+63) 946-497-3689</p>
          </div>
        </div>
      </div>

      <!-- Buttons -->
      <div class="mt-6 flex justify-end gap-x-3 print:hidden" data-aos="fade-up" data-aos-delay="160">
        <a id="downloadPdfBtn"
          class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-hidden focus:bg-red-700 cursor-pointer"
          href="./functions/export_receipt.php?order_code=<?= urlencode($orderCode) ?>"
          target="_blank">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20 4l-9 9m-4 0l9 -9"/>
          </svg>
          Download E-Receipt
        </a>
        <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg text-white bg-orange-600 hover:bg-orange-700 shadow-2xs disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50" href="index.php">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>
          </svg>
          Continue Shopping
        </a>
      </div>

    </div>
  </div>

  <?php include('./components/footer.php'); ?>
</section>

<script>
  function copyOrderCode() {
    navigator.clipboard.writeText('<?= addslashes($orderCode) ?>').then(() => {
      const btn  = document.getElementById('copyBtn');
      const orig = btn.innerHTML;
      btn.innerHTML = '<svg class="size-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>';
      setTimeout(() => btn.innerHTML = orig, 1800);
    });
  }

  (function () {
    const INITIAL_ORDER_STATUS   = '<?= addslashes($orderStatus) ?>';
    const INITIAL_PAYMENT_STATUS = '<?= addslashes($paymentStatus ?? 'Pending') ?>';

    const orderInFlight   = ['Pending','Processing'].includes(INITIAL_ORDER_STATUS);
    const paymentInFlight = INITIAL_PAYMENT_STATUS === 'Pending';
    if (!orderInFlight && !paymentInFlight) return;

    let pollCount = 0;
    const pollTimer = setInterval(async () => {
      if (++pollCount > 24) { clearInterval(pollTimer); return; }
      try {
        const res  = await fetch('./functions/check_order_status.php?order_code=<?= urlencode($orderCode) ?>');
        if (!res.ok) return;
        const data = await res.json();
        const orderChanged   = data.order_status   && data.order_status   !== INITIAL_ORDER_STATUS;
        const paymentChanged = data.payment_status && data.payment_status !== INITIAL_PAYMENT_STATUS;
        if (orderChanged || paymentChanged) { clearInterval(pollTimer); location.reload(); }
      } catch (e) {}
    }, 5000);
  })();
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>AOS.init({ once: true, duration: 500, easing: 'ease-out', offset: 40 });</script>
<!-- Fixed: previous src pointed at a local node_modules path, which isn't served in production and 404s, so Preline's JS-driven behaviors never initialized. Pinned to jsdelivr instead. -->
<script src="https://cdn.jsdelivr.net/npm/preline@2.5.1/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>

</body>
</html>