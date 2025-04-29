<?php
$cart = $_SESSION['cart'] ?? [];
?>

<div id="hs-cart-sidebar" class="fixed inset-0 z-50 bg-gray-900 bg-opacity-50 hidden overflow-hidden" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div id="sidebar-white-bg" class="fixed top-0 right-0 h-full bg-white shadow-xl transform transition-transform duration-300 translate-x-full overflow-y-auto w-full">
        <div class="flex flex-col h-full">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h2 class="font-bold text-xl">Your Cart: <span id="cart-count-sidebar" class="cart-count text-orange-500"><?php echo count($cart); ?></span></h2>
                <button type="button" class="btn btn-icon btn-sm btn-ghost" onclick="closeOffCanvas()">
                    <span class="sr-only">Close</span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="cart-items-list" class="p-4">
                <?php if (!empty($cart)): ?>
                    <?php foreach ($cart as $index => $item): ?>
                        <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200" 
                             data-product-id="<?= $item['product_id'] ?>" 
                             data-variant-id="<?= $item['variant_id'] ?>">
                            <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                            <div class="flex-grow">
                                <h3 class="font-medium text-base mb-2 flex justify-between"><?= $item['product_name'] ?></h3>
                                <p class="text-sm text-gray-500 mb-4"><?= $item['variant_name'] ?></p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center border border-gray-300 rounded">
                                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600">-</button>
                                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                                               value="<?= $item['quantity'] ?>" readonly> pcs.
                                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600">+</button>
                                    </div>
                                    <span class="price ml-4 font-medium text-sm" data-price-per-unit="<?= $item['price'] ?>">
                                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="remove text-red-500 hover:text-red-700 ml-4">
                                <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18"></path>
                                    <path d="m6 6 12 12"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500">Your cart is empty.</p>
                <?php endif; ?>
            </div>

            <div class="p-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="font-bold">Subtotal:</span>
                    <span id="cart-total-sidebar" class="font-medium">
                        ₱<?php echo number_format(array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart)), 2); ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500">Taxes and shipping calculated at checkout</p>
                <a href="<?= $baseUrl ?>checkout.php" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">
                    Checkout
                </a>
                
                <div class="text-center my-5">
                    <a class="text-blue-500 hover:underline cursor-pointer" onclick="closeOffCanvas()">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
  input[type=number]::-webkit-inner-spin-button, 
  input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
  }

  .animate-bounce {
    animation: bounce 0.5s;
  }

  @keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.5); }
  }

  /* Responsive sidebar width if you want custom sizes */
  @media (max-width: 768px) {
    #sidebar-white-bg {
      width: 100%;
    }
  }

  @media (min-width: 769px) and (max-width: 1024px) {
    #sidebar-white-bg {
      width: 50%;
    }
  }

  @media (min-width: 1025px) {
    #sidebar-white-bg {
      width: 33.3333%;
    }
  }
</style>

<script>
// Cart functions
function openOffCanvas() {
    document.getElementById('hs-cart-sidebar').classList.remove('hidden');
    setTimeout(() => document.getElementById('sidebar-white-bg').classList.remove('translate-x-full'), 10);
    updateCartUI(); // Refresh cart when opened
}

function closeOffCanvas() {
    document.getElementById('sidebar-white-bg').classList.add('translate-x-full');
    setTimeout(() => document.getElementById('hs-cart-sidebar').classList.add('hidden'), 300);
}

async function updateCartUI() {
    try {
        const response = await fetch('./functions/fetch_cart_items.php');
        
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

async function updateCartItemQuantity(item, change) {
    const productId = item.dataset.productId;
    const variantId = item.dataset.variantId;
    const quantityInput = item.querySelector('.quantity');
    
    if (!quantityInput) {
        console.error('Quantity input not found');
        showToast('Error updating quantity', 'error');
        return;
    }

    const newQuantity = parseInt(quantityInput.value) + change;

    const updates = [{
        product_id: productId,
        variant_id: variantId,
        quantity: newQuantity
    }];

    try {
        const response = await fetch('./functions/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updates)
        });

        // First check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(`Invalid response: ${text}`);
        }

        const data = await response.json();
        if (data.status === 'success') {
            await updateCartUI();
        } else {
            showToast(data.message || 'Failed to update quantity', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(error.message || 'An error occurred', 'error');
    }
}

// Helper function to remove item
async function removeCartItem(item) {
    const productId = item.dataset.productId;
    const variantId = item.dataset.variantId;

    try {
        const response = await fetch('./functions/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                variant_id: variantId
            })
        });

        const data = await response.json();
        if (data.status === 'success') {
            showToast('Item removed from cart');
            await updateCartUI();
        } else {
            showToast('Failed to remove item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    }
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-md shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } animate-fade-in`;
    toast.textContent = message;
    
    // Append to toast container
    const container = document.getElementById('toastContainer') || document.body;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('animate-fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Initialize cart event handlers
function initCartEventHandlers() {
    // Remove any existing event listeners to prevent duplicates
    document.removeEventListener('click', handleCartClick);
    
    // Add new event listener
    document.addEventListener('click', handleCartClick);
}

// Handle all cart-related clicks
function handleCartClick(e) {
    // Increase quantity
    if (e.target.classList.contains('increase-quantity')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            updateCartItemQuantity(item, 1);
        }
    }
    
    // In your handleCartClick function
    if (e.target.classList.contains('decrease-quantity')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            const quantityInput = item.querySelector('.quantity');
            if (!quantityInput) {
                console.error('Quantity input not found');
                return;
            }
            const currentQty = parseInt(quantityInput.value);
            if (currentQty > 1) {
                updateCartItemQuantity(item, -1);
            } else {
                showToast('Minimum quantity is 1', 'error');
            }
        }
    }
    
    // Remove item
    if (e.target.closest('.remove')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            if (confirm('Are you sure you want to remove this item?')) {
                removeCartItem(item);
            }
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initCartEventHandlers();
    
    // Open cart when clicking cart icon (if exists)
    document.querySelectorAll('[data-cart-toggle]').forEach(btn => {
        btn.addEventListener('click', openOffCanvas);
    });
    
    // Add animation styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(10px); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in forwards;
        }
        .animate-fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
    `;
    document.head.appendChild(style);
});
</script>