<?php
/**
 * supadmin/functions/delivery_process.php
 */
session_start();
require '../../conn.php';
require_once 'activity_log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit;
}

['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function redirectWithMessage(string $location, string $msg, string $type = 'success'): void {
    header('Content-Type: text/html');
    $_SESSION['message'] = ['text' => $msg, 'type' => $type];
    header("Location: $location"); exit;
}

// ── GET DELIVERY DETAIL ───────────────────────────────────────────────────
if ($action === 'get_delivery' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $delivery_id = (int)($_GET['delivery_id'] ?? 0);
    if (!$delivery_id) { echo json_encode(['success'=>false,'message'=>'Missing delivery_id.']); exit; }

    $stmt = $conn->prepare("SELECT d.*, o.order_code, o.total_price, o.payment_method, o.recipient_first_name, o.recipient_last_name, o.recipient_address, o.recipient_phone, o.city, o.delivery_notes, COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name, r.vehicle_type, r.vehicle_plate_number, r.rider_phone, r.current_lat, r.current_lng FROM deliveries d JOIN orders o ON o.order_id=d.order_id LEFT JOIN riders r ON r.rider_id=d.rider_id LEFT JOIN accounts a ON a.account_id=r.account_id WHERE d.delivery_id=? LIMIT 1");
    $stmt->bind_param('i', $delivery_id); $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$delivery) { echo json_encode(['success'=>false,'message'=>'Delivery not found.']); exit; }

    $pStmt = $conn->prepare("SELECT dp.*, COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name FROM delivery_proofs dp LEFT JOIN riders r ON r.rider_id=dp.rider_id LEFT JOIN accounts a ON a.account_id=r.account_id WHERE dp.delivery_id=? ORDER BY dp.uploaded_at ASC");
    $pStmt->bind_param('i', $delivery_id); $pStmt->execute();
    $proofs = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC); $pStmt->close();

    $tStmt = $conn->prepare("SELECT tracking_id, tracking_status AS status, latitude, longitude, notes, timestamp FROM delivery_tracking WHERE delivery_id=? ORDER BY timestamp ASC");
    $tStmt->bind_param('i', $delivery_id); $tStmt->execute();
    $tracking = $tStmt->get_result()->fetch_all(MYSQLI_ASSOC); $tStmt->close();

    echo json_encode(['success'=>true,'delivery'=>$delivery,'proofs'=>$proofs,'tracking'=>$tracking]); exit;
}

// ── ADD FEE ───────────────────────────────────────────────────────────────
if ($action === 'add_fee') {
    $city      = trim($_POST['city'] ?? '');
    $area_type = trim($_POST['area_type'] ?? 'Metro Manila');
    $base_fee  = (float)($_POST['base_fee'] ?? 50);
    $threshold = trim($_POST['free_shipping_threshold'] ?? '') !== '' ? (float)$_POST['free_shipping_threshold'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($city)) redirectWithMessage('../deliveries.php', 'City name is required.', 'error');
    if ($base_fee < 0) redirectWithMessage('../deliveries.php', 'Base fee cannot be negative.', 'error');

    $ck = $conn->prepare("SELECT fee_id FROM delivery_fees WHERE city=? LIMIT 1");
    $ck->bind_param('s', $city); $ck->execute();
    if ($ck->get_result()->num_rows > 0) redirectWithMessage('../deliveries.php', "City '{$city}' already has a delivery fee.", 'error');
    $ck->close();

    $stmt = $conn->prepare("INSERT INTO delivery_fees (city,area_type,base_fee,free_shipping_threshold,is_active) VALUES (?,?,?,?,?)");
    $stmt->bind_param('ssddi', $city, $area_type, $base_fee, $threshold, $is_active);
    if ($stmt->execute()) {
        logActivity($conn, 'delivery_fee', $conn->insert_id, 'Delivery fee added', null, json_encode(['city'=>$city,'base_fee'=>$base_fee]), "Delivery fee added for {$city}: ₱{$base_fee}", $actorId, $actorType);
        redirectWithMessage('../deliveries.php', "Delivery fee for '{$city}' added!");
    }
    redirectWithMessage('../deliveries.php', 'Failed to add: '.$conn->error, 'error');
}

