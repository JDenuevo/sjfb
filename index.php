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

  <?php include './components/navigation.php'; ?>

  <?php include './components/nav_crumb.php'; ?>
  
  <!-- Hero -->
  <div class="px-4 sm:px-6 lg:px-8 py-2">
    <div class="relative overflow-hidden rounded-2xl min-h-[420px] sm:min-h-[520px] md:h-[80dvh]">
      
      <!-- Image - ADD z-index: 0 -->
      <img src="./assets/images/herobanner.png" 
        alt="St. Joseph Fish Brokerage Navotas Fish Port Complex" 
        loading="eager" 
        class="absolute bottom-0 left-0 w-full h-auto min-h-full object-cover z-0">
      
      <!-- Overlay - STRONGER with z-index: 1 -->
      <div class="overlay absolute inset-0 z-10"></div>
      
      <!-- Content - HIGHER z-index -->
      <div class="relative z-20 h-full flex flex-col justify-center items-center text-center px-4">
        <div class="max-w-4xl mx-auto">
          <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-4 sm:mb-6">
            Fresh Catch <span class="text-orange-400">Daily</span>
          </h1>
          <p class="text-lg sm:text-xl md:text-2xl text-white/90 mb-8 sm:mb-12 max-w-2xl mx-auto">
            Premium seafood straight from Navotas Fish Port Complex
          </p>
          
          <!-- Optional CTA Button -->
          <a href="#shop-products" 
            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold mt-2 py-3 px-8 rounded-full transition-all duration-300 transform hover:scale-105">
            <span>Shop Now</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14"></path>
              <path d="m12 5 7 7-7 7"></path>
            </svg>
          </a>
        </div>

        <!-- This is your mouse/scroll indicator -->
        <a href="#shop-products" class="absolute bottom-4 md:bottom-6 lg:bottom-8 flex flex-col items-center gap-1 cursor-pointer animate-bounce" title="Scroll to shop?">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
            viewBox="0 0 24 24" fill="none" stroke="white"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M6 7a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v10a4 4 0 0 1 -4 4h-4a4 4 0 0 1 -4 -4z" />
            <path d="M12 7v4" />
          </svg>
          <span class="text-white text-sm">Scroll to explore</span>
        </a>
      
      </div>
      
    </div>
  </div>
  <!-- End Hero -->

  <!-- Products -->
  <div class="max-w-7xl px-4 sm:px-6 lg:px-8 mx-auto" id="shop-products">
    <?php include('./components/products.php'); ?>
  </div>

  

  <!-- JS PLUGINS -->
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

  <script>
    window.dataLayer = window.dataLayer || [];
  
    function gtag() {
      dataLayer.push(arguments);
    }
  
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');
  </script>

  
<style>
.overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.8) 0%,           /* Darker at bottom */
    rgba(0, 0, 0, 0.5) 40%,          /* Medium darkness */
    rgba(0, 0, 0, 0.2) 70%,          /* Lighter */
    transparent 100%                  /* Transparent at top */
  );
  z-index: 10; /* Higher than image */
}

/* Alternative darker overlay */
.overlay-dark {
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.85),
    rgba(0, 0, 0, 0.5),
    rgba(0, 0, 0, 0.2)
  );
}

/* Simple overlay if gradient isn't working */
.overlay-simple {
  background: rgba(0, 0, 0, 0.4); /* Semi-transparent black */
}
</style>
</body>
</html>