<?php
session_start();
include 'conn.php';

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
<?php include('./components/preloader.php'); ?>
<!-- Our Story Section -->

<section id="order-success-section" class="flex-grow">
  <?php include('./components/navigation.php'); ?>

  <!-- Success Message Section -->
  <div class="h-auto container mx-auto p-8 flex flex-col items-center justify-center text-center" data-aos="fade-up">
  <svg xmlns="http://www.w3.org/2000/svg" style="color: #22c55e;" class="w-20 h-20 text-green-500 mb-4 bg-slate-400 rounded-full" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5" /></svg>

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md">
      <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
      <p class="text-gray-500 mb-6">Thank you for shopping with us. Your order has been confirmed. We'll call you to verify details before shipping.</p>
    </div>
    <a href="index.php" class="bg-orange-600 mt-10 text-white py-3 px-6 rounded-lg hover:bg-orange-700 transition">Go Back to Home</a>

  </div>

  <?php include('./components/footer.php'); ?>
</section>


  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <?php include('live_chat.php'); ?>
  
</body>
</html>
