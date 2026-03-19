<?php
// sidebar.php — with live notification badges
$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
  ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard', 'category' => 'General'],

  ['href' => 'orders.php',    'label' => 'Orders',    'icon' => 'orders',    'category' => 'Sales'],
  ['href' => 'payments.php',  'label' => 'Payments',  'icon' => 'payments',  'category' => 'Sales'],
  ['href' => 'deliveries.php',  'label' => 'Deliveries',  'icon' => 'deliveries',  'category' => 'Sales'],

  ['href' => 'products.php',  'label' => 'Products',  'icon' => 'products',  'category' => 'Catalog'],
  ['href' => 'discounts.php',   'label' => 'Discounts',   'icon' => 'discounts',   'category' => 'Catalog'],
  ['href' => 'category.php',  'label' => 'Categories','icon' => 'category',  'category' => 'Catalog'],
  ['href' => 'markets.php',   'label' => 'Markets',   'icon' => 'markets',   'category' => 'Catalog'],

  ['href' => 'blogs.php',     'label' => 'Blogs',     'icon' => 'blogs',     'category' => 'Content'],
  ['href' => 'reviews.php',   'label' => 'Reviews',   'icon' => 'reviews',   'category' => 'Content'],
  ['href' => 'cooking_suggestions.php', 'label' => 'Cooking Suggestions', 'icon' => 'cooking', 'category' => 'Content'],

  ['href' => 'accounts.php',  'label' => 'Accounts',  'icon' => 'accounts',  'category' => 'Users'],
  ['href' => 'riders.php',    'label' => 'Riders',    'icon' => 'riders',    'category' => 'Users'],

  ['href' => 'inquiries.php', 'label' => 'Inquiries', 'icon' => 'inquiries', 'category' => 'Support'],
];

$groupedNav = [];
foreach ($navItems as $item) {
    $groupedNav[$item['category']][] = $item;
}

// ── Notification badge counts ────────────────────────────────────────────
// Each count represents items that need admin attention.
// We only query if the $conn variable is available (it's included before this file).
$badges = [];
$badgeDetails = []; // Store more details for tooltips

if (isset($conn) && $conn instanceof mysqli) {

     // Orders: Paid orders (online payments) waiting to be approved
    $r = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'Paid' AND is_deleted = 0");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['orders.php'] = ($badges['orders.php'] ?? 0) + $count;
        $badgeDetails['orders_paid'] = ['count' => $count, 'label' => 'paid orders awaiting approval'];
    }

    // Orders: Pending orders (COD) waiting to be approved
    $r = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'Pending' AND is_deleted = 0");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['orders.php'] = ($badges['orders.php'] ?? 0) + $count;
        $badgeDetails['orders_pending'] = ['count' => $count, 'label' => 'COD orders awaiting approval'];
    }

    // Reviews: Pending moderation
    $r = $conn->query("SELECT COUNT(*) AS c FROM reviews WHERE status = 'pending'");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['reviews.php'] = $count;
        $badgeDetails['reviews.php'] = ['count' => $count, 'label' => 'reviews pending'];
    }

    // Accounts: New registrations in the last 48 hours (not guests, not deleted)
    $r = $conn->query("SELECT COUNT(*) AS c FROM accounts WHERE role NOT IN ('guest','super_admin','admin') AND is_deleted = 0 AND created_at >= NOW() - INTERVAL 48 HOUR");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['accounts.php'] = $count;
        $badgeDetails['accounts.php'] = ['count' => $count, 'label' => 'new accounts'];
    }

    // Riders: New rider profiles in the last 7 days
    $r = $conn->query("SELECT COUNT(*) AS c FROM riders WHERE is_deleted = 0 AND created_at >= NOW() - INTERVAL 7 DAY");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['riders.php'] = $count;
        $badgeDetails['riders.php'] = ['count' => $count, 'label' => 'new riders'];
    }

    // Inquiries: Received in the last 48 hours (no is_read flag on table)
    $r = $conn->query("SELECT COUNT(*) AS c FROM contact_inquiries WHERE created_at >= NOW() - INTERVAL 48 HOUR");
    if ($r) {
        $count = (int)$r->fetch_assoc()['c'];
        $badges['inquiries.php'] = $count;
        $badgeDetails['inquiries.php'] = ['count' => $count, 'label' => 'new inquiries'];
    }
}

