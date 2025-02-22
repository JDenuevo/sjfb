<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Story | St. Joseph Fish Brokerage Inc.</title>

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
<section id="ourstory-section">
    
    <?php include('./components/navigation.php'); ?>

    <div class="py-10 px-4 sm:px-6 lg:px-8">
        <?php include('./components/history.php'); ?>
    </div>

    <div>
        <?php include('./components/core_values.php'); ?>
    </div>

    <div>
        <?php include('./components/note.php'); ?>
    </div>

    <div class="overflow-hidden shadow-lg pb-5" id="bottom-page">
        <?php include('./components/company_events.php'); ?>
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

