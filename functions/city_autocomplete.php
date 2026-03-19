<?php
session_start();
header('Content-Type: application/json');
require_once '../conn.php';

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT city, area_type, base_fee
    FROM delivery_fees 
    WHERE is_active = 1 AND city LIKE ?
    ORDER BY 
        CASE 
            WHEN city = ? THEN 1
            WHEN city LIKE ? THEN 2
            ELSE 3
        END,
        city
    LIMIT 10
");
$search = "%$query%";
$stmt->bind_param("sss", $search, $query, $search);
$stmt->execute();
$result = $stmt->get_result();

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

echo json_encode($cities);