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

    if (isset($data['product_id']) && isset($data['variant_id']) && isset($data['quantity'])) {
        $product_id = intval($data['product_id']);
        $variant_id = intval($data['variant_id']);
        $quantity = intval($data['quantity']);

        if (isset($_SESSION['cart'])) {
            $updated = false;
            $new_price = 0;
            $cart_total = 0;

            // Find and update the item in the cart
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] === $product_id && $item['variant_id'] === $variant_id) {
                    $item['quantity'] = $quantity;
                    $new_price = $item['price'] * $quantity; // Calculate the new price for this item
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Calculate the total cart price
                $cart_total = array_sum(array_map(function($item) {
                    return $item['price'] * $item['quantity'];
                }, $_SESSION['cart']));

                // Return success response with updated price and cart total
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cart updated',
                    'new_price' => $new_price,
                    'cart_total' => $cart_total
                ]);
                exit;
            } else {
                // Return error response if the item was not found in the cart
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
                exit;
            }
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