<!-- item.php -->

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

// Fetch product by name with its variants and images - EXCLUDE SOFT-DELETED PRODUCTS
$query = "SELECT p.*, 
                 pv.variant_id, pv.variant_name, pv.variant_price, pv.discount_price, 
                 pv.unit_type, pv.minimum_order, pv.order_increment,
                 pi.image_path, pi.is_primary
          FROM products p
          LEFT JOIN product_variants pv ON p.product_id = pv.product_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id
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

while ($row = $result->fetch_assoc()) {
    if ($product === null) {
        $product = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_description' => $row['product_description']
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
        $images[] = $row['image_path'];
        if ($row['is_primary']) {
            $primaryImage = $row['image_path'];
        }
    }
}

// If no primary image but other images exist, use the first one
if (empty($primaryImage) && !empty($images)) {
    $primaryImage = $images[0];
}

// If no images at all, use default.png
if (empty($images)) {
    $primaryImage = 'default.png';
    $images = ['default.png'];
}

// Generate canonical URL
$canonicalUrl = $baseUrl . "item/" . strtolower(str_replace(' ', '-', $product['product_name']));
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="<?= htmlspecialchars($product['product_description']) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>" />
  
  <!-- Favicons -->
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link rel="stylesheet" href="<?= $baseUrl ?>style.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>output.css">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<style>
.selected-variant {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #FFFFFF;
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

  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
    <!-- Breadcrumbs -->
    <div class="mb-4 text-sm text-gray-600">
      <a href="<?= $baseUrl ?>" class="hover:text-orange-500">Home</a>
        <svg xmlns="shrink-0 mx-3 text-gray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
      <span class="text-gray-800"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <!-- TOAST CONTAINER HERE -->
    <div id="toastContainer"></div>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="md:col-span-1 shadow-lg p-4 rounded-3xl">
        <!-- Product Image & Thumbnails -->
        <div class="flex flex-col items-center">
            <!-- Main Image -->
            <div class="max-w-xl rounded-lg relative">
                <img id="mainImage"
                    src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
                    class="rounded-lg"
                    alt="<?= htmlspecialchars($product['product_name']) ?>">
            </div>

            <!-- Thumbnails - Only show if we have more than one image -->
            <?php if (count($images) > 1): ?>
                <div class="flex justify-center space-x-3 mt-4 overflow-x-auto">
                    <?php foreach ($images as $image): ?>
                        <img src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($image) ?>"
                            class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-gray-500"
                            onclick="changeImage('<?= htmlspecialchars($image) ?>')"
                            alt="<?= htmlspecialchars($product['product_name']) ?> - Thumbnail">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
      </div>

      <!-- Right Side: Product Details -->
      <div class="md:col-span-2 shadow-lg p-4 rounded-3xl">
        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p class="mt-2 text-gray-600"><?= htmlspecialchars($product['product_description']) ?></p>

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
                        <button type="button" class="decrease-quantity px-3 py-2 rounded-l hover:bg-orange-600 hover:text-white">-</button>
                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="" placeholder="1" readonly>
                        <button type="button" class="increase-quantity px-3 py-2 rounded-r hover:bg-orange-600 hover:text-white">+</button>
                    </div>
                    &nbsp;
                    <span class="ml-2 text-sm font-medium text-gray-600 unit-display"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
            </div>

            <!-- Price and Discount Display -->
            <div class="price-display mt-3"></div>

            <!-- Add to Cart Button -->
            <button type="submit" name="add_to_cart" 
                    class="cursor-pointer mt-4 w-full py-3 rounded-lg justify-center items-center inline-flex bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 focus:outline-none" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                    Add to Cart
            </button>

            <!-- Message to select a variant -->
            <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
            <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>

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