<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the raw input for debugging
$input = file_get_contents('php://input');
error_log("Raw input: " . $input);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get and decode JSON input
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (!isset($data['product_id'], $data['variant_id'], $data['quantity'])) {
    error_log("Missing fields. Received: " . print_r($data, true));
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$product_id = (int)$data['product_id'];
$variant_id = (int)$data['variant_id'];
$quantity = max(1, (int)$data['quantity']); // Ensure quantity is at least 1

if (!isset($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

$updated = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $product_id && $item['variant_id'] == $variant_id) {
        $item['quantity'] = $quantity;
        $updated = true;
        break;
    }
}

if ($updated) {
    $cart_count = count($_SESSION['cart']);
    $cart_total = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $_SESSION['cart']));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Quantity updated',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
}
exit;
?>