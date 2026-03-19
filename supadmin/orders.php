<?php
/**
 * supadmin/orders.php
 *
 * Order list — replaces orders.php + order_list.php (merged).
 * Quick-approve on the list calls order_process.php via fetch() with action=approve_order.
 * Quick-expand items call order_process.php?action=get_order_detail (replaces fetch_orders.php).
 * Full management → order_manage.php
 */
session_start();
include '../conn.php';
require_once '../functions/order_helper.php';

// ── Auth ──────────────────────────────────────────────────────────────────
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php');
    exit;
}

// ── Filters ───────────────────────────────────────────────────────────────
$whereConditions = ["p.payment_status IN ('Pending','Paid','Refunded')"];
$params = [];
$types  = '';

if (!empty($_GET['status'])) {
    $whereConditions[] = 'o.order_status = ?';
    $params[] = $_GET['status'];
    $types   .= 's';
}
if (!empty($_GET['search'])) {
    $s = '%' . $_GET['search'] . '%';
    "(o.order_code LIKE ? OR CONCAT(o.recipient_first_name,' ',o.recipient_last_name) LIKE ? OR o.recipient_email LIKE ?)";
    array_push($params, $s, $s, $s);
    $types .= 'sss';
}
$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// ── Pagination ────────────────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$countSQL = "SELECT COUNT(*) AS total FROM orders o
    LEFT JOIN payments p ON p.order_id=o.order_id AND p.payment_id=(SELECT MAX(p2.payment_id) FROM payments p2 WHERE p2.order_id=o.order_id)
    {$whereClause}";

