<?php
include 'conn.php';

$pageTitle = 'Shop';
$currentPage = '';
$showCategories = false;
$showMobileCategories = false;

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

$productName = isset($_GET['name']) ? urldecode(str_replace('-', ' ', $_GET['name'])) : '';

if (empty($productName)) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

$productsQuery = "SELECT p.*, 
                 pv.variant_id, pv.variant_name, pv.variant_price, pv.discount_price, 
                 pv.unit_type, pv.minimum_order, pv.order_increment, pv.stock_quantity,
                 pv.stock_status, pv.is_deleted,
                 pi.image_path, pi.is_primary,
                 pc.category_id, pc.category_name, pc.category_slug,
                 pc2.category_id as parent_category_id, 
                 pc2.category_name as parent_category_name,
                 pc2.category_slug as parent_category_slug
          FROM products p
          LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0
          LEFT JOIN product_images pi ON p.product_id = pi.product_id
          LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
          LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
          LEFT JOIN product_categories pc2 ON pc.parent_id = pc2.category_id
          WHERE LOWER(REPLACE(p.product_name, ' ', '-')) = LOWER(REPLACE(?, ' ', '-'))
          AND p.is_deleted = 0
          ORDER BY p.product_id, pv.variant_id, pi.is_primary DESC";

$stmt = $conn->prepare($productsQuery);
$stmt->bind_param("s", $productName);
$stmt->execute();
$productsResult = $stmt->get_result();

