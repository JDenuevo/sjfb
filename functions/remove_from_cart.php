<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Log the raw POST data
    $postData = file_get_contents('php://input');
    error_log('Raw POST data: ' . $postData);

    // Decode the JSON data
    $data = json_decode($postData, true);

    // Debugging: Log the decoded data
    error_log('Decoded POST data: ' . print_r($data, true));

    if (isset($data['product_id']) && isset($data['variant_id'])) {
        $product_id = intval($data['product_id']);
        $variant_id = intval($data['variant_id']);

        if (isset($_SESSION['cart'])) {
            // Find and remove the item from the cart
            foreach ($_SESSION['cart'] as $index => $item) {
                if ($item['product_id'] === $product_id && $item['variant_id'] === $variant_id) {
                    unset($_SESSION['cart'][$index]);
                    break;
                }
            }

            // Reindex the array to prevent gaps
            $_SESSION['cart'] = array_values($_SESSION['cart']);

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Product removed from cart']);
            exit;
        } else {
            // Return error response if cart is empty
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
            exit;
        }
    } else {
        // Return error response if required data is missing
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