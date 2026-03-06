<?php
// functions/fetch_cart_items.php
// Returns JSON: { status, cart_items (HTML), cart_total, cart_count }
// Used by: cart.php sidebar, to_checkout.php, products.php, item.php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$cart = $_SESSION['cart'] ?? [];

function _fci_formatUnit(string $u): string {
    return match($u) {
        'piece'    => 'pcs',
        'kilogram' => 'kg',
        'gram'     => 'g',
        'liter'    => 'L',
        default    => $u ?: 'pcs',
    };
}

$html = '';
$total = 0;

if (!empty($cart)) {
    ob_start();
    foreach ($cart as $index => $item) {
        $unitType  = $item['unit_type']       ?? 'piece';
        $minOrder  = (float)($item['minimum_order']  ?? 1);
        $orderIncr = (float)($item['order_increment'] ?? 1);
        $qty       = (float)($item['quantity'] ?? $minOrder);
        $price     = (float)($item['price']    ?? 0);
        $unitLabel = _fci_formatUnit($unitType);
        $displayQty = $unitType === 'piece' ? (int)$qty : $qty;
        $linePrice  = $price * $qty;
        $total     += $linePrice;
    ?>
    <div class="cart-item flex items-start gap-3 p-4 border-b border-gray-100 last:border-b-0"
         data-cart-index="<?= $index ?>"
         data-product-id="<?= htmlspecialchars((string)($item['product_id']  ?? '')) ?>"
         data-variant-id="<?= htmlspecialchars((string)($item['variant_id']  ?? '')) ?>"
         data-unit-type="<?= htmlspecialchars($unitType) ?>"
         data-minimum-order="<?= $minOrder ?>"
         data-order-increment="<?= $orderIncr ?>"
         data-price-per-unit="<?= $price ?>">

        <img src="<?= htmlspecialchars($item['image_url'] ?? '') ?>"
             alt="<?= htmlspecialchars($item['product_name'] ?? '') ?>"
             class="w-16 h-16 object-cover rounded-xl border border-gray-100 shrink-0">

        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-800 truncate">
                <?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?>
            </p>
            <p class="text-xs text-gray-400 mt-0.5">
                <?= htmlspecialchars($item['variant_name'] ?? '') ?>
            </p>
            <p class="text-xs text-gray-400">Min: <?= $minOrder ?> <?= $unitLabel ?></p>

            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                    <button type="button"
                            class="decrease-quantity px-2.5 py-1 text-gray-500 hover:bg-orange-500 hover:text-white transition-colors text-sm font-bold">−</button>
                    <input type="number"
                           class="quantity w-14 text-center text-xs font-semibold bg-transparent border-0 py-1 focus:outline-none"
                           value="<?= $displayQty ?>"
                           min="<?= $minOrder ?>"
                           step="<?= $orderIncr ?>">
                    <button type="button"
                            class="increase-quantity px-2.5 py-1 text-gray-500 hover:bg-orange-500 hover:text-white transition-colors text-sm font-bold">+</button>
                </div>
                <span class="item-price text-sm font-bold text-gray-800">
                    ₱<?= number_format($linePrice, 2) ?>
                </span>
            </div>
        </div>

        <button type="button" class="remove shrink-0 text-gray-300 hover:text-red-500 transition-colors mt-1" title="Remove">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    </div>
    <?php
    }
    $html = ob_get_clean();
} else {
    $html = '<p style="text-align:center;color:#9ca3af;padding:2rem 0;font-size:.875rem">Your cart is empty.</p>';
}

echo json_encode([
    'status'     => 'success',
    'cart_items' => $html,
    'cart_total' => $total,
    'cart_count' => count($cart),
]);