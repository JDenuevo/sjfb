<?php
/**
 * track.php
 * Customer-facing order tracking page.
 * Stepper differs by payment method AND order_type:
 *   Delivery COD:    Pending     → Processing → Out for Delivery → Delivered
 *   Delivery Online: Paid        → Processing → Out for Delivery → Delivered
 *   Pickup COD:      Pending     → Processing → Ready for Pickup → Picked Up
 *   Pickup Online:   Paid        → Processing → Ready for Pickup → Picked Up
 */
session_start();
include 'conn.php';

$pageTitle = 'Track';
date_default_timezone_set('Asia/Manila');

// ── Fetch order on GET ─────────────────────────────────────────────────────
if (isset($_GET['order_code']) && !empty($_GET['order_code'])) {
    $orderCode = trim($_GET['order_code']);
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->bind_param('s', $orderCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order   = $result->fetch_assoc();
        $orderId = $order['order_id'];

        // Items
        $itemsStmt = $conn->prepare("
            SELECT oi.*, p.product_name, v.variant_price, v.variant_name, oi.price
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Delivery + rider info (only relevant for delivery orders, but we fetch anyway)
        $dlStmt = $conn->prepare("
            SELECT d.delivery_id, d.delivery_status,
                   d.is_third_party, d.third_party_name, d.delivery_link,
                   d.assigned_at, d.accepted_at, d.picked_up_at, d.delivered_at,
                   d.estimated_time, d.estimated_distance,
                   COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name,
                   r.image AS rider_image, r.vehicle_type, r.vehicle_plate_number,
                   r.variant_color, r.organization, r.rider_phone
            FROM deliveries d
            LEFT JOIN riders r   ON r.rider_id = d.rider_id
            LEFT JOIN accounts a ON a.account_id = r.account_id
            WHERE d.order_id = ? AND d.delivery_status NOT IN ('reassigned','cancelled')
            ORDER BY d.assigned_at DESC LIMIT 1
        ");
        $dlStmt->bind_param('i', $orderId);
        $dlStmt->execute();
        $delivery = $dlStmt->get_result()->fetch_assoc();

        $_SESSION['tracked_order']       = $order;
        $_SESSION['tracked_order_items'] = $items;
        $_SESSION['tracked_delivery']    = $delivery;
    }
}

// ── Handle POST submit ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderCode = trim($_POST['order_code']);
    if (empty($orderCode)) {
        $_SESSION['error'] = 'Please enter your order code.';
        header('Location: track.php');
        exit;
    }
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_code = ?");
    $stmt->bind_param('s', $orderCode);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $_SESSION['error'] = 'No order found with that code. Please double-check and try again.';
        header('Location: track.php');
        exit;
    }
    header('Location: track.php?order_code=' . urlencode($orderCode));
    exit;
}

// ── Display config ─────────────────────────────────────────────────────────
$methodLabels = [
    'gcash'    => 'GCash',
    'paymaya'  => 'PayMaya',
    'grab_pay' => 'GrabPay',
    'qrph'     => 'QR Ph',
    'cod'      => 'Cash on Delivery',
    'cop'      => 'Cash on Pickup',
    'card'     => 'Visa/Mastercard',
];

// Status display now includes Completed for pickup flow
$statusDisplay = [
    'Paid'           => 'Payment Received',
    'Pending'        => 'Order Placed',
    'Processing'     => 'In Process',
    'OutForDelivery' => 'Out for Delivery',
    'Completed'      => 'Ready for Pickup',
    'Delivered'      => 'Delivered / Picked Up',
    'Cancelled'      => 'Cancelled',
];

$dlStatusLabels = [
    'pending_acceptance' => 'Awaiting Rider Acceptance',
    'accepted'           => 'Rider Accepted',
    'picked_up'          => 'Picked Up',
    'in_transit'         => 'In Transit',
    'delivered'          => 'Delivered',
];

