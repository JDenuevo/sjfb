<?php
include 'conn.php';

$pageTitle = 'Item';
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

$product             = null;
$variants            = [];
$images              = [];
$primaryImage        = null;
$categories          = [];
$primaryCategory     = null;
$hasStock            = false;
$totalStock          = 0;
$productCategories   = [];

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
                     (SELECT MIN(variant_price) FROM product_variants WHERE product_id = p.product_id AND is_deleted = 0) as min_price,
                     (SELECT SUM(stock_quantity > 0) FROM product_variants WHERE product_id = p.product_id) as has_stock
                     FROM products p
                     JOIN product_category_links pcl ON p.product_id = pcl.product_id
                     WHERE pcl.category_id IN ($catIds) AND p.product_id != ? AND p.is_deleted = 0
                     GROUP BY p.product_id ORDER BY has_stock DESC, RAND() LIMIT 6");
    $stmtRel->bind_param("i", $product['product_id']);
    $stmtRel->execute();
    $relatedProducts = $stmtRel->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtRel->close();
}

// Lowest/highest prices + first in-stock variant
$lowestPrice         = null;
$highestPrice        = null;
$firstInStockVariant = null;
foreach ($variants as $vid => $v) {
    $effectivePrice = ($v['discount_price'] > 0) ? $v['discount_price'] : $v['variant_price'];
    if ($lowestPrice  === null || $effectivePrice     < $lowestPrice)  $lowestPrice  = $effectivePrice;
    if ($highestPrice === null || $v['variant_price'] > $highestPrice) $highestPrice = $v['variant_price'];
    if ($firstInStockVariant === null && $v['has_stock']) {
        $firstInStockVariant = array_merge(['variant_id' => $vid], $v);
    }
}

