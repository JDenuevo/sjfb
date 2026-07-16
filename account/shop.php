<?php
session_start();
include '../conn.php';

$pageTitle = 'Shop';

$showCategories = true;
$showMobileCategories = true;

// Auto-login check
if (!isset($_SESSION['account_id'])) {
    require_once '../../functions/remember.php';
    validateRememberToken($conn);
}

// Check login
if (!isset($_SESSION['loggedinasuser']) || $_SESSION['loggedinasuser'] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$account_id = $_SESSION['account_id'];

// Products Query
$productsQuery = "SELECT p.*,
            pi.image_path, 
            v.variant_id, v.variant_name, v.variant_price, v.discount_price,
            v.unit_type, v.minimum_order, v.order_increment, v.stock_quantity,
            v.stock_status, v.is_deleted,
            c.category_name
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0
    LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
    LEFT JOIN product_categories c ON pcl.category_id = c.category_id
    WHERE p.is_deleted = 0
    ORDER BY p.created_at DESC";

$productsResult = $conn->query($productsQuery);

// ✅ BEST PRACTICE: Define baseUrl ONCE at the top (project root)
// This was missing — it's why images break on a plain page load but work
// once fetch_products.php runs (that endpoint sets its own $baseUrl).
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
         . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

// ✅ Image helper function
if (!function_exists('img_url')) {
    function img_url($image_path) {
        global $baseUrl;
        if (!empty($image_path)) {
            return $baseUrl . 'uploads/products/' . ltrim($image_path, '/');
        }
        return $baseUrl . 'uploads/products/default.png';
    }
}
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
  <meta name="description" content="Buy fresh seafood online in the Philippines. St. Joseph Fish Brokerage Inc. delivers premium bangus, tilapia, and more directly from Filipino fishermen to your door.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/shop">
  <meta property="og:title" content="Fresh Seafood Shop | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Buy fresh seafood online — bangus, tilapia, tinapa & more. Sourced directly from Filipino fishermen. Wholesale & retail. Nationwide delivery.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Fresh Seafood Shop | St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Buy fresh seafood online — sourced directly from Filipino fishermen. Nationwide delivery.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Store",
    "name": "St. Joseph Fish Brokerage Inc. – Seafood Shop",
    "url": "https://fishbrokers.net/shop.php",
    "description": "Buy fresh, premium seafood online in the Philippines. Wholesale and retail orders welcome.",
    "areaServed": "Philippines",
    "telephone": "(+63) 946-497-3689",
    "priceRange": "₱₱",
    "hasMap": "https://maps.app.goo.gl/navotas"
  }
  </script>

  <link rel="shortcut icon" href="../assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="../assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="../assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="../assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <!-- ✅ UNIFIED CART CORE — must load before cart.php / products.php -->
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>
  
  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>

<style>
  :root {
    --grad-orange: linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%);
  }
  body { font-family: 'Lexend', sans-serif; }
  .ff-display { font-family: 'Playfair Display', Georgia, serif; }

  /* Gradient text */
  .text-grad {
    background: var(--grad-orange);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  /* Gradient bg */
  .bg-grad { background: var(--grad-orange); }

  /* Dot-grid texture */
  .dot-grid::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(249,115,22,.05) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none;
    z-index: 0;
  }

  /* Pulsing dot */
  @keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(.7); }
  }
  .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

  /* Floating badge */
  @keyframes float-y {
    0%,100% { transform:translateY(0); }
    50%      { transform:translateY(-6px); }
  }
  .float-anim { animation: float-y 3.5s ease-in-out infinite; }

  /* Trust badge row scroll on mobile */
  .trust-scroll { overflow-x: auto; scrollbar-width: none; }
  .trust-scroll::-webkit-scrollbar { display: none; }
</style>

<body id="content">
<?php include('../components/preloaders.php'); ?>

  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>

  <!-- ═══════════════════════════════════════
       PRODUCTS GRID
  ═══════════════════════════════════════ -->
  <div id="products" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-14 mb-10">

    <!-- Section label -->
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8" data-aos="fade-up">
      <div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.68rem] font-bold uppercase tracking-widest mb-3">
          <span class="pulse-dot w-1.5 h-1.5 rounded-full bg-orange-500 shrink-0"></span>
          Today's Selection
        </div>
        <h2 class="ff-display text-2xl lg:text-3xl font-bold text-slate-900 leading-tight">
          Fresh Seafood <span class="text-grad">Available Now</span>
        </h2>
        <p class="text-slate-400 text-sm mt-1">Inventory updated daily from Navotas Fish Port Complex</p>
      </div>
      <!-- Freshness badge -->
      <div class="flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-200 rounded-xl text-sm font-semibold text-green-700">
        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></span>
        Live Inventory — Updated Today
      </div>
    </div>

    <?php
      $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
              . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
    ?>

    <?php include('./components/products.php'); ?>

  </div>
  <!-- /products -->

  <?php include('../components/footer.php'); ?>
  <?php include('../live_chat.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true, easing: 'ease-out-cubic' });</script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <!-- ✅ products_process.js handles all product card JS (variant selection, add-to-cart, search, share) -->
  <script src="./functions/product_process.js"></script>

</body>
</html>