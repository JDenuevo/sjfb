<?php
session_start();
header('Content-Type: application/json');

// Error handling for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function sendError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    $cart = $_SESSION['cart'] ?? [];
    $response = [
        'status' => 'success',
        'cart_items' => '',
        'cart_total' => 0,
        'cart_count' => count($cart)
    ];

    function formatUnit($unitType) {
        switch ($unitType) {
            case 'piece': return 'pcs';
            case 'kg': return 'kg';
            case 'gram': return 'g';
            case 'liter': return 'L';
            default: return $unitType;
        }
    }

    if (!empty($cart)) {
        ob_start();
        foreach ($cart as $index => $item) {
            $unitType = $item['unit_type'] ?? 'piece';
            $minimumOrder = $item['minimum_order'] ?? 1;
            $orderIncrement = $item['order_increment'] ?? 1;
            $unitLabel = formatUnit($unitType);
            ?>
            <div class="cart-item flex items-start mb-4 pb-2 border-b border-gray-200" 
                 data-cart-index="<?= $index ?>"
                 data-product-id="<?= htmlspecialchars($item['product_id']) ?>" 
                 data-variant-id="<?= htmlspecialchars($item['variant_id']) ?>"
                 data-unit-type="<?= htmlspecialchars($unitType) ?>"
                 data-minimum-order="<?= $minimumOrder ?>"
                 data-order-increment="<?= $orderIncrement ?>">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                     class="w-24 h-24 p-2 object-cover rounded-3xl mr-6">
                <div class="flex-grow">
                    <h3 class="font-medium text-base mb-2">
                        <?= htmlspecialchars($item['product_name']) ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($item['variant_name']) ?></p>
                    <p class="text-xs text-gray-400 mb-2">Min: <?= $minimumOrder . ' ' . $unitLabel ?></p>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center border border-gray-300 rounded">
                                <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                                <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" 
                                       value="<?= htmlspecialchars($item['quantity']) ?>" readonly>
                                <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                            </div>
                            &nbsp;
                            <span class="text-xs text-gray-500"><?= $unitLabel ?></span>
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
} catch (Exception $e) {
    error_log("Fetch cart items error: " . $e->getMessage());
    sendError('An error occurred while loading cart items');
}
?>