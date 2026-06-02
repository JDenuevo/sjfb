<?php
/**
 * supadmin/functions/order_process.php
 *
 * Column renames applied:
 *   orders:     recipient_first_name/last_name
 *   deliveries: delivery_status (was status)
 *
 * Email notifications are wired in order_helper.php for most actions.
 * This file handles the send_out_for_delivery case which needs an email
 * after the status update, because the status change happens here.
 */
ob_start(); // ← start output buffer
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../conn.php';
require_once __DIR__ . '/order_helper.php';
// email_helper.php is already loaded inside order_helper.php

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

    // ── Approve & Process ────────────────────────────────────────────────────
    // Email sent inside approveOrder() → email_order_approved()
    case 'approve_order':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $result = approveOrder($order_id, $actor_id, $actor_role, $conn);
        $order  = $result['ok'] ? getOrderRow($order_id, $conn) : null;
        respond_then_email($result, $order ? function() use ($order) {
            email_order_approved($order);
        } : null);

    // ── Assign Registered Rider ──────────────────────────────────────────────
    // No email here — rider accepts → then OutForDelivery email fires
    case 'assign_rider':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $rider_id = (int)($_POST['rider_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        if (!$order_id || !$rider_id) respond(['ok' => false, 'msg' => 'Missing order_id or rider_id.']);
        respond(assignRegisteredRider($order_id, $rider_id, $actor_id, $actor_role, $notes, $conn));

    // ── 3rd-Party Delivery ────────────────────────────────────────────────────
    // Email sent inside assignThirdPartyDelivery() → email_out_for_delivery()
    case 'assign_third_party':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id         = (int)($_POST['order_id'] ?? 0);
        $third_party_name = trim($_POST['third_party_name'] ?? '');
        $delivery_link    = trim($_POST['delivery_link'] ?? '');
        $notes            = trim($_POST['notes'] ?? '');
        if (!$order_id)         respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (!$third_party_name) respond(['ok' => false, 'msg' => '3rd-party provider name is required.']);

        // Detect reassignment BEFORE the function runs
        $preOrder      = getOrderRow($order_id, $conn);
        $isReassign    = ($preOrder['order_status'] ?? '') === 'OutForDelivery';

        $result = assignThirdPartyDelivery($order_id, $third_party_name, $delivery_link, $actor_id, $actor_role, $notes, $conn);

        respond_then_email($result, $result['ok'] ? function() use ($order_id, $isReassign, $conn) {
            $freshOrder = getOrderFull($order_id, $conn);
            email_third_party_dispatched($freshOrder, $isReassign);
        } : null);

    // ── Send Out for Delivery (admin dispatch without rider accept) ───────────
    // Email: email_out_for_delivery() — fired here after status update
    case 'send_out_for_delivery':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? 'Dispatched for delivery.');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);

        $r = _updateOrderStatusRaw($order_id, 'Processing', 'OutForDelivery', $actor_id, $actor_role, $notes, $conn);
        if ($r['ok']) {
            $conn->query("UPDATE deliveries SET delivery_status='accepted', accepted_at=NOW() WHERE order_id={$order_id} AND delivery_status='pending_acceptance'");
            $order = getOrderRow($order_id, $conn);
            _broadcastNotif($order_id, "Order #{$order['order_code']} is now Out for Delivery.", $order['account_id'], $order['assigned_rider_id'], $conn);
        }

        respond_then_email($r, $r['ok'] ? function() use ($order_id, $conn) {
            $fullOrder = getOrderFull($order_id, $conn);
            // Use 3rd-party email if applicable, otherwise generic OFD email
            if (!empty($fullOrder['is_third_party'])) {
                email_third_party_dispatched($fullOrder, false);
            } else {
                email_out_for_delivery($fullOrder);
            }
        } : null);

    // ── Cancel Order ──────────────────────────────────────────────────────────
    // Email sent inside cancelOrder() → email_order_cancelled()
    case 'cancel_order':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $reason   = trim($_POST['reason'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        if (!$reason)   respond(['ok' => false, 'msg' => 'Cancellation reason is required.']);

        $result = cancelOrder($order_id, $actor_id, $actor_role, $reason, $conn);

        respond_then_email($result, $result['ok'] ? function() use ($order_id, $reason, $conn) {
            $order = getOrderRow($order_id, $conn);
            if ($order) {
                email_order_cancelled($order, $reason);
            }
        } : null);

    // ── Mark Delivered ────────────────────────────────────────────────────────
    // Email sent inside markDelivered() → email_delivered_confirm_receipt()
    case 'mark_delivered':
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);

        if (isRider($actor_role)) {
            $check = $conn->prepare("
                SELECT o.order_id FROM orders o
                JOIN riders r ON r.rider_id = o.assigned_rider_id
                WHERE o.order_id = ? AND r.account_id = ?
            ");
            $check->bind_param('ii', $order_id, $actor_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc())
                respond(['ok' => false, 'msg' => 'This order is not assigned to you.']);
        }

        $result    = markDelivered($order_id, $actor_id, $actor_role, $notes, $conn);
        $freshOrder = $result['ok'] ? getOrderRow($order_id, $conn) : null;

        respond_then_email($result, ($result['ok'] && $freshOrder) ? function() use ($order_id, $freshOrder, $conn) {
            $token = generate_and_persist_confirm_token($order_id, $conn, 48);
            email_delivered_confirm_receipt($freshOrder, $token);
        } : null);

    // ── Mark Ready for Pickup ─────────────────────────────────────────────────
    // Email sent inside markReadyForPickup() → email_ready_for_pickup()
    case 'mark_ready_for_pickup':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? 'Order ready for pickup');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $result = markReadyForPickup($order_id, $actor_id, $actor_role, $notes, $conn);
        respond_then_email($result, $result['ok'] ? function() use ($order_id, $conn) {
            $freshOrder = getOrderRow($order_id, $conn);
            email_ready_for_pickup($freshOrder); // ← no token, no button
        } : null);

    // ── Mark Picked Up (admin confirms customer collected) ────────────────────
    case 'mark_picked_up':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        $notes    = trim($_POST['notes'] ?? 'Customer picked up order');
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);

        $result = markPickedUp($order_id, $actor_id, $actor_role, $notes, $conn);

        respond_then_email($result, $result['ok'] ? function() use ($order_id, $conn) {
            $order    = getOrderRow($order_id, $conn);
            $isCOP    = ($order['payment_method'] ?? '') === 'cop';
            $isOnline = !in_array(strtolower($order['payment_method'] ?? ''), ['cod', 'cop']);

            if ($isOnline) {
                // Online-paid pickup — already paid, send completion + review now
                generateReviewInvite($order_id, $conn);

                $riStmt = $conn->prepare("SELECT review_url FROM review_invites WHERE order_id = ? ORDER BY sent_at DESC LIMIT 1");
                $riStmt->bind_param('i', $order_id);
                $riStmt->execute();
                $ri = $riStmt->get_result()->fetch_assoc();

                if ($ri && !empty($ri['review_url'])) {
                    email_pickup_completed($order, $ri['review_url']);
                }
            }
            // COP pickup — no email here, wait for payment collection
        } : null);

    // ── Rider: Accept Delivery ────────────────────────────────────────────────
    // Email sent inside riderAcceptDelivery() → email_out_for_delivery()
    case 'rider_accept':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        if (!$delivery_id) respond(['ok' => false, 'msg' => 'Missing delivery_id.']);

        // Fetch data BEFORE calling the function (which currently sends email inside)
        $dlRow = $conn->query("
            SELECT d.order_id, r.account_id
            FROM deliveries d
            JOIN riders r ON r.rider_id = d.rider_id
            WHERE d.delivery_id = {$delivery_id}
            LIMIT 1
        ")->fetch_assoc();

        $result = riderAcceptDelivery($delivery_id, $actor_id, $conn);

        respond_then_email($result, ($result['ok'] && $dlRow) ? function() use ($dlRow, $conn) {
            $fullOrder = getOrderFull((int)$dlRow['order_id'], $conn);
            $riderRow  = getRiderByAccountId((int)$dlRow['account_id'], $conn);
            email_out_for_delivery($fullOrder, $riderRow);
        } : null);

    // ── Rider: Pick Up ────────────────────────────────────────────────────────
    case 'rider_pickup':
        if (!isRider($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $delivery_id = (int)($_POST['delivery_id'] ?? 0);
        if (!$delivery_id) respond(['ok' => false, 'msg' => 'Missing delivery_id.']);
        // No email on pickup — just respond normally
        respond(riderPickUp($delivery_id, $actor_id, $conn));

    // ── Push GPS Location ────────────────────────────────────────────────────
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

    // ── Upload Delivery Proof ─────────────────────────────────────────────────
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
            $rrow     = $rq->get_result()->fetch_assoc();
            $rider_id = (int)($rrow['assigned_rider_id'] ?? 0);
        }
        $ext   = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
        $fname = 'proof_' . $order_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir   = __DIR__ . '/../../uploads/delivery_proofs/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) respond(['ok' => false, 'msg' => 'File save failed.']);
        $result = saveDeliveryProof($order_id, $rider_id, 'uploads/delivery_proofs/' . $fname, $file['name'], (int)round($file['size'] / 1024), $mime, $caption, $conn);
        if ($result['ok']) {
            $order = getOrderRow($order_id, $conn);
            _broadcastNotif($order_id, "Delivery proof uploaded for Order #{$order['order_code']}.", $order['account_id'], null, $conn);
        }
        respond($result);

    // ── Notifications ────────────────────────────────────────────────────────
    case 'mark_read':
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        markNotificationsRead($actor_id, $actor_role, $conn, $order_id);
        respond(['ok' => true]);

    case 'poll_orders':
        $last = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $last)) {
            $last = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        }
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
            'ok'           => true,
            'server_time'  => date('Y-m-d H:i:s'),
            'changed'      => $changed,
            'unread_count' => count(getUnreadNotifications($actor_id, $actor_role, $conn)),
            'notifications'=> $notifs,
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
        $latest  = getLatestLocation($order_id, $conn);
        $dstmt   = $conn->prepare("SELECT delivery_id FROM deliveries WHERE order_id=? AND delivery_status IN ('accepted','picked_up','in_transit') ORDER BY assigned_at DESC LIMIT 1");
        $dstmt->bind_param('i', $order_id);
        $dstmt->execute();
        $drow        = $dstmt->get_result()->fetch_assoc();
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
        respond_then_email(markCODPaymentReceived($order_id, $actor_id, $actor_role, $conn));

    case 'mark_cop_payment_received':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        $result = markCOPPaymentReceived($order_id, $actor_id, $actor_role, $conn);
        respond_then_email($result, $result['ok'] ? function() use ($order_id, $conn) {
            $freshOrder = getOrderRow($order_id, $conn);
            // Fetch the review invite that was just generated
            $riStmt = $conn->prepare("SELECT review_url FROM review_invites WHERE order_id = ? ORDER BY sent_at DESC LIMIT 1");
            $riStmt->bind_param('i', $order_id);
            $riStmt->execute();
            $ri = $riStmt->get_result()->fetch_assoc();
            if ($ri && !empty($ri['review_url'])) {
                email_pickup_completed($freshOrder, $ri['review_url']);
            }
        } : null);

    case 'mark_third_party_paid':
        if (!isAdmin($actor_role)) respond(['ok' => false, 'msg' => 'Unauthorized.']);
        $order_id = (int)($_POST['order_id'] ?? 0);
        if (!$order_id) respond(['ok' => false, 'msg' => 'Missing order_id.']);
        respond(markThirdPartyPaid($order_id, $actor_id, $actor_role, $conn));

    default:
        http_response_code(400);
        respond(['ok' => false, 'msg' => 'Unknown action: ' . htmlspecialchars($action)]);
}

/**
 * Send JSON response immediately, then fire emails in the background
 * so SMTP latency never blocks the API response.
 */
function respond_then_email(array $data, callable $email_fn = null): void {
    $json = json_encode($data);

    // Close the HTTP connection so the browser gets its response NOW
    header('Content-Length: ' . strlen($json));
    header('Connection: close');
    ob_end_clean();
    echo $json;
    flush();

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // works on PHP-FPM (most modern servers)
    }

    // Email fires AFTER response is sent — client never waits for it
    if ($data['ok'] && $email_fn) {
        try {
            $email_fn();
        } catch (Exception $e) {
            error_log('[SJFBI Email] Post-response email error: ' . $e->getMessage());
        }
    }

    exit;
}