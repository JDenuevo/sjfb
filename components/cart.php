<?php
// Get the base URL for your site - fixed path
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

$cart = $_SESSION['cart'] ?? [];
$cartCount = count($cart);
$cartTotal = array_sum(array_map(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0), $cart));

function formatUnit($unitType, $quantity = null) {
    switch ($unitType) {
        case 'piece': $unitLabel = 'pc/s'; break;
        case 'kilogram': $unitLabel = 'kg/s'; break;
        case 'gram': $unitLabel = 'g/s'; break;
        default: $unitLabel = $unitType;
    }
    return $quantity !== null ? "{$quantity} {$unitLabel}" : $unitLabel;
}
?>

<div id="hs-cart-sidebar" class="fixed inset-0 z-50 bg-gray-900 bg-opacity-50 hidden overflow-hidden" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div id="sidebar-white-bg" class="fixed top-0 right-0 h-full bg-white shadow-xl transform transition-transform duration-300 translate-x-full overflow-y-auto w-full">
        <div class="flex flex-col h-full">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h2 class="font-bold text-xl">Your Cart: <span id="cart-count-sidebar" class="cart-count text-orange-500"><?php echo $cartCount; ?></span></h2>
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
                    <?php foreach ($cart as $index => $item): 
                        $unitType = $item['unit_type'] ?? 'piece';
                        $unitDisplay = $unitType === 'piece' ? 'pcs' : $unitType;
                        $minimumOrder = $item['minimum_order'] ?? 1;
                        $orderIncrement = $item['order_increment'] ?? 1;
                        $quantity = $item['quantity'] ?? $minimumOrder;
                        $displayQty = $unitType === 'piece' ? (int)$quantity : number_format($quantity, 2);
                    ?>
                        <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200" 
                             data-cart-index="<?= $index ?>"
                             data-product-id="<?= $item['product_id'] ?>" 
                             data-variant-id="<?= $item['variant_id'] ?>"
                             data-unit-type="<?= $unitType ?>"
                             data-minimum-order="<?= $minimumOrder ?>"
                             data-order-increment="<?= $orderIncrement ?>">
                            <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                            <div class="flex-grow">
                                <h3 class="font-medium text-base mb-2"><?= $item['product_name'] ?></h3>
                                <p class="text-sm text-gray-500 mb-1"><?= $item['variant_name'] ?></p>
                                <p class="text-xs text-gray-400 mb-2">Min: <?= $minimumOrder ?> <?= $unitDisplay ?></p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center border border-gray-300 rounded">
                                            <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                                            <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="<?= $displayQty ?>" readonly>
                                            <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                                        </div>
                                        &nbsp;
                                        <span class="text-xs text-gray-500"><?= $unitDisplay ?></span>
                                    </div>
                                    <span class="price ml-4 font-medium text-sm">
                                        ₱<?= number_format($item['price'] * $quantity, 2) ?>
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
                        ₱<?php echo number_format($cartTotal, 2); ?>
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
        const response = await fetch('/sjfbi-js/functions/fetch_cart_items.php');
        
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
        
        // Update ALL cart count elements (including navigation)
        if (data.cart_count !== undefined) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_count;
                el.classList.add('animate-bounce');
                setTimeout(() => el.classList.remove('animate-bounce'), 1000);
            });
        }
        
        // Update button states after cart refresh
        updateQuantityButtonsState();
        
        initCartEventHandlers();
    } catch (error) {
        console.error('Cart update error:', error);
        showToast(error.message || 'Error updating cart', 'error');
    }
}

async function updateCartItemQuantity(item, change) {
    const cartIndex = parseInt(item.dataset.cartIndex);
    const unitType = item.dataset.unitType;
    const minimumOrder = parseFloat(item.dataset.minimumOrder);
    const orderIncrement = parseFloat(item.dataset.orderIncrement);
    const quantityInput = item.querySelector('.quantity');
    
    if (!quantityInput) {
        console.error('Quantity input not found');
        showToast('Error updating quantity', 'error');
        return;
    }

    const currentQty = parseFloat(quantityInput.value);
    const newQty = currentQty + change;

    if (newQty < minimumOrder) {
        const unitDisplay = unitType === 'piece' ? 'pcs' : unitType;
        showToast(`Minimum order is ${minimumOrder} ${unitDisplay}`, 'error');
        return;
    }

    try {
        const response = await fetch('/sjfbi-js/functions/update_cart_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_index: cartIndex,
                quantity: newQty
            })
        });

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

