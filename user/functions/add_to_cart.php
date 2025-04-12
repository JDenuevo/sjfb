<?php
// functions/add_to_cart.php

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request method and data for debugging
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Validate and sanitize input
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
    $product_name = isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : null;
    $variant_name = isset($_POST['variant_name']) ? htmlspecialchars($_POST['variant_name']) : null;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $image_url = isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Log received data for debugging
    error_log("Received data: " . print_r($_POST, true));

    if ($product_id && $variant_id && $product_name && $variant_name && $price && $image_url && $quantity) {
        // Add to cart logic here
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id && $item['variant_id'] == $variant_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $cart_item = [
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'product_name' => $product_name,
                'variant_name' => $variant_name,
                'price' => $price,
                'image_url' => $image_url,
                'quantity' => $quantity
            ];
            $_SESSION['cart'][] = $cart_item;
        }

        // Log session state for debugging
        error_log("Session cart: " . print_r($_SESSION['cart'], true));

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
        exit;
    } else {
        // Return error response if input is invalid
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
        exit;
    }
} else {
    // Return error response if request method is not POST
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
?>