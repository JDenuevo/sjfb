<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="text-3xl font-bold text-center mb-6">Shop Our Products</h1>
    <p class="mt-1 text-gray-800">
      Explore our fresh products directly in our market
    </p>
  </div>

  <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <!-- Product Card -->
    <div class="bg-white shadow-lg rounded-lg p-4 relative group">
      <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" 
        alt="Product" class="w-full h-48 object-cover rounded-md mb-4 shadow-sm">

      <h3 class="text-lg font-semibold text-gray-800">Fresh Boston Lobster (~1kg: 1-2 pcs)</h3>
      <p class="text-sm text-gray-500">Fresh</p>
      <p class="text-gray-600 text-lg font-semibold">₱4199.99</p>

      <div class="flex items-center">
        <label for="quantity" class="mr-2 text-gray-700 px-2">Qty:</label>

        <!-- Quantity Input -->
        <div class="flex items-center mt-2">
          <button class="px-3 py-1 rounded-l text-sm bg-gray-200 hover:bg-orange-600 hover:text-white transition">
            -
          </button>
          <input type="number" class="quantity w-12 px-1 py-1 text-center text-sm border-0 focus:outline-none focus:ring-0" 
            id="quantity" name="quantity" min="1" value="1">
          <button class="px-3 py-1 rounded-r text-sm bg-gray-200 hover:bg-orange-600 hover:text-white transition">
            +
          </button>
        </div>
      </div>

      <!-- Add to Cart Button -->
      <button name="add_to_cart" class="mt-4 w-full size-10 rounded-full justify-center items-center inline-flex bg-orange-600 hover:bg-orange-400 text-white hover:scale-110 transition-all duration-500 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
          <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
          <path d="M17 17h-11v-14h-2" />
          <path d="M6 5l14 1l-1 7h-13" />
        </svg>    
        Add to Cart
      </button>
    </div>

  </div>
</div>
<!-- End Product Card -->

<style>
  input[type=number]::-webkit-inner-spin-button, 
  input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".bg-white .flex.items-center .px-3").forEach(button => {
        button.addEventListener("click", function () {
            let input = this.parentElement.querySelector(".quantity");
            let currentValue = parseInt(input.value);

            if (this.textContent.trim() === "+") {
                input.value = currentValue + 1;
            } else if (this.textContent.trim() === "-" && currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    });
  });
</script>