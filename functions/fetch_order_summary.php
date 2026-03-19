<?php
// functions/fetch_order_summary.php
// Called via AJAX whenever cart quantity changes on checkout page.
// Returns JSON with fresh HTML for the cart items + updated totals.
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../conn.php';

$cart      = $_SESSION['cart'] ?? [];
$cartTotal = array_sum(array_map(fn($i) => (float)($i['price'] ?? 0) * (float)($i['quantity'] ?? 0), $cart));
$cartCount = count($cart);

// Delivery fee — only calculate if city provided
$city         = trim($_GET['city'] ?? '');
$deliveryFee  = 0;
$feeDisplay   = '0.00';
$feeClass     = 'font-semibold text-gray-400';

if ($city) {
    require_once 'discount_helper.php';
    $deliveryFee = getDeliveryFee($city, $cartTotal, $conn);
    $feeDisplay  = '₱' . number_format($deliveryFee, 2);
    $feeClass    = 'font-semibold text-orange-600';
}

// Discount from voucher (passed via GET if one is applied)
$discountAmount = (float)($_GET['discount'] ?? 0);
$grandTotal     = max(0, $cartTotal - $discountAmount + $deliveryFee);

// Fetch live stock quantities
$stockMap = [];
if (!empty($cart)) {
    $variantIds = array_unique(array_column($cart, 'variant_id'));
    $variantIds = array_filter($variantIds, fn($v) => intval($v) > 0);
    if (!empty($variantIds)) {
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $types        = str_repeat('i', count($variantIds));
        $stmt = $conn->prepare("SELECT variant_id, stock_quantity FROM product_variants WHERE variant_id IN ($placeholders)");
        $stmt->bind_param($types, ...array_values($variantIds));
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $stockMap[(int)$row['variant_id']] = (int)$row['stock_quantity'];
        }
        $stmt->close();
    }
}

function _fmt_unit(string $u): string {
    return $u === 'piece' ? 'pcs' : $u;
}

// Build cart items HTML
ob_start();
if (!empty($cart)):
    foreach ($cart as $index => $item):
        $unitType  = $item['unit_type']       ?? 'piece';
        $unitDisp  = _fmt_unit($unitType);
        $minOrder  = (float)($item['minimum_order']   ?? 1);
        $orderIncr = (float)($item['order_increment'] ?? 1);
        $qty       = (float)$item['quantity'];
        $price     = (float)$item['price'];
        $displayQty= $unitType === 'piece' ? (int)$qty : $qty;
        $vid       = (int)($item['variant_id'] ?? 0);
        $stockQty  = $stockMap[$vid] ?? 999999;
        $isOverStock = $qty > $stockQty;
?>
<div class="cart-item flex gap-3 p-4 <?= $isOverStock ? 'bg-amber-50 cart-item-over-stock' : '' ?>"
     data-product-id="<?= $item['product_id'] ?>"
     data-variant-id="<?= $item['variant_id'] ?>"
     data-cart-index="<?= $index ?>"
     data-unit-type="<?= $unitType ?>"
     data-minimum-order="<?= $minOrder ?>"
     data-order-increment="<?= $orderIncr ?>"
     data-price-per-unit="<?= $price ?>"
     data-stock-quantity="<?= $stockQty ?>">

  <div class="relative shrink-0">
    <img src="<?= htmlspecialchars($item['image_url'] ?? '') ?>"
         alt="<?= htmlspecialchars($item['product_name'] ?? '') ?>"
         class="size-16 rounded-xl object-cover border border-gray-100">
  </div>

  <div class="flex-1 min-w-0">
    <p class="text-sm font-semibold truncate text-gray-800"><?= htmlspecialchars($item['product_name'] ?? '') ?></p>
    <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($item['variant_name'] ?? '') ?></p>

    <div class="flex items-center justify-between mt-2">
      <div class="qty-controls flex items-center border <?= $isOverStock ? 'border-amber-400' : 'border-gray-200' ?> rounded-lg overflow-hidden">
        <button type="button" class="decrease-quantity px-2.5 py-1 text-gray-500 hover:bg-orange-500 hover:text-white transition-colors text-sm font-bold">−</button>
        <input type="number"
               class="quantity w-14 text-center text-xs font-semibold bg-transparent border-0 py-1 focus:outline-none <?= $isOverStock ? 'text-amber-600' : '' ?>"
               value="<?= $displayQty ?>"
               min="<?= $minOrder ?>"
               step="<?= $orderIncr ?>"
               max="<?= $stockQty ?>">
        <button type="button" class="increase-quantity px-2.5 py-1 text-gray-500 hover:bg-orange-500 hover:text-white transition-colors text-sm font-bold">+</button>
      </div>
      <span class="item-price text-sm font-bold text-gray-800">₱<?= number_format($price * $qty, 2) ?></span>
    </div>
    <p class="text-xs text-gray-400 mt-1">Min: <?= $minOrder ?> <?= $unitDisp ?> &nbsp;·&nbsp; Stock: <?= $stockQty ?> <?= $unitDisp ?></p>
    <?php if ($isOverStock): ?>
    <p class="stock-error text-xs font-semibold text-amber-600 mt-1">
      Only <?= $stockQty ?> <?= $unitDisp ?> available — reduce to proceed
    </p>
    <?php endif; ?>
  </div>

  <button type="button"
          class="remove shrink-0 size-7 flex items-center justify-center rounded-lg transition-colors mt-0.5 text-gray-300 hover:text-red-500"
          title="Remove item">
    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
  </button>
</div>
<?php
    endforeach;
else:
    echo '<div class="py-10 text-center text-gray-400 text-sm">Your cart is empty.</div>';
endif;
$itemsHtml = ob_get_clean();

echo json_encode([
    'success'        => true,
    'items_html'     => $itemsHtml,
    'cart_count'     => $cartCount,
    'subtotal'       => $cartTotal,
    'delivery_fee'   => $deliveryFee,
    'fee_display'    => $feeDisplay,
    'fee_class'      => $feeClass,
    'free_shipping'  => $freeShipping,
    'discount'       => $discountAmount,
    'grand_total'    => $grandTotal,
]);