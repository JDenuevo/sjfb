<?php
/**
 * supadmin/functions/order_process.php
 * Column renames applied:
 *   orders: recipient_first_name/last_name
 *   deliveries: delivery_status (was status)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../conn.php';
require_once __DIR__ . '/order_helper.php';

if (empty($_SESSION['account_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
    exit;
}

if (!empty($_SESSION['loggedinassupadmin']) && $_SESSION['loggedinassupadmin'] === true) {
    $actor_role = $_SESSION['supadmin_role'] ?? 'super_admin';
    $actor_id   = (int)$_SESSION['account_id'];
} elseif (!empty($_SESSION['loggedinasrider']) && $_SESSION['loggedinasrider'] === true) {
    $actor_role = 'rider';
    $actor_id   = (int)$_SESSION['account_id'];
} elseif (!empty($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'admin', 'rider'], true)) {
    $actor_role = $_SESSION['role'];
    $actor_id   = (int)$_SESSION['account_id'];
} else {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function isAdmin(string $role): bool { return in_array($role, ['super_admin', 'admin'], true); }
function isRider(string $role): bool { return $role === 'rider'; }
function respond(array $data): void  { echo json_encode($data); exit; }

switch ($action) {

    case 'approve_order':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        respond(approveOrder($order_id, $actor_id, $actor_role, $conn));

    case 'assign_rider':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $rider_id = (int)($_POST['rider_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        if (!$order_id || !$rider_id) respond(['ok' => false, 'msg' => 'Missing order_id or rider_id.']);
        respond(assignRegisteredRider($order_id, $rider_id, $actor_id, $actor_role, $notes, $conn));

    case 'assign_third_party':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id         = (int)($_POST['order_id'] ?? 0);
        $third_party_name = trim($_POST['third_party_name'] ?? '');
        $delivery_link    = trim($_POST['delivery_link'] ?? '');
        $notes            = trim($_POST['notes'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (!$third_party_name) respond(['ok' => false, 'msg' => '3rd-party provider name is required.']);
        respond(assignThirdPartyDelivery($order_id, $third_party_name, $delivery_link, $actor_id, $actor_role, $notes, $conn));

    case 'send_out_for_delivery':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? 'Dispatched for delivery.');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $r = _updateOrderStatusRaw($order_id, 'Processing', 'OutForDelivery', $actor_id, $actor_role, $notes, $conn);
        if ($r['ok']) {
            // Uses renamed: delivery_status (was status)
            $conn->query("UPDATE deliveries SET delivery_status='accepted', accepted_at=NOW() WHERE order_id={$order_id} AND delivery_status='pending_acceptance'");
            $order = getOrderRow($order_id, $conn);
            _broadcastNotif($order_id, "Order #{$order['order_code']} is now Out for Delivery.", $order['account_id'], $order['assigned_rider_id'], $conn);
        }
        respond($r);

    case 'cancel_order':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (!$reason)   respond(['ok' => false, 'msg' => 'Cancellation reason is required.']);
        respond(cancelOrder($order_id, $actor_id, $actor_role, $reason, $conn));

    case 'mark_delivered':
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (isRider($actor_role)) {
            $check = $conn->prepare("SELECT o.order_id FROM orders o JOIN riders r ON r.rider_id=o.assigned_rider_id WHERE o.order_id=? AND r.account_id=?");
            $check->bind_param('ii', $order_id, $actor_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) respond(['ok' => false, 'msg' => 'This order is not assigned to you.']);
        }
        respond(markDelivered($order_id, $actor_id, $actor_role, $notes, $conn));

    case 'rider_accept':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        if (!$delivery_id) respond(['ok' => false, 'msg' => 'Missing delivery_id.']);
        respond(riderAcceptDelivery($delivery_id, $actor_id, $conn));

    case 'rider_pickup':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        if (!$delivery_id) respond(['ok' => false, 'msg' => 'Missing delivery_id.']);
        respond(riderPickUp($delivery_id, $actor_id, $conn));

    case 'push_location':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $lat         = (float)($_POST['lat'] ?? 0);
        $lng         = (float)($_POST['lng'] ?? 0);
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        $status      = trim($_POST['status'] ?? 'en_route');
        $notes       = trim($_POST['notes'] ?? '');
        if (!$lat || !$lng || !$delivery_id) respond(['ok' => false, 'msg' => 'Missing lat/lng/delivery_id.']);
        $rq = $conn->prepare("SELECT rider_id FROM riders WHERE account_id=? LIMIT 1");
        $rq->bind_param('i', $actor_id);
        $rq->execute();
        $rrow = $rq->get_result()->fetch_assoc();
        if (!$rrow) respond(['ok' => false, 'msg' => 'Rider profile not found.']);
        respond(pushRiderLocation((int)$rrow['rider_id'], $lat, $lng, $delivery_id, $status, $notes, $conn));

    case 'upload_proof':
        $order_id = (int)($_POST['order_id'] ?? 0);
        $caption  = trim($_POST['caption'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (empty($_FILES['proof_file']['tmp_name'])) respond(['ok' => false, 'msg' => 'No file received.']);
        $file = $_FILES['proof_file'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) respond(['ok' => false, 'msg' => 'Only JPEG, PNG, WEBP allowed.']);
        if ($file['size'] > 8 * 1024 * 1024) respond(['ok' => false, 'msg' => 'File too large. Max 8MB.']);
        $rider_id = 0;
        if (isRider($actor_role)) {
            $chk = $conn->prepare("SELECT r.rider_id FROM riders r JOIN orders o ON o.assigned_rider_id=r.rider_id WHERE o.order_id=? AND r.account_id=? LIMIT 1");
            $chk->bind_param('ii', $order_id, $actor_id);
            $chk->execute();
            $chkrow = $chk->get_result()->fetch_assoc();
            if (!$chkrow) respond(['ok' => false, 'msg' => 'This order is not assigned to you.']);
            $rider_id = (int)$chkrow['rider_id'];
        } else {
            $rq = $conn->prepare("SELECT assigned_rider_id FROM orders WHERE order_id=? LIMIT 1");
            $rq->bind_param('i', $order_id);
            $rq->execute();
            $rrow = $rq->get_result()->fetch_assoc();
            $rider_id = (int)($rrow['assigned_rider_id'] ?? 0);
        }
        $ext  = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
        $fname = 'proof_' . $order_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir  = __DIR__ . '/../../uploads/delivery_proofs/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) respond(['ok' => false, 'msg' => 'File save failed.']);
        $result = saveDeliveryProof($order_id, $rider_id, 'uploads/delivery_proofs/' . $fname, $file['name'], (int)round($file['size'] / 1024), $mime, $caption, $conn);
        if ($result['ok']) {
            $order = getOrderRow($order_id, $conn);
            _broadcastNotif($order_id, "Delivery proof uploaded for Order #{$order['order_code']}.", $order['account_id'], null, $conn);
        }
        respond($result);

    case 'mark_read':
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        markNotificationsRead($actor_id, $actor_role, $conn, $order_id);
        respond(['ok' => true]);

    case 'poll_orders':
        $last = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $last)) {
            $last = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        }
        // Uses renamed columns: recipient_first_name, recipient_last_name
        $stmt = $conn->prepare("
            SELECT o.order_id, o.order_code,
                   o.recipient_first_name AS first_name,
                   o.recipient_last_name  AS last_name,
                   o.order_status, o.updated_at, o.total_price,
                   p.payment_status
            FROM orders o LEFT JOIN payments p ON p.order_id=o.order_id
            WHERE o.updated_at > ? AND o.is_deleted=0
            ORDER BY o.updated_at DESC LIMIT 30
        ");
        $stmt->bind_param('s', $last);
        $stmt->execute();
        $changed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $notifs  = getUnreadNotifications($actor_id, $actor_role, $conn, 5);
        respond([
            'ok'            => true,
            'server_time'   => date('Y-m-d H:i:s'),
            'changed'       => $changed,
            'unread_count'  => count(getUnreadNotifications($actor_id, $actor_role, $conn)),
            'notifications' => $notifs,
        ]);

    case 'poll_notifications':
        $items = getUnreadNotifications($actor_id, $actor_role, $conn);
        respond(['ok' => true, 'count' => count($items), 'items' => $items]);

    case 'get_order_detail':
        $order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $order   = getOrderFull($order_id, $conn);
        if (!$order) respond(['ok' => false, 'msg' => 'Order not found.']);
        $items   = getOrderItems($order_id, $conn);
        $history = getOrderHistory($order_id, $conn);
        $proofs  = getDeliveryProofs($order_id, $conn);
        $riders  = isAdmin($actor_role) ? getRidersList($conn) : [];
        $transitions = ORDER_STATUS_FLOW[$order['order_status']] ?? [];
        if (isRider($actor_role)) $transitions = array_values(array_intersect($transitions, ['Delivered']));
        respond([
            'ok'              => true,
            'order'           => $order,
            'items'           => $items,
            'history'         => $history,
            'proofs'          => $proofs,
            'riders'          => $riders,
            'transitions'     => $transitions,
            'status_labels'   => STATUS_LABELS,
            'delivery_labels' => DELIVERY_STATUS_LABELS,
        ]);

    case 'get_riders':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $available_only = isset($_GET['available_only']) && $_GET['available_only'] === '1';
        respond(['ok' => true, 'riders' => getRidersList($conn, $available_only)]);

    case 'get_tracking':
        $order_id = (int)($_GET['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $latest = getLatestLocation($order_id, $conn);
        // Uses renamed: delivery_status
        $dstmt = $conn->prepare("SELECT delivery_id FROM deliveries WHERE order_id=? AND delivery_status IN ('accepted','picked_up','in_transit') ORDER BY assigned_at DESC LIMIT 1");
        $dstmt->bind_param('i', $order_id);
        $dstmt->execute();
        $drow = $dstmt->get_result()->fetch_assoc();
        $breadcrumbs = $drow ? getTrackingBreadcrumbs((int)$drow['delivery_id'], $conn) : [];
        respond([
            'ok'          => true,
            'latest'      => $latest,
            'breadcrumbs' => $breadcrumbs,
            'delivery_id' => $drow['delivery_id'] ?? null,
        ]);

    case 'get_my_deliveries':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        respond(['ok' => true, 'deliveries' => getRiderPendingDeliveries($actor_id, $conn)]);

    case 'regenerate_review_link':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);

        $chk = $conn->prepare("SELECT order_status, order_code, recipient_email FROM orders WHERE order_id = ? LIMIT 1");
        $chk->bind_param('i', $order_id);
        $chk->execute();
        $chkRow = $chk->get_result()->fetch_assoc();
        if (!$chkRow || $chkRow['order_status'] !== 'Delivered') {
            respond(['ok' => false, 'msg' => 'Order not found or not yet delivered.']);
        }

        // ── Use the SAME deterministic token that review.php validates ──────────
        $token     = strtoupper(substr(hash('sha256', $chkRow['order_code'] . $chkRow['recipient_email'] . 'sjfbi_review_2025'), 0, 12));
        $baseUrl   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $reviewUrl = $baseUrl . '/sjfbi-js/review.php?order=' . urlencode($chkRow['order_code']) . '&token=' . urlencode($token);

        $del = $conn->prepare("DELETE FROM review_invites WHERE order_id = ?");
        $del->bind_param('i', $order_id);
        $del->execute();

        $ins = $conn->prepare("INSERT INTO review_invites (order_id, review_url, sent_at) VALUES (?, ?, NOW())");
        $ins->bind_param('is', $order_id, $reviewUrl);
        if ($ins->execute()) {
            respond(['ok' => true, 'msg' => 'Review link regenerated.', 'review_url' => $reviewUrl]);
        }
        respond(['ok' => false, 'msg' => 'Failed to regenerate link.']);

    case 'mark_cod_payment_received':
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        // Riders AND admins can do this
        respond(markCODPaymentReceived($order_id, $actor_id, $actor_role, $conn));

    case 'mark_third_party_paid':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        respond(markThirdPartyPaid($order_id, $actor_id, $actor_role, $conn));

    default:
        http_response_code(400);
        respond(['ok' => false, 'msg' => 'Unknown action: ' . htmlspecialchars($action)]);
}