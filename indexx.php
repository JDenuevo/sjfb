<?php
session_start();
include 'conn.php';

// Auto-login if remember me token exists and user is not logged in
if (!isset($_SESSION['account_id'])) {
    require_once 'functions/remember.php';
    
    if (validateRememberToken($conn)) {
        // User was auto-logged in, redirect based on role
        if (isset($_SESSION['role'])) {
            $baseUrl = '/sjfbi-js/';
            switch ($_SESSION['role']) {
                case 'customer':
                    header("Location: {$baseUrl}user/products.php");
                    exit();
                case 'admin':
                    header("Location: {$baseUrl}admin/dashboard.php");
                    exit();
                case 'super_admin':
                    header("Location: {$baseUrl}supadmin/dashboard.php");
                    exit();
                case 'rider':
                    header("Location: {$baseUrl}rider/dashboard.php");
                    exit();
            }
        }
    }
}

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
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<body>

<style>
  /* Fade Out Animation */
.fade-out {
  opacity: 0;
  transition: opacity 1s ease-in-out;
}

/* Fade In Animation */
.fade-in {
  opacity: 1;
  transition: opacity 1s ease-in-out;
}

</style>
<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
  <section id="home-section">
    
    <?php include('./components/navigation.php'); ?>
  
    <div class="overflow-hidden shadow-lg pb-5" id="bottom-page">
      <?php include('./components/products.php'); ?>
    </div>

  </section>
  
  <?php include('./components/footer.php'); ?>

 <script>
  document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('showModal') === 'true') {
          openModal();
          
          // Remove the parameter from URL without reloading
          const newUrl = window.location.pathname + window.location.hash;
          window.history.replaceState({}, document.title, newUrl);
      }
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