if ($params) {
    $cs = $conn->prepare($countSQL);
    $cs->bind_param($types, ...$params);
    $cs->execute();
    $totalItems = (int)$cs->get_result()->fetch_assoc()['total'];
} else {
    $totalItems = (int)$conn->query($countSQL)->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// ── Main query ────────────────────────────────────────────────────────────
$mainSQL = "SELECT o.order_id, o.order_code, o.order_date, o.order_status,
               o.total_price, o.payment_method, o.is_guest_order,
               o.recipient_first_name, o.recipient_last_name, o.recipient_address, o.city, o.postal_code, o.recipient_email,
               o.assigned_rider_id, o.delivery_fee,
               COALESCE(r.rider_name, CONCAT(ra.account_first_name,' ',ra.account_last_name)) AS rider_name,
               p.payment_status, p.paid_at
            FROM orders o
            LEFT JOIN payments p ON p.order_id=o.order_id AND p.payment_id=(SELECT MAX(p2.payment_id) FROM payments p2 WHERE p2.order_id=o.order_id)
            LEFT JOIN riders r   ON r.rider_id=o.assigned_rider_id
            LEFT JOIN accounts ra ON ra.account_id=r.account_id
            {$whereClause}
            ORDER BY o.order_date DESC
            LIMIT ? OFFSET ?";

$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt      = $conn->prepare($mainSQL);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$result = $stmt->get_result();

// ── Stats ─────────────────────────────────────────────────────────────────
$counts = getOrderCounts($conn);

// ── Display config ────────────────────────────────────────────────────────
$statusConf = [
    'Paid'           => ['badge' => 'bg-green-100 text-green-800', 'card' => 'bg-green-50 border-green-200'],
    'Pending'        => ['badge' => 'bg-yellow-100 text-yellow-800', 'card' => 'bg-yellow-50 border-yellow-200'],
    'Processing'     => ['badge' => 'bg-blue-100 text-blue-800',     'card' => 'bg-blue-50 border-blue-200'],
    'OutForDelivery' => ['badge' => 'bg-purple-100 text-purple-800', 'card' => 'bg-purple-50 border-purple-200'],
    'Delivered'      => ['badge' => 'bg-green-100 text-green-800',   'card' => 'bg-green-50 border-green-200'],
    'Cancelled'      => ['badge' => 'bg-red-100 text-red-800',       'card' => 'bg-red-50 border-red-200'],
];
$paymentConf  = ['Paid'=>'bg-green-100 text-green-700','Pending'=>'bg-yellow-100 text-yellow-700','Failed'=>'bg-red-100 text-red-700','Refunded'=>'bg-blue-100 text-blue-700'];
$methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','qrph'=>'QR Ph','cod'=>'COD','card'=>'Card'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders | SJFBI Admin</title>
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
    .order-row { border-left: 3px solid transparent; transition: border-color .15s, background .15s; }
    .order-row:hover { border-left-color: #f97316; background: #fff7ed33; }
    .expand-row { animation: slideDown .2s ease; }
    @keyframes slideDown { from { opacity:0; transform:translateY(-4px) } to { opacity:1; transform:translateY(0) } }
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

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

  <!-- Stat cards -->
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
    <?php foreach ($statusConf as $status => $cfg): ?>
    <a href="?status=<?= $status ?>"
       class="<?= $cfg['card'] ?> border rounded-xl p-3 text-center hover:shadow-sm transition-shadow <?= ($_GET['status'] ?? '') === $status ? 'ring-2 ring-orange-400 ring-offset-1' : '' ?>">
      <div class="text-2xl font-bold text-gray-800"><?= $counts[$status] ?? 0 ?></div>
      <div class="text-xs text-gray-500 mt-0.5"><?= STATUS_LABELS[$status] ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Table card -->
  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

    <!-- Filters -->
    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
      <div class="flex-1">
        <h2 class="text-lg font-semibold text-gray-800">All Orders</h2>
        <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> orders</p>
      </div>
      <form method="GET" class="flex flex-wrap gap-2">
        <select name="status" onchange="this.form.submit()"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-400">
          <option value="">All Statuses</option>
          <?php foreach (STATUS_LABELS as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($_GET['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
          <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                 placeholder="Code, name, email…"
                 class="text-sm px-3 py-2 focus:outline-none w-44">
          <button type="submit" class="px-3 py-2 text-orange-500 hover:bg-orange-50">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>
        </div>
        <?php if (!empty($_GET['status']) || !empty($_GET['search'])): ?>
        <a href="orders.php" class="text-sm text-gray-400 hover:text-orange-500 py-2 px-1">✕ Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-8"></th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rider</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" id="orders-tbody">

          <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="8" class="py-16 text-center text-gray-400 text-sm">No orders found.</td></tr>
          <?php else: while ($row = $result->fetch_assoc()):
            $sBadge = $statusConf[$row['order_status']]['badge'] ?? 'bg-gray-100 text-gray-700';
            $pBadge = $paymentConf[$row['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
            $mLabel = $methodLabels[$row['payment_method'] ?? ''] ?? ucfirst($row['payment_method'] ?? '—');
          ?>

          <!-- Main row -->
          <tr class="order-row cursor-pointer" id="row-<?= $row['order_id'] ?>" onclick="toggleExpand(<?= $row['order_id'] ?>)">
            <!-- Toggle chevron -->
            <td class="px-5 py-3 text-gray-300">
              <svg id="chev-<?= $row['order_id'] ?>" class="size-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
            </td>
            <!-- Order -->
            <td class="px-4 py-3">
              <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" onclick="event.stopPropagation()"
                 class="text-sm font-bold text-orange-600 hover:text-orange-700 hover:underline"><?= htmlspecialchars($row['order_code']) ?></a>
              <div class="text-xs text-gray-400"><?= date('M j, Y · g:i A', strtotime($row['order_date'])) ?></div>
            </td>
            <!-- Customer -->
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($row['recipient_first_name'].' '.$row['recipient_last_name']) ?></div>
              <div class="text-xs text-gray-400"><?= $row['is_guest_order'] ? 'Guest' : 'Member' ?></div>
            </td>
            <!-- Order status -->
            <td class="px-4 py-3">
                <span class="order-status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $sBadge ?>">
                    <?= STATUS_LABELS[$row['order_status']] ?? $row['order_status'] ?>
                </span>
            </td>
            <!-- Payment -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $pBadge ?>"><?= $row['payment_status'] ?? 'Pending' ?></span>
              <div class="text-xs text-gray-400 mt-0.5"><?= $mLabel ?></div>
            </td>
            <!-- Rider -->
            <td class="px-4 py-3">
              <?php if (!empty($row['rider_name'])): ?>
              <span class="text-xs font-medium text-gray-700"><?= htmlspecialchars($row['rider_name']) ?></span>
              <?php else: ?>
              <span class="text-xs text-gray-400">—</span>
              <?php endif; ?>
            </td>
            <!-- Total -->
            <td class="px-4 py-3 text-right">
              <span class="text-sm font-bold text-gray-800">₱<?= number_format($row['total_price'], 2) ?></span>
            </td>
            <!-- Actions -->
            <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
              <div class="inline-flex gap-1.5">
                <?php if ($row['order_status'] === 'Pending'): ?>
                <!-- Approve button: AJAX → order_process.php action=approve_order -->
                <button id="approve-btn-<?= $row['order_id'] ?>"
                        onclick="quickApprove(<?= $row['order_id'] ?>, this)"
                        class="size-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                        title="Approve & Process">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
                <?php endif; ?>
                <a href="order_manage.php?order_id=<?= $row['order_id'] ?>"
                   class="size-8 flex items-center justify-center rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-100 transition-colors"
                   title="Manage Order">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5"/><path d="M17.5 2.5a2.121 2.121 0 0 1 3 3L12 14l-4 1 1-4 7.5-7.5z"/></svg>
                </a>
              </div>
            </td>
          </tr>

          <!-- Expand row — items loaded via order_process.php action=get_order_detail (replaces fetch_orders.php) -->
          <tr id="expand-<?= $row['order_id'] ?>" class="hidden expand-row bg-orange-50/20">
            <td colspan="8" class="px-6 py-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <!-- Customer details -->
                <div class="space-y-1.5">
                  <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Customer Details</p>
                  <p class="text-xs text-gray-600"><span class="font-medium">Email:</span> <?= htmlspecialchars($row['recipient_email']) ?></p>
                  <p class="text-xs text-gray-600"><span class="font-medium">Address:</span> <?= htmlspecialchars($row['recipient_address'].', '.$row['city']) ?></p>
                  <p class="text-xs text-gray-600"><span class="font-medium">Type:</span> <?= $row['is_guest_order'] ? 'Guest' : 'Registered Member' ?></p>
                </div>
                <!-- Order items — fetched via get_order_detail -->
                <div class="sm:col-span-2">
                  <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Order Items</p>
                  <div id="items-<?= $row['order_id'] ?>" class="space-y-1 min-h-[32px]">
                    <div class="text-xs text-gray-400 italic">Loading…</div>
                  </div>
                  <!-- Delivery Fee (END / Summary Style) -->
                  <div class="flex justify-between items-center mt-3 pt-2 border-t">
                    <p class="text-xs font-medium text-gray-600">Delivery Fee</p>
                    <p class="text-xs font-semibold text-gray-800">
                      ₱<?= number_format($row['delivery_fee'], 2) ?>
                    </p>
                  </div>
                  <div class="flex gap-2 mt-3">
                    <a href="order_manage.php?order_id=<?= $row['order_id'] ?>"
                       class="px-3 py-1.5 text-xs bg-orange-500 text-white hover:bg-orange-600 rounded-lg transition-colors">
                      Manage Order →
                    </a>
                  </div>
                </div>
              </div>
            </td>
          </tr>

          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-500"><?= $totalItems ?> orders · Page <?= $page ?> of <?= $totalPages ?></p>
      <div class="flex gap-1">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
           class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
           class="px-3 py-1.5 text-xs border rounded-lg <?= $i===$page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
           class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /table card -->
</div>
</div>

<!-- Toast -->
<div id="toast-wrap" class="fixed bottom-5 right-5 flex flex-col gap-2 z-50"></div>

<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
const PROCESS = './functions/order_process.php';

// ── Toast ──────────────────────────────────────────────────────────────────
function toast(msg, type = 'info') {
  const c = { success: 'bg-teal-600', error: 'bg-red-600', info: 'bg-gray-800', warning: 'bg-orange-500' };
  const el = document.createElement('div');
  el.className = `${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 min-w-56 max-w-sm`;
  el.innerHTML = `<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none">✕</button>`;
  document.getElementById('toast-wrap').prepend(el);
  setTimeout(() => el?.remove(), 5000);
}

// ── Expand row — calls order_process.php action=get_order_detail ───────────
// Replaces the old fetch_orders.php entirely.
const loaded = new Set();

function toggleExpand(orderId) {
  const expandRow = document.getElementById('expand-' + orderId);
  const chev      = document.getElementById('chev-' + orderId);
  const isOpen    = !expandRow.classList.contains('hidden');

  // Close all
  document.querySelectorAll('[id^="expand-"]').forEach(r => r.classList.add('hidden'));
  document.querySelectorAll('[id^="chev-"]').forEach(c => c.style.transform = '');

  if (isOpen) return; // was open → just close

  expandRow.classList.remove('hidden');
  chev.style.transform = 'rotate(90deg)';

  if (loaded.has(orderId)) return; // already fetched

  const container = document.getElementById('items-' + orderId);

  // Uses get_order_detail which already exists in order_process.php
  fetch(`${PROCESS}?action=get_order_detail&order_id=${orderId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.ok) { container.innerHTML = `<div class="text-xs text-red-400">${data.msg}</div>`; return; }
      if (!data.items.length) { container.innerHTML = '<div class="text-xs text-gray-400">No items.</div>'; return; }

      container.innerHTML = data.items.map(item => {
        const total = (item.quantity * parseFloat(item.price)).toFixed(2);
        const img = item.image_path
          ? `<img src="../${item.image_path}" class="size-8 rounded object-cover shrink-0" onerror="this.style.display='none'">`
          : `<div class="size-8 rounded bg-gray-100 shrink-0"></div>`;
        return `<div class="flex items-center justify-between text-xs py-1.5 border-b border-gray-100 last:border-0 gap-2">
          <div class="flex items-center gap-2 min-w-0">
            ${img}
            <span class="font-medium text-gray-700 truncate">${item.product_name}</span>
            <span class="text-gray-400 shrink-0">(${item.variant_name})</span>
          </div>
          <span class="text-gray-600 shrink-0">×${item.quantity} &nbsp; <strong>₱${total}</strong></span>
        </div>`;
      }).join('');
      loaded.add(orderId);
    })
    .catch(() => { container.innerHTML = '<div class="text-xs text-red-400">Failed to load.</div>'; });
}

// ── Quick approve — calls order_process.php action=approve_order ─────────────
// Sends POST action=approve_order (aligned to order_process.php router).
// Shows stock check errors as toast WITHOUT reloading the page.
function quickApprove(orderId, btn) {
  btn.disabled = true;
  btn.innerHTML = `<svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>`;

  const fd = new FormData();
  fd.append('action', 'approve_order');   // ← matches order_process.php case 'approve_order'
  fd.append('order_id', orderId);

  fetch(PROCESS, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        toast('✅ ' + data.msg, 'success');
        // Update badge inline — no page reload needed
        const row   = document.getElementById('row-' + orderId);
        const badge = row?.querySelector('.order-status-badge');
        if (badge) {
          badge.className = 'order-status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800';
          badge.textContent = 'Processing';
        }
        btn.remove();
        loaded.delete(orderId); // force re-fetch if items were loaded
      } else {
        // Stock check failure — show which items are short
        if (data.shortfalls?.length) {
          const lines = data.shortfalls.map(s =>
            `<li>${s.product_name} (${s.variant_name}): need ${s.requested}, only ${s.available} available</li>`
          ).join('');
          toast(`⚠️ Cannot approve — stock insufficient:<ul class="mt-1 list-disc list-inside text-xs">${lines}</ul>`, 'error');
        } else {
          toast('⚠️ ' + data.msg, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = `<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
      }
    })
    .catch(() => {
      toast('Network error. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = `<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
    });
}
</script>
</body>
</html>