$hasOrder   = isset($_SESSION['tracked_order']);
$orderData  = $_SESSION['tracked_order']       ?? [];
$orderItems = $_SESSION['tracked_order_items'] ?? [];
$delivery   = $_SESSION['tracked_delivery']    ?? null;

// ── Detect order type ──────────────────────────────────────────────────────
$isPickup = ($orderData['order_type'] ?? 'delivery') === 'pickup';
$isCOD = in_array($orderData['payment_method'] ?? '', ['cod', 'cop']);

// Add these icon path variables at the top, before the $steps arrays
$iconOrder  = '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/>';
$iconSpin   = '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>';
$iconBox    = '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>';
$iconTruck  = '<rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>';
$iconCheck  = '<path d="M20 6 9 17l-5-5"/>';
$iconCard   = '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>';
$iconHome   = '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>';

if ($isPickup) {
    $steps = [
        ['key' => $isCOD ? 'Pending' : 'Paid',
         'label' => $isCOD ? 'Order Placed' : 'Payment Received',
         'icon' => $isCOD ? $iconOrder : $iconCard],
        ['key' => 'Processing', 'label' => 'In Process',       'icon' => $iconSpin],
        ['key' => 'Completed',  'label' => 'Ready for Pickup', 'icon' => $iconBox],
        ['key' => 'Delivered',  'label' => 'Picked Up',        'icon' => $iconHome],
    ];
    $stepIndex = $isCOD
        ? ['Pending'=>0,'Processing'=>1,'Completed'=>2,'Delivered'=>3]
        : ['Paid'=>0,'Processing'=>1,'Completed'=>2,'Delivered'=>3];
} else {
    $steps = [
        ['key' => $isCOD ? 'Pending' : 'Paid',
         'label' => $isCOD ? 'Order Placed' : 'Payment Received',
         'icon' => $isCOD ? $iconOrder : $iconCard],
        ['key' => 'Processing',     'label' => 'In Process',       'icon' => $iconSpin],
        ['key' => 'OutForDelivery', 'label' => 'Out for Delivery', 'icon' => $iconTruck],
        ['key' => 'Delivered',      'label' => 'Delivered',        'icon' => $iconCheck],
    ];
    $stepIndex = $isCOD
        ? ['Pending'=>0,'Processing'=>1,'OutForDelivery'=>2,'Delivered'=>3,'Completed'=>2]
        : ['Paid'=>0,'Processing'=>1,'OutForDelivery'=>2,'Delivered'=>3,'Completed'=>2];
}

$isCancelled = ($orderData['order_status'] ?? '') === 'Cancelled';
$currentStep = $stepIndex[$orderData['order_status'] ?? ''] ?? 0;
$lastStep    = count($steps) - 1;
$fillPct     = $isCancelled ? 0 : ($lastStep > 0 ? round(($currentStep / $lastStep) * 100) : 0);
$currentStepSafe = min($currentStep, $lastStep);
?>
<!DOCTYPE html>
<html lang="en">
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

  <style>
    /* Only what Tailwind's utility classes genuinely can't express is kept here:
       the site-wide display font and the two keyframe-driven progress transitions
       that need a gradient fill animated by an inline (PHP-computed) width. */
    body { font-family: 'Lexend', sans-serif; }
  </style>
</head>

<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>

<body class="bg-gray-50 font-['Lexend',sans-serif]">
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- Hero / Search -->
<section class="bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 py-16 px-4">
  <div class="max-w-xl mx-auto text-center" data-aos="fade-up">
    <div class="inline-flex items-center gap-2 bg-orange-500/10 border border-orange-400/30 rounded-full px-4 py-1.5 text-xs font-semibold text-orange-600 uppercase tracking-widest mb-5">
      <svg class="size-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
      Real-time tracking
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-3 leading-tight">Track Your Order</h1>
    <p class="text-slate-500 text-sm mb-10">Enter your order code to see live status, items, and delivery info.</p>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700" role="alert">
      <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input type="text" name="order_code" required
               value="<?= isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : '' ?>"
               placeholder="e.g. ORD260318VLIBKU"
               class="py-3 px-4 block w-full rounded-xl text-sm bg-white/70 border border-slate-300/50 text-slate-900 placeholder:text-slate-500 hover:bg-white/90 focus:bg-white/90 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/15 focus:outline-none transition-all">
      </div>
      <button type="submit" name="track_order"
              class="shrink-0 flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white font-semibold text-sm rounded-xl px-6 py-3 transition-all">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        Track Order
      </button>
    </form>
  </div>
