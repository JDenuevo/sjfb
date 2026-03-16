<?php
/**
 * supadmin/order_manage.php
 *
 * Full order detail/management page.
 * Replaces order_manage.php + order_summary.php (merged — no separate component needed).
 *
 * ALL form actions POST to functions/order_process.php with the correct action= field:
 *   approve_order       — Paid or Pending → Processing (stock check inside order_helper)
 *   assign_rider        — registered rider → deliveries row (pending_acceptance)
 *   assign_third_party  — Lalamove/3rd-party → OutForDelivery immediately
 *   send_out_for_delivery — manual push to OutForDelivery (admin override)
 *   mark_delivered      — OutForDelivery → Delivered (admin override)
 *   cancel_order        — any → Cancelled (restores stock)
 *
 * fetch_orders.php is fully replaced: item data comes from order_process.php action=get_order_detail.
 */
session_start();
include '../conn.php';
require_once '../functions/order_helper.php';

// ── Auth ──────────────────────────────────────────────────────────────────
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php');
    exit;
}

$actor_id   = (int)$_SESSION['account_id'];
$actor_role = 'super_admin'; // page is supadmin only

// ── Validate order ID ─────────────────────────────────────────────────────
if (empty($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid order ID.'];
    header('Location: orders.php');
    exit;
}
$order_id = (int)$_GET['order_id'];

// ── Fetch all data via order_helper functions ─────────────────────────────
$order   = getOrderFull($order_id, $conn);
if (!$order) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found.'];
    header('Location: orders.php');
    exit;
}
$order_items = getOrderItems($order_id, $conn);
$history     = getOrderHistory($order_id, $conn);
$proofs      = getDeliveryProofs($order_id, $conn);
$riders      = getRidersList($conn); // all riders — assign form lets admin pick

