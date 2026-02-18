<?php
session_start();
include 'conn.php';
require_once __DIR__ . '/functions/mail_functions.php';

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
  <title>Forgot Password | St. Joseph Fish Brokerage Inc.</title>

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
<body>

<?php include('./components/preloader.php'); ?>
<!-- Hero Section -->
<section id="home-section">
    <?php include('./components/navigation.php'); ?>

    <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mb-10">
      <div class="my-8 text-center">
        <h1>Forgot Password</h1>
        <p>Enter your email and we’ll send you an OTP code to reset your password.</p>
      </div>
    
      <?php
        // Change this part in forgot_password.php
        if (!empty($_SESSION['error'])) {
          echo '
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
              <span class="font-bold">Error!</span> ' . $_SESSION['error'] . '
          </div>';
          unset($_SESSION['error']);
        }

        if (!empty($_SESSION['success'])) {
          echo '
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
              <span class="font-bold">Success!</span> ' . $_SESSION['success'] . '
          </div>';
          unset($_SESSION['success']);
        }
      ?>
      
      <form method="POST" action="functions/update.php" class="space-y-4">
          <input type="hidden" name="forgot_password" value="1">
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" placeholder="Enter your registered email address.." required class="py-2 px-3 pe-11 w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none">
          </div>
        <div class="text-center">
          
          <button type="submit" name="send_otp" class="w-1/2 py-2 px-3 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Send OTP</button>
        
        </div>
        
      </form>
      
      <p class="mt-4 text-center">Remember your password? <a href="index.php" class="text-orange-500 hover:underline">Login here</a></p>
    </div>
</section>

<?php include('./components/footer.php'); ?>

<!-- [Rest of your scripts remain the same] -->
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>