</section>

<?php if ($hasOrder):
  $orderStatus   = $orderData['order_status'];
  $methodDisplay = $methodLabels[strtolower($orderData['payment_method'] ?? '')] ?? ucfirst($orderData['payment_method'] ?? '—');

  $statusBadgeConf = [
    'Paid'           => 'bg-green-100 text-green-800',
    'Pending'        => 'bg-yellow-100 text-yellow-800',
    'Processing'     => 'bg-blue-100 text-blue-800',
    'OutForDelivery' => 'bg-purple-100 text-purple-800',
    'Completed'      => 'bg-indigo-100 text-indigo-800',
    'Delivered'      => 'bg-green-100 text-green-800',
    'Cancelled'      => 'bg-red-100 text-red-800',
  ];
  $osBadge = $statusBadgeConf[$orderStatus] ?? 'bg-gray-100 text-gray-700';

  $statusMessages = [
    'Paid'           => 'Payment received! Your order is waiting for admin approval.',
    'Pending'        => $isPickup
                          ? "Order placed! We'll prepare your items and notify you when ready for pickup."
                          : "Order placed! We'll confirm and start processing it shortly.",
    'Processing'     => $isPickup
                          ? 'Your order is being prepared and packed for pickup.'
                          : 'Your order is being prepared and packed.',
    'OutForDelivery' => 'Your order is on the way — our rider is heading to you!',
    'Completed'      => 'Your order is ready! Visit our store to collect it.',
    'Delivered'      => $isPickup
                          ? 'Order picked up! Thank you for choosing St. Joseph Fish Brokerage.'
                          : 'Order delivered! Thank you for choosing St. Joseph Fish Brokerage.',
    'Cancelled'      => 'This order has been cancelled. Contact us if you need help.',
  ];

  // Rider/delivery section only shown for delivery orders out for delivery or delivered
  $isOutForDelivery = $orderStatus === 'OutForDelivery';
  $isDelivered      = $orderStatus === 'Delivered';
  $showDelivery     = !$isPickup && $delivery && ($isOutForDelivery || $isDelivered);

  $paymentStatusDisplay = $orderData['payment_status'] ?? 'Pending';
?>

