<?php
// --- KPI Stats ---
$stats = [];

// Total Revenue (paid only)
$r = $conn->query("SELECT COALESCE(SUM(gross_amount),0) as v FROM payments WHERE payment_status='Paid'");
$stats['total_revenue'] = (float)$r->fetch_assoc()['v'];

// Revenue this month
$r = $conn->query("SELECT COALESCE(SUM(gross_amount),0) as v FROM payments WHERE payment_status='Paid' AND MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW())");
$stats['monthly_revenue'] = (float)$r->fetch_assoc()['v'];

// Revenue last month for growth calc
$r = $conn->query("SELECT COALESCE(SUM(gross_amount),0) as v FROM payments WHERE payment_status='Paid' AND MONTH(paid_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(paid_at)=YEAR(NOW()-INTERVAL 1 MONTH)");
$stats['last_month_revenue'] = (float)$r->fetch_assoc()['v'];
$stats['revenue_growth'] = $stats['last_month_revenue'] > 0 ? round((($stats['monthly_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100, 1) : 0;

// Total orders
$r = $conn->query("SELECT COUNT(*) as v FROM orders");
$stats['total_orders'] = (int)$r->fetch_assoc()['v'];

// Orders today
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE DATE(order_date)=CURDATE()");
$stats['orders_today'] = (int)$r->fetch_assoc()['v'];

// Pending orders
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='Pending'");
$stats['pending_orders'] = (int)$r->fetch_assoc()['v'];

// Processing orders
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='Processing'");
$stats['processing_orders'] = (int)$r->fetch_assoc()['v'];

// Out for delivery
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='OutForDelivery'");
$stats['out_for_delivery'] = (int)$r->fetch_assoc()['v'];

// Delivered orders
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='Delivered'");
$stats['delivered_orders'] = (int)$r->fetch_assoc()['v'];

// Cancelled orders
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='Cancelled'");
$stats['cancelled_orders'] = (int)$r->fetch_assoc()['v'];

// Total customers
$r = $conn->query("SELECT COUNT(*) as v FROM accounts WHERE role='customer'");
$stats['total_customers'] = (int)$r->fetch_assoc()['v'];

// New customers this month
$r = $conn->query("SELECT COUNT(*) as v FROM accounts WHERE role='customer' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
$stats['new_customers'] = (int)$r->fetch_assoc()['v'];

// Total products (active)
$r = $conn->query("SELECT COUNT(*) as v FROM products WHERE is_deleted=0");
$stats['total_products'] = (int)$r->fetch_assoc()['v'];

// Out of stock variants
$r = $conn->query("SELECT COUNT(*) as v FROM product_variants WHERE stock_status='Out of Stock'");
$stats['out_of_stock'] = (int)$r->fetch_assoc()['v'];

// Available riders
$r = $conn->query("SELECT COUNT(*) as v FROM riders WHERE is_available=1");
$stats['available_riders'] = (int)$r->fetch_assoc()['v'];

// Total riders
$r = $conn->query("SELECT COUNT(*) as v FROM riders");
$stats['total_riders'] = (int)$r->fetch_assoc()['v'];

// Pending payments
$r = $conn->query("SELECT COUNT(*) as v, COALESCE(SUM(gross_amount),0) as amt FROM payments WHERE payment_status='Pending'");
$row_tmp = $r->fetch_assoc();
$stats['pending_payments'] = (int)$row_tmp['v'];
$stats['pending_payment_amount'] = (float)$row_tmp['amt'];

// Failed payments
$r = $conn->query("SELECT COUNT(*) as v FROM payments WHERE payment_status='Failed'");
$stats['failed_payments'] = (int)$r->fetch_assoc()['v'];

// --- Revenue for last 7 days (for sparkline) ---
$revenue7 = $conn->query("
    SELECT DATE(paid_at) as d, COALESCE(SUM(gross_amount),0) as rev
    FROM payments WHERE payment_status='Paid' AND paid_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(paid_at) ORDER BY d ASC
")->fetch_all(MYSQLI_ASSOC);

// --- Revenue last 6 months (for area chart) ---
$revenue6m = $conn->query("
    SELECT DATE_FORMAT(paid_at,'%b %Y') as mo, DATE_FORMAT(paid_at,'%Y-%m') as mo_key,
           COALESCE(SUM(gross_amount),0) as rev
    FROM payments WHERE payment_status='Paid' AND paid_at >= CURDATE() - INTERVAL 5 MONTH
    GROUP BY mo_key ORDER BY mo_key ASC
")->fetch_all(MYSQLI_ASSOC);

// --- Order status breakdown ---
$orderStatuses = ['Pending'=>$stats['pending_orders'],'Processing'=>$stats['processing_orders'],
                  'OutForDelivery'=>$stats['out_for_delivery'],'Delivered'=>$stats['delivered_orders'],'Cancelled'=>$stats['cancelled_orders']];

// --- Payment method breakdown ---
$pmResult = $conn->query("SELECT payment_method, COUNT(*) as cnt FROM orders WHERE payment_method IS NOT NULL GROUP BY payment_method");
$paymentMethods = [];
while($pmRow = $pmResult->fetch_assoc()) $paymentMethods[] = $pmRow;

// --- Recent orders (latest 8) ---
$recentOrders = $conn->query("
    SELECT o.order_id, o.order_code, o.first_name, o.last_name, o.order_status,
           o.total_price, o.order_date, o.payment_method, o.is_guest_order,
           p.payment_status
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.order_id AND p.payment_id = (SELECT MAX(p2.payment_id) FROM payments p2 WHERE p2.order_id=o.order_id)
    ORDER BY o.order_date DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// --- Top products by sales ---
$topProducts = $conn->query("
    SELECT p.product_name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_status != 'Cancelled'
    GROUP BY oi.product_id ORDER BY total_sold DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// --- Recent activity log (latest 10) ---
$recentActivity = $conn->query("
    SELECT al.*, o.order_code,
           CONCAT(a.first_name,' ',a.last_name) as user_name
    FROM activity_log al
    LEFT JOIN orders o ON al.entity_type='order' AND al.entity_id=o.order_id
    LEFT JOIN accounts a ON al.user_id = a.account_id
    ORDER BY al.created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// --- Rider status ---
$riderStatus = $conn->query("
    SELECT r.rider_id, r.is_available, r.vehicle_type,
           a.first_name, a.last_name,
           (SELECT COUNT(*) FROM orders WHERE assigned_rider_id=r.rider_id AND order_status='OutForDelivery') as active_deliveries
    FROM riders r JOIN accounts a ON r.account_id=a.account_id
    ORDER BY active_deliveries DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Build chart JSON
$chart6m_labels = json_encode(array_column($revenue6m, 'mo'));
$chart6m_data   = json_encode(array_map(fn($x) => round($x['rev'], 2), $revenue6m));
$order_status_labels = json_encode(array_keys($orderStatuses));
$order_status_data   = json_encode(array_values($orderStatuses));
$pm_labels = json_encode(array_column($paymentMethods, 'payment_method'));
$pm_data   = json_encode(array_column($paymentMethods, 'cnt'));

// ── helpers ────────────────────────────────────────────────────────────────────
$timeOfDay = date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening');

// Single canonical status config used everywhere in this file
$statusConf = [
  'Pending'        => ['pill_bg'=>'bg-yellow-50',  'pill_text'=>'text-yellow-600',  'pill_border'=>'border-yellow-200',  'dot'=>'bg-yellow-500',  'badge'=>'bg-yellow-100 text-yellow-700'],
  'Processing'     => ['pill_bg'=>'bg-blue-50',    'pill_text'=>'text-blue-600',    'pill_border'=>'border-blue-200',    'dot'=>'bg-blue-500',    'badge'=>'bg-blue-100 text-blue-700'],
  'OutForDelivery' => ['pill_bg'=>'bg-purple-50',  'pill_text'=>'text-purple-600',  'pill_border'=>'border-purple-200',  'dot'=>'bg-purple-500',  'badge'=>'bg-purple-100 text-purple-700'],
  'Delivered'      => ['pill_bg'=>'bg-green-50',   'pill_text'=>'text-green-600',   'pill_border'=>'border-green-200',   'dot'=>'bg-green-500',   'badge'=>'bg-green-100 text-green-700'],
  'Cancelled'      => ['pill_bg'=>'bg-red-50',     'pill_text'=>'text-red-600',     'pill_border'=>'border-red-200',     'dot'=>'bg-red-500',     'badge'=>'bg-red-100 text-red-700'],
];
$statusLabels = [
  'Pending'        => 'Pending',
  'Processing'     => 'Processing',
  'OutForDelivery' => 'Out for Delivery',
  'Delivered'      => 'Delivered',
  'Cancelled'      => 'Cancelled',
];
$paymentBadge = [
  'Paid'     => 'bg-green-100 text-green-700',
  'Pending'  => 'bg-yellow-100 text-yellow-700',
  'Failed'   => 'bg-red-100 text-red-700',
  'Refunded' => 'bg-blue-100 text-blue-700',
];
// Full entity-type colour map matching all types written by activity_log_helper.php
$entityColors = [
  'order'              => 'bg-blue-100 text-blue-600',
  'payment'            => 'bg-green-100 text-green-600',
  'product'            => 'bg-orange-100 text-orange-600',
  'product_variant'    => 'bg-amber-100 text-amber-600',
  'account'            => 'bg-purple-100 text-purple-600',
  'rider'              => 'bg-cyan-100 text-cyan-600',
  'category'           => 'bg-pink-100 text-pink-600',
  'blog'               => 'bg-indigo-100 text-indigo-600',
  'cooking_suggestion' => 'bg-lime-100 text-lime-600',
  'review'             => 'bg-rose-100 text-rose-600',
  'delivery'           => 'bg-teal-100 text-teal-600',
  'refund'             => 'bg-red-100 text-red-600',
  'system'             => 'bg-gray-100 text-gray-600',
];
?>

<style>
/* ── Hero search card ── */
.welcome-hero {
  background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%);
}
</style>

<!-- Welcome banner -->
<div class="relative overflow-hidden welcome-hero rounded-2xl p-6 text-white shadow-lg"> 
  <div class="relative z-10">
    <p class="text-white/70 text-sm font-medium">Good <?= (date('H') < 12) ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?> 👋</p>
    <h1 class="text-2xl font-bold mt-1 text-white/70">Welcome back, <?= htmlspecialchars($adminName ?? 'Admin') ?>!</h1>
    <p class="text-white/70 text-sm mt-1"><?= date('l, F j, Y') ?> — Here's what's happening today.</p>
    <div class="flex flex-wrap gap-3 mt-4">
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 text-center min-w-[90px]">
        <div class="text-xl font-bold text-white"><?= $stats['orders_today'] ?></div>
        <div class="text-xs text-white/70 mt-0.5">Orders Today</div>
      </div>
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 text-center min-w-[90px]">
        <div class="text-xl font-bold text-white">₱<?= number_format($stats['monthly_revenue'], 0) ?></div>
        <div class="text-xs text-white/70 mt-0.5">Revenue This Month</div>
      </div>
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 text-center min-w-[90px]">
        <div class="text-xl font-bold text-white"><?= $stats['pending_orders'] ?></div>
        <div class="text-xs text-white/70 mt-0.5">Pending Orders</div>
      </div>
      <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 text-center min-w-[90px]">
        <div class="text-xl font-bold text-white"><?= $stats['available_riders'] ?>/<?= $stats['total_riders'] ?></div>
        <div class="text-xs text-white/70 mt-0.5">Riders Available</div>
      </div>
    </div>
  </div>
  <!-- Decorative circles -->
  <div class="absolute -top-8 -right-8 w-48 h-48 bg-white/10 rounded-full"></div>
  <div class="absolute -bottom-12 -right-4 w-32 h-32 bg-white/10 rounded-full"></div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">

  <!-- Total Revenue -->
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
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

  <!-- Total Orders -->
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
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
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
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

  <!-- Products — FIX #2b: replaced broken icon with clean box/package SVG -->
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between mb-3">
      <div class="size-10 rounded-xl bg-orange-100 flex items-center justify-center">
        <svg class="size-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <?php if ($stats['out_of_stock'] > 0): ?>
        <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full"><?= $stats['out_of_stock'] ?> out of stock</span>
      <?php else: ?>
        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">All in stock</span>
      <?php endif; ?>
    </div>
    <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_products']) ?></div>
    <div class="text-sm text-gray-500 mt-1">Active Products</div>
    <div class="text-xs text-gray-400 mt-1"><?= $stats['out_of_stock'] ?> variants out of stock</div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════
     ORDER STATUS PILLS
     FIX #3a: Pending dot now animate-pulse when count > 0.
     FIX #3b: Added hover:-translate-y-0.5 lift on hover.
════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
  <?php foreach ($orderStatuses as $status => $count):
    $sc = $statusConf[$status];
  ?>
  <a href="orders.php?status=<?= $status ?>"
     class="<?= $sc['pill_bg'] ?> border <?= $sc['pill_border'] ?> rounded-xl p-4 text-center hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200 group">
    <div class="flex items-center justify-center gap-2 mb-1">
      <div class="size-2 rounded-full <?= $sc['dot'] ?> <?= ($status === 'Pending' && $count > 0) ? 'animate-pulse' : '' ?>"></div>
      <span class="text-xs font-medium <?= $sc['pill_text'] ?>"><?= $statusLabels[$status] ?></span>
    </div>
    <div class="text-2xl font-bold <?= $sc['pill_text'] ?>"><?= number_format($count) ?></div>
  </a>
  <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════════════
     CHARTS ROW
     FIX #4a: Revenue chart header now shows total revenue pill.
     FIX #4b: Donut legend used undefined $labels — now uses
              $statusLabels defined above.
════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Revenue Area Chart -->
  <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Revenue Overview</h3>
        <p class="text-xs text-gray-500">Last 6 months</p>
      </div>
      <!-- FIX #4a: added total pill on right side of header -->
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400 hidden sm:inline">Total:</span>
        <span class="text-sm font-bold text-orange-500 hidden sm:inline">₱<?= number_format($stats['total_revenue'], 0) ?></span>
        <div class="size-9 rounded-xl bg-orange-50 flex items-center justify-center">
          <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
      </div>
    </div>
    <div id="revenueChart"></div>
  </div>

  <!-- Order Status Donut — FIX #4b: $labels → $statusLabels -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Order Status</h3>
        <p class="text-xs text-gray-500">All time breakdown</p>
      </div>
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

<!-- ═══════════════════════════════════════════════════════
     RECENT ORDERS + SIDEBAR
     FIX #5: Payment badges now use canonical $paymentBadge
              array instead of a re-declared inline array
              that had different class names.
     FIX #5b: Empty state row added.
════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Recent Orders Table -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-gray-800">Recent Orders</h3>
        <p class="text-xs text-gray-500">Latest <?= count($recentOrders) ?> orders</p>
      </div>
      <a href="orders.php" class="text-xs text-orange-500 hover:text-orange-600 font-medium transition-colors">View all →</a>
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
          <?php foreach ($recentOrders as $ro):
            $osBadge = $statusConf[$ro['order_status']]['badge']           ?? 'bg-gray-100 text-gray-700';
            $psBadge = $paymentBadge[$ro['payment_status'] ?? 'Pending']   ?? 'bg-gray-100 text-gray-700';
          ?>
          <tr class="hover:bg-orange-50/50 transition-colors">
            <td class="px-4 py-3">
              <a href="order_manage.php?order_id=<?= $ro['order_id'] ?>"
                 class="text-xs font-semibold text-orange-600 hover:text-orange-700 hover:underline">
                <?= htmlspecialchars($ro['order_code']) ?>
              </a>
              <div class="text-xs text-gray-400 mt-0.5"><?= date('M j, g:i A', strtotime($ro['order_date'])) ?></div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs font-medium text-gray-800">
                <?= htmlspecialchars($ro['first_name'] . ' ' . $ro['last_name']) ?>
              </span>
              <?php if ($ro['is_guest_order']): ?>
                <div class="text-xs text-gray-400">Guest</div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $osBadge ?>">
                <?= $statusLabels[$ro['order_status']] ?? $ro['order_status'] ?>
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="text-xs font-semibold text-gray-800">₱<?= number_format($ro['total_price'], 2) ?></span>
              <div class="mt-0.5">
                <span class="text-xs <?= $psBadge ?> inline-block px-1.5 py-0.5 rounded-full">
                  <?= $ro['payment_status'] ?? 'Pending' ?>
                </span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <!-- FIX #5b: empty state -->
          <?php if (empty($recentOrders)): ?>
          <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No orders yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right column -->
  <div class="space-y-6">

    <!-- Top Products
         FIX #6a: added rank number badge (#1 gets orange, rest gray).
         FIX #6b: only #1 bar gets the orange gradient; rest use gray-300. -->
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
          $maxSold = $topProducts[0]['total_sold'] ?? 1;
          $pct     = $maxSold > 0 ? round($tp['total_sold'] / $maxSold * 100) : 0;
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
        <?php foreach ($riderStatus as $rider): ?>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="size-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-semibold text-orange-600 shrink-0">
              <?= strtoupper(substr($rider['first_name'],0,1) . substr($rider['last_name'],0,1)) ?>
            </div>
            <div class="min-w-0">
              <div class="text-xs font-medium text-gray-800 truncate">
                <?= htmlspecialchars($rider['first_name'] . ' ' . $rider['last_name']) ?>
              </div>
              <div class="text-xs text-gray-400"><?= ucfirst($rider['vehicle_type']) ?></div>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0 ml-2">
            <?php if ($rider['active_deliveries'] > 0): ?>
              <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                <?= $rider['active_deliveries'] ?> delivering
              </span>
            <?php elseif ($rider['is_available']): ?>
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

<!-- ═══════════════════════════════════════════════════════
     ACTIVITY LOG + PAYMENT METHODS + NEEDS ATTENTION
════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Activity Log
       FIX #7a: entity colours now cover all 13 types from activity_log_helper.php
       FIX #7b: order_code is a clickable link not plain text
       FIX #7c: details line now shown
       FIX #7d: old/new value diff now shown
       FIX #7e: empty state added
       FIX #7f: hover selector aligned (class="logs-row" matches CSS below) -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
      <h3 class="text-base font-semibold text-gray-800">Recent Activity</h3>
      <p class="text-xs text-gray-500">Latest system events</p>
    </div>
    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
      <?php if (empty($recentActivity)): ?>
        <div class="px-6 py-8 text-center text-sm text-gray-400">No activity logged yet.</div>
      <?php endif; ?>
      <?php foreach ($recentActivity as $log):
        $etype  = $log['entity_type'] ?? 'system';
        $eClass = $entityColors[$etype] ?? 'bg-gray-100 text-gray-600';
        $actor  = !empty($log['user_name'])
                    ? $log['user_name']
                    : ucfirst($log['user_type'] ?? 'system');
        // Old/new value: if JSON object show a short label, otherwise show raw string
        $oldDisplay = '';
        $newDisplay = '';
        if (!empty($log['old_value']) && !empty($log['new_value'])) {
          $oldDisplay = json_decode($log['old_value'], true) !== null ? '(prev)' : $log['old_value'];
          $newDisplay = json_decode($log['new_value'], true) !== null ? '(new)'  : $log['new_value'];
        }
      ?>
      <div class="logs-row px-6 py-3 flex items-start gap-3 hover:bg-orange-50/40 transition-colors">
        <span class="mt-0.5 text-xs font-semibold px-2 py-0.5 rounded-full <?= $eClass ?> capitalize whitespace-nowrap shrink-0">
          <?= str_replace('_', ' ', $etype) ?>
        </span>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-700 truncate"><?= htmlspecialchars($log['action']) ?></p>
          <!-- FIX #7b: link not plain text -->
          <?php if (!empty($log['order_code'])): ?>
          <a href="order_manage.php?order_id=<?= $log['entity_id'] ?>"
             class="text-xs text-orange-500 hover:text-orange-600 hover:underline font-medium">
            #<?= htmlspecialchars($log['order_code']) ?>
          </a>
          <?php endif; ?>
          <!-- FIX #7c: details line -->
          <?php if (!empty($log['details'])): ?>
          <p class="text-xs text-gray-400 truncate mt-0.5"><?= htmlspecialchars($log['details']) ?></p>
          <?php endif; ?>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-xs text-gray-500"><?= htmlspecialchars($actor) ?></span>
            <span class="text-gray-300">·</span>
            <span class="text-xs text-gray-400"><?= date('M j, g:i A', strtotime($log['created_at'])) ?></span>
          </div>
        </div>
        <!-- FIX #7d: old → new diff -->
        <?php if ($oldDisplay && $newDisplay): ?>
        <div class="shrink-0 hidden sm:flex flex-col items-end gap-0.5 max-w-[90px]">
          <span class="text-[10px] line-through text-red-300 truncate w-full text-right"><?= htmlspecialchars($oldDisplay) ?></span>
          <span class="text-[10px] text-green-500 truncate w-full text-right"><?= htmlspecialchars($newDisplay) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right column -->
  <div class="space-y-4">

    <!-- Payment Method Breakdown — FIX #8: added subtitle -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <h3 class="text-base font-semibold text-gray-800 mb-1">Payment Methods</h3>
      <p class="text-xs text-gray-500 mb-3">Order count by method</p>
      <div id="paymentMethodChart"></div>
    </div>

    <!-- Needs Attention
         FIX #9a: badge boxes size-8 → size-9 (consistent with design system)
         FIX #9b: chevron arrow added to each alert card
         FIX #9c: all-clear uses proper SVG checkmark, not emoji -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
      <h3 class="text-base font-semibold text-gray-800 mb-3">Needs Attention</h3>
      <div class="space-y-3">

        <?php if ($stats['pending_orders'] > 0): ?>
        <a href="orders.php?status=Pending"
           class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-100 rounded-xl hover:bg-yellow-100 transition-colors group">
          <div class="size-9 bg-yellow-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0">
            <?= $stats['pending_orders'] ?>
          </div>
          <div class="flex-1">
            <div class="text-xs font-semibold text-yellow-800">Pending Orders</div>
            <div class="text-xs text-yellow-600">Need approval</div>
          </div>
          <svg class="size-4 text-yellow-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['out_of_stock'] > 0): ?>
        <a href="products.php"
           class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-colors group">
          <div class="size-9 bg-red-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0">
            <?= $stats['out_of_stock'] ?>
          </div>
          <div class="flex-1">
            <div class="text-xs font-semibold text-red-800">Out of Stock</div>
            <div class="text-xs text-red-600">Restock needed</div>
          </div>
          <svg class="size-4 text-red-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['failed_payments'] > 0): ?>
        <a href="payments.php?payment_status=Failed"
           class="flex items-center gap-3 p-3 bg-orange-50 border border-orange-100 rounded-xl hover:bg-orange-100 transition-colors group">
          <div class="size-9 bg-orange-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0">
            <?= $stats['failed_payments'] ?>
          </div>
          <div class="flex-1">
            <div class="text-xs font-semibold text-orange-800">Failed Payments</div>
            <div class="text-xs text-orange-600">Review required</div>
          </div>
          <svg class="size-4 text-orange-400 shrink-0 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <?php endif; ?>

        <?php if ($stats['pending_orders'] === 0 && $stats['out_of_stock'] === 0 && $stats['failed_payments'] === 0): ?>
        <div class="text-center py-4">
          <div class="size-10 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
            <svg class="size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
          </div>
          <div class="text-xs font-semibold text-gray-700">Everything looks good!</div>
          <div class="text-xs text-gray-400 mt-0.5">No actions required.</div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     APEXCHARTS
     FIX #10a: Revenue chart kept at height 200 (original).
     FIX #10b: Donut labels now remapped — "OutForDelivery"
               becomes "Out for Delivery" in the tooltip.
     FIX #10c: Donut total label changed from 'Total' → 'Orders'.
     FIX #10d: paymentMethodChart series now has name: 'Orders'
               so tooltip reads "X Orders" not just "X".
════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── Revenue area chart ──────────────────────────────────────────────────────
  const rev6mLabels = <?= $chart6m_labels ?>;
  const rev6mData   = <?= $chart6m_data ?>;

  if (document.getElementById('revenueChart')) {
    new ApexCharts(document.getElementById('revenueChart'), {
      series: [{ name: 'Revenue (₱)', data: rev6mData }],
      chart: { type: 'area', height: 200, toolbar: { show: false }, zoom: { enabled: false } },
      colors: ['#f97316'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: {
        categories: rev6mLabels,
        labels: { style: { fontSize: '10px', colors: '#9ca3af' } },
        axisBorder: { show: false }, axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          formatter: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v),
          style: { fontSize: '10px', colors: '#9ca3af' }
        }
      },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
      dataLabels: { enabled: false },
      markers: { size: 0 },
      tooltip: {
        y: { formatter: v => '₱' + parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
      }
    }).render();
  }

  // ── Order status donut ───────────────────────────────────────────────────────
  const osRawLabels = <?= $order_status_labels ?>;
  const osData      = <?= $order_status_data ?>;
  // FIX #10b: remap raw DB status keys to human-readable display labels
  const osLabelMap  = { Pending:'Pending', Processing:'Processing', OutForDelivery:'Out for Delivery', Delivered:'Delivered', Cancelled:'Cancelled' };
  const osLabels    = osRawLabels.map(l => osLabelMap[l] || l);

  if (document.getElementById('orderStatusChart') && osData.some(v => v > 0)) {
    new ApexCharts(document.getElementById('orderStatusChart'), {
      series: osData,
      chart: { type: 'donut', height: 180 },
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
                show: true,
                label: 'Orders',  // FIX #10c: was 'Total'
                formatter: () => <?= $stats['total_orders'] ?>,
                style: { fontSize: '14px', fontWeight: 700, color: '#1f2937' }
              }
            }
          }
        }
      },
      stroke: { width: 2, colors: ['#fff'] },
      tooltip: { y: { formatter: v => v + ' orders' } }
    }).render();
  }

  // ── Payment method bar ───────────────────────────────────────────────────────
  const pmRawLabels = <?= $pm_labels ?>;
  const pmData      = <?= $pm_data ?>;
  const pmLabelMap  = { gcash:'GCash', paymaya:'PayMaya', grab_pay:'GrabPay', qrph:'QR Ph', cod:'COD', card:'Card' };
  const pmDisplay   = pmRawLabels.map(l => pmLabelMap[l] || l);

  if (document.getElementById('paymentMethodChart') && pmData.length > 0) {
    new ApexCharts(document.getElementById('paymentMethodChart'), {
      // FIX #10d: series now has name:'Orders' — tooltip shows "X Orders"
      series: [{ name: 'Orders', data: pmData }],
      chart: { type: 'bar', height: 150, toolbar: { show: false } },
      colors: ['#f97316'],
      xaxis: {
        categories: pmDisplay,
        labels: { style: { fontSize: '10px', colors: '#9ca3af' } },
        axisBorder: { show: false }
      },
      yaxis: { labels: { style: { fontSize: '10px', colors: '#9ca3af' } } },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
      dataLabels: { enabled: false },
      plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '55%' } },
      tooltip: { y: { formatter: v => v + ' orders' } }
    }).render();
  }

});
</script>

<!-- ═══════════════════════════════════════════════════════
     STYLES
     FIX #7f: .logs-row selector used here matches the class
              applied in the HTML above (was mismatched in a
              previous version that used a different selector).
════════════════════════════════════════════════════════ -->
<style>
.logs-row {
  transition: all 0.2s ease;
  border-left: 4px solid transparent;
}
.logs-row:hover {
  border-left-color: #f97316;
}
</style>