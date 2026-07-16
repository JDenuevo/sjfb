<?php
// Get the base URL for your site
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

$cart = $_SESSION['cart'] ?? [];
?>

<!-- ========== HEADER ========== -->
<header id="main-header" class="fixed top-0 left-0 right-0 z-50 flex flex-wrap md:justify-start md:flex-nowrap w-full py-2 bg-white shadow-xs transition-transform duration-300 ease-in-out">
  <nav class="main-nav relative max-w-7xl w-full grid px-4 md:px-6 mx-auto">
    <div class="flex items-center">
      <!-- Logo -->
      <a class="flex-none rounded-xl inline-block font-semibold focus:outline-hidden focus:opacity-80" href="/" aria-label="Home">
        <img src="<?= $baseUrl ?>assets/icons/square-logo.png" class="logo-big w-auto h-24 transition-all duration-500 hover:scale-110 filter drop-shadow-lg" alt="St. Joseph Fish Brokerage Inc. Logo">
        <img src="<?= $baseUrl ?>assets/icons/landscape-logo.png" class="md:hidden logo-small w-36 h-auto transition-all duration-500 hover:scale-110 filter drop-shadow-lg" alt="St. Joseph Fish Brokerage Inc. Logo">
      </a>
      <!-- End Logo -->
    </div>

    <div class="top-nav flex items-center justify-center gap-x-8 w-fit mx-auto bg-gray-100 border border- rounded-full px-8 py-2">
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>" aria-current="page" title="St. Joseph Fish Brokerage Inc. – Largest Fish Brokerage in the Philippines">Home</a>
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>shop.php" title="Seafood Shop – Fresh Fish and Seafood in the Philippines">Seafood Shop</a>
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>about.php" title="About St. Joseph Fish Brokerage Inc. – The Largest Fish Brokerage in the Philippines">About</a>
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>events.php" title="Sustainable Seafood Practices – St. Joseph Fish Brokerage Inc.">Events</a>
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>services.php" title="Fish Brokerage Services in the Philippines – Seafood Trading & Wholesale">Services</a>
      <a class="text-gray-700 hover:text-orange-500 cursor-pointer text-sm font-medium rounded-xl transition-all duration-300" href="<?= $baseUrl ?>contact.php" title="Contact us at St. Joseph Fish Brokerage Inc. – Contact us Directly">Contact</a>
    </div> 

    <div class="nav-actions flex items-center justify-end gap-x-1">
      <div class="relative">
        <a href="./track.php" class="size-10 relative flex justify-center items-center rounded-xl bg-white border border-gray-200 text-black hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0" /></svg>
        </a>
      </div>
      <div class="relative">
        <button type="button" class="size-10 relative flex justify-center items-center rounded-xl bg-white border border-gray-200 text-black hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-cart-sidebar" aria-label="Toggle navigation" onclick="openOffCanvas()">
          <span class="sr-only">Cart</span>
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
          </svg>
        </button>
        <span id="cart-count-sidebar" class="cart-count cart-count-2 bg-orange-500 text-white w-5 h-5 text-xs font-bold rounded-full flex items-center justify-center">
          <?php echo count($cart); ?>
        </span>
      </div>

      <div class="hidden md:flex items-center">
        <img src="<?= $baseUrl ?>assets/icons/eos.png" class="h-10 w-auto opacity-80 hover:opacity-100 transition-opacity" alt="We Run on EOS">
      </div>
      
      <button type="button" onclick="openSignInModal()" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-nowrap rounded-xl border border-transparent bg-orange-600 text-white hover:bg-orange-400 focus:outline-hidden focus:bg-orange-400 transition disabled:opacity-50 disabled:pointer-events-none">
        Sign in
      </button>

      <!-- Mobile Navigation Dropdown (visible only on small screens) -->
      <div class="relative inline-flex md:hidden">
        <button type="button" id="mobile-nav-button" class="size-10 flex justify-center items-center rounded-xl border border-gray-200 text-black hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" aria-label="Toggle mobile menu">
          <svg class="menu-icon-open shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" x2="21" y1="6" y2="6" />
            <line x1="3" x2="21" y1="12" y2="12" />
            <line x1="3" x2="21" y1="18" y2="18" />
          </svg>
          <svg class="menu-icon-close shrink-0 size-4 hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
          </svg>
        </button>

        <!-- Mobile Navigation Dropdown Menu -->
        <div id="mobile-nav-menu" class="absolute top-full right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-lg hidden z-50 transition-all duration-300">
          <div class="inline-flex px-4 py-3 border-b border-gray-100 text-center">
            <img src="<?= $baseUrl ?>assets/icons/eos.png" class="h-10 w-auto" alt="We Run on EOS">
            <p class="text-gray-500 text-[10px] my-auto">Entrepreneurial Operating System</p>
          </div>
          <div class="p-2 space-y-1">
            <a href="<?= $baseUrl ?>" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">Home</a>
            <a href="<?= $baseUrl ?>shop.php" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">Seafood Shop</a>
            <a href="<?= $baseUrl ?>about.php" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">About us</a>
            <a href="<?= $baseUrl ?>events.php" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">Events</a>
            <a href="<?= $baseUrl ?>services.php" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">Services</a>
            <a href="<?= $baseUrl ?>contact.php" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">Contact</a>
          </div>
        </div>
      </div>
    </div>
  </nav>
