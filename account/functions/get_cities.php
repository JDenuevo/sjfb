<?php
// functions/get_cities.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once '../conn.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $conn->prepare("
    SELECT city, area_type, base_fee, free_shipping_threshold
    FROM delivery_fees
    WHERE is_active = 1
    ORDER BY city ASC
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$cities = [];

while ($row = $result->fetch_assoc()) {
    $cities[] = [
        'city'                   => $row['city'],
        'area_type'              => $row['area_type'],
        'base_fee'               => (float)$row['base_fee'],
        'free_shipping_threshold'=> $row['free_shipping_threshold'] !== null
                                    ? (float)$row['free_shipping_threshold']
                                    : null,
    ];
}

$stmt->close();
echo json_encode($cities);