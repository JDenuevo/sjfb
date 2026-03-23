<?php
// components/to_checkout.php
// Requires cart_process.js loaded on the parent checkout.php page.

$cart      = $_SESSION['cart'] ?? [];
$cartTotal = array_sum(array_map(fn($i) => (float)($i['price'] ?? 0) * (float)($i['quantity'] ?? 0), $cart));
$cartCount = count($cart);

$cartErrors = $_SESSION['cart_errors'] ?? [];
unset($_SESSION['cart_errors']);
$orderError = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$errorNames = array_column($cartErrors, 'product_name');
$hasErrors  = !empty($cartErrors);

$savedData = $_SESSION['pending_checkout'] ?? [];

// ── Fetch live stock quantities ────────────────────────────────────────────
$stockMap = [];
if (!empty($cart)) {
    $variantIds = array_unique(array_column($cart, 'variant_id'));
    $variantIds = array_filter($variantIds, fn($v) => intval($v) > 0);
    if (!empty($variantIds)) {
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $types        = str_repeat('i', count($variantIds));
        $stmt = $conn->prepare(
            "SELECT variant_id, stock_quantity FROM product_variants WHERE variant_id IN ($placeholders)"
        );
        $stmt->bind_param($types, ...array_values($variantIds));
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $stockMap[(int)$row['variant_id']] = (int)$row['stock_quantity'];
        }
        $stmt->close();
    }
}

$stockExceeded = [];
foreach ($cart as $item) {
    $vid   = (int)($item['variant_id'] ?? 0);
    $qty   = (float)($item['quantity'] ?? 0);
    $stock = $stockMap[$vid] ?? PHP_INT_MAX;
    if ($stock !== PHP_INT_MAX && $qty > $stock) {
        $stockExceeded[$vid] = $stock;
    }
}
$hasStockIssues = !empty($stockExceeded);
$blockCheckout  = $hasErrors || $hasStockIssues;

function getFormValue($field, $userDetails, $savedData) {
    if (isset($savedData[$field]) && $savedData[$field] !== '') {
        return htmlspecialchars($savedData[$field]);
    }
    return isset($userDetails[$field]) ? htmlspecialchars($userDetails[$field]) : '';
}
?>

