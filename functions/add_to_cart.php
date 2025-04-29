<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Validate and sanitize input
    $required_fields = ['product_id', 'variant_id', 'product_name', 'variant_name', 'price', 'image_url'];
    $data = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
            exit;
        }
        $data[$field] = $_POST[$field];
    }
    
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if item already exists in cart
    $item_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $data['product_id'] && $item['variant_id'] == $data['variant_id']) {
            $item['quantity'] += $quantity;
            $item_exists = true;
            break;
        }
    }
    
    // Add new item if not exists
    if (!$item_exists) {
        $_SESSION['cart'][] = [
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'],
            'product_name' => $data['product_name'],
            'variant_name' => $data['variant_name'],
            'price' => (float)$data['price'],
            'image_url' => $data['image_url'],
            'quantity' => $quantity
        ];
    }
    
    // Return success response with updated cart info
    $cart_count = count($_SESSION['cart']);
    $cart_total = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $_SESSION['cart']));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to cart',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total
    ]);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}