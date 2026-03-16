<?php
/**
 * supadmin/functions/fetch_accounts.php
 * Returns account data as JSON for the edit modal.
 */
session_start();
include '../../conn.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$account_id = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
if ($account_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No account ID provided.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM accounts 
    WHERE account_id = ? AND is_deleted = 0
    LIMIT 1
");
$stmt->bind_param('i', $account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

if ($account) {
    echo json_encode(['success' => true, 'account' => $account]);
} else {
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
}