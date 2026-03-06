<!-- fetch_products.php -->
<?php
session_start();
include '../conn.php';

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with search and filter parameters
$query = "SELECT 
            p.*, 
            pi.image_path, 
            v.variant_id, 
            v.variant_name, 
            v.variant_price, 
            v.discount_price,
            v.unit_type, 
            v.minimum_order, 
            v.order_increment, 
            v.stock_quantity,
            v.stock_status,
            GROUP_CONCAT(DISTINCT c.category_name SEPARATOR ', ') as category_names
          FROM products p
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN product_variants v ON p.product_id = v.product_id
          LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
          LEFT JOIN product_categories c ON pcl.category_id = c.category_id
          WHERE p.is_deleted = 0";

$params = [];
$paramTypes = "";

// Category filter
if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'all') {
    $categories = explode(',', $_GET['category']);
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $query .= " AND pcl.category_id IN ($placeholders)";
    foreach ($categories as $cat) {
        $paramTypes .= "i";
        $params[] = intval($cat);
    }
}

// Price range filter
if (isset($_GET['price']) && !empty($_GET['price'])) {
    switch($_GET['price']) {
        case 'under200':
            $query .= " AND v.variant_price < 200";
            break;
        case '200-400':
            $query .= " AND v.variant_price BETWEEN 200 AND 400";
            break;
        case '400-600':
            $query .= " AND v.variant_price BETWEEN 400 AND 600";
            break;
        case 'over600':
            $query .= " AND v.variant_price > 600";
            break;
    }
}

// Origin filter (based on product name or nickname)
if (isset($_GET['origin']) && !empty($_GET['origin'])) {
    $origins = explode(',', $_GET['origin']);
    $originConditions = [];
    foreach ($origins as $index => $origin) {
        $originConditions[] = "(p.product_name LIKE ? OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)";
        $paramTypes .= "ss";
        $searchTerm = "%" . $origin . "%";
        $params[] = $searchTerm;
        $params[] = $origin; // Exact term for JSON search
    }
    $query .= " AND (" . implode(' OR ', $originConditions) . ")";
}

// SEARCH FILTER - UPDATED WITH NICKNAME JSON SEARCH
if (!empty($search)) {
    $query .= " AND (p.product_name LIKE ? 
                OR p.product_unit LIKE ? 
                OR c.category_name LIKE ? 
                OR v.variant_name LIKE ?
                OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)";
    
    $searchTerm = "%" . $search . "%";
    // Add 4 parameters for LIKE searches
    for ($i = 0; $i < 4; $i++) {
        $paramTypes .= "s";
        $params[] = $searchTerm;
    }
    // Add 1 parameter for JSON search (exact term)
    $paramTypes .= "s";
    $params[] = $search;
}

$query .= " GROUP BY p.product_id, v.variant_id ORDER BY p.created_at DESC";

// Prepare and execute statement
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

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
                'product_nickname' => $row['product_nickname'],
                'image_url' => !empty($row['image_path']) ? $baseUrl . "uploads/products/" . $row['image_path'] : $baseUrl . "uploads/products/default.png",
                'category_names' => $row['category_names'],
                'variants' => [],
                'has_stock' => false, // Track if any variant has stock quantity > 0
                'total_stock' => 0     // Total available stock across all variants
            ];
        }
        if (!empty($row['variant_id'])) {
            // Check actual stock quantity
            $stockQuantity = intval($row['stock_quantity'] ?? 0);
            $hasStock = $stockQuantity > 0;
            
            $products[$product_id]['variants'][] = [
                'variant_id' => $row['variant_id'],
                'variant_name' => $row['variant_name'],
                'variant_price' => $row['variant_price'],
                'discount_price' => $row['discount_price'],
                'unit_type' => $row['unit_type'] ?? 'piece',
                'minimum_order' => $row['minimum_order'] ?? 1,
                'order_increment' => $row['order_increment'] ?? 1,
                'stock_quantity' => $stockQuantity,
                'has_stock' => $hasStock
            ];
            
            // Update product stock status
            if ($hasStock) {
                $products[$product_id]['has_stock'] = true;
            }
            $products[$product_id]['total_stock'] += $stockQuantity;
        }
    }

    foreach ($products as $product_id => $product):
        $product_name = $product['product_name'];
        $product_unit = $product['product_unit'];
        $product_nickname = $product['product_nickname'];
        $image_url = $product['image_url'];
        $category_names = $product['category_names'];
        $variants = $product['variants'];
        $hasStock = $product['has_stock'];
        $totalStock = $product['total_stock'];
        
        // Decode nickname JSON for display
        $nicknames = [];
        if (!empty($product_nickname)) {
            $nicknameData = json_decode($product_nickname, true);
            if (is_array($nicknameData)) {
                $nicknames = array_slice($nicknameData, 0, 3); // Show first 3 tags
            }
        }
        
        // Determine stock status class
        $stockClass = $hasStock ? '' : 'out-of-stock';
        
        // Generate share URLs
        $canonicalUrl = $baseUrl . "item/" . urlencode(strtolower(str_replace(' ', '-', $product_name)));
        $shareUrlNew = $canonicalUrl;
        $shareTitle = $product_name;
        $shareText = "Check out this fresh seafood: " . $product_name . " from St. Joseph Fish Brokerage Inc.";
