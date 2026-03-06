<?php
session_start();
include '../../conn.php';

// Check if the customer is logged in
if (!isset($_SESSION['loggedinasuser']) || $_SESSION['loggedinasuser'] !== true || !isset($_SESSION['account_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$account_id = $_SESSION['account_id'];

// Get the order ID from the request
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// First, verify that this order belongs to the logged-in user
$verify_query = "SELECT order_id FROM orders WHERE order_id = ? AND account_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $orderId, $account_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not authorized to view this order']);
    exit;
}

// Fetch order items - only for the verified user's order
$query = "SELECT 
            oi.product_id, 
            p.product_name, 
            oi.variant_id, 
            pv.variant_name, 
            oi.quantity, 
            oi.price, 
            oi.discount
          FROM order_items oi
          LEFT JOIN products p ON oi.product_id = p.product_id
          LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
          INNER JOIN orders o ON oi.order_id = o.order_id
          WHERE oi.order_id = ? AND o.account_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $orderId, $account_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$orderItems = [];
while ($row = $result->fetch_assoc()) {
    // Ensure price is a number and handle null variant names
    $row['price'] = (float)$row['price'];
    $row['variant_name'] = $row['variant_name'] ? $row['variant_name'] : 'Standard';
    $orderItems[] = $row;
}

// Return the order items as JSON
header('Content-Type: application/json');
echo json_encode($orderItems);
?>