</header>
<!-- ========== END HEADER ========== -->

<div class="pt-16 md:pt-24"></div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/components/sign_in.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/components/cart.php'; ?>

<style>
  .cart-count-1, .cart-count-2 {
    background-color: #f97316;
    color: white;
    min-width: 1.25rem; 
    height: 1.25rem;
    border-radius: 9999px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 0.25rem;
    position: absolute;
    top: -4px;
    right: -4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .header-hidden  { transform: translateY(-100%); }
  .header-visible { transform: translateY(0); }

  #mobile-nav-menu {
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  #mobile-nav-menu.show {
    opacity: 1;
    transform: translateY(0);
  }

  /* Mobile: 2-col, hide center nav */
  .main-nav {
    grid-template-columns: auto auto;
    align-items: center;
  }
  .top-nav, .logo-big { display: none; }

  /* Desktop: logo left | nav center | actions right */
  @media screen and (min-width: 1024px) {
    .main-nav {
      grid-template-columns: 1fr auto 1fr; /* equal sides = truly centered middle */
    }
    .nav-actions {
      justify-self: end; /* push right column to the far right */
    }
    .top-nav { display: flex; justify-content: center; }
    .logo-big { display: block; }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('main-header');
    let lastScrollTop = 0;
    const scrollThreshold = 100;
    let ticking = false;
    let isHeaderVisible = true;

    function handleScroll() {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      if (!ticking) {
        window.requestAnimationFrame(function() {
          if (scrollTop > scrollThreshold) {
            if (scrollTop > lastScrollTop) {
              if (isHeaderVisible) { header.classList.add('header-hidden'); header.classList.remove('header-visible'); isHeaderVisible = false; }
            } else {
              if (!isHeaderVisible) { header.classList.remove('header-hidden'); header.classList.add('header-visible'); isHeaderVisible = true; }
            }
          } else {
            if (!isHeaderVisible) { header.classList.remove('header-hidden'); header.classList.add('header-visible'); isHeaderVisible = true; }
          }
          lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
          ticking = false;
        });
        ticking = true;
      }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    header.classList.add('header-visible');

    const mobileNavButton = document.getElementById('mobile-nav-button');
    const mobileNavMenu   = document.getElementById('mobile-nav-menu');
    const menuIconOpen    = mobileNavButton.querySelector('.menu-icon-open');
    const menuIconClose   = mobileNavButton.querySelector('.menu-icon-close');
    let isMenuOpen = false;

    function closeMenu() {
      mobileNavMenu.classList.remove('show');
      setTimeout(() => mobileNavMenu.classList.add('hidden'), 300);
      menuIconOpen.classList.remove('hidden');
      menuIconClose.classList.add('hidden');
      isMenuOpen = false;
    }

    mobileNavButton.addEventListener('click', function(e) {
      e.stopPropagation();
      if (isMenuOpen) {
        closeMenu();
      } else {
        mobileNavMenu.classList.remove('hidden');
        setTimeout(() => mobileNavMenu.classList.add('show'), 10);
        menuIconOpen.classList.add('hidden');
        menuIconClose.classList.remove('hidden');
        isMenuOpen = true;
      }
    });

    document.addEventListener('click', function(e) {
      if (isMenuOpen && !mobileNavButton.contains(e.target) && !mobileNavMenu.contains(e.target)) closeMenu();
    });

    mobileNavMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

    window.addEventListener('resize', function() {
      if (window.innerWidth >= 1024 && isMenuOpen) closeMenu();
    });
  });

</script>