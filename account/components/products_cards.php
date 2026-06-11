<?php
/**
 * accounts/components/product_cards.php
 *
 * Shared product card renderer.
 * Expects $fp_products (array) and $baseUrl (string) to be in scope.
 * Outputs the inner grid of cards — no wrapping <div class="grid"> here,
 * that wrapper lives in fetch_products.php so AJAX replacement is seamless.
 */

if (empty($fp_products)): ?>
<div class="col-span-full">
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 mb-4">
            <svg class="w-16 h-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16.69 7.44a6.973 6.973 0 0 0-1.69 4.56c0 1.747.64 3.345 1.699 4.571"/>
                <path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25-7.761-12.285-6.142-20 2.504"/>
                <path d="M18 11v.01"/><path d="M11.5 10.5c-.667 1-.667 2 0 3"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No products found</h3>
        <p class="mt-2 text-gray-500 max-w-sm">Try a different category or clear your filters.</p>
        <button onclick="clearAllFilters()"
                class="inline-flex items-center mt-5 px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            Clear Filters
        </button>
    </div>
</div>
<?php return; endif; ?>

<?php foreach ($fp_products as $product_id => $product):
    $product_name  = $product['product_name'];
    $product_unit  = $product['product_unit'];
    $image_url     = $product['image_url'];
    $variants      = $product['variants'];
    $category_names = $product['category_names'] ?? '';
    $hasStock      = $product['has_stock'];

    $nicknames = [];
    if (!empty($product['product_nickname'])) {
        $nd = json_decode($product['product_nickname'], true);
        if (is_array($nd)) $nicknames = array_slice($nd, 0, 3);
    }

    $shareUrl    = $baseUrl . 'item/' . urlencode(strtolower(str_replace(' ', '-', $product_name)));
    $shareTitle  = $product_name;
    $lowestPrice = !empty($variants) ? min(array_column($variants, 'variant_price')) : 0;
    $shareText   = 'Fresh ' . $product_name . ($lowestPrice > 0 ? ' starting at ₱' . number_format($lowestPrice, 2) : '') . ' — Order from St. Joseph Fish Brokerage Inc.!';

    $firstInStock = null;
    foreach ($variants as $v) { if ($v['has_stock']) { $firstInStock = $v; break; } }
