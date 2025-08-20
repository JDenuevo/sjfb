<?php
session_start();
require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    
    // Mark order as paid for testing
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    $_SESSION['success'] = "Payment verified successfully for testing!";
    header("Location: order_success.php?order_id=" . $orderId);
    exit();
}

header("Location: index.php");
exit();
?>