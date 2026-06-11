<?php
/**
 * components/products_card.php
 *
 * Shopee-style product card. Always stacks vertically — responsive solely
 * via column width provided by the parent grid. Works in all three contexts:
 *
 *   products.php preview ($fp_limit set) → wider cols, bigger feel
 *   products.php full shop               → sidebar present, 2–4 cols
 *   fetch_products.php AJAX              → same as full shop
 *
 * Expects per iteration (set by caller):
 *   $fp_products  array   — keyed by product_id
 *   $baseUrl      string
 * Each product may have 'created_at' and 'total_sold'.
 */

if (empty($fp_products)): ?>
<div class="col-span-full flex flex-col items-center justify-center py-16 text-center">
    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-3">
        <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
    </div>
    <p class="text-sm font-semibold text-gray-700">No products found</p>
    <p class="text-xs text-gray-400 mt-1">Try a different category or clear your filters.</p>
    <button onclick="clearAllFilters()"
            class="mt-4 px-5 py-2 text-xs font-semibold bg-orange-600 text-white rounded-full hover:bg-orange-700 transition-colors">
        Clear Filters
    </button>
</div>
<?php return; endif; ?>

<?php foreach ($fp_products as $product_id => $product):
    $product_name = $product['product_name'];
    $product_unit = $product['product_unit'];
    $image_url    = $product['image_url'];
    $variants     = $product['variants'];
    $hasStock     = $product['has_stock'];
    $totalSold    = intval($product['total_sold'] ?? 0);

    // Sold label: 1k+ → "1k"
    $soldLabel = $totalSold >= 1000 ? round($totalSold / 1000, 1) . 'k' : $totalSold;

    // NEW badge — product added within last 7 days
    $isNew = false;
    if (!empty($product['created_at'])) {
        $createdAt = strtotime($product['created_at']);
        $isNew = $createdAt && (time() - $createdAt) < (7 * 24 * 60 * 60);
    }

    $firstInStock = null;
    foreach ($variants as $v) { if ($v['has_stock']) { $firstInStock = $v; break; } }

    // SALE badge + % off (based on first in-stock variant)
    $hasSale     = false;
    $salePercent = 0;
    if ($firstInStock) {
        $dp_s = floatval($firstInStock['discount_price'] ?? 0);
        $vp_s = floatval($firstInStock['variant_price']);
        if ($dp_s > 0 && $vp_s > 0) {
            $hasSale     = true;
            $salePercent = round((($vp_s - $dp_s) / $vp_s) * 100);
        }
    }

    // Display price for out-of-stock fallback
    $displayPrice = 0;
    if (!empty($variants)) {
        $displayPrice = floatval($variants[0]['discount_price'] ?? 0) > 0
            ? floatval($variants[0]['discount_price'])
            : floatval($variants[0]['variant_price']);
    }

    // Share helpers
    $itemUrl    = $baseUrl . 'item/' . urlencode(strtolower(str_replace(' ', '-', $product_name)));
    $shareUrl   = $itemUrl;
    $shareTitle = $product_name;
    $lowestPrice = !empty($variants) ? min(array_column($variants, 'variant_price')) : 0;
    $shareText  = 'Fresh ' . $product_name . ($lowestPrice > 0 ? ' starting at ₱' . number_format($lowestPrice, 2) : '') . ' — Order from St. Joseph Fish Brokerage Inc.!';
