<?php
session_start();
include 'conn.php';

$pageTitle = 'Shop';

$showCategories = true; // Show desktop categories
$showMobileCategories = true; // Show mobile categories

// Fetch all products with their primary image and variants (including new fields)
$query = "SELECT p.*,  
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
<html lang="en" dir="ltr" class="scroll-smooth"> 

<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  
  <title>Fresh Seafood Shop in the Philippines | St. Joseph Fish Brokerage Inc.</title>
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

<body id="content">

  <?php include './components/navigation.php'; ?>

  <?php include './components/nav_crumb.php'; ?>

  <?php include './components/preloaders.php'; ?>

  <!-- Products -->
  <div class="px-4 sm:px-6 lg:px-8 mx-auto">
    <!-- Title -->
    <div class="max-w-7xl mx-auto text-center my-10 lg:my-14">
      <h1 class="text-4xl font-bold md:text-3xl md:leading-tight text-foreground">Fresh Seafood Shop in the Philippines</h1>
      <p class="mt-5 text-muted-foreground-2">    
        Explore our wide selection of fresh, high-quality seafood sourced directly from trusted fishermen and suppliers across the Philippines. From fresh bangus and tilapia to expertly prepared tinapang bangus, we offer reliable quality for households, restaurants, and wholesale buyers.
      </p>
    </div>
    <!-- End Title -->

    <?php include('./components/products.php'); ?>

  </div>
  <!-- Hero -->
  <div class="relative overflow-hidden mt-10 shadow-lg">
  
  <div class="absolute"></div>
  <!-- Image - ADD z-index: 0 -->
  <img src="./assets/images/contents/membership.png"
    alt="St. Joseph Fish Brokerage Navotas Fish Port Complex" 
    loading="eager" 
    class="absolute w-full h-auto object-cover z-0">
  
    <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8 py-24 space-y-8 relative z-20">
      <!-- Announcement Banner -->
      <div class="flex justify-center">
        <a class="group inline-flex items-center bg-blue-600 border border-plain/10 p-1 ps-4 rounded-full shadow-md focus:outline-hidden focus:bg-plain/10" href="./register.php" title="Enjoy exciting discounts and affordable Fresh Fishes">
          <p class="me-2 text-white text-sm">
            Enjoy discounts up to 10% and free shipping.         
          </p>
          <span class="group-hover:bg-plain/10 py-1.5 px-2.5 flex justify-center items-center gap-x-2 rounded-full bg-plain/10 font-semibold text-foreground-inverse text-sm">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
          </span>
        </a>
      </div>
      <!-- End Announcement Banner -->

      <!-- Title -->
      <div class="max-w-3xl text-center mx-auto">
        <h1 class="block font-medium text-4xl sm:text-5xl md:text-6xl lg:text-7xl">
          Become a Member. Save More on Every Catch.
        </h1>
      </div>
      <!-- End Title -->

      <div class="max-w-3xl text-center mx-auto">
        <p class="text-lg">Register today to unlock exclusive member-only discounts, faster checkout, and easy order management for all your fresh seafood needs.</p>
      </div>

      <!-- Buttons -->
      <div class="text-center">
        <a class="inline-flex justify-center items-center gap-x-3 text-center text-white bg-orange-600 hover:bg-orange-700 text-sm font-medium rounded-full py-3 px-6" href="./register.php" title="Join St. Joseph Fish Brokerage Inc. – Member Discounts and Easy Seafood Ordering">
          Register Now & Start Saving
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>
      <!-- End Buttons -->
    </div>
  </div>
  <!-- End Hero -->

  <?php include('./components/footer.php'); ?>

  <!-- Required plugins -->
  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>
  <script src="./assets/main.js"></script>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

</body>
</html>