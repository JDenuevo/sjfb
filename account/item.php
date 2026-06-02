<?php
// account/item.php
// HTTP URL for browser assets (CSS, JS, images)
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
         . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

// For file includes, use file system paths (NOT HTTP URLs)
require_once __DIR__ . '/../conn.php';  // Go up one level from account folder

// Set these variables for the breadcrumb
$pageTitle = 'Shop';
$currentPage = '';
$showCategories = false;
$showMobileCategories = false;

// Get product name from URL
$productName = isset($_GET['name']) ? urldecode(str_replace('-', ' ', $_GET['name'])) : '';

if (empty($productName)) {
    header("HTTP/1.0 404 Not Found");
    include __DIR__ . '/../error/404.php';  // Use file system path, NOT $baseUrl
    die();
}

// Fetch product by name with its variants, images, AND CATEGORIES
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
    include __DIR__ . '/../error/404.php';  // Use file system path
    die();
}

// Process the result set (keep your existing processing code)
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
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_unit' => $row['product_unit'],
            'product_description' => $row['product_description'],
            'product_nickname' => $row['product_nickname']
        ];
        
        $currentPage = $row['product_name'];
    }
    
    // Handle variants
    if ($row['variant_id'] && !isset($variants[$row['variant_id']])) {
        $stockQuantity = intval($row['stock_quantity'] ?? 0);
        $variantHasStock = $stockQuantity > 0;
        
        $variants[$row['variant_id']] = [
            'variant_name' => $row['variant_name'],
            'variant_price' => $row['variant_price'],
            'discount_price' => $row['discount_price'],
            'unit_type' => $row['unit_type'],
            'minimum_order' => $row['minimum_order'],
            'order_increment' => $row['order_increment'],
            'stock_quantity' => $stockQuantity,
            'has_stock' => $variantHasStock
        ];
        
        if ($variantHasStock) {
            $hasStock = true;
        }
        $totalStock += $stockQuantity;
    }
    
    // Handle images
    if ($row['image_path']) {
        $images[$row['image_path']] = $row['image_path'];
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
        
        $productCategories[] = $row['category_id'];
        
        if ($primaryCategory === null) {
            $primaryCategory = $categories[$row['category_id']];
        }
    }
}

// Set default image if none found
if (empty($primaryImage) && !empty($images)) {
    $primaryImage = reset($images);
}
if (empty($images)) {
    $primaryImage = 'default.png';
    $images = ['default.png'];
} else {
    $images = array_values($images);
}

// Define share URLs
$canonicalUrl = $baseUrl . "item/" . strtolower(str_replace(' ', '-', $product['product_name']));
$shareUrlNew = $canonicalUrl;
$shareTitle = $product['product_name'];
$shareText = "Check out this fresh seafood: " . $product['product_name'] . " from St. Joseph Fish Brokerage Inc.";
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth"> 
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>
  
  <!-- Favicons -->
  <link rel="shortcut icon" href="<?= $baseUrl ?>assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="<?= $baseUrl ?>assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="<?= $baseUrl ?>assets/icons/logo.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  
  <!-- CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  <link href="<?= $baseUrl ?>style.css" rel="stylesheet">
  <link href="<?= $baseUrl ?>output.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script>window.CART_BASE = '<?= $baseUrl ?>';</script>
  <script src="<?= $baseUrl ?>functions/cart_process.js"></script>
</head>
<body>

<?php include __DIR__ . '/../components/navigation.php'; ?>

