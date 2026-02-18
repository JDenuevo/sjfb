<!-- Modal -->
<div id="hs-modal-signin" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
  <div id="signin-white-bg" class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
    <!-- Close Button -->
    <div class="text-end">
      <button type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200" onclick="closeModal()">
        <span class="sr-only">Close</span>
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6 6 18"></path>
          <path d="m6 6 12 12"></path>
        </svg>
      </button>
    </div>
    <!-- Modal Content -->
    <div class="mt-5">
      <div class="mb-5 text-center">
        <h3 class="text-xl font-bold text-gray-900">Sign in</h3>
        <p class="text-gray-500">Log in to your account.</p>
      </div>

      <?php if (!empty($_SESSION['error_message'])): ?>
        <div style="color: white; background-color: #CC3333;" class="my-4 text-sm rounded-lg p-4 text-center" role="alert">
          <span class="font-bold">Invalid!</span> <?= $_SESSION['error_message']; ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>

      <!-- Form with proper autocomplete -->
      <form action="/sjfbi-js/functions/checker.php" method="POST" autocomplete="on" id="signin-form" name="login">
        <div class="grid gap-y-4 mt-5">
          <!-- Username Field -->
          <div>
            <label for="username" class="block mb-2 text-sm text-gray-700 font-medium">Username</label>
            <input type="text" 
                   name="username" 
                   id="username" 
                   placeholder="Username" 
                   autocomplete="username" 
                   class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                   required>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm mb-2">Password</label>
            <div class="password-input-container">
              <input type="password" 
                     id="password" 
                     name="password" 
                     placeholder="Password" 
                     autocomplete="current-password"
                     class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 password-input" 
                     required>

              <button type="button" class="password-toggle-button" onclick="togglePasswordVisibility()" title="Show Password">
                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500 hover:text-gray-700">
                  <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                  <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                </svg>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between mt-4">
            <!-- Remember Me Checkbox -->
            <div class="flex items-center">
              <div class="flex">
                <input id="remember-me" 
                       name="remember-me" 
                       type="checkbox" 
                       class="shrink-0 mt-0.5 border-gray-200 rounded-sm text-orange-600 focus:ring-orange-500">
              </div>
              <div class="ms-3">
                <label for="remember-me" class="text-sm text-gray-600">Remember me</label>
              </div>
            </div>

            <!-- Forgot Password Link -->
            <a href="/sjfbi-js/forgot_password.php" class="text-sm text-gray-600 hover:underline transition duration-200">Forgot password?</a>
          </div>

          <!-- Sign In Button -->
          <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">Sign In</button>
        </div>
      </form>
    </div>
    <div class="mt-5 text-center">
      Don't have an account? <a href="/sjfbi-js/register.php" class="text-orange-600 hover:underline transition duration-200">Sign up here</a>
    </div>
  </div>
</div>

<style>
  #signin-white-bg {
    width: 400px;
    transition: transform 0.3s ease-in-out;
  }

  .password-input-container {
    position: relative;
  }

  .password-toggle-button {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 3px;
    display: flex;
    align-items: center;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    z-index: 10;
  }

  .password-input {
    padding-right: 2.5rem;
  }
</style>

<script>
  function openModal() {
    document.getElementById('hs-modal-signin').classList.remove('hidden');
    
    // Restore remember-me checkbox state from localStorage
    const rememberMe = localStorage.getItem('rememberMeChecked');
    if (rememberMe === 'true') {
      document.getElementById('remember-me').checked = true;
    }
  }

  function closeModal() {
    document.getElementById('hs-modal-signin').classList.add('hidden');
  }

  function togglePasswordVisibility() {
    const passwordField = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      eyeIcon.innerHTML = `<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />`;
    } else {
      passwordField.type = 'password';
      eyeIcon.innerHTML = `<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" />`;
    }
  }

  // Save remember-me checkbox state when form is submitted
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signin-form');
    const rememberCheckbox = document.getElementById('remember-me');
    
    if (form) {
      form.addEventListener('submit', function() {
        // Save checkbox state to localStorage
        localStorage.setItem('rememberMeChecked', rememberCheckbox.checked);
      });
    }
    
    // Restore checkbox state on page load
    const rememberMe = localStorage.getItem('rememberMeChecked');
    if (rememberMe === 'true' && rememberCheckbox) {
      rememberCheckbox.checked = true;
    }
  });

</script>