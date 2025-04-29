<?php
session_start();
include 'conn.php';

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<!-- End Google Tag Manager -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link href="./style.css" rel="stylesheet">
  <link href="./output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<style>
  .password-input-container {
    position: relative;
  }

  .password-toggle-button {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 10px;
    display: flex;
    align-items: center;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
  }

  .password-input {
    padding-right: 2.5rem;
  }

  .error-border {
    border-color: #ef4444 !important;
  }

  .success-border {
    border-color: #10b981 !important;
  }

  .requirement-list {
    margin-top: 0.5rem;
    padding-left: 1rem;
    list-style-type: none;
  }

  .requirement-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
  }

  .requirement-item.valid {
    color: #10b981;
  }

  .requirement-icon {
    margin-right: 0.5rem;
    width: 1rem;
    height: 1rem;
  }

  .requirement-icon.valid {
    color: #10b981;
  }
</style>
<body>

<!-- Register Section -->
<section id="register-section">
    
    <?php include('./components/navigation.php'); ?>

    <!-- HTML + TailwindCSS + JavaScript -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
      <div class="max-w-xl mx-auto shadow-2xl p-4" data-aos="fade-up" data-aos-duration="1500">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Create Your Account</h1>
          <p class="mt-1 text-gray-600">Join us and experience the freshness of our seafood! </p>
        </div>
        <div class="mt-5">  
          <?php
          if (!empty($_SESSION['success']) || !empty($_SESSION['error'])) {
              $messageText = !empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error'];
              $messageType = !empty($_SESSION['success']) ? 'success' : 'error';
              $alertType = ($messageType === 'success') ? 'bg-teal-500 text-green' : 'bg-red-500 text-red';

              echo '
              <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-lg p-4 text-center text-red-500" role="alert">
                  <span class="font-bold">' . ucfirst(htmlspecialchars($messageType)) . '!</span> ' . htmlspecialchars($messageText) . '
              </div>';

              // Unset messages after displaying
              unset($_SESSION['success'], $_SESSION['error']);
          }
          ?>
          <!-- Form -->
          <form action="./functions/add.php" method="POST" id="registerForm">
            <div class="grid gap-y-4">
              <!-- Form Group -->
              <div>
                <label for="Email" class="block text-sm mb-2 text-dark">Email address</label>
                <div class="relative">
                  <input type="email" id="Email" name="email" class="border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you.</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="Username" class="block text-sm mb-2 text-dark">Username</label>
                <div class="relative">
                  <input type="text" id="Username" name="username" class="border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <ul class="requirement-list" id="username-requirements">
                  <li class="requirement-item" id="username-length">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 5 characters
                  </li>
                  <li class="requirement-item" id="username-chars">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Only letters, numbers, and underscores
                  </li>
                </ul>
                <p class="hidden text-xs text-red-600 mt-2" id="username-error">Username must contain only letters, numbers.</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="Password" class="block text-sm mb-2 text-dark">Password</label>
                <div class="password-input-container">
                  <input type="password" id="Password" name="password" class="password-input border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <button type="button" class="password-toggle-button" onclick="togglePassword('Password', this)">
                    <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                    </svg>
                  </button>
                </div>
                <ul class="requirement-list" id="password-requirements">
                  <li class="requirement-item" id="password-length">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 8 characters
                  </li>
                  <li class="requirement-item" id="password-uppercase">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 1 uppercase letter
                  </li>
                  <li class="requirement-item" id="password-number">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 1 number
                  </li>
                  <li class="requirement-item" id="password-special">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 1 special character
                  </li>
                </ul>
                <p class="hidden text-xs text-red-600 mt-2" id="password-error">Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character!</p>
              </div>
              <!-- End Form Group -->

              <!-- Confirm Password -->
              <div>
                <label for="Confirm_password" class="block text-sm mb-2 text-dark">Confirm Password</label>
                <div class="password-input-container">
                  <input type="password" id="Confirm_password" name="confirm_password" class="password-input border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <button type="button" class="password-toggle-button" onclick="togglePassword('Confirm_password', this)">
                    <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                    </svg>
                  </button>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="confirm-password-error">Passwords do not match</p>
              </div>
              <!-- End Confirm Password -->

              <button type="submit" name="register_account" class="w-full my-10 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none disabled:opacity-50 disabled:pointer-events-none">Sign up</button>
            </div>
          </form>
          <!-- End Form -->
        </div>
      </div>
    </div>

  </section>
  
  <?php include('./components/footer.php'); ?>
  
  <script>
    function togglePassword(inputId, button) {
      const input = document.getElementById(inputId);
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      
      // Toggle the eye icon
      const eyeIcon = button.querySelector('svg');
      if (isPassword) {
        eyeIcon.innerHTML = `
          <path d="M13.875 18.825a12.042 12.042 0 0 1-9.9-6.825 12.042 12.042 0 0 1 1.975-3.175m2.225-2.225a12.042 12.042 0 0 1 6.825-1.975M19.125 18.825A12.042 12.042 0 0 0 21 12a12.042 12.042 0 0 0-1.975-3.175M9 15l6-6" />
        `;
      } else {
        eyeIcon.innerHTML = `
          <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
          <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
        `;
      }
    }

    document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById("registerForm");
      const emailInput = document.getElementById("Email");
      const usernameInput = document.getElementById("Username");
      const passwordInput = document.getElementById("Password");
      const confirmPasswordInput = document.getElementById("Confirm_password");
      
      const emailError = document.getElementById("email-error");
      const usernameError = document.getElementById("username-error");
      const passwordError = document.getElementById("password-error");
      const confirmPasswordError = document.getElementById("confirm-password-error");

      // Username requirements
      const usernameLengthReq = document.getElementById("username-length");
      const usernameCharsReq = document.getElementById("username-chars");

      // Password requirements
      const passwordLengthReq = document.getElementById("password-length");
      const passwordUppercaseReq = document.getElementById("password-uppercase");
      const passwordNumberReq = document.getElementById("password-number");
      const passwordSpecialReq = document.getElementById("password-special");

      function validateEmail() {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(email)) {
          emailInput.classList.add("error-border");
          emailInput.classList.remove("success-border");
          emailError.classList.remove("hidden");
          return false;
        } else {
          emailInput.classList.remove("error-border");
          emailInput.classList.add("success-border");
          emailError.classList.add("hidden");
          return true;
        }
      }

      function validateUsername() {
        const username = usernameInput.value.trim();
        const usernameRegex = /^[a-zA-Z0-9_]{5,}$/;
        let isValid = true;

        // Check length requirement
        if (username.length >= 5) {
          usernameLengthReq.classList.add("valid");
          usernameLengthReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          usernameLengthReq.classList.remove("valid");
          usernameLengthReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check character requirement
        if (/^[a-zA-Z0-9_]+$/.test(username)) {
          usernameCharsReq.classList.add("valid");
          usernameCharsReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          usernameCharsReq.classList.remove("valid");
          usernameCharsReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        if (!usernameRegex.test(username)) {
          usernameInput.classList.add("error-border");
          usernameInput.classList.remove("success-border");
          usernameError.classList.remove("hidden");
          return false;
        } else {
          usernameInput.classList.remove("error-border");
          usernameInput.classList.add("success-border");
          usernameError.classList.add("hidden");
          return true;
        }
      }

      function validatePassword() {
        const password = passwordInput.value;
        let isValid = true;

        // Check length requirement
        if (password.length >= 8) {
          passwordLengthReq.classList.add("valid");
          passwordLengthReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          passwordLengthReq.classList.remove("valid");
          passwordLengthReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check uppercase requirement
        if (/[A-Z]/.test(password)) {
          passwordUppercaseReq.classList.add("valid");
          passwordUppercaseReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          passwordUppercaseReq.classList.remove("valid");
          passwordUppercaseReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check number requirement
        if (/\d/.test(password)) {
          passwordNumberReq.classList.add("valid");
          passwordNumberReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          passwordNumberReq.classList.remove("valid");
          passwordNumberReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check special character requirement
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
          passwordSpecialReq.classList.add("valid");
          passwordSpecialReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          passwordSpecialReq.classList.remove("valid");
          passwordSpecialReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        if (!isValid) {
          passwordInput.classList.add("error-border");
          passwordInput.classList.remove("success-border");
          passwordError.classList.remove("hidden");
          return false;
        } else {
          passwordInput.classList.remove("error-border");
          passwordInput.classList.add("success-border");
          passwordError.classList.add("hidden");
          return true;
        }
      }

      function validateConfirmPassword() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword || confirmPassword === "") {
          confirmPasswordInput.classList.add("error-border");
          confirmPasswordInput.classList.remove("success-border");
          confirmPasswordError.classList.remove("hidden");
          return false;
        } else {
          confirmPasswordInput.classList.remove("error-border");
          confirmPasswordInput.classList.add("success-border");
          confirmPasswordError.classList.add("hidden");
          return true;
        }
      }

      // Validate on input change
      emailInput.addEventListener("input", validateEmail);
      usernameInput.addEventListener("input", validateUsername);
      passwordInput.addEventListener("input", function() {
        validatePassword();
        // Also validate confirm password when password changes
        if (confirmPasswordInput.value.length > 0) {
          validateConfirmPassword();
        }
      });
      confirmPasswordInput.addEventListener("input", validateConfirmPassword);

      // Form submission validation
      form.addEventListener("submit", function (event) {
        let isValid = true;
        
        if (!validateEmail()) isValid = false;
        if (!validateUsername()) isValid = false;
        if (!validatePassword()) isValid = false;
        if (!validateConfirmPassword()) isValid = false;
        
        if (!isValid) {
          event.preventDefault();
          // Scroll to the first error
          const firstError = document.querySelector(".error-border");
          if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      });
    });
  </script>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>