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

// Fetch product by name with its variants and images
$query = "SELECT p.*, 
                 pv.variant_id, pv.variant_name, pv.variant_price, pv.discount_price, pv.stock_quantity,
                 pi.image_path, pi.is_primary
          FROM products p
          LEFT JOIN product_variants pv ON p.product_id = pv.product_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id
          WHERE LOWER(REPLACE(p.product_name, ' ', '-')) = LOWER(REPLACE(?, ' ', '-'))
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
$primaryImage = null; // Initialize as null instead of 'default-image.jpg'

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
            'discount_price' => $row['discount_price'] ? $row['discount_price'] : $row['variant_price'],
            'stock' => $row['stock_quantity']
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
    $images = ['default.png']; // Add default to images array for thumbnails
}

// Set default price to first variant if exists
$defaultVariant = reset($variants);
$defaultPrice = $defaultVariant ? $defaultVariant['variant_price'] : 0;
$defaultDiscountPrice = $defaultVariant ? $defaultVariant['discount_price'] : 0;

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
    border-color: #000000;
    color: #FFFFFF;
}

#toastContainer {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 50;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.toast {
    background: #ea580c;
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    font-size: 1rem;
    font-weight: bold;
    text-align: center;
    min-width: 250px;
    margin-bottom: 10px;
    animation: fadeIn 0.3s ease-in, fadeOut 0.3s ease-out 3s forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(10px); }
}

.animate-bounce {
    animation: bounce 0.5s;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.5); }
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.toast.success {
    background-color: #10b981;
}

.toast.error {
    background-color: #ef4444;
}

.variant-message {
    display: none;
}
</style>

<!-- Hero Section -->
<section id="home-section">
  <?php include('./components/navigation.php'); ?>

  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
    <!-- Breadcrumbs -->
    <div class="mb-4 text-sm text-gray-600">
      <a href="<?= $baseUrl ?>" class="hover:text-orange-500">Home</a> &gt;
      <span class="text-gray-800"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <div id="toastContainer"></div>

    <div class="grid md:grid-cols-3 gap-4">
      <div class="md:col-span-1 shadow-lg p-4 rounded-3xl">
        <!-- Product Image & Thumbnails -->
        <div class="flex flex-col items-center">
            <!-- Main Image Zoom Area -->
            <div class="max-w-xl zoom-container rounded-lg" id="zoomContainer">
                <img id="mainImage"
                    src="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>"
                    class="zoom-image rounded-lg"
                    alt="<?= htmlspecialchars($product['product_name']) ?>">

                <div id="zoomOverlay" class="zoom-overlay rounded-lg"
                    style="background-image: url('<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>');">
                </div>
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

        <!-- Price and Discount -->
        <div class="flex items-center space-x-3 mt-4">
          <?php if (!empty($variants) && $defaultDiscountPrice < $defaultPrice): ?>
            <span class="price-display text-gray-500 line-through text-lg">₱<?= number_format($defaultPrice, 2) ?></span>
          <?php endif; ?>
          <span class="discount-display text-red-600 font-bold text-2xl">₱<?= number_format($defaultDiscountPrice, 2) ?></span>
          <?php if (!empty($variants) && $defaultDiscountPrice < $defaultPrice): ?>
            <span class="discount-percent bg-red-600 text-white text-xs px-2 py-1 rounded-full">
              SAVE <?= number_format(100 - ($defaultDiscountPrice / $defaultPrice * 100), 0) ?>%
            </span>
          <?php endif; ?>
        </div>

        <!-- Variant Selection -->
        <?php if (!empty($variants)): ?>
          <div class="mt-2">
              <label class="block text-sm font-medium text-gray-700">Select Size:</label>
              <div class="flex flex-wrap gap-4 variant-buttons-container">
                  <?php foreach ($variants as $variant_id => $variant): ?>
                      <button type="button" 
                              class="variant-button px-4 py-2 border rounded-lg text-sm font-medium hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200 text-dark <?= $variant === reset($variants) ? 'selected-variant' : '' ?>"
                              data-variant-id="<?= $variant_id ?>"
                              data-price="<?= htmlspecialchars($variant['variant_price']) ?>"
                              data-discount="<?= htmlspecialchars($variant['discount_price']) ?>"
                              onclick="selectVariant(this)">
                          <?= htmlspecialchars($variant['variant_name']) ?>
                      </button>
                  <?php endforeach; ?>
              </div>
          </div>
        <?php endif; ?>

        <!-- Quantity Selector -->
        <div class="flex items-center mt-5">
          <span class="mr-3 text-gray-700">Quantity:</span>
          
          <div class="flex items-center justify-start">
              <div class="flex items-center border border-gray-300 rounded mx-2">
                  <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600">-</button>
                  <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="1" readonly>
                  <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600">+</button>
              </div>
              &nbsp;
          </div>
        </div>

        <!-- Call-to-Action Buttons -->
        <div class="flex flex-col space-y-3 mt-6">
          <!-- Add to Cart Form -->
          <form class="add-to-cart-form">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="variant_id" value="<?= !empty($variants) ? key($variants) : '' ?>">
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>">
            <input type="hidden" name="variant_name" value="<?= !empty($variants) ? reset($variants)['variant_name'] : '' ?>">
            <input type="hidden" name="price" value="<?= $defaultDiscountPrice ?>">
            <input type="hidden" name="image_url" value="<?= $baseUrl ?>uploads/products/<?= htmlspecialchars($primaryImage) ?>">
            <input type="hidden" name="quantity" value="1">
            
            <button type="submit" name="add_to_cart" 
                    class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-105 transition-all duration-500 disabled:opacity-50"
                    <?= empty($variants) ? '' : 'disabled' ?>>
              <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
              ADD TO CART
            </button>
          </form>
          
          <p class="text-red-500 text-sm mt-2 variant-message" style="display: none;">Please select a variant first.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>

