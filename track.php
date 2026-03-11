<?php
session_start();
include 'conn.php';

$pageTitle = 'Track';

date_default_timezone_set('Asia/Manila');

// Handle GET retain (refresh persistence)
if (isset($_GET['order_code']) && !empty($_GET['order_code'])) {
    $orderCode = trim($_GET['order_code']);
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_code, o.order_date, o.total_price, o.order_status,
               o.payment_method, o.first_name, o.last_name, o.email,
               o.phone_number, o.address, o.postal_code, o.city
        FROM orders o WHERE o.order_code = ?
    ");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $orderId = $order['order_id'];
        $itemsStmt = $conn->prepare("
            SELECT oi.*, p.product_name, v.variant_price, v.variant_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $_SESSION['tracked_order'] = $order;
        $_SESSION['tracked_order_items'] = $items;
    }
}

// Handle POST submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderCode = trim($_POST['order_code']);
    if (empty($orderCode)) {
        $_SESSION['error'] = "Please enter your order code.";
        header("Location: track.php");
        exit();
    }
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_code, o.order_date, o.total_price, o.order_status,
               o.payment_method, o.first_name, o.last_name, o.email,
               o.phone_number, o.address, o.postal_code, o.city
        FROM orders o WHERE o.order_code = ?
    ");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No order found with that code. Please double-check and try again.";
        header("Location: track.php");
        exit();
    }
    $order = $result->fetch_assoc();
    $orderId = $order['order_id'];
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.product_name, v.variant_price, v.variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $_SESSION['tracked_order'] = $order;
    $_SESSION['tracked_order_items'] = $items;
    header("Location: track.php?order_code=" . urlencode($orderCode));
    exit();
}

// Status config (mirrors order_summary.php logic)
$statusFlow    = ['Pending', 'Processing', 'OutForDelivery', 'Delivered'];
$statusDisplay = ['Pending'=>'Pending','Processing'=>'Processing','OutForDelivery'=>'Out for Delivery','Delivered'=>'Delivered'];
$methodLabels  = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','qrph'=>'QR Ph','cod'=>'Cash on Delivery','card'=>'Visa/Mastercard'];

