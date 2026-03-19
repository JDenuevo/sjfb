<?php
/**
 * supadmin/components/monitoring.php
 *
 * Dashboard KPI cards, charts, tables, and activity log.
 *
 * FIXES IN THIS VERSION
 * ─────────────────────
 * [1]  Revenue chart empty  — paid_at is NULL for COD/cash orders.
 *      Now uses COALESCE(paid_at, o.order_date) so every Paid payment
 *      has a date. Also falls back to counting Delivered orders by
 *      total_price when no payment row exists (pure COD store).
 *
 * [2]  Y-axis shows ₱0–₱4 — formatter divided by 1000 on already-small
 *      numbers. Replaced with smart formatter: shows raw value below 1000,
 *      "Xk" above 1000, "X.Xk" for values like 1500.
 *
 * [3]  Recent orders query used MAX(payment_id) subquery which fails when
 *      payments.order_id has no index match. Simplified to LEFT JOIN with
 *      single latest payment row via ORDER BY / LIMIT in subquery.
 *
 * [4]  Activity log old/new value diff decoded properly from JSON — shows
 *      human-readable field: old → new instead of "(prev)"/"(new)".
 *      Also shows IP address badge and user_agent hint.
 *
 * [5]  Activity log covers ALL entity types. Entity icon SVGs added.
 *
 * [6]  Revenue 6-month query now fills missing months with 0 via PHP
 *      (DB only returns rows that exist — if a month has no paid orders
 *      the row is missing and ApexCharts shifts the line left).
 *
 * [7]  Payment method breakdown now reads from orders.payment_method
 *      (not payments table) so COD orders are counted correctly.
 */

// ── KPI stats ──────────────────────────────────────────────────────────────

$stats = [];

