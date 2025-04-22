<?php
session_start();
include 'conn.php';
require_once __DIR__ . '/functions/mail_functions.php';

date_default_timezone_set('Asia/Manila');

// Redirect if email session doesn't exist
if (!isset($_SESSION['email'])) {
    header("Location: forgot_password.php");
    exit();
}

// Handle OTP resend if requested
if (isset($_GET['resend']) && isset($_SESSION['email'])) {
    // Rate limiting - only allow resend every 60 seconds
    if (!isset($_SESSION['last_otp_resend']) || time() - $_SESSION['last_otp_resend'] > 60) {
        require_once __DIR__ . '/functions/update.php';
        $email = $_SESSION['email'];
        
        if (sendOTP($email, $conn)) {
            $_SESSION['last_otp_resend'] = time();
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'New OTP has been sent to your email.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Failed to send new OTP. Please try again.'
            ];
        }
    } else {
        $remaining = 60 - (time() - $_SESSION['last_otp_resend']);
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => "Please wait $remaining seconds before requesting a new OTP."
        ];
    }
    
    header("Location: verify_otp.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<!-- [Rest of your head section remains exactly the same] -->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP | St. Joseph Fish Brokerage Inc.</title>

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
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
<section id="home-section">
    <?php include('./components/navigation.php'); ?>

    <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
      <div class="my-8 text-center">
        <h1>Verify OTP</h1>
        <p>Enter the 6-digit OTP code that has been sent to your email.</p>
      </div>
        
      <?php
        if (!empty($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $alertType = ($message['type'] === 'success') ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700';

          echo '
          <div class="' . $alertType . ' px-4 py-3 rounded mb-4">
            <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
          </div>';

          // Clear message after displaying it
          unset($_SESSION['message']);
        }
      ?>
      
      <form method="POST" action="functions/update.php" class="max-w-md space-y-4">
          <input type="hidden" name="verify_otp" value="1">

          <div class="mb-4">
            <label class="block text-gray-700 mb-2">Enter 6-digit OTP</label>
            <div class="flex gap-x-3" id="otp-container">
                <input type="text" name="otp1" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      oninput="moveToNext(this, 'otp2')" 
                      onkeydown="handleBackspace(this, '')"
                      autocomplete="off" autofocus>
                <input type="text" name="otp2" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      oninput="moveToNext(this, 'otp3')" 
                      onkeydown="handleBackspace(this, 'otp1')"
                      id="otp2" autocomplete="off">
                <input type="text" name="otp3" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      oninput="moveToNext(this, 'otp4')" 
                      onkeydown="handleBackspace(this, 'otp2')"
                      id="otp3" autocomplete="off">
                <input type="text" name="otp4" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      oninput="moveToNext(this, 'otp5')" 
                      onkeydown="handleBackspace(this, 'otp3')"
                      id="otp4" autocomplete="off">
                <input type="text" name="otp5" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      oninput="moveToNext(this, 'otp6')" 
                      onkeydown="handleBackspace(this, 'otp4')"
                      id="otp5" autocomplete="off">
                <input type="text" name="otp6" maxlength="1" pattern="\d"
                      placeholder="⚬" 
                      class="py-2 px-3 text-center w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" 
                      onkeydown="handleBackspace(this, 'otp5')"
                      id="otp6" autocomplete="off">
            </div>
          </div>
          
          <div class="text-center">
            <button type="submit" name="submit_otp" class="w-1/2 py-2 px-3 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">
                Verify OTP
            </button>
            
            <div class="mt-4">
              Didn't receive an OTP code? Click here to
              <a href="verify_otp.php?resend=1" 
                 class="text-blue-500 hover:text-blue-700 hover:underline">
                 Resend OTP
              </a>
            </div>
          </div>
      </form>
      
      <p class="mt-4 text-sm text-gray-600">
          OTP sent to: <?php echo htmlspecialchars($_SESSION['email']); ?>
      </p>
    </div>
</section>

<?php include('./components/footer.php'); ?>

<script>
// Auto-focus first OTP input on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('input[name="otp1"]').focus();
});

// Function to move to next input field
function moveToNext(current, nextFieldId) {
    if (current.value.length >= 1) {
        document.getElementById(nextFieldId)?.focus();
    }
    updateFullOTP();
}

// Function to handle backspace
function handleBackspace(current, prevFieldId) {
    if (event.key === 'Backspace' && current.value.length === 0) {
        if (prevFieldId) {
            document.getElementById(prevFieldId)?.focus();
        }
    }
    updateFullOTP();
}

// Function to combine all OTP digits
function updateFullOTP() {
    let fullOtp = '';
    for (let i = 1; i <= 6; i++) {
        const val = document.querySelector(`input[name="otp${i}"]`).value;
        fullOtp += val;
    }
    document.getElementById("full-otp").value = fullOtp;
}


// Handle paste event for OTP
document.getElementById('otp-container').addEventListener('paste', function(e) {
    e.preventDefault();
    const pasteData = e.clipboardData.getData('text/plain').trim();
    if (pasteData.length === 6 && /^\d+$/.test(pasteData)) {
        const inputs = document.querySelectorAll('input[name^="otp"]');
        inputs.forEach((input, index) => {
            input.value = pasteData[index] || '';
        });
        updateFullOTP();
        document.querySelector('input[name="otp6"]').focus();
    }
});
</script>

<!-- [Rest of your scripts remain the same] -->
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>