// ── EDIT FEE ──────────────────────────────────────────────────────────────
if ($action === 'edit_fee') {
    $fee_id    = (int)($_POST['fee_id'] ?? 0);
    $city      = trim($_POST['city'] ?? '');
    $area_type = trim($_POST['area_type'] ?? 'Metro Manila');
    $base_fee  = (float)($_POST['base_fee'] ?? 50);
    $threshold = trim($_POST['free_shipping_threshold'] ?? '') !== '' ? (float)$_POST['free_shipping_threshold'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$fee_id) redirectWithMessage('../deliveries.php', 'Invalid fee ID.', 'error');
    if (empty($city)) redirectWithMessage('../deliveries.php', 'City name is required.', 'error');

    $ck = $conn->prepare("SELECT fee_id FROM delivery_fees WHERE city=? AND fee_id!=? LIMIT 1");
    $ck->bind_param('si', $city, $fee_id); $ck->execute();
    if ($ck->get_result()->num_rows > 0) redirectWithMessage('../deliveries.php', "Another city '{$city}' already exists.", 'error');
    $ck->close();

    $stmt = $conn->prepare("UPDATE delivery_fees SET city=?,area_type=?,base_fee=?,free_shipping_threshold=?,is_active=? WHERE fee_id=?");
    $stmt->bind_param('ssddii', $city, $area_type, $base_fee, $threshold, $is_active, $fee_id);
    if ($stmt->execute()) {
        logActivity($conn, 'delivery_fee', $fee_id, 'Delivery fee updated', null, json_encode(['city'=>$city,'base_fee'=>$base_fee]), "Delivery fee for {$city} updated: ₱{$base_fee}", $actorId, $actorType);
        redirectWithMessage('../deliveries.php', "Delivery fee for '{$city}' updated!");
    }
    redirectWithMessage('../deliveries.php', 'Failed to update: '.$conn->error, 'error');
}

// ── DELETE FEE ────────────────────────────────────────────────────────────
if ($action === 'delete_fee') {
    $fee_id = (int)($_POST['fee_id'] ?? 0);
    if (!$fee_id) redirectWithMessage('../deliveries.php', 'Invalid fee ID.', 'error');

    $get = $conn->prepare("SELECT city FROM delivery_fees WHERE fee_id=?");
    $get->bind_param('i', $fee_id); $get->execute();
    $row = $get->get_result()->fetch_assoc();
    if (!$row) redirectWithMessage('../deliveries.php', 'Fee not found.', 'error');

    $stmt = $conn->prepare("DELETE FROM delivery_fees WHERE fee_id=?");
    $stmt->bind_param('i', $fee_id);
    if ($stmt->execute()) {
        logActivity($conn, 'delivery_fee', $fee_id, 'Delivery fee deleted', json_encode(['city'=>$row['city']]), null, "Delivery fee for '{$row['city']}' deleted.", $actorId, $actorType);
        redirectWithMessage('../deliveries.php', "Delivery fee for '{$row['city']}' deleted.");
    }
    redirectWithMessage('../deliveries.php', 'Delete failed: '.$conn->error, 'error');
}

// ── TOGGLE FEE ACTIVE (AJAX) ──────────────────────────────────────────────
if ($action === 'toggle_fee') {
    $fee_id    = (int)($_POST['fee_id']    ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 0);
    if (!$fee_id) { echo json_encode(['success'=>false,'message'=>'Invalid fee ID.']); exit; }

    $stmt = $conn->prepare("UPDATE delivery_fees SET is_active=? WHERE fee_id=?");
    $stmt->bind_param('ii', $is_active, $fee_id);
    if ($stmt->execute()) {
        $status = $is_active ? 'enabled' : 'disabled';
        logActivity($conn, 'delivery_fee', $fee_id, 'Delivery fee toggled', null, (string)$is_active, "Fee ID {$fee_id} {$status}.", $actorId, $actorType);
        echo json_encode(['success'=>true,'message'=>"Fee {$status}."]);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// ── BULK ADJUST FEE (AJAX) ────────────────────────────────────────────────
// Adds or subtracts a fixed ₱ amount from every city's base_fee.
// Floors at ₱0 — fees cannot go negative.
if ($action === 'bulk_adjust_fee') {
    $mode   = $_POST['mode']   ?? 'increase';  // 'increase' | 'decrease'
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['success'=>false,'message'=>'Amount must be greater than ₱0.']); exit;
    }
    if (!in_array($mode, ['increase','decrease'])) {
        echo json_encode(['success'=>false,'message'=>'Invalid mode.']); exit;
    }

    if ($mode === 'increase') {
        $stmt = $conn->prepare("UPDATE delivery_fees SET base_fee = base_fee + ?");
    } else {
        // GREATEST(0, ...) prevents negative fees
        $stmt = $conn->prepare("UPDATE delivery_fees SET base_fee = GREATEST(0, base_fee - ?)");
    }
    $stmt->bind_param('d', $amount);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $verb     = $mode === 'increase' ? 'increased' : 'decreased';
        logActivity($conn, 'delivery_fee', 0, "Bulk fee {$verb}",
            null,
            json_encode(['mode'=>$mode,'amount'=>$amount,'affected'=>$affected]),
            "All delivery fees {$verb} by ₱".number_format($amount,2).". {$affected} cities updated.",
            $actorId, $actorType
        );
        echo json_encode([
            'success' => true,
            'message' => "All fees {$verb} by ₱".number_format($amount,2)." · {$affected} cities updated.",
        ]);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// Fallback
echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);