<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id'], $input['variant_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$product_id = (int)$input['product_id'];
$variant_id = (int)$input['variant_id'];

if (!isset($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

$initial_count = count($_SESSION['cart']);
$_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id, $variant_id) {
    return !($item['product_id'] == $product_id && $item['variant_id'] == $variant_id);
});

if (count($_SESSION['cart']) < $initial_count) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed',
        'cart_count' => count($_SESSION['cart']),
        'cart_total' => array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $_SESSION['cart']))
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
}
exit;
?>