<?php
session_start();
include '../../conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['loggedinasadmin']) || $_SESSION['loggedinasadmin'] !== true || !isset($_SESSION['account_id'])) {
  header("HTTP/1.1 403 Forbidden");
  exit;
}

// Get the order ID from the request
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
  header("HTTP/1.1 400 Bad Request");
  exit;
}

// Fetch order items
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
          WHERE oi.order_id = $orderId";
$result = mysqli_query($conn, $query);

if (!$result) {
  header("HTTP/1.1 500 Internal Server Error");
  exit;
}

$orderItems = [];
while ($row = mysqli_fetch_assoc($result)) {
  // Ensure price is a number
  $row['price'] = (float)$row['price']; // Convert to float
  $orderItems[] = $row;
}

// Return the order items as JSON
header('Content-Type: application/json');
echo json_encode($orderItems);
?>