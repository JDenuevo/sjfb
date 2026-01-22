<?php
session_start();
include 'conn.php';

// Fetch all products with their primary image and variants (including new fields)
$query = "SELECT p.product_id, p.product_name, p.product_description, 
            pi.image_path, 
            v.variant_id, v.variant_name, v.variant_price, v.discount_price,
            v.unit_type, v.minimum_order, v.order_increment
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN product_variants v ON p.product_id = v.product_id
    WHERE v.stock_status = 'In Stock' AND p.is_deleted = 0
    ORDER BY p.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  
  <title>St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. - Providing professional fish brokerage services with excellence and integrity.">

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

  <?php include './components/navigation1.php'; ?>

  <?php include './components/nav_crumb.php'; ?>
  
  <!-- Hero -->
  <div class="px-4 sm:px-6 lg:px-8 py-2">
    <div
      class="relative flex items-end h-[60dvh] md:h-[80dvh] bg-cover bg-bottom bg-no-repeat rounded-2xl"
      style="background-image: url('./assets/images/herobanner.png');"
    >
      <!-- Overlay (optional but recommended) -->
      <div class="absolute inset-0 bg-black/30 rounded-2xl"></div>

      <!-- Content -->
      <div class="relative w-2/3 md:max-w-lg ps-5 pb-5 md:ps-10 md:pb-10">
        <h1 class="text-xl md:text-3xl lg:text-5xl font-semibold text-white">
          Sariwang Araw!
        </h1>
      </div>
    </div>
  </div>
  <!-- End Hero -->


  <!-- Products -->
  <div class="max-w-7xl px-4 sm:px-6 lg:px-8 py-12 mx-auto">
    <?php include('./components/products1.php'); ?>
  </div>

  <div class="my-10 text-center">
    <a class="relative inline-block font-medium md:text-lg text-black before:absolute before:bottom-0.5 before:start-0 before:-z-1 before:w-full before:h-1 before:bg-yellow-400 hover:before:bg-black focus:outline-hidden focus:before:bg-black dark:text-white dark:hover:before:bg-white dark:focus:before:bg-white" href="#">
      View all Products
    </a>
  </div>
  
  
  <!-- JS Implementing Plugins -->

  <!-- JS PLUGINS -->
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/index.js"></script>

  <!-- JS THIRD PARTY PLUGINS -->
  <!-- Google Analytics. Global site tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-B73TDMXKF5"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
  
    function gtag() {
      dataLayer.push(arguments);
    }
  
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');
  </script>
</body>
</html>