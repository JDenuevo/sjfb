<?php
// Check if cart exists in session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>

<div id="hs-cart-sidebar" class="fixed inset-0 z-50 w-full bg-gray-900 bg-opacity-50 hidden overflow-hidden" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div id="sidebar-white-bg" class="fixed top-0 right-0 h-full bg-white shadow-xl transform transition-transform duration-300 translate-x-full overflow-y-auto w-96">
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

            <form action="./functions/add.php" method="POST">
              <div class="flex-1 overflow-y-auto px-4 py-2">
                  <div id="cart-items-list">
                      <?php if (!empty($cart)): ?>
                          <?php foreach ($cart as $item): ?>
                              <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200">
                                  <img src="path_to_product_images/<?php echo $item['product_id']; ?>.jpg" alt="<?php echo $item['product_name']; ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                                  <div class="flex-grow">
                                      <h3 class="font-medium text-base mb-2 flex justify-between"><?php echo $item['product_name']; ?></h3>
                                      <p class="text-sm text-gray-500 mb-4"><?php echo $item['product_description']; ?></p>
                                      <div class="flex items-center justify-between mt-2">
                                          <div class="flex items-center border border-gray-300 rounded">
                                              <button class="px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-orange-600" data-product-id="<?php echo $item['product_id']; ?>">-</button>
                                              <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0 focus:outline-none focus:ring-0" value="<?php echo $item['quantity']; ?>" readonly>
                                              <button class="px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-orange-600" data-product-id="<?php echo $item['product_id']; ?>">+</button>
                                          </div>
                                          <span class="price ml-4 font-medium text-sm">₱<?php echo number_format($item['product_price'], 2); ?></span>
                                      </div>
                                  </div>
                                  <button class="remove text-red-500 hover:text-red-700 ml-4" data-product-id="<?php echo $item['product_id']; ?>">
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
              </div>

              <div class="p-4 border-t border-gray-200">
                  <div class="flex justify-between items-center">
                      <span class="font-bold">Subtotal:</span>
                      <span id="cart-total-sidebar" class="font-medium">
                          ₱<?php echo number_format(array_sum(array_map(fn($item) => $item['product_price'] * $item['quantity'], $cart)), 2); ?>
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
            </form>
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
    // Select all plus and minus buttons
    document.querySelectorAll(".cart-item .px-1").forEach(button => {
        button.addEventListener("click", function () {
            let input = this.parentElement.querySelector(".quantity"); // Select input field
            let currentValue = parseInt(input.value);

            if (this.textContent === "+") {
                input.value = currentValue + 1; // Increase quantity
            } else if (this.textContent === "-" && currentValue > 1) {
                input.value = currentValue - 1; // Decrease quantity but not below 1
            }
        });
    });
});

</script>