// ── Icon paths ────────────────────────────────────────────────────────────
$icons = [
  'dashboard' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  'orders'    => '<path d="M3.5 5.5l1.5 1.5l2.5-2.5"/><path d="M3.5 11.5l1.5 1.5l2.5-2.5"/><path d="M3.5 17.5l1.5 1.5l2.5-2.5"/><path d="M11 6l9 0"/><path d="M11 12l9 0"/><path d="M11 18l9 0"/>',
  'payments'  => '<path d="M17 8v-3a1 1 0 0 0-1-1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1-1 1h-12a2 2 0 0 1-2-2v-12"/><path d="M20 12v4h-4a2 2 0 0 1 0-4h4"/>',
  'deliveries'=> '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 17a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M15 17a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5" /><path d="M3 9l4 0" />',
  'products'  => '<path d="M16.69 7.44a6.973 6.973 0 0 0-1.69 4.56c0 1.747.64 3.345 1.699 4.571"/><path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25-7.761-12.285-6.142-20 2.504"/><path d="M18 11v.01"/><path d="M11.5 10.5c-.667 1-.667 2 0 3"/>',
  'discounts' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 15l6 -6" /><path d="M9 9.5a.5 .5 0 1 0 1 0a.5 .5 0 1 0 -1 0" fill="currentColor" /><path d="M14 14.5a.5 .5 0 1 0 1 0a.5 .5 0 1 0 -1 0" fill="currentColor" /><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />',
  'category'  => '<path d="M4 4h6v6h-6z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6h-6z"/><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/>',
  'markets'   => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4" /><path d="M5 21l0 -10.15" /><path d="M19 21l0 -10.15" /><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4" />',
  'blogs'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
  'reviews'   => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
  'cooking'   => '<path d="M3 2h1a2 2 0 0 1 2 2v1.5a.5.5 0 0 0 1 0V4c0-1.1.9-2 2-2h1"/><path d="M9 7.5V22"/><path d="M3 22v-3a6 6 0 0 1 12 0v3"/><path d="M21 15a2 2 0 0 0-2-2h-1"/>',
  'accounts'  => '<path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0-8 0"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/>',
  'riders'    => '<path d="M12 4a9 9 0 0 1 5.656 16h-11.312a9 9 0 0 1 5.656-16z"/><path d="M20 9h-8.8a1 1 0 0 0-.968 1.246c.507 2 1.596 3.418 3.268 4.254c2 1 4.333 1.5 7 1.5"/>',
  'inquiries' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 19h-10a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v2.5" /><path d="M19 22v.01" /><path d="M19 19a2.003 2.003 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483" />',
];

// ── Badge color per page ──────────────────────────────────────────────────
$badgeColor = [
  'dashboard.php' => 'bg-gray-700',
  'orders.php'    => 'bg-blue-500',
  'payments.php'  => 'bg-green-500',
  'deliveries.php' => 'bg-sky-500',
  'products.php'  => 'bg-orange-500',
  'discounts.php' => 'bg-pink-500',
  'category.php'  => 'bg-indigo-500',
  'markets.php'   => 'bg-teal-500',
  'blogs.php'     => 'bg-purple-500',
  'reviews.php'   => 'bg-yellow-500',
  'cooking.php'   => 'bg-rose-500',
  'accounts.php'  => 'bg-cyan-500',
  'riders.php'    => 'bg-violet-500',
  'inquiries.php' => 'bg-red-500',
];

// ── Helper: format badge number (cap at 99) ───────────────────────────────
function fmtBadge(int $n): string {
    return $n > 99 ? '99+' : (string)$n;
}
?>

<style>
  /* pulse animation for sidebar badges */
  @keyframes sb-pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: .85; transform: scale(1.1); }
  }
  .sb-badge-pulse { animation: sb-pulse 2s ease-in-out infinite; }
  
  /* Tooltip styles */
  .badge-tooltip {
    position: relative;
  }
  .badge-tooltip:hover:after {
    content: attr(data-tooltip);
    position: absolute;
    right: 0;
    top: -25px;
    background: #1f2937;
    color: white;
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 6px;
    white-space: nowrap;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    pointer-events: none;
  }
  
  /* Category header with count */
  .category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  /* Active item glow */
  .nav-item-active {
    box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3);
  }
</style>

