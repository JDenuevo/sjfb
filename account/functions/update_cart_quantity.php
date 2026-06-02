<?php
// functions/update_cart_quantity.php
// Called by cart_process.js _syncQtyToServer()
// Accepts: { cart_index, quantity } as JSON body
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!isset($data['cart_index'], $data['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$cartIndex   = (int)$data['cart_index'];
// Use floatval — FILTER_VALIDATE_INT rejects 1.5 kg, 0.5 kg etc.
$newQuantity = (float)$data['quantity'];

if (!isset($_SESSION['cart'][$cartIndex])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart item not found']);
    exit;
}

$item        = $_SESSION['cart'][$cartIndex];
$minimumOrder = (float)($item['minimum_order'] ?? 1);
$unitType     = $item['unit_type'] ?? 'piece';

if ($newQuantity < $minimumOrder) {
    echo json_encode([
        'status'  => 'error',
        'message' => "Minimum order is {$minimumOrder} {$unitType}",
    ]);
    exit;
}

$_SESSION['cart'][$cartIndex]['quantity'] = $newQuantity;

$cart_total = array_sum(array_map(function ($i) {
    return (float)$i['price'] * (float)$i['quantity'];
}, $_SESSION['cart']));

echo json_encode([
    'status'     => 'success',
    'message'    => 'Quantity updated',
    'cart_count' => count($_SESSION['cart']),
    'cart_total' => $cart_total,
]);
exit;