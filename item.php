<?php
include 'conn.php'; // Database connection

// Get the base URL for your site - fixed path
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

// Get product name from URL and convert back from URL format
$productName = isset($_GET['name']) ? urldecode(str_replace('-', ' ', $_GET['name'])) : '';

if (empty($productName)) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

// Fetch product by name with its variants, images, AND CATEGORIES
$query = "SELECT p.*, 
                 pv.variant_id, pv.variant_name, pv.variant_price, pv.discount_price, 
                 pv.unit_type, pv.minimum_order, pv.order_increment,
                 pi.image_path, pi.is_primary,
                 pc.category_id, pc.category_name, pc.category_slug,
                 pc2.category_id as parent_category_id, 
                 pc2.category_name as parent_category_name,
                 pc2.category_slug as parent_category_slug
          FROM products p
          LEFT JOIN product_variants pv ON p.product_id = pv.product_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id
          LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
          LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
          LEFT JOIN product_categories pc2 ON pc.parent_id = pc2.category_id
          WHERE LOWER(REPLACE(p.product_name, ' ', '-')) = LOWER(REPLACE(?, ' ', '-'))
          AND p.is_deleted = 0
          ORDER BY p.product_id, pv.variant_id, pi.is_primary DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $productName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

// Process the result set
$product = null;
$variants = [];
$images = [];
$primaryImage = null;
$categories = [];
$primaryCategory = null;

while ($row = $result->fetch_assoc()) {
    if ($product === null) {
        $product = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_unit' => $row['product_unit'],
            'product_description' => $row['product_description'],
            'product_nickname' => $row['product_nickname']
        ];
    }
    
    // Handle variants
    if ($row['variant_id'] && !isset($variants[$row['variant_id']])) {
        $variants[$row['variant_id']] = [
            'variant_name' => $row['variant_name'],
            'variant_price' => $row['variant_price'],
            'discount_price' => $row['discount_price'],
            'unit_type' => $row['unit_type'],
            'minimum_order' => $row['minimum_order'],
            'order_increment' => $row['order_increment'],
        ];
    }
    
    // Handle images
    if ($row['image_path']) {
        $images[$row['image_path']] = $row['image_path']; // Use array key to prevent duplicates
        if ($row['is_primary']) {
            $primaryImage = $row['image_path'];
        }
    }
    
    // Handle categories
    if ($row['category_id'] && !isset($categories[$row['category_id']])) {
        $categories[$row['category_id']] = [
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'category_slug' => $row['category_slug'],
            'parent_id' => $row['parent_category_id'],
            'parent_name' => $row['parent_category_name'],
            'parent_slug' => $row['parent_category_slug']
        ];
        
        // Set primary category (first one)
        if ($primaryCategory === null) {
            $primaryCategory = $categories[$row['category_id']];
        }
    }
}

// If no primary image but other images exist, use the first one
if (empty($primaryImage) && !empty($images)) {
    $primaryImage = reset($images);
}

// If no images at all, use default.png
if (empty($images)) {
    $primaryImage = 'default.png';
    $images = ['default.png'];
} else {
    $images = array_values($images); // Reset array keys
}

// After your existing code, define the share URLs properly
$canonicalUrl = $baseUrl . "item/" . strtolower(str_replace(' ', '-', $product['product_name']));

$shareUrlNew = $canonicalUrl; // Clean URL format for sharing
$shareTitle = $product['product_name'];
$shareText = "Check out this fresh seafood: " . $product['product_name'] . " from St. Joseph Fish Brokerage Inc.";
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

  <link rel="shortcut icon" href="<?= $baseUrl ?>/assets/icons/logo.ico">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="<?= $baseUrl ?>style.css" rel="stylesheet">
  <link href="<?= $baseUrl ?>output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>

<body>

<style>
.variant-button {
    background-color: white;
    border: 1px solid #d1d5db;
    color: #374151;
    transition: all 0.2s ease;
}

.variant-button.selected-variant {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #FFFFFF;
}

/* Add to cart button disabled state */
button[name="add_to_cart"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

button[name="add_to_cart"]:not(:disabled) {
    opacity: 1;
    cursor: pointer;
}

/* Quantity buttons */
.decrease-quantity, .increase-quantity {
    background-color: white;
    border: 1px solid #d1d5db;
    color: #374151;
    transition: all 0.2s ease;
}

.decrease-quantity:hover, .increase-quantity:hover {
    background-color: #f59e0b;
    color: white;
    border-color: #f97316;
}


/* Toast Container */
#toastContainer {
    position: fixed;
    bottom: 5rem;
    right: 1rem;
    z-index: 60;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Toast Styles */
.toast {
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    font-size: 0.95rem;
    font-weight: 600;
    text-align: left;
    min-width: 280px;
    max-width: 400px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease-out, fadeOut 0.3s ease-in 2.7s forwards;
    color: white;
}

/* Green background for added to cart */
.toast.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

/* Red background for removed from cart */
.toast.remove {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

/* Dark red for errors */
.toast.error {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
}

/* Toast Icons */
.toast-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.toast-message {
    flex: 1;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100px);
    }
}

