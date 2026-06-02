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
                     GROUP BY p.product_id ORDER BY has_stock DESC, RAND() LIMIT 6");
    $stmtRel->bind_param("i", $product['product_id']);
    $stmtRel->execute();
    $relatedProducts = $stmtRel->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtRel->close();
}

// Lowest and highest prices for display
$lowestPrice  = null;
$highestPrice = null;
$firstInStockVariant = null;
foreach ($variants as $vid => $v) {
    $effectivePrice = ($v['discount_price'] > 0) ? $v['discount_price'] : $v['variant_price'];
    if ($lowestPrice === null || $effectivePrice < $lowestPrice) $lowestPrice = $effectivePrice;
    if ($highestPrice === null || $v['variant_price'] > $highestPrice) $highestPrice = $v['variant_price'];
    if ($firstInStockVariant === null && $v['has_stock']) {
        $firstInStockVariant = array_merge(['variant_id' => $vid], $v);
    }
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="<?= htmlspecialchars($product['product_description'] ?? $product['product_name']) ?>">
  <meta property="og:type" content="product">
  <meta property="og:title" content="<?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.">
  <meta property="og:image" content="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>">

  <link rel="shortcut icon" href="<?= $baseUrl ?>assets/icons/logo.ico">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $baseUrl ?>assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="<?= $baseUrl ?>style.css" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="<?= $baseUrl ?>functions/cart_process.js"></script>

  <style>
    body { font-family: 'Lexend', sans-serif; }

    /* ── Gallery ── */
    .gallery-thumb {
      width: 72px; height: 72px; border-radius: 8px; overflow-x: auto;
      cursor: pointer; flex-shrink: 0; border: 2px solid transparent;
      box-sizing: border-box; transition: border-color .15s, transform .15s;
    }
    .gallery-thumb:hover { transform: scale(1.04); }
    .gallery-thumb.active { border-color: #ea580c; }
    .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }

    #mainImageWrap {
      position: relative; border-radius: 16px; overflow: hidden;
      background: #fff; aspect-ratio: 1/1;
      border: 1px solid #e5e7eb;
      box-shadow: 0 4px 20px rgba(0,0,0,.06);
    }
    #mainImage { width: 100%; height: 100%; object-fit: contain; display: block; transition: opacity .2s; }

    /* ── Stock / category badges ── */
    .badge-pill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 12px; border-radius: 9999px;
      font-size: 12px; font-weight: 600; border: 1px solid;
    }
    .badge-instock  { background: #ecfdf5; color: #15803d; border-color: #bbf7d0; }
    .badge-outstock { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .badge-cat      { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }

    /* ── Variant buttons ── */
    .variant-btn {
      padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      border: 2px solid #e5e7eb; background: #fff; color: #374151;
      cursor: pointer; transition: all .2s; white-space: nowrap;
    }
    .variant-btn:hover:not(:disabled) { border-color: #fb923c; color: #ea580c; background: #fff7ed; }
    .variant-btn.selected-variant { border-color: #ea580c; background: #ea580c; color: #fff; }
    .variant-btn:disabled { opacity: .45; cursor: not-allowed; background: #f9fafb; }

    /* ── Qty controls ── */
    .qty-wrap { display: flex; align-items: center; border: 1.5px solid #e5e7eb; border-radius: 10px; overflow: hidden; width: fit-content; }
    .qty-btn { width: 38px; height: 42px; display: flex; align-items: center; justify-content: center; background: #f9fafb; border: none; cursor: pointer; font-size: 18px; color: #374151; transition: background .15s; flex-shrink: 0; }
    .qty-btn:hover { background: #ea580c; color: #fff; }
    .qty-input { width: 60px; height: 42px; text-align: center; border: none; outline: none; font-family: inherit; font-size: 15px; font-weight: 600; background: transparent; }

    /* ── Add to cart button ── */
    .btn-cart {
      width: 100%; padding: 14px; border-radius: 12px; border: none;
      background: #ea580c; color: #fff; font-family: inherit;
      font-size: 15px; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: all .2s; box-shadow: 0 4px 14px rgba(234,88,12,.3);
    }
    .btn-cart:hover:not(:disabled) { background: #c2410c; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(234,88,12,.35); }
    .btn-cart:active:not(:disabled) { transform: translateY(0); }
    .btn-cart:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }

    /* ── Trust features ── */
    .trust-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
    .trust-item svg { color: #16a34a; flex-shrink: 0; }
    .trust-item span { font-size: 13.5px; color: #374151; font-weight: 500; }

    /* ── Delivery notice ── */
    .delivery-notice {
      background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
      padding: 12px 16px; display: flex; align-items: flex-start; gap: 10px;
    }

    /* ── Price display ── */
    .price-main { font-size: 32px; font-weight: 800; color: #111827; line-height: 1; }
    .price-original { font-size: 18px; color: #9ca3af; text-decoration: line-through; margin-left: 8px; }
    .price-save { font-size: 13px; font-weight: 700; color: #16a34a; margin-left: 8px; }

    /* ── Tabs ── */
    .tab-btn {
      padding: 12px 20px; font-size: 14px; font-weight: 600; color: #6b7280;
      border: none; background: transparent; cursor: pointer;
      border-bottom: 2.5px solid transparent; transition: all .2s; font-family: inherit;
    }
    .tab-btn.active { color: #ea580c; border-bottom-color: #ea580c; }
    .tab-btn:hover:not(.active) { color: #374151; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ── Related product cards ── */
    .rel-card { background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid #f0f0f0; transition: all .25s; }
    .rel-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,.1); }
    .rel-card img { width: 100%; height: 160px; object-fit: cover; }

    /* ── Difficulty badges ── */
    .diff-easy   { background: #dcfce7; color: #166534; }
    .diff-medium { background: #fef3c7; color: #92400e; }
    .diff-hard   { background: #fee2e2; color: #991b1b; }

    /* ── Lightbox ── */
    #galleryLightbox {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.88); z-index: 9999;
      align-items: center; justify-content: center; cursor: zoom-out;
    }
    #lightboxImg { max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 12px; }

    /* hide spinners */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

    /* Hide scrollbar on thumb strip */
    .gallery-thumb-strip::-webkit-scrollbar { display: none; }

  </style>
</head>
<body>

<?php include('./components/navigation.php'); ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <!-- Breadcrumb -->
  <?php include('./components/nav_crumb.php'); ?>

  <!-- ═══════════════════════════════════════
       MAIN PRODUCT SECTION
  ═══════════════════════════════════════ -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="grid lg:grid-cols-2 gap-10">

      <!-- LEFT: Gallery -->
      <div class="flex flex-col gap-3">
 
        <!-- Main image -->
        <div id="mainImageWrap" style="position:relative">
          <?php if (!$hasStock): ?>
          <div style="position:absolute;inset:0;z-index:10;display:flex;align-items:center;justify-content:center;pointer-events:none">
            <span style="background:#dc2626;color:#fff;font-weight:700;font-size:14px;padding:6px 20px;border-radius:6px;transform:rotate(-12deg);letter-spacing:.05em">OUT OF STOCK</span>
          </div>
          <?php endif; ?>

          <img id="mainImage"
               src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
               alt="<?= htmlspecialchars($product['product_name']) ?>"
               style="<?= !$hasStock ? 'opacity:.55;' : '' ?>">

          <!-- Expand -->
          <button onclick="openGalleryLightbox()" title="View full size"
                  style="position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:9px;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.1)">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
          </button>

          <!-- Prev/Next -->
          <?php if (count($images) > 1): ?>
          <button onclick="stepGallery(-1)"
                  style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.08)">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <button onclick="stepGallery(1)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.08)">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
          </button>
          <div id="imgCounter" style="position:absolute;bottom:10px;right:10px;font-size:11px;color:#6b7280;background:#fff;padding:3px 10px;border-radius:99px;border:1px solid #e5e7eb">
            1 / <?= count($images) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Thumbnail strip — horizontal on mobile, hidden on desktop (desktop uses vertical rail) -->
        <div class="flex gap-2 overflow-x-auto pb-1">
          <?php foreach ($images as $i => $img): ?>
          <div class="gallery-thumb flex-shrink-0 <?= $i === 0 ? 'active' : '' ?>"
               style="width:128px;height:128px"
               onclick="selectThumb(this, '<?= htmlspecialchars($img) ?>')">
            <img src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($img) ?>" alt="thumb <?= $i+1 ?>">
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- RIGHT: Product Info -->
      <div class="flex flex-col gap-4">

        <!-- Badges -->
        <div class="flex flex-wrap gap-2">
          <span class="badge-pill <?= $hasStock ? 'badge-instock' : 'badge-outstock' ?>">
            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
            <?= $hasStock ? 'In Stock' : 'Out of Stock' ?>
          </span>
          <?php foreach (array_slice($categories, 0, 2) as $cat): ?>
          <span class="badge-pill badge-cat"><?= htmlspecialchars($cat['category_name']) ?></span>
          <?php endforeach; ?>
        </div>

        <!-- Name -->
        <div>
          <h1 style="font-size:28px;font-weight:800;color:#111827;line-height:1.2;margin:0">
            <?= htmlspecialchars($product['product_name']) ?>
          </h1>
          <?php if (!empty($product['product_unit'])): ?>
          <p style="font-size:14px;color:#6b7280;margin-top:4px"><?= htmlspecialchars($product['product_unit']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Description excerpt -->
        <?php if (!empty($product['product_description'])): ?>
        <p style="font-size:14.5px;color:#4b5563;line-height:1.65;margin:0">
          <?= htmlspecialchars($product['product_description']) ?>
        </p>
        <?php endif; ?>

        <hr style="border:none;border-top:1px solid #f0f0f0">

        <!-- Price -->
        <div id="priceDisplay" style="display:flex;align-items:baseline;flex-wrap:wrap;gap:4px">
          <?php if ($firstInStockVariant): 
            $dp = (float)$firstInStockVariant['discount_price'];
            $vp = (float)$firstInStockVariant['variant_price'];
            $mo = (float)$firstInStockVariant['minimum_order'];
            $showDiscount = $dp > 0 && $dp < $vp;
            $displayPrice = $showDiscount ? $dp : $vp;
          ?>
          <span class="price-main">₱<?= number_format($displayPrice * $mo, 2) ?></span>
          <?php if ($showDiscount): ?>
          <span class="price-original">₱<?= number_format($vp * $mo, 2) ?></span>
          <span class="price-save">Save <?= round((($vp - $dp) / $vp) * 100) ?>%</span>
          <?php endif; ?>
          <?php else: ?>
          <span style="font-size:20px;color:#9ca3af;font-weight:600">Price unavailable</span>
          <?php endif; ?>
        </div>
        <?php if ($firstInStockVariant): ?>
        <p id="pricePerUnit" style="font-size:12.5px;color:#6b7280;margin:-8px 0 0">
          ₱<?= number_format(($firstInStockVariant['discount_price'] > 0 ? $firstInStockVariant['discount_price'] : $firstInStockVariant['variant_price']), 2) ?> per <?= htmlspecialchars($firstInStockVariant['unit_type']) ?>
          &nbsp;·&nbsp; Min order: <?= $firstInStockVariant['minimum_order'] ?> <?= $firstInStockVariant['unit_type'] === 'piece' ? 'pcs' : $firstInStockVariant['unit_type'] ?>
        </p>
        <?php endif; ?>

        <!-- Trust features -->
        <div style="border:1px solid #f0f0f0;border-radius:12px;padding:8px 16px">
          <div class="trust-item" style="border-bottom:1px solid #f9fafb">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Fresh from Navotas Fish Port daily</span>
          </div>
          <div class="trust-item" style="border-bottom:1px solid #f9fafb">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Cold chain delivery — temperature maintained</span>
          </div>
          <div class="trust-item" style="border-bottom:1px solid #f9fafb">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Quality guaranteed — satisfaction or refund</span>
          </div>
          <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Directly from local Filipino fishermen</span>
          </div>
        </div>

        <?php if ($hasStock): ?>
        <!-- Variant selection -->
        <?php if (!empty($variants)): ?>
        <div>
          <p style="font-size:13px;font-weight:600;color:#374151;margin-bottom:8px">Select Variant</p>
          <div style="display:flex;flex-wrap:wrap;gap:8px" id="variantBtns">
            <?php foreach ($variants as $vid => $v): ?>
            <button type="button"
                    class="variant-btn <?= ($firstInStockVariant && $vid == $firstInStockVariant['variant_id']) ? 'selected-variant' : '' ?>"
                    data-variant-id="<?= $vid ?>"
                    data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                    data-variant-price="<?= $v['variant_price'] ?>"
                    data-discount-price="<?= $v['discount_price'] ?>"
                    data-unit-type="<?= $v['unit_type'] ?>"
                    data-minimum-order="<?= $v['minimum_order'] ?>"
                    data-order-increment="<?= $v['order_increment'] ?>"
                    data-stock-quantity="<?= $v['stock_quantity'] ?>"
                    data-has-stock="<?= $v['has_stock'] ? 'true' : 'false' ?>"
                    <?= !$v['has_stock'] ? 'disabled' : '' ?>>
              <?= htmlspecialchars($v['variant_name']) ?>
              <?php if (!$v['has_stock']): ?> <span style="font-size:10px;opacity:.7">(No Stock)</span><?php endif; ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Add to cart form -->
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

          <!-- Quantity -->
          <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
            <div>
              <p style="font-size:13px;font-weight:600;color:#374151;margin-bottom:8px">Quantity</p>
              <div class="qty-wrap">
                <button type="button" class="qty-btn decrease-quantity">−</button>
                <input type="number" class="qty-input quantity" value="" min="" step="">
                <button type="button" class="qty-btn increase-quantity">+</button>
              </div>
            </div>
            <div style="padding-top:22px">
              <span style="font-size:13px;color:#6b7280" class="unit-display"></span>
            </div>
          </div>
          <p style="font-size:12px;color:#9ca3af;margin:-8px 0 12px" class="minimum-order-text"></p>

          <!-- Buttons row -->
          <div style="display:flex;gap:10px">
            <button type="submit" name="add_to_cart" class="btn-cart">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                <path d="M17 17h-11v-14h-2"/>
                <path d="M6 5l14 1l-1 7h-13"/>
              </svg>
              <span id="cartBtnLabel">Add to Cart</span>
            </button>

            <!-- Share buttons -->
            <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')" title="Share on Facebook"
                    style="width:46px;flex-shrink:0;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s"
                    onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background='#fff'">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#fbgItem)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="fbgItem" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
            </button>
            <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')" title="Share"
                    style="width:46px;flex-shrink:0;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M8 9h-1a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1"/><path d="M12 14v-11"/><path d="M9 6l3-3 3 3"/></svg>
            </button>
          </div>

          <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
          <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
          <p class="text-red-500 text-sm mt-2 stock-error-message hidden"></p>
        </form>

        <!-- Delivery notice -->
        <div class="delivery-notice">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          <div>
            <p style="font-size:13px;font-weight:700;color:#92400e">Order now for fast delivery</p>
            <p style="font-size:12px;color:#b45309;margin-top:2px">Fresh seafood packed and dispatched from Navotas daily</p>
          </div>
        </div>

        <?php else: ?>
        <!-- Out of stock state -->
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;text-align:center">
          <p style="color:#b91c1c;font-weight:700;margin:0 0 6px">Currently Out of Stock</p>
          <p style="color:#6b7280;font-size:13px;margin:0">Check back soon or browse other products.</p>
        </div>
        <div style="display:flex;gap:10px">
          <button onclick="shareToFacebook('<?= $shareUrlNew ?>')" type="button"
                  style="flex:1;padding:13px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-size:14px;font-weight:600;color:#374151;font-family:inherit">
            Share on Facebook
          </button>
        </div>
        <?php endif; ?>

      </div><!-- /right -->
    </div><!-- /grid -->
  </div>

  <!-- ═══════════════════════════════════════
       TABS: Description / Cooking Suggestions
  ═══════════════════════════════════════ -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">
    <!-- Tab bar -->
    <div style="display:flex;border-bottom:1px solid #f0f0f0;padding:0 20px;gap:4px">
      <button class="tab-btn active" onclick="switchTab('tabDesc', this)">Description</button>
      <?php if (!empty($cookingSuggestions)): ?>
      <button class="tab-btn" onclick="switchTab('tabCooking', this)">
        Cooking Suggestions
        <span style="background:#ea580c;color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:99px;margin-left:6px"><?= count($cookingSuggestions) ?></span>
      </button>
      <?php endif; ?>
      <?php if (!empty($product['product_nickname'])): ?>
      <button class="tab-btn" onclick="switchTab('tabTags', this)">Tags</button>
      <?php endif; ?>
    </div>

    <div style="padding:28px">

      <!-- Tab: Description -->
      <div id="tabDesc" class="tab-panel active">
        <div class="grid md:grid-cols-2 gap-10">
          <div>
            <h2 style="font-size:20px;font-weight:800;color:#111827;margin:0 0 14px">
              About <?= htmlspecialchars($product['product_name']) ?>
            </h2>
            <?php if (!empty($product['product_description'])): ?>
            <p style="color:#4b5563;line-height:1.75;font-size:14.5px">
              <?= nl2br(htmlspecialchars($product['product_description'])) ?>
            </p>
            <?php else: ?>
            <p style="color:#9ca3af;font-size:14px">No description available for this product.</p>
            <?php endif; ?>

            <!-- Categories detail -->
            <?php if (!empty($categories)): ?>
            <div style="margin-top:20px">
              <h3 style="font-size:14px;font-weight:700;color:#374151;margin-bottom:10px">Categories</h3>
              <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($categories as $cat): ?>
                <a href="<?= $baseUrl ?>account/shop.php?category=<?= urlencode($cat['category_slug']) ?>"
                   style="padding:5px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9999px;font-size:12.5px;color:#374151;text-decoration:none;transition:all .15s"
                   onmouseover="this.style.borderColor='#fb923c';this.style.color='#ea580c'"
                   onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">
                  <?= htmlspecialchars($cat['category_name']) ?>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Trust cards -->
          <div style="display:grid;gap:12px">
            <?php
            $trustItems = [
              ['🐟', 'Fresh Daily',        'Sourced directly from Navotas Fish Port every morning.'],
              ['❄️', 'Cold Chain',          'Temperature-maintained from catch to your doorstep.'],
              ['✅', 'Quality Guaranteed',  '100% satisfaction — refund if you\'re not happy.'],
              ['🤝', 'Supports Fishermen',  'Every purchase goes directly to local Filipino fishermen.'],
            ];
            foreach ($trustItems as $t): ?>
            <div style="display:flex;gap:12px;padding:14px;background:#f9fafb;border-radius:12px;border:1px solid #f0f0f0">
              <span style="font-size:22px;line-height:1;flex-shrink:0"><?= $t[0] ?></span>
              <div>
                <p style="font-size:13.5px;font-weight:700;color:#111827;margin:0 0 3px"><?= $t[1] ?></p>
                <p style="font-size:12.5px;color:#6b7280;margin:0"><?= $t[2] ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Tab: Cooking Suggestions -->
      <?php if (!empty($cookingSuggestions)): ?>
      <div id="tabCooking" class="tab-panel">
        <h2 style="font-size:20px;font-weight:800;color:#111827;margin:0 0 20px">
          How to Cook <?= htmlspecialchars($product['product_name']) ?>
        </h2>
        <div class="grid md:grid-cols-2 gap-6">
          <?php foreach ($cookingSuggestions as $sug): ?>
          <div style="border:1px solid #f0f0f0;border-radius:14px;overflow:hidden">
            <!-- Card header -->
            <div style="padding:16px 18px;border-bottom:1px solid #f9fafb;display:flex;align-items:center;justify-content:space-between;background:#fafafa">
              <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0"><?= htmlspecialchars($sug['dish_name']) ?></h3>
              <span style="padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700"
                    class="diff-<?= strtolower($sug['difficulty_level']) ?>">
                <?= $sug['difficulty_level'] ?>
              </span>
            </div>
            <div style="padding:16px 18px">
              <!-- Time info -->
              <?php if (!empty($sug['prep_time_minutes']) || !empty($sug['cook_time_minutes'])): ?>
              <div style="display:flex;gap:16px;margin-bottom:14px">
                <?php if (!empty($sug['prep_time_minutes'])): ?>
                <span style="font-size:12.5px;color:#6b7280;display:flex;align-items:center;gap:5px">
                  <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                  Prep: <?= $sug['prep_time_minutes'] ?> min
                </span>
                <?php endif; ?>
                <?php if (!empty($sug['cook_time_minutes'])): ?>
                <span style="font-size:12.5px;color:#6b7280;display:flex;align-items:center;gap:5px">
                  <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                  Cook: <?= $sug['cook_time_minutes'] ?> min
                </span>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              <!-- Ingredients -->
              <div style="margin-bottom:12px">
                <p style="font-size:12.5px;font-weight:700;color:#374151;margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em">Ingredients</p>
                <p style="font-size:13px;color:#6b7280;line-height:1.65;margin:0"><?= nl2br(htmlspecialchars($sug['ingredients'])) ?></p>
              </div>

              <!-- Steps -->
              <div>
                <p style="font-size:12.5px;font-weight:700;color:#374151;margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em">Instructions</p>
                <?php foreach (array_filter(explode("\n", $sug['steps']), 'trim') as $si => $step): ?>
                <div style="display:flex;gap:10px;margin-bottom:8px">
                  <span style="width:22px;height:22px;border-radius:50%;background:#ea580c;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px"><?= $si+1 ?></span>
                  <span style="font-size:13px;color:#4b5563;line-height:1.55"><?= htmlspecialchars($step) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tab: Tags -->
      <?php if (!empty($product['product_nickname'])): ?>
      <div id="tabTags" class="tab-panel">
        <h2 style="font-size:18px;font-weight:800;color:#111827;margin:0 0 14px">Also known as</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
          <?php
          $tags = json_decode($product['product_nickname'], true);
          if (is_array($tags)) {
            foreach ($tags as $tag) {
              echo '<span style="padding:8px 18px;background:#fff7ed;border:1px solid #fed7aa;border-radius:9999px;font-size:13.5px;color:#c2410c;font-weight:600">#' . htmlspecialchars($tag) . '</span>';
            }
          }
          ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- ═══════════════════════════════════════
       RELATED PRODUCTS
  ═══════════════════════════════════════ -->
  <?php if (!empty($relatedProducts)): ?>
  <div class="mb-10">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
      <h2 style="font-size:20px;font-weight:800;color:#111827;margin:0">You May Also Like</h2>
      <a href="<?= $baseUrl ?>account/shop.php" style="font-size:13px;color:#ea580c;font-weight:600;text-decoration:none">Browse all →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      <?php foreach ($relatedProducts as $rel):
        $relHasStock = $rel['has_stock'] > 0;
        $relImg = !empty($rel['primary_image'])
            ? $baseUrl . 'uploads/products/' . $rel['primary_image']
            : $baseUrl . 'uploads/products/default.png';
        $relUrl = $baseUrl . 'item/' . strtolower(str_replace(' ', '-', $rel['product_name']));
      ?>
      <a href="<?= $relUrl ?>" class="rel-card" style="text-decoration:none;<?= !$relHasStock ? 'opacity:.6' : '' ?>">
        <div style="position:relative">
          <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['product_name']) ?>">
          <?php if (!$relHasStock): ?>
          <div style="position:absolute;inset:0;background:rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center">
            <span style="background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px">Out of Stock</span>
          </div>
          <?php endif; ?>
        </div>
        <div style="padding:10px 12px 12px">
          <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 3px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= htmlspecialchars($rel['product_name']) ?>
          </p>
          <?php if (!empty($rel['min_price'])): ?>
          <p style="font-size:13px;font-weight:700;color:#ea580c;margin:0">
            from ₱<?= number_format($rel['min_price'], 0) ?>
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
<div id="galleryLightbox" onclick="closeGalleryLightbox()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out">
  <img id="lightboxImg" src="" alt="full size" style="max-width:90vw;max-height:90vh;object-fit:contain;border-radius:12px">
</div>

<?php include('./components/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

  var itemForm     = document.querySelector('.add-to-cart-form');
  var variantBtns  = document.querySelectorAll('.variant-btn');

  // ── Variant selection ────────────────────────────────────────────────────
  variantBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      variantBtns.forEach(function (b) { b.classList.remove('selected-variant'); });
      btn.classList.add('selected-variant');
      applyVariant(btn);
    });
  });

  function applyVariant(btn) {
    if (!itemForm) return;
    var vp  = parseFloat(btn.dataset.variantPrice);
    var dp  = parseFloat(btn.dataset.discountPrice);
    var mo  = parseFloat(btn.dataset.minimumOrder);
    var oi  = parseFloat(btn.dataset.orderIncrement);
    var ut  = btn.dataset.unitType;
    var hs  = btn.dataset.hasStock === 'true';
    var effectivePrice = (dp > 0 && dp < vp) ? dp : vp;

    itemForm.querySelector('[name="variant_id"]').value      = btn.dataset.variantId;
    itemForm.querySelector('[name="variant_name"]').value    = btn.dataset.variantName;
    itemForm.querySelector('[name="price"]').value           = effectivePrice;
    itemForm.querySelector('[name="unit_type"]').value       = ut;
    itemForm.querySelector('[name="minimum_order"]').value   = mo;
    itemForm.querySelector('[name="order_increment"]').value = oi;

    var qtyEl = itemForm.querySelector('.quantity');
    qtyEl.value = ut === 'piece' ? Math.round(mo) : mo;
    qtyEl.min   = mo;
    qtyEl.step  = oi;
    itemForm.querySelector('[name="quantity"]').value = mo;

    var unitDisp = ut === 'piece' ? 'pcs' : ut;
    itemForm.querySelector('.unit-display').textContent       = unitDisp;
    itemForm.querySelector('.minimum-order-text').textContent = 'Minimum: ' + mo + ' ' + unitDisp;

    // Update price display
    updatePriceDisplay(vp, dp, mo);

    // Update per-unit text
    var perUnit = document.getElementById('pricePerUnit');
    if (perUnit) perUnit.textContent = '₱' + effectivePrice.toFixed(2) + ' per ' + ut + ' · Min order: ' + mo + ' ' + unitDisp;

    // Update cart button label
    var label = document.getElementById('cartBtnLabel');
    if (label) {
      var total = effectivePrice * mo;
      label.textContent = 'Add to Cart — ₱' + total.toFixed(2);
    }

    var submitBtn = itemForm.querySelector('[name="add_to_cart"]');
    if (submitBtn) submitBtn.disabled = !hs;

    itemForm.querySelector('.variant-message')?.classList.add('hidden');
    itemForm.querySelector('.minimum-error-message')?.classList.add('hidden');
    itemForm.querySelector('.stock-error-message')?.classList.add('hidden');
  }

  function updatePriceDisplay(vp, dp, qty) {
    var priceEl = document.getElementById('priceDisplay');
    if (!priceEl) return;
    var showDisc = dp > 0 && dp < vp;
    var effective = showDisc ? dp : vp;
    var total = effective * qty;
    if (showDisc) {
      var pct = Math.round(((vp - dp) / vp) * 100);
      priceEl.innerHTML =
        '<span class="price-main">₱' + total.toFixed(2) + '</span>' +
        '<span class="price-original">₱' + (vp * qty).toFixed(2) + '</span>' +
        '<span class="price-save">Save ' + pct + '%</span>';
    } else {
      priceEl.innerHTML = '<span class="price-main">₱' + total.toFixed(2) + '</span>';
    }
  }

  function updateTotalFromQty() {
    var sel = document.querySelector('.variant-btn.selected-variant');
    if (!sel) return;
    var qty = parseFloat(itemForm.querySelector('.quantity').value) || 0;
    var vp  = parseFloat(sel.dataset.variantPrice);
    var dp  = parseFloat(sel.dataset.discountPrice);
    updatePriceDisplay(vp, dp, qty);
    var effective = (dp > 0 && dp < vp) ? dp : vp;
    var label = document.getElementById('cartBtnLabel');
    if (label) label.textContent = 'Add to Cart — ₱' + (effective * qty).toFixed(2);
  }

  // ── Quantity controls ────────────────────────────────────────────────────
  if (itemForm) {
    itemForm.querySelector('.decrease-quantity')?.addEventListener('click', function () {
      var qi = itemForm.querySelector('.quantity');
      var mo = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
      var oi = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
      var nv = Math.max(mo, parseFloat(qi.value) - oi);
      qi.value = nv;
      itemForm.querySelector('[name="quantity"]').value = nv;
      updateTotalFromQty();
    });

    itemForm.querySelector('.increase-quantity')?.addEventListener('click', function () {
      var qi = itemForm.querySelector('.quantity');
      var oi = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
      var nv = parseFloat(qi.value) + oi;
      qi.value = nv;
      itemForm.querySelector('[name="quantity"]').value = nv;
      updateTotalFromQty();
    });

    itemForm.querySelector('.quantity')?.addEventListener('input', function () {
      itemForm.querySelector('[name="quantity"]').value = parseFloat(this.value) || 0;
      updateTotalFromQty();
    });

    itemForm.querySelector('.quantity')?.addEventListener('change', function () {
      var mo = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
      var oi = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
      var val = parseFloat(this.value);
      if (isNaN(val) || val < mo) val = mo;
      val = mo + Math.round((val - mo) / oi) * oi;
      this.value = val;
      itemForm.querySelector('[name="quantity"]').value = val;
      updateTotalFromQty();
    });

    // ── Submit ───────────────────────────────────────────────────────────
    itemForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      var variantId    = itemForm.querySelector('[name="variant_id"]').value;
      var quantity     = parseFloat(itemForm.querySelector('[name="quantity"]').value);
      var minimumOrder = parseFloat(itemForm.querySelector('[name="minimum_order"]').value);
      var unitType     = itemForm.querySelector('[name="unit_type"]').value;
      var selVariant   = itemForm.querySelector('.variant-btn.selected-variant');
      var stockQty     = selVariant ? parseInt(selVariant.dataset.stockQuantity) : Infinity;

      if (!variantId) { itemForm.querySelector('.variant-message')?.classList.remove('hidden'); return; }
      if (quantity < minimumOrder) {
        var em = itemForm.querySelector('.minimum-error-message');
        em.textContent = 'Minimum order is ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);
        em.classList.remove('hidden');
        return;
      }
      if (quantity > stockQty) {
        var se = itemForm.querySelector('.stock-error-message');
        se.textContent = 'Only ' + stockQty + ' ' + (unitType === 'piece' ? 'pcs' : unitType) + ' available';
        se.classList.remove('hidden');
        return;
      }
      try {
        var res  = await fetch((window.CART_BASE || '/sjfbi-js') + '/functions/add_to_cart.php', {
          method: 'POST', body: new FormData(itemForm)
        });
        var data = await res.json();
        if (data.status === 'success') {
          showToast('Added to cart!', 'success');
          await refreshCartFromServer();
          var first = document.querySelector('.variant-btn[data-has-stock="true"]');
          if (first) first.click();
        } else {
          showToast(data.message || 'Failed to add', 'error');
        }
      } catch (err) {
        showToast('An error occurred.', 'error');
      }
    });
  }

  // ── Auto-select first in-stock variant ───────────────────────────────────
  var firstActive = document.querySelector('.variant-btn[data-has-stock="true"]');
  if (firstActive) firstActive.click();

});

// ── Gallery ──────────────────────────────────────────────────────────────────
var _imgs     = <?= json_encode(array_values($images)) ?>;
var _base     = '<?= $baseUrl ?>';
var _current  = 0;

function selectThumb(el, img) {
  document.querySelectorAll('.gallery-thumb').forEach(function (t, i) {
    var isActive = t === el;
    t.classList.toggle('active', isActive);
    if (isActive) _current = i;
  });
  document.getElementById('mainImage').src = _base + 'uploads/products/' + img;
  var ctr = document.getElementById('imgCounter');
  if (ctr) ctr.textContent = (_current + 1) + ' / ' + _imgs.length;
}

function stepGallery(dir) {
  _current = (_current + dir + _imgs.length) % _imgs.length;
  var thumbs = document.querySelectorAll('.gallery-thumb');
  thumbs.forEach(function (t, i) { t.classList.toggle('active', i === _current); });
  document.getElementById('mainImage').src = _base + 'uploads/products/' + _imgs[_current];
  var ctr = document.getElementById('imgCounter');
  if (ctr) ctr.textContent = (_current + 1) + ' / ' + _imgs.length;
  thumbs[_current]?.scrollIntoView({ block: 'nearest' });
}

function openGalleryLightbox() {
  document.getElementById('lightboxImg').src = document.getElementById('mainImage').src;
  var lb = document.getElementById('galleryLightbox');
  lb.style.display = 'flex';
}
function closeGalleryLightbox() {
  document.getElementById('galleryLightbox').style.display = 'none';
}

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape')      closeGalleryLightbox();
  if (e.key === 'ArrowLeft')   stepGallery(-1);
  if (e.key === 'ArrowRight')  stepGallery(1);
});

// ── Tabs ──────────────────────────────────────────────────────────────────────
function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
  document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
  document.getElementById(id)?.classList.add('active');
  btn.classList.add('active');
}

// ── Share ─────────────────────────────────────────────────────────────────────
function shareToFacebook(url) {
  window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
}
function shareProduct(title, text, url) {
  if (navigator.share) {
    navigator.share({ title, text, url }).catch(function (err) { if (err.name !== 'AbortError') _copyLink(url); });
  } else { _copyLink(url); }
}
function _copyLink(url) {
  navigator.clipboard?.writeText(url)
    .then(function () { showToast('Link copied!', 'success'); })
    .catch(function () { showToast('Could not copy link', 'error'); });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
</body>
</html>