// Precompute if order is available
$hasOrder   = isset($_SESSION['tracked_order']);
$orderData  = $_SESSION['tracked_order'] ?? [];
$orderItems = $_SESSION['tracked_order_items'] ?? [];
$isCancelled = ($orderData['order_status'] ?? '') === 'Cancelled';
$currentStep = array_search($orderData['order_status'] ?? 'Pending', $statusFlow);
if ($currentStep === false) $currentStep = 0;
$progressPct = $isCancelled ? 0 : round(($currentStep + 1) / count($statusFlow) * 100);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Order | St. Joseph Fish Brokerage Inc.</title>
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <style>
     body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    /* ── Step timeline ── */
    .step-connector {
      position: absolute;
      top: 20px;
      height: 2px;
      z-index: 0;
    }
    .step-connector-fill {
      height: 100%;
      background: linear-gradient(to right, #f97316, #fb923c);
      transition: width 0.8s cubic-bezier(0.4,0,0.2,1);
    }
    .step-bubble {
      width: 40px; height: 40px;
      border-radius: 9999px;
      display: flex; align-items: center; justify-content: center;
      position: relative; z-index: 10;
      transition: all 0.3s ease;
      font-size: 18px;
    }
    .step-bubble.done   { background: #f97316; box-shadow: 0 4px 14px rgba(249,115,22,.35); }
    .step-bubble.active { background: #fff; border: 2px solid #f97316; }
    .step-bubble.idle   { background: #f3f4f6; border: 2px solid #e5e7eb; }

    /* ── Hero search card ── */
    .track-hero {
      background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 60%, #e2e8f0 100%);
    }

    /* input focus stays consistent */
    .track-input:focus {
      border-color: #f97316;
      box-shadow: 0 0 0 3px rgba(249,115,22,.15);
    }

    /* ── Subtle entry animations ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .anim-1 { animation: fadeUp .5s ease both; }
    .anim-2 { animation: fadeUp .5s .1s ease both; }
    .anim-3 { animation: fadeUp .5s .2s ease both; }
    .anim-4 { animation: fadeUp .5s .3s ease both; }

    /* ── Receipt card ── */
    .receipt-row:not(:last-child) { border-bottom: 1px solid #f3f4f6; }

    /* search input for light hero */
    .track-input {
      background: rgba(255,255,255,0.7);
      border: 1px solid rgba(148,163,184,0.35); /* slate-400 */
      color: #0f172a; /* slate-900 */
    }

    .track-input::placeholder {
      color: #64748b; /* slate-500 */
    }

    .track-input:hover {
      background: rgba(255,255,255,0.9);
    }
  </style>
</head>

<body class="bg-gray-50">

<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>

<?php include('./components/nav_crumb.php'); ?>
<!-- ── Hero Section ── -->
<section class="track-hero py-16 px-4">
  <div class="max-w-xl mx-auto text-center anim-1">
    <div class="inline-flex items-center gap-2  bg-orange-500/10 border border-orange-400/30 rounded-full px-4 py-1.5 text-xs font-semibold text-orange-600 uppercase tracking-widest mb-5">
      <svg class="size-3" fill="currentColor" viewBox="0 0 8 8">
        <circle cx="4" cy="4" r="4"/>
      </svg>
      Real-time tracking
    </div>

    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-3 leading-tight">
      Track Your Order
    </h1>

    <p class="text-slate-500 text-sm mb-10">
      Enter your order code to see live status, items, and delivery info.
    </p>

    <!-- Search Form -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-5 flex items-center gap-3 bg-red-500/15 border border-red-400/30 rounded-xl px-4 py-3 text-sm text-red-300">
      <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col sm:flex-row gap-3 mt-5">
      <div class="flex-1 relative">

        <input
          type="text"
          id="order_code"
          name="order_code"
          required
          value="<?= isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : '' ?>"
          placeholder="e.g. ORD-XXXX-XXXX-XXXX"
          class="py-3 px-4 block border track-input w-full rounded-xl pl-10 pr-4 text-sm
                focus:outline-none transition-all"
        >
      </div>

      <button
        type="submit"
        name="track_order"
        class="shrink-0 flex items-center justify-center gap-2
              bg-orange-600 hover:bg-orange-500 active:scale-95
              text-white font-semibold text-sm
              rounded-xl px-6 py-3 transition-all">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14"/>
          <path d="m12 5 7 7-7 7"/>
        </svg>
        Track Order
      </button>
    </form>
  </div>
</section>

<?php if ($hasOrder): ?>

<?php
  // Derived display values
  $orderStatus   = $orderData['order_status'];
  $methodDisplay = $methodLabels[strtolower($orderData['payment_method'] ?? '')] ?? ucfirst($orderData['payment_method'] ?? '—');
  $statusBadgeConf = [
    'Pending'        => 'bg-yellow-100 text-yellow-800',
    'Processing'     => 'bg-blue-100 text-blue-800',
    'OutForDelivery' => 'bg-purple-100 text-purple-800',
    'Delivered'      => 'bg-green-100 text-green-800',
    'Cancelled'      => 'bg-red-100 text-red-800',
  ];
  $osBadge = $statusBadgeConf[$orderStatus] ?? 'bg-gray-100 text-gray-700';

  // Step icons & messages (same metaphor as order_summary)
  $stepIcons = ['🛒','⚙️','🛵','✅'];
  $statusMessages = [
    'Pending'        => 'Your order is pending confirmation. We\'ll start processing it shortly.',
    'Processing'     => 'Great news! Your order is being prepared and packed.',
    'OutForDelivery' => 'Your order is on the way — our rider is heading to you!',
    'Delivered'      => 'Order delivered! Thank you for choosing St. Joseph Fish Brokerage.',
    'Cancelled'      => 'This order has been cancelled. Contact us if you need help.',
  ];
?>

<!-- ── Results Section ── -->
<section class="py-10 px-4">
  <div class="max-w-3xl mx-auto space-y-5">

    <!-- Order Header Banner (mirrors order_summary banner) -->
    <div class="anim-2 relative overflow-hidden bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-6 text-white shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Order Reference</p>
          <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-2xl font-bold text-orange-600"><?= htmlspecialchars($orderData['order_code']) ?></h2>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $osBadge ?>">
              <?= $statusDisplay[$orderStatus] ?? $orderStatus ?>
            </span>
          </div>
          <p class="text-gray-400 text-sm mt-1">
            <?= htmlspecialchars($orderData['first_name'].' '.$orderData['last_name']) ?>
            &nbsp;·&nbsp;
            <?= date('M j, Y · g:i A', strtotime($orderData['order_date'])) ?>
          </p>
        </div>
        <div class="text-left sm:text-right shrink-0">
          <p class="text-xs text-gray-400 mb-0.5">Order Total</p>
          <p class="text-3xl font-bold text-orange-600">₱<?= number_format($orderData['total_price'], 2) ?></p>
          <p class="text-xs text-gray-400 mt-0.5"><?= $methodDisplay ?></p>
        </div>
      </div>
      <!-- Decorative circles -->
      <div class="absolute -top-6 -right-6 size-28 bg-white/5 rounded-full pointer-events-none"></div>
      <div class="absolute -bottom-8 right-10 size-20 bg-orange-500/10 rounded-full pointer-events-none"></div>
    </div>

    <?php if ($isCancelled): ?>
    <!-- Cancelled Banner -->
    <div class="anim-3 bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <div class="size-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
        <svg class="size-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>
        </svg>
      </div>
      <p class="text-lg font-bold text-red-700 mb-1">Order Cancelled</p>
      <p class="text-sm text-red-500"><?= $statusMessages['Cancelled'] ?></p>
    </div>

    <?php else: ?>

    <!-- Progress Timeline (mirrors order_summary progress) -->
    <div class="anim-3 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-semibold text-gray-700">Delivery Progress</h3>
        <span class="text-xs font-bold text-orange-500"><?= $progressPct ?>% complete</span>
      </div>

      <!-- Thin progress bar -->
      <div class="w-full h-1.5 bg-gray-100 rounded-full mb-6 overflow-hidden">
        <div class="h-full bg-gradient-to-r from-orange-500 to-amber-400 rounded-full transition-all duration-700"
             style="width: <?= $progressPct ?>%"></div>
      </div>

      <!-- Step bubbles -->
      <div class="relative flex items-start justify-between">
        <!-- Background connector line -->
        <div class="step-connector bg-gray-200" style="left:20px; right:20px;"></div>
        <!-- Filled connector -->
        <div class="step-connector" style="left:20px; right:20px;">
          <div class="step-connector-fill" style="width:<?= $currentStep > 0 ? min(100, ($currentStep / (count($statusFlow)-1)) * 100) : 0 ?>%"></div>
        </div>

        <?php foreach ($statusFlow as $i => $status):
          $done   = $i <= $currentStep;
          $active = $i === $currentStep;
          $bubbleClass = $done ? 'done' : ($active ? 'active' : 'idle');
          $labelClass  = $done ? 'text-orange-600 font-semibold' : 'text-gray-400';
        ?>
        <div class="flex flex-col items-center gap-2 relative z-10" style="width:<?= 100/count($statusFlow) ?>%">
          <div class="step-bubble <?= $bubbleClass ?>">
            <?php if ($done): ?>
              <?= $stepIcons[$i] ?>
            <?php else: ?>
              <span class="size-3 rounded-full <?= $active ? 'bg-orange-300' : 'bg-gray-300' ?> inline-block"></span>
            <?php endif; ?>
          </div>
          <span class="text-xs text-center leading-tight <?= $labelClass ?> max-w-[64px]">
            <?= $statusDisplay[$status] ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Status message -->
      <div class="mt-5 pt-4 border-t border-gray-100 flex items-start gap-3">
        <div class="size-8 rounded-xl bg-orange-100 flex items-center justify-center shrink-0 text-base">
          <?= $stepIcons[$currentStep] ?>
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-800"><?= $statusDisplay[$orderStatus] ?></p>
          <p class="text-xs text-gray-500 mt-0.5"><?= $statusMessages[$orderStatus] ?? '' ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Order Receipt Card (essential info only) -->
    <div class="anim-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="orderReceipt">

      <!-- Receipt header -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 bg-gray-50/60">
        <div class="flex items-center gap-3">
          <img src="./assets/icons/logo.svg" alt="SJFBI Logo" class="size-10">
          <div>
            <p class="text-sm font-bold text-gray-800">St. Joseph Fish Brokerage Inc.</p>
            <p class="text-xs text-gray-400">Navotas, Philippines</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-400">Order Code</p>
          <p class="text-sm font-bold text-orange-600"><?= htmlspecialchars($orderData['order_code']) ?></p>
          <p class="text-xs text-gray-400 mt-0.5"><?= date('M j, Y', strtotime($orderData['order_date'])) ?></p>
        </div>
      </div>

      <!-- Delivery info row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
        <div class="px-6 py-4">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Deliver To</p>
          <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($orderData['first_name'].' '.$orderData['last_name']) ?></p>
          <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($orderData['address']) ?></p>
          <p class="text-xs text-gray-500"><?= htmlspecialchars($orderData['city'].', '.$orderData['postal_code']) ?></p>
        </div>
        <div class="px-6 py-4">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment</p>
          <div class="flex items-center gap-2">
            <?php
              $methodBadges = [
                'gcash'    => 'bg-blue-100 text-blue-700',
                'paymaya'  => 'bg-green-100 text-green-700',
                'grab_pay' => 'bg-green-100 text-green-700',
                'qrph'     => 'bg-indigo-100 text-indigo-700',
                'cod'      => 'bg-orange-100 text-orange-700',
                'card'     => 'bg-purple-100 text-purple-700',
              ];
              $mKey = strtolower($orderData['payment_method'] ?? '');
              $mBadge = $methodBadges[$mKey] ?? 'bg-gray-100 text-gray-600';
            ?>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $mBadge ?>"><?= $methodDisplay ?></span>
          </div>
          <p class="text-xs text-gray-400 mt-1">Order placed <?= date('F j, Y \a\t g:i A', strtotime($orderData['order_date'])) ?></p>
        </div>
      </div>

      <!-- Items table -->
      <div class="border-t border-gray-100">
        <div class="px-6 py-3 bg-gray-50/60">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Items Ordered</p>
        </div>
        <div class="divide-y divide-gray-50">
          <?php foreach ($orderItems as $item):
            $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
          ?>
          <div class="receipt-row flex items-center gap-4 px-6 py-3.5">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($item['product_name']) ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($item['variant_name'] ?? '—') ?></p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-sm font-bold text-gray-800">₱<?= number_format($lineTotal, 2) ?></p>
              <p class="text-xs text-gray-400">×<?= $item['quantity'] ?> @ ₱<?= number_format($item['variant_price'] ?? $item['price'], 2) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Subtotal -->
        <div class="px-6 py-4 bg-gray-50/60 border-t border-gray-100 flex items-center justify-between">
          <span class="text-sm font-semibold text-gray-700">Order Total</span>
          <span class="text-xl font-bold text-orange-600">₱<?= number_format($orderData['total_price'], 2) ?></span>
        </div>
      </div>
    </div>
    <!-- End Receipt -->

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 anim-4">
      <button id="downloadBtn"
        class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl transition-all">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download Receipt
      </button>
      <a href="shop.php"
        class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 active:scale-95 rounded-xl transition-all">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Shop
      </a>
      <a href="track.php"
        class="flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-gray-600 border border-gray-200 bg-white hover:bg-gray-50 active:scale-95 rounded-xl transition-all">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
        Track Another
      </a>
    </div>

  </div>
</section>

<?php endif; ?>

<!-- ── Need Help Strip ── -->
<section class="py-10 px-4 border-t border-gray-100">
  <div class="max-w-3xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
    <div>
      <p class="text-sm font-semibold text-gray-800">Can't find your order?</p>
      <p class="text-xs text-gray-400 mt-0.5">Check your email for the order confirmation or contact us.</p>
    </div>
    <a href="contact.php" class="shrink-0 px-5 py-2.5 text-sm font-semibold text-orange-600 border border-orange-200 bg-orange-50 hover:bg-orange-100 rounded-xl transition-colors">
      Contact Support
    </a>
  </div>
</section>

<?php include('./components/footer.php'); ?>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>
  AOS.init();

  // Download receipt as PNG
  const downloadBtn = document.getElementById('downloadBtn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', function () {
      const receipt = document.getElementById('orderReceipt');
      if (!receipt) return;
      this.innerHTML = '<svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="30"/></svg> Generating…';
      html2canvas(receipt, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(canvas => {
        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = '<?= htmlspecialchars($orderData['order_code'] ?? 'receipt') ?>_receipt.png';
        link.click();
        this.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download Receipt';
      });
    });
  }
</script>

<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

<?php include('live_chat.php'); ?>

</body>
</html>