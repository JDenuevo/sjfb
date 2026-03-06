<?php
// components/cart.php
// Requires cart_core.js loaded on the parent page.
// All cart manipulation (qty, remove, price recalc) is handled by cart_core.js.
$baseUrl   = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
$cart      = $_SESSION['cart'] ?? [];
$cartCount = count($cart);
$cartTotal = array_sum(array_map(fn($i) => (float)($i['price'] ?? 0) * (float)($i['quantity'] ?? 0), $cart));

function _cart_fmt(string $u): string {
    return match($u) {
        'piece' => 'pcs', 'kilogram' => 'kg', 'gram' => 'g', 'liter' => 'L',
        default => $u ?: 'pcs',
    };
}
?>

<div id="hs-cart-sidebar"
     class="fixed inset-0 z-50 bg-gray-900/50 hidden overflow-hidden"
     role="dialog" aria-label="Cart sidebar">

    <div id="sidebar-white-bg"
         class="fixed top-0 right-0 h-full bg-white shadow-xl flex flex-col
                transition-transform duration-300 translate-x-full overflow-y-auto
                w-full md:w-1/2 lg:w-1/3">

        <!-- Header -->
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-100 shrink-0">
            <h2 class="font-bold text-lg text-gray-800">
                Your Cart:&nbsp;
                <span id="cart-count-sidebar" class="cart-count text-orange-500"><?= $cartCount ?></span>
            </h2>
            <button type="button" onclick="closeOffCanvas()"
                    class="text-gray-400 hover:text-gray-700 transition-colors" aria-label="Close">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        <!-- Items -->
        <div id="cart-items-list" class="flex-1 overflow-y-auto divide-y divide-gray-50 px-1">
            <?php if (!empty($cart)): ?>
                <?php foreach ($cart as $index => $item):
                    $unitType  = $item['unit_type']      ?? 'piece';
                    $minOrder  = (float)($item['minimum_order']  ?? 1);
                    $orderIncr = (float)($item['order_increment'] ?? 1);
                    $qty       = (float)($item['quantity'] ?? $minOrder);
                    $price     = (float)($item['price']   ?? 0);
                    $unitLabel = _cart_fmt($unitType);
                    $displayQty = $unitType === 'piece' ? (int)$qty : $qty;
                ?>
                <div class="cart-item flex items-start gap-3 p-4"
                     data-cart-index="<?= $index ?>"
                     data-product-id="<?= htmlspecialchars((string)($item['product_id'] ?? '')) ?>"
                     data-variant-id="<?= htmlspecialchars((string)($item['variant_id'] ?? '')) ?>"
                     data-unit-type="<?= htmlspecialchars($unitType) ?>"
                     data-minimum-order="<?= $minOrder ?>"
                     data-order-increment="<?= $orderIncr ?>"
                     data-price-per-unit="<?= $price ?>">

                    <img src="<?= htmlspecialchars($item['image_url'] ?? '') ?>"
                         alt="<?= htmlspecialchars($item['product_name'] ?? '') ?>"
                         class="w-16 h-16 object-cover rounded-xl border border-gray-100 shrink-0">

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 truncate">
                            <?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($item['variant_name'] ?? '') ?></p>
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
                                ₱<?= number_format($price * $qty, 2) ?>
                            </span>
                        </div>
                    </div>

                    <button type="button" class="remove shrink-0 text-gray-300 hover:text-red-500 transition-colors mt-1" title="Remove">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-400 py-12 text-sm">Your cart is empty.</p>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 border-t border-gray-100 shrink-0 space-y-3">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800">Subtotal:</span>
                <span id="cart-total-sidebar" class="font-bold text-gray-800">
                    ₱<?= number_format($cartTotal, 2) ?>
                </span>
            </div>
            <p class="text-xs text-gray-400">Delivery fee calculated at checkout.</p>
            <a href="<?= $baseUrl ?>checkout.php"
               class="w-full py-3 px-4 inline-flex justify-center items-center gap-2
                      text-sm font-bold rounded-xl bg-orange-600 text-white hover:bg-orange-700 transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
                Checkout
            </a>
            <div class="text-center">
                <button type="button" onclick="closeOffCanvas()"
                        class="text-sm text-orange-500 hover:text-orange-600 hover:underline transition-colors">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>
</div>

<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
.cart-item input.quantity:focus { outline:2px solid #f97316; outline-offset:1px; border-radius:2px; }

@media (max-width: 768px) {
    #sidebar-white-bg {
        width: 100%;
    }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
    #sidebar-white-bg {
        width: 50%;
    }
    }

    @media (min-width: 1025px) {
    #sidebar-white-bg {
        width: 33.3333%;
    }
    }
</style>

<script>
// ── Off-canvas open/close ──────────────────────────────────────────────────────
function openOffCanvas() {
    document.getElementById('hs-cart-sidebar').classList.remove('hidden');
    requestAnimationFrame(function () {
        document.getElementById('sidebar-white-bg').classList.remove('translate-x-full');
    });
    // Always refresh cart content when opening
    if (typeof refreshCartFromServer === 'function') refreshCartFromServer();
}

function closeOffCanvas() {
    document.getElementById('sidebar-white-bg').classList.add('translate-x-full');
    setTimeout(function () {
        document.getElementById('hs-cart-sidebar').classList.add('hidden');
    }, 310);
}

// Close on overlay click
document.getElementById('hs-cart-sidebar').addEventListener('click', function (e) {
    if (e.target === this) closeOffCanvas();
});

// Wire [data-cart-toggle] buttons
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-cart-toggle]').forEach(function (btn) {
        btn.addEventListener('click', openOffCanvas);
    });
});
</script>