$canonicalUrl = $baseUrl . 'item/' . strtolower(str_replace(' ', '-', $product['product_name']));
$shareUrlNew  = $canonicalUrl;
$shareTitle   = $product['product_name'];
$shareText    = 'Check out this fresh seafood: ' . $product['product_name'] . ' from St. Joseph Fish Brokerage Inc.';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth">
<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="<?= htmlspecialchars($product['product_description'] ?? $product['product_name']) ?>">
  <meta property="og:type"  content="product">
  <meta property="og:title" content="<?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.">
  <meta property="og:image" content="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>">

  <link rel="shortcut icon"    href="<?= $baseUrl ?>assets/icons/logo.ico">
  <link rel="icon"             href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $baseUrl ?>assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="<?= $baseUrl ?>style.css" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="<?= $baseUrl ?>functions/cart_process.js"></script>
  <script src="<?= $baseUrl ?>functions/product_process.js"></script>

  <style>

    /* ── Gallery (preserved as-is) ── */
    .gallery-thumb-strip::-webkit-scrollbar { display:none; }
    #galleryLightbox { display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out; }
    #lightboxImg     { max-width:90vw;max-height:90vh;object-fit:contain;border-radius:12px; }

    /* ── Qty controls ── */
    .qty-wrap  { display:flex;align-items:center;border:1.5px solid #e5e7eb;border-radius:10px;overflow:hidden;width:fit-content; }
    .qty-btn   { width:38px;height:42px;display:flex;align-items:center;justify-content:center;background:#f9fafb;border:none;cursor:pointer;font-size:18px;color:#374151;transition:background .15s;flex-shrink:0; }
    .qty-btn:hover { background:#ea580c;color:#fff; }
    .qty-input { width:60px;height:42px;text-align:center;border:none;outline:none;font-family:inherit;font-size:15px;font-weight:600;background:transparent; }

    /* hide number spinners */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none;margin:0; }

    /* difficulty badge colours (not in Tailwind defaults) */
    .diff-easy   { background:#dcfce7;color:#166534; }
    .diff-medium { background:#fef3c7;color:#92400e; }
    .diff-hard   { background:#fee2e2;color:#991b1b; }
  </style>
</head>
<body>

<?php include('./components/navigation.php'); ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <?php include('./components/nav_crumb.php'); ?>

  <!-- ═══════════════════════════════════════
       MAIN PRODUCT SECTION
  ═══════════════════════════════════════ -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 my-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">

      <!-- ── LEFT: Gallery (PRESERVED) ─────────────────────────── -->
      <div class="flex flex-col gap-3 w-full">

        <!-- Main image -->
        <div id="mainImageWrap"
             class="relative w-full aspect-square rounded-2xl overflow-hidden bg-white border border-gray-200">

          <?php if (!$hasStock): ?>
          <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
            <span class="bg-red-600 text-white font-bold text-sm px-5 py-1.5 rounded -rotate-12 tracking-wide">
              OUT OF STOCK
            </span>
          </div>
          <?php endif; ?>

          <img id="mainImage"
               src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
               alt="<?= htmlspecialchars($product['product_name']) ?>"
               class="w-full h-full object-contain block transition-opacity duration-200
                      <?= !$hasStock ? 'opacity-55' : '' ?>">

          <!-- Fullscreen -->
          <button onclick="openGalleryLightbox()" title="View full size"
                  style="position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:9px;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.1)">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
            </svg>
          </button>

          <?php if (count($images) > 1): ?>
          <button onclick="stepGallery(-1)"
                  style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.08)">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <button onclick="stepGallery(1)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.08)">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
          </button>
          <div id="imgCounter"
               class="absolute bottom-2.5 right-2.5 z-20 text-xs text-gray-500 bg-white px-2.5 py-1 rounded-full border border-gray-200">
            1 / <?= count($images) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Thumbnail strip -->
        <div class="flex gap-2 overflow-x-auto pb-1 gallery-thumb-strip">
          <?php foreach ($images as $i => $img): ?>
          <div class="gallery-thumb flex-shrink-0 w-20 h-20 sm:w-24 sm:h-24 rounded-lg overflow-hidden cursor-pointer border-2
                      <?= $i === 0 ? 'border-orange-500' : 'border-transparent' ?>
                      hover:scale-105 transition-all duration-150"
               onclick="selectThumb(this, '<?= htmlspecialchars($img) ?>')">
            <img src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($img) ?>" alt="thumb <?= $i + 1 ?>"
                 class="w-full h-full object-cover">
          </div>
          <?php endforeach; ?>
        </div>

      </div><!-- /gallery -->

      <!-- ── RIGHT: Product Info ────────────────────────────────── -->
      <div class="flex flex-col gap-5">

        <!-- Badges -->
        <div class="flex flex-wrap gap-2">
          <?php if ($hasStock): ?>
          <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-teal-100 text-teal-800">
            <span class="size-1.5 inline-block bg-teal-600 rounded-full"></span>
            In Stock
          </span>
          <?php else: ?>
          <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-red-100 text-red-800">
            <span class="size-1.5 inline-block bg-red-600 rounded-full"></span>
            Out of Stock
          </span>
          <?php endif; ?>
          <?php foreach (array_slice($categories, 0, 2) as $cat): ?>
          <span class="inline-flex items-center py-1.5 px-3 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
            <?= htmlspecialchars($cat['category_name']) ?>
          </span>
          <?php endforeach; ?>
        </div>

        <!-- Name + unit -->
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight">
            <?= htmlspecialchars($product['product_name']) ?>
          </h1>
          <?php if (!empty($product['product_unit'])): ?>
          <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($product['product_unit']) ?></p>
          <?php endif; ?>
        </div>

        <?php if (!empty($product['product_description'])): ?>
        <p class="hidden sm:block text-sm text-gray-600 leading-relaxed">
          <?= htmlspecialchars($product['product_description']) ?>
        </p>
        <?php endif; ?>

        <hr class="border-gray-100">

        <!-- Price -->
        <div class="price-display flex items-baseline flex-wrap gap-1.5">
          <?php if ($firstInStockVariant):
            $dp = (float)$firstInStockVariant['discount_price'];
            $vp = (float)$firstInStockVariant['variant_price'];
            $mo = (float)$firstInStockVariant['minimum_order'];
            $showDiscount = $dp > 0 && $dp < $vp;
            $pct_init = $showDiscount ? round((($vp - $dp) / $vp) * 100) : 0;
          ?>
            <?php if ($showDiscount): ?>
            <span class="original-price text-lg text-gray-400 line-through">₱<?= number_format($vp * $mo, 2) ?></span>
            <span class="sale-price text-4xl font-extrabold text-red-600 leading-none">₱<?= number_format($dp * $mo, 2) ?></span>
            <span class="discount-pill inline-flex items-center py-0.5 px-2 rounded-full text-xs font-bold bg-green-100 text-green-700 ms-1">Save <?= $pct_init ?>%</span>
            <?php else: ?>
            <span class="original-price text-lg text-gray-400 line-through hidden"></span>
            <span class="sale-price text-4xl font-extrabold text-gray-900 leading-none">₱<?= number_format($vp * $mo, 2) ?></span>
            <span class="discount-pill hidden"></span>
            <?php endif; ?>
          <?php else: ?>
            <span class="original-price text-lg text-gray-400 line-through hidden"></span>
            <span class="sale-price text-xl font-semibold text-gray-400">Price unavailable</span>
            <span class="discount-pill hidden"></span>
          <?php endif; ?>
        </div>

        <?php if ($firstInStockVariant): ?>
        <p id="pricePerUnit" class="-mt-2 text-xs text-gray-500">
          ₱<?= number_format($firstInStockVariant['discount_price'] > 0 ? $firstInStockVariant['discount_price'] : $firstInStockVariant['variant_price'], 2) ?> per <?= htmlspecialchars($firstInStockVariant['unit_type']) ?>
          &nbsp;·&nbsp; Min order: <?= $firstInStockVariant['minimum_order'] ?> <?= $firstInStockVariant['unit_type'] === 'piece' ? 'pcs' : $firstInStockVariant['unit_type'] ?>
        </p>
        <?php endif; ?>

        <!-- Trust features -->
        <div class="hidden sm:flex flex-col divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
          <?php foreach ([
            ['✔', 'Fresh from Navotas Fish Port daily',              'text-green-600'],
            ['✔', 'Quality guaranteed — satisfaction or refund',     'text-green-600'],
            ['✔', 'Directly from local Filipino fishermen',           'text-green-600'],
          ] as [$icon, $text, $color]): ?>
          <div class="flex items-center gap-3 px-4 py-3">
            <svg class="size-4 <?= $color ?> flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span class="text-sm text-gray-700 font-medium"><?= $text ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($hasStock): ?>

        <!-- Variant select -->
        <?php if (!empty($variants)): ?>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5">Select Variant</label>
          <select id="visibleVariantSelect"
                  class="py-2.5 px-3 pe-9 block w-full border border-gray-200 rounded-lg text-sm
                         focus:border-orange-500 focus:ring-orange-500
                         disabled:opacity-50 disabled:pointer-events-none">
            <?php foreach ($variants as $vid => $v):
              $vdp   = floatval($v['discount_price'] ?? 0);
              $vvp   = floatval($v['variant_price']);
              $vpct  = ($vdp > 0 && $vvp > 0) ? round((($vvp - $vdp) / $vvp) * 100) : 0;
              $isSelected = ($firstInStockVariant && $vid == $firstInStockVariant['variant_id']);
              $label = htmlspecialchars($v['variant_name']);
              if (!$v['has_stock']) $label .= ' (No Stock)';
              elseif ($vpct > 0)    $label .= ' (-' . $vpct . '%)';
            ?>
            <option value="<?= $vid ?>" <?= $isSelected ? 'selected' : '' ?> <?= !$v['has_stock'] ? 'disabled' : '' ?>>
              <?= $label ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Add-to-cart form -->
        <form class="add-to-cart-form flex flex-col gap-3"
              method="POST" action="javascript:void(0)"
              data-product-id="<?= $product['product_id'] ?>">

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

          <!-- Hidden variant-select — required by product_process.js -->
          <select class="variant-select hidden" data-product-id="<?= $product['product_id'] ?>">
            <?php foreach ($variants as $vid => $v):
              $vdp  = floatval($v['discount_price'] ?? 0);
              $vvp  = floatval($v['variant_price']);
              $vpct = ($vdp > 0 && $vvp > 0) ? round((($vvp - $vdp) / $vvp) * 100) : 0;
              $isSelected = ($firstInStockVariant && $vid == $firstInStockVariant['variant_id']);
            ?>
            <option
              value="<?= $vid ?>"
              data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
              data-variant-price="<?= $vvp ?>"
              data-discount-price="<?= $vdp ?>"
              data-discount-percent="<?= $vpct ?>"
              data-unit-type="<?= htmlspecialchars($v['unit_type'] ?? 'piece') ?>"
              data-minimum-order="<?= floatval($v['minimum_order'] ?? 1) ?>"
              data-order-increment="<?= floatval($v['order_increment'] ?? 1) ?>"
              data-stock-quantity="<?= intval($v['stock_quantity'] ?? 0) ?>"
              data-has-stock="<?= $v['has_stock'] ? 'true' : 'false' ?>"
              <?= $isSelected ? 'selected' : '' ?>
              <?= !$v['has_stock'] ? 'disabled' : '' ?>>
              <?= htmlspecialchars($v['variant_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>

          <!-- Quantity row -->
          <div class="flex items-end gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1.5">Quantity</label>
              <div class="qty-wrap">
                <button type="button" class="qty-btn decrease-quantity">−</button>
                <input type="number" class="qty-input quantity" value="" min="" step="">
                <button type="button" class="qty-btn increase-quantity">+</button>
              </div>
            </div>
            <span class="mb-2.5 text-sm text-gray-500 unit-display"></span>
          </div>
          <p class="text-xs text-gray-400 -mt-1 minimum-order-text"></p>

          <!-- Cart + Share buttons -->
          <div class="flex gap-2.5 items-center">
            <button type="submit" name="add_to_cart"
                    class="cart-btn flex-1 py-3 px-4 inline-flex items-center justify-center gap-x-2
                           text-sm font-bold rounded-xl border border-transparent
                           bg-orange-600 text-white shadow-sm
                           hover:bg-orange-700 active:bg-orange-800
                           disabled:opacity-50 disabled:pointer-events-none
                           transition-all focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
              <svg class="size-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                <path d="M17 17h-11v-14h-2"/>
                <path d="M6 5l14 1l-1 7h-13"/>
              </svg>
              <span class="cart-btn-label">Add to Cart</span>
            </button>

            <!-- Facebook share -->
            <button type="button"
                    onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                    title="Share on Facebook"
                    class="size-11 flex-shrink-0 inline-flex items-center justify-center
                           rounded-xl border border-gray-200 bg-white text-gray-700
                           hover:bg-blue-50 hover:border-blue-300
                           transition focus:outline-none focus:ring-2 focus:ring-blue-300">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="14" fill="url(#fbgItem)"/>
                <path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/>
                <defs>
                  <linearGradient id="fbgItem" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/>
                  </linearGradient>
                </defs>
              </svg>
            </button>

            <!-- Generic share -->
            <button type="button"
                    onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                    title="Share"
                    class="size-11 flex-shrink-0 inline-flex items-center justify-center
                           rounded-xl border border-gray-200 bg-white text-gray-500
                           hover:bg-gray-50 hover:border-gray-300
                           transition focus:outline-none focus:ring-2 focus:ring-gray-200">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M8 9h-1a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1"/>
                <path d="M12 14v-11"/><path d="M9 6l3-3 3 3"/>
              </svg>
            </button>
          </div>

          <!-- Validation messages -->
          <p class="text-red-500 text-xs mt-1 variant-message hidden">Please select a variant first.</p>
          <p class="text-red-500 text-xs mt-1 minimum-error-message hidden"></p>
          <p class="text-red-500 text-xs mt-1 stock-error-message hidden"></p>
        </form>

        <!-- Delivery notice -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3.5 flex items-start gap-3">
          <svg class="size-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
          </svg>
          <div>
            <p class="text-xs font-bold text-yellow-800">Order now for fast delivery</p>
            <p class="text-xs text-yellow-700 mt-0.5">Fresh seafood packed and dispatched from Navotas daily</p>
          </div>
        </div>

        <?php else: ?>

        <!-- Out of stock state -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 text-center">
          <p class="text-red-700 font-bold mb-1">Currently Out of Stock</p>
          <p class="text-gray-500 text-sm">Check back soon or browse other products.</p>
        </div>

        <button onclick="shareToFacebook('<?= $shareUrlNew ?>')" type="button"
                class="w-full py-3 px-4 inline-flex items-center justify-center gap-x-2
                       rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700
                       hover:bg-gray-50 transition focus:outline-none focus:ring-2 focus:ring-gray-200">
          Share on Facebook
        </button>

        <?php endif; ?>

      </div><!-- /right -->
    </div><!-- /grid -->
  </div><!-- /product card -->


  <!-- ═══════════════════════════════════════
       TABS  (Preline nav-tabs pattern)
  ═══════════════════════════════════════ -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">

    <!-- Tab nav -->
    <div class="border-b border-gray-100 px-5">
      <nav class="flex gap-x-1 overflow-x-auto" aria-label="Tabs" role="tablist">
        <button type="button"
                class="hs-tab-active:font-bold hs-tab-active:border-orange-500 hs-tab-active:text-orange-600
                       py-4 px-3 inline-flex items-center gap-x-2 border-b-2 border-transparent
                       text-sm font-medium whitespace-nowrap text-gray-500
                       hover:text-orange-600 focus:outline-none active"
                id="tab-desc-item" data-hs-tab="#tab-desc" aria-controls="tab-desc" role="tab">
          Description
        </button>

        <?php if (!empty($cookingSuggestions)): ?>
        <button type="button"
                class="hs-tab-active:font-bold hs-tab-active:border-orange-500 hs-tab-active:text-orange-600
                       py-4 px-3 inline-flex items-center gap-x-2 border-b-2 border-transparent
                       text-sm font-medium whitespace-nowrap text-gray-500
                       hover:text-orange-600 focus:outline-none"
                id="tab-cooking-item" data-hs-tab="#tab-cooking" aria-controls="tab-cooking" role="tab">
          Cooking Suggestions
          <span class="ms-1 inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-bold bg-orange-500 text-white">
            <?= count($cookingSuggestions) ?>
          </span>
        </button>
        <?php endif; ?>

        <?php if (!empty($product['product_nickname'])): ?>
        <button type="button"
                class="hs-tab-active:font-bold hs-tab-active:border-orange-500 hs-tab-active:text-orange-600
                       py-4 px-3 inline-flex items-center gap-x-2 border-b-2 border-transparent
                       text-sm font-medium whitespace-nowrap text-gray-500
                       hover:text-orange-600 focus:outline-none"
                id="tab-tags-item" data-hs-tab="#tab-tags" aria-controls="tab-tags" role="tab">
          Tags
        </button>
        <?php endif; ?>
      </nav>
    </div>

    <!-- Tab panels -->
    <div class="p-5 sm:p-7">

      <!-- Description -->
      <div id="tab-desc" role="tabpanel" aria-labelledby="tab-desc-item">
        <div class="grid md:grid-cols-2 gap-8 lg:gap-10">
          <div>
            <h2 class="text-xl font-extrabold text-gray-900 mb-3">
              About <?= htmlspecialchars($product['product_name']) ?>
            </h2>
            <?php if (!empty($product['product_description'])): ?>
            <p class="text-sm text-gray-600 leading-relaxed">
              <?= nl2br(htmlspecialchars($product['product_description'])) ?>
            </p>
            <?php else: ?>
            <p class="text-sm text-gray-400">No description available for this product.</p>
            <?php endif; ?>

            <?php if (!empty($categories)): ?>
            <div class="mt-5">
              <h3 class="text-sm font-bold text-gray-700 mb-2">Categories</h3>
              <div class="flex flex-wrap gap-2">
                <?php foreach ($categories as $cat): ?>
                <a href="<?= $baseUrl ?>shop.php?category=<?= urlencode($cat['category_slug']) ?>"
                   class="py-1 px-3.5 inline-flex items-center rounded-full text-xs font-medium
                          bg-gray-100 text-gray-700 border border-gray-200
                          hover:border-orange-400 hover:text-orange-600 hover:bg-orange-50 transition">
                  <?= htmlspecialchars($cat['category_name']) ?>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Feature cards -->
          <div class="grid gap-3">
            <?php foreach ([
              ['🐟', 'Fresh Daily',        'Sourced directly from Navotas Fish Port every morning.'],
              ['✅', 'Quality Guaranteed',  '100% satisfaction — refund if you\'re not happy.'],
              ['🤝', 'Supports Fishermen',  'Every purchase goes directly to local Filipino fishermen.'],
            ] as [$icon, $title, $desc]): ?>
            <div class="flex gap-3.5 p-4 bg-gray-50 rounded-xl border border-gray-100">
              <span class="text-xl leading-none flex-shrink-0"><?= $icon ?></span>
              <div>
                <p class="text-sm font-bold text-gray-900 mb-0.5"><?= $title ?></p>
                <p class="text-xs text-gray-500"><?= $desc ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Cooking suggestions -->
      <?php if (!empty($cookingSuggestions)): ?>
      <div id="tab-cooking" class="hidden" role="tabpanel" aria-labelledby="tab-cooking-item">
        <h2 class="text-xl font-extrabold text-gray-900 mb-5">
          How to Cook <?= htmlspecialchars($product['product_name']) ?>
        </h2>
        <div class="grid md:grid-cols-2 gap-6">
          <?php foreach ($cookingSuggestions as $sug): ?>
          <div class="border border-gray-100 rounded-2xl overflow-hidden">

            <!-- Card header -->
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
              <h3 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($sug['dish_name']) ?></h3>
              <span class="inline-flex items-center py-0.5 px-2.5 rounded-full text-xs font-bold diff-<?= strtolower($sug['difficulty_level']) ?>">
                <?= $sug['difficulty_level'] ?>
              </span>
            </div>

            <div class="p-4 space-y-3">

              <!-- Time row -->
              <?php if (!empty($sug['prep_time_minutes']) || !empty($sug['cook_time_minutes'])): ?>
              <div class="flex gap-4">
                <?php if (!empty($sug['prep_time_minutes'])): ?>
                <span class="inline-flex items-center gap-x-1.5 text-xs text-gray-500">
                  <svg class="size-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                  Prep: <?= $sug['prep_time_minutes'] ?> min
                </span>
                <?php endif; ?>
                <?php if (!empty($sug['cook_time_minutes'])): ?>
                <span class="inline-flex items-center gap-x-1.5 text-xs text-gray-500">
                  <svg class="size-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                  Cook: <?= $sug['cook_time_minutes'] ?> min
                </span>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              <!-- Ingredients -->
              <div>
                <p class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Ingredients</p>
                <p class="text-xs text-gray-500 leading-relaxed"><?= nl2br(htmlspecialchars($sug['ingredients'])) ?></p>
              </div>

              <!-- Steps -->
              <div>
                <p class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Instructions</p>
                <ol class="space-y-2">
                  <?php foreach (array_filter(explode("\n", $sug['steps']), 'trim') as $si => $step): ?>
                  <li class="flex gap-2.5">
                    <span class="size-5 flex-shrink-0 mt-0.5 rounded-full bg-orange-500 text-white
                                 text-[10px] font-bold inline-flex items-center justify-center">
                      <?= $si + 1 ?>
                    </span>
                    <span class="text-xs text-gray-600 leading-relaxed"><?= htmlspecialchars($step) ?></span>
                  </li>
                  <?php endforeach; ?>
                </ol>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tags -->
      <?php if (!empty($product['product_nickname'])): ?>
      <div id="tab-tags" class="hidden" role="tabpanel" aria-labelledby="tab-tags-item">
        <h2 class="text-lg font-extrabold text-gray-900 mb-3">Also known as</h2>
        <div class="flex flex-wrap gap-2.5">
          <?php
          $tags = json_decode($product['product_nickname'], true);
          if (is_array($tags)) {
            foreach ($tags as $tag) {
              echo '<span class="py-2 px-4 inline-flex items-center rounded-full text-sm font-semibold bg-orange-50 border border-orange-200 text-orange-700">#' . htmlspecialchars($tag) . '</span>';
            }
          }
          ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div><!-- /tabs -->


  <!-- ═══════════════════════════════════════
       RELATED PRODUCTS
  ═══════════════════════════════════════ -->
  <?php if (!empty($relatedProducts)): ?>
  <div class="mb-10">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-extrabold text-gray-900">You May Also Like</h2>
      <a href="<?= $baseUrl ?>shop"
         class="inline-flex items-center gap-x-1 text-sm text-orange-600 font-semibold hover:text-orange-700 hover:underline decoration-2 underline-offset-2 transition">
        Browse all
        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
      <?php foreach ($relatedProducts as $rel):
        $relHasStock = $rel['has_stock'] > 0;
        $relImg = !empty($rel['primary_image'])
            ? $baseUrl . 'uploads/products/' . $rel['primary_image']
            : $baseUrl . 'uploads/products/default.png';
        $relUrl = $baseUrl . 'item/' . strtolower(str_replace(' ', '-', $rel['product_name']));
      ?>
      <a href="<?= $relUrl ?>"
         class="group flex flex-col bg-white rounded-2xl border border-gray-100 overflow-hidden
                <?= !$relHasStock ? 'opacity-60' : '' ?>
                hover:-translate-y-1 hover:shadow-lg transition-all duration-200 no-underline">
        <div class="relative overflow-hidden">
          <img src="<?= htmlspecialchars($relImg) ?>"
               alt="<?= htmlspecialchars($rel['product_name']) ?>"
               class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
          <?php if (!$relHasStock): ?>
          <div class="absolute inset-0 bg-black/35 flex items-center justify-center">
            <span class="inline-flex items-center py-0.5 px-2.5 rounded-full text-[10px] font-bold bg-red-600 text-white">
              Out of Stock
            </span>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-2.5 pb-3">
          <p class="text-xs font-bold text-gray-900 leading-snug line-clamp-2 mb-1">
            <?= htmlspecialchars($rel['product_name']) ?>
          </p>
          <?php if (!empty($rel['min_price'])): ?>
          <p class="text-xs font-bold text-orange-600">
            From ₱<?= number_format($rel['min_price'], 0) ?>
          </p>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /max-w -->


<!-- Lightbox -->
<div id="galleryLightbox" onclick="closeGalleryLightbox()">
  <img id="lightboxImg" src="" alt="full size">
</div>

<?php include('./components/footer.php'); ?>

<script>
// ── Visible select → hidden select bridge ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var visibleSel = document.getElementById('visibleVariantSelect');
    var hiddenSel  = document.querySelector('.variant-select');

    function syncAndFire() {
        if (!visibleSel || !hiddenSel) return;
        for (var i = 0; i < hiddenSel.options.length; i++) {
            if (hiddenSel.options[i].value === visibleSel.value) {
                hiddenSel.selectedIndex = i;
                break;
            }
        }
        hiddenSel.dispatchEvent(new Event('change'));
    }

    if (visibleSel) {
        visibleSel.addEventListener('change', syncAndFire);
        syncAndFire();
    } else if (hiddenSel) {
        hiddenSel.dispatchEvent(new Event('change'));
    }

    // Update price display + pricePerUnit on every variant change
    if (hiddenSel) {
        hiddenSel.addEventListener('change', function () {
            var opt = hiddenSel.options[hiddenSel.selectedIndex];
            if (!opt) return;

            var vp  = parseFloat(opt.dataset.variantPrice)  || 0;
            var dp  = parseFloat(opt.dataset.discountPrice) || 0;
            var pct = parseInt(opt.dataset.discountPercent  || 0, 10);
            var ut  = opt.dataset.unitType    || 'piece';
            var mo  = parseFloat(opt.dataset.minimumOrder) || 1;
            var ep  = (dp > 0 && dp < vp) ? dp : vp;
            var ud  = ut === 'piece' ? 'pcs' : ut;

            var origEl = document.querySelector('.price-display .original-price');
            var saleEl = document.querySelector('.price-display .sale-price');
            var pillEl = document.querySelector('.price-display .discount-pill');

            if (origEl && saleEl) {
                if (dp > 0 && dp < vp && pct > 0) {
                    origEl.textContent = '₱' + (vp * mo).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    origEl.classList.remove('hidden');
                    saleEl.textContent = '₱' + (dp * mo).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    saleEl.classList.remove('text-gray-900');
                    saleEl.classList.add('text-red-600');
                    if (pillEl) { pillEl.textContent = 'Save ' + pct + '%'; pillEl.classList.remove('hidden'); }
                } else {
                    origEl.textContent = ''; origEl.classList.add('hidden');
                    saleEl.textContent = '₱' + (vp * mo).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    saleEl.classList.remove('text-red-600');
                    saleEl.classList.add('text-gray-900');
                    if (pillEl) { pillEl.textContent = ''; pillEl.classList.add('hidden'); }
                }
            }

            var perUnit = document.getElementById('pricePerUnit');
            if (perUnit) perUnit.textContent = '₱' + ep.toFixed(2) + ' per ' + ut + ' · Min order: ' + mo + ' ' + ud;

            var form  = document.querySelector('.add-to-cart-form');
            var label = form && form.querySelector('.cart-btn-label');
            if (label) label.textContent = 'Add to Cart — ₱' + (ep * mo).toFixed(2);
        });
    }
});

// Keep cart-btn-label updated when qty changes
document.addEventListener('DOMContentLoaded', function () {
    var form     = document.querySelector('.add-to-cart-form');
    var qtyInput = form && form.querySelector('.quantity');
    if (!form || !qtyInput) return;

    function syncLabel() {
        var sel = form.querySelector('.variant-select');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        var vp  = parseFloat(opt.dataset.variantPrice)  || 0;
        var dp  = parseFloat(opt.dataset.discountPrice) || 0;
        var qty = parseFloat(qtyInput.value) || 0;
        var ep  = (dp > 0 && dp < vp) ? dp : vp;
        var lbl = form.querySelector('.cart-btn-label');
        if (lbl) lbl.textContent = 'Add to Cart — ₱' + (ep * qty).toFixed(2);
    }

    qtyInput.addEventListener('input',  syncLabel);
    qtyInput.addEventListener('change', syncLabel);
    form.querySelector('.decrease-quantity')?.addEventListener('click', function () { setTimeout(syncLabel, 0); });
    form.querySelector('.increase-quantity')?.addEventListener('click', function () { setTimeout(syncLabel, 0); });
});

// ── Gallery ───────────────────────────────────────────────────────────────────
var _imgs    = <?= json_encode(array_values($images)) ?>;
var _base    = '<?= $baseUrl ?>';
var _current = 0;

function _setThumb(index) {
    document.querySelectorAll('.gallery-thumb').forEach(function (t, i) {
        t.classList.toggle('border-orange-500', i === index);
        t.classList.toggle('border-transparent', i !== index);
    });
    _current = index;
    document.getElementById('mainImage').src = _base + 'uploads/products/' + _imgs[index];
    var ctr = document.getElementById('imgCounter');
    if (ctr) ctr.textContent = (index + 1) + ' / ' + _imgs.length;
}

function selectThumb(el, img) {
    var thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
    _setThumb(thumbs.indexOf(el));
}

function stepGallery(dir) {
    var next = (_current + dir + _imgs.length) % _imgs.length;
    _setThumb(next);
    var thumbs = document.querySelectorAll('.gallery-thumb');
    thumbs[next]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

function openGalleryLightbox() {
    document.getElementById('lightboxImg').src = document.getElementById('mainImage').src;
    document.getElementById('galleryLightbox').style.display = 'flex';
}
function closeGalleryLightbox() {
    document.getElementById('galleryLightbox').style.display = 'none';
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape')     closeGalleryLightbox();
    if (e.key === 'ArrowLeft')  stepGallery(-1);
    if (e.key === 'ArrowRight') stepGallery(1);
});

// ── Share ─────────────────────────────────────────────────────────────────────
function shareToFacebook(url) {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
}
function shareProduct(title, text, url) {
    if (navigator.share) {
        navigator.share({ title, text, url }).catch(function (err) {
            if (err.name !== 'AbortError') _copyLink(url);
        });
    } else { _copyLink(url); }
}
function _copyLink(url) {
    navigator.clipboard?.writeText(url)
        .then(function () { showToast('Link copied!', 'success'); })
        .catch(function () { showToast('Could not copy link', 'error'); });
}
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>AOS.init();</script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>

</body>
</html>