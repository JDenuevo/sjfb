<?php
session_start();
include 'conn.php';

date_default_timezone_set('Asia/Manila');

function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

// Verify OTP was successfully verified first
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['email'])) {
    header("Location: forgot_password.php");
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Reset Password
  if (isset($_POST['reset_password'])) {
    if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['email'])) {
        error_log("Session verification failed");
        redirectWithMessage('forgot_password.php', "Session expired");
    }

    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);
    $email = $_SESSION['email'];
    
    // Validate password
    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[\W]/', $password)) {
        error_log("Password validation failed");
        $_SESSION['reset_error'] = "Password must be at least 8 characters with uppercase, number, and special character";
        header("Location: reset_password.php");
        exit();
    }

    if ($password !== $confirm) {
        error_log("Password confirmation failed");
        $_SESSION['reset_error'] = "Passwords don't match!";
        header("Location: reset_password.php");
        exit();
    }

    // Hash the password after validation
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE accounts SET password_hash = ?, reset_otp = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    
    if ($stmt->execute()) {
        error_log("Password update successful for email: $email");
        
        // Clear all session variables
        session_unset();
        session_destroy();
        
        // Start new session for success message
        session_start();
        $_SESSION['success'] = "Password reset successfully! You can now login with your new password.";
        header("Location: index.php");
        exit();
    } else {
        error_log("Password update failed: " . $conn->error);
        $_SESSION['reset_error'] = "Password reset failed. Please try again.";
        header("Location: reset_password.php");
        exit();
    }
  }
}
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
  <title>Reset Password | St. Joseph Fish Brokerage Inc.</title>

  
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>

<style>
   body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

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

  #password-strength-meter {
    transition: width 0.3s ease, background-color 0.3s ease;
  }
</style>

<body>

<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
<section id="home-section">
  <?php include('./components/navigation.php'); ?>

  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="my-8 text-center">
      <h1>Reset Password</h1>
      <p>Enter your new password.</p>
    </div>

    <?php
    if (!empty($_SESSION['message'])) {
      $message = $_SESSION['message'];
      $alertType = ($message['type'] === 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700';

      echo '
      <div class="' . $alertType . ' px-4 py-3 rounded mb-4">
        <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
      </div>';
      unset($_SESSION['message']);
    }

    if (isset($_SESSION['reset_error'])) {
      echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">'
          . $_SESSION['reset_error'] 
          . '</div>';
      unset($_SESSION['reset_error']);
    }
    ?>
    
    <form method="POST" action="reset_password.php" id="password-reset-form">
      <!-- Form Group -->
      <div>
        <label for="Password" class="block text-sm mb-2 text-dark">Password</label>
        <div class="password-input-container">
          <input type="password" id="Password" name="password" class="password-input border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
          <button type="button" class="password-toggle-button" onclick="togglePassword('Password', this)" aria-label="Toggle password visibility" aria-pressed="false">
              <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
              </svg>
          </button>
        </div>
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

      <div>
        <label for="ConfirmPassword" class="block text-sm mb-2 text-dark">Confirm Password</label>
        <div class="password-input-container">
          <input type="password" id="ConfirmPassword" name="confirm_password" class="password-input border border-black py-3 px-4 block w-full rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
          <button type="button" class="password-toggle-button" onclick="togglePassword('ConfirmPassword', this)" aria-label="Toggle password visibility" aria-pressed="false">
              <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
              </svg>
          </button>
        </div>
        <p class="hidden text-xs text-red-600 mt-2" id="confirm-password-error">Passwords do not match</p>
      </div>
      
      <button type="submit" name="reset_password" class="w-full my-10 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none disabled:opacity-50 disabled:pointer-events-none">Reset Password</button>

    </form>
  </div>
</section>

<?php include('./components/footer.php'); ?>

<script>
  function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    button.setAttribute('aria-pressed', !isPassword);

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

  const form = document.querySelector('form'); // Adjust if your form has an ID
  const passwordInput = document.getElementById('Password');
  const confirmInput = document.getElementById('ConfirmPassword');
  const passwordError = document.getElementById('password-error');
  const confirmError = document.getElementById('confirm-password-error');

  const lengthRequirement = document.getElementById('password-length');
  const uppercaseRequirement = document.getElementById('password-uppercase');
  const numberRequirement = document.getElementById('password-number');
  const specialRequirement = document.getElementById('password-special');

  function validatePassword(password) {
    const validations = {
      length: password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[\W_]/.test(password)
    };
    return validations;
  }

  function toggleClass(el, valid) {
    el.classList.toggle('valid', valid);
  }

  passwordInput.addEventListener('input', function () {
    const password = passwordInput.value;
    const validations = validatePassword(password);

    toggleClass(lengthRequirement, validations.length);
    toggleClass(uppercaseRequirement, validations.uppercase);
    toggleClass(numberRequirement, validations.number);
    toggleClass(specialRequirement, validations.special);

    const allValid = Object.values(validations).every(Boolean);
    passwordInput.classList.toggle('error-border', !allValid);
    passwordInput.classList.toggle('success-border', allValid);

    if (!allValid) {
      passwordError.classList.remove('hidden');
    } else {
      passwordError.classList.add('hidden');
    }
  });

  // Confirm password real-time check
  if (confirmInput) {
    confirmInput.addEventListener('input', function () {
      const match = passwordInput.value === confirmInput.value;
      confirmInput.classList.toggle('error-border', !match);
      confirmInput.classList.toggle('success-border', match);
      confirmError.classList.toggle('hidden', match);
    });
  }

  // Final check on form submit
  if (form) {
    form.addEventListener('submit', function (e) {
      const match = passwordInput.value === confirmInput.value;
      if (!match) {
        e.preventDefault(); // Stop form submission
        confirmInput.classList.add('error-border');
        confirmError.classList.remove('hidden');
      }
    });
  }
</script>


<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>
      