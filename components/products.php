<!-- components/products.php -->
<!-- JS handled by: cart_core.js (cart ops) + products_patch.js (variants, search, add-to-cart, share) -->
<div class="px-4 sm:px-6 lg:px-8 mx-auto">

  <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div class="flex items-center justify-between my-5 gap-2">
      <div class="flex-grow"></div>
      
      <div class="relative w-full">
        <?php 
          $preservedParams = ['page'];
          foreach ($preservedParams as $param) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
              echo '<input type="hidden" name="' . $param . '" value="' . htmlspecialchars($_GET[$param]) . '">';
            }
          }
        ?>
        <div class="relative">
          <input type="text" name="search" id="searchInput"
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
            class="py-3 pl-10 pr-12 px-4 block w-full rounded-full text-sm border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition"
            placeholder="What would you like?" autocomplete="off"/>
          <button type="button" id="clearSearch"
            class="absolute inset-y-0 right-0 flex items-center pr-3 hover:text-gray-700 transition-colors <?php echo !isset($_GET['search']) || empty($_GET['search']) ? 'hidden' : ''; ?>">
            <span class="text-lg font-semibold me-3 text-gray-400">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
            </span>
          </button>
        </div>
        <button type="submit" class="hidden"></button>
        <div id="autocompleteResults" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-lg border border-gray-100 hidden"></div>
      </div>
      <button type="submit" class="cursor-pointer p-3 rounded-3xl justify-center items-center inline-flex bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 focus:outline-none ml-2 flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" />
        </svg>
      </button>
    </div>
  </form>

</div>

