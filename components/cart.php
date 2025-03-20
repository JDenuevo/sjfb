<?php
$cart = $_SESSION['cart'] ?? [];
?>

<div id="hs-cart-sidebar" class="fixed inset-0 z-50 bg-gray-900 bg-opacity-50 hidden overflow-hidden" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div id="sidebar-white-bg" class="fixed top-0 right-0 h-full bg-white shadow-xl transform transition-transform duration-300 translate-x-full overflow-y-auto" style="width: 450px;">
        <div class="flex flex-col h-full">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h2 class="font-bold text-xl">Your Cart <span id="cart-count-sidebar"><?php echo count($cart); ?></span></h2>                
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
                        <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200">
                            <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                            <div class="flex-grow">
                                <h3 class="font-medium text-base mb-2 flex justify-between"><?= $item['product_name'] ?></h3>
                                <p class="text-sm text-gray-500 mb-4"><?= $item['variant_name'] ?></p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center border border-gray-300 rounded">
                                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600" 
                                                data-product-id="<?= $item['product_id'] ?>" 
                                                data-variant-id="<?= $item['variant_id'] ?>">-</button>
                                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                                               value="<?= $item['quantity'] ?>" readonly 
                                               data-product-id="<?= $item['product_id'] ?>" 
                                               data-variant-id="<?= $item['variant_id'] ?>">
                                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600" 
                                                data-product-id="<?= $item['product_id'] ?>" 
                                                data-variant-id="<?= $item['variant_id'] ?>">+</button>
                                    </div>
                                    <span class="price ml-4 font-medium text-sm" data-price-per-unit="<?= $item['price'] ?>">
                                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="remove text-red-500 hover:text-red-700 ml-4" 
                                  data-product-id="<?= $item['product_id'] ?>" 
                                  data-variant-id="<?= $item['variant_id'] ?>">
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
                <button name="order" type="submit" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">
                    Checkout
                </button>
                <div class="text-center my-5">
                    <a href="cart.php" class="text-gray-500 hover:underline">View Cart</a><br>
                    <a class="text-gray-500 hover:underline" onclick="closeOffCanvas()">Continue Shopping</a>
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
</style>

<script>
function openOffCanvas() {
    document.getElementById('hs-cart-sidebar').classList.remove('hidden');
    setTimeout(() => document.getElementById('sidebar-white-bg').classList.remove('translate-x-full'), 10);
}

function closeOffCanvas() {
    document.getElementById('sidebar-white-bg').classList.add('translate-x-full');
    setTimeout(() => document.getElementById('hs-cart-sidebar').classList.add('hidden'), 300);
}

document.addEventListener("DOMContentLoaded", function () {
    // Event delegation for remove and update quantity buttons
    document.addEventListener('click', function(e) {
        // Handle remove item from cart
        if (e.target.closest('.remove')) {
            console.log('Remove button clicked'); // Debugging
            const removeButton = e.target.closest('.remove');
            const productId = removeButton.dataset.productId;
            const variantId = removeButton.dataset.variantId;

            fetch('./functions/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    variant_id: variantId
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // Debugging
                if (data.status === 'success') {
                    fetchCart(); // Refresh the cart sidebar
                } else {
                    alert('Failed to remove product from cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Handle increase quantity in cart
        if (e.target.classList.contains('increase-quantity')) {
            console.log('Increase quantity button clicked'); // Debugging
            const button = e.target;
            const cartItem = button.closest('.cart-item');
            console.log('Cart item:', cartItem); // Debugging

            if (cartItem) {
                const quantityInput = cartItem.querySelector('.quantity');
                console.log('Quantity input:', quantityInput); // Debugging

                if (quantityInput) {
                    let quantity = parseInt(quantityInput.value);
                    quantity += 1;
                    quantityInput.value = quantity;

                    // Update the price
                    const priceElement = cartItem.querySelector('.price');
                    const pricePerUnit = parseFloat(priceElement.dataset.pricePerUnit || 0);
                    if (priceElement && !isNaN(pricePerUnit)) {
                        priceElement.textContent = `₱${(pricePerUnit * quantity).toFixed(2)}`;
                    }

                    // Send update to the server
                    const productId = button.dataset.productId;
                    const variantId = button.dataset.variantId;

                    fetch('./functions/update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            variant_id: variantId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            fetchCart(); // Refresh the cart sidebar
                        } else {
                            alert('Failed to update cart: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            }
        }

        // Handle decrease quantity in cart
        if (e.target.classList.contains('decrease-quantity')) {
            console.log('Decrease quantity button clicked'); // Debugging
            const button = e.target;
            const cartItem = button.closest('.cart-item');
            console.log('Cart item:', cartItem); // Debugging

            if (cartItem) {
                const quantityInput = cartItem.querySelector('.quantity');
                console.log('Quantity input:', quantityInput); // Debugging

                if (quantityInput && quantityInput.value > 1) {
                    let quantity = parseInt(quantityInput.value);
                    quantity -= 1;
                    quantityInput.value = quantity;

                    // Update the price
                    const priceElement = cartItem.querySelector('.price');
                    const pricePerUnit = parseFloat(priceElement.dataset.pricePerUnit || 0);
                    if (priceElement && !isNaN(pricePerUnit)) {
                        priceElement.textContent = `₱${(pricePerUnit * quantity).toFixed(2)}`;
                    }

                    // Send update to the server
                    const productId = button.dataset.productId;
                    const variantId = button.dataset.variantId;

                    fetch('./functions/update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            variant_id: variantId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            fetchCart(); // Refresh the cart sidebar
                        } else {
                            alert('Failed to update cart: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            }
        }
    });

    // Function to fetch and update the cart
    function fetchCart() {
        console.log('Fetching cart...'); // Debugging
        fetch('./functions/fetch_cart_items.php')
            .then(response => response.json())
            .then(data => {
                console.log('Fetched cart data:', data); // Debugging

                // Update the cart items list
                document.getElementById('cart-items-list').innerHTML = data.cart_items;

                // Update the cart subtotal
                const cartTotalElement = document.getElementById('cart-total-sidebar');
                cartTotalElement.textContent = `₱${data.cart_total.toFixed(2)}`;

                // Update the cart count
                const cartCountElement = document.getElementById('cart-count-sidebar');
                cartCountElement.textContent = data.cart_count;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
});
</script>