<script>

function changeImage(imageName) {
  // Skip if trying to change to the same image
  if (imageName === document.getElementById('mainImage').src.split('/').pop()) {
      return;
  }
  
  const fullPath = "<?= $baseUrl ?>uploads/products/" + imageName;
  document.getElementById('mainImage').src = fullPath;
  document.getElementById('zoomOverlay').style.backgroundImage = `url('${fullPath}')`;
}
  
// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    const container = document.getElementById('toastContainer');
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('animate-fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Change main product image
function changeImage(newImage) {
    document.getElementById('mainImage').src = '<?= $baseUrl ?>uploads/products/' + newImage;
}

// Variant selection handler
function selectVariant(button) {
    // Update active state for all variant buttons
    const variantButtons = document.querySelectorAll('.variant-button');
    variantButtons.forEach(btn => {
        btn.classList.remove('selected-variant');
    });
    button.classList.add('selected-variant');
    
    // Update prices
    const price = parseFloat(button.dataset.price);
    const discountPrice = parseFloat(button.dataset.discount);
    
    // Update displayed prices
    const priceDisplay = document.querySelector('.price-display');
    const discountDisplay = document.querySelector('.discount-display');
    const discountPercent = document.querySelector('.discount-percent');
    
    if (discountDisplay) discountDisplay.textContent = `₱${discountPrice.toFixed(2)}`;
    
    if (priceDisplay && discountPrice < price) {
        priceDisplay.textContent = `₱${price.toFixed(2)}`;
        priceDisplay.style.display = 'inline';
        if (discountPercent) {
            discountPercent.textContent = `SAVE ${Math.round(100 - (discountPrice / price * 100))}%`;
            discountPercent.style.display = 'inline';
        }
    } else {
        if (priceDisplay) priceDisplay.style.display = 'none';
        if (discountPercent) discountPercent.style.display = 'none';
    }
    
    // Update form fields
    const form = document.querySelector('.add-to-cart-form');
    form.querySelector('input[name="variant_id"]').value = button.dataset.variantId;
    form.querySelector('input[name="variant_name"]').value = button.textContent.trim();
    form.querySelector('input[name="price"]').value = discountPrice;
    
    // Enable add to cart button
    form.querySelector('button[name="add_to_cart"]').disabled = false;
    
    // Hide variant message
    document.querySelector('.variant-message').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    // Quantity increase
    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const quantityInput = this.parentElement.querySelector('.quantity');
            const newValue = parseInt(quantityInput.value) + 1;
            quantityInput.value = newValue;
            document.querySelector('input[name="quantity"]').value = newValue;
        });
    });

    // Quantity decrease
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const quantityInput = this.parentElement.querySelector('.quantity');
            const newValue = Math.max(1, parseInt(quantityInput.value) - 1);
            quantityInput.value = newValue;
            document.querySelector('input[name="quantity"]').value = newValue;
        });
    });
   
    // Add to cart form submission
    document.querySelector('.add-to-cart-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const variantId = this.querySelector('input[name="variant_id"]').value;
        if (!variantId) {
            document.querySelector('.variant-message').style.display = 'block';
            return;
        }
        
        const submitBtn = this.querySelector('button[name="add_to_cart"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Adding...';
        
        try {
            const response = await fetch('<?= $baseUrl ?>functions/add_to_cart.php', {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                showToast('Product added to cart', 'success');
                await updateCartCount();
                await updateCartItems();
            } else {
                showToast(data.message || 'Failed to add product', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to add to cart. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 17h-11v-14h-2" />
                    <path d="M6 5l14 1l-1 7h-13" />
                </svg>
                ADD TO CART
            `;
        }
    });

    // Select first variant by default if available
    const firstVariantBtn = document.querySelector('.variant-button.selected-variant');
    if (firstVariantBtn) {
        selectVariant(firstVariantBtn);
    }
});

// Update cart UI (including items and count)
async function updateCartUI() {
    try {
        await updateCartItems();
        await updateCartCount();
    } catch (error) {
        console.error('Cart update error:', error);
        showToast('Error updating cart', 'error');
    }
}

async function updateCartItems() {
    try {
        const response = await fetch('/sjfbi-js/functions/fetch_cart_items.php');
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to fetch cart items: ${errorText}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'error') {
            throw new Error(data.message);
        }
        
        // Update the cart sidebar content
        const cartSidebar = document.getElementById('hs-cart-sidebar');
        if (cartSidebar) {
            const itemsList = cartSidebar.querySelector('#cart-items-list');
            if (itemsList) {
                itemsList.innerHTML = data.cart_items || '<p class="text-center text-gray-500">Your cart is empty.</p>';
            }
            
            const totalElement = cartSidebar.querySelector('#cart-total-sidebar');
            if (totalElement && data.cart_total !== undefined) {
                totalElement.textContent = `₱${data.cart_total.toFixed(2)}`;
            }
        }
        
        // Re-initialize event handlers for the new cart items
        initCartItemEventHandlers();
        
        return data;
    } catch (error) {
        console.error('Cart items update error:', error);
        throw error;
    }
}
function initCartItemEventHandlers() {
    // Handle quantity increases
    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', async function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;
            
            const productId = cartItem.dataset.productId;
            const variantId = cartItem.dataset.variantId;
            const quantityInput = cartItem.querySelector('.quantity');
            
            if (!productId || !variantId || !quantityInput) {
                showToast('Error: Missing cart item data', 'error');
                return;
            }
            
            const currentQuantity = parseInt(quantityInput.value);
            const newQuantity = currentQuantity + 1;
            
            // Update the UI immediately for better responsiveness
            quantityInput.value = newQuantity;
            
            await updateCartItemQuantity(productId, variantId, newQuantity);
        });
    });

    // Handle quantity decreases
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', async function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;
            
            const productId = cartItem.dataset.productId;
            const variantId = cartItem.dataset.variantId;
            const quantityInput = cartItem.querySelector('.quantity');
            
            if (!productId || !variantId || !quantityInput) {
                showToast('Error: Missing cart item data', 'error');
                return;
            }
            
            const currentQuantity = parseInt(quantityInput.value);
            
            if (currentQuantity <= 1) {
                showToast('Minimum quantity is 1', 'error');
                return;
            }
            
            const newQuantity = currentQuantity - 1;
            
            // Update the UI immediately for better responsiveness
            quantityInput.value = newQuantity;
            
            await updateCartItemQuantity(productId, variantId, newQuantity);
        });
    });

    // Handle item removal
    document.querySelectorAll('.remove').forEach(button => {
        button.addEventListener('click', async function() {
            const cartItem = this.closest('.cart-item');
            if (!cartItem) return;
            
            const productId = cartItem.dataset.productId;
            const variantId = cartItem.dataset.variantId;
            
            if (!productId || !variantId) {
                showToast('Error: Missing cart item data', 'error');
                return;
            }
            
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                await removeCartItem(productId, variantId);
            }
        });
    });
}

async function updateCartItemQuantity(productId, variantId, quantity) {
    try {
        const response = await fetch('/sjfbi-js/functions/update_cart_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: parseInt(productId),
                variant_id: parseInt(variantId),
                quantity: parseInt(quantity)
            })
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server error: ${errorText}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Update the specific quantity input in the UI immediately
            const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"][data-variant-id="${variantId}"]`);
            if (cartItem) {
                const quantityInput = cartItem.querySelector('.quantity');
                if (quantityInput) {
                    quantityInput.value = quantity;
                }
                
                // Update the price display if it exists
                const priceDisplay = cartItem.querySelector('.price');
                if (priceDisplay) {
                    const pricePerUnit = parseFloat(priceDisplay.dataset.pricePerUnit || '0');
                    priceDisplay.textContent = `₱${(pricePerUnit * quantity).toFixed(2)}`;
                }
            }
            
            // Update the cart UI
            await updateCartUI();
            showToast('Quantity updated', 'success');
        } else {
            throw new Error(data.message || 'Failed to update quantity');
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showToast(error.message || 'Failed to update quantity', 'error');
        
        // Revert the quantity in the UI if the update failed
        const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"][data-variant-id="${variantId}"]`);
        if (cartItem) {
            const quantityInput = cartItem.querySelector('.quantity');
            if (quantityInput) {
                const currentValue = parseInt(quantityInput.value);
                quantityInput.value = currentValue; // This will revert any visual change
            }
        }
    }
}

async function removeCartItem(productId, variantId) {
    try {
        const response = await fetch('/sjfbi-js/functions/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                variant_id: variantId
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'success') {
            await updateCartUI();
            showToast('Item removed from cart', 'success');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showToast('Failed to remove item', 'error');
    }
}

// Initialize cart event handlers when page loads
document.addEventListener('DOMContentLoaded', function() {
    initCartItemEventHandlers();
});
// Update cart count
async function updateCartCount() {
    try {
        // Use absolute path from root
        const response = await fetch('/sjfbi-js/functions/get_cart_count.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        document.querySelectorAll('.cart-count').forEach(el => {
            el.textContent = data.cart_count || '0';
            el.classList.add('animate-bounce');
            setTimeout(() => el.classList.remove('animate-bounce'), 1000);
        });
    } catch (error) {
        console.error('Cart count update error:', error);
        throw error; // Re-throw to be caught by updateCartUI
    }
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