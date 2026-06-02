<?php
session_start();
require_once __DIR__ . '../../conn.php';

header('Content-Type: application/json');

$orderCode = trim($_GET['order_code'] ?? '');

if (empty($orderCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_code']);
    exit();
}

try {
    // Pull order status + latest payment status in one query
    $stmt = $conn->prepare("
        SELECT
            o.order_status,
            p.payment_status
        FROM orders o
        LEFT JOIN payments p
               ON p.order_id = o.order_id
        WHERE o.order_code = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    echo json_encode([
        'order_status'   => $row['order_status']   ?? 'Pending',
        'payment_status' => $row['payment_status']  ?? 'Pending',
    ]);

} catch (Exception $e) {
    error_log("check_order_status.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}