<div class="max-w-7xl px-4 sm:px-6 lg:px-8 mx-auto mt-10">
    <?php include __DIR__ . '/../components/nav_crumb.php'; ?>

    <!-- Main Product Section -->
    <div class="grid md:grid-cols-3 gap-4 shadow-lg mb-10">
        <!-- Left Column - Images -->
        <div class="md:col-span-1 p-4 rounded-3xl">
            <div class="flex flex-col items-center">
                <div class="max-w-xl rounded-lg relative">
                    <?php if (!$hasStock): ?>
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg z-10">
                        <span class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg transform -rotate-12 shadow-lg">OUT OF STOCK</span>
                    </div>
                    <?php endif; ?>
                    <img id="mainImage"
                        src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
                        class="rounded-lg shadow-md w-full <?= !$hasStock ? 'opacity-60' : '' ?>"
                        alt="<?= htmlspecialchars($product['product_name']) ?>">
                </div>
                
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

        <!-- Middle Column - Product Details -->
        <div class="md:col-span-1 p-4 rounded-3xl">
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($product['product_name']) ?></h1>
            <p class="mt-2 text-gray-600"><?= htmlspecialchars($product['product_unit']) ?></p>

            <?php if ($hasStock): ?>
            <form class="add-to-cart-form mt-4" data-product-id="<?= $product['product_id'] ?>">
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

                <?php if (!empty($variants)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <?php 
                        $firstInStock = null;
                        foreach ($variants as $variant_id => $variant) { 
                            if ($variant['has_stock']) {
                                $firstInStock = $variant_id;
                                break;
                            }
                        }
                        
                        foreach ($variants as $variant_id => $variant): 
                            $variantHasStock = $variant['has_stock'];
                        ?>
                            <button type="button"
                                class="variant-button px-3 py-2 border rounded-lg text-sm font-medium transition-all duration-200
                                    <?= ($firstInStock && $variant_id === $firstInStock) ? 'selected-variant bg-amber-500 text-white' : 'border-gray-300' ?>
                                    <?= !$variantHasStock ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                data-variant-id="<?= $variant_id ?>"
                                data-variant-name="<?= htmlspecialchars($variant['variant_name']) ?>"
                                data-variant-price="<?= $variant['variant_price'] ?>"
                                data-discount-price="<?= $variant['discount_price'] ?>"
                                data-unit-type="<?= $variant['unit_type'] ?>"
                                data-minimum-order="<?= $variant['minimum_order'] ?>"
                                data-order-increment="<?= $variant['order_increment'] ?>"
                                data-stock-quantity="<?= $variant['stock_quantity'] ?>"
                                data-has-stock="<?= $variantHasStock ? 'true' : 'false' ?>"
                                <?= !$variantHasStock ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($variant['variant_name']) ?>
                                <?php if (!$variantHasStock): ?>
                                    <span class="ml-1 text-red-500">(No Stock)</span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-4">
                    <div class="flex items-center">
                        <div class="flex items-center border border-gray-300 rounded">
                            <button type="button" class="decrease-quantity px-3 py-1 rounded-l text-sm hover:bg-orange-600 hover:text-white">−</button>
                            <input type="text" class="quantity w-16 px-2 py-1 text-center text-sm border-0" value="1" readonly>
                            <button type="button" class="increase-quantity px-3 py-1 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-600 unit-display"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
                </div>

                <div class="price-display mt-3"></div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <button type="submit" name="add_to_cart" 
                            class="w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" 
                            disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                            <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                            <path d="M17 17h-11v-14h-2"/>
                            <path d="M6 5l14 1l-1 7h-13"/>
                        </svg>
                        Add to Cart
                    </button>
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
            <?php endif; ?>
        </div>

        <!-- Right Column - Description -->
        <div class="md:col-span-1 p-4 rounded-3xl">
            <h2 class="text-xl font-bold text-gray-800">About <?= htmlspecialchars($product['product_name']) ?></h2>
            <p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($product['product_description'])) ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
<?php include __DIR__ . '/../live_chat.php'; ?>

<script>
function changeImage(imageName) {
    const mainImage = document.getElementById('mainImage');
    mainImage.src = '<?= $baseUrl ?>uploads/products/' + imageName;
}