?>
<div class="spc group relative flex flex-col bg-white overflow-hidden
            border border-gray-100 hover:border-orange-300
            hover:shadow-[0_4px_20px_rgba(0,0,0,0.10)] hover:-translate-y-0.5
            transition-all duration-150 cursor-pointer"
     data-product-id="<?= $product_id ?>">

    <!-- ── IMAGE ────────────────────────────────────────────────────────────── -->
    <a href="<?= $shareUrl ?>" class="block relative flex-shrink-0 bg-gray-50 overflow-hidden">
        <div class="aspect-square overflow-hidden">
            <img src="<?= htmlspecialchars($image_url) ?>"
                 alt="<?= htmlspecialchars($product_name) ?>"
                 loading="lazy"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.04]
                        <?= !$hasStock ? 'opacity-55' : '' ?>"
                 onerror="this.onerror=null;this.src='<?= $baseUrl ?>uploads/products/default.png'">
        </div>

        <!-- SALE badge — Shopee-style, top-right corner tab -->
        <?php if ($hasSale): ?>
        <div class="sale-badge absolute top-0 right-0 bg-orange-500 text-white font-bold leading-tight px-1.5 py-0.5
                    text-[11px] sm:text-xs">
            🔥 -<?= $salePercent ?>%
        </div>
        <?php endif; ?>

        <!-- NEW badge — top-left corner tab -->
        <?php if ($isNew): ?>
        <div class="new-badge absolute top-0 left-0 bg-emerald-500 text-white font-bold leading-tight px-1.5 py-0.5
                    text-[11px] sm:text-xs tracking-wide">
            ✦ NEW
        </div>
        <?php endif; ?>

        <!-- Out of stock overlay -->
        <?php if (!$hasStock): ?>
        <div class="absolute inset-0 flex items-center justify-center bg-white/40">
            <span class="bg-gray-700/80 text-white text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full">
                Out of Stock
            </span>
        </div>
        <?php endif; ?>

    </a><!-- /image -->

    <!-- ── INFO BLOCK ───────────────────────────────────────────────────────── -->
    <div class="flex flex-col flex-grow px-2 pt-2 pb-2">

        <!-- Product name -->
        <a href="<?= $shareUrl ?>" class="block mb-1.5">
            <p class="spc-name font-semibold text-gray-800 leading-snug line-clamp-2 hover:text-orange-500 transition-colors">
                <?= htmlspecialchars($product_name) ?>
            </p>
        </a>

        <?php if ($hasStock && $firstInStock):
            $dp_init  = floatval($firstInStock['discount_price'] ?? 0);
            $vp_init  = floatval($firstInStock['variant_price']);
            $pct_init = ($dp_init > 0 && $vp_init > 0) ? round((($vp_init - $dp_init) / $vp_init) * 100) : 0;
        ?>

        <form class="add-to-cart-form flex flex-col flex-grow" data-product-id="<?= $product_id ?>">
            <input type="hidden" name="add_to_cart"     value="1">
            <input type="hidden" name="product_id"      value="<?= $product_id ?>">
            <input type="hidden" name="variant_id"      value="<?= $firstInStock['variant_id'] ?? '' ?>">
            <input type="hidden" name="product_name"    value="<?= htmlspecialchars($product_name) ?>">
            <input type="hidden" name="variant_name"    value="<?= htmlspecialchars($firstInStock['variant_name'] ?? '') ?>">
            <input type="hidden" name="price"           value="<?= $dp_init > 0 ? $firstInStock['discount_price'] : $firstInStock['variant_price'] ?>">
            <input type="hidden" name="image_url"       value="<?= htmlspecialchars($image_url) ?>">
            <input type="hidden" name="quantity"        value="<?= $firstInStock['minimum_order'] ?? 1 ?>">
            <input type="hidden" name="unit_type"       value="<?= htmlspecialchars($firstInStock['unit_type'] ?? '') ?>">
            <input type="hidden" name="minimum_order"   value="<?= $firstInStock['minimum_order'] ?? 1 ?>">
            <input type="hidden" name="order_increment" value="<?= $firstInStock['order_increment'] ?? 1 ?>">

            <!-- Variant select: shown only when >1 variant; single-variant uses hidden select for JS -->
            <?php if (count($variants) > 1): ?>
            <div class="mb-1.5">
                <select class="variant-select w-full px-2 py-1 border border-gray-200 rounded
                               text-[11px] text-gray-600 bg-white
                               focus:outline-none focus:ring-1 focus:ring-orange-400 focus:border-orange-400
                               hover:border-orange-300 transition cursor-pointer"
                        data-product-id="<?= $product_id ?>">
                    <?php foreach ($variants as $v):
                        $vdp  = floatval($v['discount_price'] ?? 0);
                        $vvp  = floatval($v['variant_price']);
                        $vpct = ($vdp > 0 && $vvp > 0) ? round((($vvp - $vdp) / $vvp) * 100) : 0;
                        $isSelected = ($v['variant_id'] == $firstInStock['variant_id']);
                        $label = htmlspecialchars($v['variant_name']);
                        if (!$v['has_stock']) $label .= ' (No Stock)';
                        elseif ($vpct > 0)    $label .= ' (-' . $vpct . '%)';
                    ?>
                    <option
                        value="<?= $v['variant_id'] ?>"
                        data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                        data-variant-price="<?= $vvp ?>"
                        data-discount-price="<?= $vdp ?>"
                        data-discount-percent="<?= $vpct ?>"
                        data-unit-type="<?= htmlspecialchars($v['unit_type'] ?? 'piece') ?>"
                        data-minimum-order="<?= floatval($v['minimum_order'] ?? 1) ?>"
                        data-order-increment="<?= floatval($v['order_increment'] ?? 1) ?>"
                        data-stock-quantity="<?= intval($v['stock_quantity'] ?? 0) ?>"
                        data-has-stock="<?= $v['has_stock'] ? 'true' : 'false' ?>"
                        <?= $isSelected ? 'selected' : '' ?>
                        <?= !$v['has_stock'] ? 'disabled' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else:
                $v   = $firstInStock;
                $vdp = floatval($v['discount_price'] ?? 0);
            ?>
            <!-- Hidden single-variant select — needed for product_process.js -->
            <select class="variant-select hidden" data-product-id="<?= $product_id ?>">
                <option
                    value="<?= $v['variant_id'] ?>"
                    data-variant-name="<?= htmlspecialchars($v['variant_name']) ?>"
                    data-variant-price="<?= floatval($v['variant_price']) ?>"
                    data-discount-price="<?= $vdp ?>"
                    data-discount-percent="<?= $pct_init ?>"
                    data-unit-type="<?= htmlspecialchars($v['unit_type'] ?? 'piece') ?>"
                    data-minimum-order="<?= floatval($v['minimum_order'] ?? 1) ?>"
                    data-order-increment="<?= floatval($v['order_increment'] ?? 1) ?>"
                    data-stock-quantity="<?= intval($v['stock_quantity'] ?? 0) ?>"
                    data-has-stock="true"
                    selected>
                    <?= htmlspecialchars($v['variant_name']) ?>
                </option>
            </select>
            <?php endif; ?>

            <!-- Qty stepper -->
            <div class="flex items-center gap-1.5 mb-1.5">
                <div class="flex items-center border border-gray-200 rounded overflow-hidden flex-shrink-0">
                    <button type="button"
                            class="decrease-quantity w-6 h-6 flex items-center justify-center
                                   text-gray-500 hover:bg-orange-500 hover:text-white transition text-sm font-bold">−</button>
                    <input type="number"
                           class="quantity w-14 text-center text-xs font-semibold bg-transparent border-0 focus:outline-none leading-none"
                           value="<?= $firstInStock['minimum_order'] ?? 1 ?>"
                           min="<?= $firstInStock['minimum_order'] ?? 1 ?>"
                           step="<?= $firstInStock['order_increment'] ?? 1 ?>">
                    <button type="button"
                            class="increase-quantity w-6 h-6 flex items-center justify-center
                                   text-gray-500 hover:bg-orange-500 hover:text-white transition text-sm font-bold">+</button>
                </div>
                <span class="text-[11px] text-gray-400 unit-display">
                    <?= $firstInStock['unit_type'] === 'piece' ? 'pcs' : htmlspecialchars($firstInStock['unit_type']) ?>
                </span>
            </div>

            <!-- Price row — price left, sold right (updated by product_process.js) -->
            <div class="flex items-end justify-between mt-auto gap-1">
                <!-- .price-display filled by _updateCardPriceDisplay() in product_process.js -->
                <div class="price-display leading-none">
                    <?php if ($dp_init > 0): ?>
                        <span class="original-price block text-[10px] line-through text-gray-400">₱<?= number_format($vp_init, 2) ?></span>
                        <div class="flex items-baseline gap-1 flex-wrap">
                            <span class="sale-price text-sm font-bold text-red-600">₱<?= number_format($dp_init, 2) ?></span>
                            <span class="discount-pill text-[10px] font-semibold text-orange-600">-<?= $pct_init ?>%</span>
                        </div>
                    <?php else: ?>
                        <span class="original-price hidden text-[10px] line-through text-gray-400"></span>
                        <div class="flex items-baseline gap-1">
                            <span class="sale-price text-sm font-bold text-orange-600">₱<?= number_format($vp_init, 2) ?></span>
                            <span class="discount-pill hidden text-[10px] font-semibold text-orange-600"></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($totalSold > 0): ?>
                <span class="text-[10px] text-gray-400 whitespace-nowrap shrink-0"><?= $soldLabel ?> sold</span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart button -->
            <div class="pt-2 mt-1 border-t border-gray-100">
                <button type="submit" name="add_to_cart"
                        class="spc-atc w-full py-1.5 bg-orange-500 hover:bg-orange-600 active:bg-orange-700
                               text-white font-semibold rounded
                               transition-colors flex items-center justify-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M17 17h-11v-14h-2"/>
                        <path d="M6 5l14 1l-1 7h-13"/>
                    </svg>
                    Add to Cart
                </button>
            </div>

            <p class="text-red-500 text-[10px] mt-1 variant-message hidden">Please select a variant.</p>
            <p class="text-red-500 text-[10px] mt-1 minimum-error-message hidden"></p>
            <p class="text-red-500 text-[10px] mt-1 stock-error-message hidden"></p>
        </form>

        <?php else: ?>
        <!-- Out of stock state -->
        <div class="flex items-end justify-between mt-auto pt-1 gap-1">
            <?php if ($displayPrice > 0): ?>
            <span class="text-sm font-bold text-gray-400">₱<?= number_format($displayPrice, 2) ?></span>
            <?php endif; ?>
            <?php if ($totalSold > 0): ?>
            <span class="text-[10px] text-gray-400 shrink-0"><?= $soldLabel ?> sold</span>
            <?php endif; ?>
        </div>
        <a href="<?= $shareUrl ?>"
           class="mt-2 block w-full py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-500
                  text-xs font-semibold text-center rounded transition-colors">
            View Details
        </a>
        <?php endif; ?>

    </div><!-- /info -->
</div><!-- /spc -->
<?php endforeach; ?>

<style>
/* Card: sharp 2px radius, Shopee-style */
.spc { border-radius: 2px; }

/* Name: 11px mobile → 12px sm+ */
.spc-name { font-size: 11px; }
@media (min-width: 640px) { .spc-name { font-size: 12px; } }

/* Add to cart button size */
.spc-atc { font-size: 11px; }
@media (min-width: 640px) { .spc-atc { font-size: 12px; } }
</style>