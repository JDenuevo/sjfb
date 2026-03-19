<?php
/**
 * rider/deliveries.php
 * Column renames applied:
 *   riders:     rider_name (was full_name)
 *   accounts:   account_first_name/last_name
 *   orders:     recipient_first_name/last_name/address (was first_name/last_name/address)
 *   deliveries: delivery_status (was status)
 */
session_start();
require_once '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinasrider']) || $_SESSION['loggedinasrider'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../sign_in.php'); exit;
}
if ($_SESSION['role'] !== 'rider') { header('Location: ../index.php'); exit; }

$rider_account_id = (int)$_SESSION['account_id'];

// ── Rider profile ──────────────────────────────────────────────────────────
// Uses renamed columns: rider_name, account_first_name/last_name
$rq = $conn->prepare("
    SELECT r.rider_id, r.image, r.is_available,
           COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS display_name,
           a.account_first_name AS first_name,
           a.account_last_name  AS last_name
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.account_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$rq->bind_param('i', $rider_account_id);
$rq->execute();
$rider = $rq->get_result()->fetch_assoc();
if (!$rider) { header('Location: ../index.php'); exit; }
$rider_id = (int)$rider['rider_id'];

// ── Stats summary ──────────────────────────────────────────────────────────
// Uses renamed column: delivery_status (was status)
$statsStmt = $conn->prepare("
    SELECT
        COUNT(*)                                                              AS total,
        SUM(d.delivery_status = 'delivered')                                 AS completed,
        SUM(d.delivery_status IN ('pending_acceptance','accepted','picked_up','in_transit')) AS active,
        SUM(d.delivery_status IN ('reassigned','cancelled'))                 AS cancelled,
        SUM(CASE WHEN d.delivery_status='delivered' THEN o.total_price ELSE 0 END) AS total_earnings
    FROM deliveries d
    JOIN orders o ON o.order_id = d.order_id
    WHERE d.rider_id = ?
");
$statsStmt->bind_param('i', $rider_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ── Filter ─────────────────────────────────────────────────────────────────
$filterStatus   = $_GET['status'] ?? 'all';
$allowedFilters = ['all','delivered','active','cancelled'];
if (!in_array($filterStatus, $allowedFilters)) $filterStatus = 'all';

// Uses renamed column: delivery_status (was status)
$whereExtra = match($filterStatus) {
    'delivered' => "AND d.delivery_status = 'delivered'",
    'active'    => "AND d.delivery_status IN ('pending_acceptance','accepted','picked_up','in_transit')",
    'cancelled' => "AND d.delivery_status IN ('reassigned','cancelled')",
    default     => '',
};

$fcStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM deliveries d WHERE d.rider_id = ? $whereExtra");
$fcStmt->bind_param('i', $rider_id);
$fcStmt->execute();
$filteredTotal = (int)$fcStmt->get_result()->fetch_assoc()['cnt'];
$totalPages    = (int)ceil($filteredTotal / $perPage);

// ── Deliveries list ────────────────────────────────────────────────────────
// Uses renamed columns:
//   delivery_status (was status),
//   recipient_first_name/last_name/address (was first_name/last_name/address)
$dStmt = $conn->prepare("
    SELECT d.delivery_id,
           d.order_id,
           d.delivery_status,
           d.is_third_party,
           d.third_party_name,
           d.assigned_at,
           d.accepted_at,
           d.picked_up_at,
           d.delivered_at,
           d.estimated_time,
           d.estimated_distance,
           o.order_code,
           o.order_status,
           o.recipient_first_name AS first_name,
           o.recipient_last_name  AS last_name,
           o.total_price,
           o.delivery_address,
           o.recipient_address    AS address,
           o.city,
           o.delivery_latitude,
           o.delivery_longitude
    FROM deliveries d
    JOIN orders o ON o.order_id = d.order_id
    WHERE d.rider_id = ? $whereExtra
    ORDER BY d.assigned_at DESC
    LIMIT ? OFFSET ?
");
$dStmt->bind_param('iii', $rider_id, $perPage, $offset);
$dStmt->execute();
$deliveries = $dStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Proof photos (batch) ───────────────────────────────────────────────────
$proofsByDelivery = [];
if (!empty($deliveries)) {
    $dids = implode(',', array_map(fn($d) => (int)$d['delivery_id'], $deliveries));
    $pRes = $conn->query("
        SELECT delivery_id, proof_id, file_path, caption, uploaded_at
        FROM delivery_proofs
        WHERE delivery_id IN ($dids)
        ORDER BY uploaded_at ASC
    ");
    if ($pRes) while ($row = $pRes->fetch_assoc()) {
        $proofsByDelivery[(int)$row['delivery_id']][] = $row;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────
$dlStatusColor = [
    'pending_acceptance' => 'bg-yellow-100 text-yellow-800',
    'accepted'           => 'bg-blue-100 text-blue-800',
    'picked_up'          => 'bg-indigo-100 text-indigo-800',
    'in_transit'         => 'bg-purple-100 text-purple-800',
    'delivered'          => 'bg-green-100 text-green-800',
    'reassigned'         => 'bg-gray-100 text-gray-500',
    'cancelled'          => 'bg-red-100 text-red-700',
];
$dlStatusLabel = array_merge(DELIVERY_STATUS_LABELS, [
    'reassigned' => 'Reassigned',
    'cancelled'  => 'Cancelled',
]);

function fmtDate(?string $ts): string {
    if (!$ts) return '—';
    return date('M j, Y g:i A', strtotime($ts));
}
function fmtShort(?string $ts): string {
    if (!$ts) return '—';
    return date('M j, g:i A', strtotime($ts));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deliveries | SJFBI Rider</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body { font-family: 'Lexend', sans-serif; }
    .delivery-card { transition: box-shadow .2s; }
    .delivery-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
    .proof-thumb { width: 64px; height: 64px; border-radius: .5rem; object-fit: cover; border: 2px solid #e5e7eb; cursor: pointer; transition: transform .15s; }
    .proof-thumb:hover { transform: scale(1.05); }
    .timeline-dot { width: 10px; height: 10px; border-radius: 9999px; border: 2px solid; flex-shrink: 0; margin-top: 3px; }
    .timeline-line { width: 2px; flex: 1; min-height: 16px; margin-left: 4px; }
    #lightbox { display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:100;align-items:center;justify-content:center;padding:1rem; }
    #lightbox.open { display:flex; }
    #lightbox img { max-width:100%;max-height:90vh;border-radius:.75rem;object-fit:contain; }
    .filter-pill { padding:.35rem 1rem;border-radius:9999px;font-size:.75rem;font-weight:600;border:1.5px solid;transition:all .15s;cursor:pointer;text-decoration:none; }
    .filter-pill.active { background:#f97316;border-color:#f97316;color:#fff; }
    .filter-pill:not(.active) { background:#fff;border-color:#e5e7eb;color:#6b7280; }
    .filter-pill:not(.active):hover { border-color:#f97316;color:#f97316; }
  </style>
</head>
<body class="bg-gray-50">

<header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
  <div class="flex items-center gap-3">
    <?php if (!empty($rider['image'])): ?>
    <img src="../<?= htmlspecialchars($rider['image']) ?>" class="size-9 rounded-xl object-cover border border-gray-100 shrink-0">
    <?php else: ?>
    <div class="size-9 rounded-xl bg-purple-100 flex items-center justify-center text-sm font-bold text-purple-600 shrink-0">
      <?= strtoupper(substr($rider['first_name'],0,1).substr($rider['last_name'],0,1)) ?>
    </div>
    <?php endif; ?>
    <div>
      <div class="text-sm font-bold text-gray-800">Delivery History</div>
      <div class="text-xs text-gray-400"><?= htmlspecialchars($rider['display_name']) ?></div>
    </div>
  </div>
  <span class="text-xs bg-gray-100 text-gray-600 font-semibold px-2.5 py-1 rounded-full"><?= $filteredTotal ?> total</span>
</header>

<div id="lightbox" onclick="closeLightbox()">
  <img id="lightbox-img" src="" alt="Proof photo">
</div>

<div id="toast-wrap" class="fixed bottom-20 right-4 flex flex-col gap-2 z-[60]"></div>

<div class="max-w-2xl mx-auto px-4 py-5 space-y-4">

  <!-- Stats -->
  <div class="grid grid-cols-2 gap-3">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5">
      <div class="text-2xl font-bold text-gray-800"><?= (int)$stats['completed'] ?></div>
      <div class="text-xs text-gray-400 mt-0.5">Completed deliveries</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5">
      <div class="text-2xl font-bold text-orange-600">₱<?= number_format((float)$stats['total_earnings'], 2) ?></div>
      <div class="text-xs text-gray-400 mt-0.5">Total order value</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5">
      <div class="text-2xl font-bold text-blue-600"><?= (int)$stats['active'] ?></div>
      <div class="text-xs text-gray-400 mt-0.5">Active right now</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5">
      <div class="text-2xl font-bold text-gray-400"><?= (int)$stats['cancelled'] ?></div>
      <div class="text-xs text-gray-400 mt-0.5">Reassigned / cancelled</div>
    </div>
  </div>

  <!-- Filter pills -->
  <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
    <?php
    $filters = ['all'=>'All','active'=>'Active','delivered'=>'Completed','cancelled'=>'Cancelled'];
    foreach ($filters as $val => $label):
      $active = ($filterStatus === $val) ? 'active' : '';
    ?>
    <a href="?status=<?= $val ?>&page=1" class="filter-pill <?= $active ?> whitespace-nowrap"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Delivery list -->
  <?php if (empty($deliveries)): ?>
  <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm">
    <div class="text-5xl mb-3">📭</div>
    <p class="text-sm font-semibold text-gray-500">No deliveries here yet</p>
    <p class="text-xs text-gray-400 mt-1">Check a different filter or wait for new assignments.</p>
  </div>

  <?php else: foreach ($deliveries as $d):
    $badge   = $dlStatusColor[$d['delivery_status']] ?? 'bg-gray-100 text-gray-600';
    $label   = $dlStatusLabel[$d['delivery_status']] ?? $d['delivery_status'];
    $proofs  = $proofsByDelivery[(int)$d['delivery_id']] ?? [];
    $addr    = $d['delivery_address'] ?: ($d['address'].', '.$d['city']);
    $isDone  = $d['delivery_status'] === 'delivered';
    $isActive= in_array($d['delivery_status'], ['pending_acceptance','accepted','picked_up','in_transit']);
  ?>
  <div class="delivery-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <div class="px-5 pt-4 pb-3 flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-base font-bold text-orange-600"><?= htmlspecialchars($d['order_code']) ?></span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $badge ?>"><?= $label ?></span>
          <?php if ($isActive): ?>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-700 animate-pulse">● Active</span>
          <?php endif; ?>
        </div>
        <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></div>
        <div class="text-xs text-gray-400 mt-0.5 flex items-start gap-1">
          <svg class="size-3 shrink-0 mt-0.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <?= htmlspecialchars($addr) ?>
        </div>
      </div>
      <div class="text-right shrink-0">
        <div class="text-sm font-bold text-gray-800">₱<?= number_format($d['total_price'], 2) ?></div>
        <?php if ($d['delivery_latitude'] && $d['delivery_longitude']): ?>
        <a href="https://maps.google.com/?q=<?= $d['delivery_latitude'] ?>,<?= $d['delivery_longitude'] ?>"
           target="_blank" class="text-[10px] text-blue-500 hover:underline mt-0.5 block">Maps ↗</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Timeline -->
    <div class="px-5 pb-4">
      <div class="bg-gray-50 rounded-xl px-3 py-3 space-y-0">
        <?php
        $timelineSteps = [
            ['label'=>'Assigned',   'done'=>!!$d['assigned_at'],  'ts'=>$d['assigned_at'],  'color'=>'border-orange-400 bg-orange-400'],
            ['label'=>'Accepted',   'done'=>!!$d['accepted_at'],  'ts'=>$d['accepted_at'],  'color'=>'border-blue-400 bg-blue-400'],
            ['label'=>'Picked Up',  'done'=>!!$d['picked_up_at'], 'ts'=>$d['picked_up_at'], 'color'=>'border-indigo-400 bg-indigo-400'],
            ['label'=>'Delivered',  'done'=>!!$d['delivered_at'], 'ts'=>$d['delivered_at'], 'color'=>'border-green-400 bg-green-400'],
        ];
        $stepCount = count($timelineSteps);
        foreach ($timelineSteps as $idx => $step):
          $isLast = ($idx === $stepCount - 1);
          $dotCls  = $step['done'] ? $step['color'] : 'border-gray-300 bg-white';
          $lineCls = $step['done'] ? 'bg-orange-200' : 'bg-gray-200';
        ?>
        <div class="flex items-start gap-2.5">
          <div class="flex flex-col items-center <?= $isLast ? '' : 'min-h-[36px]' ?>">
            <div class="timeline-dot <?= $dotCls ?>"></div>
            <?php if (!$isLast): ?><div class="timeline-line <?= $lineCls ?>"></div><?php endif; ?>
          </div>
          <div class="pb-2.5 flex-1 min-w-0">
            <div class="flex items-baseline gap-2">
              <span class="text-xs font-semibold <?= $step['done'] ? 'text-gray-700' : 'text-gray-400' ?>"><?= $step['label'] ?></span>
              <?php if ($step['ts']): ?>
              <span class="text-[11px] text-gray-400"><?= fmtShort($step['ts']) ?></span>
              <?php else: ?>
              <span class="text-[11px] text-gray-300">—</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($d['estimated_time'] || $d['estimated_distance']): ?>
        <div class="mt-1 pt-2 border-t border-gray-200 flex gap-4 text-xs text-gray-500">
          <?php if ($d['estimated_time']): ?>
          <span>⏱ <?= htmlspecialchars($d['estimated_time']) ?></span>
          <?php endif; ?>
          <?php if ($d['estimated_distance']): ?>
          <span>📍 <?= htmlspecialchars($d['estimated_distance']) ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Proof photos -->
    <?php if (!empty($proofs)): ?>
    <div class="px-5 pb-4">
      <div class="flex items-center gap-2 mb-2">
        <span class="text-xs font-semibold text-gray-600">📷 Proof Photos</span>
        <span class="text-[10px] bg-teal-100 text-teal-700 font-semibold px-1.5 py-0.5 rounded-full"><?= count($proofs) ?></span>
      </div>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($proofs as $p): ?>
        <img src="../<?= htmlspecialchars($p['file_path']) ?>"
             class="proof-thumb"
             onclick="openLightbox('../<?= htmlspecialchars($p['file_path']) ?>')"
             alt="<?= htmlspecialchars($p['caption'] ?? 'Delivery proof') ?>"
             title="<?= htmlspecialchars($p['caption'] ?? '') ?> — <?= fmtShort($p['uploaded_at']) ?>">
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($isActive): ?>
    <div class="px-5 pb-4">
      <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-semibold bg-orange-600 hover:bg-orange-500 text-white rounded-xl transition-colors">
        🛵 Go to Active Dashboard
      </a>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; endif; ?>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="flex items-center justify-center gap-2 pt-2">
    <?php if ($page > 1): ?>
    <a href="?status=<?= $filterStatus ?>&page=<?= $page-1 ?>" class="px-3 py-1.5 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">← Prev</a>
    <?php endif; ?>
    <span class="text-xs text-gray-500 font-medium">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
    <a href="?status=<?= $filterStatus ?>&page=<?= $page+1 ?>" class="px-3 py-1.5 text-xs font-semibold border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Next →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<?php include './components/navigation.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script>
function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>
</body>
</html>