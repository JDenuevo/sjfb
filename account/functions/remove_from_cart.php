<?php
// Capture any accidental output (warnings, notices) so they don't corrupt JSON
ob_start();

session_start();
header('Content-Type: application/json');

// Suppress display errors — they must never appear in JSON responses
error_reporting(0);
ini_set('display_errors', 0);

// Discard any buffered output (warnings etc.) before we send JSON
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Accept both JSON body (cart_process.js) and FormData (fallback)
$product_id = 0;
$variant_id = 0;

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['product_id'], $input['variant_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }
    $product_id = (int)$input['product_id'];
    $variant_id = (int)$input['variant_id'];
} else {
    // FormData / form-encoded fallback
    if (!isset($_POST['product_id'], $_POST['variant_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }
    $product_id = (int)$_POST['product_id'];
    $variant_id = (int)$_POST['variant_id'];
}

if ($product_id <= 0 || $variant_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product or variant ID']);
    exit;
}

if (empty($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

$initial_count = count($_SESSION['cart']);

$_SESSION['cart'] = array_values(array_filter(
    $_SESSION['cart'],
    function ($item) use ($product_id, $variant_id) {
        return !(
            (int)$item['product_id'] === $product_id &&
            (int)$item['variant_id'] === $variant_id
        );
    }
));

if (count($_SESSION['cart']) < $initial_count) {
    $cart_total = array_sum(array_map(function ($item) {
        return (float)$item['price'] * (float)$item['quantity'];
    }, $_SESSION['cart']));

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Item removed',
        'cart_count' => count($_SESSION['cart']),
        'cart_total' => $cart_total,
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
}
exit;
?>