async function removeCartItem(item) {
    const productId = item.dataset.productId;
    const variantId = item.dataset.variantId;

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

        const data = await response.json();
        if (data.status === 'success') {
            showToast('Item removed from cart', 'remove'); // RED TOAST ❌
            await updateCartUI();
        } else {
            showToast('Failed to remove item', 'error'); // DARK RED TOAST ⚠️
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred', 'error'); // DARK RED TOAST ⚠️
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') || document.body;
    
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

function initCartEventHandlers() {
    document.removeEventListener('click', handleCartClick);
    document.addEventListener('click', handleCartClick);
    updateQuantityButtonsState();
}

function handleCartClick(e) {
    if (e.target.classList.contains('increase-quantity')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            const orderIncrement = parseFloat(item.dataset.orderIncrement);
            updateCartItemQuantity(item, orderIncrement);
        }
    }
    
    if (e.target.classList.contains('decrease-quantity')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            const quantityInput = item.querySelector('.quantity');
            if (!quantityInput) {
                console.error('Quantity input not found');
                return;
            }
            const currentQty = parseFloat(quantityInput.value);
            const minimumOrder = parseFloat(item.dataset.minimumOrder);
            const orderIncrement = parseFloat(item.dataset.orderIncrement);
            
            if (currentQty - orderIncrement < minimumOrder) {
                const unitType = item.dataset.unitType;
                const unitDisplay = unitType === 'piece' ? 'pcs' : unitType;
                showToast(`Cannot go below minimum order of ${minimumOrder} ${unitDisplay}`, 'error');
                return;
            }
            
            updateCartItemQuantity(item, -orderIncrement);
        }
    }
    
    if (e.target.closest('.remove')) {
        const item = e.target.closest('.cart-item');
        if (item) {
            if (confirm('Are you sure you want to remove this item?')) {
                removeCartItem(item);
            }
        }
    }
}

function updateQuantityButtonsState() {
    document.querySelectorAll('.cart-item').forEach(item => {
        const quantityInput = item.querySelector('.quantity');
        const decreaseBtn = item.querySelector('.decrease-quantity');
        
        if (quantityInput && decreaseBtn) {
            const currentQty = parseFloat(quantityInput.value);
            const minimumOrder = parseFloat(item.dataset.minimumOrder);
            const orderIncrement = parseFloat(item.dataset.orderIncrement);
            
            if (currentQty - orderIncrement < minimumOrder) {
                decreaseBtn.disabled = true;
                decreaseBtn.classList.add('opacity-50', 'cursor-not-allowed');
                decreaseBtn.classList.remove('hover:bg-orange-600');
            } else {
                decreaseBtn.disabled = false;
                decreaseBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                decreaseBtn.classList.add('hover:bg-orange-600');
            }
        }
    });
}

// Initialize on page load AND update cart count from server
document.addEventListener('DOMContentLoaded', function() {
    initCartEventHandlers();
    
    document.querySelectorAll('[data-cart-toggle]').forEach(btn => {
        btn.addEventListener('click', openOffCanvas);
    });
    
    updateQuantityButtonsState();
    
    // CRITICAL: Fetch and update cart count on page load
    fetchAndUpdateCartCount();
    
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

// NEW FUNCTION: Fetch cart count on page load
async function fetchAndUpdateCartCount() {
    try {
        const response = await fetch('/sjfbi-js/functions/get_cart_count.php');
        const data = await response.json();
        
        if (data.cart_count !== undefined) {
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_count;
            });
        }
    } catch (error) {
        console.error('Error fetching cart count:', error);
    }
}
</script>