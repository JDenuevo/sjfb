<?php
/**
 * track.php
 * Customer-facing order tracking page.
 * Stepper differs by payment method:
 *   COD:    Pending → Processing → Out for Delivery → Delivered
 *   Online: Paid    → Processing → Out for Delivery → Delivered
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

        // Delivery + rider info
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
    'card'     => 'Visa/Mastercard',
];

$statusDisplay = [
    'Paid'           => 'Payment Received',
    'Pending'        => 'Order Placed',
    'Processing'     => 'In Process',
    'OutForDelivery' => 'Out for Delivery',
    'Delivered'      => 'Delivered',
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

// ── Build stepper based on payment method ─────────────────────────────────
// Must be safe to run even when no order is loaded yet
$isCOD = ($orderData['payment_method'] ?? '') === 'cod';

if ($isCOD) {
    $steps = [
        ['key' => 'Pending',        'label' => 'Order Placed',     'icon' => '🧾'],
        ['key' => 'Processing',     'label' => 'In Process',       'icon' => '⚙️'],
        ['key' => 'OutForDelivery', 'label' => 'Out for Delivery', 'icon' => '🛵'],
        ['key' => 'Delivered',      'label' => 'Delivered',        'icon' => '✅'],
    ];
    $stepIndex = [
        'Pending'        => 0,
        'Processing'     => 1,
        'OutForDelivery' => 2,
        'Delivered'      => 3,
    ];
} else {
    $steps = [
        ['key' => 'Paid',           'label' => 'Payment Received', 'icon' => '💰'],
        ['key' => 'Processing',     'label' => 'In Process',       'icon' => '⚙️'],
        ['key' => 'OutForDelivery', 'label' => 'Out for Delivery', 'icon' => '🛵'],
        ['key' => 'Delivered',      'label' => 'Delivered',        'icon' => '✅'],
    ];
    $stepIndex = [
        'Paid'           => 0,
        'Processing'     => 1,
        'OutForDelivery' => 2,
        'Delivered'      => 3,
    ];
}

$isCancelled = ($orderData['order_status'] ?? '') === 'Cancelled';
$currentStep = $stepIndex[$orderData['order_status'] ?? ''] ?? 0;
$totalSteps  = count($steps) - 1; // 3
$fillPct     = $isCancelled ? 0 : ($totalSteps > 0 ? round(($currentStep / $totalSteps) * 100) : 0);
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
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <style>
    body { font-family: 'Lexend', sans-serif; }
    .track-hero { background: linear-gradient(135deg,#f8fafc 0%,#eef2f7 60%,#e2e8f0 100%); }
    .track-input { background:rgba(255,255,255,.7);border:1px solid rgba(148,163,184,.35);color:#0f172a; }
    .track-input::placeholder { color:#64748b; }
    .track-input:hover { background:rgba(255,255,255,.9); }
    .track-input:focus { border-color:#f97316;box-shadow:0 0 0 3px rgba(249,115,22,.15); }
    .os-stepper-wrap { background:#fff; border:1px solid #f3f4f6; border-radius:1rem; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .os-progress-track { width:100%; height:6px; background:#f3f4f6; border-radius:9999px; overflow:hidden; margin-bottom:1.5rem; }
    .os-progress-fill { height:100%; border-radius:9999px; background:linear-gradient(90deg,#f97316,#fbbf24); transition:width .7s cubic-bezier(.4,0,.2,1); }
    .os-bar-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
    .os-bar-header span:first-child { font-size:12px; font-weight:600; color:#6b7280; }
    .os-bar-header span:last-child  { font-size:12px; font-weight:700; color:#f97316; }
    .os-steps { position:relative; display:flex; align-items:flex-start; justify-content:space-between; }
    .os-connector-bg, .os-connector-fill { position:absolute; top:20px; left:20px; right:20px; height:2px; pointer-events:none; }
    .os-connector-bg   { background:#e5e7eb; z-index:0; }
    .os-connector-fill { background:linear-gradient(90deg,#f97316,#fbbf24); z-index:1; transition:width .7s cubic-bezier(.4,0,.2,1); }
    .os-step { display:flex; flex-direction:column; align-items:center; gap:8px; position:relative; z-index:10; flex:1; }
    .os-bubble { width:40px; height:40px; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:18px; line-height:1; flex-shrink:0; transition:background .3s,box-shadow .3s,border-color .3s; }
    .os-bubble.done   { background:#f97316; box-shadow:0 4px 12px rgba(249,115,22,.35); border:2px solid #f97316; color:#fff; }
    .os-bubble.active { background:#fff; border:2px solid #f97316; box-shadow:0 0 0 5px rgba(249,115,22,.15); }
    .os-bubble.idle   { background:#f9fafb; border:2px solid #e5e7eb; }
    .os-dot { width:10px; height:10px; border-radius:9999px; display:inline-block; }
    .os-dot.active { background:#fdba74; }
    .os-dot.idle   { background:#d1d5db; }
    .os-step-label { font-size:11px; text-align:center; line-height:1.3; max-width:64px; transition:color .3s; }
    .os-step-label.done   { color:#ea580c; font-weight:600; }
    .os-step-label.active { color:#f97316; font-weight:500; }
    .os-step-label.idle   { color:#9ca3af; }
    .os-status-strip { display:flex; align-items:center; gap:12px; margin-top:1.25rem; padding-top:1rem; border-top:1px solid #f3f4f6; }
    .os-status-icon { width:36px; height:36px; border-radius:10px; background:#fff7ed; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
    .os-status-strip p:first-child { font-size:14px; font-weight:600; color:#1f2937; margin:0; }
    .os-status-strip p:last-child  { font-size:12px; color:#6b7280; margin:2px 0 0; }
    .dl-step { display:flex;align-items:flex-start;gap:.75rem;position:relative; }
    .dl-step:not(:last-child)::after { content:'';position:absolute;left:11px;top:24px;bottom:-12px;width:2px;background:#e5e7eb;z-index:0; }
    .dl-dot { width:24px;height:24px;border-radius:9999px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;z-index:1;position:relative; }
    .dl-dot.done { background:#f97316;color:#fff; }
    .dl-dot.idle { background:#f3f4f6;border:2px solid #e5e7eb;color:#9ca3af; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
    .anim-1 { animation:fadeUp .5s ease both; }
    .anim-2 { animation:fadeUp .5s .1s ease both; }
    .anim-3 { animation:fadeUp .5s .2s ease both; }
    .anim-4 { animation:fadeUp .5s .3s ease both; }
    .anim-5 { animation:fadeUp .5s .4s ease both; }
    .receipt-row:not(:last-child) { border-bottom:1px solid #f3f4f6; }
  </style>
</head>

<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>

<body class="bg-gray-50">
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- ── Hero / Search ──────────────────────────────────────────────────────── -->
<section class="track-hero py-16 px-4">
  <div class="max-w-xl mx-auto text-center anim-1">
    <div class="inline-flex items-center gap-2 bg-orange-500/10 border border-orange-400/30 rounded-full px-4 py-1.5 text-xs font-semibold text-orange-600 uppercase tracking-widest mb-5">
      <svg class="size-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
      Real-time tracking
    </div>
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-3 leading-tight">Track Your Order</h1>
    <p class="text-slate-500 text-sm mb-10">Enter your order code to see live status, items, and delivery info.</p>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
      <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input type="text" name="order_code" required
               value="<?= isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : '' ?>"
               placeholder="e.g. ORD260318VLIBKU"
               class="py-3 px-4 block border track-input w-full rounded-xl text-sm focus:outline-none transition-all">
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
    'Delivered'      => 'bg-green-100 text-green-800',
    'Cancelled'      => 'bg-red-100 text-red-800',
  ];
  $osBadge = $statusBadgeConf[$orderStatus] ?? 'bg-gray-100 text-gray-700';

  $statusMessages = [
    'Paid'           => 'Payment received! Your order is waiting for admin approval.',
    'Pending'        => "Order placed! We'll confirm and start processing it shortly.",
    'Processing'     => 'Your order is being prepared and packed.',
    'OutForDelivery' => 'Your order is on the way — our rider is heading to you!',
    'Delivered'      => 'Order delivered! Thank you for choosing St. Joseph Fish Brokerage.',
    'Cancelled'      => 'This order has been cancelled. Contact us if you need help.',
  ];

  $isOutForDelivery = $orderStatus === 'OutForDelivery';
  $isDelivered      = $orderStatus === 'Delivered';
  $showDelivery     = $delivery && ($isOutForDelivery || $isDelivered);

  // Payment status from orders table (fallback) — we fetch from payments if needed
  // For display we just show the payment_method context
  $paymentStatusDisplay = $orderData['payment_status'] ?? 'Pending';
?>

<section class="py-10 px-4">
<div class="max-w-3xl mx-auto space-y-5">

  <!-- ── Order header banner ──────────────────────────────────────────────── -->
  <div class="anim-2 relative overflow-hidden bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-6 text-white shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
      <div>
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Order Reference</p>
        <div class="flex items-center gap-3 flex-wrap">
          <h2 class="text-2xl font-bold text-orange-500"><?= htmlspecialchars($orderData['order_code']) ?></h2>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $osBadge ?>">
            <?= $statusDisplay[$orderStatus] ?? $orderStatus ?>
          </span>
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
  <!-- ── Cancelled ────────────────────────────────────────────────────────── -->
  <div class="anim-3 bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
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
  <!-- ── Progress stepper ─────────────────────────────────────────────────── -->
  <div class="anim-3 os-stepper-wrap">
    <div class="os-bar-header">
      <span>Delivery Progress</span>
      <span><?= $fillPct ?>% complete</span>
    </div>
    <div class="os-progress-track">
      <div class="os-progress-fill" style="width:<?= $fillPct ?>%"></div>
    </div>
    <div class="os-steps">
      <div class="os-connector-bg"></div>
      <div class="os-connector-fill" style="width:<?= $totalSteps > 0 ? min(100, round(($currentStep / $totalSteps) * 100)) : 0 ?>%"></div>
      <?php foreach ($steps as $i => $step):
        $done   = $i <= $currentStep;
        $active = $i === $currentStep;
        $sc     = $done ? 'done' : ($active ? 'active' : 'idle');
      ?>
      <div class="os-step">
        <div class="os-bubble <?= $sc ?>">
          <?php if ($done): echo $step['icon'];
          else: ?><span class="os-dot <?= $active ? 'active' : 'idle' ?>"></span><?php endif; ?>
        </div>
        <span class="os-step-label <?= $sc ?>"><?= htmlspecialchars($step['label']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="os-status-strip">
      <div class="os-status-icon"><?= $steps[$currentStep]['icon'] ?></div>
      <div>
        <p><?= $statusDisplay[$orderStatus] ?? $orderStatus ?></p>
        <p><?= $statusMessages[$orderStatus] ?? '' ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Delivery / Rider section ─────────────────────────────────────────── -->
  <?php if ($showDelivery): ?>
  <div class="anim-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
      <span class="size-7 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-sm">🛵</span>
      <div>
        <h3 class="text-sm font-semibold text-gray-800">
          <?= !empty($delivery['is_third_party']) ? '3rd-Party Delivery' : 'Rider Information' ?>
        </h3>
        <?php
          $dlStatus = $delivery['delivery_status'] ?? '';
        ?>
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
      <!-- 3rd-party provider card -->
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
      <!-- Registered rider card -->
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

      <!-- Delivery timestamp timeline -->
      <?php
        if (!empty($delivery['is_third_party'])) {
          $dlSteps = [
            ['key' => 'assigned_at',  'label' => 'Dispatched via '.htmlspecialchars($delivery['third_party_name'] ?? '3rd Party'), 'icon' => '🚚', 'sub' => '3rd-party pickup requested'],
            ['key' => 'delivered_at', 'label' => 'Delivered',  'icon' => '✅', 'sub' => 'Delivered to recipient'],
          ];
        } else {
          $dlSteps = [
            ['key' => 'assigned_at',  'label' => 'Assigned',   'icon' => '📋', 'sub' => 'Delivery assigned to rider'],
            ['key' => 'accepted_at',  'label' => 'Accepted',   'icon' => '👍', 'sub' => 'Rider accepted the delivery'],
            ['key' => 'picked_up_at', 'label' => 'Picked Up',  'icon' => '📦', 'sub' => 'Package collected'],
            ['key' => 'delivered_at', 'label' => 'Delivered',  'icon' => '✅', 'sub' => 'Delivered to recipient'],
          ];
        }
        $lastDone = -1;
        foreach ($dlSteps as $si => $step) {
          if (!empty($delivery[$step['key']])) $lastDone = $si;
        }
      ?>
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Delivery Timeline</p>
        <div class="space-y-3">
          <?php foreach ($dlSteps as $si => $step):
            $ts   = $delivery[$step['key']] ?? null;
            $done = !empty($ts);
          ?>
          <div class="dl-step">
            <div class="dl-dot <?= $done ? 'done' : 'idle' ?>"><?= $done ? $step['icon'] : ($si + 1) ?></div>
            <div class="flex-1 min-w-0 pb-1">
              <p class="text-xs font-semibold <?= $done ? 'text-gray-800' : 'text-gray-400' ?> leading-snug">
                <?= $step['label'] ?>
              </p>
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

      <!-- ETA if available -->
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

  <!-- ── Order receipt card ───────────────────────────────────────────────── -->
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

    <!-- Delivery + payment info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
      <div class="px-6 py-4">
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
      </div>
      <div class="px-6 py-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment</p>
        <?php
          $mBadges = [
            'gcash'    => 'bg-blue-100 text-blue-700',
            'paymaya'  => 'bg-green-100 text-green-700',
            'grab_pay' => 'bg-green-100 text-green-700',
            'qrph'     => 'bg-indigo-100 text-indigo-700',
            'cod'      => 'bg-orange-100 text-orange-700',
            'card'     => 'bg-purple-100 text-purple-700',
          ];
          $mBadge = $mBadges[strtolower($orderData['payment_method'] ?? '')] ?? 'bg-gray-100 text-gray-600';
        ?>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $mBadge ?>"><?= $methodDisplay ?></span>
        <p class="text-xs text-gray-400 mt-1">
          Placed <?= date('F j, Y \a\t g:i A', strtotime($orderData['order_date'])) ?>
        </p>
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
        <div class="receipt-row flex items-center gap-4 px-6 py-3.5">
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

      <!-- Order summary breakdown -->
      <div class="px-6 py-4 bg-gray-50/60 border-t border-gray-100 space-y-2">
        <div class="flex justify-between text-sm text-gray-500">
          <span>Subtotal</span>
          <span class="font-medium text-gray-800">
            ₱<?= number_format((float)($orderData['subtotal'] ?? $orderData['total_price']), 2) ?>
          </span>
        </div>

        <?php if (!empty($orderData['discount_amount']) && (float)$orderData['discount_amount'] > 0): ?>
        <div class="flex justify-between text-sm text-green-600">
          <span>
            Discount
            <?php if (!empty($orderData['voucher_code'])): ?>
            <span class="text-xs font-normal text-green-500">(<?= htmlspecialchars($orderData['voucher_code']) ?>)</span>
            <?php endif; ?>
          </span>
          <span class="font-medium">-₱<?= number_format((float)$orderData['discount_amount'], 2) ?></span>
        </div>
        <?php endif; ?>

        <div class="flex justify-between text-sm text-gray-500">
          <span>Delivery Fee</span>
          <?php $deliveryFee = (float)($orderData['delivery_fee'] ?? 0); ?>
          <span class="font-medium <?= $deliveryFee == 0 ? 'text-green-600' : 'text-gray-800' ?>">
            <?= $deliveryFee == 0 ? 'FREE' : '₱'.number_format($deliveryFee, 2) ?>
          </span>
        </div>

        <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
          <span>Total</span>
          <span class="text-orange-600">₱<?= number_format((float)$orderData['total_price'], 2) ?></span>
        </div>

        <!-- Payment status -->
        <div class="flex justify-between text-sm pt-1">
          <span class="text-gray-400">Payment Status</span>
          <?php if ($isCOD): ?>
            <?php if ($paymentStatusDisplay === 'Paid'): ?>
            <span class="font-semibold text-green-600">✓ Paid (COD Collected)</span>
            <?php else: ?>
            <span class="font-semibold text-amber-600">⏳ Cash on Delivery</span>
            <?php endif; ?>
          <?php else: ?>
            <span class="font-semibold text-green-600">✓ Paid (Online)</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- /orderReceipt -->

  <!-- ── Action buttons ──────────────────────────────────────────────────── -->
  <div class="flex flex-col sm:flex-row gap-3 anim-5">
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
<?php endif; /* hasOrder */ ?>

<!-- ── Help strip ─────────────────────────────────────────────────────────── -->
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