if ($productsResult->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

$product = null;
$variants = [];
$images = [];
$primaryImage = null;
$categories = [];
$primaryCategory = null;
$hasStock = false;
$totalStock = 0;
$productCategories = [];

while ($row = $productsResult->fetch_assoc()) {
    if ($product === null) {
        $product = [
            'product_id'          => $row['product_id'],
            'product_name'        => $row['product_name'],
            'product_unit'        => $row['product_unit'],
            'product_description' => $row['product_description'],
            'product_nickname'    => $row['product_nickname'],
        ];
        $currentPage = $row['product_name'];
    }
    if ($row['variant_id'] && !isset($variants[$row['variant_id']])) {
        $stockQty  = intval($row['stock_quantity'] ?? 0);
        $vHasStock = $stockQty > 0;
        $variants[$row['variant_id']] = [
            'variant_name'    => $row['variant_name'],
            'variant_price'   => $row['variant_price'],
            'discount_price'  => $row['discount_price'],
            'unit_type'       => $row['unit_type'],
            'minimum_order'   => $row['minimum_order'],
            'order_increment' => $row['order_increment'],
            'stock_quantity'  => $stockQty,
            'has_stock'       => $vHasStock,
        ];
        if ($vHasStock) $hasStock = true;
        $totalStock += $stockQty;
    }
    if ($row['image_path']) {
        $images[$row['image_path']] = $row['image_path'];
        if ($row['is_primary']) $primaryImage = $row['image_path'];
    }
    if ($row['category_id'] && !isset($categories[$row['category_id']])) {
        $categories[$row['category_id']] = [
            'category_id'   => $row['category_id'],
            'category_name' => $row['category_name'],
            'category_slug' => $row['category_slug'],
            'parent_id'     => $row['parent_category_id'],
            'parent_name'   => $row['parent_category_name'],
            'parent_slug'   => $row['parent_category_slug'],
        ];
        $productCategories[] = $row['category_id'];
        if ($primaryCategory === null) $primaryCategory = $categories[$row['category_id']];
    }
}

if (empty($primaryImage) && !empty($images)) $primaryImage = reset($images);
if (empty($images)) { $primaryImage = 'default.png'; $images = ['default.png']; }
else $images = array_values($images);

// Cooking suggestions
$cookingSuggestions = [];
if (!empty($product['product_id'])) {
    $stmtSugg = $conn->prepare("SELECT * FROM product_cooking_suggestions WHERE product_id = ? ORDER BY difficulty_level, dish_name");
    $stmtSugg->bind_param("i", $product['product_id']);
    $stmtSugg->execute();
    $cookingSuggestions = $stmtSugg->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtSugg->close();
}

// Related products
$relatedProducts = [];
if (!empty($productCategories)) {
    $catIds = implode(',', array_unique($productCategories));
    $stmtRel = $conn->prepare("SELECT DISTINCT p.*,
                     (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
                     (SELECT MIN(variant_price) FROM product_variants WHERE product_id = p.product_id) as min_price,
                     (SELECT SUM(stock_quantity > 0) FROM product_variants WHERE product_id = p.product_id) as has_stock
                     FROM products p
                     JOIN product_category_links pcl ON p.product_id = pcl.product_id
                     WHERE pcl.category_id IN ($catIds) AND p.product_id != ? AND p.is_deleted = 0
                     GROUP BY p.product_id ORDER BY has_stock DESC, RAND()");
    $stmtRel->bind_param("i", $product['product_id']);
    $stmtRel->execute();
    $relatedProducts = $stmtRel->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtRel->close();
}

$canonicalUrl  = $baseUrl . 'item/' . strtolower(str_replace(' ', '-', $product['product_name']));
$shareUrlNew   = $canonicalUrl;
$shareTitle    = $product['product_name'];
$shareText     = 'Check out this fresh seafood: ' . $product['product_name'] . ' from St. Joseph Fish Brokerage Inc.';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth"> 
<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>
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

  <link rel="shortcut icon" href="<?= $baseUrl ?>assets/icons/logo.ico">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $baseUrl ?>assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link href="<?= $baseUrl ?>style.css" rel="stylesheet">
  <link href="<?= $baseUrl ?>output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- ✅ UNIFIED CART CORE — provides showToast, refreshCartFromServer, recalcTotals, etc. -->
  <script src="<?= $baseUrl ?>functions/cart_process.js"></script>

  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <style>
    body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }
    .difficulty-easy   { background-color: #10b981; color: white; }
    .difficulty-medium { background-color: #f59e0b; color: white; }
    .difficulty-hard   { background-color: #ef4444; color: white; }
    .suggestion-card, .related-product-card { transition: all 0.3s ease; }
    .suggestion-card:hover, .related-product-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,.1); }
    .step-number { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; background-color:#f59e0b; color:white; border-radius:9999px; font-size:.875rem; font-weight:600; margin-right:.75rem; flex-shrink:0; }
    .variant-button { background-color:white; border:1px solid #d1d5db; color:#374151; transition:all .2s ease; }
    .variant-button.selected-variant { background-color:#f59e0b; border-color:#f59e0b; color:#fff; }
    .variant-button:disabled { opacity:.5; cursor:not-allowed; background-color:#f3f4f6; }
    button[name="add_to_cart"]:disabled { opacity:.5; cursor:not-allowed; }
    .decrease-quantity, .increase-quantity { background-color:white; border:1px solid #d1d5db; color:#374151; transition:all .2s ease; }
    .decrease-quantity:hover, .increase-quantity:hover { background-color:#f59e0b; color:white; border-color:#f97316; }
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
    .add-to-cart-form input.quantity:focus { outline:2px solid #f97316; outline-offset:1px; border-radius:2px; }
  </style>
</head>

<body>
<section id="home-section">
  <?php include('./components/navigation.php'); ?>

  <div class="max-w-7xl px-4 sm:px-6 lg:px-8 mx-auto mt-10">

    <?php include('./components/nav_crumb.php'); ?>

    <!-- Main Product Section -->
    <div class="grid md:grid-cols-3 gap-4 shadow-lg mb-10">
      <!-- Left: Image -->
      <div class="md:col-span-1 p-4 rounded-3xl">
        <div class="flex flex-col items-center">
          <div class="max-w-xl rounded-lg relative">
            <?php if (!$hasStock): ?>
            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg z-10">
              <span class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg transform -rotate-12 shadow-lg border-2 border-white text-base tracking-wide">OUT OF STOCK</span>
            </div>
            <?php endif; ?>
            <img id="mainImage"
                 src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
                 class="rounded-lg shadow-md <?= !$hasStock ? 'opacity-60' : '' ?>"
                 width="250px"
                 alt="<?= htmlspecialchars($product['product_name']) ?>">
          </div>
          <?php if (count($images) > 1): ?>
          <div class="flex justify-center space-x-3 mt-4 overflow-x-auto">
            <?php foreach ($images as $img): ?>
            <img src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($img) ?>"
                 class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:border-gray-500 <?= !$hasStock ? 'opacity-60' : '' ?>"
                 onclick="changeImage('<?= htmlspecialchars($img) ?>')"
                 alt="<?= htmlspecialchars($product['product_name']) ?> Thumbnail">
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Middle: Add to Cart -->
      <div class="md:col-span-1 p-4 rounded-3xl">
        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p class="mt-2 text-gray-600"><?= htmlspecialchars($product['product_unit']) ?></p>

        <?php if ($hasStock): ?>
        <form class="add-to-cart-form" method="POST" action="javascript:void(0)" data-product-id="<?= $product['product_id'] ?>">
          <input type="hidden" name="add_to_cart"     value="1">
          <input type="hidden" name="product_id"      value="<?= $product['product_id'] ?>">
          <input type="hidden" name="variant_id"      value="">
          <input type="hidden" name="product_name"    value="<?= htmlspecialchars($product['product_name']) ?>">
          <input type="hidden" name="variant_name"    value="">
          <input type="hidden" name="price"           value="">
          <input type="hidden" name="image_url"       value="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>">
          <input type="hidden" name="quantity"        value="">
          <input type="hidden" name="unit_type"       value="">
          <input type="hidden" name="minimum_order"   value="">
          <input type="hidden" name="order_increment" value="">

          <?php if (!empty($variants)): ?>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Select Size:</label>
            <div class="flex flex-wrap gap-2">
              <?php 
              $firstInStockId = null;
              foreach ($variants as $vid => $v) { if ($v['has_stock']) { $firstInStockId = $vid; break; } }
              foreach ($variants as $vid => $v):
                $vHasStock   = $v['has_stock'];
                $isSelected  = ($firstInStockId && $vid === $firstInStockId);
                $disabledAttr = $vHasStock ? '' : 'disabled';
                $disabledCls  = $vHasStock ? '' : 'opacity-50 cursor-not-allowed';
              ?>
              <button type="button"
                      class="variant-button px-3 py-2 border rounded-lg text-sm font-medium hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200
                             <?= $isSelected ? 'selected-variant' : '' ?> <?= $disabledCls ?>"
                      data-product-id="<?= $product['product_id'] ?>"
                      data-variant-id="<?= $vid ?>"
                      data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                      data-variant-price="<?= $v['variant_price'] ?>"
                      data-discount-price="<?= $v['discount_price'] ?>"
                      data-unit-type="<?= $v['unit_type'] ?>"
                      data-minimum-order="<?= $v['minimum_order'] ?>"
                      data-order-increment="<?= $v['order_increment'] ?>"
                      data-stock-quantity="<?= $v['stock_quantity'] ?>"
                      data-has-stock="<?= $vHasStock ? 'true' : 'false' ?>"
                      <?= $disabledAttr ?>>
                <?= htmlspecialchars($v['variant_name']) ?>
                <?php if (!$vHasStock): ?><span class="ml-1 text-red-500">(No Stock)</span><?php endif; ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
          <?php else: ?>
          <p class="text-red-500 text-sm mt-2">No variants available for this product.</p>
          <?php endif; ?>

          <!-- Qty Selector -->
          <div class="mt-3">
            <div class="flex items-center">
              <div class="flex items-center border border-gray-300 rounded">
                <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                <input type="number" class="quantity w-14 px-1 py-0.5 text-center text-sm border-0 focus:outline-none" value="" min="" step="">
                <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
              </div>
              &nbsp;
              <span class="ml-2 text-sm font-medium text-gray-600 unit-display"></span>
            </div>
            <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
          </div>

          <div class="price-display mt-3"></div>

          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex gap-2">
              <button type="submit" name="add_to_cart"
                      class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                      title="Add to Cart">
                <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                Add to Cart
              </button>
              <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                      class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 flex items-center justify-center" title="Share on Facebook">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#fbgItem)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="fbgItem" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
              </button>
              <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                      class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 flex items-center justify-center" title="Share">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>
              </button>
            </div>
          </div>

          <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
          <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
          <p class="text-red-500 text-sm mt-2 stock-error-message hidden"></p>
        </form>
        <?php else: ?>
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-600 font-semibold text-center">This product is currently out of stock.</p>
          <p class="text-sm text-gray-600 text-center mt-2">Please check back later or browse other products.</p>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex gap-2">
            <button type="button" class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 text-white font-medium flex items-center justify-center opacity-50 cursor-not-allowed" disabled>
              <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
              Add to Cart
            </button>
            <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')" class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 flex items-center justify-center" title="Share on Facebook">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#fbgItemOos)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="fbgItemOos" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
            </button>
            <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')" class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 flex items-center justify-center" title="Share">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right: Description & Tags -->
      <div class="md:col-span-1 p-4 rounded-3xl">
        <div class="flex flex-col h-full items-start">
          <h2 class="text-xl font-bold text-gray-800">What is <?= htmlspecialchars($product['product_name']) ?>?</h2>
          <p class="text-gray-800 font-semibold mt-2"><?= htmlspecialchars($product['product_description']) ?></p>
          <?php if (!empty($product['product_nickname'])): ?>
          <div class="mt-5">
            <p class="text-gray-600 font-medium mb-2">Tags:</p>
            <div class="flex flex-wrap gap-2">
              <?php
              $nickname = $product['product_nickname'];
              $tags = json_decode($nickname, true);
              if (is_array($tags)) {
                foreach ($tags as $tag) echo '<span class="px-3 py-1 bg-orange-50 text-orange-700 text-xs rounded-full border border-orange-200">#' . htmlspecialchars($tag) . '</span>';
              } else {
                echo '<span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">' . htmlspecialchars($nickname) . '</span>';
              }
              ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- COOKING SUGGESTIONS -->
    <?php if (!empty($cookingSuggestions)): ?>
    <div class="mt-16 mb-16">
      <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Cooking Suggestions</h2>
        <p class="text-gray-600">Delicious ways to prepare your <?= htmlspecialchars($product['product_name']) ?></p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($cookingSuggestions as $suggestion): ?>
        <div class="suggestion-card bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
          <div class="p-6">
            <div class="flex justify-between items-start mb-4">
              <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($suggestion['dish_name']) ?></h3>
              <span class="difficulty-<?= strtolower($suggestion['difficulty_level']) ?> px-3 py-1 rounded-full text-xs font-semibold">
                <?= $suggestion['difficulty_level'] ?>
              </span>
            </div>
            <div class="flex items-center gap-4 mb-4 text-sm text-gray-600">
              <?php if (!empty($suggestion['prep_time_minutes'])): ?>
              <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Prep: <?= $suggestion['prep_time_minutes'] ?> min</div>
              <?php endif; ?>
              <?php if (!empty($suggestion['cook_time_minutes'])): ?>
              <div class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Cook: <?= $suggestion['cook_time_minutes'] ?> min</div>
              <?php endif; ?>
            </div>
            <div class="mb-4">
              <h4 class="font-semibold text-gray-700 mb-2">Ingredients</h4>
              <p class="text-gray-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($suggestion['ingredients'])) ?></p>
            </div>
            <div>
              <h4 class="font-semibold text-gray-700 mb-2">Instructions</h4>
              <div class="text-gray-600 text-sm">
                <?php foreach (array_filter(explode("\n", $suggestion['steps']), 'trim') as $si => $step): ?>
                <div class="flex mb-2">
                  <span class="step-number"><?= $si + 1 ?></span>
                  <span><?= htmlspecialchars($step) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- YOU MAY ALSO LIKE -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="mt-16 mb-16">
      <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">You May Also Like</h2>
        <p class="text-gray-600">Discover other products you might enjoy</p>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php foreach ($relatedProducts as $related):
          $relHasStock = $related['has_stock'] > 0;
          $relImg      = !empty($related['primary_image']) ? $baseUrl . 'uploads/products/' . $related['primary_image'] : $baseUrl . 'uploads/products/default.png';
          $relUrl      = $baseUrl . 'item/' . strtolower(str_replace(' ', '-', $related['product_name']));
        ?>
        <div class="related-product-card bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 <?= !$relHasStock ? 'opacity-60' : '' ?>">
          <a href="<?= $relUrl ?>" class="block">
            <div class="relative">
              <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($related['product_name']) ?>" class="w-full h-48 object-cover">
              <?php if (!$relHasStock): ?>
              <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
                <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">Out of Stock</span>
              </div>
              <?php endif; ?>
            </div>
            <div class="p-4">
              <h3 class="font-semibold text-gray-800 mb-1 line-clamp-2"><?= htmlspecialchars($related['product_name']) ?></h3>
              <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($related['product_unit']) ?></p>
              <?php if (!empty($related['min_price'])): ?>
              <p class="text-orange-600 font-bold"><?= $relHasStock ? 'Starting at ' : '' ?>₱<?= number_format($related['min_price'], 2) ?></p>
              <?php else: ?>
              <p class="text-gray-400 text-sm italic">No price available</p>
              <?php endif; ?>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php include('./components/footer.php'); ?>

<!-- ✅ All cart operations (qty, remove, price recalc) handled by cart_process.js -->
<!-- This script ONLY handles: variant selection, qty for add-to-cart form, image switcher, share -->
<script>
  document.addEventListener('DOMContentLoaded', function () {

      var itemForm = document.querySelector('.add-to-cart-form');

      // ── Variant selection — MUST be defined before auto-select click below ─────
      document.querySelectorAll('.variant-button').forEach(function (button) {
          button.addEventListener('click', function () {
              var form          = document.querySelector('.add-to-cart-form');
              if (!form) return;
              var variantPrice  = parseFloat(button.dataset.variantPrice);
              var discountPrice = parseFloat(button.dataset.discountPrice);
              var unitType      = button.dataset.unitType;
              var minimumOrder  = parseFloat(button.dataset.minimumOrder);
              var orderIncr     = parseFloat(button.dataset.orderIncrement);
              var vHasStock     = button.dataset.hasStock === 'true';

              form.querySelectorAll('.variant-button').forEach(function (b) { b.classList.remove('selected-variant'); });
              button.classList.add('selected-variant');

              form.querySelector('[name="variant_id"]').value      = button.dataset.variantId;
              form.querySelector('[name="variant_name"]').value    = button.dataset.variantName;
              form.querySelector('[name="price"]').value           = discountPrice > 0 ? discountPrice : variantPrice;
              form.querySelector('[name="unit_type"]').value       = unitType;
              form.querySelector('[name="minimum_order"]').value   = minimumOrder;
              form.querySelector('[name="order_increment"]').value = orderIncr;

              var qtyInput = form.querySelector('.quantity');
              var dispQty  = unitType === 'piece' ? Math.round(minimumOrder) : minimumOrder;
              qtyInput.value = dispQty;
              qtyInput.min   = minimumOrder;
              qtyInput.step  = orderIncr;
              form.querySelector('[name="quantity"]').value = minimumOrder;

              form.querySelector('.unit-display').textContent       = unitType === 'piece' ? 'pcs' : unitType;
              form.querySelector('.minimum-order-text').textContent = 'Minimum: ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);

              _updatePriceDisplay(form, variantPrice, discountPrice, minimumOrder);

              // Enable/disable submit based on stock
              var submitBtn = form.querySelector('[name="add_to_cart"]');
              if (submitBtn) submitBtn.disabled = !vHasStock;

              form.querySelector('.variant-message')?.classList.add('hidden');
              form.querySelector('.minimum-error-message')?.classList.add('hidden');
              form.querySelector('.stock-error-message')?.classList.add('hidden');
          });
      });

      if (itemForm) {
          // ── Qty − button ──────────────────────────────────────────────────────
          itemForm.querySelector('.decrease-quantity')?.addEventListener('click', function () {
              var qtyInput  = itemForm.querySelector('.quantity');
              var minOrder  = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var newQty    = Math.max(minOrder, parseFloat(qtyInput.value) - orderIncr);
              qtyInput.value = newQty;
              itemForm.querySelector('[name="quantity"]').value = newQty;
              _updateTotalPrice(itemForm);
          });

          // ── Qty + button ──────────────────────────────────────────────────────
          itemForm.querySelector('.increase-quantity')?.addEventListener('click', function () {
              var qtyInput  = itemForm.querySelector('.quantity');
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var newQty    = parseFloat(qtyInput.value) + orderIncr;
              qtyInput.value = newQty;
              itemForm.querySelector('[name="quantity"]').value = newQty;
              _updateTotalPrice(itemForm);
          });

          // ── Live price preview while typing ───────────────────────────────────
          itemForm.querySelector('.quantity')?.addEventListener('input', function () {
              itemForm.querySelector('[name="quantity"]').value = parseFloat(this.value) || 0;
              _updateTotalPrice(itemForm);
          });

          // ── Snap to increment on blur ─────────────────────────────────────────
          itemForm.querySelector('.quantity')?.addEventListener('change', function () {
              var minOrder  = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var val       = parseFloat(this.value);
              if (isNaN(val) || val < minOrder) val = minOrder;
              val = minOrder + Math.round((val - minOrder) / orderIncr) * orderIncr;
              this.value = val;
              itemForm.querySelector('[name="quantity"]').value = val;
              _updateTotalPrice(itemForm);
          });

          itemForm.querySelector('.quantity')?.addEventListener('keydown', function (e) {
              if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
          });

          // ── Add-to-cart submit ─────────────────────────────────────────────────
          itemForm.addEventListener('submit', async function (e) {
              e.preventDefault();
              var variantId       = itemForm.querySelector('[name="variant_id"]').value;
              var quantity        = parseFloat(itemForm.querySelector('[name="quantity"]').value);
              var minimumOrder    = parseFloat(itemForm.querySelector('[name="minimum_order"]').value);
              var unitType        = itemForm.querySelector('[name="unit_type"]').value;
              var selectedVariant = itemForm.querySelector('.variant-button.selected-variant');
              var stockQty        = selectedVariant ? parseInt(selectedVariant.dataset.stockQuantity) : 0;
              var errMsg          = itemForm.querySelector('.minimum-error-message');
              var stockErrMsg     = itemForm.querySelector('.stock-error-message');

              if (!variantId) { itemForm.querySelector('.variant-message')?.classList.remove('hidden'); return; }
              if (quantity < minimumOrder) {
                  errMsg.textContent = 'Minimum order is ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);
                  errMsg.classList.remove('hidden');
                  return;
              }
              if (quantity > stockQty) {
                  stockErrMsg.textContent = 'Only ' + stockQty + ' ' + (unitType === 'piece' ? 'pcs' : unitType) + ' available';
                  stockErrMsg.classList.remove('hidden');
                  return;
              }

              try {
                  var res  = await fetch((window.CART_BASE || '/sjfbi-js') + '/functions/add_to_cart.php', {
                      method: 'POST',
                      body:   new FormData(itemForm)
                  });
                  var data = await res.json();
                  if (data.status === 'success') {
                      showToast('Product added to cart!', 'success');
                      await refreshCartFromServer();
                      var first = document.querySelector('.variant-button[data-has-stock="true"]');
                      if (first) first.click();
                  } else {
                      showToast(data.message || 'Failed to add product', 'error');
                  }
              } catch (err) {
                  showToast('An error occurred. Please try again.', 'error');
              }
          });
      }

      // ── Auto-select first in-stock variant — LAST so all listeners are ready ──
      var firstInStock = document.querySelector('.variant-button[data-has-stock="true"]');
      if (firstInStock) firstInStock.click();

      // ── Price helpers ─────────────────────────────────────────────────────────
      function _updatePriceDisplay(form, variantPrice, discountPrice, quantity) {
          var el = form.querySelector('.price-display');
          if (!el) return;
          var price = discountPrice > 0 ? discountPrice : variantPrice;
          var total = price * quantity;
          if (discountPrice > 0) {
              el.innerHTML = '<span class="line-through text-gray-500 text-lg">₱' + (variantPrice * quantity).toFixed(2) + '</span>' +
                            '<span class="text-red-600 font-bold text-2xl ml-2">₱' + total.toFixed(2) + '</span>';
          } else {
              el.innerHTML = '<span class="text-gray-800 font-bold text-2xl">₱' + total.toFixed(2) + '</span>';
          }
      }

      function _updateTotalPrice(form) {
          var selected = form.querySelector('.variant-button.selected-variant');
          if (!selected) return;
          var qty = parseFloat(form.querySelector('.quantity').value) || 0;
          _updatePriceDisplay(form, parseFloat(selected.dataset.variantPrice), parseFloat(selected.dataset.discountPrice), qty);
      }
  });

  // ── Image switcher ────────────────────────────────────────────────────────────
  function changeImage(imageName) {
      document.getElementById('mainImage').src = '<?= $baseUrl ?>uploads/products/' + imageName;
  }

  // ── Share helpers ─────────────────────────────────────────────────────────────
  function shareToFacebook(url) {
      window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
  }

  function shareProduct(title, text, url) {
      if (navigator.share) {
          navigator.share({ title: title, text: text, url: url })
              .catch(function (err) { if (err.name !== 'AbortError') _copyShareLink(url); });
      } else {
          _copyShareLink(url);
      }
  }

  function _copyShareLink(url) {
      if (navigator.clipboard) {
          navigator.clipboard.writeText(url)
              .then(function () { showToast('Link copied to clipboard!', 'success'); })
              .catch(function () { showToast('Failed to copy link', 'error'); });
      } else {
          var el = document.createElement('textarea');
          el.value = url;
          document.body.appendChild(el);
          el.select();
          document.execCommand('copy');
          document.body.removeChild(el);
          showToast('Link copied to clipboard!', 'success');
      }
  }
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>AOS.init();</script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>

</body>
</html>