<!-- ========== SIDEBAR ========== -->
<div id="hs-application-sidebar"
  class="hs-overlay [--auto-close:lg]
    hs-overlay-open:translate-x-0
    -translate-x-full transition-all duration-300 transform
    w-64 h-full
    hidden fixed inset-y-0 start-0 z-60
    bg-white border-e border-gray-100 shadow-lg
    lg:block lg:translate-x-0 lg:end-auto lg:bottom-0"
  role="dialog" tabindex="-1" aria-label="Sidebar">

  <div class="flex flex-col h-full">

    <!-- Logo area with toggle for mobile -->
    <div class="h-14 flex items-center justify-between px-5 border-b border-gray-100 shrink-0">
      <a href="dashboard.php" class="flex items-center focus:outline-none focus:opacity-80">
        <img src="../assets/icons/landscape-logo.svg" alt="St. Joseph Fish Brokerage Inc." class="h-8 w-auto">
      </a>
      <!-- Mobile close button -->
      <button type="button" class="lg:hidden text-gray-400 hover:text-gray-600" data-hs-overlay="#hs-application-sidebar">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto py-4 px-3
      [&::-webkit-scrollbar]:w-1.5
      [&::-webkit-scrollbar-thumb]:rounded-full
      [&::-webkit-scrollbar-track]:bg-transparent
      [&::-webkit-scrollbar-thumb]:bg-gray-200">

      <ul class="space-y-0.5">
        <?php 
        $totalBadges = array_sum($badges);
        ?>
        
        <?php foreach ($groupedNav as $category => $items): ?>
          <?php 
          // Calculate category total badges
          $catTotal = 0;
          foreach ($items as $item) {
              $catTotal += $badges[$item['href']] ?? 0;
          }
          ?>
          <li class="px-3 mt-4 mb-1">
            <div class="category-header">
              <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">
                <?= htmlspecialchars($category) ?>
              </p>
              <?php if ($catTotal > 0): ?>
                <span class="text-[9px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full">
                  <?= fmtBadge($catTotal) ?>
                </span>
              <?php endif; ?>
            </div>
          </li>

          <ul class="space-y-0.5">
            <?php foreach ($items as $item):
              $isActive  = ($currentPage === $item['href']);
              $iconPath  = $icons[$item['icon']] ?? '';
              $badgeCnt  = $badges[$item['href']] ?? 0;
              $badgeCls  = $badgeColor[$item['href']] ?? 'bg-red-500';
              $tooltip   = $badgeDetails[$item['href']]['label'] ?? 'needs attention';
            ?>
              <li>
                <a href="<?= $item['href'] ?>"
                  class="group flex items-center gap-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                    <?= $isActive
                      ? 'bg-orange-500 text-white shadow-sm shadow-orange-200 nav-item-active'
                      : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' ?>">

                  <!-- Icon bubble -->
                  <span class="shrink-0 size-8 flex items-center justify-center rounded-lg
                    <?= $isActive ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100' ?>">
                    <svg class="size-4 <?= $isActive ? 'text-white' : 'text-gray-500 group-hover:text-orange-600' ?>"
                      xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="2">
                      <path stroke="none" d="M0 0h24v24H0z"/>
                      <?= $iconPath ?>
                    </svg>
                  </span>

                  <!-- Label with truncation -->
                  <span class="truncate flex-1"><?= htmlspecialchars($item['label']) ?></span>

                  <!-- Notification badge with tooltip -->
                  <?php if ($badgeCnt > 0): ?>
                    <span class="badge-tooltip shrink-0 ms-auto inline-flex items-center justify-center
                                 min-w-[20px] h-5 px-1.5
                                 <?= $badgeCls ?> text-white
                                 text-[10px] font-bold leading-none
                                 rounded-full sb-badge-pulse"
                          data-tooltip="<?= $badgeCnt ?> <?= $tooltip ?>"
                          title="<?= $badgeCnt ?> <?= $tooltip ?>">
                      <?= fmtBadge($badgeCnt) ?>
                    </span>
                  <?php elseif ($isActive): ?>
                    <span class="ms-auto size-1.5 rounded-full bg-white/70"></span>
                  <?php endif; ?>

                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endforeach; ?>
      </ul>

    </div>

    <!-- Footer with user info and quick actions -->
    <div class="shrink-0 px-4 py-4 border-t border-gray-100">
      <div class="flex items-center gap-x-3 px-3 py-2.5 rounded-xl bg-orange-50 border border-orange-100 relative group">
        <div class="size-8 rounded-lg bg-orange-500 flex items-center justify-center shrink-0">
          <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
        <div class="overflow-hidden">
          <p class="text-xs font-semibold text-gray-800 truncate">SJFBI Admin</p>
          <p class="text-xs text-gray-400">Super Admin Panel</p>
        </div>
        
        <!-- Quick logout button (optional) -->
        <a href="logout.php" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity"
           title="Logout">
          <svg class="size-4 text-gray-400 hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </a>
      </div>
      
      <!-- System status indicator (optional) -->
      <div class="mt-2 flex items-center gap-1.5 px-3">
        <span class="size-1.5 rounded-full bg-green-500 animate-pulse"></span>
        <span class="text-[9px] text-gray-400">System online</span>
        <span class="text-[9px] text-gray-300 ml-auto">v2.1.0</span>
      </div>
    </div>

  </div>
</div>
<!-- ========== END SIDEBAR ========== -->