<section class="py-10 px-4">
<div class="max-w-3xl mx-auto space-y-5">

  <!-- Order header banner -->
  <div class="relative overflow-hidden bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-6 text-white shadow-sm" data-aos="fade-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
      <div>
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Order Reference</p>
        <div class="flex items-center gap-3 flex-wrap">
          <h2 class="text-2xl font-bold text-orange-500"><?= htmlspecialchars($orderData['order_code']) ?></h2>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $osBadge ?>">
            <?= $statusDisplay[$orderStatus] ?? $orderStatus ?>
          </span>
          <?php if ($isPickup): ?>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">🏠 Pickup</span>
          <?php endif; ?>
          <?php if (!empty($delivery['is_third_party']) && $delivery['third_party_name']): ?>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
            via <?= htmlspecialchars($delivery['third_party_name']) ?>
          </span>
          <?php endif; ?>
        </div>
        <p class="text-gray-400 text-sm mt-1">
          <?= htmlspecialchars($orderData['recipient_first_name'].' '.$orderData['recipient_last_name']) ?>
          &nbsp;·&nbsp;
          <?= date('M j, Y · g:i A', strtotime($orderData['order_date'])) ?>
        </p>
      </div>
      <div class="text-left sm:text-right shrink-0">
        <p class="text-xs text-gray-400 mb-0.5">Order Total</p>
        <p class="text-3xl font-bold text-orange-500">₱<?= number_format($orderData['total_price'], 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= $methodDisplay ?></p>
      </div>
    </div>
    <div class="absolute -top-6 -right-6 size-28 bg-white/5 rounded-full pointer-events-none"></div>
    <div class="absolute -bottom-8 right-10 size-20 bg-orange-500/10 rounded-full pointer-events-none"></div>
  </div>

  <?php if ($isCancelled): ?>
  <!-- Cancelled -->
  <div class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center" data-aos="fade-up">
    <div class="size-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
      <svg class="size-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
    </div>
    <p class="text-lg font-bold text-red-700 mb-1">Order Cancelled</p>
    <p class="text-sm text-red-500"><?= $statusMessages['Cancelled'] ?></p>
    <?php if (!empty($orderData['cancel_reason'])): ?>
    <p class="text-xs text-red-400 mt-2 italic">"<?= htmlspecialchars($orderData['cancel_reason']) ?>"</p>
    <?php endif; ?>
  </div>

  <?php else: ?>

    <!-- Pickup-specific "Ready for Pickup" notice -->
  <?php if ($isPickup && $orderStatus === 'Completed'): ?>
  <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-2xl p-4" data-aos="fade-up">

    <div class="size-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
      <svg class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
      </svg>
    </div>

    <div>
      <p class="text-sm font-bold text-blue-800">Your order is ready for pickup!</p>
      <p class="text-xs text-blue-600 mt-0.5">
        Please visit our store at Bulungan Avenue corner HACCP St., Navotas and bring your order code
        <strong><?= htmlspecialchars($orderData['order_code']) ?></strong> to collect your items.
        <?= $isCOD ? 'Please prepare ₱'.number_format($orderData['total_price'], 2).' for payment.' : '' ?>
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pickup-specific "Already picked up" notice -->
  <?php if ($isPickup && $orderStatus === 'Delivered'): ?>
  <div class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-2xl p-4" data-aos="fade-up">

    <div class="size-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
      <svg class="size-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path d="M20 6 9 17l-5-5"/>
      </svg>
    </div>

    <div>
      <p class="text-sm font-bold text-green-800">Order picked up — thank you!</p>
      <p class="text-xs text-green-600 mt-0.5">
        Your order was collected from our store. We hope you enjoy your purchase!
      </p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Progress stepper -->
  <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm" data-aos="fade-up">
    <div class="flex items-center justify-between mb-1.5">
      <span class="text-xs font-semibold text-gray-500"><?= $isPickup ? 'Pickup Progress' : 'Delivery Progress' ?></span>
      <span class="text-xs font-bold text-orange-500"><?= $fillPct ?>% complete</span>
    </div>
    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-6">
      <div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-amber-400 transition-all duration-700 ease-in-out" style="width:<?= $fillPct ?>%"></div>
    </div>
    <div class="relative flex items-start justify-between">
      <div class="absolute top-5 left-5 right-5 h-0.5 bg-gray-200 z-0"></div>
      <div class="absolute top-5 left-5 h-0.5 bg-gradient-to-r from-orange-500 to-amber-400 z-[1] transition-all duration-700 ease-in-out" style="width:<?= $lastStep > 0 ? min(100, round(($currentStep / $lastStep) * 100)) : 0 ?>%"></div>
      <?php foreach ($steps as $i => $step):
        $done   = $i <= $currentStep;
        $active = $i === $currentStep;
      ?>
      <div class="flex flex-col items-center gap-2 relative z-10 flex-1">
        <?php if ($done && !$active): ?>
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 bg-orange-500 border-2 border-orange-500 text-white shadow-[0_4px_12px_rgba(249,115,22,0.35)]">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path d="M20 6 9 17l-5-5"/>
          </svg>
        </div>
        <span class="text-[11px] text-center leading-tight max-w-16 transition-colors duration-300 text-orange-600 font-semibold"><?= htmlspecialchars($step['label']) ?></span>
        <?php elseif ($active): ?>
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 bg-white border-2 border-orange-500 shadow-[0_0_0_5px_rgba(249,115,22,0.15)] text-orange-500">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <?= $step['icon'] ?>
          </svg>
        </div>
        <span class="text-[11px] text-center leading-tight max-w-16 transition-colors duration-300 text-orange-500 font-medium"><?= htmlspecialchars($step['label']) ?></span>
        <?php else: ?>
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 bg-gray-50 border-2 border-gray-200 text-gray-400">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <?= $step['icon'] ?>
          </svg>
        </div>
        <span class="text-[11px] text-center leading-tight max-w-16 transition-colors duration-300 text-gray-400"><?= htmlspecialchars($step['label']) ?></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
      <?php $safeStep = $steps[$currentStepSafe] ?? $steps[0]; ?>
      <div class="w-9 h-9 rounded-[10px] bg-orange-50 flex items-center justify-center shrink-0">
        <svg class="size-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <?= $safeStep['icon'] ?>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold text-gray-800 m-0"><?= $statusDisplay[$orderStatus] ?? $orderStatus ?></p>
        <p class="text-xs text-gray-500 mt-0.5 m-0"><?= $statusMessages[$orderStatus] ?? '' ?></p>
      </div>
    </div>
  </div>

  <?php endif; /* not cancelled */ ?>

  <!-- Pickup store info card (shown while not yet delivered) -->
  <?php if ($isPickup && !$isCancelled && $orderStatus !== 'Delivered'): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
      <span class="size-7 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm">🏠</span>
      <div>
        <h3 class="text-sm font-semibold text-gray-800">Pickup Location</h3>
        <p class="text-xs text-gray-400">Visit us to collect your order</p>
      </div>
    </div>
    <div class="p-6 space-y-3">
      <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl">
        <div class="size-12 rounded-xl bg-blue-100 flex items-center justify-center text-2xl shrink-0">📍</div>
        <div>
          <p class="text-sm font-bold text-blue-800">St. Joseph Fish Brokerage Inc.</p>
          <p class="text-xs text-blue-600 mt-0.5">Bulungan Avenue corner HACCP St., NFPC NBBS, Navotas, Philippines</p>
          <p class="text-xs text-blue-500 mt-1">Boulevard South Proper, Navotas, Philippines</p>
        </div>
      </div>
      <div class="flex items-start gap-2 text-xs text-gray-500">
        <svg class="size-3.5 text-orange-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Bring your order code <strong class="text-gray-700 font-mono"><?= htmlspecialchars($orderData['order_code']) ?></strong> when you come to collect.
        <?= $isCOD ? 'Payment of <strong>₱'.number_format($orderData['total_price'], 2).'</strong> is due at pickup.' : '' ?>
      </div>
      <?php if (!empty($orderData['delivery_notes'])): ?>
      <p class="text-xs text-orange-600 italic">"<?= htmlspecialchars($orderData['delivery_notes']) ?>"</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Delivery / Rider section (delivery orders only) -->
  <?php if ($showDelivery): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
      <span class="size-7 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-sm">🛵</span>
      <div>
        <h3 class="text-sm font-semibold text-gray-800">
          <?= !empty($delivery['is_third_party']) ? '3rd-Party Delivery' : 'Rider Information' ?>
        </h3>
        <?php $dlStatus = $delivery['delivery_status'] ?? ''; ?>
        <p class="text-xs text-gray-400"><?= htmlspecialchars($dlStatusLabels[$dlStatus] ?? ucfirst($dlStatus)) ?></p>
      </div>
      <?php
        $dlBadgeConf = [
          'pending_acceptance' => 'bg-yellow-100 text-yellow-700',
          'accepted'           => 'bg-blue-100 text-blue-700',
          'picked_up'          => 'bg-indigo-100 text-indigo-700',
          'in_transit'         => 'bg-purple-100 text-purple-700',
          'delivered'          => 'bg-green-100 text-green-700',
        ];
        $dlBadge = $dlBadgeConf[$dlStatus] ?? 'bg-gray-100 text-gray-600';
      ?>
      <span class="ml-auto px-2.5 py-1 rounded-full text-xs font-semibold <?= $dlBadge ?>">
        <?= htmlspecialchars($dlStatusLabels[$dlStatus] ?? ucfirst($dlStatus)) ?>
      </span>
    </div>

    <div class="p-6 space-y-5">
      <?php if (!empty($delivery['is_third_party'])): ?>
      <div class="flex items-center gap-4 p-4 bg-indigo-50 rounded-xl">
        <div class="size-12 rounded-xl bg-indigo-200 flex items-center justify-center text-2xl shrink-0">🚚</div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-indigo-800"><?= htmlspecialchars($delivery['third_party_name'] ?? '3rd-Party Delivery') ?></p>
          <p class="text-xs text-indigo-500 mt-0.5">3rd-party delivery provider</p>
        </div>
        <?php if (!empty($delivery['delivery_link'])): ?>
        <a href="<?= htmlspecialchars($delivery['delivery_link']) ?>" target="_blank"
           class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded-xl transition-colors">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Track Parcel
        </a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="flex items-center gap-4 p-4 bg-purple-50 rounded-xl">
        <?php if (!empty($delivery['rider_image'])): ?>
        <img src="<?= htmlspecialchars($delivery['rider_image']) ?>" class="size-14 rounded-2xl object-cover border-2 border-purple-200 shrink-0">
        <?php else: ?>
        <div class="size-14 rounded-2xl bg-purple-200 flex items-center justify-center text-xl shrink-0">🛵</div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($delivery['rider_name'] ?? 'Your Rider') ?></p>
          <?php if (!empty($delivery['vehicle_type'])): ?>
          <p class="text-xs text-gray-500 mt-0.5">
            <?= htmlspecialchars($delivery['vehicle_type']) ?>
            <?php if (!empty($delivery['vehicle_plate_number'])): ?>&nbsp;·&nbsp;<?= htmlspecialchars($delivery['vehicle_plate_number']) ?><?php endif; ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($delivery['variant_color'])): ?>
          <p class="text-xs text-gray-400"><?= htmlspecialchars($delivery['variant_color']) ?></p>
          <?php endif; ?>
          <?php if (!empty($delivery['organization'])): ?>
          <p class="text-xs text-indigo-600 font-medium mt-0.5"><?= htmlspecialchars($delivery['organization']) ?></p>
          <?php endif; ?>
        </div>
        <?php if (!empty($delivery['rider_phone'])): ?>
        <a href="tel:<?= htmlspecialchars($delivery['rider_phone']) ?>"
           class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-purple-700 bg-purple-100 hover:bg-purple-200 rounded-xl transition-colors">
          <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.12 6.12l1.79-1.79a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Call
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Delivery timeline -->
      <?php
        if (!empty($delivery['is_third_party'])) {
          $dlSteps = [
            ['key'=>'assigned_at', 'label'=>'Dispatched via '.htmlspecialchars($delivery['third_party_name'] ?? '3rd Party'),'icon'=>'🚚','sub'=>'3rd-party pickup requested'],
            ['key'=>'delivered_at','label'=>'Delivered','icon'=>'✅','sub'=>'Delivered to recipient'],
          ];
        } else {
          $dlSteps = [
            ['key'=>'assigned_at',  'label'=>'Assigned',  'icon'=>'📋','sub'=>'Delivery assigned to rider'],
            ['key'=>'accepted_at',  'label'=>'Accepted',  'icon'=>'👍','sub'=>'Rider accepted the delivery'],
            ['key'=>'picked_up_at', 'label'=>'Picked Up', 'icon'=>'📦','sub'=>'Package collected'],
            ['key'=>'delivered_at', 'label'=>'Delivered', 'icon'=>'✅','sub'=>'Delivered to recipient'],
          ];
        }
      ?>
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Delivery Timeline</p>
        <div class="space-y-3">
          <?php foreach ($dlSteps as $si => $step):
            $ts   = $delivery[$step['key']] ?? null;
            $done = !empty($ts);
          ?>
          <div class="flex items-start gap-3 relative after:content-[''] after:absolute after:left-[11px] after:top-6 after:-bottom-3 after:w-0.5 after:bg-gray-200 after:z-0 last:after:hidden">
            <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center text-[11px] z-10 relative <?= $done ? 'bg-orange-500 text-white' : 'bg-gray-100 border-2 border-gray-200 text-gray-400' ?>">
              <?= $done ? $step['icon'] : ($si + 1) ?>
            </div>
            <div class="flex-1 min-w-0 pb-1">
              <p class="text-xs font-semibold <?= $done ? 'text-gray-800' : 'text-gray-400' ?> leading-snug"><?= $step['label'] ?></p>
              <?php if ($done): ?>
              <p class="text-[11px] text-orange-600 font-medium mt-0.5"><?= date('M j, Y · g:i A', strtotime($ts)) ?></p>
              <?php else: ?>
              <p class="text-[11px] text-gray-400 mt-0.5"><?= $step['sub'] ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (!empty($delivery['estimated_time']) || !empty($delivery['estimated_distance'])): ?>
      <div class="flex flex-wrap gap-3">
        <?php if (!empty($delivery['estimated_time'])): ?>
        <div class="flex items-center gap-2 bg-orange-50 border border-orange-100 rounded-xl px-4 py-2.5">
          <svg class="size-4 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span class="text-xs text-orange-700 font-medium">ETA: <?= htmlspecialchars($delivery['estimated_time']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($delivery['estimated_distance'])): ?>
        <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-xl px-4 py-2.5">
          <svg class="size-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span class="text-xs text-blue-700 font-medium"><?= htmlspecialchars($delivery['estimated_distance']) ?> away</span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; /* showDelivery */ ?>

  <!-- Order receipt card -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="orderReceipt" data-aos="fade-up">
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

    <!-- Delivery / Pickup info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
      <div class="px-6 py-4">
        <?php if ($isPickup): ?>
        <!-- Pickup: show contact + store location instead of delivery address -->
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pickup Details</p>
        <p class="text-sm font-semibold text-gray-800">
          <?= htmlspecialchars($orderData['recipient_first_name'].' '.$orderData['recipient_last_name']) ?>
        </p>
        <div class="mt-2 flex items-center gap-2 text-xs text-blue-600">
          <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Store Pickup — Bulungan Ave., Navotas</span>
        </div>
        <?php if (!empty($orderData['delivery_notes'])): ?>
        <p class="text-xs text-orange-600 italic mt-1">"<?= htmlspecialchars($orderData['delivery_notes']) ?>"</p>
        <?php endif; ?>
        <?php else: ?>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Deliver To</p>
        <p class="text-sm font-semibold text-gray-800">
          <?= htmlspecialchars($orderData['recipient_first_name'].' '.$orderData['recipient_last_name']) ?>
        </p>
        <?php $addrLine = $orderData['delivery_address'] ?: ($orderData['recipient_address'] ?? ''); ?>
        <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($addrLine) ?></p>
        <p class="text-xs text-gray-500"><?= htmlspecialchars(($orderData['city'] ?? '').', '.($orderData['postal_code'] ?? '')) ?></p>
        <?php if (!empty($orderData['delivery_notes'])): ?>
        <p class="text-xs text-orange-600 italic mt-1">"<?= htmlspecialchars($orderData['delivery_notes']) ?>"</p>
        <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="px-6 py-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment</p>
        <?php
          $mBadges = ['gcash'=>'bg-blue-100 text-blue-700','paymaya'=>'bg-green-100 text-green-700','grab_pay'=>'bg-green-100 text-green-700','qrph'=>'bg-indigo-100 text-indigo-700','cod'=>'bg-orange-100 text-orange-700','card'=>'bg-purple-100 text-purple-700'];
          $mBadge = $mBadges[strtolower($orderData['payment_method'] ?? '')] ?? 'bg-gray-100 text-gray-600';
        ?>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $mBadge ?>"><?= $methodDisplay ?></span>
        <p class="text-xs text-gray-400 mt-1">Placed <?= date('F j, Y \a\t g:i A', strtotime($orderData['order_date'])) ?></p>
      </div>
    </div>

    <!-- Items -->
    <div class="border-t border-gray-100">
      <div class="px-6 py-3 bg-gray-50/60">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Items Ordered</p>
      </div>
      <div class="divide-y divide-gray-50">
        <?php foreach ($orderItems as $item):
          $unitPrice = (float)($item['price'] ?? $item['variant_price'] ?? 0);
          $lineTotal = $unitPrice * (float)($item['quantity'] ?? 1);
        ?>
        <div class="flex items-center gap-4 px-6 py-3.5">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($item['product_name']) ?></p>
            <p class="text-xs text-gray-400"><?= htmlspecialchars($item['variant_name'] ?? '—') ?></p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-sm font-bold text-gray-800">₱<?= number_format($lineTotal, 2) ?></p>
            <p class="text-xs text-gray-400">×<?= $item['quantity'] ?> @ ₱<?= number_format($unitPrice, 2) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary -->
      <div class="px-6 py-4 bg-gray-50/60 border-t border-gray-100 space-y-2">
        <div class="flex justify-between text-sm text-gray-500">
          <span>Subtotal</span>
          <span class="font-medium text-gray-800">₱<?= number_format((float)($orderData['subtotal'] ?? $orderData['total_price']), 2) ?></span>
        </div>
        <?php if (!empty($orderData['discount_amount']) && (float)$orderData['discount_amount'] > 0): ?>
        <div class="flex justify-between text-sm text-green-600">
          <span>Discount<?= !empty($orderData['voucher_code']) ? ' ('.$orderData['voucher_code'].')' : '' ?></span>
          <span class="font-medium">-₱<?= number_format((float)$orderData['discount_amount'], 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between text-sm text-gray-500">
          <span>Delivery Fee</span>
          <?php $deliveryFee = (float)($orderData['delivery_fee'] ?? 0); ?>
          <span class="font-medium <?= ($deliveryFee == 0 || $isPickup) ? 'text-green-600' : 'text-gray-800' ?>">
            <?= ($deliveryFee == 0 || $isPickup) ? ($isPickup ? 'FREE (Pickup)' : 'FREE') : '₱'.number_format($deliveryFee, 2) ?>
          </span>
        </div>
        <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
          <span>Total</span>
          <span class="text-orange-600">₱<?= number_format((float)$orderData['total_price'], 2) ?></span>
        </div>
        <div class="flex justify-between text-sm pt-1">
          <span class="text-gray-400">Payment Status</span>
          <?php if ($isCOD): ?>
            <?php if ($paymentStatusDisplay === 'Paid'): ?>
            <span class="font-semibold text-green-600">✓ Paid (<?= $isPickup ? 'Collected at Pickup' : 'COD Collected' ?>)</span>
            <?php else: ?>
            <span class="font-semibold text-amber-600">⏳ <?= $isPickup ? 'Due at Pickup' : 'Cash on Delivery' ?></span>
            <?php endif; ?>
          <?php else: ?>
            <span class="font-semibold text-green-600">✓ Paid (Online)</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Action buttons -->
  <div class="flex flex-col sm:flex-row gap-3" data-aos="fade-up">
    <a id="downloadPdfBtn"
      class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl transition-all"
      href="./functions/export_receipt.php?order_code=<?= urlencode($orderCode) ?>"
      target="_blank">
      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Download E-Receipt
    </a>
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
<?php endif; /* hasOrder */ ?>

<!-- Help strip -->
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
<script>AOS.init({ once: true, duration: 500, easing: 'ease-out', offset: 40 });</script>
<!-- Fixed: previous src pointed at a local node_modules path, which isn't served in production and 404s, so Preline's JS-driven behaviors never initialized. Pinned to jsdelivr instead. -->
<script src="https://cdn.jsdelivr.net/npm/preline@2.5.1/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>
</body>
</html>