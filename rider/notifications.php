<?php
/**
 * rider/notifications.php
 *
 * Shows all notifications for this rider.
 * Scope: target_role='rider' AND (target_user_id = account_id OR target_user_id IS NULL).
 * Marks ALL as read on page open.
 * Riders CANNOT see other riders' notifications.
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
$rq = $conn->prepare("
    SELECT r.rider_id, r.image, r.is_available,
           COALESCE(r.full_name, CONCAT(a.first_name,' ',a.last_name)) AS display_name,
           a.first_name, a.last_name
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.account_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$rq->bind_param('i', $rider_account_id);
$rq->execute();
$rider = $rq->get_result()->fetch_assoc();
if (!$rider) { header('Location: ../index.php'); exit; }

// ── Count unread BEFORE marking read ──────────────────────────────────────
$cntStmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM order_notifications
    WHERE target_role = 'rider'
      AND (target_user_id = ? OR target_user_id IS NULL)
      AND is_read = 0
");
$cntStmt->bind_param('i', $rider_account_id);
$cntStmt->execute();
$unreadCount = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];

// ── Mark ALL as read now that rider has opened the page ────────────────────
$markStmt = $conn->prepare("
    UPDATE order_notifications
    SET is_read = 1
    WHERE target_role = 'rider'
      AND (target_user_id = ? OR target_user_id IS NULL)
      AND is_read = 0
");
$markStmt->bind_param('i', $rider_account_id);
$markStmt->execute();

// ── Fetch ALL notifications for this rider (newest first, max 200) ─────────
$nStmt = $conn->prepare("
    SELECT n.notif_id,
           n.message,
           n.is_read,
           n.created_at,
           o.order_code,
           o.order_status,
           o.total_price
    FROM order_notifications n
    JOIN orders o ON o.order_id = n.order_id
    WHERE n.target_role = 'rider'
      AND (n.target_user_id = ? OR n.target_user_id IS NULL)
    ORDER BY n.created_at DESC
    LIMIT 200
");
$nStmt->bind_param('i', $rider_account_id);
$nStmt->execute();
$notifications = $nStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Icon + ring color per message content ─────────────────────────────────
function notifStyle(string $msg): array {
    $m = strtolower($msg);
    if (str_contains($m, 'assigned') || str_contains($m, 'new delivery'))
        return ['icon'=>'🛵','ring'=>'border-orange-300 bg-orange-50'];
    if (str_contains($m, 'accepted'))
        return ['icon'=>'👍','ring'=>'border-blue-200 bg-blue-50'];
    if (str_contains($m, 'picked up') || str_contains($m, 'pickup'))
        return ['icon'=>'📦','ring'=>'border-indigo-200 bg-indigo-50'];
    if (str_contains($m, 'out for delivery'))
        return ['icon'=>'🚚','ring'=>'border-purple-200 bg-purple-50'];
    if (str_contains($m, 'delivered'))
        return ['icon'=>'✅','ring'=>'border-green-200 bg-green-50'];
    if (str_contains($m, 'cancel'))
        return ['icon'=>'✕', 'ring'=>'border-red-200 bg-red-50'];
    if (str_contains($m, 'proof'))
        return ['icon'=>'📷','ring'=>'border-teal-200 bg-teal-50'];
    if (str_contains($m, 'reassign'))
        return ['icon'=>'🔄','ring'=>'border-yellow-200 bg-yellow-50'];
    return    ['icon'=>'🔔','ring'=>'border-gray-200 bg-gray-50'];
}

$orderStatusBadge = [
    'Pending'        => 'bg-yellow-100 text-yellow-700',
    'Processing'     => 'bg-blue-100 text-blue-700',
    'OutForDelivery' => 'bg-purple-100 text-purple-700',
    'Delivered'      => 'bg-green-100 text-green-700',
    'Cancelled'      => 'bg-red-100 text-red-700',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | SJFBI Rider</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body { font-family: 'Lexend', sans-serif; }
    .notif-row { transition: background .15s; }
    .notif-row:hover { background: #fafafa; }
  </style>
</head>
<body class="bg-gray-50">

<!-- Header -->
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
      <div class="text-sm font-bold text-gray-800">Notifications</div>
      <div class="text-xs text-gray-400"><?= htmlspecialchars($rider['display_name']) ?></div>
    </div>
  </div>
  <?php if ($unreadCount > 0): ?>
  <span class="text-xs bg-orange-100 text-orange-700 font-bold px-2.5 py-1 rounded-full"><?= $unreadCount ?> new</span>
  <?php endif; ?>
</header>

<div id="toast-wrap" class="fixed bottom-20 right-4 flex flex-col gap-2 z-[60]"></div>

<div class="max-w-2xl mx-auto px-4 py-5 space-y-4">

  <?php if ($unreadCount > 0): ?>
  <div class="flex items-center gap-3 bg-orange-50 border border-orange-200 rounded-2xl px-4 py-3">
    <span class="text-2xl">🔔</span>
    <p class="text-sm text-orange-700 font-medium">
      You had <strong><?= $unreadCount ?></strong> unread notification<?= $unreadCount > 1 ? 's' : '' ?> — all marked as read.
    </p>
  </div>
  <?php endif; ?>

  <?php if (empty($notifications)): ?>
  <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm">
    <div class="text-5xl mb-3">🔕</div>
    <p class="text-sm font-semibold text-gray-500">No notifications yet</p>
    <p class="text-xs text-gray-400 mt-1">Delivery assignments and updates will appear here.</p>
  </div>

  <?php else:
    $grouped   = [];
    foreach ($notifications as $n) {
        $day = date('Y-m-d', strtotime($n['created_at']));
        $grouped[$day][] = $n;
    }
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    foreach ($grouped as $day => $items):
      $dayLabel = match($day) {
          $today     => 'Today',
          $yesterday => 'Yesterday',
          default    => date('F j, Y', strtotime($day)),
      };
  ?>
  <div>
    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest px-1 mb-2"><?= $dayLabel ?></div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-100">
      <?php foreach ($items as $n):
        $meta   = notifStyle($n['message']);
        $sBadge = $orderStatusBadge[$n['order_status']] ?? 'bg-gray-100 text-gray-600';
        $sLabel = str_replace('OutForDelivery','Out for Delivery',$n['order_status']);
      ?>
      <div class="notif-row px-4 py-3.5 flex items-start gap-3">
        <div class="size-10 rounded-xl border <?= $meta['ring'] ?> flex items-center justify-center text-lg shrink-0 mt-0.5">
          <?= $meta['icon'] ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm text-gray-800 leading-snug"><?= htmlspecialchars($n['message']) ?></p>
          <div class="flex items-center gap-2 mt-1.5 flex-wrap">
            <span class="text-xs font-bold text-orange-600"><?= htmlspecialchars($n['order_code']) ?></span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $sBadge ?>"><?= $sLabel ?></span>
            <span class="text-xs text-gray-400">₱<?= number_format($n['total_price'], 2) ?></span>
          </div>
        </div>
        <div class="text-[11px] text-gray-400 shrink-0 pt-0.5 whitespace-nowrap">
          <?= date('g:i A', strtotime($n['created_at'])) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>

</div>

<?php include './components/navigation.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>