<!-- update_cart_quantity.php -->

<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['cart_index'], $data['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$cartIndex = (int)$data['cart_index'];
$newQuantity = (float)$data['quantity'];

if (!isset($_SESSION['cart'][$cartIndex])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart item not found']);
    exit;
}

$item = $_SESSION['cart'][$cartIndex];
$minimumOrder = $item['minimum_order'] ?? 1;

if ($newQuantity < $minimumOrder) {
    echo json_encode([
        'status' => 'error', 
        'message' => "Minimum order is {$minimumOrder} {$item['unit_type']}"
    ]);
    exit;
}

$_SESSION['cart'][$cartIndex]['quantity'] = $newQuantity;

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
exit;
?>