<!-- Off-Canvas -->

<?php

// Sample product data (replace with your database or data source)
$products = [
    [
        'image' => 'https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606', // Path to image
        'name' => 'Fresh Boston Lobster (~1kg: 1-2 pcs)',
        'description' => 'Fresh',
        'price' => 4199.99,
        'quantity' => 1 // Initial quantity
    ],
    [
        'image' => 'https://fisherscart.com/cdn/shop/files/FullSizeRender_280b767d-a17c-4437-b621-e48b805a0f87_1024x1024@2x.jpg?v=1723812491', // Path to image
        'name' => 'Fresh Hipon (~1kg)',
        'description' => '1kg (25-55pcs)',
        'price' => 729.99,
        'quantity' => 1
    ],
];

?>

<div id="hs-cart-sidebar" class="fixed inset-0 z-50 w-full bg-gray-900 bg-opacity-50 hidden overflow-hidden" role="dialog" tabindex="-1" aria-label="Sidebar">
    <div id="sidebar-white-bg" class="fixed top-0 left-0 h-full bg-white shadow-xl transform transition-transform duration-300 translate-x-full overflow-y-auto w-96">  <div class="flex flex-col h-full">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h2 class="font-bold text-xl">Your Cart <span id="cart-count-sidebar" class="text-gray-500">0</span></h2>
                <button type="button" class="btn btn-icon btn-sm btn-ghost" onclick="closeOffCanvas()">  <span class="sr-only">Close</span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-2">
              <div id="cart-items-list">
                  <?php foreach ($products as $product): ?>
                      <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200">
                          <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                          <div class="flex-grow">
                            <h3 class="font-medium text-base mb-2 flex justify-between"><?php echo $product['name']; ?></h3>
                            <p class="text-sm text-gray-500 mb-4"><?php echo $product['description']; ?></p> 
                            <div class="flex items-center justify-between mt-2">
                              <div class="flex items-center border border-gray-300 rounded">
                                <button class="px-1 py-0.5 rounded-l text-sm" data-product-name="<?php echo $product['name']; ?>">-</button>
                                <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0 focus:outline-none focus:ring-0" data-product-name="<?php echo $product['name']; ?>" value="<?php echo $product['quantity']; ?>">
                                <button class="px-1 py-0.5 rounded-r text-sm" data-product-name="<?php echo $product['name']; ?>">+</button>
                              </div>
                              <span class="price ml-4 font-medium text-sm">₱<?php echo $product['price']; ?></span>
                            </div>
                          </div>
                          <button class="remove text-red-500 hover:text-red-700 ml-4" data-product-name="<?php echo $product['name']; ?>">
                            <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M18 6 6 18"></path>
                              <path d="m6 6 12 12"></path>
                            </svg>
                          </button>
                      </div>
                  <?php endforeach; ?>
              </div>
            </div>
            
            <div class="p-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="font-bold">Subtotal:</span>
                    <span id="cart-total-sidebar" class="font-medium">₱0.00</span>  </div>
                <p class="text-sm text-gray-500">Taxes and shipping calculated at checkout</p>

                <button type="submit" class="w-full mt-2 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 hover:scale-110 transition-all duration-500" aria-label="Submit the form">Checkout</button>  
                <div class="text-center my-5">
                    <a href="cart.php" class="text-gray-500 hover:underline">View Cart</a><br>
                    <a class="text-gray-500 hover:underline" onclick="closeOffCanvas()">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

  #sidebar-white-bg {
    width: 400px;
    transition: transform 0.3s ease-in-out;
  }

  /* Off-Canvas visible state */
  #hs-cart-sidebar.open {
    display: block;
  }

</style>

<script>
// JavaScript to handle quantity changes and removal (example)
const cartItemsList = document.getElementById('cart-items-list');

cartItemsList.addEventListener('click', function(event) {
    if (event.target.classList.contains('increase')) {
        const productName = event.target.dataset.productName;
        const quantitySpan = cartItemsList.querySelector('.quantity[data-product-name="' + productName + '"]');
        let quantity = parseInt(quantitySpan.textContent);
        quantity++;
        quantitySpan.textContent = quantity;
        // Update cart data (e.g., in local storage or send an AJAX request)
    } else if (event.target.classList.contains('decrease')) {
        const productName = event.target.dataset.productName;
        const quantitySpan = cartItemsList.querySelector('.quantity[data-product-name="' + productName + '"]');
        let quantity = parseInt(quantitySpan.textContent);
        if (quantity > 1) { // Prevent going below 1
            quantity--;
            quantitySpan.textContent = quantity;
            // Update cart data
        }
    } else if (event.target.classList.contains('remove')) {
      const productName = event.target.dataset.productName;
      const cartItem = event.target.closest('.cart-item');
      cartItem.remove();
      // Update cart data
    }
});
    // Function to open off-canvas
  function openOffCanvas() {
    const offCanvas = document.getElementById('hs-cart-sidebar');
    offCanvas.classList.remove('hidden');
    setTimeout(() => offCanvas.querySelector('div').classList.remove('translate-x-full'), -10);
  }

  // Function to close off-canvas
  function closeOffCanvas() {
    const offCanvas = document.getElementById('hs-cart-sidebar');
    offCanvas.querySelector('div').classList.add('translate-x-full');
    setTimeout(() => offCanvas.classList.add('hidden'), 300); // Delay to allow animation
  }

</script>
