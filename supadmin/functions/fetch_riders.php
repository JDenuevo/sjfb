<?php
/**
 * supadmin/functions/fetch_riders.php
 * Returns rider data as JSON for the edit modal.
 * Uses renamed columns: rider_name (was full_name), rider_phone (was contact_number)
 *                       account_first_name, account_last_name, account_email, account_phone
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

// Uses renamed columns:
//   riders:   rider_name (was full_name), rider_phone (was contact_number)
//   accounts: account_first_name, account_last_name, account_email, account_phone
$stmt = $conn->prepare("
    SELECT r.rider_id, r.account_id, r.image,
           r.rider_name, r.vehicle_type, r.vehicle_plate_number,
           r.variant_color, r.rider_phone, r.organization, r.is_available,
           a.account_first_name, a.account_last_name,
           a.account_email, a.account_phone
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.rider_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$stmt->bind_param('i', $rider_id);
$stmt->execute();
$rider = $stmt->get_result()->fetch_assoc();

if ($rider) {
    // Provide legacy-named aliases so existing JS modal code still works without changes
    $rider['full_name']      = $rider['rider_name'];
    $rider['contact_number'] = $rider['rider_phone'];
    $rider['first_name']     = $rider['account_first_name'];
    $rider['last_name']      = $rider['account_last_name'];
    $rider['email']          = $rider['account_email'];
    $rider['phone_number']   = $rider['account_phone'];

    echo json_encode(['success' => true, 'rider' => $rider]);
} else {
    echo json_encode(['success' => false, 'message' => 'Rider not found.']);
}
?>