<div class="px-4 sm:px-6 lg:px-8 mx-auto">

  <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

    <!-- Search -->
    <div class="flex items-center justify-between my-5 gap-2">
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
          <input type="text" name="search" id="searchInput" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="py-3 pl-10 pr-12 px-4 block w-full rounded-full text-sm border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="What would you like?" autocomplete="off"/>
          <!-- Clear Button -->
          <button type="button" id="clearSearch" class="absolute inset-y-0 right-0 flex items-center pr-3 hover:text-gray-700 transition-colors <?php echo !isset($_GET['search']) || empty($_GET['search']) ? 'hidden' : ''; ?>">
            <span class="text-lg font-semibold me-3 text-gray-400">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
            </span>
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

<!-- Main Content Grid: Sidebar + Products -->
<div class="grid lg:grid-cols-4 gap-8">
  <!-- Sidebar Filters - Left Column (1 column on desktop) -->
  <div class="md:col-span-1 lg:col-span-1 relative">
      <aside class="space-y-6">
          <!-- Categories -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          
              <svg class="w-5 h-5 me-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                  </svg>
                  Categories
              </h3>
              
              <?php
              // Fetch all main categories with product counts
              $cat_query = "SELECT 
                              pc.category_id, 
                              pc.category_name, 
                              pc.category_slug,
                              COUNT(DISTINCT pcl.product_id) as product_count
                            FROM product_categories pc
                            LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
                            LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
                            LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.stock_status = 'In Stock'
                            WHERE pc.parent_id IS NULL 
                              AND pc.is_active = 1
                            GROUP BY pc.category_id
                            ORDER BY pc.sort_order ASC, pc.category_name ASC";
              
              $cat_result = $conn->query($cat_query);
              
              // Get currently selected categories from URL
              $selected_cats = isset($_GET['category']) ? explode(',', $_GET['category']) : [];
              ?>
              
              <div class="space-y-2">
                  <!-- All Products Option -->
                  <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                      <input type="checkbox" 
                            class="category-filter w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-orange-500"
                            value="all"
                            data-category-id="all"
                            <?php echo empty($selected_cats) || in_array('all', $selected_cats) ? 'checked' : ''; ?>
                            onchange="handleCategoryChange(this)">
                      <span class="text-sm text-gray-700 flex-1">All Products</span>
                      <?php
                      // Get total products count
                      $total_query = "SELECT COUNT(DISTINCT p.product_id) as total 
                                    FROM products p 
                                    JOIN product_variants pv ON p.product_id = pv.product_id 
                                    WHERE p.is_deleted = 0 AND pv.stock_status = 'In Stock'";
                      $total_result = $conn->query($total_query);
                      $total_count = $total_result->fetch_assoc()['total'];
                      ?>
                      <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $total_count ?></span>
                  </label>

                  <?php 
                  if ($cat_result && $cat_result->num_rows > 0):
                      while ($category = $cat_result->fetch_assoc()): 
                          // Get subcategories for this category
                          $sub_query = "SELECT 
                                          pc.category_id, 
                                          pc.category_name, 
                                          pc.category_slug,
                                          COUNT(DISTINCT pcl.product_id) as product_count
                                      FROM product_categories pc
                                      LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
                                      LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
                                      LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.stock_status = 'In Stock'
                                      WHERE pc.parent_id = ? AND pc.is_active = 1
                                      GROUP BY pc.category_id
                                      ORDER BY pc.sort_order ASC, pc.category_name ASC";
                          $sub_stmt = $conn->prepare($sub_query);
                          $sub_stmt->bind_param("i", $category['category_id']);
                          $sub_stmt->execute();
                          $sub_result = $sub_stmt->get_result();
                  ?>
                  
                  <!-- Main Category -->
                  <div class="category-group">
                      <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                          <input type="checkbox" 
                                class="category-filter w-4 h-4 text-orange-600 rounded border border-gray-300 focus:ring-orange-500"
                                value="<?= $category['category_id'] ?>"
                                data-category-id="<?= $category['category_id'] ?>"
                                data-category-slug="<?= $category['category_slug'] ?>"
                                <?php echo in_array($category['category_id'], $selected_cats) ? 'checked' : ''; ?>
                                onchange="handleCategoryChange(this)">
                          <span class="text-sm text-gray-700 flex-1"><?= htmlspecialchars($category['category_name']) ?></span>
                          <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $category['product_count'] ?></span>
                      </label>
                      
                      <!-- Subcategories (indented) -->
                      <?php if ($sub_result && $sub_result->num_rows > 0): ?>
                          <div class="ml-6 mt-1 space-y-1">
                              <?php while ($subcat = $sub_result->fetch_assoc()): ?>
                              <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                                  <input type="checkbox" 
                                        class="category-filter w-4 h-4 text-orange-600 rounded border border-gray-300 focus:ring-orange-500"
                                        value="<?= $subcat['category_id'] ?>"
                                        data-category-id="<?= $subcat['category_id'] ?>"
                                        data-category-slug="<?= $subcat['category_slug'] ?>"
                                        data-parent-id="<?= $category['category_id'] ?>"
                                        <?php echo in_array($subcat['category_id'], $selected_cats) ? 'checked' : ''; ?>
                                        onchange="handleCategoryChange(this)">
                                  <span class="text-sm text-gray-600 flex-1"><?= htmlspecialchars($subcat['category_name']) ?></span>
                                  <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $subcat['product_count'] ?></span>
                              </label>
                              <?php endwhile; ?>
                          </div>
                      <?php endif; ?>
                      <?php $sub_stmt->close(); ?>
                  </div>
                  
                  <?php 
                      endwhile;
                  endif; 
                  ?>
              </div>
          </div>

          <!-- Price Range -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
              <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                  <svg class="w-5 h-5 me-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Price Range
              </h3>
              <?php
              // Get current price filter from URL
              $selected_price = isset($_GET['price']) ? $_GET['price'] : '';
              ?>
              <div class="space-y-2">
                  <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                      <input type="radio" name="price" value="under200" class="price-filter w-4 h-4 text-orange-600 border border-gray-300 focus:ring-orange-500"
                            <?php echo $selected_price == 'under200' ? 'checked' : ''; ?>>
                      <span class="text-sm text-gray-700">Under ₱200</span>
                  </label>
                  <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                      <input type="radio" name="price" value="200-400" class="price-filter w-4 h-4 text-orange-600 border border-gray-300 focus:ring-orange-500"
                            <?php echo $selected_price == '200-400' ? 'checked' : ''; ?>>
                      <span class="text-sm text-gray-700">₱200 - ₱400</span>
                  </label>
                  <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                      <input type="radio" name="price" value="400-600" class="price-filter w-4 h-4 text-orange-600 border border-gray-300 focus:ring-orange-500"
                            <?php echo $selected_price == '400-600' ? 'checked' : ''; ?>>
                      <span class="text-sm text-gray-700">₱400 - ₱600</span>
                  </label>
                  <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                      <input type="radio" name="price" value="over600" class="price-filter w-4 h-4 text-orange-600 border border-gray-300 focus:ring-orange-500"
                            <?php echo $selected_price == 'over600' ? 'checked' : ''; ?>>
                      <span class="text-sm text-gray-700">Over ₱600</span>
                  </label>
              </div>
          </div>

          <!-- Clear Filters Button -->
          <button type="button" onclick="clearAllFilters()" class="w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/>
                  <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
              Clear All Filters
          </button>
      </aside>
  </div>

  <!-- Products Grid - Right Column -->
  <div class="md:col-span-4 lg:col-span-3">
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
              if ($result->num_rows > 0) {
              $products = [];
              while ($row = $result->fetch_assoc()) {
              $product_id = $row['product_id'];
              if (!isset($products[$product_id])) {
                  $products[$product_id] = [
                  'product_name' => $row['product_name'],
                  'product_unit' => $row['product_unit'],
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
              $product_unit = $product['product_unit'];
              $image_url = $product['image_url'];
              $variants = $product['variants'];
              
              // Generate URLs
              $shareUrlOld = $baseUrl . "item.php?q=" . urlencode($product_name);
              $shareUrlNew = $baseUrl . "item/" . urlencode(strtolower(str_replace(' ', '-', $product_name)));
              $shareTitle = $product['product_name'];
              $shareText = "Check out this fresh seafood: " . $product['product_name'] . " from St. Joseph Fish Brokerage Inc.";

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
                  <?= htmlspecialchars($product_unit) ?>
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
                  <div class="flex gap-2">
                    <!-- Add to Cart Button -->
                    <button type="submit" name="add_to_cart" 
                            class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" title="Add to Cart" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                            Add to Cart
                    </button>
                    <!-- Facebook Share Button -->
                    <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                            class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-white font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#paint0_linear_87_7208)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="paint0_linear_87_7208" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
                    </button>
                    
                    <!-- General Share Button -->
                    <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                            class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share on other platforms">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-share-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>                      
                    </button>
                  </div>
              </div>
              
              <!-- Message to select a variant -->
              <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
              <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
              </form>
          </div>
          <?php
              }
            } else {
          ?>
            <div class="md:col-span-3">
              <div class="flex flex-col items-center justify-center py-20 text-center">
                <!-- Icon -->
                <div class="flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 mb-4">
                  <svg class="w-16 h-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fish"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.69 7.44a6.973 6.973 0 0 0 -1.69 4.56c0 1.747 .64 3.345 1.699 4.571" /><path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25 -7.761 -12.285 -6.142 -20 2.504" /><path d="M18 11v.01" /><path d="M11.5 10.5c-.667 1 -.667 2 0 3" /></svg>
                </div>

                <!-- Text -->
                <h3 class="text-lg font-semibold text-gray-800">
                  No products available
                </h3>
                <p class="mt-2 text-gray-500 max-w-sm">
                  We couldn’t find any products at the moment. Please check back later or try browsing other categories.
                </p>

              </div>
            </div>
          <?php
          }
          ?>
        </div>
      </div>
    </div>
    
  </div>
</div>


<script>
// Category filter handling
function handleCategoryChange(checkbox) {
    const url = new URL(window.location);
    let selectedCategories = [];
    
    // Get all checked category checkboxes
    document.querySelectorAll('.category-filter:checked').forEach(cb => {
        if (cb.value !== 'all') {
            selectedCategories.push(cb.value);
        }
    });
    
    // Handle "All Products" checkbox
    const allCheckbox = document.querySelector('.category-filter[value="all"]');
    if (checkbox.value === 'all') {
        if (checkbox.checked) {
            // Uncheck all other categories
            document.querySelectorAll('.category-filter:not([value="all"])').forEach(cb => {
                cb.checked = false;
            });
            selectedCategories = [];
        }
    } else {
        // If any category is checked, uncheck "All Products"
        if (allCheckbox) {
            allCheckbox.checked = false;
        }
    }
    
    // Update URL parameter
    if (selectedCategories.length > 0) {
        url.searchParams.set('category', selectedCategories.join(','));
    } else {
        url.searchParams.delete('category');
        // If no categories selected, check "All Products"
        if (allCheckbox) allCheckbox.checked = true;
    }
    
    // Preserve search query if exists
    const searchQuery = document.getElementById('searchInput')?.value;
    if (searchQuery) {
        url.searchParams.set('search', searchQuery);
    }
    
    // Reload page with new filters
    window.location.href = url.toString();
}

// Price filter handling
document.querySelectorAll('.price-filter').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            const url = new URL(window.location);
            url.searchParams.set('price', this.value);
            
            // Preserve other filters
            const searchQuery = document.getElementById('searchInput')?.value;
            if (searchQuery) url.searchParams.set('search', searchQuery);
            
            window.location.href = url.toString();
        }
    });
});