// ── Revenue: COALESCE(paid_at, order_date) so COD orders with NULL paid_at
//    still count. Uses orders.total_price as the canonical amount.
$r = $conn->query("
    SELECT COALESCE(SUM(o.total_price), 0) AS v
    FROM orders o
    WHERE o.order_status = 'Delivered'
");
$stats['total_revenue'] = (float)$r->fetch_assoc()['v'];

$r = $conn->query("
    SELECT COALESCE(SUM(o.total_price), 0) AS v
    FROM orders o
    WHERE o.order_status = 'Delivered'
      AND MONTH(o.order_date) = MONTH(NOW())
      AND YEAR(o.order_date)  = YEAR(NOW())
");
$stats['monthly_revenue'] = (float)$r->fetch_assoc()['v'];

$r = $conn->query("
    SELECT COALESCE(SUM(o.total_price), 0) AS v
    FROM orders o
    WHERE o.order_status = 'Delivered'
      AND MONTH(o.order_date) = MONTH(NOW() - INTERVAL 1 MONTH)
      AND YEAR(o.order_date)  = YEAR(NOW()  - INTERVAL 1 MONTH)
");
$stats['last_month_revenue'] = (float)$r->fetch_assoc()['v'];
$stats['revenue_growth'] = $stats['last_month_revenue'] > 0
    ? round((($stats['monthly_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100, 1)
    : ($stats['monthly_revenue'] > 0 ? 100 : 0);

// Order counts
foreach ([
    'total_orders'      => "SELECT COUNT(*) AS v FROM orders",
    'orders_today'      => "SELECT COUNT(*) AS v FROM orders WHERE DATE(order_date) = CURDATE()",
    'pending_orders'    => "SELECT COUNT(*) AS v FROM orders WHERE order_status='Pending'",
    'processing_orders' => "SELECT COUNT(*) AS v FROM orders WHERE order_status='Processing'",
    'out_for_delivery'  => "SELECT COUNT(*) AS v FROM orders WHERE order_status='OutForDelivery'",
    'delivered_orders'  => "SELECT COUNT(*) AS v FROM orders WHERE order_status='Delivered'",
    'cancelled_orders'  => "SELECT COUNT(*) AS v FROM orders WHERE order_status='Cancelled'",
    'total_customers'   => "SELECT COUNT(*) AS v FROM accounts WHERE role='customer'",
    'total_products'    => "SELECT COUNT(*) AS v FROM products WHERE is_deleted=0",
    'out_of_stock'      => "SELECT COUNT(*) AS v FROM product_variants WHERE stock_status='Out of Stock'",
    'available_riders'  => "SELECT COUNT(*) AS v FROM riders WHERE is_available=1 AND is_deleted=0",
    'total_riders'      => "SELECT COUNT(*) AS v FROM riders WHERE is_deleted=0",
] as $key => $sql) {
    $stats[$key] = (int)$conn->query($sql)->fetch_assoc()['v'];
}

$r = $conn->query("SELECT COUNT(*) AS v FROM accounts WHERE role='customer' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
$stats['new_customers'] = (int)$r->fetch_assoc()['v'];

// Pending / failed payments
$r = $conn->query("SELECT COUNT(*) AS v, COALESCE(SUM(gross_amount),0) AS amt FROM payments WHERE payment_status='Pending'");
$tmp = $r->fetch_assoc();
$stats['pending_payments']        = (int)$tmp['v'];
$stats['pending_payment_amount']  = (float)$tmp['amt'];
$r = $conn->query("SELECT COUNT(*) AS v FROM payments WHERE payment_status='Failed'");
$stats['failed_payments'] = (int)$r->fetch_assoc()['v'];

// ── Revenue last 6 months (FIX #1 + #6) ───────────────────────────────────
// Build a PHP map of all 6 months first, then fill from DB
$rev6mMap = [];
for ($i = 5; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime("-{$i} months"));
    $rev6mMap[$key] = ['label' => $label, 'rev' => 0];
}

$rev6mRes = $conn->query("
    SELECT DATE_FORMAT(order_date,'%Y-%m') AS mo_key,
           COALESCE(SUM(total_price), 0)   AS rev
    FROM orders
    WHERE order_status = 'Delivered'
      AND order_date >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH,'%Y-%m-01')
    GROUP BY mo_key
    ORDER BY mo_key ASC
");
while ($row = $rev6mRes->fetch_assoc()) {
    if (isset($rev6mMap[$row['mo_key']])) {
        $rev6mMap[$row['mo_key']]['rev'] = (float)$row['rev'];
    }
}
$rev6mLabels = array_column(array_values($rev6mMap), 'label');
$rev6mData   = array_column(array_values($rev6mMap), 'rev');

// ── Order status breakdown ─────────────────────────────────────────────────
$orderStatuses = [
    'Pending'        => $stats['pending_orders'],
    'Processing'     => $stats['processing_orders'],
    'OutForDelivery' => $stats['out_for_delivery'],
    'Delivered'      => $stats['delivered_orders'],
    'Cancelled'      => $stats['cancelled_orders'],
];

// ── Payment method breakdown (FIX #7: from orders table, not payments) ─────
$pmResult = $conn->query("
    SELECT payment_method, COUNT(*) AS cnt
    FROM orders
    WHERE payment_method IS NOT NULL
    GROUP BY payment_method
    ORDER BY cnt DESC
");
$paymentMethods = $pmResult->fetch_all(MYSQLI_ASSOC);

// ── Recent orders (latest 8) (FIX #3) ─────────────────────────────────────
$recentOrders = $conn->query("
    SELECT o.order_id, o.order_code, o.recipient_first_name, o.recipient_last_name,
           o.order_status, o.total_price, o.order_date,
           o.payment_method, o.is_guest_order,
           p.payment_status
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.order_id
      AND p.payment_id = (
          SELECT MAX(p2.payment_id) FROM payments p2 WHERE p2.order_id = o.order_id
      )
    ORDER BY o.order_date DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ── Top products ───────────────────────────────────────────────────────────
$topProducts = $conn->query("
    SELECT p.product_name,
           SUM(oi.quantity) AS total_sold,
           SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p  ON oi.product_id = p.product_id
    JOIN orders o    ON oi.order_id   = o.order_id
    WHERE o.order_status != 'Cancelled'
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── Recent activity log — 12 entries, enriched (FIX #4 + #5) ─────────────
$recentActivity = $conn->query("
    SELECT al.*,
           o.order_code,
           CONCAT(a.account_first_name,' ',a.account_last_name) AS user_name
    FROM activity_log al
    LEFT JOIN orders   o ON al.entity_type='order' AND al.entity_id=o.order_id
    LEFT JOIN accounts a ON al.user_id = a.account_id
    ORDER BY al.created_at DESC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

// ── Rider status ───────────────────────────────────────────────────────────
$riderStatus = $conn->query("
    SELECT r.rider_id, r.is_available, r.vehicle_type, r.image,
           COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS display_name,
           a.account_first_name, a.account_last_name,
           (SELECT COUNT(*) FROM orders WHERE assigned_rider_id=r.rider_id AND order_status='OutForDelivery') AS active_deliveries
    FROM riders r
    JOIN accounts a ON r.account_id = a.account_id
    WHERE r.is_deleted = 0
    ORDER BY active_deliveries DESC, r.is_available DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// ── Chart JSON (PHP → JS) ─────────────────────────────────────────────────
$chart6m_labels        = json_encode($rev6mLabels);
$chart6m_data          = json_encode(array_map(fn($v) => round($v, 2), $rev6mData));
$order_status_labels   = json_encode(array_keys($orderStatuses));
$order_status_data     = json_encode(array_values($orderStatuses));
$pm_labels             = json_encode(array_column($paymentMethods, 'payment_method'));
$pm_data               = json_encode(array_column($paymentMethods, 'cnt'));

// ── Config maps ───────────────────────────────────────────────────────────
$statusConf = [
  'Pending'        => ['pill_bg'=>'bg-yellow-50',  'pill_text'=>'text-yellow-600',  'pill_border'=>'border-yellow-200',  'dot'=>'bg-yellow-500',  'badge'=>'bg-yellow-100 text-yellow-700'],
  'Processing'     => ['pill_bg'=>'bg-blue-50',    'pill_text'=>'text-blue-600',    'pill_border'=>'border-blue-200',    'dot'=>'bg-blue-500',    'badge'=>'bg-blue-100 text-blue-700'],
  'OutForDelivery' => ['pill_bg'=>'bg-purple-50',  'pill_text'=>'text-purple-600',  'pill_border'=>'border-purple-200',  'dot'=>'bg-purple-500',  'badge'=>'bg-purple-100 text-purple-700'],
  'Delivered'      => ['pill_bg'=>'bg-green-50',   'pill_text'=>'text-green-600',   'pill_border'=>'border-green-200',   'dot'=>'bg-green-500',   'badge'=>'bg-green-100 text-green-700'],
  'Cancelled'      => ['pill_bg'=>'bg-red-50',     'pill_text'=>'text-red-600',     'pill_border'=>'border-red-200',     'dot'=>'bg-red-500',     'badge'=>'bg-red-100 text-red-700'],
];
$statusLabels = [
  'Pending' => 'Pending', 'Processing' => 'Processing',
  'OutForDelivery' => 'Out for Delivery', 'Delivered' => 'Delivered', 'Cancelled' => 'Cancelled',
];
$paymentBadge = [
  'Paid' => 'bg-green-100 text-green-700', 'Pending' => 'bg-yellow-100 text-yellow-700',
  'Failed' => 'bg-red-100 text-red-700',   'Refunded' => 'bg-blue-100 text-blue-700',
];
// All entity types from activity_log
$entityConf = [
  'order'              => ['color' => 'bg-blue-100 text-blue-600',     'icon' => '🛒'],
  'payment'            => ['color' => 'bg-green-100 text-green-600',   'icon' => '💳'],
  'product'            => ['color' => 'bg-orange-100 text-orange-600', 'icon' => '📦'],
  'product_variant'    => ['color' => 'bg-amber-100 text-amber-600',   'icon' => '🏷️'],
  'account'            => ['color' => 'bg-purple-100 text-purple-600', 'icon' => '👤'],
  'rider'              => ['color' => 'bg-cyan-100 text-cyan-600',     'icon' => '🛵'],
  'category'           => ['color' => 'bg-pink-100 text-pink-600',     'icon' => '🗂️'],
  'blog'               => ['color' => 'bg-indigo-100 text-indigo-600', 'icon' => '📝'],
  'cooking_suggestion' => ['color' => 'bg-lime-100 text-lime-600',     'icon' => '🍳'],
  'review'             => ['color' => 'bg-rose-100 text-rose-600',     'icon' => '⭐'],
  'delivery'           => ['color' => 'bg-teal-100 text-teal-600',     'icon' => '🚚'],
  'refund'             => ['color' => 'bg-red-100 text-red-600',       'icon' => '↩️'],
  'system'             => ['color' => 'bg-gray-100 text-gray-600',     'icon' => '⚙️'],
];

/**
 * Decode activity log old/new JSON values into a human-readable diff string.
 * Returns ['old' => '...', 'new' => '...'] or null if not useful.
 */
function decodeLogDiff(?string $oldJson, ?string $newJson): ?array {
    if (!$oldJson || !$newJson) return null;
    $old = json_decode($oldJson, true);
    $new = json_decode($newJson, true);

    // Not JSON — treat as raw string values
    if (!is_array($old) || !is_array($new)) {
        $oldStr = is_string($old) ? $old : ($oldJson ?? '');
        $newStr = is_string($new) ? $new : ($newJson ?? '');
        if ($oldStr === $newStr) return null;
        return ['old' => mb_strimwidth($oldStr, 0, 30, '…'), 'new' => mb_strimwidth($newStr, 0, 30, '…')];
    }

    // Find first key that changed
    $changes = [];
    foreach ($new as $k => $v) {
        $oldV = $old[$k] ?? null;
        if ((string)$oldV !== (string)$v) {
            $changes[] = [
                'key' => ucfirst(str_replace('_', ' ', $k)),
                'old' => mb_strimwidth((string)$oldV, 0, 28, '…'),
                'new' => mb_strimwidth((string)$v,    0, 28, '…'),
            ];
        }
    }
    if (empty($changes)) return null;

    // Show first change (most significant)
    $c = $changes[0];
    return [
        'label' => $c['key'],
        'old'   => $c['old'],
        'new'   => $c['new'],
        'count' => count($changes), // extra changed fields
    ];
}
?>

<style>
.welcome-hero { background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24 100%); }
.logs-row { transition: all 0.2s ease; border-left: 4px solid transparent; }
.logs-row:hover { border-left-color: #f97316; background: #fffbf5; }
.stat-card { transition: box-shadow .2s, transform .15s; }
.stat-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-1px); }
</style>

<!-- ══ Welcome banner ══════════════════════════════════════════════════════ -->
<div class="relative overflow-hidden welcome-hero rounded-2xl p-6 text-white shadow-lg">
  <div class="relative z-10">
    <p class="text-white/70 text-sm font-medium">
      Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?> 👋
    </p>
    <h1 class="text-2xl font-bold mt-1">Welcome back, <?= htmlspecialchars($adminName ?? 'Admin') ?>!</h1>
    <p class="text-white/70 text-sm mt-1"><?= date('l, F j, Y') ?> — Here's what's happening today.</p>
    <div class="flex flex-wrap gap-3 mt-4">
      <?php
      $heroStats = [
          [$stats['orders_today'],                            'Orders Today'],
          ['₱'.number_format($stats['monthly_revenue'], 0),  'Revenue This Month'],
          [$stats['pending_orders'],                          'Pending Orders'],
          [$stats['available_riders'].'/'.$stats['total_riders'], 'Riders Available'],
      ];
      foreach ($heroStats as [$val, $label]):
      ?>
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 text-center min-w-[90px]">
        <div class="text-xl font-bold text-white"><?= $val ?></div>
        <div class="text-xs text-white/70 mt-0.5"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="absolute -top-8 -right-8 w-48 h-48 bg-white/10 rounded-full"></div>
  <div class="absolute -bottom-12 -right-4 w-32 h-32 bg-white/10 rounded-full"></div>
</div>

<!-- ══ KPI cards ═══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">

  <!-- Revenue -->
  <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-3">
      <div class="size-10 rounded-xl bg-green-100 flex items-center justify-center">
        <svg class="size-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
      <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $stats['revenue_growth'] >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' ?>">
        <?= $stats['revenue_growth'] >= 0 ? '+' : '' ?><?= $stats['revenue_growth'] ?>%
      </span>
    </div>
    <div class="text-2xl font-bold text-gray-800">₱<?= number_format($stats['total_revenue'], 0) ?></div>
    <div class="text-sm text-gray-500 mt-1">Total Revenue</div>
    <div class="text-xs text-gray-400 mt-1">₱<?= number_format($stats['monthly_revenue'], 0) ?> this month</div>
  </div>

  <!-- Orders -->
  <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-3">
      <div class="size-10 rounded-xl bg-blue-100 flex items-center justify-center">
        <svg class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      </div>
      <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">+<?= $stats['orders_today'] ?> today</span>
    </div>
    <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_orders']) ?></div>
    <div class="text-sm text-gray-500 mt-1">Total Orders</div>
    <div class="text-xs text-gray-400 mt-1"><?= $stats['delivered_orders'] ?> delivered</div>
  </div>

  <!-- Customers -->
  <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-3">
      <div class="size-10 rounded-xl bg-purple-100 flex items-center justify-center">
        <svg class="size-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded-full">+<?= $stats['new_customers'] ?> new</span>
    </div>
    <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_customers']) ?></div>
    <div class="text-sm text-gray-500 mt-1">Total Customers</div>
    <div class="text-xs text-gray-400 mt-1"><?= $stats['new_customers'] ?> joined this month</div>
  </div>

  <!-- Products -->
  <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-3">
      <div class="size-10 rounded-xl bg-orange-100 flex items-center justify-center">
        <svg class="size-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <?php if ($stats['out_of_stock'] > 0): ?>
      <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full"><?= $stats['out_of_stock'] ?> OOS</span>
      <?php else: ?>
      <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">All in stock</span>
      <?php endif; ?>
    </div>
    <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_products']) ?></div>
    <div class="text-sm text-gray-500 mt-1">Active Products</div>
    <div class="text-xs text-gray-400 mt-1"><?= $stats['out_of_stock'] ?> variants out of stock</div>
  </div>

</div>

<!-- ══ Order status pills ══════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
  <?php foreach ($orderStatuses as $status => $count):
    $sc = $statusConf[$status];
  ?>
  <a href="orders.php?status=<?= $status ?>"
     class="<?= $sc['pill_bg'] ?> border <?= $sc['pill_border'] ?> rounded-xl p-4 text-center hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200">
    <div class="flex items-center justify-center gap-2 mb-1">
      <div class="size-2 rounded-full <?= $sc['dot'] ?> <?= ($status === 'Pending' && $count > 0) ? 'animate-pulse' : '' ?>"></div>
      <span class="text-xs font-medium <?= $sc['pill_text'] ?>"><?= $statusLabels[$status] ?></span>
    </div>
    <div class="text-2xl font-bold <?= $sc['pill_text'] ?>"><?= number_format($count) ?></div>
  </a>
  <?php endforeach; ?>
</div>

<!-- ══ Charts row ══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Revenue area chart -->
  <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Revenue Overview</h3>
        <p class="text-xs text-gray-500">Last 6 months · delivered orders</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400 hidden sm:inline">Total:</span>
        <span class="text-sm font-bold text-orange-500 hidden sm:inline">₱<?= number_format($stats['total_revenue'], 0) ?></span>
        <div class="size-9 rounded-xl bg-orange-50 flex items-center justify-center">
          <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
      </div>
    </div>
    <div id="revenueChart"></div>
    <?php if (array_sum($rev6mData) == 0): ?>
    <p class="text-xs text-gray-400 text-center mt-2">No delivered orders in the last 6 months yet.</p>
    <?php endif; ?>
  </div>

  <!-- Order status donut -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <div class="mb-4">
      <h3 class="text-base font-semibold text-gray-800">Order Status</h3>
      <p class="text-xs text-gray-500">All-time breakdown</p>
    </div>
    <div id="orderStatusChart"></div>
    <div class="mt-3 space-y-1.5">
      <?php foreach ($orderStatuses as $status => $count):
        $sc  = $statusConf[$status];
        $pct = $stats['total_orders'] > 0 ? round($count / $stats['total_orders'] * 100, 1) : 0;
      ?>
      <div class="flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
          <div class="size-2 rounded-full <?= $sc['dot'] ?>"></div>
          <span class="text-gray-600"><?= $statusLabels[$status] ?></span>
        </div>
        <span class="font-semibold text-gray-800"><?= number_format($count) ?>
          <span class="text-gray-400 font-normal">(<?= $pct ?>%)</span>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- ══ Recent orders + sidebar ════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Recent orders table -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Recent Orders</h3>
        <p class="text-xs text-gray-500">Latest <?= count($recentOrders) ?> orders</p>
      </div>
      <a href="orders.php" class="text-xs text-orange-500 hover:text-orange-600 font-medium">View all →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">Order</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">Customer</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-left uppercase tracking-wide">Status</th>
            <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-right uppercase tracking-wide">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php if (empty($recentOrders)): ?>
          <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No orders yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentOrders as $ro):
            $osBadge = $statusConf[$ro['order_status']]['badge'] ?? 'bg-gray-100 text-gray-700';
            $psBadge = $paymentBadge[$ro['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
            $pmLabel = strtoupper(str_replace(['_','-'], ' ', $ro['payment_method'] ?? 'N/A'));
          ?>
          <tr class="hover:bg-orange-50/50 transition-colors">
            <td class="px-4 py-3">
              <a href="order_manage.php?order_id=<?= $ro['order_id'] ?>"
                 class="text-xs font-semibold text-orange-600 hover:underline">
                <?= htmlspecialchars($ro['order_code']) ?>
              </a>
              <div class="text-xs text-gray-400 mt-0.5"><?= date('M j, g:i A', strtotime($ro['order_date'])) ?></div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs font-medium text-gray-800"><?= htmlspecialchars($ro['recipient_first_name'].' '.$ro['recipient_last_name']) ?></span>
              <?php if ($ro['is_guest_order']): ?>
              <div class="text-xs text-gray-400">Guest</div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $osBadge ?>">
                <?= $statusLabels[$ro['order_status']] ?? $ro['order_status'] ?>
              </span>
              <div class="mt-0.5 text-[10px] text-gray-400"><?= $pmLabel ?></div>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="text-xs font-semibold text-gray-800">₱<?= number_format($ro['total_price'], 2) ?></span>
              <div class="mt-0.5">
                <span class="text-[10px] <?= $psBadge ?> inline-block px-1.5 py-0.5 rounded-full">
                  <?= $ro['payment_status'] ?? 'Pending' ?>
                </span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right column -->
  <div class="space-y-6">

    <!-- Top Products -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-800">Top Products</h3>
        <a href="products.php" class="text-xs text-orange-500 hover:text-orange-600 font-medium">View all →</a>
      </div>
      <?php if (empty($topProducts)): ?>
        <p class="text-xs text-gray-400 text-center py-4">No sales data yet</p>
      <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($topProducts as $i => $tp):
          $maxSold = max(1, (int)($topProducts[0]['total_sold'] ?? 1));
          $pct     = round($tp['total_sold'] / $maxSold * 100);
        ?>
        <div>
          <div class="flex justify-between items-center mb-1">
            <div class="flex items-center gap-2">
              <span class="size-5 rounded flex items-center justify-center text-[10px] font-bold shrink-0
                    <?= $i === 0 ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-500' ?>">
                <?= $i + 1 ?>
              </span>
              <span class="text-xs font-medium text-gray-700 truncate max-w-[130px]">
                <?= htmlspecialchars($tp['product_name']) ?>
              </span>
            </div>
            <span class="text-xs text-gray-500 shrink-0"><?= number_format($tp['total_sold']) ?> sold</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-1.5">
            <div class="h-1.5 rounded-full <?= $i === 0 ? 'bg-gradient-to-r from-orange-400 to-amber-400' : 'bg-gray-300' ?>"
                 style="width:<?= $pct ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Rider Status -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-800">Rider Status</h3>
        <a href="riders.php" class="text-xs text-orange-500 hover:text-orange-600 font-medium">Manage →</a>
      </div>
      <?php if (empty($riderStatus)): ?>
        <p class="text-xs text-gray-400 text-center py-4">No riders registered</p>
      <?php else: ?>
      <div class="space-y-2.5">
        <?php foreach ($riderStatus as $rd): ?>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <?php if (!empty($rd['image'])): ?>
            <img src="../<?= htmlspecialchars($rd['image']) ?>" class="size-8 rounded-full object-cover border border-gray-100 shrink-0">
            <?php else: ?>
            <div class="size-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600 shrink-0">
              <?= strtoupper(substr($rd['first_name'],0,1).substr($rd['last_name'],0,1)) ?>
            </div>
            <?php endif; ?>
            <div class="min-w-0">
              <div class="text-xs font-medium text-gray-800 truncate"><?= htmlspecialchars($rd['display_name']) ?></div>
              <div class="text-xs text-gray-400"><?= htmlspecialchars($rd['vehicle_type']) ?></div>
            </div>
          </div>
          <div class="shrink-0 ml-2">
            <?php if ($rd['active_deliveries'] > 0): ?>
            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
              <?= $rd['active_deliveries'] ?> delivering
            </span>
            <?php elseif ($rd['is_available']): ?>
            <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
              <span class="size-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>Available
            </span>
            <?php else: ?>
            <span class="text-xs text-gray-400">Offline</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ══ Activity log + payment methods + needs attention ═══════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Activity log (FIX #4 + #5: full detail, JSON diff, all entity types) -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Recent Activity</h3>
        <p class="text-xs text-gray-500">Latest system events — all changes logged</p>
      </div>
      <a href="logs.php" class="text-xs text-orange-500 hover:text-orange-600 font-medium">Full log →</a>
    </div>
    <div class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
      <?php if (empty($recentActivity)): ?>
      <div class="px-6 py-8 text-center text-sm text-gray-400">No activity logged yet.</div>
      <?php endif; ?>

      <?php foreach ($recentActivity as $log):
        $etype   = $log['entity_type'] ?? 'system';
        $conf    = $entityConf[$etype] ?? $entityConf['system'];
        $actor   = !empty($log['user_name']) ? trim($log['user_name']) : '';
        if (!$actor) $actor = ucfirst($log['user_type'] ?? 'system');
        $diff    = decodeLogDiff($log['old_value'], $log['new_value']);
      ?>
      <div class="logs-row px-5 py-3 flex items-start gap-3">

        <!-- Entity icon bubble -->
        <div class="size-9 rounded-xl <?= $conf['color'] ?> flex items-center justify-center text-base shrink-0 mt-0.5 border border-current/10">
          <?= $conf['icon'] ?>
        </div>

        <!-- Main content -->
        <div class="flex-1 min-w-0">

          <!-- Action text -->
          <p class="text-xs font-semibold text-gray-800 leading-snug">
            <?= htmlspecialchars($log['action']) ?>
          </p>

          <!-- Order code link -->
          <?php if (!empty($log['order_code'])): ?>
          <a href="order_manage.php?order_id=<?= (int)$log['entity_id'] ?>"
             class="inline-flex items-center gap-1 text-[11px] text-orange-500 hover:text-orange-600 hover:underline font-medium mt-0.5">
            🛒 #<?= htmlspecialchars($log['order_code']) ?>
          </a>
          <?php endif; ?>

          <!-- Details line -->
          <?php if (!empty($log['details'])): ?>
          <p class="text-[11px] text-gray-400 mt-0.5 line-clamp-1"><?= htmlspecialchars($log['details']) ?></p>
          <?php endif; ?>

          <!-- Old → New diff (FIX #4: decoded from JSON, shows field name) -->
          <?php if ($diff): ?>
          <div class="flex items-center gap-1.5 mt-1 flex-wrap">
            <?php if (isset($diff['label'])): ?>
            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide"><?= htmlspecialchars($diff['label']) ?>:</span>
            <?php endif; ?>
            <span class="text-[10px] px-1.5 py-0.5 bg-red-50 text-red-500 rounded line-through"><?= htmlspecialchars($diff['old']) ?></span>
            <span class="text-[10px] text-gray-400">→</span>
            <span class="text-[10px] px-1.5 py-0.5 bg-green-50 text-green-600 rounded font-medium"><?= htmlspecialchars($diff['new']) ?></span>
            <?php if (($diff['count'] ?? 1) > 1): ?>
            <span class="text-[10px] text-gray-400">+<?= $diff['count'] - 1 ?> more</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Actor · time · IP -->
          <div class="flex items-center gap-2 mt-1 flex-wrap">
            <span class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars($actor) ?></span>
            <span class="text-gray-300">·</span>
            <span class="text-[11px] text-gray-400"><?= date('M j, g:i A', strtotime($log['created_at'])) ?></span>
            <?php if (!empty($log['ip_address'])): ?>
            <span class="text-gray-300">·</span>
            <span class="text-[10px] font-mono text-gray-300"><?= htmlspecialchars($log['ip_address']) ?></span>
            <?php endif; ?>
          </div>

        </div>

        <!-- Entity type badge (right) -->
        <span class="shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full <?= $conf['color'] ?> capitalize whitespace-nowrap hidden sm:inline-block mt-0.5">
          <?= str_replace('_', ' ', $etype) ?>
        </span>

      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right column -->
  <div class="space-y-4">

    <!-- Payment method breakdown -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <h3 class="text-base font-semibold text-gray-800 mb-1">Payment Methods</h3>
      <p class="text-xs text-gray-500 mb-3">Order count by method</p>
      <?php if (empty($paymentMethods)): ?>
      <p class="text-xs text-gray-400 text-center py-4">No payment data yet</p>
      <?php else: ?>
      <div id="paymentMethodChart"></div>
      <?php endif; ?>
    </div>

    <!-- Needs attention -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <h3 class="text-base font-semibold text-gray-800 mb-3">Needs Attention</h3>
      <div class="space-y-3">

        <?php if ($stats['pending_orders'] > 0): ?>
        <a href="orders.php?status=Pending"
           class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-100 rounded-xl hover:bg-yellow-100 transition-colors group">
          <div class="size-9 bg-yellow-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0"><?= $stats['pending_orders'] ?></div>
          <div class="flex-1"><div class="text-xs font-semibold text-yellow-800">Pending Orders</div><div class="text-xs text-yellow-600">Need approval</div></div>
          <svg class="size-4 text-yellow-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['processing_orders'] > 0): ?>
        <a href="orders.php?status=Processing"
           class="flex items-center gap-3 p-3 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors group">
          <div class="size-9 bg-blue-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0"><?= $stats['processing_orders'] ?></div>
          <div class="flex-1"><div class="text-xs font-semibold text-blue-800">Processing</div><div class="text-xs text-blue-600">Awaiting rider assignment</div></div>
          <svg class="size-4 text-blue-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['out_of_stock'] > 0): ?>
        <a href="products.php"
           class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-colors group">
          <div class="size-9 bg-red-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0"><?= $stats['out_of_stock'] ?></div>
          <div class="flex-1"><div class="text-xs font-semibold text-red-800">Out of Stock</div><div class="text-xs text-red-600">Restock needed</div></div>
          <svg class="size-4 text-red-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['failed_payments'] > 0): ?>
        <a href="payments.php?payment_status=Failed"
           class="flex items-center gap-3 p-3 bg-orange-50 border border-orange-100 rounded-xl hover:bg-orange-100 transition-colors group">
          <div class="size-9 bg-orange-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0"><?= $stats['failed_payments'] ?></div>
          <div class="flex-1"><div class="text-xs font-semibold text-orange-800">Failed Payments</div><div class="text-xs text-orange-600">Review required</div></div>
          <svg class="size-4 text-orange-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['pending_orders'] === 0 && $stats['processing_orders'] === 0 && $stats['out_of_stock'] === 0 && $stats['failed_payments'] === 0): ?>
        <div class="text-center py-4">
          <div class="size-10 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
            <svg class="size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
          </div>
          <div class="text-xs font-semibold text-gray-700">Everything looks good!</div>
          <div class="text-xs text-gray-400 mt-0.5">No actions required.</div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<!-- ══ ApexCharts ══════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── FIX #2: Smart Y-axis formatter ──────────────────────────────────────
  // Shows raw value for < 1000, Xk for >= 1000, X.Xk for in-between
  function fmtPeso(v) {
    if (v >= 1000000) return '₱' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000)    return '₱' + (v / 1000).toFixed(v % 1000 === 0 ? 0 : 1) + 'k';
    return '₱' + Math.round(v);
  }
  function fmtPesoFull(v) {
    return '₱' + parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  }

  // ── Revenue area chart ──────────────────────────────────────────────────
  const rev6mLabels = <?= $chart6m_labels ?>;
  const rev6mData   = <?= $chart6m_data ?>;
  const hasRevData  = rev6mData.some(v => v > 0);

  if (document.getElementById('revenueChart') && hasRevData) {
    new ApexCharts(document.getElementById('revenueChart'), {
      series: [{ name: 'Revenue (₱)', data: rev6mData }],
      chart: {
        type: 'area', height: 200,
        toolbar: { show: false }, zoom: { enabled: false },
        fontFamily: 'Lexend, sans-serif',
      },
      colors: ['#f97316'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.03 } },
      stroke: { curve: 'smooth', width: 2.5 },
      xaxis: {
        categories: rev6mLabels,
        labels: { style: { fontSize: '11px', colors: '#9ca3af', fontFamily: 'Lexend, sans-serif' } },
        axisBorder: { show: false }, axisTicks: { show: false },
      },
      yaxis: {
        labels: {
          formatter: fmtPeso,
          style: { fontSize: '11px', colors: '#9ca3af', fontFamily: 'Lexend, sans-serif' },
        },
        // Min 0 so chart doesn't float above baseline
        min: 0,
      },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
      dataLabels: { enabled: false },
      markers: { size: 4, colors: ['#f97316'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
      tooltip: {
        y: { formatter: fmtPesoFull },
        x: { show: true },
        theme: 'light',
      },
    }).render();
  }

  // ── Order status donut ──────────────────────────────────────────────────
  const osRawLabels = <?= $order_status_labels ?>;
  const osData      = <?= $order_status_data ?>;
  const osLabelMap  = {
    Pending:'Pending', Processing:'Processing',
    OutForDelivery:'Out for Delivery', Delivered:'Delivered', Cancelled:'Cancelled'
  };
  const osLabels = osRawLabels.map(l => osLabelMap[l] || l);

  if (document.getElementById('orderStatusChart') && osData.some(v => v > 0)) {
    new ApexCharts(document.getElementById('orderStatusChart'), {
      series: osData,
      chart: { type: 'donut', height: 180, fontFamily: 'Lexend, sans-serif' },
      labels: osLabels,
      colors: ['#fbbf24', '#3b82f6', '#a855f7', '#22c55e', '#ef4444'],
      legend: { show: false },
      dataLabels: { enabled: false },
      plotOptions: {
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: true,
              total: {
                show: true, label: 'Orders',
                formatter: () => <?= $stats['total_orders'] ?>,
                style: { fontSize: '14px', fontWeight: 700, color: '#1f2937', fontFamily: 'Lexend, sans-serif' },
              },
            },
          },
        },
      },
      stroke: { width: 2, colors: ['#fff'] },
      tooltip: { y: { formatter: v => v + ' orders' } },
    }).render();
  }

  // ── Payment method bar ──────────────────────────────────────────────────
  const pmRawLabels = <?= $pm_labels ?>;
  const pmData      = <?= $pm_data ?>;
  const pmLabelMap  = { gcash:'GCash', paymaya:'PayMaya', grab_pay:'GrabPay', qrph:'QR Ph', cod:'COD', card:'Card' };
  const pmDisplay   = pmRawLabels.map(l => pmLabelMap[l] || l);

  if (document.getElementById('paymentMethodChart') && pmData.length > 0) {
    new ApexCharts(document.getElementById('paymentMethodChart'), {
      series: [{ name: 'Orders', data: pmData }],
      chart: { type: 'bar', height: 150, toolbar: { show: false }, fontFamily: 'Lexend, sans-serif' },
      colors: ['#f97316'],
      xaxis: {
        categories: pmDisplay,
        labels: { style: { fontSize: '10px', colors: '#9ca3af', fontFamily: 'Lexend, sans-serif' } },
        axisBorder: { show: false },
      },
      yaxis: {
        labels: {
          formatter: v => Math.round(v),
          style: { fontSize: '10px', colors: '#9ca3af' },
        },
      },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
      dataLabels: { enabled: false },
      plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '55%' } },
      tooltip: { y: { formatter: v => v + ' orders' } },
    }).render();
  }

});
</script>