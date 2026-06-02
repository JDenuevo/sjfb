<?php
/**
 * supadmin/delivery.php
 * Delivery management: live deliveries, delivery fees, proofs, GPS tracking
 */
session_start();
include '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php');
    exit;
}

$month = date('n');
$year  = date('Y');
$base  = 'exports/';

$statRes = $conn->query("SELECT COUNT(*) AS total, SUM(delivery_status='pending_acceptance') AS pending, SUM(delivery_status='accepted') AS accepted, SUM(delivery_status IN ('picked_up','in_transit')) AS in_transit, SUM(delivery_status='delivered') AS delivered, SUM(delivery_status='failed') AS failed, SUM(delivery_status='cancelled') AS cancelled FROM deliveries WHERE assigned_at >= CURDATE() - INTERVAL 30 DAY");
$stats = $statRes->fetch_assoc();

$activeDeliveries = $conn->query("SELECT d.*, o.order_code, o.total_price, o.recipient_first_name, o.recipient_last_name, o.recipient_address, o.recipient_phone, o.city, o.delivery_notes, o.payment_method, COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name, r.vehicle_type, r.vehicle_plate_number, r.rider_phone, r.current_lat, r.current_lng, (SELECT COUNT(*) FROM delivery_proofs dp WHERE dp.delivery_id=d.delivery_id) AS proof_count, (SELECT COUNT(*) FROM delivery_tracking dt WHERE dt.delivery_id=d.delivery_id) AS tracking_points FROM deliveries d JOIN orders o ON o.order_id=d.order_id LEFT JOIN riders r ON r.rider_id=d.rider_id LEFT JOIN accounts a ON a.account_id=r.account_id WHERE d.delivery_status IN ('pending_acceptance','accepted','picked_up','in_transit') ORDER BY d.assigned_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

$recentCompleted = $conn->query("SELECT d.*, o.order_code, o.total_price, o.recipient_first_name, o.recipient_last_name, o.city, COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name, (SELECT COUNT(*) FROM delivery_proofs dp WHERE dp.delivery_id=d.delivery_id) AS proof_count FROM deliveries d JOIN orders o ON o.order_id=d.order_id LEFT JOIN riders r ON r.rider_id=d.rider_id LEFT JOIN accounts a ON a.account_id=r.account_id WHERE d.delivery_status IN ('delivered','failed','cancelled') AND d.assigned_at >= NOW() - INTERVAL 7 DAY ORDER BY d.delivered_at DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);

$deliveryFees = $conn->query("SELECT * FROM delivery_fees ORDER BY area_type ASC, city ASC")->fetch_all(MYSQLI_ASSOC);
$feeStats = $conn->query("SELECT area_type, COUNT(*) AS cnt, AVG(base_fee) AS avg_fee, SUM(is_active) AS active FROM delivery_fees GROUP BY area_type")->fetch_all(MYSQLI_ASSOC);
$feeStatsByArea = [];
foreach ($feeStats as $fs) $feeStatsByArea[$fs['area_type']] = $fs;

$dlLabels = ['pending_acceptance'=>'Awaiting Acceptance','accepted'=>'Accepted','picked_up'=>'Picked Up','in_transit'=>'In Transit','delivered'=>'Delivered','failed'=>'Failed','reassigned'=>'Reassigned','cancelled'=>'Cancelled'];
$dlColors  = ['pending_acceptance'=>'bg-yellow-100 text-yellow-800','accepted'=>'bg-blue-100 text-blue-800','picked_up'=>'bg-indigo-100 text-indigo-800','in_transit'=>'bg-purple-100 text-purple-800','delivered'=>'bg-green-100 text-green-800','failed'=>'bg-red-100 text-red-800','reassigned'=>'bg-gray-100 text-gray-700','cancelled'=>'bg-red-100 text-red-700'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deliveries | SJFBI Admin</title>
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css"/>
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css"/>
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body{font-family:'Lexend',sans-serif}
    .tab-nav{display:flex;gap:.375rem;border-bottom:2px solid #f3f4f6;margin-bottom:1.25rem}
    .tab-btn{padding:.625rem 1.125rem;font-size:.875rem;font-weight:600;color:#6b7280;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.15s;font-family:inherit}
    .tab-btn.active{color:#ea580c;border-bottom-color:#ea580c}
    .tab-btn:hover:not(.active){color:#374151}
    .tab-panel{display:none}.tab-panel.active{display:block}
    .modal-overlay{position:fixed;inset:0;z-index:999;display:flex;align-items:flex-start;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);overflow-y:auto;padding:2rem 1rem}
    .modal-overlay.hidden{display:none}
    .modal-box{background:white;width:100%;max-width:48rem;border-radius:1.25rem;box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden}
    .modal-box-sm{max-width:32rem}.modal-box-lg{max-width:64rem}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#fafafa}
    .modal-header h3{font-size:1rem;font-weight:700;color:#111827}
    .modal-header p{font-size:.75rem;color:#6b7280;margin-top:1px}
    .modal-close{width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f3f4f6;color:#6b7280;border:none;cursor:pointer;transition:.15s}
    .modal-close:hover{background:#fee2e2;color:#dc2626}
    .modal-body{padding:1.5rem;max-height:80vh;overflow-y:auto}
    .modal-footer{padding:1rem 1.5rem;border-top:1px solid #f3f4f6;background:#fafafa;display:flex;justify-content:flex-end;gap:.625rem}
    .form-label{display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.375rem}
    .form-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.5rem;font-size:.875rem;color:#111827;outline:none;transition:.15s;font-family:inherit}
    .form-input:focus{border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,.1)}
    .form-section{font-size:.875rem;font-weight:700;color:#111827;border-left:3px solid #ea580c;padding-left:.625rem;margin:1.25rem 0 .75rem}
    .btn{padding:.5rem 1.125rem;border-radius:.625rem;border:none;font-size:.875rem;font-weight:600;cursor:pointer;transition:.15s;font-family:inherit;display:inline-flex;align-items:center;gap:.375rem}
    .btn-orange{background:#ea580c;color:white}.btn-orange:hover{background:#c2410c}
    .btn-green{background:#16a34a;color:white}.btn-green:hover{background:#15803d}
    .btn-red{background:#dc2626;color:white}.btn-red:hover{background:#b91c1c}
    .btn-outline{background:white;color:#374151;border:1px solid #e5e7eb}.btn-outline:hover{background:#f9fafb}
    .btn-danger{background:#dc2626;color:white}.btn-danger:hover{background:#b91c1c}
    .btn-sm{padding:.3rem .75rem;font-size:.75rem;border-radius:.5rem}
    .btn-xs{padding:.2rem .5rem;font-size:.7rem;border-radius:.4rem}
    .dl-card{background:white;border:1px solid #f3f4f6;border-radius:1rem;border-left:3px solid #e5e7eb;transition:.2s}
    .dl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
    .dl-card.status-pending{border-left-color:#f59e0b}.dl-card.status-accepted{border-left-color:#3b82f6}.dl-card.status-transit{border-left-color:#8b5cf6}
    .toggle{position:relative;display:inline-block;width:36px;height:20px;flex-shrink:0}
    .toggle input{opacity:0;width:0;height:0}
    .toggle-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:9999px;transition:.2s}
    .toggle-slider::before{content:'';position:absolute;width:14px;height:14px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s}
    .toggle input:checked+.toggle-slider{background:#16a34a}
    .toggle input:checked+.toggle-slider::before{transform:translateX(16px)}
    .fee-row{transition:.15s}.fee-row:hover{background:#fff7ed}
    .tl-step{display:flex;align-items:flex-start;gap:.75rem;position:relative}
    .tl-step:not(:last-child)::after{content:'';position:absolute;left:11px;top:24px;bottom:-8px;width:2px;background:#e5e7eb;z-index:0}
    .tl-dot{width:24px;height:24px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:10px;z-index:1;position:relative}
    .tl-dot.done{background:#f97316;color:white}.tl-dot.idle{background:#f3f4f6;border:2px solid #e5e7eb;color:#9ca3af}
    .proof-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem}
    .proof-thumb{aspect-ratio:1;border-radius:.625rem;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer;transition:.2s}
    .proof-thumb:hover{transform:scale(1.03);box-shadow:0 4px 12px rgba(0,0,0,.12)}
    .proof-thumb img{width:100%;height:100%;object-fit:cover}
    .g2{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
    @media(max-width:640px){.g2{grid-template-columns:1fr}}
    .empty-state{text-align:center;padding:3rem 1rem;color:#9ca3af}.empty-state .icon{font-size:2.5rem;margin-bottom:.75rem}
    .pulse-dot{width:8px;height:8px;border-radius:50%;display:inline-block;background:#16a34a;animation:pulse 1.5s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.4)}}

    /* Bulk adjuster strip */
    .bulk-strip{display:flex;align-items:center;gap:.5rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:.75rem;padding:.4rem .75rem}
    .bulk-strip label{font-size:.75rem;font-weight:600;color:#6b7280;white-space:nowrap}
    .bulk-input{width:90px;padding:.3rem .5rem;border:1px solid #e5e7eb;border-radius:.5rem;font-size:.8125rem;font-family:inherit;outline:none;text-align:center;color:#111827}
    .bulk-input:focus{border-color:#ea580c;box-shadow:0 0 0 2px rgba(234,88,12,.1)}
    input[type=number]::-webkit-inner-spin-button,input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<div id="toast-wrap" class="fixed bottom-5 right-5 flex flex-col gap-2 z-[9999]"></div>

<!-- ══ DELIVERY DETAIL MODAL ══ -->
<div id="detailModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-lg">
    <div class="modal-header">
      <div><h3 id="detailTitle">Delivery Detail</h3><p id="detailSubtitle"></p></div>
      <button class="modal-close" onclick="closeModal('detailModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div id="detailContent" class="modal-body"><div class="flex items-center justify-center py-12 text-gray-400"><svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Loading…</div></div>
  </div>
</div>

<!-- ══ ADD/EDIT FEE MODAL ══ -->
<div id="feeModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3 id="feeModalTitle">Add Delivery Fee</h3><p>City-based delivery pricing</p></div>
      <button class="modal-close" onclick="closeModal('feeModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form action="./functions/delivery_process.php" method="POST" id="feeForm" class="space-y-4">
        <input type="hidden" name="action" id="feeAction" value="add_fee">
        <input type="hidden" name="fee_id" id="feeIdField" value="">
        <div><label class="form-label">City / Municipality <span class="text-red-500">*</span></label><input type="text" name="city" id="feeCity" required class="form-input" placeholder="e.g. Navotas"></div>
        <div><label class="form-label">Area Type <span class="text-red-500">*</span></label>
          <select name="area_type" id="feeAreaType" required class="form-input">
            <option value="CaMaNaVa">CaMaNaVa</option><option value="Metro Manila" selected>Metro Manila</option><option value="Province">Province</option>
          </select>
        </div>
        <div class="g2">
          <div><label class="form-label">Base Fee (₱) <span class="text-red-500">*</span></label><input type="number" name="base_fee" id="feeBase" required min="0" step="0.01" class="form-input" placeholder="50.00"></div>
          <div><label class="form-label">Free Shipping Threshold (₱) <span style="font-weight:400;color:#9ca3af">(optional)</span></label><input type="number" name="free_shipping_threshold" id="feeThreshold" min="0" step="0.01" class="form-input" placeholder="Leave blank if none"></div>
        </div>
        <div class="flex items-center justify-between pt-1">
          <div><label class="form-label mb-0">Active</label><p class="text-xs text-gray-400">Show this city in checkout</p></div>
          <label class="toggle"><input type="checkbox" name="is_active" id="feeIsActive" checked><span class="toggle-slider"></span></label>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('feeModal')" class="btn btn-outline">Cancel</button>
      <button type="submit" form="feeForm" class="btn btn-orange">Save Fee</button>
    </div>
  </div>
</div>

<!-- ══ BULK CONFIRM MODAL ══ -->
<div id="bulkModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3 id="bulkTitle">Confirm Bulk Adjustment</h3><p id="bulkSubtitle">Updates all <?= count($deliveryFees) ?> city fees</p></div>
      <button class="modal-close" onclick="closeModal('bulkModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body space-y-4 text-center">
      <div id="bulkIcon" class="text-5xl">💰</div>
      <p id="bulkMsg" class="text-sm text-gray-700 leading-relaxed"></p>
      <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 text-left">
        <p class="text-xs font-semibold text-gray-500 mb-2">Preview (first 5 cities)</p>
        <div id="bulkPreview" class="space-y-1"></div>
      </div>
      <p class="text-xs text-amber-600 font-medium">⚠ This affects ALL <?= count($deliveryFees) ?> city fees and cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('bulkModal')" class="btn btn-outline">Cancel</button>
      <button type="button" id="bulkConfirmBtn" class="btn btn-orange">Confirm</button>
    </div>
  </div>
</div>

<!-- ══ DELETE CONFIRM MODAL ══ -->
<div id="deleteModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3>Confirm Delete</h3><p>This cannot be undone</p></div>
      <button class="modal-close" onclick="closeModal('deleteModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body text-center">
      <div class="size-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-3"><svg class="size-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></div>
      <p id="deleteMsg" class="text-sm text-gray-700 mb-4"></p>
      <form action="./functions/delivery_process.php" method="POST">
        <input type="hidden" name="action" id="deleteAction" value="delete_fee">
        <input type="hidden" name="fee_id" id="deleteFeeId">
        <div class="flex gap-3 justify-center">
          <button type="button" onclick="closeModal('deleteModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="w-full lg:ps-64">
<div class="p-4 sm:p-6 space-y-6">

  <?php if (!empty($_SESSION['message'])): $msg=$_SESSION['message']; unset($_SESSION['message']); $cls=$msg['type']==='success'?'bg-teal-500':'bg-red-500'; ?>
  <div class="<?=$cls?> text-white text-sm rounded-xl p-4 flex items-center gap-2 shadow-sm"><span class="font-bold"><?=ucfirst($msg['type'])?>!</span> <?=htmlspecialchars($msg['text'])?></div>
  <?php endif; ?>

  <div>
    <h1 class="text-2xl font-bold text-gray-900">Delivery Management</h1>
    <p class="text-sm text-gray-500 mt-0.5">Track deliveries, manage city fees, and view delivery proofs.</p>
  </div>

  <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
    <?php foreach([['⏳','Pending',$stats['pending']],['👍','Accepted',$stats['accepted']],['🛵','In Transit',$stats['in_transit']],['✅','Delivered',$stats['delivered']],['❌','Failed',$stats['failed']],['🚫','Cancelled',$stats['cancelled']]] as [$icon,$label,$val]): ?>
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm text-center"><div class="text-xl mb-1"><?=$icon?></div><div class="text-xl font-bold text-gray-900"><?=number_format((int)$val)?></div><div class="text-xs text-gray-500 mt-0.5"><?=$label?></div></div>
    <?php endforeach; ?>
  </div>

  <!-- ═══════════════════════════════════════════
     deliveries.php
  ════════════════════════════════════════════ -->
  <div style="display:flex; gap:8px; flex-wrap:wrap; margin:12px 0;">
      <a href="<?= $base ?>export_deliveries.php" target="_blank"
        class="btn btn-outline-success btn-sm">
          <i class="ti ti-file-spreadsheet"></i> Export All Deliveries
      </a>
      <a href="<?= $base ?>export_deliveries.php?month=<?= $month ?>&year=<?= $year ?>" target="_blank"
        class="btn btn-outline-success btn-sm">
          <i class="ti ti-calendar-month"></i> This Month
      </a>
      <a href="<?= $base ?>export_deliveries.php?status=failed" target="_blank"
        class="btn btn-outline-danger btn-sm">
          <i class="ti ti-truck-off"></i> Failed Only
      </a>
  </div>


  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 pt-5">
      <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('active',this)">🔴 Active (<?=count($activeDeliveries)?>)</button>
        <button class="tab-btn" onclick="switchTab('recent',this)">📋 Recent (7 days)</button>
        <button class="tab-btn" onclick="switchTab('fees',this)">🗺 Delivery Fees (<?=count($deliveryFees)?>)</button>
      </div>
    </div>
    <div class="px-6 pb-6">

      <!-- ACTIVE -->
      <div id="tab-active" class="tab-panel active">
        <?php if(empty($activeDeliveries)): ?>
        <div class="empty-state"><div class="icon">🛵</div><h3 class="text-sm font-semibold text-gray-600">No active deliveries</h3><p class="text-xs mt-1">All deliveries are completed or no orders are out for delivery.</p></div>
        <?php else: ?>
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500"><?=count($activeDeliveries)?> delivery<?=count($activeDeliveries)!==1?'s':''?> in progress</p>
          <div class="flex items-center gap-2 text-xs text-green-600"><span class="pulse-dot"></span><span id="lastRefresh">Live</span><button onclick="location.reload()" class="btn btn-outline btn-xs">↻ Refresh</button></div>
        </div>
        <div class="space-y-3">
          <?php foreach($activeDeliveries as $d):
            $cardCls=match($d['delivery_status']){'pending_acceptance'=>'status-pending','accepted'=>'status-accepted','picked_up','in_transit'=>'status-transit',default=>''};
            $badge=$dlColors[$d['delivery_status']]??'bg-gray-100 text-gray-700';
          ?>
          <div class="dl-card <?=$cardCls?> p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <div class="size-10 rounded-xl flex items-center justify-center text-lg shrink-0 <?=$d['is_third_party']?'bg-indigo-50':'bg-orange-50'?>"><?=$d['is_third_party']?'🚚':'🛵'?></div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <a href="order_manage.php?order_id=<?=$d['order_id']?>" class="font-bold text-orange-600 hover:underline text-sm"><?=htmlspecialchars($d['order_code'])?></a>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?=$badge?>"><?=$dlLabels[$d['delivery_status']]??$d['delivery_status']?></span>
                    <?php if($d['is_third_party']):?><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-semibold">3P: <?=htmlspecialchars($d['third_party_name']??'')?></span><?php endif;?>
                    <?php if($d['proof_count']>0):?><span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full font-semibold">📷 <?=$d['proof_count']?></span><?php endif;?>
                  </div>
                  <p class="text-xs text-gray-600 mt-0.5"><?=htmlspecialchars($d['recipient_first_name'].' '.$d['recipient_last_name'])?> · <?=htmlspecialchars($d['city'])?></p>
                  <?php if(!$d['is_third_party']&&$d['rider_name']):?><p class="text-xs text-gray-400 mt-0.5">Rider: <?=htmlspecialchars($d['rider_name'])?><?=$d['vehicle_type']?' · '.htmlspecialchars($d['vehicle_type']):''?><?=$d['vehicle_plate_number']?' ('.htmlspecialchars($d['vehicle_plate_number']).')':''?></p><?php elseif($d['delivery_link']):?><a href="<?=htmlspecialchars($d['delivery_link'])?>" target="_blank" class="text-xs text-indigo-600 hover:underline mt-0.5 inline-block">↗ Tracking Link</a><?php endif;?>
                  <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                    <?php foreach(['assigned_at'=>'Assigned','accepted_at'=>'Accepted','picked_up_at'=>'Picked Up','delivered_at'=>'Delivered'] as $col=>$lbl): $done=!empty($d[$col]);?>
                    <span class="flex items-center gap-1 text-[10px] <?=$done?'text-orange-600 font-semibold':'text-gray-400'?>"><span class="size-1.5 rounded-full inline-block <?=$done?'bg-orange-500':'bg-gray-300'?>"></span><?=$lbl?></span>
                    <?php if($lbl!=='Delivered'):?><span class="text-gray-300 text-[10px]">→</span><?php endif;endforeach;?>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <div class="text-right"><div class="text-sm font-bold text-gray-800">₱<?=number_format($d['total_price'],2)?></div><div class="text-xs text-gray-400"><?=date('M j g:i A',strtotime($d['assigned_at']))?></div><?php if($d['tracking_points']>0):?><div class="text-[11px] text-green-600 mt-0.5">📍 <?=$d['tracking_points']?> GPS pts</div><?php endif;?></div>
                <button onclick="openDeliveryDetail(<?=$d['delivery_id']?>)" class="btn btn-outline btn-sm">View</button>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <?php endif;?>
      </div>

      <!-- RECENT -->
      <div id="tab-recent" class="tab-panel">
        <?php if(empty($recentCompleted)):?><div class="empty-state"><div class="icon">📋</div><h3 class="text-sm font-semibold text-gray-600">No completed deliveries in the last 7 days</h3></div><?php else:?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rider</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Delivered At</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Proofs</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <?php foreach($recentCompleted as $d): $badge=$dlColors[$d['delivery_status']]??'bg-gray-100 text-gray-700';?>
              <tr class="hover:bg-orange-50/20 transition-colors">
                <td class="px-4 py-3"><a href="order_manage.php?order_id=<?=$d['order_id']?>" class="text-sm font-bold text-orange-600 hover:underline"><?=htmlspecialchars($d['order_code'])?></a></td>
                <td class="px-4 py-3"><p class="text-sm text-gray-800"><?=htmlspecialchars($d['recipient_first_name'].' '.$d['recipient_last_name'])?></p><p class="text-xs text-gray-400"><?=htmlspecialchars($d['city'])?></p></td>
                <td class="px-4 py-3 text-xs text-gray-600"><?=htmlspecialchars($d['rider_name']??'—')?></td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-semibold <?=$badge?>"><?=$dlLabels[$d['delivery_status']]??$d['delivery_status']?></span></td>
                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">₱<?=number_format($d['total_price'],2)?></td>
                <td class="px-4 py-3 text-xs text-gray-500"><?=$d['delivered_at']?date('M j, Y g:i A',strtotime($d['delivered_at'])):'—'?></td>
                <td class="px-4 py-3 text-center"><?php if($d['proof_count']>0):?><span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full font-semibold">📷 <?=$d['proof_count']?></span><?php else:?><span class="text-xs text-gray-400">—</span><?php endif;?></td>
                <td class="px-4 py-3 text-right"><button onclick="openDeliveryDetail(<?=$d['delivery_id']?>)" class="btn btn-outline btn-xs">View</button></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
        <?php endif;?>
      </div>

      <!-- FEES -->
      <div id="tab-fees" class="tab-panel">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
          <?php foreach(['CaMaNaVa','Metro Manila','Province'] as $area): $fs=$feeStatsByArea[$area]??['cnt'=>0,'avg_fee'=>0,'active'=>0];?>
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4"><p class="text-sm font-semibold text-gray-700"><?=$area?></p><p class="text-xl font-bold text-gray-900 mt-0.5"><?=$fs['cnt']?> cities</p><p class="text-xs text-gray-400">Avg ₱<?=number_format($fs['avg_fee'],2)?> · <?=$fs['active']?> active</p></div>
          <?php endforeach;?>
        </div>

        <div class="flex items-center justify-between mb-4">
          <p class="text-sm font-semibold text-gray-700"><?=count($deliveryFees)?> cities configured</p>
          <button onclick="openAddFee()" class="btn btn-orange btn-sm"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Add City</button>
        </div>

        <!-- Controls row: search | bulk adjuster -->
        <div class="flex flex-wrap items-center gap-3 mb-4">

          <!-- Search -->
          <input type="text" id="feeSearch" oninput="filterFees(this.value)" placeholder="Search city…"
                 class="form-input" style="width:180px;padding:.4rem .75rem;font-size:.8125rem">

          <!-- Bulk adjuster -->
          <div class="bulk-strip">
            <label>Bulk adjust all fees:</label>
            <input type="number" id="bulkAmt" min="0.01" step="0.01" placeholder="₱ amount" class="bulk-input">
            <button onclick="openBulk('increase')" class="btn btn-green btn-sm">
              <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 12h14M12 5v14"/></svg>
              + Increase
            </button>
            <button onclick="openBulk('decrease')" class="btn btn-red btn-sm">
              <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 12h14"/></svg>
              − Decrease
            </button>
          </div>

        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">City</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Area Type</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Base Fee</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Free Ship Threshold</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Active</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 bg-white" id="feeTableBody">
              <?php foreach($deliveryFees as $fee):?>
              <tr class="fee-row" data-city="<?=strtolower($fee['city'])?>" data-fee-id="<?=$fee['fee_id']?>" data-base-fee="<?=$fee['base_fee']?>">
                <td class="px-4 py-3 text-sm font-medium text-gray-800"><?=htmlspecialchars($fee['city'])?></td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-semibold <?=$fee['area_type']==='CaMaNaVa'?'bg-orange-100 text-orange-700':($fee['area_type']==='Metro Manila'?'bg-blue-100 text-blue-700':'bg-gray-100 text-gray-700')?>"><?=$fee['area_type']?></span></td>
                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800 fee-cell">₱<?=number_format($fee['base_fee'],2)?></td>
                <td class="px-4 py-3 text-right text-sm text-gray-600"><?=$fee['free_shipping_threshold']?'₱'.number_format($fee['free_shipping_threshold'],2):'<span class="text-gray-400">—</span>'?></td>
                <td class="px-4 py-3 text-center"><label class="toggle" style="margin:0 auto"><input type="checkbox" <?=$fee['is_active']?'checked':''?> onchange="toggleFeeActive(<?=$fee['fee_id']?>,this.checked)"><span class="toggle-slider"></span></label></td>
                <td class="px-4 py-3 text-right"><div class="inline-flex gap-1.5"><button onclick='editFee(<?=json_encode($fee)?>)' class="btn btn-outline btn-xs">Edit</button><button onclick="confirmDeleteFee(<?=$fee['fee_id']?>,'<?=htmlspecialchars(addslashes($fee['city']))?>')" class="btn btn-xs text-red-500 border border-red-200 hover:bg-red-50">Del</button></div></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<script>
const PROCESS = './functions/delivery_process.php';
const FEES = <?= json_encode(array_values(array_map(fn($f) => ['fee_id'=>(int)$f['fee_id'],'city'=>$f['city'],'base_fee'=>(float)$f['base_fee']], $deliveryFees))) ?>;

function switchTab(id,btn){document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.getElementById('tab-'+id).classList.add('active');btn.classList.add('active');}
function openModal(id){document.getElementById(id).classList.remove('hidden');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.add('hidden');document.body.style.overflow='';}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModal(m.id);}));

// ── Bulk adjuster ─────────────────────────────────────────────────────────
let _bulkMode='increase', _bulkAmt=0;

function openBulk(mode) {
  const raw = parseFloat(document.getElementById('bulkAmt').value);
  if (!raw || raw <= 0) { toast('Enter a ₱ amount first.', 'error'); return; }
  _bulkMode = mode; _bulkAmt = raw;
  const inc = mode === 'increase';
  document.getElementById('bulkTitle').textContent   = inc ? 'Confirm Fee Increase' : 'Confirm Fee Decrease';
  document.getElementById('bulkIcon').textContent    = inc ? '📈' : '📉';
  document.getElementById('bulkMsg').innerHTML =
    `All <strong>${FEES.length} city</strong> base fees will be <strong>${inc?'increased':'decreased'} by ₱${raw.toFixed(2)}</strong>.`;
  document.getElementById('bulkPreview').innerHTML = FEES.slice(0,5).map(f=>{
    const nv = Math.max(0, f.base_fee + (inc ? raw : -raw));
    return `<div class="flex justify-between text-xs py-0.5"><span class="text-gray-600">${esc(f.city)}</span><span class="text-gray-500">₱${f.base_fee.toFixed(2)} → <span class="${inc?'text-green-600':'text-red-600'} font-semibold">₱${nv.toFixed(2)}</span></span></div>`;
  }).join('') + (FEES.length>5?`<p class="text-xs text-gray-400 mt-1">…and ${FEES.length-5} more</p>`:'');
  const btn = document.getElementById('bulkConfirmBtn');
  btn.onclick = executeBulk; btn.disabled = false; btn.textContent = 'Confirm';
  openModal('bulkModal');
}

function executeBulk() {
  const btn = document.getElementById('bulkConfirmBtn');
  btn.disabled = true; btn.textContent = 'Updating…';
  const fd = new FormData();
  fd.append('action','bulk_adjust_fee'); fd.append('mode',_bulkMode); fd.append('amount',_bulkAmt);
  fetch(PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    closeModal('bulkModal'); btn.disabled=false; btn.textContent='Confirm';
    if(d.success){
      toast(d.message,'success');
      const delta = _bulkMode==='increase' ? _bulkAmt : -_bulkAmt;
      document.querySelectorAll('#feeTableBody .fee-row').forEach(row=>{
        const old = parseFloat(row.dataset.baseFee);
        const nv  = Math.max(0, old + delta);
        row.dataset.baseFee = nv.toFixed(2);
        const cell = row.querySelector('.fee-cell');
        if(cell) cell.textContent = '₱'+nv.toLocaleString('en-PH',{minimumFractionDigits:2});
      });
      FEES.forEach(f=>{ f.base_fee = Math.max(0, f.base_fee+delta); });
      document.getElementById('bulkAmt').value = '';
    } else { toast('Error: '+d.message,'error'); }
  }).catch(()=>{ btn.disabled=false; btn.textContent='Confirm'; toast('Network error.','error'); });
}

// ── Delivery detail ────────────────────────────────────────────────────────
// ── Delivery detail ────────────────────────────────────────────────────────
const FETCH_DELIVERIES = './functions/fetch_deliveries.php';

function openDeliveryDetail(deliveryId) {
  document.getElementById('detailContent').innerHTML =
    '<div class="flex items-center justify-center py-12 text-gray-400">' +
    '<svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24">' +
    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>' +
    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Loading\u2026</div>';
  openModal('detailModal');

  fetch(`${FETCH_DELIVERIES}?delivery_id=${deliveryId}`)
    .then(r => r.text().then(text => {
      console.log('[fetch_deliveries] raw response:', text.substring(0, 500));
      try { return JSON.parse(text); }
      catch(e) { throw new Error('Invalid JSON: ' + text.substring(0, 200)); }
    }))
    .then(data => {
      if (!data.success) {
        document.getElementById('detailContent').innerHTML =
          `<p class="text-red-500 text-center p-4">${esc(data.message)}</p>`;
        return;
      }

      const d        = data.delivery;
      const items    = data.order_items  || [];
      const payment  = data.payment;
      const proofs   = data.proofs       || [];
      const tracking = data.tracking     || [];

      document.getElementById('detailTitle').textContent    = `Delivery \u2014 ${d.order_code}`;
      document.getElementById('detailSubtitle').textContent =
        d.delivery_status.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());

      const badgeMap = {
        pending_acceptance:'bg-yellow-100 text-yellow-800',accepted:'bg-blue-100 text-blue-800',
        picked_up:'bg-indigo-100 text-indigo-800',in_transit:'bg-purple-100 text-purple-800',
        delivered:'bg-green-100 text-green-800',failed:'bg-red-100 text-red-800',
        reassigned:'bg-gray-100 text-gray-600',cancelled:'bg-red-100 text-red-700',
      };
      const badgeCls = badgeMap[d.delivery_status] || 'bg-gray-100 text-gray-600';
      const badgeLbl = d.delivery_status.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());

      // Timeline
      const tlSteps    = [{label:'Assigned',ts:d.assigned_at},{label:'Accepted',ts:d.accepted_at},{label:'Picked Up',ts:d.picked_up_at},{label:'Delivered',ts:d.delivered_at}];
      const dotColors  = ['bg-orange-400','bg-blue-400','bg-indigo-400','bg-green-400'];
      const tlHtml = tlSteps.map((s,i) => {
        const done=!!s.ts, isLast=i===tlSteps.length-1;
        return `<div class="flex items-start gap-2.5">
          <div class="flex flex-col items-center ${isLast?'':'min-h-[36px]'}">
            <div class="size-2.5 rounded-full flex-shrink-0 mt-0.5 ${done?dotColors[i]:'bg-white border-2 border-gray-300'}"></div>
            ${!isLast?`<div class="w-0.5 flex-1 min-h-[16px] mt-0.5 ${done?'bg-orange-200':'bg-gray-200'}"></div>`:''}
          </div>
          <div class="pb-2.5 flex-1">
            <span class="text-xs font-semibold ${done?'text-gray-700':'text-gray-400'}">${s.label}</span>
            <span class="text-[11px] ml-2 ${done?'text-orange-600':'text-gray-300'}">${s.ts?fmt(s.ts):'--'}</span>
          </div>
        </div>`;
      }).join('');

      // Order items
      let itemsHtml='';
      if(items.length>0){
        const rows=items.map(it=>{
          const unitDisp=it.unit_type==='piece'?'pcs':it.unit_type;
          const lineTotal=(parseFloat(it.price)*parseFloat(it.quantity)).toFixed(2);
          return `<div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-gray-800 truncate">${esc(it.product_name)}</p>
              <p class="text-[11px] text-gray-400">${esc(it.variant_name)} &middot; ${parseFloat(it.quantity)} ${unitDisp}</p>
            </div>
            <div class="text-right shrink-0 ml-3">
              <p class="text-xs font-bold text-gray-800">&yen;${parseFloat(lineTotal).toLocaleString('en-PH',{minimumFractionDigits:2})}</p>
              <p class="text-[11px] text-gray-400">&yen;${parseFloat(it.price).toLocaleString('en-PH',{minimumFractionDigits:2})} each</p>
            </div>
          </div>`;
        }).join('');
        const sub=parseFloat(d.subtotal||0),fee=parseFloat(d.delivery_fee||0),disc=parseFloat(d.discount_amount||0),tot=parseFloat(d.total_price||0);
        itemsHtml=`<p class="form-section">Order Items</p>
          <div class="bg-gray-50 rounded-xl px-4 py-2">${rows}
            <div class="pt-2 space-y-1">
              <div class="flex justify-between text-xs text-gray-500"><span>Subtotal</span><span>\u20b1${sub.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>
              ${fee>0?`<div class="flex justify-between text-xs text-gray-500"><span>Delivery Fee</span><span>\u20b1${fee.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>`:''}
              ${disc>0?`<div class="flex justify-between text-xs text-green-600"><span>Discount${d.voucher_code?' ('+esc(d.voucher_code)+')':''}</span><span>-\u20b1${disc.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>`:''}
              <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t border-gray-200"><span>Total</span><span class="text-orange-600">\u20b1${tot.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>
            </div>
          </div>`;
      }

      // Payment
      let payHtml='';
      if(payment){
        const pBadge=payment.payment_status==='Paid'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700';
        payHtml=`<p class="form-section">Payment</p>
          <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold text-gray-700 capitalize">${esc(d.payment_method||'--')}</p>
              ${payment.reference_number && !String(payment.reference_number).startsWith('cs_')?`<p class="text-[11px] text-gray-400 mt-0.5">Ref: ${esc(payment.reference_number)}</p>`:''}
              ${payment.paid_at?`<p class="text-[11px] text-gray-400">${fmt(payment.paid_at)}</p>`:''}
            </div>
            <div class="text-right">
              <span class="text-xs font-bold px-2.5 py-1 rounded-full ${pBadge}">${esc(payment.payment_status)}</span>
              <p class="text-sm font-bold text-gray-800 mt-1">\u20b1${parseFloat(payment.amount||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</p>
            </div>
          </div>`;
      }

      // GPS map
      let mapHtml='';
      if(tracking.length>0){
        const last=tracking[tracking.length-1];
        mapHtml=`<p class="form-section">GPS Tracking (${tracking.length} points)</p>
          <div class="bg-gray-100 rounded-xl overflow-hidden mb-2" style="height:220px">
            <iframe width="100%" height="220" frameborder="0"
              src="https://maps.google.com/maps?q=${last.latitude},${last.longitude}&z=15&output=embed"
              style="border:0"></iframe>
          </div>
          <p class="text-[11px] text-gray-400">Last: ${last.latitude}, ${last.longitude} &middot; ${last.timestamp}</p>`;
      } else if(d.delivery_latitude&&d.delivery_longitude){
        mapHtml=`<p class="form-section">Delivery Location</p>
          <div class="bg-gray-100 rounded-xl overflow-hidden" style="height:220px">
            <iframe width="100%" height="220" frameborder="0"
              src="https://maps.google.com/maps?q=${d.delivery_latitude},${d.delivery_longitude}&z=15&output=embed"
              style="border:0"></iframe>
          </div>`;
      }

      // Proofs
      let proofsHtml='';
      if(proofs.length>0){
        proofsHtml=`<p class="form-section">Delivery Proofs (${proofs.length})</p>
          <div class="proof-grid">${proofs.map(p=>`
            <a href="../${esc(p.file_path)}" target="_blank" class="proof-thumb block" title="${esc(p.caption||'')}${p.uploaded_by?' \u2014 '+esc(p.uploaded_by):''}">
              <img src="../${esc(p.file_path)}" alt="proof" class="w-full h-full object-cover" onerror="this.parentElement.style.display='none'">
            </a>`).join('')}</div>`;
      }

      const failedHtml='';

      document.getElementById('detailContent').innerHTML=`
        <div class="space-y-4">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-bold px-2.5 py-1 rounded-full ${badgeCls}">${badgeLbl}</span>
            <a href="order_manage.php?order_id=${d.order_id}" class="text-xs text-orange-600 hover:underline font-semibold">View Full Order \u2197</a>
          </div>
          ${failedHtml}
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-xl p-3 space-y-1">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Customer</p>
              <p class="text-sm font-semibold text-gray-800">${esc(d.recipient_first_name)} ${esc(d.recipient_last_name)}</p>
              <p class="text-[11px] text-gray-500">${esc(d.recipient_phone||'--')}</p>
              <p class="text-[11px] text-gray-500">${esc(d.recipient_address)}, ${esc(d.city)}</p>
              ${d.delivery_notes?`<p class="text-[11px] text-orange-600 italic mt-1">"${esc(d.delivery_notes)}"</p>`:''}
            </div>
            <div class="bg-gray-50 rounded-xl p-3 space-y-1">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">${d.is_third_party?'3rd-Party':'Rider'}</p>
              ${d.is_third_party
                ?`<p class="text-sm font-semibold text-indigo-700">${esc(d.third_party_name||'--')}</p>
                  ${d.delivery_link?`<a href="${esc(d.delivery_link)}" target="_blank" class="text-[11px] text-indigo-500 hover:underline">\u2197 Tracking Link</a>`:''}`
                :`<p class="text-sm font-semibold text-gray-800">${esc(d.rider_name||'--')}</p>
                  <p class="text-[11px] text-gray-500">${esc(d.rider_phone||'--')}</p>
                  <p class="text-[11px] text-gray-500">${esc(d.vehicle_type||'')} ${d.vehicle_plate_number?'('+esc(d.vehicle_plate_number)+')':''}</p>`}
              ${d.notes?`<p class="text-[11px] text-gray-400 mt-1">${esc(d.notes)}</p>`:''}
              ${d.estimated_time?`<p class="text-[11px] text-gray-500">\u23f1 ${esc(d.estimated_time)}</p>`:''}
              ${d.estimated_distance?`<p class="text-[11px] text-gray-500">\ud83d\udccd ${esc(d.estimated_distance)}</p>`:''}
            </div>
          </div>
          <p class="form-section">Delivery Timeline</p>
          <div class="bg-gray-50 rounded-xl px-4 py-3">${tlHtml}</div>
          ${itemsHtml}
          ${payHtml}
          ${mapHtml}
          ${proofsHtml}
        </div>`;
    })
    .catch(err => {
      console.error('[fetch_deliveries] error:', err);
      document.getElementById('detailContent').innerHTML =
        `<p class="text-red-500 text-center p-4">Failed to load delivery details.<br>
         <span class="text-xs text-gray-400">${err && err.message ? err.message : 'Check browser console for details.'}</span></p>`;
    });
}

function fmt(dt){if(!dt)return'—';return new Date(dt.replace(' ','T')).toLocaleString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});}
function esc(v){if(v==null)return'';return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

// ── Fee CRUD ──────────────────────────────────────────────────────────────
function openAddFee(){document.getElementById('feeModalTitle').textContent='Add Delivery Fee';document.getElementById('feeAction').value='add_fee';document.getElementById('feeIdField').value='';document.getElementById('feeForm').reset();document.getElementById('feeIsActive').checked=true;openModal('feeModal');}
function editFee(fee){document.getElementById('feeModalTitle').textContent='Edit Delivery Fee';document.getElementById('feeAction').value='edit_fee';document.getElementById('feeIdField').value=fee.fee_id;document.getElementById('feeCity').value=fee.city;document.getElementById('feeAreaType').value=fee.area_type;document.getElementById('feeBase').value=fee.base_fee;document.getElementById('feeThreshold').value=fee.free_shipping_threshold||'';document.getElementById('feeIsActive').checked=!!parseInt(fee.is_active);openModal('feeModal');}
function confirmDeleteFee(id,city){document.getElementById('deleteMsg').innerHTML=`Delete delivery fee for <strong>${city}</strong>?`;document.getElementById('deleteAction').value='delete_fee';document.getElementById('deleteFeeId').value=id;openModal('deleteModal');}
function toggleFeeActive(id,active){const fd=new FormData();fd.append('action','toggle_fee');fd.append('fee_id',id);fd.append('is_active',active?'1':'0');fetch(PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)toast(d.message,'success');else toast('Error: '+d.message,'error');}).catch(()=>toast('Network error','error'));}
function filterFees(q){document.querySelectorAll('#feeTableBody .fee-row').forEach(r=>{r.style.display=r.dataset.city.includes(q.toLowerCase())?'':'none';});}

// ── Toast ─────────────────────────────────────────────────────────────────
function toast(msg,type='info'){const c={success:'bg-teal-600',error:'bg-red-600',info:'bg-gray-800',warning:'bg-orange-500'};const el=document.createElement('div');el.className=`${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-start gap-2 min-w-56 max-w-sm`;el.innerHTML=`<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none">✕</button>`;document.getElementById('toast-wrap').prepend(el);setTimeout(()=>el?.remove(),4500);}

function updateRefreshTime(){const el=document.getElementById('lastRefresh');if(el)el.textContent='Updated '+new Date().toLocaleTimeString('en-PH',{hour:'numeric',minute:'2-digit'});}
updateRefreshTime(); setInterval(updateRefreshTime,60000);
</script>

<?php $conn->close();?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>