?>
<div class="flex flex-col h-full bg-white shadow-lg rounded-lg p-5 relative group">

    <!-- Image -->
    <div class="relative">
        <a href="<?= $baseUrl ?>item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>" class="block">
            <img src="<?= htmlspecialchars($image_url) ?>"
                alt="<?= htmlspecialchars($product_name) ?>"
                class="w-full h-48 object-cover rounded-md mb-4 shadow-sm <?= !$hasStock ? 'opacity-60' : '' ?>"
                onerror="this.onerror=null; this.src='<?= $baseUrl ?>uploads/products/default.png'">
            <?php if (!$hasStock): ?>
            <div class="absolute inset-0 flex items-center justify-center rounded-md h-48 bg-opacity-100">
                <span class="bg-red-600 text-white font-bold py-2 px-4 rounded-lg transform -rotate-12 shadow-lg">OUT OF STOCK</span>
            </div>
            <?php endif; ?>
        </a>
    </div>

    <!-- Name / unit -->
    <h3 class="text-xl font-semibold text-gray-800 mb-1"><?= htmlspecialchars($product_name) ?></h3>
    <p class="text-md text-gray-500 mb-4"><?= htmlspecialchars($product_unit) ?></p>

    <!-- Nickname tags -->
    <?php if (!empty($nicknames)): ?>
    <div class="flex flex-wrap gap-1 mb-3">
        <?php foreach ($nicknames as $nick): ?>
        <span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs rounded-full border border-orange-200">
            #<?= htmlspecialchars($nick) ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Category -->
    <?php if (!empty($category_names)): ?>
    <div class="mb-3">
        <span class="text-xs text-gray-500">Categories: </span>
        <span class="text-xs font-medium text-gray-700"><?= htmlspecialchars($category_names) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($hasStock): ?>
    <!-- ── Add to Cart Form ── -->
    <form class="add-to-cart-form flex flex-col flex-grow" data-product-id="<?= $product_id ?>">
        <input type="hidden" name="add_to_cart"     value="1">
        <input type="hidden" name="product_id"      value="<?= $product_id ?>">
        <input type="hidden" name="variant_id"      value="<?= $firstInStock['variant_id'] ?? '' ?>">
        <input type="hidden" name="product_name"    value="<?= htmlspecialchars($product_name) ?>">
        <input type="hidden" name="variant_name"    value="<?= $firstInStock ? htmlspecialchars($firstInStock['variant_name']) : '' ?>">
        <input type="hidden" name="price"           value="<?= $firstInStock ? (floatval($firstInStock['discount_price']) > 0 ? $firstInStock['discount_price'] : $firstInStock['variant_price']) : '' ?>">
        <input type="hidden" name="image_url"       value="<?= htmlspecialchars($image_url) ?>">
        <input type="hidden" name="quantity"        value="<?= $firstInStock['minimum_order'] ?? 1 ?>">
        <input type="hidden" name="unit_type"       value="<?= $firstInStock['unit_type'] ?? '' ?>">
        <input type="hidden" name="minimum_order"   value="<?= $firstInStock['minimum_order'] ?? 1 ?>">
        <input type="hidden" name="order_increment" value="<?= $firstInStock['order_increment'] ?? 1 ?>">

        <!-- Variant select -->
        <div class="variant-control min-h-[72px]">
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Select Size:</label>
            <select class="variant-select w-full min-w-0 border border-gray-300 rounded-lg bg-white px-2 py-2 text-xs md:text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20" data-product-id="<?= $product_id ?>">
                <?php foreach ($variants as $v):
                    $vHasStock = $v['has_stock'];
                    $isSelected = ($firstInStock && $v['variant_id'] == $firstInStock['variant_id']);
                    $dp = floatval($v['discount_price'] ?? 0);
                ?>
                <option
                    value="<?= $v['variant_id'] ?>"
                    data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                    data-variant-price="<?= $v['variant_price'] ?>"
                    data-discount-price="<?= $dp ?>"
                    data-unit-type="<?= $v['unit_type'] ?>"
                    data-minimum-order="<?= $v['minimum_order'] ?>"
                    data-order-increment="<?= $v['order_increment'] ?>"
                    data-stock-quantity="<?= $v['stock_quantity'] ?>"
                    data-has-stock="<?= $vHasStock ? 'true' : 'false' ?>"
                    <?= $isSelected ? 'selected' : '' ?>
                    <?= !$vHasStock ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($v['variant_name']) ?><?= !$vHasStock ? ' - No Stock' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Qty -->
        <div class="quantity-control mt-2 md:mt-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                    <button type="button" class="decrease-quantity px-2 py-1 rounded-l text-sm hover:bg-orange-600 hover:text-white transition">−</button>
                    <input type="number" class="quantity w-14 px-1 py-1 text-center text-sm border-0 focus:outline-none"
                           value="<?= $firstInStock['minimum_order'] ?? 1 ?>"
                           min="<?= $firstInStock['minimum_order'] ?? 1 ?>"
                           step="<?= $firstInStock['order_increment'] ?? 1 ?>">
                    <button type="button" class="increase-quantity px-2 py-1 rounded-r text-sm hover:bg-orange-600 hover:text-white transition">+</button>
                </div>
                <span class="text-sm font-medium text-gray-600 unit-display">
                    <?php if ($firstInStock): echo $firstInStock['unit_type'] === 'piece' ? 'pcs' : htmlspecialchars($firstInStock['unit_type']); endif; ?>
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1 minimum-order-text">
                <?php if ($firstInStock): ?>
                    Minimum: <?= $firstInStock['minimum_order'] ?> <?= $firstInStock['unit_type'] === 'piece' ? 'pcs' : htmlspecialchars($firstInStock['unit_type']) ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Price -->
        <div class="price-display mt-3">
            <?php if ($firstInStock):
                $dp = floatval($firstInStock['discount_price'] ?? 0);
                $vp = floatval($firstInStock['variant_price']);
                $mo = floatval($firstInStock['minimum_order']);
                if ($dp > 0): ?>
                    <span style="text-decoration:line-through;color:#9ca3af;font-size:.875rem">₱<?= number_format($vp * $mo, 2) ?></span>
                    <span style="color:#dc2626;font-weight:700;margin-left:.5rem">₱<?= number_format($dp * $mo, 2) ?></span>
                <?php else: ?>
                    <span style="color:#1f2937;font-weight:700">₱<?= number_format($vp * $mo, 2) ?></span>
                <?php endif;
            endif; ?>
        </div>

        <div class="flex-grow"></div>

        <div class="product-actions mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-200">
            <div class="flex gap-2">
                <button type="submit" name="add_to_cart"
                        class="add-cart-btn cursor-pointer flex-1 min-w-0 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs md:text-sm font-medium transition-all duration-300 flex items-center justify-center"
                        title="Add to Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" class="me-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M17 17h-11v-14h-2"/>
                        <path d="M6 5l14 1l-1 7h-13"/>
                    </svg>
                    Add to Cart
                </button>
                <button type="button" onclick="shareToFacebook('<?= $shareUrl ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="14" fill="url(#fbg_pc_<?= $product_id ?>)"/>
                        <path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/>
                        <defs><linearGradient id="fbg_pc_<?= $product_id ?>" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs>
                    </svg>
                </button>
                <button type="button" onclick="shareProduct('<?= addslashes($shareTitle) ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrl ?>')"
                        class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 9h-1a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1"/>
                        <path d="M12 14v-11"/><path d="M9 6l3-3 3 3"/>
                    </svg>
                </button>
            </div>
        </div>

        <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
        <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
        <p class="text-red-500 text-sm mt-2 stock-error-message hidden"></p>
    </form>

    <?php else: ?>
    <!-- Out of stock -->
    <div class="flex-grow"></div>
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex gap-2">
            <a href="<?= $baseUrl ?>item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>"
               class="block w-full py-2 rounded-lg bg-gray-400 hover:bg-gray-500 text-white font-medium transition-all duration-300 text-center">
                View Details
            </a>
            <button type="button" onclick="shareToFacebook('<?= $shareUrl ?>')"
                    class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 font-medium transition-all duration-300 flex items-center justify-center" title="Share on Facebook">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" fill="none">
                    <circle cx="16" cy="16" r="14" fill="url(#fbg2_pc_<?= $product_id ?>)"/>
                    <path d="M21.2137 20.2816L21.8356 16.3301H17.9452V13.767C17.9452 12.6857 18.4877 11.6311 20.2302 11.6311H22V8.26699C22 8.26699 20.3945 8 18.8603 8C15.6548 8 13.5617 9.89294 13.5617 13.3184V16.3301H10V20.2816H13.5617V29.8345C14.2767 29.944 15.0082 29.994 15.7534 30C16.4986 30 17.2302 29.944 17.9452 29.8345V20.2816H21.2137Z" fill="white"/>
                    <defs><linearGradient id="fbg2_pc_<?= $product_id ?>" x1="16" y1="2" x2="16" y2="29.917" gradientUnits="userSpaceOnUse"><stop stop-color="#18ACFE"/><stop offset="1" stop-color="#0163E0"/></linearGradient></defs>
                </svg>
            </button>
            <button type="button" onclick="shareProduct('<?= addslashes($shareTitle) ?>', '<?= addslashes($shareText) ?>', '<?= $shareUrl ?>')"
                    class="cursor-pointer w-1/4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 text-dark font-medium transition-all duration-300 flex items-center justify-center" title="Share">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 9h-1a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1"/>
                    <path d="M12 14v-11"/><path d="M9 6l3-3 3 3"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
