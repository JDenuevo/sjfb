<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['loggedinasuser']) || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php'); exit;
}

$uid  = (int)$_SESSION['account_id'];
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// ── User info ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT account_first_name, account_last_name, account_email, city
    FROM accounts WHERE account_id = ? AND is_deleted = 0
");
$stmt->bind_param('i', $uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();
$firstName = htmlspecialchars($user['account_first_name'] ?? 'there');

// ── Quick stats ────────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(order_status = 'Delivered') AS delivered,
        SUM(order_status IN ('Pending','Processing','OutForDelivery')) AS active
    FROM orders WHERE account_id = ? AND is_deleted = 0
");
$stmt->bind_param('i', $uid); $stmt->execute();
$stats = $stmt->get_result()->fetch_assoc(); $stmt->close();

// ── Latest active order ────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT order_id, order_code, order_status, total_price, order_date
    FROM orders
    WHERE account_id = ? AND order_status IN ('Pending','Processing','OutForDelivery')
    ORDER BY order_date DESC LIMIT 1
");
$stmt->bind_param('i', $uid); $stmt->execute();
$activeOrder = $stmt->get_result()->fetch_assoc(); $stmt->close();

// ── Recent orders (last 3) ─────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT order_id, order_code, order_status, total_price, order_date
    FROM orders WHERE account_id = ? AND is_deleted = 0
    ORDER BY order_date DESC LIMIT 3
");
$stmt->bind_param('i', $uid); $stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// ── Today's featured products ─────────────────────────────────────────────
$featuredResult = $conn->query("
    SELECT
        p.product_id,
        p.product_name,
        p.product_unit,
        p.created_at,
        pi.image_path,
        pv_agg.min_price,
        pv_agg.has_discount,
        pv_agg.total_stock
    FROM products p
    LEFT JOIN product_images pi
        ON pi.product_id = p.product_id AND pi.is_primary = 1
    INNER JOIN (
        SELECT
            product_id,
            MIN(variant_price)  AS min_price,
            MAX(discount_price) AS has_discount,
            SUM(stock_quantity) AS total_stock
        FROM product_variants
        WHERE is_deleted = 0
        GROUP BY product_id
        HAVING SUM(stock_quantity) > 0
    ) pv_agg ON pv_agg.product_id = p.product_id
    WHERE p.is_deleted = 0
    ORDER BY p.created_at DESC
    LIMIT 8
");
$featured = $featuredResult ? $featuredResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Categories ─────────────────────────────────────────────────────────────
$catResult = $conn->query("
    SELECT category_id, category_name, category_slug, category_image
    FROM product_categories
    WHERE parent_id IS NULL AND is_active = 1
    ORDER BY sort_order ASC LIMIT 8
");
$categories = $catResult ? $catResult->fetch_all(MYSQLI_ASSOC) : [];

// ✅ BEST PRACTICE: Define baseUrl ONCE at the top (project root)
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
         . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

// ✅ Image helper function - FIX: ADD THIS FUNCTION
function img_url($image_path) {
    if (!empty($image_path)) {
        return 'http://localhost/sjfbi-js/uploads/products/' . ltrim($image_path, '/');
    }
    return 'http://localhost/sjfbi-js/uploads/products/default.png';
}

if (!function_exists('fp_slugify')) {
    function fp_slugify(string $text): string {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}

$statusConf = [
    'Pending'        => ['label'=>'Pending',          'dot'=>'bg-yellow-400', 'text'=>'text-yellow-600', 'badge'=>'bg-yellow-50 text-yellow-700 border-yellow-200'],
    'Processing'     => ['label'=>'Processing',       'dot'=>'bg-blue-400',   'text'=>'text-blue-600',   'badge'=>'bg-blue-50 text-blue-700 border-blue-200'],
    'OutForDelivery' => ['label'=>'Out for Delivery', 'dot'=>'bg-purple-400', 'text'=>'text-purple-600', 'badge'=>'bg-purple-50 text-purple-700 border-purple-200'],
    'Delivered'      => ['label'=>'Delivered',        'dot'=>'bg-green-400',  'text'=>'text-green-600',  'badge'=>'bg-green-50 text-green-700 border-green-200'],
    'Cancelled'      => ['label'=>'Cancelled',        'dot'=>'bg-red-400',    'text'=>'text-red-600',    'badge'=>'bg-red-50 text-red-700 border-red-200'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | St. Joseph Fish Brokerage Inc.</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link rel="shortcut icon"             href="../assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon"  href="../assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="../assets/icons/logo.svg">
  <link rel="apple-touch-icon"          href="../assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <style>
    body { font-family: 'Lexend', sans-serif; }

    .welcome-hero { background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24 100%); }

    .hero-dots {
      background-image: radial-gradient(circle, rgba(255,255,255,.12) 1px, transparent 1px);
      background-size: 24px 24px;
    }

    /* Card hover lift */
    .product-card { transition: transform .2s ease, box-shadow .2s ease; }
    .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,.10); }

    /* Pulse badge */
    @keyframes pulse-ring {
      0%   { transform: scale(.9); opacity: .8; }
      70%  { transform: scale(1.3); opacity: 0; }
      100% { transform: scale(1.3); opacity: 0; }
    }
    .live-dot::before {
      content: '';
      position: absolute; inset: -3px;
      border-radius: 50%;
      background: #22c55e;
      animation: pulse-ring 2s ease-out infinite;
    }

    /* Scroll snap categories */
    .cat-scroll { scrollbar-width: none; }
    .cat-scroll::-webkit-scrollbar { display: none; }

    /* Stagger reveal */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .5s ease forwards; opacity: 0; }
    .delay-1 { animation-delay: .1s; }
    .delay-2 { animation-delay: .2s; }
    .delay-3 { animation-delay: .3s; }
    .delay-4 { animation-delay: .4s; }
  </style>
</head>
<body class="bg-gray-50">
<?php include('../components/preloaders.php'); ?>

<?php include './components/navigation.php'; ?>

<?php
/**
 * Drop this near the top of accounts/home.php.
 * Requires $_SESSION['email_verified'] to be set (it is, from functions/add.php / verify.php).
 */
if (isset($_SESSION['account_id']) && empty($_SESSION['email_verified'])):
?>
<div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <span class="text-sm">
            Please verify your email to unlock ordering. Check your inbox for the verification link.
        </span>
    </div>
    <form action="../functions/add.php" method="POST" class="shrink-0">
        <button type="submit" name="resend_verification"
                class="text-sm font-medium text-[#E85D20] hover:underline whitespace-nowrap">
            Resend email
        </button>
    </form>
</div>
<?php endif; ?>

<!-- ══ HERO — Personalized Welcome ══════════════════════════════════════════════════════ -->
<div class="relative overflow-hidden welcome-hero pt-10 pb-20 px-4">
  <div class="relative z-10">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <!-- Greeting -->
        <div class="fade-up delay-1">
          <p class="text-orange-100 text-sm font-medium mb-1"><?= $greeting ?>, <?= $firstName ?> 👋</p>
          <h1 class="text-white text-3xl sm:text-4xl font-extrabold leading-tight">
            Ready for today's<br><span class="text-yellow-200">fresh catch?</span>
          </h1>
          <p class="text-orange-100 text-sm mt-2 font-light">Navotas Fish Port · Updated daily</p>
          <a href="shop.php"
            class="mt-5 inline-flex items-center gap-2 bg-white text-orange-600 font-bold text-sm px-6 py-3 rounded-2xl shadow-lg hover:bg-orange-50 transition-all active:scale-95">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Shop Now
          </a>
        </div>

        <!-- Quick stats -->
        <div class="fade-up delay-2 grid grid-cols-3 gap-3 sm:gap-4">
          <?php
          $kpis = [
              ['v' => $stats['total']    ?? 0, 'l' => 'Orders',   'i' => '📦'],
              ['v' => $stats['active']   ?? 0, 'l' => 'Active',   'i' => '🚚'],
              ['v' => $stats['delivered']?? 0, 'l' => 'Delivered','i' => '✅'],
          ];
          foreach ($kpis as $k): ?>
          <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4 text-center border border-white/30">
            <div class="text-2xl mb-1"><?= $k['i'] ?></div>
            <div class="text-white text-xl font-bold"><?= $k['v'] ?></div>
            <div class="text-orange-100 text-xs"><?= $k['l'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Active order banner -->
      <?php if ($activeOrder): $sc = $statusConf[$activeOrder['order_status']] ?? $statusConf['Pending']; ?>
      <div class="fade-up delay-3 mt-6 bg-white/95 backdrop-blur rounded-2xl px-5 py-4 flex items-center gap-4 shadow-lg border border-white/60">
        <div class="relative shrink-0">
          <span class="live-dot relative size-3 rounded-full <?= $sc['dot'] ?> inline-block"></span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 font-medium">Active Order</p>
          <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($activeOrder['order_code']) ?></p>
        </div>
        <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full border <?= $sc['badge'] ?>">
          <?= $sc['label'] ?>
        </span>
        <a href="orders.php" class="shrink-0 text-xs text-orange-600 font-semibold hover:underline">Track →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="absolute -top-8 -right-8 w-48 h-48 bg-white/10 rounded-full"></div>
  <div class="absolute -bottom-12 -right-4 w-32 h-32 bg-white/10 rounded-full"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     QUICK ACTIONS
══════════════════════════════════════════════════════════════ -->
<section class="max-w-7xl mx-auto px-4 -mt-4 mb-8 fade-up delay-2">
  <div class="grid grid-cols-4 gap-3">
    <?php
    $actions = [
      ['href'=>'orders.php',   'icon'=>'📦', 'label'=>'My Orders',   'color'=>'bg-orange-50 border-orange-100'],
      ['href'=>'shop.php',     'icon'=>'🐟', 'label'=>'Shop',        'color'=>'bg-blue-50   border-blue-100'],
      ['href'=>'orders.php',   'icon'=>'🔍', 'label'=>'Track',       'color'=>'bg-purple-50 border-purple-100'],
      ['href'=>'checkout.php', 'icon'=>'🛒', 'label'=>'Checkout',    'color'=>'bg-green-50  border-green-100'],
    ];
    foreach ($actions as $a): ?>
    <a href="<?= $a['href'] ?>"
       class="flex flex-col items-center gap-2 p-4 rounded-2xl border <?= $a['color'] ?> hover:shadow-md transition-all active:scale-95 bg-white">
      <span class="text-2xl"><?= $a['icon'] ?></span>
      <span class="text-xs font-semibold text-gray-700 text-center"><?= $a['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     CATEGORIES
══════════════════════════════════════════════════════════════ -->
<?php if (!empty($categories)): ?>
<section class="max-w-7xl mx-auto px-4 mb-10">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-bold text-gray-800">Browse Categories</h2>
    <a href="shop.php" class="text-xs text-orange-500 font-semibold hover:underline">See all →</a>
  </div>
  <div class="flex gap-3 overflow-x-auto cat-scroll pb-2">
    <a href="shop.php"
       class="shrink-0 flex flex-col items-center gap-2 px-4 py-3 bg-orange-500 text-white rounded-2xl text-xs font-bold shadow-sm shadow-orange-200">
      <span class="text-xl">🎣</span>All
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="shop.php?category=<?= urlencode($cat['category_slug'] ?? '') ?>"
       class="shrink-0 flex flex-col items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-2xl text-xs font-semibold text-gray-700 hover:border-orange-300 hover:text-orange-600 transition-colors">
      <span class="text-xl">🐟</span>
      <?= htmlspecialchars($cat['category_name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     TODAY'S FRESH CATCH — Product Grid
══════════════════════════════════════════════════════════════ -->
<section class="max-w-7xl mx-auto px-4 mb-10">
  <div class="flex items-center justify-between mb-5">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <span class="relative flex size-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full size-2.5 bg-green-500"></span>
        </span>
        <span class="text-xs font-bold text-green-600 uppercase tracking-widest">Live Inventory</span>
      </div>
      <h2 class="text-xl font-extrabold text-gray-900">Today's Fresh Catch 🐟</h2>
    </div>
    <a href="shop.php" class="text-xs text-orange-500 font-semibold hover:underline">View all →</a>
  </div>

  <?php if (empty($featured)): ?>
  <div class="text-center py-16 text-gray-400 text-sm">No products available today.</div>
  <?php else: ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($featured as $i => $p):
      // Build image URL using helper function
      $imgUrl = img_url($p['image_path'] ?? null);

      $isLow = (int)$p['total_stock'] > 0 && (int)$p['total_stock'] <= 10;
      $isNew = strtotime($p['created_at']) > strtotime('-7 days');
    ?>
    <a href="<?= $baseUrl ?>item/<?= urlencode(fp_slugify($p['product_name'])) ?>" class="product-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group">
      <div class="relative">
        <img src="<?= htmlspecialchars($imgUrl) ?>" 
             alt="<?= htmlspecialchars($p['product_name']) ?>"
             class="w-full h-36 object-cover">

        <!-- Badges -->
        <div class="absolute top-2 left-2 flex flex-col gap-1">
          <?php if ($isNew): ?>
          <span class="text-[10px] font-bold bg-orange-500 text-white px-2 py-0.5 rounded-full">NEW</span>
          <?php endif; ?>
          <?php if ($isLow): ?>
          <span class="text-[10px] font-bold bg-red-500 text-white px-2 py-0.5 rounded-full">LIMITED</span>
          <?php endif; ?>
          <?php if ($p['has_discount']): ?>
          <span class="text-[10px] font-bold bg-green-500 text-white px-2 py-0.5 rounded-full">SALE</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="p-3">
        <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($p['product_name']) ?></p>
        <p class="text-xs text-gray-400 mt-0.5 truncate"><?= htmlspecialchars($p['product_unit'] ?? '') ?></p>
        <p class="text-sm font-bold text-orange-600 mt-2">
          from ₱<?= number_format($p['min_price'], 0) ?>
        </p>
      </div>
    </a>
    <?php endforeach; ?>
</div>
  <?php endif; ?>
</section>

<!-- ══════════════════════════════════════════════════════════════
     RECENT ORDERS
══════════════════════════════════════════════════════════════ -->
<?php if (!empty($recentOrders)): ?>
<section class="max-w-5xl mx-auto px-4 mb-10">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-base font-bold text-gray-800">Recent Orders</h2>
    <a href="orders.php" class="text-xs text-orange-500 font-semibold hover:underline">View all →</a>
  </div>
  <div class="space-y-3">
    <?php foreach ($recentOrders as $o):
      $sc = $statusConf[$o['order_status']] ?? $statusConf['Pending'];
    ?>
    <a href="orders.php"
       class="flex items-center gap-4 bg-white rounded-2xl px-5 py-4 border border-gray-100 shadow-sm hover:border-orange-200 hover:shadow-md transition-all">
      <div class="size-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
        <svg class="size-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($o['order_code']) ?></p>
        <p class="text-xs text-gray-400"><?= date('M j, Y', strtotime($o['order_date'])) ?></p>
      </div>
      <div class="text-right shrink-0">
        <p class="text-sm font-bold text-gray-800">₱<?= number_format($o['total_price'], 2) ?></p>
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full border <?= $sc['badge'] ?>">
          <?= $sc['label'] ?>
        </span>
      </div>
      <svg class="size-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>
<?php $conn->close(); ?>