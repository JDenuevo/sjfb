<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

    $minimumOrder   = (float)$data['minimum_order'];
    $orderIncrement = (float)$data['order_increment'];

    // Use minimum_order as fallback if quantity is missing or empty
    $rawQty   = isset($_POST['quantity']) ? trim($_POST['quantity']) : '';
    $quantity = ($rawQty !== '' && is_numeric($rawQty)) ? (float)$rawQty : $minimumOrder;

    // Validate minimum order quantity
    if ($quantity < $minimumOrder) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Minimum order is {$minimumOrder} {$data['unit_type']}"
        ]);
        exit;
    }

    // Validate quantity is in proper increments
    $diff = $quantity - $minimumOrder;
    if ($orderIncrement > 0 && abs(fmod($diff, $orderIncrement)) > 0.001) {
        echo json_encode([
            'status'  => 'error',
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

    // For items sold by piece, check stock quantity
    if ($data['unit_type'] === 'piece' && $variant['stock_quantity'] > 0 && $quantity > $variant['stock_quantity']) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Only {$variant['stock_quantity']} pieces available"
        ]);
        exit;
    }

    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if item already exists in cart
    $item_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $data['product_id'] && $item['variant_id'] == $data['variant_id']) {
            $new_quantity = $item['quantity'] + $quantity;

            // Check stock for pieces
            if ($data['unit_type'] === 'piece' && $variant['stock_quantity'] > 0 && $new_quantity > $variant['stock_quantity']) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => "Cannot add more. Only {$variant['stock_quantity']} pieces available"
                ]);
                exit;
            }

            $item['quantity'] = $new_quantity;
            $item_exists      = true;
            break;
        }
    }
    unset($item);

    // Add new item
    if (!$item_exists) {
        $_SESSION['cart'][] = [
            'product_id'     => $data['product_id'],
            'variant_id'     => $data['variant_id'],
            'product_name'   => $data['product_name'],
            'variant_name'   => $data['variant_name'],
            'price'          => (float)$data['price'],
            'image_url'      => $data['image_url'],
            'quantity'       => $quantity,
            'unit_type'      => $data['unit_type'],
            'minimum_order'  => $minimumOrder,
            'order_increment'=> $orderIncrement
        ];
    }

    $cart_count = count($_SESSION['cart']);
    $cart_total = array_sum(array_map(function ($i) {
        return $i['price'] * $i['quantity'];
    }, $_SESSION['cart']));

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Product added to cart',
        'cart_count' => $cart_count,
        'cart_total' => $cart_total
    ]);
    exit;

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}
?>