// Origin filter handling
function handleOriginChange(checkbox) {
    const url = new URL(window.location);
    let selectedOrigins = [];
    
    document.querySelectorAll('.origin-filter:checked').forEach(cb => {
        selectedOrigins.push(cb.value);
    });
    
    if (selectedOrigins.length > 0) {
        url.searchParams.set('origin', selectedOrigins.join(','));
    } else {
        url.searchParams.delete('origin');
    }
    
    // Preserve other filters
    const searchQuery = document.getElementById('searchInput')?.value;
    if (searchQuery) url.searchParams.set('search', searchQuery);
    
    window.location.href = url.toString();
}

// Clear all filters
function clearAllFilters() {
    const url = new URL(window.location);
    
    // Remove all filter parameters
    url.searchParams.delete('category');
    url.searchParams.delete('price');
    url.searchParams.delete('origin');
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
}

// Initialize filter states on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check if any categories are selected
    const selectedCats = urlParams.get('category');
    if (!selectedCats) {
        const allCheckbox = document.querySelector('.category-filter[value="all"]');
        if (allCheckbox) allCheckbox.checked = true;
    }
});
</script>

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

    // Display autocomplete results with nickname tags
    function displayAutocompleteResults(results) {
        if (results.length === 0) {
            autocompleteResults.innerHTML = '<div class="p-6 text-center text-gray-500">No products found. Try a different search term.</div>';
            autocompleteResults.classList.remove('hidden');
            return;
        }

        autocompleteResults.innerHTML = '';
        
        // Add result count header
        const header = document.createElement('div');
        header.className = 'px-4 py-2 bg-gray-50 border-b border-gray-200 flex justify-between items-center';
        header.innerHTML = `<p class="text-sm font-semibold text-gray-600">${results.length} results found</p>
                            <span class="text-xs text-gray-400">Press Enter to search all</span>`;
        autocompleteResults.appendChild(header);
        
        results.forEach(product => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item px-4 py-3 hover:bg-gray-50 transition cursor-pointer focus:bg-gray-50 outline-none border-b border-gray-100 last:border-b-0';
            item.tabIndex = 0;

            // Highlight matching text in product name
            const highlightedName = highlightText(product.name, currentSearchTerm);
            
            // Build variant/unit display
            let variantDisplay = '';
            if (product.variant) {
                variantDisplay = `<span class="font-medium text-gray-700">${highlightText(product.variant, currentSearchTerm)}</span>`;
            }
            
            // Build description display
            let descriptionDisplay = '';
            if (product.description) {
                descriptionDisplay = `<span class="text-gray-500">${product.description}</span>`;
            }
            
            // Combine variant and description
            let metaDisplay = '';
            if (variantDisplay && descriptionDisplay) {
                metaDisplay = `${variantDisplay} | ${descriptionDisplay}`;
            } else if (variantDisplay) {
                metaDisplay = variantDisplay;
            } else if (descriptionDisplay) {
                metaDisplay = descriptionDisplay;
            }
            
            // Build tags display
            let tagsDisplay = '';
            if (product.tags && product.tags.length > 0) {
                const tagItems = product.tags.map(tag => 
                    `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-50 text-orange-700 mr-1">
                        ${highlightText(tag, currentSearchTerm)}
                    </span>`
                ).join('');
                tagsDisplay = `<div class="flex flex-wrap items-center gap-1 mt-1.5">
                                <span class="text-xs text-gray-400 mr-1">Tags:</span>
                                ${tagItems}
                              </div>`;
            }
            
            // Match type badge
            let matchBadge = '';
            if (product.match_type) {
                matchBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${product.match_class} ml-2">
                                ${product.match_type}
                            </span>`;
            }
            
            item.innerHTML = `
                <div class="flex items-start gap-3">
                    <!-- Search Icon -->
                    <div class="flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <!-- Product Name with Match Badge -->
                        <div class="flex items-center flex-wrap gap-1">
                            <h4 class="text-sm font-semibold text-gray-900">
                                ${highlightedName}
                            </h4>
                            ${matchBadge}
                        </div>
                        
                        <!-- Meta Info (Variant | Unit) -->
                        ${metaDisplay ? `<p class="text-xs text-gray-600 mt-0.5">${metaDisplay}</p>` : ''}
                        
                        <!-- Tags -->
                        ${tagsDisplay}
                    </div>

                    <!-- Category Badge (Right side) -->
                    <div class="flex-shrink-0 self-start">
                        <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 whitespace-nowrap">
                            ${product.category || 'General'}
                        </span>
                    </div>
                </div>
            `;
            
            item.addEventListener('click', (e) => {
                e.preventDefault();
                searchInput.value = product.name;
                performSearch(product.name);
                autocompleteResults.classList.add('hidden');
            });
            
            // Keyboard navigation
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchInput.value = product.name;
                    performSearch(product.name);
                    autocompleteResults.classList.add('hidden');
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = item.nextElementSibling;
                    if (next && next.classList.contains('autocomplete-item')) {
                        next.focus();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = item.previousElementSibling;
                    if (prev && prev.classList.contains('autocomplete-item')) {
                        prev.focus();
                    } else {
                        searchInput.focus();
                    }
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

  // Function to share product (general share API)
  function shareProduct(shareTitle, shareText, shareUrl) {
    const shareData = {
      title: shareTitle,
      text: shareText,
      url: shareUrl
    };
    
    if (navigator.share) {
      navigator.share(shareData)
        .then(() => console.log('Shared successfully'))
        .catch(err => {
          console.log('Share cancelled', err);
          copyShareLink(shareUrl);
        });
    } else {
      copyShareLink(shareUrl);
    }
  }
  
  // Function to share to Facebook
  function shareToFacebook(url) {
    const facebookShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
    window.open(facebookShareUrl, '_blank', 'width=600,height=400');
  }
  
  // Fallback copy function
  function copyShareLink(url) {
    navigator.clipboard.writeText(url)
      .then(() => {
        // Show a toast notification instead of alert
        showToast('Link copied to clipboard!', 'success');
      })
      .catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy link', 'error');
      });
  }
  
  // Toast notification function
  function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;
    
    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-lg shadow-lg text-white ${
      type === 'success' ? 'bg-green-500' : 
      type === 'error' ? 'bg-red-500' : 
      'bg-blue-500'
    }`;
    toast.textContent = message;
    
    toastContainer.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
      toast.remove();
    }, 3000);
  }
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