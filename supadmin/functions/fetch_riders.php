<?php
/**
 * supadmin/functions/fetch_riders.php
 * Returns rider data as JSON for the edit modal.
 * Returns new columns: image, full_name, variant_color, organization, contact_number
 */
session_start();
require_once __DIR__ . '/../../conn.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$rider_id = isset($_GET['rider_id']) ? (int)$_GET['rider_id'] : 0;
if ($rider_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No rider ID provided.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT r.rider_id, r.account_id, r.image, r.full_name,
           r.vehicle_type, r.vehicle_plate_number, r.variant_color,
           r.contact_number, r.organization, r.is_available,
           a.first_name, a.last_name, a.email, a.phone_number
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.rider_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$stmt->bind_param('i', $rider_id);
$stmt->execute();
$rider = $stmt->get_result()->fetch_assoc();

if ($rider) {
    echo json_encode(['success' => true, 'rider' => $rider]);
} else {
    echo json_encode(['success' => false, 'message' => 'Rider not found.']);
}