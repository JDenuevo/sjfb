<?php
/**
 * supadmin/functions/fetch_accounts.php
 * Returns account data (+ optional groups) as JSON for the edit/group modals.
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

// Fetch account — uses renamed columns (account_email, account_phone, etc.)
$stmt = $conn->prepare("
    SELECT account_id, username, role,
           account_first_name, account_last_name,
           account_email, account_phone,
           account_address, city, postal_code,
           created_at
    FROM accounts
    WHERE account_id = ? AND is_deleted = 0
    LIMIT 1
");
$stmt->bind_param('i', $account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    echo json_encode(['success' => false, 'message' => 'Account not found.']);
    exit;
}

$response = ['success' => true, 'account' => $account];

// Also return groups if requested (for the group management modal)
if (!empty($_GET['include_groups'])) {
    $gStmt = $conn->prepare("
        SELECT ag.account_group_id, ag.group_id, ag.expires_at,
               cg.group_name, cg.group_code, cg.discount_percentage, cg.description
        FROM account_groups ag
        JOIN customer_groups cg ON cg.group_id = ag.group_id
        WHERE ag.account_id = ?
          AND cg.is_active = 1
          AND (ag.expires_at IS NULL OR ag.expires_at > NOW())
        ORDER BY cg.priority DESC
    ");
    $gStmt->bind_param('i', $account_id);
    $gStmt->execute();
    $response['account_groups'] = $gStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $gStmt->close();
}

echo json_encode($response);