// Variant selection and add to cart logic
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.add-to-cart-form');
    if (!form) return;

    // Variant selection
    document.querySelectorAll('.variant-button').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove selected class from all
            document.querySelectorAll('.variant-button').forEach(b => {
                b.classList.remove('selected-variant', 'bg-amber-500', 'text-white');
                b.classList.add('border-gray-300');
            });
            
            this.classList.add('selected-variant', 'bg-amber-500', 'text-white');
            this.classList.remove('border-gray-300');
            
            // Update form fields
            form.querySelector('[name="variant_id"]').value = this.dataset.variantId;
            form.querySelector('[name="variant_name"]').value = this.dataset.variantName;
            
            const discountPrice = parseFloat(this.dataset.discountPrice);
            const variantPrice = parseFloat(this.dataset.variantPrice);
            form.querySelector('[name="price"]').value = discountPrice > 0 ? discountPrice : variantPrice;
            form.querySelector('[name="unit_type"]').value = this.dataset.unitType;
            form.querySelector('[name="minimum_order"]').value = this.dataset.minimumOrder;
            form.querySelector('[name="order_increment"]').value = this.dataset.orderIncrement;
            
            const minOrder = parseFloat(this.dataset.minimumOrder);
            form.querySelector('.quantity').value = minOrder;
            form.querySelector('[name="quantity"]').value = minOrder;
            
            const unitType = this.dataset.unitType;
            document.querySelector('.unit-display').textContent = unitType === 'piece' ? 'pcs' : unitType;
            document.querySelector('.minimum-order-text').textContent = `Minimum: ${minOrder} ${unitType === 'piece' ? 'pcs' : unitType}`;
            
            // Update price display
            const price = discountPrice > 0 ? discountPrice : variantPrice;
            const total = price * minOrder;
            if (discountPrice > 0) {
                document.querySelector('.price-display').innerHTML = `
                    <span class="line-through text-gray-500">₱${(variantPrice * minOrder).toFixed(2)}</span>
                    <span class="text-red-600 font-bold text-2xl ml-2">₱${total.toFixed(2)}</span>
                `;
            } else {
                document.querySelector('.price-display').innerHTML = `
                    <span class="text-gray-800 font-bold text-2xl">₱${total.toFixed(2)}</span>
                `;
            }
            
            // Enable add to cart button
            form.querySelector('[name="add_to_cart"]').disabled = false;
            document.querySelector('.variant-message').classList.add('hidden');
        });
    });
    
    // Auto-select first in-stock variant
    const firstInStock = document.querySelector('.variant-button[data-has-stock="true"]');
    if (firstInStock) {
        firstInStock.click();
    }
    
    // Quantity buttons
    document.querySelector('.decrease-quantity')?.addEventListener('click', function() {
        const input = form.querySelector('.quantity');
        const minOrder = parseFloat(form.querySelector('[name="minimum_order"]').value);
        const orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value);
        let val = parseFloat(input.value) || minOrder;
        if (val - orderIncr >= minOrder) {
            val -= orderIncr;
            input.value = val;
            form.querySelector('[name="quantity"]').value = val;
            
            // Update price
            const selectedBtn = document.querySelector('.variant-button.selected-variant');
            if (selectedBtn) {
                const price = parseFloat(form.querySelector('[name="price"]').value);
                const total = price * val;
                const priceDisplay = document.querySelector('.price-display');
                priceDisplay.innerHTML = `<span class="text-gray-800 font-bold text-2xl">₱${total.toFixed(2)}</span>`;
            }
        }
    });
    
    document.querySelector('.increase-quantity')?.addEventListener('click', function() {
        const input = form.querySelector('.quantity');
        const orderIncr = parseFloat(form.querySelector('[name="order_increment"]').value);
        let val = parseFloat(input.value) || 0;
        val += orderIncr;
        input.value = val;
        form.querySelector('[name="quantity"]').value = val;
        
        // Update price
        const selectedBtn = document.querySelector('.variant-button.selected-variant');
        if (selectedBtn) {
            const price = parseFloat(form.querySelector('[name="price"]').value);
            const total = price * val;
            const priceDisplay = document.querySelector('.price-display');
            priceDisplay.innerHTML = `<span class="text-gray-800 font-bold text-2xl">₱${total.toFixed(2)}</span>`;
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const variantId = form.querySelector('[name="variant_id"]').value;
        if (!variantId) {
            document.querySelector('.variant-message').classList.remove('hidden');
            return;
        }
        
        const submitBtn = form.querySelector('[name="add_to_cart"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin mr-2 h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Adding...';
        
        try {
            const response = await fetch('<?= $baseUrl ?>functions/add_to_cart.php', {
                method: 'POST',
                body: new FormData(form)
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                showToast('Product added to cart!', 'success');
                if (typeof refreshCartFromServer === 'function') {
                    await refreshCartFromServer();
                }
            } else {
                showToast(data.message || 'Failed to add product', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="mr-2" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg> Add to Cart';
        }
    });
});

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} transition-all duration-300 transform translate-x-0`;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

</body>
</html>