// ── Review invite: read-only ─────────────────────────────────────────────
// The invite is created exclusively by review_helper.php when delivery is
// confirmed (tied to the SMS/Semaphore + SMTP pipeline). order_manage.php
// only reads the existing row. Use the Regenerate button to re-create it.
$reviewInvite = null;
if ($order['order_status'] === 'Delivered') {
    $riSt = $conn->prepare("SELECT * FROM review_invites WHERE order_id = ? ORDER BY sent_at DESC LIMIT 1");
    $riSt->bind_param('i', $order_id);
    $riSt->execute();
    $reviewInvite = $riSt->get_result()->fetch_assoc();
}
// ── Also fetch existing reviews for this order ─────────────────────────────
$orderReviews = [];
if ($order['order_status'] === 'Delivered') {
    $revSt = $conn->prepare("
        SELECT r.review_id, r.product_id, r.full_name, r.rating, r.feedback,
               r.status, r.created_at, p.product_name
        FROM reviews r
        JOIN products p ON p.product_id = r.product_id
        WHERE r.order_id = ?
        ORDER BY r.created_at DESC
    ");
    $revSt->bind_param('i', $order_id);
    $revSt->execute();
    $orderReviews = $revSt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Merge feed (status history + activity log + delivery events) ──────────
$feed = [];

// 1. Status changes
foreach ($history as $t) {
    $ol    = STATUS_LABELS[$t['old_status']] ?? $t['old_status'];
    $nl    = STATUS_LABELS[$t['new_status']] ?? $t['new_status'];
    
    // Build actor name safely
    $actor = 'System';
    if (!empty($t['first_name'])) {
        $actor = trim($t['first_name'] . ' ' . ($t['last_name'] ?? ''));
    } elseif (!empty($t['changed_by_user_type'])) {
        $actor = ucfirst($t['changed_by_user_type']);
    }
    
    $feed[] = [
        'type'       => 'status',
        'action'     => 'status_change',
        'label'      => "Status changed: {$ol} → {$nl}",
        'sub'        => $t['notes'] ?? '',
        'old_val'    => $ol,
        'new_val'    => $nl,
        'actor'      => $actor,
        'actor_role' => $t['changed_by_user_type'] ?? 'system',
        'ip'         => null,
        'ts'         => $t['created_at'],
    ];
}

// 2. Activity log
$al = $conn->prepare("
    SELECT al.*, CONCAT(a.first_name,' ',a.last_name) AS actor_name
    FROM activity_log al
    LEFT JOIN accounts a ON a.account_id = al.user_id
    WHERE al.entity_type = 'order' AND al.entity_id = ?
    ORDER BY al.created_at ASC LIMIT 100
");
$al->bind_param('i', $order_id);
$al->execute();
$activity_rows = $al->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($activity_rows as $l) {
    $actor    = trim($l['actor_name'] ?? '') ?: ucfirst($l['user_type'] ?? 'system');
    $oldParsed = $l['old_value'] ?? null;
    $newParsed = $l['new_value'] ?? null;
    // If JSON-encoded, decode for nicer display
    if ($oldParsed && $oldParsed[0] === '{') { $d = json_decode($oldParsed, true); if ($d) $oldParsed = implode(', ', array_map(fn($k,$v) => "{$k}: {$v}", array_keys($d), $d)); }
    if ($newParsed && $newParsed[0] === '{') { $d = json_decode($newParsed, true); if ($d) $newParsed = implode(', ', array_map(fn($k,$v) => "{$k}: {$v}", array_keys($d), $d)); }
    $feed[] = [
        'type'       => 'activity',
        'action'     => $l['action'] ?? 'log',
        'label'      => ucfirst(str_replace('_', ' ', $l['action'] ?? 'Activity')),
        'sub'        => $l['details'] ?? '',
        'old_val'    => $oldParsed,
        'new_val'    => $newParsed,
        'actor'      => $actor,
        'actor_role' => $l['user_type'] ?? 'system',
        'ip'         => $l['ip_address'] ?? null,
        'ts'         => $l['created_at'],
    ];
}

// 3. Delivery events (assigned, accepted, picked_up, delivered timestamps)
$dlQ = $conn->prepare("
    SELECT d.delivery_id, d.assigned_at, d.accepted_at, d.picked_up_at, d.delivered_at,
           d.is_third_party, d.third_party_name, d.status,
           COALESCE(r.full_name, CONCAT(ra.first_name,' ',ra.last_name)) AS rider_name
    FROM deliveries d
    LEFT JOIN riders r   ON r.rider_id = d.rider_id
    LEFT JOIN accounts ra ON ra.account_id = r.account_id
    WHERE d.order_id = ?
    ORDER BY d.assigned_at ASC
");
$dlQ->bind_param('i', $order_id);
$dlQ->execute();
$dlRows = $dlQ->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($dlRows as $dl) {
    $rname = $dl['is_third_party'] ? ($dl['third_party_name'] ?? '3rd Party') : ($dl['rider_name'] ?? 'Rider');
    if ($dl['assigned_at']) $feed[] = ['type'=>'delivery','action'=>'rider_assigned',  'label'=>"Rider assigned: {$rname}", 'sub'=>$dl['is_third_party']?'3rd-party delivery':'Registered rider', 'old_val'=>null,'new_val'=>$rname,'actor'=>'System','actor_role'=>'system','ip'=>null,'ts'=>$dl['assigned_at']];
    if ($dl['accepted_at']) $feed[] = ['type'=>'delivery','action'=>'rider_accept',    'label'=>"{$rname} accepted the delivery", 'sub'=>'Order moved to Out for Delivery', 'old_val'=>null,'new_val'=>null,'actor'=>$rname,'actor_role'=>'rider','ip'=>null,'ts'=>$dl['accepted_at']];
    if ($dl['picked_up_at'])$feed[] = ['type'=>'delivery','action'=>'rider_pickup',    'label'=>"{$rname} picked up the order", 'sub'=>'Package collected from warehouse', 'old_val'=>null,'new_val'=>null,'actor'=>$rname,'actor_role'=>'rider','ip'=>null,'ts'=>$dl['picked_up_at']];
    if ($dl['delivered_at'])$feed[] = ['type'=>'delivery','action'=>'mark_delivered',  'label'=>"Delivered by {$rname}", 'sub'=>'Customer received the order', 'old_val'=>null,'new_val'=>null,'actor'=>$rname,'actor_role'=>'rider','ip'=>null,'ts'=>$dl['delivered_at']];
}

// 4. Proof uploads
$proofQ = $conn->prepare("SELECT dp.uploaded_at, dp.caption, dp.file_path, COALESCE(r.full_name, CONCAT(a.first_name,' ',a.last_name)) AS rider_name FROM delivery_proofs dp LEFT JOIN riders r ON r.rider_id=dp.rider_id LEFT JOIN accounts a ON a.account_id=r.account_id WHERE dp.order_id=? ORDER BY dp.uploaded_at ASC");
$proofQ->bind_param('i', $order_id);
$proofQ->execute();
$proofEvts = $proofQ->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($proofEvts as $pe) {
    $feed[] = ['type'=>'proof','action'=>'upload_proof','label'=>'Delivery proof uploaded','sub'=>$pe['caption']??'','old_val'=>null,'new_val'=>$pe['file_path'],'actor'=>$pe['rider_name']??'Rider','actor_role'=>'rider','ip'=>null,'ts'=>$pe['uploaded_at']];
}

usort($feed, fn($a,$b) => strtotime($a['ts']) <=> strtotime($b['ts']));

// ── Display config ─────────────────────────────────────────────────────────
$statusConf = [
    'Paid'           => ['badge' => 'bg-green-100 text-green-800', 'card' => 'bg-green-50 border-green-200'],
    'Pending'        => ['badge' => 'bg-yellow-100 text-yellow-800', 'card' => 'bg-yellow-50 border-yellow-200'],
    'Processing'     => ['badge' => 'bg-blue-100 text-blue-800', 'card' => 'bg-blue-50 border-blue-200'],
    'OutForDelivery' => ['badge' => 'bg-purple-100 text-purple-800', 'card' => 'bg-purple-50 border-purple-200'],
    'Delivered'      => ['badge' => 'bg-green-100 text-green-800', 'card' => 'bg-green-50 border-green-200'],
    'Cancelled'      => ['badge' => 'bg-red-100 text-red-800', 'card' => 'bg-red-50 border-red-200'],
];

$statusLabels = [
    'Paid' => 'Paid - Awaiting Approval',
    'Pending' => 'Pending Payment',
    'Processing' => 'Processing',
    'OutForDelivery' => 'Out for Delivery',
    'Delivered' => 'Delivered',
    'Cancelled' => 'Cancelled'
];

// Update the steps array to include 'Paid'
$steps = [
    ['key' => 'Paid',           'label' => 'Payment Received', 'icon' => '💰'],
    ['key' => 'Processing',     'label' => 'Processing',       'icon' => '⚙️'],
    ['key' => 'OutForDelivery', 'label' => 'Out for Delivery', 'icon' => '🛵'],
    ['key' => 'Delivered',      'label' => 'Delivered',        'icon' => '✅'],
];

$stepIndex = [
    'Paid' => 0,
    'Processing' => 1,
    'OutForDelivery' => 2,
    'Delivered' => 3
];

$paymentConf  = ['Paid'=>'bg-green-100 text-green-700','Pending'=>'bg-yellow-100 text-yellow-700','Failed'=>'bg-red-100 text-red-700','Refunded'=>'bg-blue-100 text-blue-700'];
$methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','qrph'=>'QR Ph','cod'=>'Cash on Delivery','card'=>'Card'];

// FIXED: Access the 'badge' key from the array
$osBadge       = $statusConf[$order['order_status']]['badge'] ?? 'bg-gray-100 text-gray-700';
$osCard        = $statusConf[$order['order_status']]['card'] ?? 'bg-gray-50 border-gray-100';

$psClass       = $paymentConf[$order['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
$methodDisplay = $methodLabels[$order['payment_method'] ?? ''] ?? ucfirst($order['payment_method'] ?? '—');

$currentStep = $stepIndex[$order['order_status']] ?? 0;
$fillPct     = $currentStep > 0 ? round(($currentStep / (count($steps)-1)) * 100) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order <?= htmlspecialchars($order['order_code']) ?> | SJFBI Admin</title>
  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <style>
    body { font-family: 'Lexend', sans-serif; }
    /* Stepper */
    .step-bubble { width:40px;height:40px;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;transition:all .3s; }
    .step-bubble.done   { background:#f97316;box-shadow:0 4px 12px rgba(249,115,22,.3);border:2px solid #f97316;color:#fff; }
    .step-bubble.active { background:#fff;border:2px solid #f97316; }
    .step-bubble.idle   { background:#f9fafb;border:2px solid #e5e7eb; }
    .step-dot { width:10px;height:10px;border-radius:9999px;display:inline-block; }
    /* Modals — hidden by default, .modal-open shows them */
    .modal-overlay { display:none; position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;align-items:center;justify-content:center;padding:1rem; }
    .modal-overlay.modal-open { display:flex; }
    .modal-box { background:#fff;border-radius:1.25rem;padding:1.5rem;width:100%;max-width:440px;position:relative;z-index:50; }
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<!-- Toast -->
<div id="toast-wrap" class="fixed bottom-5 right-5 flex flex-col gap-2 z-50"></div>

<!-- ── Cancel modal ─────────────────────────────────────────────────────── -->
<div id="cancel-modal" class="modal-overlay">
  <div class="modal-box">
    <h3 class="text-base font-bold text-gray-800 mb-1">Cancel Order</h3>
    <p class="text-xs text-gray-500 mb-4">Provide a reason. Stock will be restored if already deducted.</p>
    <textarea id="cancel-reason" rows="3" placeholder="Reason for cancellation…"
              class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400 resize-none mb-4"></textarea>
    <div class="flex gap-2 justify-end">
      <button onclick="closeModal('cancel-modal')" class="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">No, keep it</button>
      <button onclick="submitCancel()" class="px-4 py-2 text-sm bg-red-600 text-white rounded-xl hover:bg-red-500">Yes, cancel order</button>
    </div>
  </div>
</div>

<!-- ── 3rd-party delivery modal ─────────────────────────────────────────── -->
<div id="thirdparty-modal" class="modal-overlay">
  <div class="modal-box">
    <h3 class="text-base font-bold text-gray-800 mb-1">3rd-Party Delivery</h3>
    <p class="text-xs text-gray-500 mb-4">Use this for Lalamove, Foodpanda, etc. Order moves to Out for Delivery immediately.</p>
    <div class="space-y-3">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Provider Name <span class="text-red-500">*</span></label>
        <input id="tp-name" type="text" placeholder="e.g. Lalamove, Foodpanda"
               class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Tracking Link <span class="text-gray-400">(optional)</span></label>
        <input id="tp-link" type="url" placeholder="https://…"
               class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
        <input id="tp-notes" type="text" placeholder="e.g. Booking ref, driver name"
               class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
      </div>
    </div>
    <div class="flex gap-2 justify-end mt-4">
      <button onclick="closeModal('thirdparty-modal')" class="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
      <button onclick="submitThirdParty()" class="px-4 py-2 text-sm bg-purple-600 text-white rounded-xl hover:bg-purple-500">Dispatch Now</button>
    </div>
  </div>
</div>

<div class="w-full lg:ps-64">
<div class="p-4 sm:p-6 space-y-5">

  <?php if (!empty($_SESSION['message'])):
    $msg = $_SESSION['message']; unset($_SESSION['message']);
    $cls = $msg['type'] === 'success' ? 'bg-teal-500' : 'bg-red-500';
  ?>
  <div class="<?= $cls ?> text-white text-sm rounded-xl p-4">
    <span class="font-bold"><?= ucfirst($msg['type']) ?>!</span> <?= htmlspecialchars($msg['text']) ?>
  </div>
  <?php endif; ?>

  <!-- Back + date -->
  <div class="flex items-center justify-between">
    <a href="orders.php" class="flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
      Back to Orders
    </a>
    <span class="text-xs text-gray-400"><?= date('F j, Y · g:i A', strtotime($order['order_date'])) ?></span>
  </div>

  <!-- Order header banner -->
  <div class="relative overflow-hidden bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-5 text-white shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 relative z-10">
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl font-bold text-orange-500"><?= htmlspecialchars($order['order_code']) ?></h1>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $osBadge ?>">
            <?= STATUS_LABELS[$order['order_status']] ?>
          </span>
          <?php if (!empty($order['is_third_party'])): ?>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
            3rd-Party: <?= htmlspecialchars($order['third_party_name'] ?? '') ?>
          </span>
          <?php endif; ?>
        </div>
        <p class="text-gray-400 text-sm mt-1">
          <?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?>
          · <?= htmlspecialchars($order['email']) ?>
        </p>
      </div>
      <div class="text-right">
        <div class="text-2xl font-bold text-green-400">₱<?= number_format($order['total_price'], 2) ?></div>
        <div class="text-sm text-gray-400"><?= $methodDisplay ?></div>
      </div>
    </div>
    <div class="absolute -top-4 -right-4 size-24 bg-white/5 rounded-full pointer-events-none"></div>
  </div>

  <!-- Progress stepper -->
  <?php if ($order['order_status'] !== 'Cancelled'): ?>
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-2">
      <span class="text-xs font-semibold text-gray-500">Delivery Progress</span>
      <span class="text-xs font-bold text-orange-500"><?= $fillPct ?>% complete</span>
    </div>
    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-5">
      <div class="h-full bg-gradient-to-r from-orange-500 to-yellow-400 rounded-full transition-all duration-700" style="width:<?= $fillPct ?>%"></div>
    </div>
    <div class="relative flex justify-between">
      <div class="absolute top-5 left-5 right-5 h-0.5 bg-gray-200"></div>
      <div class="absolute top-5 left-5 h-0.5 bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-700" style="width:calc(<?= $fillPct ?>% - 20px)"></div>
      <?php foreach ($steps as $i => $step):
        $done   = $currentStep >= $i;
        $active = $currentStep === $i;
        $cls    = $done ? 'done' : ($active ? 'active' : 'idle');
      ?>
      <div class="flex flex-col items-center gap-2 relative z-10">
        <div class="step-bubble <?= $cls ?>">
          <?php if ($done): echo $step['icon'];
          else: ?><span class="step-dot <?= $active ? 'bg-orange-200' : 'bg-gray-300' ?>"></span><?php endif; ?>
        </div>
        <span class="text-[11px] text-center max-w-16 leading-tight <?= $done ? 'text-orange-600 font-semibold' : ($active ? 'text-orange-500 font-medium' : 'text-gray-400') ?>">
          <?= $step['label'] ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="bg-red-50 border border-red-200 rounded-2xl p-5 text-center">
    <div class="size-10 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
      <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </div>
    <p class="text-sm font-semibold text-red-700">Order Cancelled</p>
    <?php if (!empty($order['cancel_reason'])): ?>
    <p class="text-xs text-red-500 mt-1"><?= htmlspecialchars($order['cancel_reason']) ?></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Main grid -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- ── LEFT COL ───────────────────────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-5">

      <!-- Order Items -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="text-base font-semibold text-gray-800">Order Items</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Variant</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Qty</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Price</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($order_items as $item):
              $lineTotal = $item['quantity'] * $item['price'];
            ?>
            <tr class="hover:bg-orange-50/20 transition-colors">
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <?php if (!empty($item['image_path'])): ?>
                  <img src="../<?= htmlspecialchars($item['image_path']) ?>" class="size-10 rounded-lg object-cover shrink-0" onerror="this.style.display='none'">
                  <?php else: ?>
                  <div class="size-10 rounded-lg bg-gray-100 shrink-0"></div>
                  <?php endif; ?>
                  <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['product_name'] ?? '—') ?></span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full"><?= htmlspecialchars($item['variant_name'] ?? '—') ?></span>
                <?php if (!empty($item['stock_quantity']) && $item['stock_quantity'] < 5): ?>
                <span class="text-xs text-orange-500 ml-1">(<?= $item['stock_quantity'] ?> left)</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="text-sm font-semibold text-gray-800">×<?= $item['quantity'] ?></span>
              </td>
              <td class="px-4 py-3 text-right text-sm text-gray-600">₱<?= number_format($item['price'], 2) ?></td>
              <td class="px-4 py-3 text-right text-sm font-bold text-gray-800">₱<?= number_format($lineTotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="4" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Order Total</td>
              <td class="px-4 py-3 text-right text-base font-bold text-orange-600">₱<?= number_format($order['total_price'], 2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Delivery Address -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Delivery Address</h3>
        <div class="flex items-start gap-3">
          <div class="size-9 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
            <svg class="size-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div class="text-sm text-gray-600 space-y-0.5">
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></p>
            <?php $addr = $order['delivery_address'] ?: $order['address']; ?>
            <p><?= htmlspecialchars($addr) ?></p>
            <p><?= htmlspecialchars($order['city'].', '.$order['postal_code']) ?></p>
            <?php if (!empty($order['delivery_notes'])): ?>
            <p class="text-orange-600 text-xs italic mt-1">"<?= htmlspecialchars($order['delivery_notes']) ?>"</p>
            <?php endif; ?>
            <?php if (!empty($order['active_delivery_link'])): ?>
            <a href="<?= htmlspecialchars($order['active_delivery_link']) ?>" target="_blank"
               class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline mt-1">
              <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              View 3rd-Party Tracking
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ── ACTION BUTTONS ─────────────────────────────────────────────
          All forms POST to order_process.php with action= field matching
          the switch cases in order_process.php.
      ──────────────────────────────────────────────────────────────────── -->
      <?php if (!in_array($order['order_status'], ['Delivered','Cancelled'])): ?>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 space-y-4">
          <div>
              <h3 class="text-base font-semibold text-gray-800">Manage Order</h3>
              <p class="text-xs text-gray-400 mt-0.5">
                  Current: <span class="font-semibold text-gray-600"><?= STATUS_LABELS[$order['order_status']] ?></span>
              </p>
          </div>

          <div class="flex flex-wrap gap-2">
              
              <?php if ($order['order_status'] === 'Paid'): ?>
              <!-- PAID - Awaiting approval -->
              <button onclick="approveOrder()" id="approve-btn"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-500 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Approve & Process Order
              </button>
              <p class="w-full text-xs text-gray-400 -mt-1">Payment received. Approve to start processing and deduct stock.</p>

              <?php elseif ($order['order_status'] === 'Processing'): ?>
              <!-- PROCESSING - Ready for rider assignment -->
              <button onclick="openModal('assign-modal')"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  Assign Rider
              </button>

              <button onclick="openModal('thirdparty-modal')"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                  3rd-Party Delivery
              </button>

              <?php if (!empty($order['assigned_rider_id'])): ?>
              <button onclick="sendOutForDelivery()"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-500 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>
                  Send Out for Delivery
              </button>
              <?php endif; ?>

              <?php elseif ($order['order_status'] === 'OutForDelivery'): ?>
              <!-- OUT FOR DELIVERY - Mark as delivered -->
              <button onclick="markDelivered()"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-500 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                  Mark as Delivered
              </button>
              <p class="w-full text-xs text-gray-400 -mt-1">Use this if the rider is unregistered or customer didn't confirm receipt.</p>

              <?php endif; ?>

              <!-- Cancel is always visible for non-terminal statuses -->
              <button onclick="openModal('cancel-modal')"
                      class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 border border-gray-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 rounded-xl transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  Cancel Order
              </button>

          </div>
      </div>
      <?php endif; ?>

      <!-- Delivery Proofs -->
      <?php if (!empty($proofs)): ?>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Delivery Proofs</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <?php foreach ($proofs as $proof): ?>
          <a href="../<?= htmlspecialchars($proof['file_path']) ?>" target="_blank" class="group relative rounded-xl overflow-hidden border border-gray-100">
            <img src="../<?= htmlspecialchars($proof['file_path']) ?>" class="w-full h-28 object-cover group-hover:opacity-90 transition-opacity">
            <div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-xs px-2 py-1 truncate">
              <?= htmlspecialchars($proof['rider_name'] ?? '—') ?> · <?= date('M j g:i A', strtotime($proof['uploaded_at'])) ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── Review Invitation (only when Delivered) ────────────────────── -->
      <?php if ($order['order_status'] === 'Delivered'): ?>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="size-8 rounded-xl bg-yellow-100 flex items-center justify-center text-base">⭐</div>
            <div>
              <h3 class="text-base font-semibold text-gray-800">Review Invitation</h3>
              <p class="text-xs text-gray-400 mt-0.5">Send a review link to the customer</p>
            </div>
          </div>
          <?php if (!empty($orderReviews)): ?>
          <span class="text-xs bg-green-100 text-green-700 font-semibold px-2.5 py-1 rounded-full">
            <?= count($orderReviews) ?> review<?= count($orderReviews) > 1 ? 's' : '' ?> received
          </span>
          <?php else: ?>
          <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">No reviews yet</span>
          <?php endif; ?>
        </div>

        <div class="px-5 py-4 space-y-4">

          <!-- Customer info row -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
            <div class="size-9 rounded-full bg-orange-100 flex items-center justify-center text-sm font-bold text-orange-600 shrink-0">
              <?= strtoupper(substr($order['first_name'],0,1).substr($order['last_name'],0,1)) ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></p>
              <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($order['email']) ?></p>
            </div>
            <span class="text-xs text-gray-400 shrink-0">
              Delivered <?= !empty($order['delivered_at']) ? date('M j, Y', strtotime($order['delivered_at'])) : date('M j, Y', strtotime($order['updated_at'])) ?>
            </span>
          </div>

          <!-- Review link copy box -->
          <?php if ($reviewInvite): ?>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Review Link</label>
            <div class="flex items-center gap-2">
              <input id="review-link-input" type="text" readonly
                     value="<?= htmlspecialchars($reviewInvite['review_url']) ?>"
                     class="flex-1 text-xs font-mono border border-gray-200 rounded-xl px-3 py-2.5 bg-gray-50 text-gray-600 outline-none focus:border-orange-300 min-w-0">
              <button onclick="copyReviewLink()"
                      id="copy-btn"
                      class="shrink-0 flex items-center gap-1.5 px-3 py-2.5 text-xs font-semibold bg-orange-500 hover:bg-orange-600 text-white rounded-xl transition-colors">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy
              </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-1.5">Generated <?= date('M j, Y · g:i A', strtotime($reviewInvite['sent_at'])) ?></p>
          </div>

          <!-- Quick action buttons -->
          <div class="flex flex-wrap gap-2">
            <a href="mailto:<?= urlencode($order['email']) ?>?subject=<?= urlencode('How was your order '.$order['order_code'].'?') ?>&body=<?= urlencode("Hi ".$order['first_name'].",\n\nThank you for your order ".$order['order_code']."! We hope you enjoyed your purchase.\n\nWe'd love to hear your feedback. Please take a moment to leave a review:\n".$reviewInvite['review_url']."\n\nThank you!\nSt. Joseph Fish Brokerage Inc.") ?>"
               class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-colors">
              <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              Open in Mail
            </a>
            <a href="<?= htmlspecialchars($reviewInvite['review_url']) ?>" target="_blank"
               class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors">
              <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Preview Link
            </a>
            <button onclick="regenerateReviewLink()"
                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-500 hover:text-red-600 bg-gray-50 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-xl transition-colors">
              <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
              Regenerate
            </button>
          </div>
          <?php else: ?>
          <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-center">
            <p class="text-xs text-gray-400">Review invite link not yet generated.</p>
            <p class="text-[11px] text-gray-300 mt-0.5">It is created automatically when delivery is confirmed via the notification pipeline.</p>
          </div>
          <?php endif; ?>

          <!-- Existing reviews for this order -->
          <?php if (!empty($orderReviews)): ?>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Received Reviews</p>
            <div class="space-y-2">
              <?php foreach ($orderReviews as $rev):
                $statusBadge = match($rev['status']) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    'spam'     => 'bg-gray-100 text-gray-500',
                    default    => 'bg-yellow-100 text-yellow-700',
                };
              ?>
              <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <div class="shrink-0 mt-0.5">
                  <div class="flex gap-0.5">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="text-xs <?= $s <= $rev['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
                    <?php endfor; ?>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($rev['product_name']) ?></p>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold <?= $statusBadge ?>"><?= ucfirst($rev['status']) ?></span>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?= htmlspecialchars($rev['feedback']) ?></p>
                  <p class="text-[10px] text-gray-400 mt-1"><?= htmlspecialchars($rev['full_name']) ?> · <?= date('M j, Y', strtotime($rev['created_at'])) ?></p>
                </div>
                <a href="reviews.php?review_id=<?= $rev['review_id'] ?>"
                   class="shrink-0 text-[10px] text-orange-500 hover:underline font-medium mt-1">View →</a>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
      <?php endif; ?>

      <!-- Order History feed -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-800">Order History &amp; Logs</h3>
            <p class="text-xs text-gray-400 mt-0.5">All events for <?= htmlspecialchars($order['order_code']) ?></p>
          </div>
          <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full"><?= count($feed) ?> events</span>
        </div>
        <?php if (empty($feed)): ?>
        <div class="px-5 py-8 text-center text-sm text-gray-400">No history recorded yet.</div>
        <?php else:
          $actionConf = [
            'status_change'     => ['dot'=>'bg-orange-500','icon'=>'→','card'=>'bg-orange-50 border-orange-100','text'=>'text-orange-700'],
            'approve_order'     => ['dot'=>'bg-teal-500',  'icon'=>'✓','card'=>'bg-teal-50 border-teal-100',   'text'=>'text-teal-700'],
            'assign_rider'      => ['dot'=>'bg-blue-500',  'icon'=>'🛵','card'=>'bg-blue-50 border-blue-100',  'text'=>'text-blue-700'],
            'rider_assigned'    => ['dot'=>'bg-blue-400',  'icon'=>'📋','card'=>'bg-blue-50 border-blue-100',  'text'=>'text-blue-700'],
            'rider_accept'      => ['dot'=>'bg-indigo-500','icon'=>'👍','card'=>'bg-indigo-50 border-indigo-100','text'=>'text-indigo-700'],
            'rider_pickup'      => ['dot'=>'bg-purple-500','icon'=>'📦','card'=>'bg-purple-50 border-purple-100','text'=>'text-purple-700'],
            'mark_delivered'    => ['dot'=>'bg-green-500', 'icon'=>'✅','card'=>'bg-green-50 border-green-100', 'text'=>'text-green-700'],
            'assign_third_party'=> ['dot'=>'bg-indigo-400','icon'=>'🚚','card'=>'bg-indigo-50 border-indigo-100','text'=>'text-indigo-700'],
            'cancel_order'      => ['dot'=>'bg-red-500',   'icon'=>'✕','card'=>'bg-red-50 border-red-100',    'text'=>'text-red-700'],
            'upload_proof'      => ['dot'=>'bg-teal-400',  'icon'=>'📷','card'=>'bg-teal-50 border-teal-100',  'text'=>'text-teal-700'],
            'send_out_for_delivery'=>['dot'=>'bg-purple-400','icon'=>'🛵','card'=>'bg-purple-50 border-purple-100','text'=>'text-purple-700'],
            'default'           => ['dot'=>'bg-gray-400',  'icon'=>'·','card'=>'bg-gray-50 border-gray-100',  'text'=>'text-gray-700'],
          ];
          $roleBadge = [
            'super_admin'=>'bg-orange-100 text-orange-700',
            'admin'      =>'bg-blue-100 text-blue-700',
            'rider'      =>'bg-purple-100 text-purple-700',
            'customer'   =>'bg-green-100 text-green-700',
            'system'     =>'bg-gray-100 text-gray-500',
          ];
        ?>
        <div class="relative px-5 py-4 max-h-[600px] overflow-y-auto space-y-3">
          <div class="absolute left-8 top-4 bottom-4 w-px bg-gray-200 pointer-events-none"></div>
          <?php foreach ($feed as $i => $entry):
            $conf    = $actionConf[$entry['action']] ?? $actionConf['default'];
            $rbClass = $roleBadge[strtolower($entry['actor_role'] ?? 'system')] ?? $roleBadge['system'];
            $isLast  = $i === count($feed) - 1;
          ?>
          <div class="flex items-start gap-3 relative">
            <div class="shrink-0 size-6 rounded-full <?= $conf['dot'] ?> flex items-center justify-center z-10 mt-1 text-white text-[11px] font-bold leading-none <?= $isLast ? 'ring-4 ring-orange-100' : '' ?>">
              <?= $conf['icon'] ?>
            </div>
            <div class="flex-1 <?= $conf['card'] ?> border rounded-xl px-4 py-3 min-w-0">
              <!-- Label + role badge -->
              <div class="flex items-start justify-between gap-2 flex-wrap">
                <p class="text-xs font-semibold <?= $conf['text'] ?> leading-snug"><?= htmlspecialchars($entry['label']) ?></p>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $rbClass ?>">
                  <?= htmlspecialchars(ucfirst(str_replace('_',' ', $entry['actor_role'] ?? 'system'))) ?>
                </span>
              </div>
              <!-- Old → New -->
              <?php if (!empty($entry['old_val']) || !empty($entry['new_val'])): ?>
              <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                <?php if (!empty($entry['old_val'])): ?>
                <span class="text-[11px] bg-white border border-gray-200 text-gray-400 px-2 py-0.5 rounded-md line-through"><?= htmlspecialchars($entry['old_val']) ?></span>
                <span class="text-gray-400 text-xs">→</span>
                <?php endif; ?>
                <?php if (!empty($entry['new_val']) && $entry['type'] !== 'proof'): ?>
                <span class="text-[11px] bg-white border border-gray-200 text-gray-700 font-medium px-2 py-0.5 rounded-md"><?= htmlspecialchars($entry['new_val']) ?></span>
                <?php elseif (!empty($entry['new_val']) && $entry['type'] === 'proof'): ?>
                <a href="../<?= htmlspecialchars($entry['new_val']) ?>" target="_blank" class="shrink-0 size-10 rounded-lg overflow-hidden border border-gray-200 inline-block">
                  <img src="../<?= htmlspecialchars($entry['new_val']) ?>" class="size-10 object-cover" onerror="this.parentElement.style.display='none'">
                </a>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <!-- Sub details -->
              <?php if (!empty($entry['sub'])): ?>
              <p class="text-[11px] text-gray-500 mt-1 leading-snug"><?= htmlspecialchars($entry['sub']) ?></p>
              <?php endif; ?>
              <!-- Footer -->
              <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 mt-2">
                <span class="text-[11px] font-medium text-gray-600"><?= htmlspecialchars($entry['actor']) ?></span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[11px] text-gray-400"><?= date('M j, Y · g:i A', strtotime($entry['ts'])) ?></span>
                <?php if (!empty($entry['ip'])): ?>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[11px] text-gray-400 font-mono"><?= htmlspecialchars($entry['ip']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <!-- ── RIGHT SIDEBAR ──────────────────────────────────────────────── -->
    <div class="space-y-5">

      <!-- Payment -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Payment</h3>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Status</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $psClass ?>"><?= $order['payment_status'] ?? 'Pending' ?></span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Method</span>
            <span class="font-medium text-gray-800"><?= $methodDisplay ?></span>
          </div>
          <?php if (!empty($order['paid_at'])): ?>
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Paid At</span>
            <span class="text-xs text-gray-700"><?= date('M j, Y g:i A', strtotime($order['paid_at'])) ?></span>
          </div>
          <?php endif; ?>
          <div class="pt-2 border-t border-gray-100 flex justify-between">
            <span class="text-sm font-semibold text-gray-700">Total</span>
            <span class="text-base font-bold text-orange-600">₱<?= number_format($order['total_price'], 2) ?></span>
          </div>
        </div>
      </div>

      <!-- Rider card -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Assigned Rider</h3>
        <?php if (!empty($order['rider_name'])): ?>
        <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-xl mb-3">
          <?php if (!empty($order['rider_image'])): ?>
          <img src="../<?= htmlspecialchars($order['rider_image']) ?>" class="size-10 rounded-full object-cover shrink-0">
          <?php else: ?>
          <div class="size-10 rounded-full bg-purple-200 flex items-center justify-center text-sm font-bold text-purple-700 shrink-0">
            <?= strtoupper(substr($order['rider_fname']??'?',0,1).substr($order['rider_lname']??'',0,1)) ?>
          </div>
          <?php endif; ?>
          <div>
            <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($order['rider_name']) ?></div>
            <div class="text-xs text-gray-500"><?= ucfirst($order['vehicle_type'] ?? '') ?> · <?= $order['vehicle_plate_number'] ?? '' ?></div>
            <?php if (!empty($order['variant_color'])): ?>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($order['variant_color']) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['organization'])): ?>
            <div class="text-xs text-indigo-600"><?= htmlspecialchars($order['organization']) ?></div>
            <?php endif; ?>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($order['rider_direct_phone'] ?: ($order['rider_acct_phone'] ?? '')) ?></div>
          </div>
        </div>
        <?php elseif (!empty($order['is_third_party'])): ?>
        <div class="p-3 bg-indigo-50 rounded-xl mb-3">
          <div class="text-sm font-semibold text-indigo-800"><?= htmlspecialchars($order['third_party_name'] ?? '') ?></div>
          <?php if (!empty($order['active_delivery_link'])): ?>
          <a href="<?= htmlspecialchars($order['active_delivery_link']) ?>" target="_blank" class="text-xs text-indigo-600 hover:underline">View Tracking →</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3 flex items-center gap-1.5">
          <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          No rider assigned yet.
        </p>
        <?php endif; ?>

        <!-- Delivery status badge if deliveries row exists -->
        <?php if (!empty($order['delivery_status'])): ?>
        <div class="text-xs text-center py-1.5 px-3 rounded-lg bg-gray-100 text-gray-600">
          Delivery: <span class="font-semibold"><?= DELIVERY_STATUS_LABELS[$order['delivery_status']] ?? $order['delivery_status'] ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Customer -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Customer</h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Name</span>
            <span class="font-medium text-gray-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></span>
          </div>
          <div class="flex justify-between gap-2">
            <span class="text-gray-500 shrink-0">Email</span>
            <span class="text-xs text-gray-700 text-right break-all"><?= htmlspecialchars($order['email']) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Phone</span>
            <span class="text-gray-700"><?= htmlspecialchars($order['phone_number'] ?? '—') ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Type</span>
            <span class="text-xs font-medium <?= !empty($order['is_guest_order']) ? 'text-gray-500' : 'text-blue-600' ?>">
              <?= !empty($order['is_guest_order']) ? 'Guest' : 'Registered' ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Quick Actions</h3>
        <div class="space-y-2">
          <a href="orders.php" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
            <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Back to All Orders
          </a>
        </div>
      </div>

    </div><!-- /right sidebar -->
  </div><!-- /main grid -->

</div>
</div>

<!-- ── Assign Rider modal ────────────────────────────────────────────────── -->
<div id="assign-modal" class="modal-overlay">
  <div class="modal-box">
    <h3 class="text-base font-bold text-gray-800 mb-1">
      <?= !empty($order['assigned_rider_id']) ? 'Re-assign Rider' : 'Assign Rider' ?>
    </h3>
    <p class="text-xs text-gray-500 mb-4">Rider will receive a notification and must accept before order moves to Out for Delivery.</p>
    <label class="block text-xs font-medium text-gray-600 mb-1.5">Select Rider</label>
    <select id="rider-select"
            class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400 mb-4">
      <option value="">— Choose a rider —</option>
      <?php foreach ($riders as $r): ?>
      <option value="<?= $r['rider_id'] ?>"
              <?= (!empty($order['assigned_rider_id']) && $order['assigned_rider_id'] == $r['rider_id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($r['display_name']) ?>
        (<?= (int)$r['active_deliveries'] ?> active · <?= ucfirst($r['vehicle_type'] ?? '') ?>
         <?php if (!empty($r['organization'])): ?>· <?= htmlspecialchars($r['organization']) ?><?php endif; ?>)
      </option>
      <?php endforeach; ?>
    </select>
    <div id="assign-error" class="hidden text-xs text-red-600 mb-3"></div>
    <div class="flex gap-2 justify-end">
      <button onclick="closeModal('assign-modal')" class="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
      <button onclick="submitAssignRider()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-xl hover:bg-blue-500">Assign Rider</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
const PROCESS  = './functions/order_process.php';
const ORDER_ID = <?= $order_id ?>;

// ── Utilities ──────────────────────────────────────────────────────────────
function toast(msg, type = 'info') {
  const c = { success:'bg-teal-600', error:'bg-red-600', info:'bg-gray-800', warning:'bg-orange-500' };
  const el = document.createElement('div');
  el.className = `${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-start gap-2 min-w-56 max-w-sm`;
  el.innerHTML = `<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none shrink-0">✕</button>`;
  document.getElementById('toast-wrap').prepend(el);
  setTimeout(() => el?.remove(), 6000);
}

function openModal(id) {
  // Close any other open modals first
  document.querySelectorAll('.modal-overlay.modal-open').forEach(m => m.classList.remove('modal-open'));
  document.getElementById(id).classList.add('modal-open');
}
function closeModal(id) { document.getElementById(id).classList.remove('modal-open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('modal-open'); });
});

function postAction(data, onSuccess) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k,v));
  return fetch(PROCESS, { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
      if (d.ok) { onSuccess(d); }
      else {
        // Show shortfall list if stock check failed
        if (d.shortfalls?.length) {
          const lines = d.shortfalls.map(s => `<li>${s.product_name} (${s.variant_name}): need ${s.requested}, have ${s.available}</li>`).join('');
          toast(`⚠️ ${d.msg}<ul class="mt-1 list-disc list-inside text-xs">${lines}</ul>`, 'error');
        } else {
          toast('⚠️ ' + d.msg, 'error');
        }
      }
    })
    .catch(() => toast('Network error. Please try again.', 'error'));
}

// ── Actions — each calls the correct action= value in order_process.php ───

/** approve_order → Pending → Processing (stock check is inside order_helper) */
function approveOrder() {
  const btn = document.getElementById('approve-btn');
  btn.disabled = true;
  btn.textContent = 'Checking stock…';
  postAction({ action:'approve_order', order_id:ORDER_ID }, () => {
    toast('✅ Order approved. Stock deducted.', 'success');
    setTimeout(() => location.reload(), 800);
  }).finally(() => { if(btn) { btn.disabled=false; btn.innerHTML='Approve &amp; Process'; }});
}

/** assign_rider → sends to deliveries (pending_acceptance). Rider must accept. */
function submitAssignRider() {
  const riderId = document.getElementById('rider-select').value;
  const errEl   = document.getElementById('assign-error');
  if (!riderId) { errEl.textContent = 'Please select a rider.'; errEl.classList.remove('hidden'); return; }
  errEl.classList.add('hidden');
  closeModal('assign-modal');
  postAction({ action:'assign_rider', order_id:ORDER_ID, rider_id:riderId, notes:'Assigned via admin panel' }, () => {
    toast('✅ Rider assigned. Waiting for rider acceptance.', 'success');
    setTimeout(() => location.reload(), 800);
  });
}

/** assign_third_party → OutForDelivery immediately */
function submitThirdParty() {
  const name  = document.getElementById('tp-name').value.trim();
  const link  = document.getElementById('tp-link').value.trim();
  const notes = document.getElementById('tp-notes').value.trim();
  if (!name) { toast('Provider name is required.', 'warning'); return; }
  closeModal('thirdparty-modal');
  postAction({ action:'assign_third_party', order_id:ORDER_ID, third_party_name:name, delivery_link:link, notes }, () => {
    toast(`✅ Order dispatched via ${name}.`, 'success');
    setTimeout(() => location.reload(), 800);
  });
}

/** send_out_for_delivery → manual admin push (registered rider already assigned) */
function sendOutForDelivery() {
  if (!confirm('Send this order Out for Delivery now?')) return;
  postAction({ action:'send_out_for_delivery', order_id:ORDER_ID, notes:'Dispatched by admin' }, () => {
    toast('✅ Order is now Out for Delivery.', 'success');
    setTimeout(() => location.reload(), 800);
  });
}

/** mark_delivered → admin override */
function markDelivered() {
  if (!confirm('Mark this order as Delivered? Use this only if the rider cannot confirm or for guest orders.')) return;
  postAction({ action:'mark_delivered', order_id:ORDER_ID, notes:'Marked delivered by admin (override)' }, () => {
    toast('✅ Order marked as Delivered.', 'success');
    setTimeout(() => location.reload(), 800);
  });
}

/** cancel_order — requires reason */
function submitCancel() {
  const reason = document.getElementById('cancel-reason').value.trim();
  if (!reason) { toast('Please provide a cancellation reason.', 'warning'); return; }
  closeModal('cancel-modal');
  postAction({ action:'cancel_order', order_id:ORDER_ID, reason }, () => {
    toast('Order cancelled. Stock restored.', 'info');
    setTimeout(() => location.reload(), 800);
  });
}

// ── Review invite ──────────────────────────────────────────────────────────
function copyReviewLink() {
  const inp = document.getElementById('review-link-input');
  const btn = document.getElementById('copy-btn');
  if (!inp) return;
  inp.select();
  inp.setSelectionRange(0, 99999);
  if (navigator.clipboard) {
    navigator.clipboard.writeText(inp.value).then(() => {
      btn.innerHTML = `<svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Copied!`;
      btn.classList.replace('bg-orange-500','bg-green-500');
      btn.classList.replace('hover:bg-orange-600','hover:bg-green-600');
      setTimeout(() => {
        btn.innerHTML = `<svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy`;
        btn.classList.replace('bg-green-500','bg-orange-500');
        btn.classList.replace('hover:bg-green-600','hover:bg-orange-600');
      }, 2500);
    });
  } else {
    document.execCommand('copy');
    toast('Review link copied!', 'success');
  }
}

function regenerateReviewLink() {
  if (!confirm('Regenerate the review link? The old link will no longer work.')) return;
  postAction({ action: 'regenerate_review_link', order_id: ORDER_ID }, () => {
    toast('New review link generated.', 'success');
    setTimeout(() => location.reload(), 800);
  });
}
</script>
</body>
</html>