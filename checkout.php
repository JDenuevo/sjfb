<?php
session_start();
include 'conn.php';

// Fetch all products with their primary image and variants
$productQuery = "SELECT p.product_id, p.product_name, p.product_description, 
                 pi.image_path, 
                 v.variant_id, v.variant_name, v.variant_price, v.discount_price
          FROM products p
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN product_variants v ON p.product_id = v.product_id
          ORDER BY p.created_at DESC";

$productResult = $conn->query($productQuery);

if (!$productResult) {
    die("Error fetching products: " . $conn->error);
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | St. Joseph Fish Brokerage Inc.</title>
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
<?php include('./components/preloaders.php'); ?>
<!-- Our Story Section -->
<section id="checkout-section">
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
    <div class="my-10">
        <?php include('./components/to_checkout.php'); ?>
    </div>
    
</section>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
  
  <?php include('live_chat.php'); ?>
  
</body>
</html>

