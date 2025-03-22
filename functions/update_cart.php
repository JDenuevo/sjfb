<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($_SESSION['cart']) && is_array($data)) {
        foreach ($data as $update) {
            $productId = intval($update['product_id']);
            $variantId = intval($update['variant_id']);
            $quantity = intval($update['quantity']);

            // Find and update the item in the cart
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $productId && $item['variant_id'] === $variantId) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Cart updated'
        ]);
        exit;
    } else {
        // Return error response if cart is empty or data is invalid
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }
} else {
    // Return error response if request method is not POST
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
?>