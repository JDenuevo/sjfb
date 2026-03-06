<?php
// header.php — improved
$currentPage = basename($_SERVER['PHP_SELF']);
$pageLabels = [
  'dashboard.php'           => 'Dashboard',
  'orders.php'              => 'Orders',
  'payments.php'            => 'Payments',
  'products.php'            => 'Products',
  'category.php'            => 'Categories',
  'accounts.php'            => 'Accounts',
  'riders.php'              => 'Riders',
  'blogs.php'               => 'Blogs',
  'cooking_suggestions.php' => 'Cooking Suggestions',
  'reviews.php'             => 'Reviews',
  'profile.php'             => 'Profile',
];
$pageTitle = $pageLabels[$currentPage] ?? 'Super Admin';

// Fetch admin name for header greeting
$adminName = 'Super Admin';
if (isset($_SESSION['account_id'])) {
  $aid = (int)$_SESSION['account_id'];
  global $conn;
  $hStmt = $conn->prepare("SELECT first_name FROM accounts WHERE account_id = ?");
  $hStmt->bind_param("i", $aid);
  $hStmt->execute();
  $hRow = $hStmt->get_result()->fetch_assoc();
  if ($hRow) $adminName = $hRow['first_name'];
  $hStmt->close();
}
?>

<!-- ========== HEADER ========== -->
<header class="sticky top-0 inset-x-0 z-48 w-full bg-white border-b border-gray-100 shadow-sm lg:ps-65">
  <nav class="px-4 sm:px-6 h-14 flex items-center w-full gap-x-3">

    <!-- Mobile: sidebar toggle + logo -->
    <div class="flex items-center gap-x-3 lg:hidden">
      <button type="button"
        class="size-8 flex justify-center items-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors"
        aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar"
        data-hs-overlay="#hs-application-sidebar">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/>
        </svg>
      </button>
    </div>

    <!-- Page title / breadcrumb (desktop) -->
    <div class="hidden lg:flex items-center gap-x-2 flex-1">
      <span class="text-xs text-gray-400 font-medium">St. Joseph</span>
      <svg class="size-3 text-gray-300" fill="none" viewBox="0 0 16 16">
        <path d="M5 1L10.687 7.161a.5.5 0 010 .678L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($pageTitle) ?></span>
    </div>

    <!-- Right side actions -->
    <div class="flex items-center gap-x-1 ms-auto">

      <!-- Greeting chip (desktop only) -->
      <div class="hidden sm:flex items-center gap-x-2 px-3 py-1.5 rounded-lg bg-orange-50 border border-orange-100 me-2">
        <div class="size-2 rounded-full bg-green-400 animate-pulse"></div>
        <span class="text-xs font-medium text-orange-700">Hi, <?= htmlspecialchars($adminName) ?></span>
      </div>

      <!-- Profile button -->
      <a href="profile.php"
        class="relative group size-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-orange-50 hover:text-orange-600 transition-colors"
        title="Profile">
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
          <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
          <path d="M6.168 18.849a4 4 0 0 1 3.832-2.849h4a4 4 0 0 1 3.834 2.855"/>
        </svg>
        <!-- Tooltip -->
        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs bg-gray-800 text-white px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">Profile</span>
      </a>

      <!-- Divider -->
      <div class="w-px h-5 bg-gray-200 mx-1"></div>

      <!-- Logout button -->
      <?php if (isset($_SESSION['account_id'])): ?>
      <a href="logout.php"
        class="relative group size-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors"
        title="Logout">
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M14 8v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/>
          <path d="M9 12h12l-3-3"/><path d="M18 15l3-3"/>
        </svg>
        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs bg-gray-800 text-white px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">Logout</span>
      </a>
      <?php endif; ?>

    </div>
  </nav>
</header>
<!-- ========== END HEADER ========== -->