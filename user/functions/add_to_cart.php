<?php
session_start();
header('Content-Type: application/json');

// Simple response function
function jsonResponse($status, $message, $data = []) {
    $response = ['status' => $status, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if add_to_cart is set
    if (!isset($_POST['add_to_cart'])) {
        throw new Exception('Invalid request');
    }

    // Basic validation - check required fields
    $required = ['product_id', 'variant_id', 'product_name', 'variant_name', 'price', 'image_url', 'unit_type', 'minimum_order', 'order_increment'];
    
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing field: $field");
        }
    }

    // Get the data
    $product_id = $_POST['product_id'];
    $variant_id = $_POST['variant_id'];
    $product_name = $_POST['product_name'];
    $variant_name = $_POST['variant_name'];
    $price = floatval($_POST['price']);
    $image_url = $_POST['image_url'];
    $unit_type = $_POST['unit_type'];
    $minimum_order = floatval($_POST['minimum_order']);
    $order_increment = floatval($_POST['order_increment']);
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : $minimum_order;

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Create cart item
    $cart_item = [
        'product_id' => $product_id,
        'variant_id' => $variant_id,
        'product_name' => $product_name,
        'variant_name' => $variant_name,
        'price' => $price,
        'image_url' => $image_url,
        'quantity' => $quantity,
        'unit_type' => $unit_type,
        'minimum_order' => $minimum_order,
        'order_increment' => $order_increment
    ];

    // Check if item already exists in cart
    $item_index = -1;
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['product_id'] == $product_id && $item['variant_id'] == $variant_id) {
            $item_index = $index;
            break;
        }
    }

    if ($item_index >= 0) {
        // Update existing item
        $_SESSION['cart'][$item_index]['quantity'] += $quantity;
    } else {
        // Add new item
        $_SESSION['cart'][] = $cart_item;
    }

    // Calculate cart totals
    $cart_count = count($_SESSION['cart']);
    $cart_total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }

    // Return success
    jsonResponse('success', 'Product added to cart', [
        'cart_count' => $cart_count,
        'cart_total' => $cart_total
    ]);

} catch (Exception $e) {

    // Return error message
    jsonResponse('error', $e->getMessage());
}
?>