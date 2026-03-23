<?php
/**
 * supadmin/functions/fetch_deliveries.php
 * GET ?delivery_id=123  OR  ?order_id=456
 */
session_start();

// Suppress PHP notices/warnings from leaking into JSON
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

// Catch fatal errors that would otherwise produce an empty response
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Headers already sent check
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'PHP fatal error: ' . $err['message'] . ' in ' . basename($err['file']) . ' line ' . $err['line'],
        ]);
    }
});

// ── Validate session ───────────────────────────────────────────────────────
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// ── Load DB ────────────────────────────────────────────────────────────────
$connFile = __DIR__ . '/../../conn.php';
if (!file_exists($connFile)) {
    echo json_encode(['success' => false, 'message' => 'conn.php not found at: ' . $connFile]);
    exit;
}
require_once $connFile;

if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed.']);
    exit;
}

// ── Resolve delivery_id ────────────────────────────────────────────────────
$delivery_id = (int)($_GET['delivery_id'] ?? 0);
$order_id    = (int)($_GET['order_id']    ?? 0);

if (!$delivery_id && $order_id) {
    $lk = $conn->prepare("SELECT delivery_id FROM deliveries WHERE order_id = ? ORDER BY assigned_at DESC LIMIT 1");
    if ($lk) {
        $lk->bind_param('i', $order_id);
        $lk->execute();
        $lkRow = $lk->get_result()->fetch_assoc();
        $lk->close();
        if ($lkRow) $delivery_id = (int)$lkRow['delivery_id'];
    }
}

if (!$delivery_id) {
    echo json_encode(['success' => false, 'message' => 'delivery_id is required.']);
    exit;
}

// ── 1. Core delivery + order + rider ──────────────────────────────────────
$stmt = $conn->prepare("
    SELECT 
        d.*,
        o.*,
        r.rider_phone,
        r.vehicle_type,
        r.vehicle_plate_number,
        r.image AS rider_image,
        r.current_lat AS rider_lat,
        r.current_lng AS rider_lng,
        r.is_available AS rider_available,

        COALESCE(r.rider_name, CONCAT(a.account_first_name, ' ', a.account_last_name)) AS rider_name

    FROM deliveries d
    JOIN orders o ON o.order_id = d.order_id
    LEFT JOIN riders r ON r.rider_id = d.rider_id
    LEFT JOIN accounts a ON a.account_id = r.account_id
    WHERE d.delivery_id = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed (delivery): ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$delivery) {
    echo json_encode(['success' => false, 'message' => 'No delivery found for ID ' . $delivery_id . '. MySQL error: ' . $conn->error]);
    exit;
}

// ── 2. Order items ─────────────────────────────────────────────────────────
$orderItems = [];
$iStmt = $conn->prepare("
    SELECT oi.order_item_id, oi.quantity, oi.price, oi.discount,
           p.product_name, pv.variant_name, pv.unit_type,
           pv.variant_price, pv.discount_price
    FROM order_items oi
    JOIN products         p  ON p.product_id  = oi.product_id
    JOIN product_variants pv ON pv.variant_id  = oi.variant_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
");
if ($iStmt) {
    $iStmt->bind_param('i', $delivery['order_id']);
    $iStmt->execute();
    $orderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $iStmt->close();
}

// ── 3. Payment ─────────────────────────────────────────────────────────────
$payment = null;
$pyStmt  = $conn->prepare("
    SELECT payment_id, payment_status, source_type, gross_amount AS amount,
           provider_id AS reference_number, paid_at, created_at
    FROM payments
    WHERE order_id = ?
    ORDER BY created_at DESC LIMIT 1
");
if ($pyStmt) {
    $pyStmt->bind_param('i', $delivery['order_id']);
    $pyStmt->execute();
    $payment = $pyStmt->get_result()->fetch_assoc() ?: null;
    $pyStmt->close();
}

// ── 4. Delivery proofs ─────────────────────────────────────────────────────
$proofs = [];
$pStmt  = $conn->prepare("
    SELECT dp.proof_id, dp.file_path, dp.caption, dp.uploaded_at,
           COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS uploaded_by
    FROM delivery_proofs dp
    LEFT JOIN riders   r ON r.rider_id   = dp.rider_id
    LEFT JOIN accounts a ON a.account_id = r.account_id
    WHERE dp.delivery_id = ?
    ORDER BY dp.uploaded_at ASC
");
if ($pStmt) {
    $pStmt->bind_param('i', $delivery_id);
    $pStmt->execute();
    $proofs = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pStmt->close();
}

// ── 5. GPS tracking ────────────────────────────────────────────────────────
$tracking = [];
$tStmt    = $conn->prepare("
    SELECT tracking_id, tracking_status AS status,
           latitude, longitude, notes, timestamp
    FROM delivery_tracking
    WHERE delivery_id = ?
    ORDER BY timestamp ASC
");
if ($tStmt) {
    $tStmt->bind_param('i', $delivery_id);
    $tStmt->execute();
    $tracking = $tStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $tStmt->close();
}

// ── Send response ──────────────────────────────────────────────────────────
echo json_encode([
    'success'     => true,
    'delivery'    => $delivery,
    'order_items' => $orderItems,
    'payment'     => $payment,
    'proofs'      => $proofs,
    'tracking'    => $tracking,
], JSON_UNESCAPED_UNICODE);