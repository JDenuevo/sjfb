<?php
// components/navigation.php
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
         . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

$cart    = $_SESSION['cart'] ?? [];
$cartQty = count($cart);

$navUser = [];
if (isset($_SESSION['account_id']) && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT account_first_name, account_last_name, account_email, role
        FROM accounts WHERE account_id = ? AND is_deleted = 0 LIMIT 1
    ");
    $stmt->bind_param('i', $_SESSION['account_id']);
    $stmt->execute();
    $navUser = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

$navFirst    = htmlspecialchars($navUser['account_first_name'] ?? 'Guest');
$navLast     = htmlspecialchars($navUser['account_last_name']  ?? '');
$navEmail    = htmlspecialchars($navUser['account_email']      ?? '');
$navRole     = $navUser['role'] ?? 'guest';
$navInitials = strtoupper(substr($navFirst, 0, 1) . substr($navLast, 0, 1)) ?: 'G';
$isLoggedIn  = isset($_SESSION['account_id']);
$currentPage = basename($_SERVER['PHP_SELF']);

$navLinks = [
    ['href' => 'account/home.php',   'label' => 'Home'],
    ['href' => 'account/shop.php',   'label' => 'Seafood Shop'],
    ['href' => 'account/orders.php', 'label' => 'Orders'],
];
?>

<header id="main-header" class="fixed top-0 inset-x-0 z-50 bg-white border-b border-gray-100 shadow-sm transition-transform duration-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-3">

    <!-- Logo -->
    <a href="<?= $baseUrl ?>home.php" class="flex items-center gap-2.5 shrink-0 group" aria-label="Home">
      <img src="<?= $baseUrl ?>assets/icons/logo.svg" alt="SJFBI"
           class="w-9 h-9 object-contain transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110">
      <div class="hidden sm:flex flex-col leading-tight">
        <span class="text-[13px] font-bold text-orange-600 tracking-tight">St. Joseph</span>
        <span class="text-[10px] font-medium text-gray-400">Fish Brokerage Inc.</span>
      </div>
    </a>

    <!-- Desktop nav links — centered -->
    <nav class="hidden md:flex items-center gap-1 mx-auto" aria-label="Main">
      <?php foreach ($navLinks as $nl): ?>
      <a href="<?= $baseUrl . $nl['href'] ?>"
         class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors duration-200
                <?= $currentPage === $nl['href']
                    ? 'bg-orange-50 text-orange-600 font-semibold'
                    : 'text-gray-500 hover:text-orange-600 hover:bg-orange-50' ?>">
        <?= $nl['label'] ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <!-- Right actions -->
    <div class="flex items-center gap-2 ml-auto md:ml-0">

      <div class="ml-auto relative">
        <button type="button" class="size-10 relative flex justify-center items-center rounded-xl bg-white border border-gray-200 text-black hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-cart-sidebar" aria-label="Toggle navigation" onclick="openOffCanvas()">
          <span class="sr-only">Cart</span>
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
          </svg>
        </button>
        <!-- Cart Count Positioned at Top -->
        <span id="cart-count-sidebar" class="cart-count cart-count-2 bg-orange-500 text-white w-5 h-5 text-xs font-bold rounded-full flex items-center justify-center">
          <?php echo count($cart); ?>
        </span>
      </div>

      <!-- Avatar dropdown — Preline hs-dropdown -->
      <div class="hs-dropdown [--strategy:absolute] [--placement:bottom-right] [--auto-close:inside] relative inline-flex">

        <button id="nav-avatar-btn" type="button"
                class="hs-dropdown-toggle flex items-center gap-2 h-10 pl-1 pr-3 rounded-xl border border-gray-200 hover:bg-orange-50 hover:border-orange-200 transition-all duration-200 focus:outline-none"
                aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">
          <!-- Initials avatar -->
          <span class="size-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-400 text-dark text-xs font-bold flex items-center justify-center shrink-0 select-none">
            <?= $navInitials ?>
          </span>
          <svg class="size-3 text-gray-400 hs-dropdown-open:rotate-180 transition-transform duration-200"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>

        <!-- Dropdown panel -->
        <div class="hs-dropdown-menu hs-dropdown-open:opacity-100 hidden opacity-0 transition-[opacity,margin] duration-200
                    w-60 z-20 bg-white border border-gray-100 rounded-2xl shadow-xl p-1.5 mt-2"
             role="menu" aria-orientation="vertical" aria-labelledby="nav-avatar-btn">

          <!-- User info header -->
          <div class="flex items-center gap-3 px-3 py-3 mb-1">
            <span class="size-9 rounded-xl bg-gradient-to-br from-orange-500 to-orange-400 text-white text-sm font-bold flex items-center justify-center shrink-0">
              <?= $navInitials ?>
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-800 truncate"><?= $navFirst . ' ' . $navLast ?></p>
              <p class="text-xs text-gray-400 truncate"><?= $navEmail ?: 'Not signed in' ?></p>
              <?php if ($navRole !== 'guest'): ?>
              <span class="inline-block mt-1 px-1.5 py-0.5 bg-orange-50 text-orange-600 text-[10px] font-semibold rounded-md border border-orange-100 capitalize">
                <?= $navRole ?>
              </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="border-t border-gray-100 my-1"></div>

          <!-- Mobile-only nav links -->
          <div class="md:hidden">
            <?php foreach ($navLinks as $nl): ?>
            <a href="<?= $baseUrl . $nl['href'] ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 transition-colors"
               role="menuitem">
              <?php if ($nl['href'] === 'home.php'): ?>
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              <?php elseif ($nl['href'] === 'shop.php'): ?>
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <?php else: ?>
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <?php endif; ?>
              <?= $nl['label'] ?>
            </a>
            <?php endforeach; ?>
            <div class="border-t border-gray-100 my-1"></div>
          </div>

          <!-- Account links -->
          <?php if ($isLoggedIn): ?>
          <a href="<?= $baseUrl ?>account/profile.php"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 transition-colors"
             role="menuitem">
            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
          </a>
          <a href="<?= $baseUrl ?>account/checkout.php"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 transition-colors"
             role="menuitem">
            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Checkout
          </a>
          <div class="border-t border-gray-100 my-1"></div>
          <a href="<?= $baseUrl ?>account/logout.php"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
             role="menuitem">
            <svg class="size-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
            Log out
          </a>
          <?php else: ?>
          <a href="<?= $baseUrl ?>login.php"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 transition-colors"
             role="menuitem">
            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Sign in
          </a>
          <a href="<?= $baseUrl ?>register.php"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 transition-colors"
             role="menuitem">
            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Create account
          </a>
          <?php endif; ?>
        </div>
      </div>
      <!-- End Avatar dropdown -->

    </div>
  </div>
</header>

<!-- Spacer -->
<div class="h-16"></div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/account/components/cart.php'; ?>


<style>
  .cart-count-1 {
    background-color: #f97316; /* Orange */
    color: white;
    min-width: 1.25rem; 
    height: 1.25rem;
    border-radius: 9999px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 0.25rem;
    position: absolute;
    top: -4px; /* Adjust to move it upwards */
    right: -4px; /* Adjust to move it to the left */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .cart-count-2 {
    background-color: #f97316; /* Orange */
    color: white;
    min-width: 1.25rem; 
    height: 1.25rem;
    border-radius: 9999px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 0.25rem;
    position: absolute;
    top: -4px; /* Adjust to move it upwards */
    right: -4px; /* Adjust to move it to the left */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  /* Hide header when scrolling down */
  .header-hidden {
    transform: translateY(-100%);
  }
  
  /* Show header when at top or scrolling up */
  .header-visible {
    transform: translateY(0);
  }

  /* Mobile menu animation */
  #mobile-nav-menu {
    opacity: 0;
    transform: translateY(-10px);
  }
  
  #mobile-nav-menu.show {
    opacity: 1;
    transform: translateY(0);
  }

  /* Default: xs / sm / md → 2 columns */
  .main-nav {
    grid-template-columns: auto auto;
    align-items: center;
  }

  /* Hide center nav on small screens */
  .top-nav, .logo-big {
    display: none;
  }

  /* lg and up → 3 columns */
  @media screen and (min-width: 1024px) {
    .main-nav {
      grid-template-columns: auto 1fr auto;
    }

    .top-nav {
      display: flex;
      justify-content: center;
    }

    .logo-big{
      display: block;
    }

  }

</style>

<script>
(function () {
  // Scroll hide/show
  var header = document.getElementById('main-header');
  var lastY = 0, ticking = false;
  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(function () {
        var y = window.scrollY;
        if (y > 80) header.style.transform = y > lastY ? 'translateY(-100%)' : 'translateY(0)';
        else header.style.transform = 'translateY(0)';
        lastY = Math.max(y, 0);
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });

  // Cart badge pop
  var badge = document.querySelector('#cart-count-sidebar');
  if (badge && window.MutationObserver) {
    new MutationObserver(function () {
      var n = parseInt(badge.textContent) || 0;
      badge.classList.toggle('hidden', n === 0);
      badge.animate([{ transform: 'scale(1.5)' }, { transform: 'scale(1)' }],
                    { duration: 250, easing: 'cubic-bezier(.34,1.56,.64,1)' });
    }).observe(badge, { childList: true, characterData: true, subtree: true });
  }
})();
</script>