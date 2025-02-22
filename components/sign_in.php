<?php
include './conn.php';
   

include './checker.php';

?>
<!-- Modal -->
<div id="hs-modal-signin" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
  <div id="signin-white-bg" class="bg-white rounded-lg shadow-lg p-6 relative">
    <!-- Close Button -->
    <div class="text-end">
      <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-modal-signin">
        <span class="sr-only">Close</span>
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18"></path>
        <path d="m6 6 12 12"></path>
        </svg>
      </button>
    </div>
    <!-- Modal Content -->
    <div class="mt-5">
      <div class="mb-6 text-center">
        <h3 class="text-xl font-bold text-gray-900">Sign in</h3>
        <p class="text-gray-500">Log in to your account.</p>
      </div>

      <!-- Form -->
      <form action="../functions/checker.php" method="POST">
        <div class="grid gap-y-4">
          <!-- Email Field -->
          <div>
            <label for="email" class="block mb-2 text-sm text-gray-700 font-medium">Email</label>
            <input type="email" name="email" id="email" placeholder="Email" autocomplete="email" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
          </div>

          <!-- Password Field -->
          <div>
            <div class="flex justify-between items-center">
              <label for="password" class="block text-sm mb-2">Password</label>
            </div>
            <input type="password" id="password" name="password" placeholder="Password" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
          </div>

          <!-- Sign In Button -->
          <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">Sign In</button>  
        </div>
      </form>
    </div>
    <div class="mt-5 text-center">
      Don't have an account? <a href="javascript:void(0);" onclick="toggleModals()">Sign up here</a>
    </div>
  </div>
</div>

<style>
    
  #signin-white-bg {
    width: 400px;
    transition: transform 0.3s ease-in-out;
  }

</style>
<script>
  function openModal() {
    document.getElementById('hs-modal-signin').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('hs-modal-signin').classList.add('hidden');
  }
</script>
