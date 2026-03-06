<?php
session_start();
include '../../conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
  header("HTTP/1.1 403 Forbidden");
  exit;
}

// Get the payment ID from the request
$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($paymentId <= 0) {
  header("HTTP/1.1 400 Bad Request");
  echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
  exit;
}

// Fetch payment details with related order information
$query = "SELECT
            p.*,
            o.order_code,
            o.first_name,
            o.last_name,
            o.email AS order_email,
            o.total_price,
            o.payment_method
          FROM payments p
          LEFT JOIN orders o ON p.order_id = o.order_id
          WHERE p.payment_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
  header("HTTP/1.1 500 Internal Server Error");
  echo json_encode(['success' => false, 'message' => 'Database query failed']);
  exit;
}

if ($result->num_rows === 0) {
  header("HTTP/1.1 404 Not Found");
  echo json_encode(['success' => false, 'message' => 'Payment not found']);
  exit;
}

$payment = $result->fetch_assoc();

// Convert numeric values to proper format
$payment['gross_amount'] = (float)$payment['gross_amount'];
$payment['net_amount'] = (float)$payment['net_amount'];
$payment['refunded_amount'] = (float)$payment['refunded_amount'];
$payment['total_price'] = (float)$payment['total_price'];

// Return the payment details as JSON
header('Content-Type: application/json');
echo json_encode([
  'success' => true,
  'payment' => $payment
]);

// Close the statement and connection
$stmt->close();
$conn->close();
?>