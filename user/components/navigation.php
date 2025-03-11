<header id="header" class="sticky top-0 z-50 flex flex-wrap md:justify-start md:flex-nowrap w-full p-2 transition-all duration-100" :class="headerClass">
  <nav class="relative max-w-5xl w-full flex flex-wrap basis-full items-center px-4 md:px-6 mx-auto">
    <!-- Button Group -->
    <div class="flex items-center justify-between w-full py-1 md:ps-6 md:order-1 md:col-span-2">
      <!-- Logo on the left -->
      <div class="md:hidden">
        <a href="../../../index.php" class="relative inline-block focus:outline-none">
          <img src="../../sjfbi-js/assets/icons/logo.svg" class="w-12 h-12 hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">
        </a>
      </div>
      
      <div class="md:hidden ml-auto">
        <button onclick="toggleMenu()" type="button" class="size-[38px] flex justify-center items-center text-sm font-semibold rounded-xl border transition-all duration-300 text-orange-500 bg-white border-white hover:bg-gray-200 focus:outline-none">
          <!-- Burger Icon -->
          <svg class="menu-icon burger-icon size-6" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="3" x2="21" y1="6" y2="6" />
            <line x1="3" x2="21" y1="12" y2="12" />
            <line x1="3" x2="21" y1="18" y2="18" />
          </svg>

          <!-- X Icon -->
          <svg class="menu-icon close-icon size-6 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M18 6l-12 12" />
            <path d="M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    <!-- End Button Group -->

    <!-- Collapse -->
    <div id="menu" class="hs-collapse hidden overflow-hidden transition-all duration-100 basis-full grow md:block md:w-auto md:basis-auto md:order-2 md:col-span-6">
      <div class="flex flex-col gap-y-4 gap-x-0 mt-5 md:flex-row md:justify-between md:items-center md:gap-y-0 md:gap-x-7 md:mt-0 justify-between">
        <!-- Router Links -->
        <div class="hidden md:block">
          <a href="../../index.php">
            <img src="../../sjfbi-js/assets/icons/logo.svg" class="w-24 h-24 cursor-pointer hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">
          </a>
        </div>

        <div class="flex-grow"></div>

        <div class="flex flex-row">
          <a href="orders.php" class="px-4 cursor-pointer font-semibold hover:text-orange-500 transition">Orders</a>
          <a href="order_status.php" class="px-4 cursor-pointer font-semibold hover:text-orange-500 transition">Order Status</a>
          <a href="account.php" class="px-4 cursor-pointer font-semibold hover:text-orange-500 transition">Account</a>
        </div>

        <div class="flex-grow"></div>
        
        <div>
          <button type="button" class="size-10 rounded-full justify-center items-center inline-flex hover:bg-gray-100 hover:text-orange-500 hover:scale-110 transition-all duration-500 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
              <path d="M21 21l-6 -6" />
            </svg>
          </button>
        
          <button type="button" class="size-10 rounded-full justify-center items-center inline-flex hover:bg-gray-100 hover:text-orange-500 hover:scale-110 transition-all duration-500 focus:outline-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-cart-sidebar" aria-label="Toggle navigation" onclick="openOffCanvas()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
              <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
              <path d="M17 17h-11v-14h-2" />
              <path d="M6 5l14 1l-1 7h-13" />
            </svg>
            (<span id="cart-count">0</span>)
          </button>

          <a href="./logout.php" class="size-10 rounded-full justify-center items-center inline-flex hover:bg-gray-100 hover:text-orange-500 hover:scale-110 transition-all duration-500 focus:outline-none">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-logout"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg>
          </a>
        </div>
      </div>
    </div>
  </nav>
</header>

<?php include('./components/user_cart.php'); ?>

<style>
  /* Glass Effect */
  .header-glass {
    background: rgba(255, 253, 250, 0.2);
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.3s ease-in-out;
  }

  .text-black { color: black; }
  .text-white { color: white; }

  /* Animation for X icon hover */
  .close-icon, .burger-icon { transition: transform 0.3s ease; }
  .close-icon:hover { transform: rotate(90deg); }

  /* Transition for Burger to X Icon */
  .burger-icon, .close-icon { transition: all 0.3s ease-in-out; }
</style>

<script>
  // Check if we are on the index page
  const isIndexPage = window.location.pathname === '/' || window.location.pathname.includes('index');

  // Manage scroll behavior and header classes
  let isScrolled = false;
  const header = document.getElementById('header');
  
  // Set initial text color based on the page
  if (isIndexPage) {
    header.classList.add('text-white');
  }

  window.addEventListener('scroll', function () {
    if (window.scrollY > 10) {
      isScrolled = true;
      header.classList.add('header-glass');
      if (isIndexPage) {
        header.classList.remove('text-white');
        header.classList.add('text-black');
      }
    } else {
      isScrolled = false;
      header.classList.remove('header-glass');
      if (isIndexPage) {
        header.classList.remove('text-black');
        header.classList.add('text-white');
      }
    }
  });

  function toggleMenu() {
    const burgerIcon = document.querySelector('.burger-icon');
    const closeIcon = document.querySelector('.close-icon');
    const menu = document.getElementById('menu');

    // Toggle menu visibility
    menu.classList.toggle('hidden');

    // Switch between the burger and close icon
    burgerIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
  }

  function toggleModals() {
  const signinModal = document.getElementById('hs-modal-signin');
  const signupModal = document.getElementById('hs-modal-signup');

  // Hide sign-in modal and show sign-up modal
  signinModal.classList.add('hidden');
  signupModal.classList.remove('hidden');
}

</script>