<?php

// Get the base URL for your site
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

$cart = $_SESSION['cart'] ?? [];
?>

<!-- ========== HEADER ========== -->
<header class="top-0 z-50 flex flex-wrap md:justify-start md:flex-nowrap w-full py-1 bg-white shadow-xs">
  <nav class="main-nav relative max-w-7xl w-full grid px-4 md:px-6 mx-auto">
    <div class="flex items-center">
      <!-- Logo -->
      <a class="flex-none rounded-xl inline-block font-semibold focus:outline-hidden focus:opacity-80" href="/" aria-label="Home">
        <img src="<?= $baseUrl ?>/assets/icons/logo.svg" class="w-24 h-24 hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">
      </a>
      <!-- End Logo -->
    </div>

    <div class="top-nav flex flex-row items-center justify-center gap-x-8">
      <a class="text-black cursor-pointer hover:text-orange-500 transition translate-y-1" href="<?= $baseUrl ?>/" aria-current="page">Home</a>
      <a class="text-black cursor-pointer hover:text-orange-500 transition translate-y-1" href="<?= $baseUrl ?>aboutus">About</a>
      <a class="text-black cursor-pointer hover:text-orange-500 transition translate-y-1" href="<?= $baseUrl ?>sustainability">Sustainability</a>
      <a class="text-black cursor-pointer hover:text-orange-500 transition translate-y-1" href="<?= $baseUrl ?>services">Services</a>
      <a class="text-black cursor-pointer hover:text-orange-500 transition translate-y-1" href="<?= $baseUrl ?>careers">Careers</a>
    </div>

    <div class="flex items-center justify-center gap-x-1 ms-auto">
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
      
      <button type="button" onclick="openModal()" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-nowrap rounded-xl border border-transparent bg-orange-600 text-white hover:bg-orange-400 focus:outline-hidden focus:bg-orange-400 transition disabled:opacity-50 disabled:pointer-events-none">
        Sign in
      </button>

      <!-- Mobile Navigation Dropdown (visible only on small screens) -->
      <div class="relative inline-flex ml-4">
        <button type="button" id="mobile-nav-button" class="size-9.5 flex justify-center items-center rounded-xl border border-gray-200 text-black hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" aria-label="Toggle mobile menu">
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
          <div class="p-2 space-y-1">
            <a href="<?= $baseUrl ?>/" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-home">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
              </svg>
              Home
            </a>
            <a href="<?= $baseUrl ?>aboutus" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-paperclip">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M15 7l-6.5 6.5a1.5 1.5 0 0 0 3 3l6.5 -6.5a3 3 0 0 0 -6 -6l-6.5 6.5a4.5 4.5 0 0 0 9 9l6.5 -6.5" />
              </svg>
              About us
            </a>
            <a href="<?= $baseUrl ?>sustainability" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bulb">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 12h1m8 -9v1m8 8h1m-15.4 -6.4l.7 .7m12.1 -.7l-.7 .7" />
                <path d="M9 16a5 5 0 1 1 6 0a3.5 3.5 0 0 0 -1 3a2 2 0 0 1 -4 0a3.5 3.5 0 0 0 -1 -3" />
                <path d="M9.7 17l4.6 0" />
              </svg>
              Sustainability
            </a>
            <a href="<?= $baseUrl ?>services" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 ">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fish-hook">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M16 9v6a5 5 0 0 1 -10 0v-4l3 3" />
                <path d="M14 7a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                <path d="M16 5v-2" />
              </svg>
              Services
            </a>
            <a href="<?= $baseUrl ?>careers" class="w-full flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-100 ">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 9a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2l0 -9" />
                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                <path d="M12 12l0 .01" />
                <path d="M3 13a20 20 0 0 0 18 0" />
              </svg>
              Careers
            </a>
          </div>
        </div>
        <!-- End Mobile Navigation Dropdown Menu -->
      </div>
      <!-- End Mobile Navigation Dropdown -->
    </div>
  </nav>
</header>
<!-- ========== END HEADER ========== -->

<?php include('./components/sign_in.php'); ?>

<?php include('./components/cart.php'); ?>

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
  .top-nav {
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
  }

</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const mobileNavButton = document.getElementById('mobile-nav-button');
    const mobileNavMenu = document.getElementById('mobile-nav-menu');
    const menuIconOpen = mobileNavButton.querySelector('.menu-icon-open');
    const menuIconClose = mobileNavButton.querySelector('.menu-icon-close');
    let isMenuOpen = false;

    mobileNavButton.addEventListener('click', function(e) {
      e.stopPropagation();
      
      if (isMenuOpen) {
        // Close menu
        mobileNavMenu.classList.remove('show');
        setTimeout(() => {
          mobileNavMenu.classList.add('hidden');
        }, 300);
        menuIconOpen.classList.remove('hidden');
        menuIconClose.classList.add('hidden');
      } else {
        // Open menu
        mobileNavMenu.classList.remove('hidden');
        setTimeout(() => {
          mobileNavMenu.classList.add('show');
        }, 10);
        menuIconOpen.classList.add('hidden');
        menuIconClose.classList.remove('hidden');
      }
      
      isMenuOpen = !isMenuOpen;
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (isMenuOpen && !mobileNavButton.contains(e.target) && !mobileNavMenu.contains(e.target)) {
        mobileNavMenu.classList.remove('show');
        setTimeout(() => {
          mobileNavMenu.classList.add('hidden');
        }, 300);
        menuIconOpen.classList.remove('hidden');
        menuIconClose.classList.add('hidden');
        isMenuOpen = false;
      }
    });

    // Close menu when clicking on a menu item
    mobileNavMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function() {
        mobileNavMenu.classList.remove('show');
        setTimeout(() => {
          mobileNavMenu.classList.add('hidden');
        }, 300);
        menuIconOpen.classList.remove('hidden');
        menuIconClose.classList.add('hidden');
        isMenuOpen = false;
      });
    });

    // Close menu on window resize (if resizing to larger screen)
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 1024 && isMenuOpen) { // lg breakpoint
        mobileNavMenu.classList.remove('show');
        setTimeout(() => {
          mobileNavMenu.classList.add('hidden');
        }, 300);
        menuIconOpen.classList.remove('hidden');
        menuIconClose.classList.add('hidden');
        isMenuOpen = false;
      }
    });
  });

  function openModal() {
    document.getElementById('hs-modal-signin').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('hs-modal-signin').classList.add('hidden');
  }
</script>