.animate-bounce {
    animation: bounce 0.5s;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.5); }
}
</style>

<!-- Hero Section -->
<section id="home-section">
  <?php include('./components/navigation.php'); ?>

  <div class="max-w-7xl px-4 sm:px-6 lg:px-8 mx-auto mt-10">
    <!-- Breadcrumbs -->
    <div class="mb-4 text-gray-600 flex">
      <a href="<?= $baseUrl ?>" class="hover:text-orange-500">Home</a>
        <svg xmlns="shrink-0 mx-3 text-gray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
      <span class="text-gray-800"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <!-- TOAST CONTAINER HERE -->
    <div id="toastContainer"></div>

    <div class="grid md:grid-cols-3 gap-4 shadow-lg">
      <div class="md:col-span-1 p-4 rounded-3xl">
        <!-- Product Image & Thumbnails -->
        <div class="flex flex-col items-center">
            <!-- Main Image -->
            <div class="max-w-xl rounded-lg relative">
                <img id="mainImage"
                    src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
                    class="rounded-lg shadow-md"
                    width="250px"
                    alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>

            <!-- Thumbnails - Only show if we have more than one image -->
            <?php if (count($images) > 1): ?>
                <div class="flex justify-center space-x-3 mt-4 overflow-x-auto">
                    <?php foreach ($images as $image): ?>
                        <img src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($image) ?>"
                            class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:border-gray-500"
                            onclick="changeImage('<?= htmlspecialchars($image) ?>')"
                            alt="<?= htmlspecialchars($product['product_name']) ?> - Thumbnail">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
      </div>

      <!-- Right Side: Product Details -->
      <div class="md:col-span-1 p-4 rounded-3xl">
        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p class="mt-2 text-gray-600"><?= htmlspecialchars($product['product_unit']) ?></p>

        <!-- Add to Cart Form -->
        <form class="add-to-cart-form" data-product-id="<?= $product['product_id'] ?>">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="variant_id" value="">
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>">
            <input type="hidden" name="variant_name" value="">
            <input type="hidden" name="price" value="">
            <input type="hidden" name="image_url" value="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>">
            <input type="hidden" name="quantity" value="">
            <input type="hidden" name="unit_type" value="">
            <input type="hidden" name="minimum_order" value="">
            <input type="hidden" name="order_increment" value="">

            <!-- Variant Buttons -->
            <?php if (!empty($variants)): ?>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $first = true;
                    foreach ($variants as $variant_id => $variant) { ?>
                        <button type="button"
                            class="variant-button px-3 py-2 border rounded-lg text-sm font-medium 
                                hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200 text-dark 
                                <?= $first ? 'selected-variant' : '' ?>"
                            data-product-id="<?= $product['product_id'] ?>"
                            data-variant-id="<?= $variant_id ?>"
                            data-variant-name="<?= htmlspecialchars($variant['variant_name']) ?>"
                            data-variant-price="<?= $variant['variant_price'] ?>"
                            data-discount-price="<?= $variant['discount_price'] ?>"
                            data-unit-type="<?= $variant['unit_type'] ?>"
                            data-minimum-order="<?= $variant['minimum_order'] ?>"
                            data-order-increment="<?= $variant['order_increment'] ?>">
                            <?= htmlspecialchars($variant['variant_name']) ?>
                        </button>
                    <?php 
                        $first = false;
                    } ?>
                </div>
            </div>
            <?php else: ?>
                <p class="text-red-500 text-sm mt-2">No variants available for this product.</p>
            <?php endif; ?>

            <!-- Quantity Selector with Unit Display -->
            <div class="mt-3">
                <div class="flex items-center">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="" placeholder="1" readonly>
                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">+</button>
                    </div>
                    &nbsp;
                    <span class="ml-2 text-sm font-medium text-gray-600 unit-display"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
            </div>

            <!-- Price and Discount Display -->
            <div class="price-display mt-3"></div>

            <div class="mt-4 pt-4 border-t border-gray-200">
              <div class="flex gap-2">
                <!-- Add to Cart Button -->
                <button type="submit" name="add_to_cart" 
                        class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" title="Add to Cart" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                        Add to Cart
                </button>
                <!-- Facebook Share Button -->
                <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-white font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#paint0_linear_87_7208)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="paint0_linear_87_7208" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
                </button>
                
                <!-- General Share Button -->
                <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share on other platforms">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-share-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>                      
                </button>
              </div>
            </div>

            <!-- Message to select a variant -->
            <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
            <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
        </form>
      </div>
      <div class="md:col-span-1 p-4 rounded-3xl"> <!-- set a fixed height -->
        <!-- Product Image & Thumbnails -->
        <div class="flex flex-col h-full items-start"> <!-- full height for flex-grow -->
          <h2 class="text-xl font-bold text-gray-800">
            What is <?= htmlspecialchars($product['product_name']) ?></span>?
          </h2>
          <p class="text-gray-800 font-semibold mt-2">
            <?= htmlspecialchars($product['product_description']) ?>
          </p>

          <!-- Tags at the bottom -->
          <?php if (!empty($product['product_nickname'])): ?>
          <p class="mt-5 text-gray-600">
              <strong>Tags:</strong> 
              <?php 
              // Check if nickname is JSON
              $nickname = $product['product_nickname'];
              if (json_decode($nickname) !== null) {
                  // It's JSON, decode and implode
                  $tags = json_decode($nickname, true);
                  if (is_array($tags)) {
                      echo '"' . implode('", "', array_map('htmlspecialchars', $tags)) . '"';
                  } else {
                      echo htmlspecialchars($nickname);
                  }
              } else {
                  // It's plain text, just display as is
                  echo htmlspecialchars($nickname);
              }
              ?>
          </p>
          <?php endif; ?>
        </div>
      </div>


    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>

