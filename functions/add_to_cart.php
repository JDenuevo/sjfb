<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
 
    // Validate required fields
    $required_fields = ['product_id', 'variant_id', 'product_name', 'variant_name', 'price', 'image_url', 'unit_type', 'minimum_order', 'order_increment'];
    $data = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
            exit;
        }
        $data[$field] = $_POST[$field];
    }
    
    $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : (float)$data['minimum_order'];
    $minimumOrder = (float)$data['minimum_order'];
    $orderIncrement = (float)$data['order_increment'];
    
    // Validate minimum order quantity
    if ($quantity < $minimumOrder) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Minimum order is {$minimumOrder} {$data['unit_type']}"
        ]);
        exit;
    }
    
    // Validate quantity is in proper increments
    $diff = $quantity - $minimumOrder;
    if ($orderIncrement > 0 && abs(fmod($diff, $orderIncrement)) > 0.001) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Quantity must be in increments of {$orderIncrement} {$data['unit_type']}"
        ]);
        exit;
    }
    
    // Verify variant exists and check stock
    $stmt = $conn->prepare("SELECT stock_quantity, stock_status FROM product_variants WHERE variant_id = ?");
    $stmt->bind_param("i", $data['variant_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product variant not found']);
        exit;
    }
    
    $variant = $result->fetch_assoc();
    
    if ($variant['stock_status'] === 'Out of Stock') {
        echo json_encode(['status' => 'error', 'message' => 'Product is out of stock']);
        exit;
    }
    
    // For items sold by piece, check stock
    if ($data['unit_type'] === 'piece' && $quantity > $variant['stock_quantity']) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Only {$variant['stock_quantity']} pieces available"
        ]);
        exit;
    }
    
    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if item exists in cart
    $item_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $data['product_id'] && $item['variant_id'] == $data['variant_id']) {
            $new_quantity = $item['quantity'] + $quantity;
            
            // Check stock for pieces
            if ($data['unit_type'] === 'piece' && $new_quantity > $variant['stock_quantity']) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => "Cannot add more. Only {$variant['stock_quantity']} pieces available"
                ]);
                exit;
            }
            
            $item['quantity'] = $new_quantity;
            $item_exists = true;
            
            // Debug: log updated item
            error_log("Updated existing cart item - Unit type: " . $item['unit_type']);
            break;
        }
    }
    
    // Add new item
    if (!$item_exists) {
        $new_item = [
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'],
            'product_name' => $data['product_name'],
            'variant_name' => $data['variant_name'],
            'price' => (float)$data['price'],
            'image_url' => $data['image_url'],
            'quantity' => $quantity,
            'unit_type' => $data['unit_type'],
            'minimum_order' => $minimumOrder,
            'order_increment' => $orderIncrement
        ];
        
        $_SESSION['cart'][] = $new_item;
        
        // Debug: log new item
        error_log("Added new cart item: " . print_r($new_item, true));
    }
    
    // Debug: log entire cart
    error_log("Final cart contents: " . print_r($_SESSION['cart'], true));
    
    // Return success
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
?>