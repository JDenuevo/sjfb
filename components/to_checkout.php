<?php
$cart = $_SESSION['cart'] ?? []; // Retrieve cart data from the session
?>

<!-- Container -->
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
  <h1 class="text-center text-4xl font-bold" data-aos="fade-up" data-aos-duration="2000">
    Checkout
  </h1>
  <p class="text-gray-600 text-center mb-10" data-aos="fade-up" data-aos-duration="2000">
    Please fill up your Personal and Billing Address details.
  </p>
  <form action="./functions/add.php" method="POST">
    <div class="grid grid-cols-3">
      <div class="col-span-2 shadow-lg p-4">
        <!-- Form -->
          <h3 class="text-xl font-bold py-4">Contact Details</h3>
          <div class="grid gap-y-4">

            <div class="grid grid-cols-2 gap-4">
              <!-- Form Group -->
              <div>
                <label for="first_name" class="block text-sm mb-2 text-dark">First name</label>
                <div class="relative">
                  <input type="text" id="first_name" name="first_name" placeholder="First name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="first_name-error">Input your first name.</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="last_name" class="block text-sm mb-2 text-dark">Last name</label>
                <div class="relative">
                  <input type="text" id="last_name" name="last_name" placeholder="Last name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="last_name-error">Input your last name.</p>
              </div>
              <!-- End Form Group -->
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Form Group -->
              <div>
                <label for="Email" class="block text-sm mb-2 text-dark">Email address</label>
                <div class="relative">
                  <input type="email" id="Email" name="email" placeholder="Email address" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="email-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="phone_number" class="block text-sm mb-2 text-dark">Phone Number</label>
                <div class="relative">
                  <input type="number" id="phone_number" name="phone_number" placeholder="Phone Number" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="phone_number-error">Enter a valid phone number no characters allowed.</p>
              </div>
              <!-- End Form Group -->
            </div>
            
            <!-- Form Group -->
            <div>
              <label for="address" class="block text-sm mb-2 text-dark">Address</label>
              <div class="relative">
                <input type="text" id="address" name="address" placeholder="Address" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                  <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                  </svg>
                </div>
              </div>
              <p class="hidden text-xs text-red-600 mt-2" id="address-error">Enter a valid phone number no characters allowed.</p>
            </div>
            <!-- End Form Group -->


            <div class="grid grid-cols-2 gap-4">
              <!-- Form Group -->
              <div>
                <label for="city" class="block text-sm mb-2 text-dark">City</label>
                <div class="relative">
                  <input type="text" id="city" name="city" placeholder="City" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="city-error">Input your City name.</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="postal_code" class="block text-sm mb-2 text-dark">Postal Code</label>
                <div class="relative">
                  <input type="number" id="postal_code" name="postal_code" placeholder="Postal Code" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="postal_code-error">Input your last name.</p>
              </div>
              <!-- End Form Group -->
            </div>

            <h3 class="text-xl font-bold py-4">Payment</h3>
  
            <div class="relative flex items-start w-full">
              <div class="flex items-center h-5 mt-1">
                <select id="payment_method" name="payment_method" class="border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <option value="cod">Cash on Delivery</option>
                  <option value="ewallet">Gcash/Paymaya</option>
                  <option value="bank">Bank Transfer</option>
                </select>
              </div>
            </div>
            <div id="hs-payment-method-content" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" role="region" aria-labelledby="hs-payment-method">
              <div class="pb-4 px-6">
                <p class="text-sm text-gray-600">
                  Select your preferred payment method.
                </p>
              </div>
            </div>
      
            <button type="submit" name="complete_order" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500">
                Complete Order
            </button>

          </div>
        <!-- End Form -->
      </div>

      <div class="col-span-1 shadow-lg">
          <div class="flex justify-start items-center p-4 border-b border-gray-200">
          <h2 class="font-bold text-xl">To checkout: <span id="cart-count-sidebar" class="text-orange-500"><?php echo count($cart); ?></span></h2>
          </div>

          <div id="cart-items-list" class="p-4 overflow-y-scroll h-96">
              <?php if (!empty($cart)): ?>
                  <?php foreach ($cart as $index => $item): ?>
                      <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200">
                          <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                          <div class="flex-grow p-2">
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
                          <button type="button" class="remove bg-slate-500 text-red-500 hover:text-red-700 ml-4" 
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
            
              <!-- Add the "Update Cart" button -->
              <button type="button" id="update-cart-button" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 hover:scale-110 transition-all duration-500">
                  Update Cart
              </button>

              <p class="text-center mt-5">or</p>
            
              <div class="text-center my-5">
                  <a href="index.php" class="cursor-pointer text-blue-500 hover:underline">Continue Shopping</a>
              </div>
          </div>
      </div>

    </div>
    </form>

</div>

<style>
  input[type=number]::-webkit-inner-spin-button, 
  input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
  } 
</style>

<script>
  
document.addEventListener("DOMContentLoaded", function () {
    // Handle "Update Cart" button click
    document.getElementById('update-cart-button').addEventListener('click', function() {
        const cartItems = document.querySelectorAll('.cart-item');
        const updates = [];

        cartItems.forEach(cartItem => {
            const productId = cartItem.querySelector('.quantity').dataset.productId;
            const variantId = cartItem.querySelector('.quantity').dataset.variantId;
            const quantity = parseInt(cartItem.querySelector('.quantity').value);

            updates.push({
                product_id: productId,
                variant_id: variantId,
                quantity: quantity
            });
        });

        fetch('./functions/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updates)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                fetchCart(); // Refresh the cart sidebar
                alert('Cart updated successfully');
            } else {
                alert('Failed to update cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });

        location.reload();

    });

    // Function to fetch and update the cart
    function fetchCart() {
        fetch('./functions/fetch_cart_items.php')
            .then(response => response.json())
            .then(data => {
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