?>
    <div class="flex flex-col h-full bg-white shadow-lg rounded-lg p-5 relative group <?= $stockClass ?>">
        <div class="relative">
            <a href="<?= $baseUrl ?>item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>" class="block">
                <img src="<?= htmlspecialchars($image_url) ?>" 
                     alt="<?= htmlspecialchars($product_name) ?>" 
                     class="w-full h-48 object-cover rounded-md mb-4 shadow-sm <?= !$hasStock ? 'opacity-60' : '' ?>">
                
                <!-- Out of Stock Overlay - Only shows if no stock -->
                <?php if (!$hasStock): ?>
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-md h-48">
                    <span class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg transform -rotate-12 shadow-lg">
                        NO STOCK
                    </span>
                </div>
                <?php endif; ?>
            </a>
            
            <!-- Product Name and Category -->
            <h3 class="text-xl font-semibold text-gray-800 mb-1">
                <?= htmlspecialchars($product_name) ?>
            </h3>
            <p class="text-md text-gray-500 mb-2">
                <?= htmlspecialchars($product_unit) ?>
            </p>
            
            <!-- Display Nickname Tags -->
            <?php if (!empty($nicknames)): ?>
            <div class="flex flex-wrap gap-1 mb-3">
                <?php foreach ($nicknames as $nickname): ?>
                    <span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs rounded-full border border-orange-200">
                        #<?= htmlspecialchars($nickname) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Category Badge -->
            <?php if (!empty($category_names)): ?>
            <div class="mb-3">
                <span class="text-xs text-gray-500">Categories: </span>
                <span class="text-xs font-medium text-gray-700"><?= htmlspecialchars($category_names) ?></span>
            </div>
            <?php endif; ?>
        </div>
    
        <?php if ($hasStock): ?>
        <!-- Add to Cart Form (only for products with stock) -->
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

            <!-- Variant Buttons - Show all variants but disable out of stock ones -->
            <?php if (!empty($variants)): ?>
            <div class="min-h-[72px]">
                <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $first = true;
                    $firstInStock = null;
                    
                    // Find first in-stock variant to auto-select
                    foreach ($variants as $variant) { 
                        if ($variant['has_stock']) {
                            $firstInStock = $variant;
                            break;
                        }
                    }
                    
                    foreach ($variants as $variant): 
                        $variantHasStock = $variant['has_stock'];
                        $stockQuantity = $variant['stock_quantity'];
                        
                        // Determine button state
                        $isSelected = ($firstInStock && $variant === $firstInStock) || (!$firstInStock && $first && $variantHasStock);
                        $disabled = !$variantHasStock ? 'disabled' : '';
                        $disabledClass = !$variantHasStock ? 'opacity-50 cursor-not-allowed' : '';
                    ?>
                        <button type="button"
                            class="variant-button px-3 py-2 border rounded-lg text-sm font-medium 
                                  hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200 
                                  <?= $isSelected ? 'selected-variant border-gray-400 bg-gray-100' : 'border-gray-300' ?>
                                  <?= $disabledClass ?>"
                            data-product-id="<?= $product_id ?>"
                            data-variant-id="<?= $variant['variant_id'] ?>"
                            data-variant-name="<?= htmlspecialchars($variant['variant_name']) ?>"
                            data-variant-price="<?= $variant['variant_price'] ?>"
                            data-discount-price="<?= $variant['discount_price'] ?>"
                            data-unit-type="<?= $variant['unit_type'] ?>"
                            data-minimum-order="<?= $variant['minimum_order'] ?>"
                            data-order-increment="<?= $variant['order_increment'] ?>"
                            data-stock-quantity="<?= $stockQuantity ?>"
                            data-has-stock="<?= $variantHasStock ? 'true' : 'false' ?>"
                            <?= $disabled ?>>
                            <?= htmlspecialchars($variant['variant_name']) ?>
                            <?php if (!$variantHasStock): ?>
                                <span class="ml-1 text-red-500">(No Stock)</span>
                            <?php endif; ?>
                        </button>
                    <?php 
                        $first = false;
                    endforeach; ?>
                </div>
            </div>

            <!-- Quantity Selector with Unit Display and Stock Limit -->
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
                <p class="text-xs text-green-600 mt-1 stock-info hidden"></p>
            </div>

            <!-- Price and Discount Display -->
            <div class="price-display mt-3"></div>

            <div class="flex-grow"></div>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex gap-2">
                    <!-- Add to Cart Button -->
                    <button type="submit" name="add_to_cart" 
                            class="cursor-pointer w-full py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-medium transition-all duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed" 
                            title="Add to Cart" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M17 17h-11v-14h-2" />
                            <path d="M6 5l14 1l-1 7h-13" />
                        </svg>
                        Add to Cart
                    </button>
                    
                    <!-- Facebook Share Button -->
                    <button type="button" onclick="shareToFacebook('<?= $shareUrlNew ?>')"
                            class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-white font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none">
                            <circle cx="16" cy="16" r="14" fill="url(#paint0_linear_87_7208)"/>
                            <path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/>
                            <defs><linearGradient id="paint0_linear_87_7208" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs>
                        </svg>
                    </button>
                    
                    <!-- General Share Button -->
                    <button type="button" onclick="shareProduct('<?= addslashes($shareTitle) ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrlNew ?>')"
                            class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 9h-1a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-8a2 2 0 0 0 -2 -2h-1" />
                            <path d="M12 14v-11" />
                            <path d="M9 6l3 -3l3 3" />
                        </svg>                      
                    </button>
                </div>
            </div>
          
            <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
            <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
            <p class="text-red-500 text-sm mt-2 stock-error-message hidden"></p>
            
            <?php else: ?>
            <p class="text-gray-500 text-sm text-center py-4">No variants available for this product</p>
            <?php endif; ?>
        </form>
        <?php else: ?>
            <!-- Out of Stock - No Add to Cart, only view details button -->
            <div class="flex-grow"></div>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="<?= $baseUrl ?>item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>" 
                  class="block w-full py-2 rounded-lg bg-gray-400 hover:bg-gray-500 text-white font-medium transition-all duration-300 text-center">
                    View Details
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php
    endforeach;
} else {
?>
    <div class="col-span-full">
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <!-- Icon -->
            <div class="flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 mb-4">
                <svg class="w-16 h-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16.69 7.44a6.973 6.973 0 0 0 -1.69 4.56c0 1.747 .64 3.345 1.699 4.571" />
                    <path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25 -7.761 -12.285 -6.142 -20 2.504" />
                    <path d="M18 11v.01" />
                    <path d="M11.5 10.5c-.667 1 -.667 2 0 3" />
                </svg>
            </div>

            <!-- Text -->
            <h3 class="text-lg font-semibold text-gray-800">
                <?php 
                if (!empty($search)) {
                    echo 'No products found matching "' . htmlspecialchars($search) . '"';
                } elseif (isset($_GET['category']) && !empty($_GET['category'])) {
                    echo 'No products found in the selected category';
                } elseif (isset($_GET['price']) && !empty($_GET['price'])) {
                    echo 'No products found in this price range';
                } else {
                    echo 'No products available';
                }
                ?>
            </h3>
            <p class="mt-2 text-gray-500 max-w-md">
                Please check back later or try browsing other categories.
            </p>
            <?php if (!empty($search) || !empty($_GET['category']) || !empty($_GET['price'])) { ?>
                <button onclick="clearAllFilters()" class="inline-flex items-center mt-5 px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    Clear All Filters
                </button>
            <?php } ?>
        </div>
    </div>
<?php
}
?>
</div>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>