<script>
// Facebook Share function
function shareToFacebook(url) {
    const facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    window.open(facebookUrl, 'facebook-share', 'width=600,height=400,scrollbars=yes');
    return false;
}

// Web Share API function
function shareProduct() {
    const shareData = {
        title: <?= json_encode($shareTitle) ?>,
        text: <?= json_encode($shareText) ?>,
        url: <?= json_encode($shareUrlNew) ?>
    };

    if (navigator.share) {
        navigator.share(shareData)
            .catch(err => console.log('Share cancelled:', err));
    } else {
        // Fallback for unsupported browsers
        copyShareLink();
    }
}

function copyShareLink() {
    const url = <?= json_encode($shareUrlNew) ?>;
    navigator.clipboard.writeText(url)
        .then(() => {
            showToast('Link copied to clipboard!', 'success');
        })
        .catch(() => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('Link copied to clipboard!', 'success');
        });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle variant selection
    document.querySelectorAll('.variant-button').forEach(button => {
        button.addEventListener('click', function() {
            const productId = button.dataset.productId;
            const variantId = button.dataset.variantId;
            const variantName = button.dataset.variantName;
            const variantPrice = parseFloat(button.dataset.variantPrice);
            const discountPrice = parseFloat(button.dataset.discountPrice);
            const unitType = button.dataset.unitType;
            const minimumOrder = parseFloat(button.dataset.minimumOrder);
            const orderIncrement = parseFloat(button.dataset.orderIncrement);

            const form = document.querySelector('.add-to-cart-form');
            
            // Remove selected class from all variant buttons
            form.querySelectorAll('.variant-button').forEach(btn => {
                btn.classList.remove('selected-variant');
            });
            button.classList.add('selected-variant');

            // Update hidden fields
            form.querySelector('input[name="variant_id"]').value = variantId;
            form.querySelector('input[name="variant_name"]').value = variantName;
            form.querySelector('input[name="price"]').value = discountPrice > 0 ? discountPrice : variantPrice;
            form.querySelector('input[name="unit_type"]').value = unitType;
            form.querySelector('input[name="minimum_order"]').value = minimumOrder;
            form.querySelector('input[name="order_increment"]').value = orderIncrement;

            // Set quantity to minimum order
            const quantityInput = form.querySelector('.quantity');
            quantityInput.value = minimumOrder;
            form.querySelector('input[name="quantity"]').value = minimumOrder;

            // Update unit display
            const unitDisplay = form.querySelector('.unit-display');
            unitDisplay.textContent = unitType === 'piece' ? 'pcs' : unitType;

            // Update minimum order text
            const minOrderText = form.querySelector('.minimum-order-text');
            minOrderText.textContent = `Minimum: ${minimumOrder} ${unitType === 'piece' ? 'pcs' : unitType}`;

            // Update displayed price
            updatePriceDisplay(form, variantPrice, discountPrice, minimumOrder);

            // Enable "Add to Cart"
            form.querySelector('button[name="add_to_cart"]').disabled = false;
            form.querySelector('.variant-message').classList.add('hidden');
            form.querySelector('.minimum-error-message').classList.add('hidden');
        });
    });

    // Handle quantity changes
    document.querySelector('.decrease-quantity').addEventListener('click', function() {
        const form = document.querySelector('.add-to-cart-form');
        const quantityInput = form.querySelector('.quantity');
        const minimumOrder = parseFloat(form.querySelector('input[name="minimum_order"]').value);
        const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
        const currentQty = parseFloat(quantityInput.value);

        const newQty = Math.max(minimumOrder, currentQty - orderIncrement);
        quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
        form.querySelector('input[name="quantity"]').value = newQty;
        
        updateTotalPrice(form);
    });

    document.querySelector('.increase-quantity').addEventListener('click', function() {
        const form = document.querySelector('.add-to-cart-form');
        const quantityInput = form.querySelector('.quantity');
        const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
        const currentQty = parseFloat(quantityInput.value);

        const newQty = currentQty + orderIncrement;
        quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
        form.querySelector('input[name="quantity"]').value = newQty;
        
        updateTotalPrice(form);
    });

    function updatePriceDisplay(form, variantPrice, discountPrice, quantity) {
        const priceDisplay = form.querySelector('.price-display');
        const price = discountPrice > 0 ? discountPrice : variantPrice;
        const total = price * quantity;

        if (discountPrice > 0) {
            priceDisplay.innerHTML = `
                <span class="line-through text-gray-500 text-lg">₱${(variantPrice * quantity).toFixed(2)}</span>
                <span class="text-red-600 font-bold text-2xl ml-2">₱${total.toFixed(2)}</span>
            `;
        } else {
            priceDisplay.innerHTML = `
                <span class="text-gray-800 font-bold text-2xl">₱${total.toFixed(2)}</span>
            `;
        }
    }

    function updateTotalPrice(form) {
        const quantity = parseFloat(form.querySelector('.quantity').value);
        const price = parseFloat(form.querySelector('input[name="price"]').value);
        const variantPrice = parseFloat(form.querySelector('.selected-variant').dataset.variantPrice);
        const discountPrice = parseFloat(form.querySelector('.selected-variant').dataset.discountPrice);
        
        updatePriceDisplay(form, variantPrice, discountPrice, quantity);
    }

    // Auto-select first variant on load
    const firstButton = document.querySelector('.variant-button');
    if (firstButton) firstButton.click();

    // Enhanced add to cart handler with validation
    document.querySelector('.add-to-cart-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const form = this;
        const variantId = form.querySelector('input[name="variant_id"]').value;
        const quantity = parseFloat(form.querySelector('input[name="quantity"]').value);
        const minimumOrder = parseFloat(form.querySelector('input[name="minimum_order"]').value);
        const unitType = form.querySelector('input[name="unit_type"]').value;
        const errorMessage = form.querySelector('.minimum-error-message');

        if (!variantId) {
            form.querySelector('.variant-message').classList.remove('hidden');
            return;
        }

        if (quantity < minimumOrder) {
            errorMessage.textContent = `Minimum order is ${minimumOrder} ${unitType === 'piece' ? 'pcs' : unitType}`;
            errorMessage.classList.remove('hidden');
            return;
        }

        try {
            // Use absolute path to ensure it works from any URL
            const response = await fetch('/sjfbi-js/functions/add_to_cart.php', {
                method: 'POST',
                body: new FormData(form)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showToast('Product added to cart', 'success'); // GREEN TOAST
                await updateCartUI();
            } else {
                showToast(data.message || 'Failed to add product', 'error'); // DARK RED TOAST
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('An error occurred', 'error');
        }
    });

    async function updateCartUI() {
        try {
            // Use absolute path from root
            const response = await fetch('/sjfbi-js/functions/fetch_cart_items.php');
            
            // First check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Server returned: ${text}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'error') {
                throw new Error(data.message);
            }
            
            // Update cart items list
            if (data.cart_items) {
                document.getElementById('cart-items-list').innerHTML = data.cart_items;
            }
            
            // Update cart total
            if (data.cart_total !== undefined) {
                document.getElementById('cart-total-sidebar').textContent = `₱${data.cart_total.toFixed(2)}`;
            }
            
            // Update all cart count elements
            if (data.cart_count !== undefined) {
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = data.cart_count;
                    el.classList.add('animate-bounce');
                    setTimeout(() => el.classList.remove('animate-bounce'), 1000);
                });
            }
            
            // Reinitialize event handlers
            initCartEventHandlers();
        } catch (error) {
            console.error('Cart update error:', error);
            showToast(error.message || 'Error updating cart', 'error');
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            console.error('Toast container not found');
            return;
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add icon based on type
        let icon = '';
        if (type === 'success') {
            icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6L9 17l-5-5"/>
            </svg>`;
        } else if (type === 'remove') {
            icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>`;
        } else if (type === 'error') {
            icon = `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>`;
        }
        
        toast.innerHTML = `
            ${icon}
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        // Remove toast after animation completes
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});

function changeImage(imageName) {
    const mainImage = document.getElementById('mainImage');
    const fullPath = "<?= $baseUrl ?>uploads/products/" + imageName;
    mainImage.src = fullPath;
}
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>
  AOS.init();
</script>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>

</body>
</html>