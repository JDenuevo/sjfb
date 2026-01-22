<div class="px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="font-medium text-black text-3xl py-10">
      Shop our products
    </h1>
  </div>
  
  <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

    <!-- Search -->
    <div class="flex items-center justify-between mb-5 gap-2">
      <div class="flex-grow"></div>
      <!-- Search Bar Container -->
      <div class="relative w-full">

          <?php 
            $preservedParams = ['page'];
            foreach ($preservedParams as $param) {
              if (isset($_GET[$param]) && !empty($_GET[$param])) {
                echo '<input type="hidden" name="' . $param . '" value="' . htmlspecialchars($_GET[$param]) . '">';
              }
            }
          ?>
          
          <!-- Search Input with Clear Button -->
          <div class="relative">
            <input type="text" name="search" id="searchInput" 
                  value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                  class="py-3 pl-10 pr-12 px-4 block w-full rounded-full text-sm border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" 
                  placeholder="What would you like?" autocomplete="off"/>
            
            <!-- Search Icon -->
            <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none ps-3.5">
              
            </div>
            
            <!-- Clear Button -->
            <button type="button" id="clearSearch" 
                    class="absolute inset-y-0 right-0 flex items-center pr-3 hover:text-gray-700 transition-colors <?php echo !isset($_GET['search']) || empty($_GET['search']) ? 'hidden' : ''; ?>">
              <span class="text-lg font-semibold me-3 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>              </span>
            </button>
          </div>
          
          <!-- Hidden Submit Button -->
          <button type="submit" class="hidden"></button>
        
        <!-- Autocomplete Results -->
        <div id="autocompleteResults" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-lg border border-gray-100 hidden"></div>
      </div>
      
      <!-- Search Button -->
      <button type="submit" class="cursor-pointer p-3 rounded-3xl justify-center items-center inline-flex bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 focus:outline-none ml-2 flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
          <path d="M21 21l-6 -6" />
        </svg>
      </button>

    </div>
  </form>
</div>

<div id="toastContainer" class="fixed bottom-20 right-4 z-[60] flex flex-col gap-2"></div>