<div class="max-w-6xl mx-auto">
  <form action="./functions/add.php" method="POST" id="checkoutForm">

    <?php if ($hasErrors): ?>
    <div id="cartErrorBanner" class="max-w-6xl mx-auto mb-5 rounded-2xl overflow-hidden border border-red-200 shadow-sm">
      <div class="flex items-center gap-3 px-5 py-3 bg-red-600">
        <svg class="size-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p class="text-sm font-bold text-white flex-1">Some items in your cart are unavailable — remove them to continue.</p>
        <button onclick="document.getElementById('cartErrorBanner').remove()" class="text-red-200 hover:text-white">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
      <ul class="bg-red-50 px-5 py-4 space-y-2">
        <?php foreach ($cartErrors as $err): ?>
        <li class="flex items-start gap-2 text-sm text-red-700">
          <svg class="size-4 text-red-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          <?= htmlspecialchars($err['message']) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if ($hasStockIssues): ?>
    <div id="stockWarningBanner" class="max-w-6xl mx-auto mb-5 rounded-2xl overflow-hidden border border-amber-200 shadow-sm">
      <div class="flex items-center gap-3 px-5 py-3 bg-amber-500">
        <svg class="size-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <p class="text-sm font-bold text-white flex-1">Some quantities exceed available stock — adjust them to proceed.</p>
        <button onclick="document.getElementById('stockWarningBanner').remove()" class="text-amber-100 hover:text-white">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($orderError)): ?>
    <div id="orderErrorBanner" class="max-w-6xl mx-auto mb-5 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 shadow-sm">
      <svg class="size-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <p class="text-sm font-medium text-amber-800 flex-1"><?= htmlspecialchars($orderError) ?></p>
      <button onclick="document.getElementById('orderErrorBanner').remove()" class="text-amber-300 hover:text-amber-600">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

      <!-- ══ LEFT — Form fields ══════════════════════════════════════════════ -->
      <div class="lg:col-span-2 space-y-5">

        <!-- Contact Details -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="size-7 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-800">Contact Details</h3>
              <p class="text-xs text-gray-400">We'll use this for delivery and updates.</p>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">First Name <span class="text-red-400">*</span></label>
                <input type="text" name="recipient_first_name"
                  value="<?= getFormValue('first_name', $userDetails, $savedData) ?>"
                  placeholder="Juan" class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
                <p class="hidden text-xs text-red-500 mt-1" id="first_name-error">Enter your first name.</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Last Name <span class="text-red-400">*</span></label>
                <input type="text" name="recipient_last_name"
                  value="<?= getFormValue('last_name', $userDetails, $savedData) ?>"
                  placeholder="dela Cruz" class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
                <p class="hidden text-xs text-red-500 mt-1" id="last_name-error">Enter your last name.</p>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                <input type="email" name="recipient_email"
                  value="<?= getFormValue('email', $userDetails, $savedData) ?>"
                  placeholder="juan@email.com" class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
                <p class="hidden text-xs text-red-500 mt-1" id="email-error">Enter a valid email.</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone Number <span class="text-red-400">*</span></label>
                <input type="tel" name="recipient_phone"
                  value="<?= getFormValue('phone_number', $userDetails, $savedData) ?>"
                  placeholder="09-XXXX-XXXX" maxlength="11"
                  class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
                <p class="hidden text-xs text-red-500 mt-1" id="phone_number-error">Enter a valid phone number.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Address -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="size-7 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-800">Delivery Address</h3>
              <p class="text-xs text-gray-400">Where should we deliver your order?</p>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Street Address <span class="text-red-400">*</span></label>
              <input type="text" name="recipient_address"
                value="<?= getFormValue('address', $userDetails, $savedData) ?>"
                placeholder="House no., Street, Barangay"
                class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
              <p class="hidden text-xs text-red-500 mt-1" id="address-error">Enter your address.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="relative">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">City / Municipality <span class="text-red-400">*</span></label>
                <input type="text" id="City" name="city"
                      value="<?= htmlspecialchars($userDetails['city'] ?? $savedData['city'] ?? '') ?>"
                      placeholder="Type your city..."
                      class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm"
                      autocomplete="off" required>
                <div id="city-suggestions" class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto hidden"></div>
                <p class="hidden text-xs text-red-500 mt-1" id="city-error">Enter your city.</p>
                <p class="text-xs text-gray-400 mt-1" id="city-fee-preview"></p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Postal Code <span class="text-red-400">*</span></label>
                <input type="number" name="postal_code"
                  value="<?= getFormValue('postal_code', $userDetails, $savedData) ?>"
                  placeholder="XXXX"
                  class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" required>
                <p class="hidden text-xs text-red-500 mt-1" id="postal_code-error">Enter your postal code.</p>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Delivery Notes <span class="text-gray-400 font-normal">(optional)</span></label>
              <textarea name="delivery_notes" rows="2"
                placeholder="e.g. Leave at the gate, ring the bell twice…"
                class="checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm resize-none"><?= getFormValue('delivery_notes', $userDetails, $savedData) ?></textarea>
            </div>
          </div>
        </div>

        <!-- Voucher -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="size-7 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-800">Voucher / Discount Code</h3>
              <p class="text-xs text-gray-400">Enter a code to get a discount</p>
            </div>
          </div>
          <div class="p-6">
            <div class="flex gap-2">
              <input type="text" name="voucher_code" id="voucher_code"
                    value="<?= htmlspecialchars($_POST['voucher_code'] ?? '') ?>"
                    placeholder="e.g. VIP10, WELCOME20"
                    class="flex-1 checkout-input w-full border border-gray-200 rounded-xl px-4 py-3 text-sm"
                    autocomplete="off" style="text-transform:uppercase">
              <button type="button" id="applyVoucherBtn"
                      class="px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      <?= $blockCheckout ? 'disabled' : '' ?>>
                Apply
              </button>
            </div>
            <div id="voucherMessage" class="text-xs mt-2 hidden"></div>
            <div id="appliedVoucher" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg class="size-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium text-green-800" id="appliedVoucherCode"></span>
              </div>
              <button type="button" id="removeVoucherBtn" class="text-xs text-green-700 hover:text-red-600 font-medium">Remove</button>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="size-7 rounded-lg bg-orange-100 flex items-center justify-center">
              <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-800">Payment Method</h3>
              <p class="text-xs text-gray-400">Choose how you'd like to pay.</p>
            </div>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <?php
              $savedPayment = $userDetails['payment_method'] ?? '';
              $methods = [
                ['id'=>'cod',      'val'=>'cod',       'label'=>'Cash on Delivery', 'sub'=>'Pay when delivered',  'icon_type'=>'svg',  'icon'=>'<path d="M7 15h-3a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M7 9m0 1a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-12a1 1 0 0 1-1-1z"/><path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/>',  'bg'=>'bg-green-100',  'color'=>'text-green-700'],
                ['id'=>'gcash',    'val'=>'gcash',     'label'=>'GCash',            'sub'=>'Mobile wallet',       'icon_type'=>'img',  'icon'=>'./assets/icons/gcash.png',    'bg'=>'bg-blue-100'],
                ['id'=>'maya',     'val'=>'paymaya',   'label'=>'Maya',             'sub'=>'Mobile wallet',       'icon_type'=>'img',  'icon'=>'./assets/icons/maya.png',     'bg'=>'bg-green-100'],
                ['id'=>'qrph',     'val'=>'qrph',      'label'=>'QR Ph',            'sub'=>'Scan to pay',         'icon_type'=>'img',  'icon'=>'./assets/icons/qrph.png',     'bg'=>'bg-indigo-100'],
                ['id'=>'grab_pay', 'val'=>'grab_pay',  'label'=>'GrabPay',          'sub'=>'Grab wallet',         'icon_type'=>'img',  'icon'=>'./assets/icons/grabpay.png',  'bg'=>'bg-green-100'],
                ['id'=>'card',     'val'=>'card',       'label'=>'Credit / Debit Card','sub'=>'Visa, Mastercard',  'icon_type'=>'img',  'icon'=>'./assets/icons/card.png',     'bg'=>'bg-purple-100'],
              ];
              foreach ($methods as $m): ?>
              <div class="payment-option">
                <input type="radio" name="payment_method" id="<?= $m['id'] ?>" value="<?= $m['val'] ?>" class="sr-only"
                       <?= $savedPayment === $m['val'] ? 'checked' : '' ?>>
                <label for="<?= $m['id'] ?>" class="payment-label">
                  <div class="payment-icon-wrap <?= $m['bg'] ?>">
                    <?php if ($m['icon_type'] === 'svg'): ?>
                    <svg class="size-5 <?= $m['color'] ?? '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><?= $m['icon'] ?></svg>
                    <?php else: ?>
                    <img src="<?= $m['icon'] ?>" alt="<?= $m['label'] ?>" class="size-5 object-contain">
                    <?php endif; ?>
                  </div>
                  <div>
                    <span class="payment-name"><?= $m['label'] ?></span>
                    <span class="payment-sub"><?= $m['sub'] ?></span>
                  </div>
                  <div class="payment-check">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                  </div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
            <p class="hidden text-xs text-red-500 mt-3" id="payment_method-error">Please select a payment method.</p>
          </div>
        </div>

        <input type="hidden" name="total_amount"    id="total_amount"    value="<?= number_format($cartTotal, 2, '.', '') ?>">
      </div>

      <!-- ══ RIGHT — Order Summary ══════════════════════════════════════════ -->
      <div class="lg:col-span-1">
        <div class="sticky top-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="flex items-center gap-2">
              <svg class="size-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <h2 class="text-sm font-bold text-gray-800">Your Order</h2>
            </div>
            <span id="cart-count-sidebar"
                  class="cart-count inline-flex items-center justify-center size-5 rounded-full bg-orange-500 text-white text-[11px] font-bold">
              <?= $cartCount ?>
            </span>
          </div>

          <!-- Cart items list -->
          <div id="cart-items-list" class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
            <?php if (!empty($cart)): ?>
              <?php foreach ($cart as $index => $item):
                $isErr     = in_array($item['product_name'], $errorNames);
                $unitType  = $item['unit_type']      ?? 'piece';
                $unitDisp  = $unitType === 'piece'   ? 'pcs' : $unitType;
                $minOrder  = (float)($item['minimum_order']  ?? 1);
                $orderIncr = (float)($item['order_increment'] ?? 1);
                $qty       = (float)$item['quantity'];
                $price     = (float)$item['price'];
                $displayQty= $unitType === 'piece' ? (int)$qty : $qty;
                $vid       = (int)($item['variant_id'] ?? 0);
                $stockQty  = $stockMap[$vid] ?? 999999;
                $isOverStock = !$isErr && ($qty > $stockQty);
              ?>
              <div class="cart-item flex gap-3 p-4 <?= $isErr ? 'bg-red-50 cart-item-error' : ($isOverStock ? 'bg-amber-50 cart-item-over-stock' : '') ?>"
                   data-product-id="<?= $item['product_id'] ?>"
                   data-variant-id="<?= $item['variant_id'] ?>"
                   data-cart-index="<?= $index ?>"
                   data-unit-type="<?= $unitType ?>"
                   data-minimum-order="<?= $minOrder ?>"
                   data-order-increment="<?= $orderIncr ?>"
                   data-price-per-unit="<?= $price ?>"
                   data-stock-quantity="<?= $stockQty ?>">

                <div class="relative shrink-0">
                  <img src="<?= htmlspecialchars($item['image_url']) ?>"
                       alt="<?= htmlspecialchars($item['product_name']) ?>"
                       class="size-16 rounded-xl object-cover border <?= $isErr ? 'border-red-200 opacity-50 grayscale' : 'border-gray-100' ?>">
                  <?php if ($isErr): ?>
                  <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-red-400/30">
                    <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                  </div>
                  <?php endif; ?>
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold truncate <?= $isErr ? 'text-red-700' : 'text-gray-800' ?>">
                    <?= htmlspecialchars($item['product_name']) ?>
                  </p>
                  <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($item['variant_name']) ?></p>

                  <?php if ($isErr): ?>
                  <div class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                    <svg class="size-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    Unavailable — please remove
                  </div>
                  <?php else: ?>
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
                    <span class="item-price text-sm font-bold text-gray-800">
                      ₱<?= number_format($price * $qty, 2) ?>
                    </span>
                  </div>
                  <p class="text-xs text-gray-400 mt-1">Min: <?= $minOrder ?> <?= $unitDisp ?> &nbsp;·&nbsp; Stock: <?= $stockQty ?> <?= $unitDisp ?></p>
                  <p class="stock-error text-xs font-semibold text-amber-600 mt-1 <?= $isOverStock ? '' : 'hidden' ?>">
                    <?= $isOverStock ? 'Only ' . $stockQty . ' ' . $unitDisp . ' available — reduce to proceed' : '' ?>
                  </p>
                  <?php endif; ?>
                </div>

                <button type="button"
                        class="remove shrink-0 size-7 flex items-center justify-center rounded-lg transition-colors mt-0.5
                               <?= $isErr ? 'bg-red-100 text-red-500 hover:bg-red-500 hover:text-white' : 'text-gray-300 hover:text-red-500' ?>"
                        title="Remove item">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="py-10 text-center text-gray-400 text-sm">Your cart is empty.</div>
            <?php endif; ?>
          </div>

          <!-- Totals -->
          <div class="border-t border-gray-100 p-5 space-y-3">
            <div class="flex justify-between text-sm text-gray-500">
              <span>Subtotal</span>
              <span id="cart-subtotal" class="font-semibold text-gray-800">₱<?= number_format($cartTotal, 2) ?></span>
            </div>

            <div class="flex justify-between text-sm text-gray-500">
              <span>Delivery Fee</span>
              <span id="delivery-fee-display" class="font-semibold text-gray-400">Enter city first</span>
            </div>

            <!-- Discount (hidden until a voucher is applied) -->
            <div id="discount-line-container" class="hidden">
              <div class="flex justify-between text-sm text-green-600">
                <span>Discount</span>
                <span id="discount-amount" class="font-semibold">-₱0.00</span>
              </div>
            </div>

            <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
              <span>Total</span>
              <span class="text-orange-600" id="cart-grand-total">₱<?= number_format($cartTotal, 2) ?></span>
            </div>

            <button type="submit" name="complete_order" id="submitBtn"
              class="w-full mt-1 flex items-center justify-center gap-2 font-bold text-sm rounded-xl py-3 transition-all
                    <?= $blockCheckout ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-orange-600 hover:bg-orange-500 active:scale-95 text-white' ?>"
              <?= $blockCheckout ? 'disabled' : '' ?>>
              <?php if ($hasErrors): ?>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>Resolve Cart Issues First
              <?php elseif ($hasStockIssues): ?>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Adjust Quantities Above
              <?php else: ?>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>Complete Order
              <?php endif; ?>
            </button>

            <?php if ($hasErrors): ?>
            <p class="text-center text-xs text-red-500 font-medium">↑ Remove the flagged item(s) to enable checkout.</p>
            <?php elseif ($hasStockIssues): ?>
            <p class="text-center text-xs text-amber-600 font-medium" id="stockHintText">↑ Reduce highlighted quantities to available stock.</p>
            <?php endif; ?>

            <div class="flex items-center justify-center gap-4 pt-1">
              <span class="flex items-center gap-1 text-xs text-gray-400"><svg class="size-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Secure</span>
              <span class="flex items-center gap-1 text-xs text-gray-400"><svg class="size-3.5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>Verified</span>
              <span class="flex items-center gap-1 text-xs text-gray-400"><svg class="size-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Fast Delivery</span>
            </div>
            <div class="text-center pt-1">
              <a href="shop.php" class="text-xs text-orange-500 hover:text-orange-600 hover:underline">← Continue Shopping</a>
            </div>

            <!-- Hidden fields — read by add.php on submit -->
            <input type="hidden" name="delivery_fee"     id="delivery_fee_input"     value="0">
            <input type="hidden" name="subtotal"         id="subtotal_input"         value="<?= number_format($cartTotal, 2, '.', '') ?>">
            <input type="hidden" name="discount_amount"  id="discount_amount_input"  value="0">
            <!-- Mirror of applied voucher code — always submitted even when input is readonly -->
            <input type="hidden" name="applied_voucher_code" id="applied_voucher_code_input" value="">
          </div>
        </div>
      </div>

    </div>
  </form>
