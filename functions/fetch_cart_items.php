<?php
session_start();

$cart = $_SESSION['cart'] ?? [];

$response = [
    'cart_items' => '',
    'cart_total' => 0,
    'cart_count' => 0
];

if (!empty($cart)) {
    ob_start();
    foreach ($cart as $item) {
        ?>
        <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200">
            <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>" class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
            <div class="flex-grow">
                <h3 class="font-medium text-base mb-2 flex justify-between"><?= $item['product_name'] ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?= $item['variant_name'] ?></p>
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600" 
                                data-product-id="<?= $item['product_id'] ?>" 
                                data-variant-id="<?= $item['variant_id'] ?>">-</button>
                        <input type="text" class="new-quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                               value="<?= $item['quantity'] ?>" readonly 
                               data-product-id="<?= $item['product_id'] ?>" 
                               data-variant-id="<?= $item['variant_id'] ?>">
                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600" 
                                data-product-id="<?= $item['product_id'] ?>" 
                                data-variant-id="<?= $item['variant_id'] ?>">+</button>
                    </div>
                    <span class="price ml-4 font-medium text-sm">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
            </div>
            <button type="button" class="remove text-red-500 hover:text-red-700 ml-4" 
                    data-product-id="<?= $item['product_id'] ?>" 
                    data-variant-id="<?= $item['variant_id'] ?>">
                <svg class="w-9 h-9" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <?php
    }
    $response['cart_items'] = ob_get_clean();
    $response['cart_total'] = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));
    $response['cart_count'] = count($cart);
} else {
    $response['cart_items'] = '<p class="text-center text-gray-500">Your cart is empty.</p>';
}

header('Content-Type: application/json');
echo json_encode($response);
?>