<!-- Products Container with Loading State -->
<div id="productsContainer" class="relative min-h-[500px]">
  <div id="productsLoading" class="hidden">
    <div class="flex flex-col items-center justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
      <p class="text-gray-600">Loading products...</p>
    </div>
  </div>
  
  <div id="productsContent">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-5">
      <?php
      // Your existing PHP code for displaying products
      if ($result->num_rows > 0) {
          $products = [];
          while ($row = $result->fetch_assoc()) {
          $product_id = $row['product_id'];
          if (!isset($products[$product_id])) {
              $products[$product_id] = [
                  'product_name' => $row['product_name'],
                  'product_description' => $row['product_description'],
                  'image_url' => !empty($row['image_path']) ? "http://localhost/sjfbi-js/uploads/products/" . $row['image_path'] : "http://localhost/sjfbi-js/uploads/products/default.png",
                  'variants' => []
              ];
          }
          if (!empty($row['variant_name'])) {
              $products[$product_id]['variants'][] = [
                  'variant_id' => $row['variant_id'],
                  'variant_name' => $row['variant_name'],
                  'variant_price' => $row['variant_price'],
                  'discount_price' => $row['discount_price'],
                  'unit_type' => $row['unit_type'] ?? 'piece',
                  'minimum_order' => $row['minimum_order'] ?? 1,
                  'order_increment' => $row['order_increment'] ?? 1
              ];
          }
      }

      foreach ($products as $product_id => $product) {
          $product_name = $product['product_name'];
          $product_description = $product['product_description'];
          $image_url = $product['image_url'];
          $variants = $product['variants'];
  ?>
  <div class="flex flex-col h-full bg-white shadow-lg rounded-lg p-5 relative group">
      <div class="">
        <a href="item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>">
            <img src="<?= htmlspecialchars($image_url) ?>" 
                  alt="<?= htmlspecialchars($product_name) ?>" 
                  class="w-full h-48 object-cover rounded-md mb-4 shadow-sm">
        </a>
        <h3 class="text-xl font-semibold text-gray-800 mb-1">
            <?= htmlspecialchars($product_name) ?>
        </h3>
        <p class="text-md text-gray-500 mb-4">
            <?= htmlspecialchars($product_description) ?>
        </p>
      </div>
      
      <!-- Add to Cart Form -->
      <form class="add-to-cart-form flex flex-col flex-grow" data-product-id="<?= $product_id ?>">
          <input type="hidden" name="add_to_cart" value="1">
          <input type="hidden" name="product_id" value="<?= $product_id ?>">
          <input type="hidden" name="variant_id" value="">
          <input type="hidden" name="product_name" value="<?= htmlspecialchars($product_name) ?>">
          <input type="hidden" name="variant_name" value="">
          <input type="hidden" name="price" value="">
          <input type="hidden" name="image_url" value="<?= htmlspecialchars($image_url) ?>">
          <input type="hidden" name="quantity" value="">
          <input type="hidden" name="unit_type" value="">
          <input type="hidden" name="minimum_order" value="">
          <input type="hidden" name="order_increment" value="">

          <!-- Variant Buttons -->
          <div class="min-h-[72px]">
              <label class="block text-sm font-medium text-gray-700">Select Size:</label>
              <div class="flex flex-wrap gap-2">
                  <?php 
                  $first = true;
                  foreach ($variants as $variant) { ?>
                      <button type="button"
                          class="variant-button px-3 py-2 border rounded-lg text-sm font-medium 
                                 hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200 
                                 <?= $first ? 'selected-variant border-gray-400 bg-gray-100' : 'border-gray-300' ?>"
                          data-product-id="<?= $product_id ?>"
                          data-variant-id="<?= $variant['variant_id'] ?>"
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

          <!-- Quantity Selector with Unit Display -->
          <div class="mt-3">
              <div class="flex items-center">
                  <div class="flex items-center border border-gray-300 rounded">
                      <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                      <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="" placeholder="1" readonly>
                      <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                  </div>
                  &nbsp;
                  <span class="text-sm font-medium text-gray-600 unit-display"></span>
              </div>
              <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
          </div>

          <!-- Price and Discount Display -->
          <div class="price-display mt-3"></div>

          <div class="flex-grow"></div>

          <div class="mt-4 pt-4 border-t border-gray-200">
            <!-- Add to Cart Button -->
            <button type="submit" name="add_to_cart" 
                    class="cursor-pointer w-full py-3 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                    Add to Cart
            </button>
          </div>
          
          <!-- Message to select a variant -->
          <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
          <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
      </form>
  </div>
  <?php
      }
  } else {
      echo "<p class='text-center text-gray-500'>No products found.</p>";
  }
  $conn->close();
  ?>
</div>

<script>
// Your existing product functions from the original code
document.addEventListener('DOMContentLoaded', function() {
    // Store the original initialization functions
    function initializeProductFunctionality() {
        // Handle variant selection - ORIGINAL CODE
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

                const form = document.querySelector(`.add-to-cart-form[data-product-id="${productId}"]`);
                
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

        // Handle quantity changes - ORIGINAL CODE
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const form = button.closest('.add-to-cart-form');
                const quantityInput = form.querySelector('.quantity');
                const minimumOrder = parseFloat(form.querySelector('input[name="minimum_order"]').value);
                const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
                const currentQty = parseFloat(quantityInput.value);

                const newQty = Math.max(minimumOrder, currentQty - orderIncrement);
                quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
                form.querySelector('input[name="quantity"]').value = newQty;
                
                updateTotalPrice(form);
            });
        });

        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const form = button.closest('.add-to-cart-form');
                const quantityInput = form.querySelector('.quantity');
                const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
                const currentQty = parseFloat(quantityInput.value);

                const newQty = currentQty + orderIncrement;
                quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
                form.querySelector('input[name="quantity"]').value = newQty;
                
                updateTotalPrice(form);
            });
        });

        function updatePriceDisplay(form, variantPrice, discountPrice, quantity) {
            const priceDisplay = form.querySelector('.price-display');
            const price = discountPrice > 0 ? discountPrice : variantPrice;
            const total = price * quantity;

            if (discountPrice > 0) {
                priceDisplay.innerHTML = `
                    <span class="line-through text-gray-500 text-sm">₱${(variantPrice * quantity).toFixed(2)}</span>
                    <span class="text-red-600 font-bold ml-2">₱${total.toFixed(2)}</span>
                `;
            } else {
                priceDisplay.innerHTML = `
                    <span class="text-gray-800 font-bold">₱${total.toFixed(2)}</span>
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

        // Auto-select first variant on load - ORIGINAL CODE
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            const firstButton = form.querySelector('.variant-button');
            if (firstButton) firstButton.click();
        });

        // Enhanced add to cart handler with validation - ORIGINAL CODE
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

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
                    const response = await fetch('./functions/add_to_cart.php', {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        showToast('Product added to cart', 'success');
                        await updateCartUI();
                        
                        // Reset to minimum order
                        const firstButton = form.querySelector('.variant-button');
                        if (firstButton) firstButton.click();
                    } else {
                        showToast(data.message || 'Failed to add product', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred', 'error');
                }
            });
        });
    }

    // Call the original initialization on page load
    initializeProductFunctionality();

    // Now add the search functionality
    const searchInput = document.getElementById('searchInput');
    const autocompleteResults = document.getElementById('autocompleteResults');
    const clearSearchBtn = document.getElementById('clearSearch');
    const productsContainer = document.getElementById('productsContainer');
    const productsContent = document.getElementById('productsContent');
    const productsLoading = document.getElementById('productsLoading');
    
    let searchTimeout;
    let currentSearchTerm = '<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>';

    // Set initial search value
    if (currentSearchTerm) {
        searchInput.value = currentSearchTerm;
        clearSearchBtn.classList.remove('hidden');
    }

    // Show/hide clear button based on input
    searchInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            clearSearchBtn.classList.remove('hidden');
        } else {
            clearSearchBtn.classList.add('hidden');
            autocompleteResults.classList.add('hidden');
        }
    });

    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        autocompleteResults.classList.add('hidden');
        
        // Remove search parameter from URL
        const url = new URL(window.location);
        url.searchParams.delete('search');
        window.history.pushState({}, '', url);
        
        // Reload products without search filter
        performSearch('');
    });

    // Handle search input with debounce
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        currentSearchTerm = query;
        
        clearTimeout(searchTimeout);
        
        // CHANGE: Changed from 2 to 1 character
        if (query.length < 1) {
            autocompleteResults.classList.add('hidden');
            return;
        }

        // Show loading in dropdown
        autocompleteResults.innerHTML = '<div class="p-4 text-center text-gray-500">Loading...</div>';
        autocompleteResults.classList.remove('hidden');

        searchTimeout = setTimeout(() => {
            fetchAutocompleteResults(query);
        }, 200); // Reduced delay to 200ms for faster response
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
            autocompleteResults.classList.add('hidden');
        }
    });

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            autocompleteResults.classList.add('hidden');
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            const firstItem = autocompleteResults.querySelector('.autocomplete-item');
            if (firstItem) {
                firstItem.focus();
            }
        } else if (e.key === 'Enter' && currentSearchTerm.length > 0) {
            e.preventDefault();
            performSearch(currentSearchTerm);
        }
    });

    // Fetch autocomplete results
    async function fetchAutocompleteResults(query) {
        try {
            const response = await fetch(`./functions/auto_complete.php?query=${encodeURIComponent(query)}&limit=8`);
            const results = await response.json();
            
            displayAutocompleteResults(results);
        } catch (error) {
            console.error('Error fetching autocomplete:', error);
            autocompleteResults.innerHTML = '<div class="p-4 text-center text-red-500">Error loading results</div>';
        }
    }

    // Display autocomplete results
    function displayAutocompleteResults(results) {
        if (results.length === 0) {
            autocompleteResults.innerHTML = '<div class="p-4 text-center text-gray-500">No products found. Try a different search term.</div>';
            autocompleteResults.classList.remove('hidden');
            return;
        }

        autocompleteResults.innerHTML = '';
        
        // Add result count header
        const header = document.createElement('div');
        header.className = 'px-4 py-2 bg-gray-50 border-b border-gray-200';
        header.innerHTML = `<p class="text-sm font-semibold text-gray-600">${results.length} results found</p>`;
        autocompleteResults.appendChild(header);
        
        results.forEach(product => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item autocomplete-item px-3 py-2 hover:bg-gray-50 transition cursor-pointer focus:bg-gray-50 outline-none'; 
            item.tabIndex = 0;

            
            // Highlight matching text
            const highlightedName = highlightText(product.name, currentSearchTerm);
            const highlightedDesc = highlightText(product.description, currentSearchTerm);
            
            // Add match type indicator for single character searches
            let matchType = '';
            if (currentSearchTerm.length === 1) {
                if (product.name.toLowerCase().startsWith(currentSearchTerm.toLowerCase())) {
                    matchType = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 ml-2">Name starts with</span>';
                } else if (product.variant && product.variant.toLowerCase().startsWith(currentSearchTerm.toLowerCase())) {
                    matchType = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-2">Variant starts with</span>';
                }
            }
            
            item.innerHTML = `

                ${matchType}


                <div class="flex items-center gap-1">

                    <!-- Icon -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <!-- Text block -->
                    <div class="flex-1 min-w-0">
                       <div class="flex items-center gap-2">
                            <h4 class="text-sm font-semibold text-gray-900 text-start">
                            ${highlightedName}
                            </h4>
                        </div>

                        ${product.variant ? `
                            <p class="text-xs text-gray-500 mt-0.5 text-start">
                            ${highlightText(product.variant, currentSearchTerm)}
                            </p>
                        ` : ''}

                        <p class="text-xs text-gray-400 mt-1 text-start">
                            ${highlightedDesc}
                        </p>
                    </div>

                    <!-- Category (END / RIGHT) -->
                    <div class="ml-auto flex-shrink-0 self-start">
                        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                            ${product.category || 'General'}
                        </span>
                    </div>

                </div>                
            `;
            
            item.addEventListener('click', (e) => {
                e.preventDefault();
                searchInput.value = product.name;
                performSearch(product.name);
            });
            
            // Keyboard navigation
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchInput.value = product.name;
                    performSearch(product.name);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = item.nextElementSibling || autocompleteResults.querySelector('.autocomplete-item');
                    if (next) next.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = item.previousElementSibling || searchInput;
                    if (prev) prev.focus();
                } else if (e.key === 'Escape') {
                    autocompleteResults.classList.add('hidden');
                    searchInput.focus();
                }
            });
            
            autocompleteResults.appendChild(item);
        });
        
        autocompleteResults.classList.remove('hidden');
    }

    // Highlight matching text
    function highlightText(text, query) {
        if (!query || !text) return text;
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedQuery})`, 'gi');
        return text.replace(regex, '<span class="bg-yellow-200 font-semibold">$1</span>');
    }

    // Add this function to show popular searches when input is empty
    function showPopularSearches() {
        // You can customize these popular searches based on your data
        const popularSearches = [
            'Bangus',
            'Tilapia',
            'Lapu-Lapu',
            'Crab',
            'Salmon',
            'Tuna'
        ];
        
        autocompleteResults.innerHTML = '';
        
        // Add popular searches header
        const header = document.createElement('div');
        header.className = 'px-4 py-2 bg-gray-50 border-b border-gray-200';
        header.innerHTML = '<p class="text-sm font-semibold text-gray-600">Popular Searches</p>';
        autocompleteResults.appendChild(header);
        
        // Add popular search items
        popularSearches.forEach(term => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item block z-0 px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors duration-150 cursor-pointer';
            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="font-medium text-gray-700">${term}</div>
                    <svg xmlns="shrink-0 mx-3 text-gray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                </div>
            `;
            
            item.addEventListener('click', (e) => {
                e.preventDefault();
                searchInput.value = term;
                performSearch(term);
            });
            
            autocompleteResults.appendChild(item);
        });
        
        autocompleteResults.classList.remove('hidden');
    }

    // Update the search input event handler to show popular searches when empty
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length === 0) {
            showPopularSearches();
        }
    });

    // Perform search and load products
    async function performSearch(query) {
        // Update URL with search parameter
        const url = new URL(window.location);
        if (query.trim()) {
            url.searchParams.set('search', query);
        } else {
            url.searchParams.delete('search');
        }
        
        window.history.pushState({}, '', url);

        // Show loading state
        productsContent.classList.add('opacity-50');
        productsLoading.classList.remove('hidden');
        
        try {
            let fetchUrl = './functions/fetch_products.php';
            
            // Add search parameter to fetch URL
            if (query.trim()) {
                fetchUrl += `?search=${encodeURIComponent(query)}`;
            }
            
            const response = await fetch(fetchUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const html = await response.text();
            
            // Replace the products content
            productsContent.innerHTML = html;
            
            // Re-initialize product functionality for the new content
            initializeProductFunctionality();
            
        } catch (error) {
            console.error('Error loading products:', error);
            productsContent.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-red-500 text-lg mb-2">Error loading products. Please try again.</p>
                    <button onclick="window.location.reload()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Reload Page
                    </button>
                </div>
            `;
        } finally {
            productsContent.classList.remove('opacity-50');
            productsLoading.classList.add('hidden');
            
            // Close autocomplete
            autocompleteResults.classList.add('hidden');
            
            // Scroll to products if we searched
            if (query.trim()) {
                productsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        
        if (searchParam) {
            searchInput.value = searchParam;
            performSearch(searchParam);
        } else {
            searchInput.value = '';
            performSearch('');
        }
    });

    // Toast and cart functions
    async function updateCartUI() {
        try {
            const response = await fetch('./functions/fetch_cart_items.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                document.getElementById('cart-items-list').innerHTML = data.cart_items;
                
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = data.cart_count;
                    el.classList.add('animate-bounce');
                    setTimeout(() => el.classList.remove('animate-bounce'), 1000);
                });
                
                const cartTotal = document.getElementById('cart-total-sidebar');
                if (cartTotal) {
                    cartTotal.textContent = `₱${data.cart_total.toFixed(2)}`;
                }
            }
        } catch (error) {
            console.error('Error updating cart:', error);
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            console.error('Toast container not found');
            return;
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
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
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Initialize on page load if there's a search parameter
    if (currentSearchTerm) {
        performSearch(currentSearchTerm);
    }
});
</script>

