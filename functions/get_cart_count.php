<?php
session_start();

$cart_count = 0;
$cart_total = 0;

if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'cart_count' => $cart_count,
    'cart_total' => $cart_total
]);
exit;
?>
