<?php
session_start();
header('Content-Type: application/json');

$cart = $_SESSION['cart'] ?? [];
$response = [
    'status' => 'success',
    'cart_items' => '',
    'cart_total' => 0,
    'cart_count' => count($cart)
];

if (!empty($cart)) {
    ob_start();
    foreach ($cart as $item) {
        ?>
        <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200" 
             data-product-id="<?= htmlspecialchars($item['product_id']) ?>" 
             data-variant-id="<?= htmlspecialchars($item['variant_id']) ?>">
            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                 class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
            <div class="flex-grow">
                <h3 class="font-medium text-base mb-2 flex justify-between">
                    <?= htmlspecialchars($item['product_name']) ?>
                </h3>
                <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($item['variant_name']) ?></p>
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600">-</button>
                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                               value="<?= (int)$item['quantity'] ?>" readonly>
                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600">+</button>
                    </div>
                    <span class="price ml-4 font-medium text-sm">
                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                    </span>
                </div>
            </div>
            <button type="button" class="remove text-red-500 hover:text-red-700 ml-4">
                <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <?php
    }
    $response['cart_items'] = ob_get_clean();
    $response['cart_total'] = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $cart));
} else {
    $response['cart_items'] = '<p class="text-center text-gray-500">Your cart is empty.</p>';
}

echo json_encode($response);
exit;