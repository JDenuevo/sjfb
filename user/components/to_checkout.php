<?php
$cart = $_SESSION['cart'] ?? []; // Retrieve cart data from the session
?>

<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
  <h1 class="text-center text-4xl font-bold">Order Confirmation</h1>
  <p class="text-gray-600 text-center mb-10">Review your order details before completing your purchase</p>
  
  <a href="./products.php">
    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" /></svg>
  </a>

  <div id="toastContainer"></div>
  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Customer Information -->
    <div class="bg-white shadow-lg p-6 rounded-3xl">
      <h2 class="text-xl font-bold mb-4">Your Information</h2>
      
      <div class="space-y-4">
        <div>
          <p class="text-sm font-semibold text-gray-500">Full Name</p>
          <p class="text-gray-800"><?= htmlspecialchars($userDetails['first_name'] . ' ' . $userDetails['last_name']) ?></p>
        </div>
        
        <div>
          <p class="text-sm font-semibold text-gray-500">Email</p>
          <p class="text-gray-800"><?= htmlspecialchars($userDetails['email']) ?></p>
        </div>
        
        <div>
          <p class="text-sm font-semibold text-gray-500">Phone</p>
          <p class="text-gray-800"><?= htmlspecialchars($userDetails['phone_number']) ?></p>
        </div>
        
        <div>
          <p class="text-sm font-semibold text-gray-500">Shipping Address</p>
          <p class="text-gray-800">
            <?= htmlspecialchars($userDetails['address']) ?><br>
            <?= htmlspecialchars($userDetails['city']) ?>, <?= htmlspecialchars($userDetails['postal_code']) ?>
          </p>
        </div>
      </div>
    </div>
    
      <!-- Order Summary -->
      <div class="md:col-span-2 bg-white shadow-lg p-6 rounded-3xl">
        <div class="flex justify-start items-center p-4 border-b border-gray-200">
          <h2 class="font-bold text-xl">To checkout: <span id="cart-count-sidebar" class="cart-count text-orange-500"><?php echo count($cart); ?></span></h2>
        </div>

        <div id="cart-items-list" class="p-4 overflow-y-scroll h-96">
            <?php if (!empty($cart)): ?>
                <?php foreach ($cart as $index => $item): ?>
                  <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200" 
                      data-product-id="<?= $item['product_id'] ?>" 
                      data-variant-id="<?= $item['variant_id'] ?>">
                      <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                      <div class="flex-grow p-2">
                          <h3 class="font-medium text-base mb-2 flex justify-between"><?= $item['product_name'] ?></h3>
                          <p class="text-sm text-gray-500 mb-4"><?= $item['variant_name'] ?></p>
                          <div class="flex items-center justify-between mt-2">
                              <div class="flex items-center border border-gray-300 rounded">
                                  <button type="button" id="Add" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600">-</button>
                                  <input type="text" id="Quantity" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                                        value="<?= $item['quantity'] ?>" readonly>
                                  <button type="button" id="Decrease" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600">+</button>
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
          
            
          <!-- Payment Method -->
          <form action="./functions/add.php" method="POST" class="mt-6">
            <input type="hidden" name="complete_order" value="1">
            
            <h3 class="text-xl font-bold">Payment</h3>
            <p class="text-sm text-gray-600">
              Choose your preferred payment method.
            </p>

            <div class="grid grid-cols-1 gap-4">
            
                <div class="payment-method">
                  <input type="radio" name="payment_method" id="cod" value="cod">
                  <label for="cod">
                    <!-- Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="#145207" stroke="#FFFFFF"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" />
                    <path d="M7 9m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" />
                    <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                    </svg>
                    <!-- Label text -->
                    <span class="text-gray-700 font-medium">Cash on Delivery</span>
                  </label>
                </div>

                <div class="payment-method">
                  <input type="radio" name="payment_method" id="gcash" value="gcash">
                  <label for="gcash">
                    <img src="../assets/icons/gcash.png" alt="GCash" style="width: 24px; margin-right: 5px;">
                    <span class="text-gray-700 font-medium">GCash</span>
                  </label>
                </div>

                <div class="payment-method">
                    <input type="radio" name="payment_method" id="maya" value="paymaya">
                    <label for="maya">
                      <img src="../assets/icons/maya.png" alt="Maya" style="width: 24px; margin-right: 5px;">
                      <span class="text-gray-700 font-medium">Maya</span>
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" name="payment_method" id="qrph" value="qrph">
                    <label for="qrph">
                      <img src="../assets/icons/qrph.png" alt="QR Ph" style="width: 24px; margin-right: 5px;">
                      <span class="text-gray-700 font-medium">QR Ph</span>
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" name="payment_method" id="grab_pay" value="grab_pay">
                    <label for="grab_pay">
                      <img src="../assets/icons/grabpay.png" alt="Grab Pay" style="width: 24px; margin-right: 5px;">
                      <span class="text-gray-700 font-medium">GrabPay</span>
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" name="payment_method" id="card" value="card">
                    <label for="card">
                      <img src="../assets/icons/card.png" alt="Credit/Debit Card" style="width: 24px; margin-right: 5px;">
                      <span class="text-gray-700 font-medium">Credit/Debit Card</span>
                    </label>
                </div>
            </div>

            <input type="hidden" name="total_amount" id="total_amount" value="<?php echo array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart)) ?>">

            <button type="submit" name="complete_order" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">
              Complete Order
            </button>

          </div>
          <!-- End Form -->
      
          </form>
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

  /* Hide the radio but keep it focusable */
.payment-method input[type="radio"] {
  display: none;
}

/* Default style for the label */
.payment-method label {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 2px solid #ddd;
  border-radius: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.3s ease;
}

/* Hover effect */
.payment-method label:hover {
  border-color: #f97316; /* orange-500 */
  box-shadow: 0 0 5px rgba(249, 115, 22, 0.3);
}

/* When radio is checked → style the label */
.payment-method input[type="radio"]:checked + label {
  border-color: #f97316;
  background-color: #fff7ed; /* orange-50 */
  box-shadow: 0 0 8px rgba(249, 115, 22, 0.5);
}

/* Change text color when selected */
.payment-method input[type="radio"]:checked + label span {
  color: #f97316;
}
  .iti { width: 100%; } /* makes the input full width with flag */

#toastContainer {
    position: fixed;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 50;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Toast message style */
.toast {
    background: #ea580c; /* Dark background for contrast */
    color: #fff; /* White text for readability */
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    font-size: 1rem;
    border-radius: 9999px;
    font-weight: bold;
    text-align: center;
    min-width: 250px;
    animation: fadeIn 0.3s ease-in, fadeOut 0.3s ease-out 3s forwards;
}


.animate-fade-in {
    animation: fadeIn 0.3s ease-in;
}

.animate-fade-out {
    animation: fadeOut 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(10px); }
}
</style>

<script>
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

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;

    document.getElementById('toastContainer').appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3500);
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