<style>
.variant-button {
    background-color: white;
    border: 1px solid #d1d5db;
    color: #374151;
    transition: all 0.2s ease;
}

.variant-button.selected-variant {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #FFFFFF;
}

/* Add to cart button disabled state */
button[name="add_to_cart"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

button[name="add_to_cart"]:not(:disabled) {
    opacity: 1;
    cursor: pointer;
}

/* Quantity buttons */
.decrease-quantity, .increase-quantity {
    background-color: white;
    border: 1px solid #d1d5db;
    color: #374151;
    transition: all 0.2s ease;
}

.decrease-quantity:hover, .increase-quantity:hover {
    background-color: #f59e0b;
    color: white;
    border-color: #f97316;
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

/* Search Bar Styles */
#searchInput {
    transition: all 0.2s ease;
}

#searchInput:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Add these to your existing styles */
#autocompleteResults {
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

#autocompleteResults::-webkit-scrollbar {
    width: 6px;
}

#autocompleteResults::-webkit-scrollbar-track {
    background: transparent;
}

#autocompleteResults::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

.autocomplete-item {
    transition: all 0.15s ease;
}

.autocomplete-item:hover {
    background-color: #f3f5f6;
    transform: translateX(2px);
}

.autocomplete-item:focus {
    outline: none;
    background-color: #f3f5f6;
}

/* Highlight for single character matches */
.single-char-match {
    font-weight: 600;
    color: #1e40af;
}

/* Loading animation */
.loading-dots {
    display: inline-block;
    margin-left: 5px;
}

.loading-dots::after {
    content: '.';
    animation: dots 1.5s steps(5, end) infinite;
}

@keyframes dots {
    0%, 20% { content: '.'; }
    40% { content: '..'; }
    60%, 100% { content: '...'; }
}

/* Loading animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Smooth transitions */
#productsContent {
    transition: opacity 0.3s ease;
}

  /* Wrapper */
.search-wrapper {
  position: relative;
  width: 100%;
}

/* Clear button (RIGHT) */
.clear-btn {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  z-index: 10;
}

/* Hover effect */
.clear-btn:hover {
  color: #374151;
}

/* Hidden class (used by your JS) */
.hidden {
  display: none !important;
}

</style>