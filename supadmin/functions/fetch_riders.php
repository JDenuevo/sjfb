<?php
require_once __DIR__ . '/../../conn.php';

header('Content-Type: application/json');

if (isset($_GET['rider_id'])) {
    $rider_id = (int)$_GET['rider_id'];
    
    $query = "SELECT r.*, a.first_name, a.last_name, a.email, a.phone_number 
              FROM riders r 
              JOIN accounts a ON r.account_id = a.account_id 
              WHERE r.rider_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $rider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $rider = $result->fetch_assoc();
        echo json_encode(['success' => true, 'rider' => $rider]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rider not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No rider ID provided']);
}

$conn->close();
?>