<!-- Main Content Grid: Sidebar + Products -->
<div class="grid md:grid-cols-4 gap-8">
  <!-- Sidebar Filters -->
  <div class="md:col-span-1 relative">
    <aside class="space-y-6">
      <!-- Categories -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 me-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          Categories
        </h3>
        
        <?php
        $cat_query = "SELECT 
                        pc.category_id, 
                        pc.category_name, 
                        pc.category_slug,
                        COUNT(DISTINCT pcl.product_id) as product_count
                      FROM product_categories pc
                      LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
                      LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
                      LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.stock_status = 'In Stock'
                      WHERE pc.parent_id IS NULL AND pc.is_active = 1
                      GROUP BY pc.category_id
                      ORDER BY pc.sort_order ASC, pc.category_name ASC";
        $cat_result = $conn->query($cat_query);
        $selected_cats = isset($_GET['category']) ? explode(',', $_GET['category']) : [];
        ?>
        
        <div class="space-y-2">
          <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
            <input type="checkbox" class="category-filter w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-orange-500"
                   value="all" data-category-id="all"
                   <?php echo empty($selected_cats) || in_array('all', $selected_cats) ? 'checked' : ''; ?>
                   onchange="handleCategoryChange(this)">
            <span class="text-sm text-gray-700 flex-1">All Products</span>
            <?php
            $total_query = "SELECT COUNT(DISTINCT p.product_id) as total FROM products p 
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
              $sub_query = "SELECT pc.category_id, pc.category_name, pc.category_slug,
                                    COUNT(DISTINCT pcl.product_id) as product_count
                            FROM product_categories pc
                            LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
                            LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
                            LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.stock_status = 'In Stock'
                            WHERE pc.parent_id = ? AND pc.is_active = 1
                            GROUP BY pc.category_id ORDER BY pc.sort_order ASC, pc.category_name ASC";
              $sub_stmt = $conn->prepare($sub_query);
              $sub_stmt->bind_param("i", $category['category_id']);
              $sub_stmt->execute();
              $sub_result = $sub_stmt->get_result();
          ?>
          <div class="category-group">
            <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
              <input type="checkbox" class="category-filter w-4 h-4 text-orange-600 rounded border border-gray-300 focus:ring-orange-500"
                     value="<?= $category['category_id'] ?>"
                     data-category-id="<?= $category['category_id'] ?>"
                     data-category-slug="<?= $category['category_slug'] ?>"
                     <?php echo in_array($category['category_id'], $selected_cats) ? 'checked' : ''; ?>
                     onchange="handleCategoryChange(this)">
              <span class="text-sm text-gray-700 flex-1"><?= htmlspecialchars($category['category_name']) ?></span>
              <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $category['product_count'] ?></span>
            </label>
            <?php if ($sub_result && $sub_result->num_rows > 0): ?>
            <div class="ml-6 mt-1 space-y-1">
              <?php while ($subcat = $sub_result->fetch_assoc()): ?>
              <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                <input type="checkbox" class="category-filter w-4 h-4 text-orange-600 rounded border border-gray-300 focus:ring-orange-500"
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
            <path stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Price Range
        </h3>
        <?php $selected_price = isset($_GET['price']) ? $_GET['price'] : ''; ?>
        <div class="space-y-2">
          <?php 
          $priceOptions = [
            'under200' => 'Under ₱200',
            '200-400'  => '₱200 - ₱400',
            '400-600'  => '₱400 - ₱600',
            'over600'  => 'Over ₱600',
          ];
          foreach ($priceOptions as $val => $label): ?>
          <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
            <input type="radio" name="price" value="<?= $val ?>"
                   class="price-filter w-4 h-4 text-orange-600 border border-gray-300 focus:ring-orange-500"
                   <?php echo $selected_price == $val ? 'checked' : ''; ?>>
            <span class="text-sm text-gray-700"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Clear Filters -->
      <button type="button" onclick="clearAllFilters()"
              class="w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
        Clear All Filters
      </button>
    </aside>
  </div>

  <!-- Products Grid -->
  <div class="md:col-span-3">
    <div id="productsContainer" class="relative min-h-[500px]">
      <div id="productsLoading" class="hidden">
        <div class="flex flex-col items-center justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mb-4"></div>
          <p class="text-gray-600">Loading products...</p>
        </div>
      </div>
      
      <div id="productsContent">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <?php
          if ($productsResult && $productsResult->num_rows > 0) {
            $products = [];
            while ($row = $productsResult->fetch_assoc()) {
              $pid = $row['product_id'];
              if (!isset($products[$pid])) {
                $products[$pid] = [
                  'product_name'     => $row['product_name'],
                  'product_unit'     => $row['product_unit'],
                  'product_nickname' => $row['product_nickname'],
                  'image_url'        => !empty($row['image_path'])
                    ? 'http://localhost/sjfbi-js/uploads/products/' . $row['image_path']
                    : 'http://localhost/sjfbi-js/uploads/products/default.png',
                  'category_name'    => $row['category_name'],
                  'variants'         => [],
                  'has_stock'        => false,
                  'total_stock'      => 0,
                ];
              }
              if (!empty($row['variant_name'])) {
                $stockQty  = intval($row['stock_quantity'] ?? 0);
                $hasStk    = $stockQty > 0;
                $products[$pid]['variants'][] = [
                  'variant_id'      => $row['variant_id'],
                  'variant_name'    => $row['variant_name'],
                  'variant_price'   => $row['variant_price'],
                  'discount_price'  => $row['discount_price'],
                  'unit_type'       => $row['unit_type'] ?? 'piece',
                  'minimum_order'   => $row['minimum_order'] ?? 1,
                  'order_increment' => $row['order_increment'] ?? 1,
                  'stock_quantity'  => $stockQty,
                  'has_stock'       => $hasStk,
                ];
                if ($hasStk) $products[$pid]['has_stock'] = true;
                $products[$pid]['total_stock'] += $stockQty;
              }
            }

            foreach ($products as $product_id => $product):
              $product_name     = $product['product_name'];
              $product_unit     = $product['product_unit'];
              $product_nickname = $product['product_nickname'];
              $image_url        = $product['image_url'];
              $variants         = $product['variants'];
              $category_name    = $product['category_name'];
              $hasStock         = $product['has_stock'];

              $nicknames = [];
              if (!empty($product_nickname)) {
                $nd = json_decode($product_nickname, true);
                if (is_array($nd)) $nicknames = array_slice($nd, 0, 3);
              }

              $shareUrlNew = $baseUrl . 'item/' . urlencode(strtolower(str_replace(' ', '-', $product_name)));
              $shareTitle  = $product_name;
              $shareText   = 'Check out this fresh seafood: ' . $product_name . ' from St. Joseph Fish Brokerage Inc.';
          ?>

          <div class="flex flex-col h-full bg-white shadow-lg rounded-lg p-5 relative group">
            <div class="relative">
              <a href="item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>" class="block">
                <img src="<?= htmlspecialchars($image_url) ?>" 
                     alt="<?= htmlspecialchars($product_name) ?>"
                     class="w-full h-48 object-cover rounded-md mb-4 shadow-sm <?= !$hasStock ? 'opacity-60' : '' ?>">
                <?php if (!$hasStock): ?>
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-md h-48">
                  <span class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg transform -rotate-12 shadow-lg">OUT OF STOCK</span>
                </div>
                <?php endif; ?>
              </a>
            </div>

            <h3 class="text-xl font-semibold text-gray-800 mb-1"><?= htmlspecialchars($product_name) ?></h3>
            <p class="text-md text-gray-500 mb-4"><?= htmlspecialchars($product_unit) ?></p>

            <?php if (!empty($nicknames)): ?>
            <div class="flex flex-wrap gap-1 mb-3">
              <?php foreach ($nicknames as $nickname): ?>
              <span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs rounded-full border border-orange-200">
                #<?= htmlspecialchars($nickname) ?>
              </span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($category_name)): ?>
            <div class="mb-3">
              <span class="text-xs text-gray-500">Categories: </span>
              <span class="text-xs font-medium text-gray-700"><?= htmlspecialchars($category_name) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($hasStock): ?>
            <form class="add-to-cart-form flex flex-col flex-grow" method="POST" action="javascript:void(0)" data-product-id="<?= $product_id ?>">
              <input type="hidden" name="add_to_cart"      value="1">
              <input type="hidden" name="product_id"       value="<?= $product_id ?>">
              <input type="hidden" name="variant_id"       value="">
              <input type="hidden" name="product_name"     value="<?= htmlspecialchars($product_name) ?>">
              <input type="hidden" name="variant_name"     value="">
              <input type="hidden" name="price"            value="">
              <input type="hidden" name="image_url"        value="<?= htmlspecialchars($image_url) ?>">
              <input type="hidden" name="quantity"         value="">
              <input type="hidden" name="unit_type"        value="">
              <input type="hidden" name="minimum_order"    value="">
              <input type="hidden" name="order_increment"  value="">

              <!-- Variant Buttons -->
              <div class="min-h-[72px]">
                <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                <div class="flex flex-wrap gap-2">
                  <?php 
                  $firstInStock = null;
                  foreach ($variants as $v) { if ($v['has_stock']) { $firstInStock = $v; break; } }
                  foreach ($variants as $v):
                    $vHasStock    = $v['has_stock'];
                    $isSelected   = ($firstInStock && $v === $firstInStock);
                    $disabledAttr = $vHasStock ? '' : 'disabled';
                    $disabledCls  = $vHasStock ? '' : 'opacity-50 cursor-not-allowed';
                  ?>
                  <button type="button" class="variant-button px-3 py-2 border rounded-lg text-sm font-medium hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200
                    <?= $isSelected ? 'selected-variant border-gray-400 bg-gray-100' : 'border-gray-300' ?> <?= $disabledCls ?>"
                    data-product-id="<?= $product_id ?>"
                    data-variant-id="<?= $v['variant_id'] ?>"
                    data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                    data-variant-price="<?= $v['variant_price'] ?>"
                    data-discount-price="<?= $v['discount_price'] ?>"
                    data-unit-type="<?= $v['unit_type'] ?>"
                    data-minimum-order="<?= $v['minimum_order'] ?>"
                    data-order-increment="<?= $v['order_increment'] ?>"
                    data-stock-quantity="<?= $v['stock_quantity'] ?>"
                    data-has-stock="<?= $vHasStock ? 'true' : 'false' ?>"
                    <?= $disabledAttr ?>>
                    <?= htmlspecialchars($v['variant_name']) ?>
                    <?php if (!$vHasStock): ?><span class="ml-1 text-red-500">(No Stock)</span><?php endif; ?>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Qty Selector -->
              <div class="mt-3">
                <div class="flex items-center">
                  <div class="flex items-center border border-gray-300 rounded">
                    <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                    <input type="number" class="quantity w-14 px-1 py-0.5 text-center text-sm border-0 focus:outline-none"
                           value="" min="" step="">
                    <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                  </div>
                  &nbsp;
                  <span class="text-sm font-medium text-gray-600 unit-display"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
              </div>

              <div class="price-display mt-3"></div>
              <div class="flex-grow"></div>

              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex gap-2">
                  <button type="submit" name="add_to_cart"
                          class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                          title="Add to Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                    Add to Cart
                  </button>
                  <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                          class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-white font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#fbg_<?= $product_id ?>)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="fbg_<?= $product_id ?>" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
                  </button>
                  <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                          class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>
                  </button>
                </div>
              </div>

              <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
              <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
              <p class="text-red-500 text-sm mt-2 stock-error-message hidden"></p>
            </form>
            <?php else: ?>
            <div class="flex-grow"></div>
            <div class="mt-4 pt-4 border-t border-gray-200">
              <div class="flex gap-2">
                <a href="item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>"
                   class="block w-full py-2 rounded-lg bg-gray-400 hover:bg-gray-500 text-white font-medium transition-all duration-300 text-center">
                  View Details
                </a>
                <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-white font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="14" fill="url(#fbg2_<?= $product_id ?>)"/><path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/><defs><linearGradient id="fbg2_<?= $product_id ?>" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs></svg>
                </button>
                <button type="button" onclick="shareProduct('<?= $shareTitle ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" /><path d="M12 14v-11" /><path d="M9 6l3 -3l3 3" /></svg>
                </button>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <?php
            endforeach;
          } else {
          ?>
          <div class="col-span-3">
            <div class="flex flex-col items-center justify-center py-20 text-center">
              <div class="flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 mb-4">
                <svg class="w-16 h-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.69 7.44a6.973 6.973 0 0 0 -1.69 4.56c0 1.747 .64 3.345 1.699 4.571" /><path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25 -7.761 -12.285 -6.142 -20 2.504" /><path d="M18 11v.01" /><path d="M11.5 10.5c-.667 1 -.667 2 0 3" /></svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">No products available</h3>
              <p class="mt-2 text-gray-500 max-w-sm">We couldn't find any products at the moment. Please check back later or try browsing other categories.</p>
            </div>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.variant-button { background-color: white; border: 1px solid #d1d5db; color: #374151; transition: all 0.2s ease; }
.variant-button.selected-variant { background-color: #f59e0b; border-color: #f59e0b; color: #fff; }
button[name="add_to_cart"]:disabled { opacity: 0.5; cursor: not-allowed; }
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.add-to-cart-form input.quantity:focus { outline: 2px solid #f97316; outline-offset: 1px; border-radius: 2px; }
#autocompleteResults { max-height: 400px; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
.autocomplete-item { transition: all .15s ease; }
.autocomplete-item:hover { background-color: #f3f5f6; transform: translateX(2px); }
#productsContent { transition: opacity 0.3s ease; }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {

      var itemForm = document.querySelector('.add-to-cart-form');

      // ── Variant selection — MUST be defined before auto-select click below ─────
      document.querySelectorAll('.variant-button').forEach(function (button) {
          button.addEventListener('click', function () {
              var form          = document.querySelector('.add-to-cart-form');
              if (!form) return;
              var variantPrice  = parseFloat(button.dataset.variantPrice);
              var discountPrice = parseFloat(button.dataset.discountPrice);
              var unitType      = button.dataset.unitType;
              var minimumOrder  = parseFloat(button.dataset.minimumOrder);
              var orderIncr     = parseFloat(button.dataset.orderIncrement);
              var vHasStock     = button.dataset.hasStock === 'true';

              form.querySelectorAll('.variant-button').forEach(function (b) { b.classList.remove('selected-variant'); });
              button.classList.add('selected-variant');

              form.querySelector('[name="variant_id"]').value      = button.dataset.variantId;
              form.querySelector('[name="variant_name"]').value    = button.dataset.variantName;
              form.querySelector('[name="price"]').value           = discountPrice > 0 ? discountPrice : variantPrice;
              form.querySelector('[name="unit_type"]').value       = unitType;
              form.querySelector('[name="minimum_order"]').value   = minimumOrder;
              form.querySelector('[name="order_increment"]').value = orderIncr;

              var qtyInput = form.querySelector('.quantity');
              var dispQty  = unitType === 'piece' ? Math.round(minimumOrder) : minimumOrder;
              qtyInput.value = dispQty;
              qtyInput.min   = minimumOrder;
              qtyInput.step  = orderIncr;
              form.querySelector('[name="quantity"]').value = minimumOrder;

              form.querySelector('.unit-display').textContent       = unitType === 'piece' ? 'pcs' : unitType;
              form.querySelector('.minimum-order-text').textContent = 'Minimum: ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);

              _updatePriceDisplay(form, variantPrice, discountPrice, minimumOrder);

              // Enable/disable submit based on stock
              var submitBtn = form.querySelector('[name="add_to_cart"]');
              if (submitBtn) submitBtn.disabled = !vHasStock;

              form.querySelector('.variant-message')?.classList.add('hidden');
              form.querySelector('.minimum-error-message')?.classList.add('hidden');
              form.querySelector('.stock-error-message')?.classList.add('hidden');
          });
      });

      if (itemForm) {
          // ── Qty − button ──────────────────────────────────────────────────────
          itemForm.querySelector('.decrease-quantity')?.addEventListener('click', function () {
              var qtyInput  = itemForm.querySelector('.quantity');
              var minOrder  = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var newQty    = Math.max(minOrder, parseFloat(qtyInput.value) - orderIncr);
              qtyInput.value = newQty;
              itemForm.querySelector('[name="quantity"]').value = newQty;
              _updateTotalPrice(itemForm);
          });

          // ── Qty + button ──────────────────────────────────────────────────────
          itemForm.querySelector('.increase-quantity')?.addEventListener('click', function () {
              var qtyInput  = itemForm.querySelector('.quantity');
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var newQty    = parseFloat(qtyInput.value) + orderIncr;
              qtyInput.value = newQty;
              itemForm.querySelector('[name="quantity"]').value = newQty;
              _updateTotalPrice(itemForm);
          });

          // ── Live price preview while typing ───────────────────────────────────
          itemForm.querySelector('.quantity')?.addEventListener('input', function () {
              itemForm.querySelector('[name="quantity"]').value = parseFloat(this.value) || 0;
              _updateTotalPrice(itemForm);
          });

          // ── Snap to increment on blur ─────────────────────────────────────────
          itemForm.querySelector('.quantity')?.addEventListener('change', function () {
              var minOrder  = parseFloat(itemForm.querySelector('[name="minimum_order"]').value) || 1;
              var orderIncr = parseFloat(itemForm.querySelector('[name="order_increment"]').value) || 1;
              var val       = parseFloat(this.value);
              if (isNaN(val) || val < minOrder) val = minOrder;
              val = minOrder + Math.round((val - minOrder) / orderIncr) * orderIncr;
              this.value = val;
              itemForm.querySelector('[name="quantity"]').value = val;
              _updateTotalPrice(itemForm);
          });

          itemForm.querySelector('.quantity')?.addEventListener('keydown', function (e) {
              if (e.key === 'Enter') { e.preventDefault(); this.blur(); }
          });

          // ── Add-to-cart submit ─────────────────────────────────────────────────
          itemForm.addEventListener('submit', async function (e) {
              e.preventDefault();
              var variantId       = itemForm.querySelector('[name="variant_id"]').value;
              var quantity        = parseFloat(itemForm.querySelector('[name="quantity"]').value);
              var minimumOrder    = parseFloat(itemForm.querySelector('[name="minimum_order"]').value);
              var unitType        = itemForm.querySelector('[name="unit_type"]').value;
              var selectedVariant = itemForm.querySelector('.variant-button.selected-variant');
              var stockQty        = selectedVariant ? parseInt(selectedVariant.dataset.stockQuantity) : 0;
              var errMsg          = itemForm.querySelector('.minimum-error-message');
              var stockErrMsg     = itemForm.querySelector('.stock-error-message');

              if (!variantId) { itemForm.querySelector('.variant-message')?.classList.remove('hidden'); return; }
              if (quantity < minimumOrder) {
                  errMsg.textContent = 'Minimum order is ' + minimumOrder + ' ' + (unitType === 'piece' ? 'pcs' : unitType);
                  errMsg.classList.remove('hidden');
                  return;
              }
              if (quantity > stockQty) {
                  stockErrMsg.textContent = 'Only ' + stockQty + ' ' + (unitType === 'piece' ? 'pcs' : unitType) + ' available';
                  stockErrMsg.classList.remove('hidden');
                  return;
              }

              try {
                  var res  = await fetch((window.CART_BASE || '/sjfbi-js') + '/functions/add_to_cart.php', {
                      method: 'POST',
                      body:   new FormData(itemForm)
                  });
                  var data = await res.json();
                  if (data.status === 'success') {
                      showToast('Product added to cart!', 'success');
                      await refreshCartFromServer();
                      var first = document.querySelector('.variant-button[data-has-stock="true"]');
                      if (first) first.click();
                  } else {
                      showToast(data.message || 'Failed to add product', 'error');
                  }
              } catch (err) {
                  showToast('An error occurred. Please try again.', 'error');
              }
          });
      }

      // ── Auto-select first in-stock variant — LAST so all listeners are ready ──
      var firstInStock = document.querySelector('.variant-button[data-has-stock="true"]');
      if (firstInStock) firstInStock.click();

      // ── Price helpers ─────────────────────────────────────────────────────────
      function _updatePriceDisplay(form, variantPrice, discountPrice, quantity) {
          var el = form.querySelector('.price-display');
          if (!el) return;
          var price = discountPrice > 0 ? discountPrice : variantPrice;
          var total = price * quantity;
          if (discountPrice > 0) {
              el.innerHTML = '<span class="line-through text-gray-500 text-lg">₱' + (variantPrice * quantity).toFixed(2) + '</span>' +
                            '<span class="text-red-600 font-bold text-2xl ml-2">₱' + total.toFixed(2) + '</span>';
          } else {
              el.innerHTML = '<span class="text-gray-800 font-bold text-2xl">₱' + total.toFixed(2) + '</span>';
          }
      }

      function _updateTotalPrice(form) {
          var selected = form.querySelector('.variant-button.selected-variant');
          if (!selected) return;
          var qty = parseFloat(form.querySelector('.quantity').value) || 0;
          _updatePriceDisplay(form, parseFloat(selected.dataset.variantPrice), parseFloat(selected.dataset.discountPrice), qty);
      }
  });

  // ── Image switcher ────────────────────────────────────────────────────────────
  function changeImage(imageName) {
      document.getElementById('mainImage').src = '<?= $baseUrl ?>uploads/products/' + imageName;
  }

  // ── Share helpers ─────────────────────────────────────────────────────────────
  function shareToFacebook(url) {
      window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400,noopener,noreferrer');
  }

  function shareProduct(title, text, url) {
      if (navigator.share) {
          navigator.share({ title: title, text: text, url: url })
              .catch(function (err) { if (err.name !== 'AbortError') _copyShareLink(url); });
      } else {
          _copyShareLink(url);
      }
  }

  function _copyShareLink(url) {
      if (navigator.clipboard) {
          navigator.clipboard.writeText(url)
              .then(function () { showToast('Link copied to clipboard!', 'success'); })
              .catch(function () { showToast('Failed to copy link', 'error'); });
      } else {
          var el = document.createElement('textarea');
          el.value = url;
          document.body.appendChild(el);
          el.select();
          document.execCommand('copy');
          document.body.removeChild(el);
          showToast('Link copied to clipboard!', 'success');
      }
  }
</script>