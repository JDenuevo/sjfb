<!-- Modal -->
<div id="hs-modal-signup" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
  <div id="signup-white-bg" class="bg-white rounded-lg shadow-lg p-6 relative">
    <!-- Close Button -->
    <div class="text-end">
      <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#hs-modal-signup">
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
        <h3 class="text-xl font-bold text-gray-900">Sign up</h3>
        <p class="text-gray-500">Create your new account.</p>
      </div>

      <!-- Form -->
      <form>
        <div class="grid gap-y-4">
          <!-- Form Group -->
          <div>
            <label for="email" class="block text-sm mb-2 dark:text-white">Email address</label>
            <div class="relative">
              <input type="email" id="email" name="email" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
          </div>

          <!-- Form Group -->
          <div>
            <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
          </div>

          <!-- Form Group -->
          <div>
            <label for="confirm-password" class="block text-sm mb-2 dark:text-white">Confirm Password</label>
            <div class="relative">
              <input type="password" id="confirm-password" name="confirm-password" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
          </div>

          <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">Sign Up</button>  
        </div>
      </form>
    </div>
  </div>
</div>

<style>
    
  #signup-white-bg {
    width: 400px;
    transition: transform 0.3s ease-in-out;
  }

</style>
<script>
  function openModal() {
    document.getElementById('hs-modal-signup').classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('hs-modal-signup').classList.add('hidden');
  }
</script>
