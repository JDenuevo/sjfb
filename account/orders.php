<?php
session_start();
include '../conn.php';

// Check if the user is logged in as user and account_id exists
if (!isset($_SESSION["loggedinasuser"]) || $_SESSION["loggedinasuser"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../../index.php");
    exit;
}

$account_id = (int)$_SESSION['account_id'];

// ── Fetch user info ────────────────────────────────────────────────────────────
$user_stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, city FROM accounts WHERE account_id = ?");
$user_stmt->bind_param("i", $account_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// ── URL params ─────────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 8;
$offset        = ($page - 1) * $per_page;

// ── Order summary counts per status ───────────────────────────────────────────
$counts_stmt = $conn->prepare("
    SELECT order_status, COUNT(*) AS cnt
    FROM orders
    WHERE account_id = ?
    GROUP BY order_status
");
$counts_stmt->bind_param("i", $account_id);
$counts_stmt->execute();
$counts_raw    = $counts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$counts_stmt->close();

$orderCounts = ['all' => 0, 'Pending' => 0, 'Processing' => 0, 'OutForDelivery' => 0, 'Delivered' => 0, 'Cancelled' => 0];
foreach ($counts_raw as $row) {
    $orderCounts[$row['order_status']] = (int)$row['cnt'];
    $orderCounts['all'] += (int)$row['cnt'];
}

// ── Total spend ────────────────────────────────────────────────────────────────
$spend_stmt = $conn->prepare("
    SELECT COALESCE(SUM(p.gross_amount), 0) AS total_spent
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    WHERE o.account_id = ? AND p.payment_status = 'Paid'
");
$spend_stmt->bind_param("i", $account_id);
$spend_stmt->execute();
$total_spent = (float)$spend_stmt->get_result()->fetch_assoc()['total_spent'];
$spend_stmt->close();

// ── Build orders query with optional status filter + search ───────────────────
$where_parts = ["o.account_id = ?"];
$bind_types  = "i";
$bind_values = [$account_id];

if ($filter_status !== 'all') {
    $where_parts[] = "o.order_status = ?";
    $bind_types   .= "s";
    $bind_values[] = $filter_status;
}
if ($search !== '') {
    $where_parts[] = "(o.order_code LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ?)";
    $bind_types   .= "sss";
    $like = "%{$search}%";
    $bind_values[] = $like;
    $bind_values[] = $like;
    $bind_values[] = $like;
}
$where_sql = implode(' AND ', $where_parts);

// Count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders o WHERE {$where_sql}");
$count_stmt->bind_param($bind_types, ...$bind_values);
$count_stmt->execute();
$total_orders = (int)$count_stmt->get_result()->fetch_assoc()['cnt'];
$count_stmt->close();
$total_pages = max(1, ceil($total_orders / $per_page));

// Fetch orders
$orders_stmt = $conn->prepare("
    SELECT o.*,
           p.payment_status,
           p.gross_amount     AS payment_amount,
           p.paid_at,
           r.rider_id,
           ra.first_name      AS rider_first_name,
           ra.last_name       AS rider_last_name,
           ra.phone_number    AS rider_phone,
           r.vehicle_type
    FROM orders o
    LEFT JOIN payments p  ON p.order_id = o.order_id
    LEFT JOIN riders r    ON o.assigned_rider_id = r.rider_id
    LEFT JOIN accounts ra ON r.account_id = ra.account_id
    WHERE {$where_sql}
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
");
$bind_types   .= "ii";
$bind_values[] = $per_page;
$bind_values[] = $offset;
$orders_stmt->bind_param($bind_types, ...$bind_values);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

// ── Fetch items for each order (one query, grouped in PHP) ────────────────────
$order_ids = array_column($orders, 'order_id');
$items_by_order = [];
if (!empty($order_ids)) {
    $in_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $items_stmt = $conn->prepare("
        SELECT oi.order_id, oi.order_item_id, oi.quantity, oi.price,
               oi.is_reviewed, oi.review_id,
               p.product_name,
               pv.variant_name
        FROM order_items oi
        LEFT JOIN products p          ON oi.product_id = p.product_id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
        WHERE oi.order_id IN ({$in_placeholders})
        ORDER BY oi.order_item_id ASC
    ");
    $items_stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $items_stmt->execute();
    foreach ($items_stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
    $items_stmt->close();
}

// ── Fetch order_status_history for each order (for the timeline modal) ────────
$history_by_order = [];
if (!empty($order_ids)) {
    $in_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $hist_stmt = $conn->prepare("
        SELECT osh.*
        FROM order_status_history osh
        WHERE osh.order_id IN ({$in_placeholders})
        ORDER BY osh.created_at ASC
    ");
    $hist_stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $hist_stmt->execute();
    foreach ($hist_stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $h) {
        $history_by_order[$h['order_id']][] = $h;
    }
    $hist_stmt->close();
}

// ── UI helpers ─────────────────────────────────────────────────────────────────
$statusLabels = [
    'Pending'        => 'Pending',
    'Processing'     => 'Processing',
    'OutForDelivery' => 'Out for Delivery',
    'Delivered'      => 'Delivered',
    'Cancelled'      => 'Cancelled',
];
$statusConf = [
    'Pending'        => ['bg'=>'bg-yellow-50', 'text'=>'text-yellow-600', 'border'=>'border-yellow-200', 'dot'=>'bg-yellow-500', 'badge'=>'bg-yellow-100 text-yellow-700'],
    'Processing'     => ['bg'=>'bg-blue-50',   'text'=>'text-blue-600',   'border'=>'border-blue-200',   'dot'=>'bg-blue-500',   'badge'=>'bg-blue-100 text-blue-700'],
    'OutForDelivery' => ['bg'=>'bg-purple-50', 'text'=>'text-purple-600', 'border'=>'border-purple-200', 'dot'=>'bg-purple-500', 'badge'=>'bg-purple-100 text-purple-700'],
    'Delivered'      => ['bg'=>'bg-green-50',  'text'=>'text-green-600',  'border'=>'border-green-200',  'dot'=>'bg-green-500',  'badge'=>'bg-green-100 text-green-700'],
    'Cancelled'      => ['bg'=>'bg-red-50',    'text'=>'text-red-600',    'border'=>'border-red-200',    'dot'=>'bg-red-500',    'badge'=>'bg-red-100 text-red-700'],
];
$paymentBadge = [
    'Paid'     => 'bg-green-100 text-green-700',
    'Pending'  => 'bg-yellow-100 text-yellow-700',
    'Failed'   => 'bg-red-100 text-red-700',
    'Refunded' => 'bg-blue-100 text-blue-700',
];
$methodLabels = [
    'gcash'    => 'GCash',   'paymaya' => 'PayMaya', 'grab_pay' => 'GrabPay',
    'qrph'     => 'QR Ph',   'cod'     => 'COD',     'card'     => 'Card',
];
$progressSteps = [
    ['key'=>'Pending',        'label'=>'Order Placed',     'icon'=>'🛒'],
    ['key'=>'Processing',     'label'=>'Processing',       'icon'=>'⚙️'],
    ['key'=>'OutForDelivery', 'label'=>'Out for Delivery', 'icon'=>'🛵'],
    ['key'=>'Delivered',      'label'=>'Delivered',        'icon'=>'✅'],
];
$stepIndex = ['Pending'=>0,'Processing'=>1,'OutForDelivery'=>2,'Delivered'=>3,'Cancelled'=>-1];

// Build current URL without page param for pagination links
$base_url_params = http_build_query(array_filter(['status'=>$filter_status,'q'=>$search]));
$base_url = 'my_orders.php' . ($base_url_params ? "?{$base_url_params}&" : '?');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | St. Joseph Fish Brokerage Inc.</title>
    <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
    <link href="../output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>
<body class="bg-gray-50 font-[Lexend]">

<?php include('./components/navigation.php'); ?>

<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <!-- ── Page Header ──────────────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Hello, <?= htmlspecialchars($user['first_name']) ?> — track all your orders here.
            </p>
        </div>
        <a href="../shop.php"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-xl transition-colors shadow-sm shadow-orange-200">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            Shop Again
        </a>
    </div>

    <!-- ── KPI Strip ────────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $kpiItems = [
            ['label'=>'Total Orders',   'value'=> $orderCounts['all'],                    'icon'=>'📦', 'color'=>'bg-blue-50   text-blue-600'],
            ['label'=>'Delivered',      'value'=> $orderCounts['Delivered'],              'icon'=>'✅', 'color'=>'bg-green-50  text-green-600'],
            ['label'=>'In Progress',    'value'=> $orderCounts['Processing'] + $orderCounts['OutForDelivery'], 'icon'=>'🚚', 'color'=>'bg-purple-50 text-purple-600'],
            ['label'=>'Total Spent',    'value'=> '₱'.number_format($total_spent, 0),     'icon'=>'💰', 'color'=>'bg-orange-50 text-orange-600'],
        ];
        foreach ($kpiItems as $k): ?>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <div class="text-xl mb-1"><?= $k['icon'] ?></div>
            <div class="text-xl font-bold text-gray-800"><?= $k['value'] ?></div>
            <div class="text-xs text-gray-500 mt-0.5"><?= $k['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Filter Tabs + Search ─────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row gap-3">
        <!-- Status tabs -->
        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1 overflow-x-auto">
            <?php
            $tabs = ['all'=>'All', 'Pending'=>'Pending', 'Processing'=>'Processing',
                     'OutForDelivery'=>'On the Way', 'Delivered'=>'Delivered', 'Cancelled'=>'Cancelled'];
            foreach ($tabs as $key => $label):
                $active = $filter_status === $key;
                $cnt    = $orderCounts[$key] ?? 0;
                $href   = 'my_orders.php?' . http_build_query(array_filter(['status'=>$key,'q'=>$search]));
            ?>
            <a href="<?= $href ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all
                      <?= $active ? 'bg-orange-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100' ?>">
                <?= $label ?>
                <?php if ($cnt > 0): ?>
                <span class="<?= $active ? 'bg-white/30 text-white' : 'bg-gray-100 text-gray-600' ?> text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                    <?= $cnt ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <form method="GET" class="flex-1">
            <?php if ($filter_status !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
            <?php endif; ?>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search order code…"
                       class="w-full ps-10 pe-4 py-3 text-sm bg-white border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 transition-colors">
            </div>
        </form>
    </div>

    <!-- ── Flash message ────────────────────────────────────────────────────── -->
    <?php if (!empty($_SESSION['message'])):
        $msg = $_SESSION['message'];
        $cls = $msg['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700';
        unset($_SESSION['message']);
    ?>
    <div class="<?= $cls ?> border rounded-xl px-4 py-3 text-sm" role="alert">
        <span class="font-semibold"><?= ucfirst($msg['type']) ?>:</span> <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <!-- ── Orders list ──────────────────────────────────────────────────────── -->
    <?php if (empty($orders)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="size-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4 text-3xl">📦</div>
        <h3 class="text-base font-semibold text-gray-700">No orders found</h3>
        <p class="text-sm text-gray-400 mt-1">
            <?= $search ? "No results for \"{$search}\".": 'You have no orders in this category yet.'?>        
        </p>
        <a href="../shop.php" class="inline-block mt-4 px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors">
            Browse Products
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
    <?php foreach ($orders as $order):
        $oid       = $order['order_id'];
        $sc        = $statusConf[$order['order_status']] ?? $statusConf['Pending'];
        $psBadge   = $paymentBadge[$order['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-600';
        $method    = $methodLabels[$order['payment_method'] ?? ''] ?? ucfirst($order['payment_method'] ?? '—');
        $items     = $items_by_order[$oid] ?? [];
        $history   = $history_by_order[$oid] ?? [];
        $curStep   = $stepIndex[$order['order_status']] ?? 0;
        $isCancelled = $order['order_status'] === 'Cancelled';
        $isDelivered = $order['order_status'] === 'Delivered';
        $hasRider  = !empty($order['rider_first_name']);
    ?>
    <!-- Order card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <!-- Card header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-orange-600"><?= htmlspecialchars($order['order_code']) ?></span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $sc['badge'] ?>">
                    <?= $statusLabels[$order['order_status']] ?>
                </span>
                <?php if (!$isCancelled): ?>
                <span class="text-xs text-gray-400 hidden sm:inline"><?= date('M j, Y', strtotime($order['order_date'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-gray-800">₱<?= number_format($order['total_price'], 2) ?></span>
                <span class="text-xs <?= $psBadge ?> px-2 py-0.5 rounded-full"><?= $order['payment_status'] ?? 'Pending' ?></span>
                <!-- Toggle detail button -->
                <button onclick="toggleOrder(<?= $oid ?>)"
                    class="text-xs text-gray-400 hover:text-orange-500 transition-colors flex items-center gap-1">
                    <svg id="chevron-<?= $oid ?>" class="size-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>
        </div>

        <!-- Progress stepper (visible when not cancelled) -->
        <?php if (!$isCancelled): ?>
        <div class="px-5 py-4 border-b border-gray-50">
            <div class="flex items-center justify-between relative">
                <!-- Track line -->
                <div class="absolute left-0 right-0 top-5 h-0.5 bg-gray-200 z-0"></div>
                <!-- Filled portion -->
                <?php
                $fillPct = $curStep === 0 ? 0 : min(100, ($curStep / (count($progressSteps)-1)) * 100);
                ?>
                <div class="absolute left-0 top-5 h-0.5 bg-orange-400 z-0 transition-all" style="width:<?= $fillPct ?>%"></div>

                <?php foreach ($progressSteps as $si => $step):
                    $done   = $curStep >= $si;
                    $active = $curStep === $si;
                ?>
                <div class="flex flex-col items-center gap-1.5 relative z-10">
                    <div class="size-9 rounded-full flex items-center justify-center text-sm transition-all
                        <?= $done ? 'bg-orange-500 shadow shadow-orange-200' : 'bg-white border-2 border-gray-200' ?>">
                        <?= $done
                            ? $step['icon']
                            : '<span class="size-2.5 rounded-full '.($active?'bg-orange-300':'bg-gray-200').' inline-block"></span>'
                        ?>
                    </div>
                    <span class="text-[10px] font-medium text-center max-w-[65px] leading-tight
                        <?= $done ? 'text-orange-500' : 'text-gray-400' ?>">
                        <?= $step['label'] ?>
                    </span>
                    <!-- Timestamp from history if available -->
                    <?php
                    $stepTs = null;
                    foreach ($history as $h) {
                        if ($h['new_status'] === $step['key']) { $stepTs = $h['created_at']; break; }
                    }
                    if ($si === 0 && empty($stepTs)) $stepTs = $order['order_date'];
                    ?>
                    <?php if ($stepTs && $done): ?>
                    <span class="text-[9px] text-gray-400"><?= date('M j', strtotime($stepTs)) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Cancelled banner -->
        <div class="px-5 py-3 bg-red-50 border-b border-red-100 flex items-center gap-2">
            <svg class="size-4 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            <span class="text-xs text-red-600 font-medium">This order was cancelled.</span>
            <?php if (!empty($history)): $lastNote = end($history); ?>
            <?php if (!empty($lastNote['notes'])): ?>
            <span class="text-xs text-red-400">— <?= htmlspecialchars($lastNote['notes']) ?></span>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Items preview (always visible — just first 2 items) -->
        <div class="px-5 py-3 flex items-center gap-3">
            <div class="flex-1 flex flex-wrap gap-2">
                <?php foreach (array_slice($items, 0, 2) as $item): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full">
                    <?= htmlspecialchars($item['product_name']) ?>
                    <?php if (!empty($item['variant_name'])): ?>
                    <span class="text-gray-400">· <?= htmlspecialchars($item['variant_name']) ?></span>
                    <?php endif; ?>
                    <span class="font-semibold">×<?= $item['quantity'] ?></span>
                </span>
                <?php endforeach; ?>
                <?php if (count($items) > 2): ?>
                <span class="text-xs text-gray-400 self-center">+<?= count($items)-2 ?> more</span>
                <?php endif; ?>
            </div>
            <!-- Rider badge (if out for delivery) -->
            <?php if ($order['order_status'] === 'OutForDelivery' && $hasRider): ?>
            <div class="shrink-0 flex items-center gap-1.5 bg-purple-50 border border-purple-100 rounded-xl px-3 py-1.5">
                <span class="size-6 rounded-full bg-purple-200 text-purple-700 text-[10px] font-bold flex items-center justify-center">
                    <?= strtoupper(substr($order['rider_first_name'],0,1).substr($order['rider_last_name'],0,1)) ?>
                </span>
                <div>
                    <div class="text-[10px] font-semibold text-purple-700">
                        <?= htmlspecialchars($order['rider_first_name'].' '.$order['rider_last_name']) ?>
                    </div>
                    <div class="text-[9px] text-purple-400"><?= ucfirst($order['vehicle_type'] ?? '') ?> · <?= $order['rider_phone'] ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Expandable detail panel ─────────────────────────────────────── -->
        <div id="order-detail-<?= $oid ?>" class="hidden border-t border-gray-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                <!-- Left: full items table -->
                <div class="px-5 py-4">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Items Ordered</h4>
                    <div class="space-y-2">
                        <?php foreach ($items as $item):
                            $lineTotal = $item['quantity'] * $item['price'];
                        ?>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($item['product_name']) ?></p>
                                <?php if (!empty($item['variant_name'])): ?>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($item['variant_name']) ?> · ×<?= $item['quantity'] ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-gray-800">₱<?= number_format($lineTotal, 2) ?></p>
                                <p class="text-xs text-gray-400">₱<?= number_format($item['price'],2) ?>/pc</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="pt-2 border-t border-gray-100 flex justify-between">
                            <span class="text-xs font-semibold text-gray-600">Total</span>
                            <span class="text-sm font-bold text-orange-600">₱<?= number_format($order['total_price'], 2) ?></span>
                        </div>
                    </div>

                    <!-- Order details -->
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-5 mb-3">Order Details</h4>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Placed on</span>
                            <span class="font-medium text-gray-700"><?= date('M j, Y · g:i A', strtotime($order['order_date'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Payment</span>
                            <span class="font-medium text-gray-700"><?= $method ?></span>
                        </div>
                        <?php if (!empty($order['paid_at'])): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Paid at</span>
                            <span class="font-medium text-gray-700"><?= date('M j, Y · g:i A', strtotime($order['paid_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Address</span>
                            <span class="font-medium text-gray-700 text-right max-w-[55%]">
                                <?= htmlspecialchars($order['address'].', '.$order['city'].', '.$order['postal_code']) ?>
                            </span>
                        </div>
                        <?php if (!empty($order['delivery_notes'])): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Note</span>
                            <span class="italic text-orange-500 text-right max-w-[60%]">"<?= htmlspecialchars($order['delivery_notes']) ?>"</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: order timeline -->
                <div class="px-5 py-4">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Order Timeline</h4>

                    <?php if (empty($history)): ?>
                    <p class="text-xs text-gray-400">No status changes recorded yet.</p>
                    <?php else: ?>
                    <div class="relative space-y-3">
                        <div class="absolute left-[11px] top-2 bottom-2 w-px bg-gray-200 pointer-events-none"></div>
                        <?php foreach (array_reverse($history) as $hi => $h):
                            $oldL = $statusLabels[$h['old_status']] ?? $h['old_status'];
                            $newL = $statusLabels[$h['new_status']] ?? $h['new_status'];
                            $hsc  = $statusConf[$h['new_status']] ?? $statusConf['Pending'];
                            $isFirst = $hi === 0;
                        ?>
                        <div class="flex items-start gap-3 relative">
                            <div class="size-[22px] rounded-full <?= $hsc['dot'] ?> <?= $isFirst ? 'ring-4 ring-orange-100' : '' ?> shrink-0 z-10 mt-0.5"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs text-gray-500 line-through"><?= $oldL ?></span>
                                    <svg class="size-3 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    <span class="text-xs font-semibold <?= $hsc['text'] ?>"><?= $newL ?></span>
                                </div>
                                <?php if (!empty($h['notes'])): ?>
                                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($h['notes']) ?></p>
                                <?php endif; ?>
                                <p class="text-[11px] text-gray-400 mt-0.5"><?= date('M j, Y · g:i A', strtotime($h['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <!-- Order placed entry -->
                        <div class="flex items-start gap-3 relative">
                            <div class="size-[22px] rounded-full bg-gray-300 shrink-0 z-10 mt-0.5"></div>
                            <div>
                                <p class="text-xs font-semibold text-gray-600">Order placed</p>
                                <p class="text-[11px] text-gray-400 mt-0.5"><?= date('M j, Y · g:i A', strtotime($order['order_date'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Review prompt for delivered, un-reviewed items -->
                    <?php if ($isDelivered):
                        $reviewable = array_filter($items, fn($i) => !$i['is_reviewed']);
                        if (!empty($reviewable)): ?>
                    <div class="mt-4 p-3 bg-orange-50 border border-orange-100 rounded-xl">
                        <p class="text-xs font-semibold text-orange-700 mb-2">How was your order?</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($reviewable as $ri): ?>
                            <a href="../review.php?order_item_id=<?= $ri['order_item_id'] ?>&order_id=<?= $oid ?>"
                               class="text-xs bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                                Review <?= htmlspecialchars($ri['product_name']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; endif; ?>

                </div>

            </div><!-- /grid -->
        </div><!-- /expandable panel -->

    </div><!-- /order card -->
    <?php endforeach; ?>
    </div><!-- /orders list -->

    <!-- ── Pagination ────────────────────────────────────────────────────────── -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500">
            Showing <?= (($page-1)*$per_page)+1 ?>–<?= min($page*$per_page,$total_orders) ?> of <?= $total_orders ?> orders
        </p>
        <div class="flex items-center gap-1">
            <?php if ($page > 1): ?>
            <a href="<?= $base_url ?>page=<?= $page-1 ?>"
               class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">← Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
            <a href="<?= $base_url ?>page=<?= $p ?>"
               class="px-3 py-1.5 text-xs rounded-lg transition-colors <?= $p===$page ? 'bg-orange-500 text-white font-semibold' : 'text-gray-600 bg-white border border-gray-200 hover:bg-gray-50' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a href="<?= $base_url ?>page=<?= $page+1 ?>"
               class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; // end if orders not empty ?>

</div><!-- /container -->

<script>
function toggleOrder(id) {
    const panel   = document.getElementById('order-detail-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const open    = panel.classList.toggle('hidden');
    chevron.style.transform = open ? '' : 'rotate(180deg)';
}
</script>

<!-- JS PLUGINS -->
<!-- Required plugins -->
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

</body>
</html>