<?php
// functions/update_cart.php

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['variant_id']) && isset($_POST['quantity'])) {
    $product_id = intval($_POST['product_id']);
    $variant_id = intval($_POST['variant_id']);
    $quantity = intval($_POST['quantity']);

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
    // Return error response if request is invalid
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}
?>