</div>

<style>
  .checkout-input { transition: border-color .2s, box-shadow .2s; font-family: 'Lexend', sans-serif; }
  .checkout-input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
  .checkout-input.error { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
  input[type=number]::-webkit-inner-spin-button,
  input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
  .cart-item input.quantity:focus { outline: 2px solid #f97316; outline-offset: 1px; border-radius: 2px; }
  .cart-item-over-stock .quantity { color: #d97706 !important; font-weight: 700; }
  .cart-item-over-stock .qty-controls { border-color: #f59e0b !important; }
  .payment-label { display:flex; align-items:center; gap:12px; border:2px solid #e5e7eb; border-radius:14px; padding:12px 14px; cursor:pointer; transition:all .2s; background:#fff; }
  .payment-label:hover { border-color:#fdba74; background:#fff7ed; }
  .payment-option input[type="radio"]:checked + .payment-label { border-color:#f97316; background:#fff7ed; box-shadow:0 0 0 3px rgba(249,115,22,.12); }
  .payment-icon-wrap { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .payment-name { display:block; font-size:13px; font-weight:600; color:#374151; line-height:1.2; }
  .payment-option input[type="radio"]:checked + .payment-label .payment-name { color:#ea580c; }
  .payment-sub { display:block; font-size:11px; color:#9ca3af; margin-top:1px; }
  .payment-check { margin-left:auto; width:20px; height:20px; border-radius:9999px; border:2px solid #d1d5db; display:flex; align-items:center; justify-content:center; color:transparent; transition:all .2s; flex-shrink:0; }
  .payment-option input[type="radio"]:checked + .payment-label .payment-check { background:#f97316; border-color:#f97316; color:#fff; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── State ────────────────────────────────────────────────────────────────
  window.currentDeliveryFee = 0;
  window.currentDiscount    = 0;
  var currentVoucher = null;

  var voucherInput       = document.getElementById('voucher_code');
  var applyBtn           = document.getElementById('applyVoucherBtn');
  var voucherMsg         = document.getElementById('voucherMessage');
  var appliedVoucher     = document.getElementById('appliedVoucher');
  var appliedVoucherCode = document.getElementById('appliedVoucherCode');
  var removeVoucherBtn   = document.getElementById('removeVoucherBtn');
  var cityInput          = document.getElementById('City');
  var suggestionsDiv     = document.getElementById('city-suggestions');
  var cityFeePreview     = document.getElementById('city-fee-preview');

  // ── reload: single function that refreshes everything from server ─────────
  // cart_process.js exposes window.reloadOrderSummary() which calls
  // fetch_order_summary.php with the current city + discount.
  function reload() {
    if (typeof window.reloadOrderSummary === 'function') {
      window.reloadOrderSummary();
    }
  }

  // Expose so cart_process.js can call _checkAllStock after reload
  window._checkAllStock = checkAllStock;

  // Also hook updateDeliveryFee so cart_process.js detects we are on checkout
  // (it checks typeof window.updateDeliveryFee === 'function')
  window.updateDeliveryFee = reload;

  // ── City autocomplete ────────────────────────────────────────────────────
  var cityTimeout;
  if (cityInput) {
    cityInput.addEventListener('input', function () {
      clearTimeout(cityTimeout);
      var query = this.value.trim();
      if (query.length < 2) {
        if (suggestionsDiv) suggestionsDiv.classList.add('hidden');
        if (cityFeePreview) cityFeePreview.textContent = '';
        return;
      }
      cityTimeout = setTimeout(function () {
        fetch('./functions/city_autocomplete.php?q=' + encodeURIComponent(query))
          .then(function (r) { return r.json(); })
          .then(function (cities) {
            if (!suggestionsDiv) return;
            if (!cities.length) { suggestionsDiv.classList.add('hidden'); return; }
            suggestionsDiv.innerHTML = cities.map(function (c) {
              return '<div class="city-suggestion px-4 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-0"' +
                ' data-city="' + _esc(c.city) + '">' +
                '<div class="font-medium text-sm">' + _esc(c.city) + '</div>' +
                '<div class="text-xs text-gray-400">' + _esc(c.area_type) + ' · ₱' + parseFloat(c.base_fee).toFixed(2) + ' delivery' +
                (c.free_shipping_threshold ? ' · Free over ₱' + parseFloat(c.free_shipping_threshold).toLocaleString() : '') +
                '</div></div>';
            }).join('');
            suggestionsDiv.classList.remove('hidden');

            suggestionsDiv.querySelectorAll('.city-suggestion').forEach(function (el) {
              el.addEventListener('click', function () {
                cityInput.value = this.dataset.city;
                suggestionsDiv.classList.add('hidden');
                reload();
                if (currentVoucher) _doApplyVoucher();
              });
            });
          });
      }, 300);
    });

    cityInput.addEventListener('change', function () {
      if (suggestionsDiv) suggestionsDiv.classList.add('hidden');
      reload();
      if (currentVoucher) _doApplyVoucher();
    });
  }

  document.addEventListener('click', function (e) {
    if (suggestionsDiv && cityInput && !cityInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
      suggestionsDiv.classList.add('hidden');
    }
  });

  // ── Voucher ──────────────────────────────────────────────────────────────
  if (applyBtn) applyBtn.addEventListener('click', _doApplyVoucher);

  function _doApplyVoucher() {
    var code     = voucherInput ? voucherInput.value.trim().toUpperCase() : '';
    var subtotal = parseFloat(document.getElementById('subtotal_input').value || 0) || 0;
    if (!code) { _showVoucherMsg('Please enter a voucher code', 'error'); return; }
    if (applyBtn) { applyBtn.disabled = true; applyBtn.textContent = 'Applying…'; }

    fetch('./functions/validate_voucher.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'code=' + encodeURIComponent(code) +
            '&cart_total=' + subtotal +
            '&city=' + encodeURIComponent(cityInput ? cityInput.value.trim() : '')
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        currentVoucher         = data.voucher;
        window.currentDiscount = data.discount_amount;

        document.getElementById('discount_amount_input').value = data.discount_amount.toFixed(2);
        var dl = document.getElementById('discount-line-container');
        if (dl) dl.classList.remove('hidden');
        var da = document.getElementById('discount-amount');
        if (da) da.textContent = '-₱' + parseFloat(data.discount_amount).toFixed(2);

        // Mirror applied code in hidden input — ensures it submits even when input is readonly
        var mirrorInp = document.getElementById('applied_voucher_code_input');
        if (mirrorInp) mirrorInp.value = data.voucher.code;

        _showAppliedVoucher(data);
        _showVoucherMsg(data.message, 'success');
        reload(); // reload with discount applied
      } else {
        _showVoucherMsg(data.message, 'error');
      }
    })
    .catch(function () { _showVoucherMsg('An error occurred.', 'error'); })
    .finally(function () {
      if (applyBtn) { applyBtn.disabled = false; applyBtn.textContent = 'Apply'; }
    });
  }

  if (removeVoucherBtn) {
    removeVoucherBtn.addEventListener('click', function () {
      currentVoucher = null;
      window.currentDiscount = 0;
      if (appliedVoucher) appliedVoucher.classList.add('hidden');
      if (voucherInput) { voucherInput.value = ''; voucherInput.readOnly = false; voucherInput.style.opacity = ''; }
      document.getElementById('discount_amount_input').value = '0';
      var dl = document.getElementById('discount-line-container');
      if (dl) dl.classList.add('hidden');
      _showVoucherMsg('Voucher removed', 'info');
      reload();
    });
  }

  function _showAppliedVoucher(data) {
    if (!appliedVoucherCode) return;
    appliedVoucherCode.textContent = data.voucher.code + ' — ' +
      (data.voucher.discount_type === 'percentage'
        ? data.voucher.discount_value + '% off'
        : '₱' + parseFloat(data.voucher.discount_value).toFixed(2) + ' off');
    if (appliedVoucher) appliedVoucher.classList.remove('hidden');
    if (voucherInput) { voucherInput.readOnly = true; voucherInput.style.opacity = "0.6"; }
  }

  function _showVoucherMsg(msg, type) {
    var el = document.getElementById('voucherMessage');
    if (!el) return;
    var c = { success:'text-green-600', error:'text-red-600', info:'text-orange-600' };
    el.textContent = msg;
    el.className   = 'text-xs mt-2 ' + (c[type] || 'text-gray-600');
    el.classList.remove('hidden');
    if (type === 'success') setTimeout(function () { el.classList.add('hidden'); }, 5000);
  }

  // ── Stock checking ───────────────────────────────────────────────────────
  function checkItemStock(item) {
    var stock    = parseInt(item.dataset.stockQuantity) || Infinity;
    var qtyEl    = item.querySelector('.quantity');
    var qty      = parseFloat(qtyEl ? qtyEl.value : 0) || 0;
    var errEl    = item.querySelector('.stock-error');
    var unitType = item.dataset.unitType || 'piece';
    var unitDisp = unitType === 'piece' ? 'pcs' : unitType;
    var exceeded = isFinite(stock) && qty > stock;
    item.classList.toggle('cart-item-over-stock', exceeded);
    item.classList.toggle('bg-amber-50', exceeded);
    if (errEl) {
      errEl.textContent = exceeded ? 'Only ' + stock + ' ' + unitDisp + ' available — reduce to proceed' : '';
      errEl.classList.toggle('hidden', !exceeded);
    }
    return exceeded;
  }

  function checkAllStock() {
    var anyExceeded = false;
    var anyHardErr  = document.querySelectorAll('#cart-items-list .cart-item-error').length > 0;
    document.querySelectorAll('#cart-items-list .cart-item:not(.cart-item-error)').forEach(function (item) {
      if (checkItemStock(item)) anyExceeded = true;
    });
    var btn      = document.getElementById('submitBtn');
    var hintText = document.getElementById('stockHintText');
    if (!btn) return;
    var blocked  = anyHardErr || anyExceeded;
    if (blocked) {
      btn.disabled = true;
      btn.classList.remove('bg-orange-600','hover:bg-orange-500','active:scale-95','text-white');
      btn.classList.add('bg-gray-200','text-gray-400','cursor-not-allowed');
      if (anyExceeded && !anyHardErr) {
        btn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Adjust Quantities Above';
        if (hintText) hintText.classList.remove('hidden');
      }
    } else {
      btn.disabled = false;
      btn.classList.remove('bg-gray-200','text-gray-400','cursor-not-allowed');
      btn.classList.add('bg-orange-600','hover:bg-orange-500','active:scale-95','text-white');
      btn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg> Complete Order';
      if (hintText) hintText.classList.add('hidden');
    }
  }

  checkAllStock();
  document.addEventListener('input',  function (e) { if (e.target.classList.contains('quantity') && e.target.closest('.cart-item')) checkAllStock(); });
  document.addEventListener('change', function (e) { if (e.target.classList.contains('quantity') && e.target.closest('.cart-item')) setTimeout(checkAllStock, 200); });
  var cartList = document.getElementById('cart-items-list');
  if (cartList && window.MutationObserver) new MutationObserver(checkAllStock).observe(cartList, { childList: true, subtree: true });

  // ── Form submit validation ───────────────────────────────────────────────
  var checkoutForm = document.getElementById('checkoutForm');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function (e) {
      var anyExceeded = false;
      document.querySelectorAll('#cart-items-list .cart-item:not(.cart-item-error)').forEach(function (item) {
        if (checkItemStock(item)) anyExceeded = true;
      });
      if (anyExceeded) {
        e.preventDefault();
        showToast('Please reduce quantities to available stock before proceeding.', 'error');
        var first = document.querySelector('.cart-item-over-stock');
        if (first) first.scrollIntoView({ behavior:'smooth', block:'center' });
        return;
      }
      var fields = [
        { name:'recipient_first_name', errId:'first_name-error'   },
        { name:'recipient_last_name',  errId:'last_name-error'    },
        { name:'recipient_email',      errId:'email-error'        },
        { name:'recipient_phone',      errId:'phone_number-error' },
        { name:'recipient_address',    errId:'address-error'      },
        { name:'city',                 errId:'city-error'         },
        { name:'postal_code',          errId:'postal_code-error'  },
      ];
      var valid = true;
      fields.forEach(function (f) {
        var el  = document.querySelector('[name="' + f.name + '"]');
        var err = document.getElementById(f.errId);
        if (!el) return;
        var ok = el.value.trim() !== '';
        el.classList.toggle('error', !ok);
        if (err) err.classList.toggle('hidden', ok);
        if (!ok) valid = false;
      });
      var payErr = document.getElementById('payment_method-error');
      var paySel = document.querySelector('[name="payment_method"]:checked');
      if (!paySel) { valid = false; if (payErr) payErr.classList.remove('hidden'); }
      else if (payErr) payErr.classList.add('hidden');
      if (!valid) {
        e.preventDefault();
        showToast('Please fill in all required fields.', 'error');
        var firstErr = document.querySelector('.checkout-input.error');
        if (firstErr) firstErr.scrollIntoView({ behavior:'smooth', block:'center' });
      }
    });
  }

  // ── Helpers ──────────────────────────────────────────────────────────────
  function _esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Initial load ─────────────────────────────────────────────────────────
  setTimeout(function () {
    if (cityInput && cityInput.value.trim()) reload();
  }, 150);

});
</script>