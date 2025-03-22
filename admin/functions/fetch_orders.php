<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinasadmin"]) || $_SESSION["loggedinasadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Query to get order details
    $query = "SELECT o.order_id, p.product_name, oi.price AS product_price, oi.discount AS discount_price
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              JOIN products p ON oi.product_id = p.product_id
              WHERE o.order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $orderDetails = $result->fetch_assoc();
        echo json_encode(['success' => true, 'order' => $orderDetails]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>