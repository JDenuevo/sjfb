<?php
/**
 * rider/dashboard.php
 */
session_start();
require_once '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinasrider']) || $_SESSION['loggedinasrider'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../sign_in.php');
    exit;
}
if ($_SESSION['role'] !== 'rider') {
    header('Location: ../index.php');
    exit;
}

$rider_account_id = (int)$_SESSION['account_id'];

$rq = $conn->prepare("
    SELECT r.rider_id, r.image, r.vehicle_type, r.vehicle_plate_number,
           r.variant_color, r.organization,
           r.rider_phone  AS contact_number,
           r.is_available,
           COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS display_name,
           a.account_first_name AS first_name,
           a.account_last_name  AS last_name,
           a.account_email      AS email,
           a.account_phone      AS phone_number
    FROM riders r JOIN accounts a ON a.account_id=r.account_id
    WHERE r.account_id=? AND r.is_deleted=0 LIMIT 1
");
$rq->bind_param('i', $rider_account_id);
$rq->execute();
$rider = $rq->get_result()->fetch_assoc();
if (!$rider) { header('Location: ../index.php'); exit; }

$rider_id = (int)$rider['rider_id'];

$dlStmt = $conn->prepare("
    SELECT d.delivery_id, d.order_id, d.delivery_status AS status,
           d.assigned_at,
           o.order_code,
           o.recipient_first_name AS first_name,
           o.recipient_last_name  AS last_name,
           o.recipient_address    AS address,
           o.city,
           o.delivery_address,
           o.delivery_latitude, o.delivery_longitude,
           o.total_price,
           o.recipient_phone      AS phone_number,
           o.delivery_notes,
           o.payment_method,
           COALESCE(p.payment_status, 'Pending') AS payment_status
    FROM deliveries d
    JOIN riders r  ON r.rider_id  = d.rider_id
    JOIN orders o  ON o.order_id  = d.order_id
    LEFT JOIN (
        SELECT p1.order_id, p1.payment_status
        FROM payments p1
        INNER JOIN (
            SELECT order_id, MAX(created_at) AS max_created
            FROM payments GROUP BY order_id
        ) p2 ON p1.order_id = p2.order_id AND p1.created_at = p2.max_created
    ) p ON p.order_id = d.order_id
    WHERE r.account_id = ?
      AND d.delivery_status IN ('pending_acceptance','accepted','picked_up','in_transit')
    ORDER BY d.assigned_at DESC
");
$dlStmt->bind_param('i', $rider_account_id);
$dlStmt->execute();
$deliveries = $dlStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$codPendingStmt = $conn->prepare("
    SELECT d.delivery_id, d.order_id, d.delivery_status AS status,
           d.assigned_at,
           o.order_code,
           o.recipient_first_name AS first_name,
           o.recipient_last_name  AS last_name,
           o.recipient_address    AS address,
           o.city,
           o.delivery_address,
           o.delivery_latitude, o.delivery_longitude,
           o.total_price,
           o.recipient_phone      AS phone_number,
           o.delivery_notes,
           o.payment_method,
           COALESCE(p.payment_status, 'Pending') AS payment_status
    FROM deliveries d
    JOIN riders r  ON r.rider_id  = d.rider_id
    JOIN orders o  ON o.order_id  = d.order_id
    LEFT JOIN (
        SELECT p1.order_id, p1.payment_status
        FROM payments p1
        INNER JOIN (
            SELECT order_id, MAX(created_at) AS max_created
            FROM payments GROUP BY order_id
        ) p2 ON p1.order_id = p2.order_id AND p1.created_at = p2.max_created
    ) p ON p.order_id = d.order_id
    WHERE r.account_id = ?
      AND d.delivery_status = 'delivered'
      AND o.payment_method  = 'cod'
      AND COALESCE(p.payment_status, 'Pending') != 'Paid'
    ORDER BY d.assigned_at DESC
    LIMIT 10
");
$codPendingStmt->bind_param('i', $rider_account_id);
$codPendingStmt->execute();
$codPendingOrders = $codPendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$doneStmt = $conn->prepare("
    SELECT d.delivery_id, d.order_id, d.delivered_at,
           o.order_code,
           o.recipient_first_name AS first_name,
           o.recipient_last_name  AS last_name,
           o.total_price,
           o.delivery_address,
           o.recipient_address    AS address,
           o.city,
           o.payment_method,
           COALESCE(p.payment_status, 'Pending') AS payment_status
    FROM deliveries d
    JOIN orders o  ON o.order_id = d.order_id
    JOIN riders r  ON r.rider_id = d.rider_id
    LEFT JOIN (
        SELECT p1.order_id, p1.payment_status
        FROM payments p1
        INNER JOIN (
            SELECT order_id, MAX(created_at) AS max_created
            FROM payments GROUP BY order_id
        ) p2 ON p1.order_id = p2.order_id AND p1.created_at = p2.max_created
    ) p ON p.order_id = d.order_id
    WHERE r.account_id = ? AND d.delivery_status = 'delivered'
      AND NOT (o.payment_method = 'cod' AND COALESCE(p.payment_status,'Pending') != 'Paid')
    ORDER BY d.delivered_at DESC LIMIT 20
");
$doneStmt->bind_param('i', $rider_account_id);
$doneStmt->execute();
$completed = $doneStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$proofCounts = [];
$allOrderIds = array_unique(array_merge(
    array_column($deliveries, 'order_id'),
    array_column($codPendingOrders, 'order_id')
));
if (!empty($allOrderIds)) {
    $inList = implode(',', array_map('intval', $allOrderIds));
    $pcRes  = $conn->query("SELECT order_id, COUNT(*) AS cnt FROM delivery_proofs WHERE order_id IN ({$inList}) GROUP BY order_id");
    if ($pcRes) while ($row = $pcRes->fetch_assoc()) $proofCounts[(int)$row['order_id']] = (int)$row['cnt'];
}

$statusColor = [
    'pending_acceptance' => 'bg-yellow-100 text-yellow-800',
    'accepted'           => 'bg-blue-100 text-blue-800',
    'picked_up'          => 'bg-indigo-100 text-indigo-800',
    'in_transit'         => 'bg-purple-100 text-purple-800',
    'delivered'          => 'bg-green-100 text-green-800',
];
$dlLabels = DELIVERY_STATUS_LABELS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rider Dashboard | SJFBI</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body { font-family:'Lexend',sans-serif; }
    .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:50;align-items:center;justify-content:center;padding:1rem;overflow-y:auto; }
    .modal-overlay.modal-open { display:flex; }
    .modal-box { background:#fff;border-radius:1.25rem;width:100%;max-width:480px;max-height:90vh;overflow-y:auto; }
    .delivery-card { transition:box-shadow .2s; }
    .delivery-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
    @keyframes pulse-border { 0%,100%{border-color:#fdba74} 50%{border-color:#f97316} }
    .pending-card { animation:pulse-border 2s ease-in-out infinite; border-width:2px; }
    @keyframes pulse-green { 0%,100%{border-color:#86efac} 50%{border-color:#16a34a} }
    .cod-pending-card { animation:pulse-green 2s ease-in-out infinite; border-width:2px; }
    #camera-preview { width:100%;border-radius:.75rem;background:#111;aspect-ratio:4/3;object-fit:cover; }
    .tab-btn { padding:.5rem 1.25rem;border-radius:.625rem;font-size:.75rem;font-weight:600;transition:all .15s;border:none;cursor:pointer; }
    .tab-btn.active { background:#f97316;color:#fff; }
    .tab-btn:not(.active) { background:#f3f4f6;color:#6b7280; }
    .proof-thumb { position:relative;width:76px;height:76px;border-radius:.5rem;overflow:hidden;border:2px solid #e5e7eb;flex-shrink:0;cursor:default; }
    .proof-thumb img { width:100%;height:100%;object-fit:cover; }
    .proof-thumb .rm { position:absolute;top:2px;right:2px;background:rgba(239,68,68,.9);color:#fff;border:none;border-radius:9999px;width:18px;height:18px;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;font-weight:700; }
    .flow-step { display:flex;align-items:center;gap:.375rem;font-size:.65rem;font-weight:600; }
    .flow-step .dot { width:8px;height:8px;border-radius:9999px;flex-shrink:0; }
    .flow-step.done .dot  { background:#f97316; }
    .flow-step.done span  { color:#ea580c; }
    .flow-step.active .dot { background:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2); }
    .flow-step.active span { color:#2563eb; font-weight:700; }
    .flow-step.idle .dot  { background:#d1d5db; }
    .flow-step.idle span  { color:#9ca3af; }
    .flow-sep { color:#d1d5db;font-size:.6rem; }
    .btn-done { background:#f3f4f6 !important;color:#9ca3af !important;cursor:default !important;pointer-events:none; }

    /* ════ TOAST ════ */
    #toast-wrap {
      position:fixed; bottom:5.5rem; right:1.25rem;
      display:flex; flex-direction:column; align-items:flex-end; gap:.5rem;
      z-index:9999; pointer-events:none;
    }
    @media(min-width:640px){ #toast-wrap { right:1.5rem; } }
    .toast {
      pointer-events:auto;
      display:flex; align-items:flex-start; gap:.75rem;
      min-width:230px; max-width:340px;
      padding:.8rem 1rem;
      border-radius:.875rem; border-left:4px solid currentColor;
      background:#fff;
      box-shadow:0 8px 28px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
      position:relative; overflow:hidden;
      animation:tIn .28s cubic-bezier(.34,1.4,.64,1) both;
    }
    .toast::after {
      content:''; position:absolute; bottom:0; left:0;
      height:2px; width:100%; background:currentColor; opacity:.2;
      transform-origin:left; animation:tBar 4.5s linear forwards;
    }
    @keyframes tIn  { from{opacity:0;transform:translateX(24px) scale(.96)} to{opacity:1;transform:translateX(0) scale(1)} }
    @keyframes tOut { to{opacity:0;transform:translateX(24px) scale(.94);max-height:0;padding:0;margin:0} }
    @keyframes tBar { from{transform:scaleX(1)} to{transform:scaleX(0)} }
    .toast.t-success { color:#16a34a; }
    .toast.t-error   { color:#dc2626; }
    .toast.t-info    { color:#ea580c; }
    .toast.t-warning { color:#d97706; }
    .toast-icon  { font-size:1rem; flex-shrink:0; margin-top:.05rem; line-height:1; }
    .toast-body  { flex:1; min-width:0; }
    .toast-title { font-size:.8125rem; font-weight:700; color:#111827; line-height:1.3; }
    .toast-msg   { font-size:.75rem; color:#6b7280; margin-top:.15rem; line-height:1.4; }
    .toast-close {
      background:none; border:none; padding:0; color:#9ca3af;
      cursor:pointer; font-size:.875rem; flex-shrink:0; line-height:1;
      transition:color .1s;
    }
    .toast-close:hover { color:#111827; }
    .toast.leaving { animation:tOut .22s ease forwards; }
  </style>
</head>
<body class="bg-gray-50">

<header class="bg-white border-b border-gray-200 px-5 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
  <div class="flex items-center gap-3">
    <img src="../assets/icons/logo.svg" class="size-8" onerror="this.style.display='none'">
    <div>
      <div class="text-sm font-bold text-gray-800">Rider Dashboard</div>
      <div class="text-xs text-gray-400">St. Joseph Fish Brokerage</div>
    </div>
  </div>
  <div class="flex items-center gap-3">
    <button id="gps-btn" onclick="toggleGPS()"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-xl border border-gray-200 text-gray-600 hover:border-orange-400 hover:text-orange-600 transition-colors">
      <span id="gps-dot" class="size-2 rounded-full bg-gray-300 inline-block"></span>
      <span id="gps-label">GPS Off</span>
    </button>
    <div id="notif-wrap" class="relative">
      <a href="notifications.php" class="size-8 flex items-center justify-center rounded-full bg-orange-50 text-orange-500 hover:bg-orange-100 transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      </a>
      <span id="notif-count" class="absolute -top-1 -right-1 size-4 text-[10px] bg-red-500 text-white rounded-full hidden items-center justify-center font-bold">0</span>
    </div>
    <a href="logout.php" class="text-xs text-gray-400 hover:text-orange-500 transition-colors">← Logout</a>
  </div>
</header>

<div id="toast-wrap"></div>

<!-- PROOF UPLOAD MODAL -->
<div id="proof-modal" class="modal-overlay">
  <div class="modal-box">
    <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b border-gray-100">
      <div>
        <h3 class="text-base font-bold text-gray-800">Upload Delivery Proof</h3>
        <p class="text-xs text-gray-400 mt-0.5">Multiple photos — file or camera capture</p>
      </div>
      <button onclick="closeProofModal()" class="size-7 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-red-50 hover:text-red-500 transition-colors text-sm leading-none">✕</button>
    </div>
    <div class="px-5 pt-4 pb-5 space-y-4">
      <input type="hidden" id="proof-order-id">
      <div class="flex gap-2">
        <button class="tab-btn active" id="tab-file-btn" onclick="switchTab('file')">📁 Upload Files</button>
        <button class="tab-btn" id="tab-cam-btn" onclick="switchTab('camera')">📷 Camera</button>
      </div>
      <div id="panel-file">
        <label class="block w-full border-2 border-dashed border-gray-300 rounded-xl p-5 text-center cursor-pointer hover:border-orange-400 transition-colors">
          <div class="text-2xl mb-1">📎</div>
          <p class="text-sm font-medium text-gray-600">Click to select photos</p>
          <p class="text-xs text-gray-400 mt-0.5">JPEG, PNG, WEBP — max 8MB each — multiple allowed</p>
          <input type="file" id="proof-files" accept="image/jpeg,image/png,image/webp" multiple class="hidden" onchange="handleFileSelect(this)">
        </label>
      </div>
      <div id="panel-camera" class="hidden space-y-2">
        <video id="camera-preview" autoplay playsinline muted></video>
        <div class="flex gap-2">
          <button onclick="startCamera()" class="flex-1 text-xs px-3 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">▶ Start</button>
          <button onclick="capturePhoto()" class="flex-1 text-xs px-3 py-2 bg-orange-600 text-white rounded-xl hover:bg-orange-500 transition-colors font-medium">📸 Capture</button>
          <button onclick="stopCamera()" class="text-xs px-3 py-2 bg-red-50 text-red-500 rounded-xl hover:bg-red-100 transition-colors font-medium">⏹ Stop</button>
        </div>
        <canvas id="capture-canvas" class="hidden"></canvas>
      </div>
      <div id="proof-queue-wrap" class="hidden">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-semibold text-gray-600">Queued photos (<span id="queue-count">0</span>)</p>
          <button onclick="clearQueue()" class="text-xs text-red-500 hover:underline">Clear all</button>
        </div>
        <div id="proof-queue" class="flex flex-wrap gap-2"></div>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Caption <span class="text-gray-400">(optional — applies to all)</span></label>
        <input type="text" id="proof-caption" placeholder="e.g. Left at gate, handed to recipient"
               class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
      </div>
      <div class="flex gap-2 pt-1">
        <button onclick="closeProofModal()" class="flex-1 px-4 py-2.5 text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
        <button onclick="submitProofs()" id="upload-btn"
                class="flex-1 px-4 py-2.5 text-sm bg-orange-600 text-white rounded-xl hover:bg-orange-500 font-semibold transition-colors">
          Upload (<span id="upload-count">0</span>)
        </button>
      </div>
    </div>
  </div>
</div>

<div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

  <!-- Rider profile card -->
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center gap-4">
      <?php if (!empty($rider['image'])): ?>
      <img src="../<?= htmlspecialchars($rider['image']) ?>" class="size-16 rounded-2xl object-cover border border-gray-100 shrink-0">
      <?php else: ?>
      <div class="size-16 rounded-2xl bg-purple-100 flex items-center justify-center text-xl font-bold text-purple-600 shrink-0">
        <?= strtoupper(substr($rider['first_name'],0,1).substr($rider['last_name'],0,1)) ?>
      </div>
      <?php endif; ?>
      <div class="flex-1 min-w-0">
        <h2 class="text-lg font-bold text-gray-800 truncate"><?= htmlspecialchars($rider['display_name']) ?></h2>
        <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($rider['vehicle_type']) ?> · <?= htmlspecialchars($rider['vehicle_plate_number'] ?? '') ?></div>
        <?php if (!empty($rider['variant_color'])): ?>
        <div class="text-xs text-gray-400"><?= htmlspecialchars($rider['variant_color']) ?></div>
        <?php endif; ?>
        <?php if (!empty($rider['organization'])): ?>
        <div class="text-xs text-indigo-600 font-medium mt-0.5"><?= htmlspecialchars($rider['organization']) ?></div>
        <?php endif; ?>
      </div>
      <div class="text-right shrink-0">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $rider['is_available'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
          <span class="size-2 rounded-full inline-block <?= $rider['is_available'] ? 'bg-green-500 animate-pulse' : 'bg-gray-400' ?>"></span>
          <?= $rider['is_available'] ? 'Available' : 'Offline' ?>
        </div>
        <div class="text-xs text-gray-400 mt-1"><?= count($deliveries) ?> active · <?= count($codPendingOrders) ?> awaiting payment</div>
      </div>
    </div>
  </div>

  <!-- COD AWAITING PAYMENT -->
  <?php if (!empty($codPendingOrders)): ?>
  <div>
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-semibold text-gray-800">💵 Awaiting COD Payment</h3>
      <span class="text-xs bg-green-100 text-green-700 font-semibold px-2.5 py-0.5 rounded-full"><?= count($codPendingOrders) ?></span>
    </div>
    <div class="space-y-4">
      <?php foreach ($codPendingOrders as $d):
        $addr   = $d['delivery_address'] ?: ($d['address'].', '.$d['city']);
        $proofs = $proofCounts[$d['order_id']] ?? 0;
      ?>
      <div class="delivery-card bg-white rounded-2xl p-5 border border-green-200 cod-pending-card shadow-sm">
        <div class="flex items-start justify-between gap-2 mb-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-base font-bold text-green-700"><?= htmlspecialchars($d['order_code']) ?></span>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">✅ Delivered</span>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">💵 COD Unpaid</span>
            </div>
            <div class="text-xs text-gray-400 mt-0.5">Delivered — collect payment from customer</div>
          </div>
          <div class="text-right shrink-0">
            <div class="text-base font-bold text-green-700">₱<?= number_format($d['total_price'], 2) ?></div>
            <div class="text-xs text-gray-400">to collect</div>
          </div>
        </div>
        <div class="bg-green-50 rounded-xl p-3 mb-3 space-y-1.5 text-xs">
          <div class="flex items-center gap-2">
            <svg class="size-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="font-medium text-gray-700"><?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></span>
            <?php if (!empty($d['phone_number'])): ?>
            <a href="tel:<?= htmlspecialchars($d['phone_number']) ?>" class="text-blue-600 hover:underline">📞 <?= htmlspecialchars($d['phone_number']) ?></a>
            <?php endif; ?>
          </div>
          <div class="flex items-start gap-2">
            <svg class="size-3.5 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="text-gray-600"><?= htmlspecialchars($addr) ?></span>
          </div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 mb-3 flex items-center justify-between">
          <div class="text-xs text-amber-700"><span class="font-semibold">Collect from customer:</span> This is a Cash on Delivery order.</div>
          <span class="text-base font-bold text-amber-700">₱<?= number_format($d['total_price'], 2) ?></span>
        </div>
        <button onclick="markCODPaymentReceived(<?= $d['order_id'] ?>, this)"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold bg-green-600 hover:bg-green-500 active:scale-95 text-white rounded-xl transition-all">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          ✓ Payment Received from Customer
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ACTIVE DELIVERIES -->
  <div>
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-semibold text-gray-800">Active Deliveries</h3>
      <span class="text-xs bg-orange-100 text-orange-700 font-semibold px-2.5 py-0.5 rounded-full"><?= count($deliveries) ?></span>
    </div>
    <div class="space-y-4">
      <?php if (empty($deliveries)): ?>
      <div class="bg-white rounded-2xl p-8 text-center border border-gray-100 shadow-sm">
        <div class="text-3xl mb-2">🛵</div>
        <p class="text-sm text-gray-500 font-medium">No active deliveries right now.</p>
        <p class="text-xs text-gray-400 mt-1">You'll be notified when a new order is assigned.</p>
      </div>
      <?php else: foreach ($deliveries as $d):
        $badge   = $statusColor[$d['status']] ?? 'bg-gray-100 text-gray-700';
        $pending = $d['status'] === 'pending_acceptance';
        $border  = $pending ? 'border-orange-300 pending-card' : 'border-gray-100';
        $proofs  = $proofCounts[$d['order_id']] ?? 0;
        $addr    = $d['delivery_address'] ?: ($d['address'].', '.$d['city']);
        $flowMap = ['pending_acceptance'=>0,'accepted'=>1,'picked_up'=>2,'in_transit'=>2,'delivered'=>3];
        $flowIdx = $flowMap[$d['status']] ?? 0;
        $flowClass = fn(int $step) => $step < $flowIdx ? 'done' : ($step === $flowIdx ? 'active' : 'idle');
      ?>
      <div class="delivery-card bg-white rounded-2xl p-5 border <?= $border ?> shadow-sm">
        <div class="flex items-start justify-between gap-2 mb-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-base font-bold text-orange-600"><?= htmlspecialchars($d['order_code']) ?></span>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $badge ?>"><?= $dlLabels[$d['status']] ?? $d['status'] ?></span>
              <?php if ($proofs > 0): ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-700">📷 <?= $proofs ?> proof<?= $proofs > 1 ? 's' : '' ?></span>
              <?php endif; ?>
              <?php if ($d['payment_method'] === 'cod'): ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">💵 COD</span>
              <?php endif; ?>
            </div>
            <div class="text-xs text-gray-400 mt-0.5">Assigned <?= date('M j, g:i A', strtotime($d['assigned_at'])) ?></div>
          </div>
          <div class="text-sm font-bold text-gray-800 shrink-0">₱<?= number_format($d['total_price'], 2) ?></div>
        </div>
        <div class="flex items-center gap-1.5 mb-3 flex-wrap">
          <div class="flow-step <?= $flowClass(0) ?>"><span class="dot"></span><span>Assigned</span></div>
          <span class="flow-sep">→</span>
          <div class="flow-step <?= $flowClass(1) ?>"><span class="dot"></span><span>Accepted</span></div>
          <span class="flow-sep">→</span>
          <div class="flow-step <?= $flowClass(2) ?>"><span class="dot"></span><span>Picked Up</span></div>
          <span class="flow-sep">→</span>
          <div class="flow-step <?= $flowClass(3) ?>"><span class="dot"></span><span>Delivered</span></div>
          <?php if ($d['payment_method'] === 'cod'): ?>
          <span class="flow-sep">→</span>
          <div class="flow-step idle"><span class="dot"></span><span>Paid</span></div>
          <?php endif; ?>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 mb-3 space-y-1.5 text-xs">
          <div class="flex items-center gap-2">
            <svg class="size-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="font-medium text-gray-700"><?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></span>
            <?php if (!empty($d['phone_number'])): ?>
            <a href="tel:<?= htmlspecialchars($d['phone_number']) ?>" class="text-blue-600 hover:underline ml-auto">📞 Call</a>
            <?php endif; ?>
          </div>
          <div class="flex items-start gap-2">
            <svg class="size-3.5 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="text-gray-600"><?= htmlspecialchars($addr) ?></span>
          </div>
          <?php if (!empty($d['delivery_notes'])): ?>
          <div class="flex items-start gap-2">
            <svg class="size-3.5 text-orange-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span class="text-orange-600 italic">"<?= htmlspecialchars($d['delivery_notes']) ?>"</span>
          </div>
          <?php endif; ?>
          <?php if ($d['delivery_latitude'] && $d['delivery_longitude']): ?>
          <a href="https://maps.google.com/?q=<?= $d['delivery_latitude'] ?>,<?= $d['delivery_longitude'] ?>"
             target="_blank" class="flex items-center gap-1 text-blue-600 hover:underline">
            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Open in Google Maps
          </a>
          <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2">
          <?php if ($d['status'] === 'pending_acceptance'): ?>
          <button onclick="riderAction('rider_accept',<?= $d['delivery_id'] ?>,<?= $d['order_id'] ?>,this)"
                  class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold bg-orange-600 hover:bg-orange-500 text-white rounded-xl transition-colors">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Accept Delivery
          </button>
          <?php elseif ($d['status'] === 'accepted'): ?>
          <div class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-gray-100 text-gray-400 rounded-xl btn-done">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Accepted ✓
          </div>
          <button onclick="riderAction('rider_pickup',<?= $d['delivery_id'] ?>,<?= $d['order_id'] ?>,this)"
                  class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-colors">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Mark Picked Up
          </button>
          <button onclick="openProofModal(<?= $d['order_id'] ?>)"
                  class="flex items-center gap-1.5 px-3 py-2.5 text-xs text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
            📷<?php if ($proofs > 0): ?> (<?= $proofs ?>)<?php endif; ?>
          </button>
          <?php elseif (in_array($d['status'], ['picked_up','in_transit'])): ?>
          <div class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-gray-100 text-gray-400 rounded-xl btn-done">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Accepted ✓
          </div>
          <div class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-gray-100 text-gray-400 rounded-xl btn-done">📦 Picked Up ✓</div>
          <button onclick="riderAction('mark_delivered',null,<?= $d['order_id'] ?>,this)"
                  class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold bg-green-600 hover:bg-green-500 text-white rounded-xl transition-colors">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
            Mark Delivered
          </button>
          <button onclick="openProofModal(<?= $d['order_id'] ?>)"
                  class="flex items-center gap-1.5 px-3 py-2.5 text-xs text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
            📷<?php if ($proofs > 0): ?> (<?= $proofs ?>)<?php endif; ?>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- COMPLETED DELIVERIES -->
  <?php if (!empty($completed)): ?>
  <div>
    <h3 class="text-base font-semibold text-gray-800 mb-3">Recent Completed</h3>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-100">
      <?php foreach ($completed as $c): ?>
      <div class="flex items-center justify-between px-5 py-3 gap-3">
        <div>
          <div class="text-sm font-bold text-gray-700"><?= htmlspecialchars($c['order_code']) ?></div>
          <div class="text-xs text-gray-400"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
        </div>
        <div class="text-right">
          <div class="text-xs font-semibold text-green-600">₱<?= number_format($c['total_price'], 2) ?></div>
          <div class="flex items-center gap-1 justify-end mt-0.5">
            <?php if ($c['payment_method'] === 'cod'): ?>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-semibold">COD Paid ✓</span>
            <?php else: ?>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold">Online ✓</span>
            <?php endif; ?>
            <span class="text-xs text-gray-400"><?= $c['delivered_at'] ? date('M j, g:i A', strtotime($c['delivered_at'])) : '—' ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php include './components/navigation.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script>
const PROCESS = '../supadmin/functions/order_process.php';

/* ── Toast ─────────────────────────────────────────────────────────────── */
var _TOAST_META = {
  success: { icon:'✓', title:'Success', cls:'t-success' },
  error:   { icon:'✕', title:'Error',   cls:'t-error'   },
  info:    { icon:'ℹ', title:'Notice',  cls:'t-info'    },
  warning: { icon:'⚠', title:'Warning', cls:'t-warning' },
};
function showToast(msg, type, title) {
  type  = type  || 'info';
  var m  = _TOAST_META[type] || _TOAST_META.info;
  title  = title || m.title;
  var wrap = document.getElementById('toast-wrap');
  if (!wrap) return;
  var t = document.createElement('div');
  t.className = 'toast ' + m.cls;
  t.innerHTML =
    '<span class="toast-icon">' + m.icon + '</span>' +
    '<div class="toast-body">' +
      '<p class="toast-title">' + _escT(title) + '</p>' +
      '<p class="toast-msg">'   + msg           + '</p>' +
    '</div>' +
    '<button class="toast-close" aria-label="Dismiss">✕</button>';
  t.querySelector('.toast-close').addEventListener('click', function(){ _dismissToast(t); });
  wrap.appendChild(t);
  t._timer = setTimeout(function(){ _dismissToast(t); }, 4500);
}
function _dismissToast(el) {
  if (!el || el._gone) return; el._gone = true;
  clearTimeout(el._timer);
  el.classList.add('leaving');
  el.addEventListener('animationend', function(){ el.remove(); }, { once:true });
}
function _escT(v) {
  return v == null ? '' : String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
/* legacy alias — keeps all existing toast('msg','type') calls working */
function toast(msg, type) { showToast(msg, type); }

function openModal(id) {
  document.querySelectorAll('.modal-overlay.modal-open').forEach(m => m.classList.remove('modal-open'));
  document.getElementById(id).classList.add('modal-open');
}
function closeModal(id) { document.getElementById(id)?.classList.remove('modal-open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('modal-open'); stopCamera(); } });
});

async function postAction(data, onSuccess) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => { if (v !== null && v !== undefined) fd.append(k,v); });
  try {
    const r = await fetch(PROCESS, { method:'POST', body:fd });
    const d = await r.json();
    if (d.ok) onSuccess(d); else showToast('⚠️ ' + d.msg, 'error');
  } catch { showToast('Network error. Please try again.', 'error'); }
}

function riderAction(action, deliveryId, orderId, btn) {
  btn.disabled = true;
  const original = btn.innerHTML;
  btn.innerHTML = '<svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Working…';
  const labels = {
    rider_accept:   'Delivery accepted! 🛵',
    rider_pickup:   'Marked as picked up. 📦',
    mark_delivered: 'Marked as delivered! ✅'
  };
  const data = { action };
  if (deliveryId) data.delivery_id = deliveryId;
  if (orderId)    data.order_id    = orderId;
  postAction(data, () => {
    showToast(labels[action] || 'Done.', 'success');
    setTimeout(() => location.reload(), 800);
  });
  setTimeout(() => { if (btn) { btn.disabled=false; btn.innerHTML=original; } }, 5000);
}

function markCODPaymentReceived(orderId, btn) {
  if (!confirm('Confirm you have collected the cash payment from the customer?')) return;
  btn.disabled = true;
  btn.innerHTML = '<svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Processing…';
  postAction({ action: 'mark_cod_payment_received', order_id: orderId }, () => {
    showToast('COD payment collected and recorded!', 'success');
    setTimeout(() => location.reload(), 800);
  });
}

// Proof upload
let proofQueue = [];
let cameraStream = null;
let activeProofOrderId = null;

function openProofModal(orderId) {
  activeProofOrderId = orderId;
  document.getElementById('proof-order-id').value = orderId;
  proofQueue = [];
  renderQueue();
  document.getElementById('proof-caption').value = '';
  switchTab('file');
  openModal('proof-modal');
}
function closeProofModal() { closeModal('proof-modal'); stopCamera(); }

function switchTab(tab) {
  const isFile = tab === 'file';
  document.getElementById('panel-file').classList.toggle('hidden', !isFile);
  document.getElementById('panel-camera').classList.toggle('hidden', isFile);
  document.getElementById('tab-file-btn').classList.toggle('active', isFile);
  document.getElementById('tab-cam-btn').classList.toggle('active', !isFile);
  if (isFile) stopCamera();
}

function handleFileSelect(input) {
  Array.from(input.files).forEach(file => {
    if (file.size > 8 * 1024 * 1024) { showToast(file.name + ' exceeds 8MB', 'warning'); return; }
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { showToast(file.name + ' is not a valid image', 'warning'); return; }
    const reader = new FileReader();
    reader.onload = e => { proofQueue.push({ blob:file, name:file.name, preview:e.target.result }); renderQueue(); };
    reader.readAsDataURL(file);
  });
  input.value = '';
}

async function startCamera() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'environment' }, audio:false });
    document.getElementById('camera-preview').srcObject = cameraStream;
  } catch(e) { showToast('Camera error: ' + e.message, 'error'); }
}
function stopCamera() {
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
  const v = document.getElementById('camera-preview');
  if (v) v.srcObject = null;
}
function capturePhoto() {
  const video  = document.getElementById('camera-preview');
  const canvas = document.getElementById('capture-canvas');
  if (!cameraStream || !video.videoWidth) { showToast('Start the camera first.', 'warning'); return; }
  canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  canvas.toBlob(blob => {
    const name = 'capture_' + Date.now() + '.jpg';
    proofQueue.push({ blob, name, preview: canvas.toDataURL('image/jpeg') });
    renderQueue();
    showToast('Photo captured!', 'success');
  }, 'image/jpeg', 0.92);
}

function renderQueue() {
  const wrap  = document.getElementById('proof-queue-wrap');
  const qEl   = document.getElementById('proof-queue');
  const count = proofQueue.length;
  document.getElementById('queue-count').textContent  = count;
  document.getElementById('upload-count').textContent = count;
  if (count === 0) { wrap.classList.add('hidden'); return; }
  wrap.classList.remove('hidden');
  qEl.innerHTML = proofQueue.map((item, i) => `
    <div class="proof-thumb">
      <img src="${item.preview}" alt="proof ${i+1}">
      <button class="rm" onclick="removeFromQueue(${i})">✕</button>
    </div>
  `).join('');
}
function removeFromQueue(idx) { proofQueue.splice(idx, 1); renderQueue(); }
function clearQueue() { proofQueue = []; renderQueue(); }

let uploadPending = false;
async function submitProofs() {
  if (proofQueue.length === 0) { showToast('Add at least one photo first.', 'warning'); return; }
  const caption = document.getElementById('proof-caption').value.trim();
  const orderId = activeProofOrderId;
  const btn     = document.getElementById('upload-btn');
  btn.disabled  = true;
  btn.textContent = 'Uploading…';
  uploadPending = true;
  let uploaded = 0, failed = 0;
  for (const item of proofQueue) {
    const fd = new FormData();
    fd.append('action', 'upload_proof');
    fd.append('order_id', orderId);
    fd.append('caption', caption);
    fd.append('proof_file', item.blob, item.name);
    try {
      const resp = await fetch(PROCESS, { method:'POST', body:fd });
      const data = await resp.json();
      if (data.ok) uploaded++; else { failed++; showToast('⚠️ ' + data.msg, 'error'); }
    } catch { failed++; }
  }
  uploadPending = false;
  btn.disabled = false;
  btn.innerHTML = 'Upload (<span id="upload-count">' + proofQueue.length + '</span>)';
  if (uploaded > 0) {
    showToast(uploaded + ' proof photo' + (uploaded>1?'s':'') + ' uploaded!', 'success');
    proofQueue = []; renderQueue();
    closeProofModal();
    setTimeout(() => location.reload(), 800);
  }
  if (failed > 0) showToast(failed + ' photo(s) failed.', 'error');
}

// GPS
let gpsActive   = false;
let gpsInterval = null;
const GPS_KEY   = 'sjfbi_gps_on';
let currentDeliveryId = <?= !empty($deliveries) ? (int)$deliveries[0]['delivery_id'] : 'null' ?>;

function toggleGPS() { gpsActive ? stopGPS() : startGPS(); }
function startGPS(silent = false) {
  if (!navigator.geolocation) { showToast('Geolocation not supported.', 'warning'); return; }
  if (!currentDeliveryId)     { showToast('No active delivery to track.', 'warning'); return; }
  gpsActive = true;
  localStorage.setItem(GPS_KEY, '1');
  document.getElementById('gps-dot').className     = 'size-2 rounded-full bg-green-500 animate-pulse inline-block';
  document.getElementById('gps-label').textContent = 'GPS On';
  document.getElementById('gps-btn').classList.add('border-green-400','text-green-600');
  pushLocation();
  gpsInterval = setInterval(pushLocation, 15000);
  if (!silent) showToast('GPS tracking started', 'success');
}
function stopGPS() {
  gpsActive = false;
  clearInterval(gpsInterval);
  localStorage.removeItem(GPS_KEY);
  document.getElementById('gps-dot').className     = 'size-2 rounded-full bg-gray-300 inline-block';
  document.getElementById('gps-label').textContent = 'GPS Off';
  document.getElementById('gps-btn').classList.remove('border-green-400','text-green-600');
}
function pushLocation() {
  if (!currentDeliveryId) return;
  navigator.geolocation.getCurrentPosition(pos => {
    const fd = new FormData();
    fd.append('action',      'push_location');
    fd.append('lat',         pos.coords.latitude);
    fd.append('lng',         pos.coords.longitude);
    fd.append('delivery_id', currentDeliveryId);
    fd.append('status',      'en_route');
    fetch(PROCESS, { method:'POST', body:fd }).catch(()=>{});
  }, err => console.warn('GPS:', err.message), { enableHighAccuracy:true, timeout:8000 });
}
(function resumeGPS() {
  if (localStorage.getItem(GPS_KEY) === '1' && currentDeliveryId) {
    setTimeout(() => startGPS(true), 800);
  } else if (localStorage.getItem(GPS_KEY) === '1' && !currentDeliveryId) {
    localStorage.removeItem(GPS_KEY);
  }
})();

// Poll for new assignments
let seenNotifIds = new Set();
(function seedSeenIds() {
  fetch(PROCESS + '?action=poll_notifications')
    .then(r => r.json())
    .then(data => {
      if (data.ok && data.items) data.items.forEach(n => seenNotifIds.add(n.notif_id ?? String(n.created_at)));
    }).catch(() => {});
})();

setInterval(() => {
  const modalOpen = document.getElementById('proof-modal')?.classList.contains('modal-open');
  if (uploadPending || modalOpen) return;
  fetch(PROCESS + '?action=poll_notifications')
    .then(r => r.json())
    .then(data => {
      if (!data.ok) return;
      const newItems = (data.items || []).filter(n => {
        const id = n.notif_id ?? String(n.created_at);
        return !seenNotifIds.has(id);
      });
      (data.items || []).forEach(n => { seenNotifIds.add(n.notif_id ?? String(n.created_at)); });
      if (data.count > 0) {
        const badge = document.getElementById('notif-count');
        badge.textContent = data.count;
        badge.classList.remove('hidden');
        badge.classList.add('flex');
      } else {
        document.getElementById('notif-count').classList.add('hidden');
      }
      const hasNewAssignment = newItems.some(n => {
        const msg = (n.message ?? '').toLowerCase();
        return msg.includes('new delivery') || msg.includes('delivery assigned');
      });
      if (hasNewAssignment) {
        showToast('New delivery assigned to you!', 'info');
        setTimeout(() => location.reload(), 2000);
      }
    }).catch(() => {});
}, 10000);
</script>
</body>
</html>