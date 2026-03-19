<?php
/**
 * supadmin/functions/order_helper.php
 * All column renames applied:
 *   orders:   recipient_first_name/last_name/email/phone/address
 *   accounts: account_first_name/last_name/email/phone
 *   riders:   rider_name (was full_name), rider_phone (was contact_number)
 *   deliveries: delivery_status (was status)
 *   delivery_tracking: tracking_status (was status)
 */

date_default_timezone_set('Asia/Manila');

const ORDER_STATUS_FLOW = [
    'Paid'           => ['Processing', 'Cancelled'],  // ← ADD THIS
    'Pending'        => ['Processing', 'Cancelled'],
    'Processing'     => ['OutForDelivery', 'Cancelled'],
    'OutForDelivery' => ['Delivered', 'Cancelled'],
    'Delivered'      => [],
    'Cancelled'      => [],
];

const STATUS_LABELS = [
    'Paid'           => 'Paid - Awaiting Approval',  // ← ADD THIS
    'Pending'        => 'Pending',
    'Processing'     => 'Processing',
    'OutForDelivery' => 'Out for Delivery',
    'Delivered'      => 'Delivered',
    'Cancelled'      => 'Cancelled',
];

const DELIVERY_STATUS_LABELS = [
    'pending_acceptance' => 'Awaiting Rider Acceptance',
    'accepted'           => 'Rider Accepted',
    'picked_up'          => 'Picked Up',
    'in_transit'         => 'In Transit',
    'delivered'          => 'Delivered',
    'failed'             => 'Failed',
    'reassigned'         => 'Reassigned',
    'cancelled'          => 'Cancelled',
];

// ════════════════════════════════════════════════════════════════════════════
//  STOCK MANAGEMENT
// ════════════════════════════════════════════════════════════════════════════

function checkOrderStock(int $order_id, mysqli $conn): array {
    $stmt = $conn->prepare("
        SELECT oi.order_item_id, oi.product_id, oi.variant_id, oi.quantity,
               pv.variant_name, pv.stock_quantity, pv.stock_status,
               p.product_name
        FROM order_items oi
        JOIN product_variants pv ON pv.variant_id = oi.variant_id
        JOIN products p          ON p.product_id  = oi.product_id
        WHERE oi.order_id = ?
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error checking stock.'];
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $shortfalls = [];
    foreach ($items as $item) {
        if ($item['stock_quantity'] < $item['quantity']) {
            $shortfalls[] = [
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'requested'    => $item['quantity'],
                'available'    => $item['stock_quantity'],
            ];
        }
    }

    if (!empty($shortfalls)) {
        $lines = array_map(fn($s) =>
            "{$s['product_name']} ({$s['variant_name']}): need {$s['requested']}, only {$s['available']} left",
            $shortfalls
        );
        return ['ok' => false, 'msg' => 'Insufficient stock: ' . implode('; ', $lines), 'shortfalls' => $shortfalls];
    }

    return ['ok' => true, 'msg' => 'Stock OK.', 'items' => $items];
}

function deductOrderStock(int $order_id, mysqli $conn): array {
    $check = checkOrderStock($order_id, $conn);
    if (!$check['ok']) return $check;

    $conn->begin_transaction();
    try {
        foreach ($check['items'] as $item) {
            $stmt = $conn->prepare("
                UPDATE product_variants
                SET stock_quantity = stock_quantity - ?,
                    stock_status   = IF(stock_quantity - ? <= 0, 'Out of Stock', 'In Stock')
                WHERE variant_id = ? AND stock_quantity >= ?
            ");
            if (!$stmt) throw new Exception('DB error on deduct.');
            $stmt->bind_param('iiii', $item['quantity'], $item['quantity'], $item['variant_id'], $item['quantity']);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception("Stock changed for {$item['product_name']} ({$item['variant_name']}) — please re-check.");
            }
        }
        $conn->commit();
        return ['ok' => true, 'msg' => 'Stock deducted.'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}

function restoreOrderStock(int $order_id, mysqli $conn): void {
    $stmt = $conn->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id=?");
    if (!$stmt) return;
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) {
        $u = $conn->prepare("UPDATE product_variants SET stock_quantity=stock_quantity+?, stock_status='In Stock' WHERE variant_id=?");
        if ($u) { $u->bind_param('ii', $item['quantity'], $item['variant_id']); $u->execute(); }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  APPROVE ORDER
// ════════════════════════════════════════════════════════════════════════════

function approveOrder(int $order_id, int $actor_id, string $actor_type, mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if (!in_array($order['order_status'], ['Pending', 'Paid'])) {
        return ['ok' => false, 'msg' => "Order cannot be approved from status: {$order['order_status']}."];
    }
    $stockCheck = checkOrderStock($order_id, $conn);
    if (!$stockCheck['ok']) return $stockCheck;
    $deduct = deductOrderStock($order_id, $conn);
    if (!$deduct['ok']) return $deduct;
    $result = _updateOrderStatusRaw($order_id, $order['order_status'], 'Processing', $actor_id, $actor_type, 'Order approved — stock deducted.', $conn);
    if (!$result['ok']) { restoreOrderStock($order_id, $conn); return $result; }
    _logActivity('order', $order_id, $actor_id, $actor_type, 'approve_order', $order['order_status'], 'Processing', 'Stock checked and deducted.', $conn);
    _broadcastNotif($order_id, "Order #{$order['order_code']} approved and is now being processed.", $order['account_id'], null, $conn);
    return ['ok' => true, 'msg' => 'Order approved. Stock deducted.'];
}

// ════════════════════════════════════════════════════════════════════════════
//  RIDER ASSIGNMENT — FLOW A: Registered Rider
// ════════════════════════════════════════════════════════════════════════════

function assignRegisteredRider(int $order_id, int $rider_id, int $actor_id, string $actor_type, string $notes = '', mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if ($order['order_status'] !== 'Processing') {
        return ['ok' => false, 'msg' => 'Order must be in Processing before assigning a rider.'];
    }

    // Uses renamed: account_first_name, account_last_name
    $stmt = $conn->prepare("
        SELECT r.rider_id, a.account_first_name, a.account_last_name, r.account_id
        FROM riders r
        JOIN accounts a ON a.account_id=r.account_id
        WHERE r.rider_id=? AND r.is_deleted=0
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $rider = $stmt->get_result()->fetch_assoc();
    if (!$rider) return ['ok' => false, 'msg' => 'Rider not found.'];

    $conn->begin_transaction();
    try {
        // Uses renamed: delivery_status (was status)
        $conn->query("UPDATE deliveries SET delivery_status='reassigned' WHERE order_id={$order_id} AND delivery_status NOT IN ('delivered','cancelled','reassigned')");

        $ins = $conn->prepare("INSERT INTO deliveries (order_id, rider_id, is_third_party, delivery_status, assigned_by, notes) VALUES (?,?,0,'pending_acceptance',?,?)");
        if (!$ins) throw new Exception('DB error creating delivery.');
        $ins->bind_param('iiis', $order_id, $rider_id, $actor_id, $notes);
        $ins->execute();

        $conn->query("UPDATE orders SET assigned_rider_id={$rider_id}, updated_at=NOW() WHERE order_id={$order_id}");
        $conn->query("UPDATE riders SET is_available=0 WHERE rider_id={$rider_id}");
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'msg' => $e->getMessage()];
    }

    $name = "{$rider['account_first_name']} {$rider['account_last_name']}";
    _logActivity('order', $order_id, $actor_id, $actor_type, 'assign_rider', null, $name, $notes, $conn);
    _insertNotif($order_id, 'rider', (int)$rider['account_id'], "New delivery assignment for Order #{$order['order_code']}. Please accept in your dashboard.", $conn);
    _insertNotif($order_id, 'super_admin', null, "Rider {$name} assigned to Order #{$order['order_code']}. Waiting for acceptance.", $conn);
    return ['ok' => true, 'msg' => "Rider {$name} assigned. Waiting for acceptance."];
}

// ════════════════════════════════════════════════════════════════════════════
//  RIDER ASSIGNMENT — FLOW B: 3rd-Party Delivery
// ════════════════════════════════════════════════════════════════════════════

function assignThirdPartyDelivery(int $order_id, string $third_party_name, string $delivery_link, int $actor_id, string $actor_type, string $notes = '', mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if ($order['order_status'] !== 'Processing') return ['ok' => false, 'msg' => 'Order must be in Processing.'];
    if (empty(trim($third_party_name))) return ['ok' => false, 'msg' => '3rd-party provider name is required.'];

    $conn->begin_transaction();
    try {
        // Uses renamed: delivery_status (was status)
        $conn->query("UPDATE deliveries SET delivery_status='reassigned' WHERE order_id={$order_id} AND delivery_status NOT IN ('delivered','cancelled','reassigned')");

        $ins = $conn->prepare("INSERT INTO deliveries (order_id, rider_id, is_third_party, third_party_name, delivery_link, delivery_status, accepted_at, assigned_by, notes) VALUES (?,NULL,1,?,?,'accepted',NOW(),?,?)");
        if (!$ins) throw new Exception('DB error.');
        $ins->bind_param('issis', $order_id, $third_party_name, $delivery_link, $actor_id, $notes);
        $ins->execute();

        $upd = $conn->prepare("UPDATE orders SET delivery_link=?, assigned_rider_id=NULL, updated_at=NOW() WHERE order_id=?");
        $upd->bind_param('si', $delivery_link, $order_id);
        $upd->execute();

        _updateOrderStatusRaw($order_id, 'Processing', 'OutForDelivery', $actor_id, $actor_type, "3rd-party delivery via {$third_party_name}.", $conn);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'msg' => $e->getMessage()];
    }

    _logActivity('order', $order_id, $actor_id, $actor_type, 'assign_third_party', null, $third_party_name, "Link: {$delivery_link}", $conn);
    _broadcastNotif($order_id, "Order #{$order['order_code']} dispatched via {$third_party_name}. Now out for delivery.", $order['account_id'], null, $conn);
    return ['ok' => true, 'msg' => "Order dispatched via {$third_party_name}. Now Out for Delivery."];
}

// ════════════════════════════════════════════════════════════════════════════
//  RIDER ACCEPTS / PICKS UP
// ════════════════════════════════════════════════════════════════════════════

function riderAcceptDelivery(int $delivery_id, int $rider_account_id, mysqli $conn): array {
    $stmt = $conn->prepare("
        SELECT d.*, r.account_id, o.order_code, o.order_status
        FROM deliveries d
        JOIN riders r ON r.rider_id=d.rider_id
        JOIN orders o ON o.order_id=d.order_id
        WHERE d.delivery_id=? AND r.account_id=?
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('ii', $delivery_id, $rider_account_id);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    if (!$d) return ['ok' => false, 'msg' => 'Delivery not found or not assigned to you.'];
    // Uses renamed: delivery_status
    if ($d['delivery_status'] !== 'pending_acceptance') return ['ok' => false, 'msg' => 'Delivery is no longer pending acceptance.'];

    $conn->query("UPDATE deliveries SET delivery_status='accepted', accepted_at=NOW() WHERE delivery_id={$delivery_id}");
    _updateOrderStatusRaw((int)$d['order_id'], 'Processing', 'OutForDelivery', $rider_account_id, 'rider', 'Rider accepted the delivery.', $conn);
    _broadcastNotif((int)$d['order_id'], "Rider accepted Order #{$d['order_code']}. Now Out for Delivery.", null, (int)$d['rider_id'], $conn);
    return ['ok' => true, 'msg' => 'Delivery accepted. Order is now Out for Delivery.'];
}

function riderPickUp(int $delivery_id, int $rider_account_id, mysqli $conn): array {
    // Uses renamed: delivery_status
    $stmt = $conn->prepare("
        SELECT d.order_id, o.order_code
        FROM deliveries d
        JOIN riders r ON r.rider_id=d.rider_id
        JOIN orders o ON o.order_id=d.order_id
        WHERE d.delivery_id=? AND r.account_id=? AND d.delivery_status='accepted'
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('ii', $delivery_id, $rider_account_id);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    if (!$d) return ['ok' => false, 'msg' => 'Delivery not found or not in accepted state.'];
    $conn->query("UPDATE deliveries SET delivery_status='picked_up', picked_up_at=NOW() WHERE delivery_id={$delivery_id}");
    _broadcastNotif((int)$d['order_id'], "Rider picked up Order #{$d['order_code']}. On the way!", null, null, $conn);
    return ['ok' => true, 'msg' => 'Marked as picked up.'];
}

// ════════════════════════════════════════════════════════════════════════════
//  MARK DELIVERED / CANCEL
// ════════════════════════════════════════════════════════════════════════════

function markDelivered(int $order_id, int $actor_id, string $by_role, string $notes = '', mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if ($order['order_status'] !== 'OutForDelivery') 
        return ['ok' => false, 'msg' => 'Order is not Out for Delivery.'];

    $conn->begin_transaction();
    try {
        $conn->query("
            UPDATE deliveries 
            SET delivery_status = 'delivered', delivered_at = NOW() 
            WHERE order_id = {$order_id} 
              AND delivery_status IN ('accepted','picked_up','in_transit')
        ");
        $reason = $by_role === 'rider' 
            ? 'Rider confirmed delivery.' 
            : "Admin override ({$by_role}). {$notes}";
        _updateOrderStatusRaw($order_id, 'OutForDelivery', 'Delivered', 
            $actor_id, $by_role, $reason, $conn);
        if ($order['assigned_rider_id']) 
            updateRiderAvailability((int)$order['assigned_rider_id'], $conn);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'msg' => $e->getMessage()];
    }

    // Auto-generate review invite
    $reviewUrl = generateReviewInvite($order_id, $conn);

    _broadcastNotif($order_id, 
        "Order #{$order['order_code']} has been delivered!", 
        $order['account_id'], $order['assigned_rider_id'], $conn);
    _logActivity('order', $order_id, $actor_id, $by_role, 
        'mark_delivered', 'OutForDelivery', 'Delivered', $notes, $conn);

    // TODO: trigger SMS + email here via your Semaphore/SMTP pipeline
    // sendReviewInviteNotification($order, $reviewUrl);

    return ['ok' => true, 'msg' => 'Order marked as Delivered.'];
}

function cancelOrder(int $order_id, int $actor_id, string $actor_type, string $reason, mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if (in_array($order['order_status'], ['Delivered', 'Cancelled'], true)) {
        return ['ok' => false, 'msg' => "Cannot cancel a {$order['order_status']} order."];
    }

    $stock_was_deducted = in_array($order['order_status'], ['Processing', 'OutForDelivery'], true);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE orders SET order_status='Cancelled', cancelled_by=?, cancel_reason=?, updated_at=NOW() WHERE order_id=?");
        if (!$stmt) throw new Exception('DB error.');
        $stmt->bind_param('ssi', $actor_type, $reason, $order_id);
        $stmt->execute();

        $hist = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by_user_id, changed_by_user_type, notes) VALUES (?,?,'Cancelled',?,?,?)");
        $hist->bind_param('isiis', $order_id, $order['order_status'], $actor_id, $actor_type, $reason);
        $hist->execute();

        // Uses renamed: delivery_status
        $conn->query("UPDATE deliveries SET delivery_status='cancelled' WHERE order_id={$order_id} AND delivery_status NOT IN ('delivered','cancelled')");

        if ($stock_was_deducted) restoreOrderStock($order_id, $conn);
        if ($order['assigned_rider_id']) updateRiderAvailability((int)$order['assigned_rider_id'], $conn);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        return ['ok' => false, 'msg' => $e->getMessage()];
    }

    _broadcastNotif($order_id, "Order #{$order['order_code']} cancelled. Reason: {$reason}", $order['account_id'], $order['assigned_rider_id'], $conn);
    _logActivity('order', $order_id, $actor_id, $actor_type, 'cancel_order', $order['order_status'], 'Cancelled', $reason, $conn);
    return ['ok' => true, 'msg' => 'Order cancelled.'];
}

// ════════════════════════════════════════════════════════════════════════════
//  GPS TRACKING — uses renamed tracking_status (was status)
// ════════════════════════════════════════════════════════════════════════════

function pushRiderLocation(int $rider_id, float $lat, float $lng, int $delivery_id, string $status = 'en_route', string $notes = '', mysqli $conn): array {
    $upd = $conn->prepare("UPDATE riders SET current_lat=?, current_lng=?, updated_at=NOW() WHERE rider_id=?");
    if (!$upd) return ['ok' => false, 'msg' => 'DB error.'];
    $upd->bind_param('ddi', $lat, $lng, $rider_id);
    $upd->execute();

    // Uses renamed: tracking_status (was status)
    $ins = $conn->prepare("INSERT INTO delivery_tracking (delivery_id, tracking_status, latitude, longitude, notes) VALUES (?,?,?,?,?)");
    if (!$ins) return ['ok' => false, 'msg' => 'DB error.'];
    $ins->bind_param('isdds', $delivery_id, $status, $lat, $lng, $notes);
    $ins->execute();
    return ['ok' => true];
}

function getLatestLocation(int $order_id, mysqli $conn): ?array {
    $stmt = $conn->prepare("
        SELECT dt.latitude, dt.longitude, dt.timestamp, dt.tracking_status AS status,
               COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name,
               r.vehicle_type, r.vehicle_plate_number, r.variant_color
        FROM delivery_tracking dt
        JOIN deliveries d ON d.delivery_id=dt.delivery_id
        JOIN riders r     ON r.rider_id=d.rider_id
        JOIN accounts a   ON a.account_id=r.account_id
        WHERE d.order_id=? AND d.delivery_status IN ('accepted','picked_up','in_transit')
        ORDER BY dt.timestamp DESC LIMIT 1
    ");
    if (!$stmt) return null;
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getTrackingBreadcrumbs(int $delivery_id, mysqli $conn): array {
    // Uses renamed: tracking_status (was status)
    $stmt = $conn->prepare("SELECT latitude, longitude, tracking_status AS status, timestamp FROM delivery_tracking WHERE delivery_id=? ORDER BY timestamp ASC");
    if (!$stmt) return [];
    $stmt->bind_param('i', $delivery_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ════════════════════════════════════════════════════════════════════════════
//  DELIVERY PROOFS
// ════════════════════════════════════════════════════════════════════════════

function saveDeliveryProof(int $order_id, int $rider_id, string $file_path, string $file_name, int $file_size_kb, string $mime, string $caption, mysqli $conn): array {
    // Uses renamed: delivery_status (was status)
    $stmt = $conn->prepare("SELECT delivery_id FROM deliveries WHERE order_id=? AND delivery_status IN ('accepted','picked_up','in_transit','delivered') ORDER BY assigned_at DESC LIMIT 1");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    if (!$d) return ['ok' => false, 'msg' => 'No active delivery found for this order.'];

    $delivery_id = (int)$d['delivery_id'];
    $ins = $conn->prepare("INSERT INTO delivery_proofs (delivery_id, order_id, rider_id, file_path, file_name, file_size, mime_type, caption) VALUES (?,?,?,?,?,?,?,?)");
    if (!$ins) return ['ok' => false, 'msg' => 'DB error.'];
    $ins->bind_param('iiississ', $delivery_id, $order_id, $rider_id, $file_path, $file_name, $file_size_kb, $mime, $caption);
    if (!$ins->execute()) return ['ok' => false, 'msg' => 'Failed to save proof.'];
    return ['ok' => true, 'proof_id' => $conn->insert_id, 'delivery_id' => $delivery_id];
}

function getDeliveryProofs(int $order_id, mysqli $conn): array {
    // Uses renamed: rider_name, account_first_name, account_last_name
    $stmt = $conn->prepare("
        SELECT dp.*,
               COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS rider_name
        FROM delivery_proofs dp
        JOIN riders r   ON r.rider_id=dp.rider_id
        JOIN accounts a ON a.account_id=r.account_id
        WHERE dp.order_id=?
        ORDER BY dp.uploaded_at ASC
    ");
    if (!$stmt) return [];
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ════════════════════════════════════════════════════════════════════════════
//  PAYMENT STATUS MARKING
// ════════════════════════════════════════════════════════════════════════════

function markCODPaymentReceived(int $order_id, int $actor_id, string $actor_type, mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if ($order['order_status'] !== 'Delivered') 
        return ['ok' => false, 'msg' => 'Order must be Delivered first.'];
    if ($order['payment_method'] !== 'cod')
        return ['ok' => false, 'msg' => 'Only COD orders need payment collection.'];

    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'Paid', paid_at = NOW()
        WHERE order_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) return ['ok' => false, 'msg' => 'Failed to update payment.'];
    
    $conn->query("UPDATE orders SET updated_at = NOW() WHERE order_id = {$order_id}");

    _logActivity('order', $order_id, $actor_id, $actor_type, 
        'payment_received', 'Pending', 'Paid', 'COD payment collected.', $conn);
    _broadcastNotif($order_id, 
        "Payment collected for COD Order #{$order['order_code']}.", 
        $order['account_id'], null, $conn);

    return ['ok' => true, 'msg' => 'Payment marked as received.'];
}

function markThirdPartyPaid(int $order_id, int $actor_id, string $actor_type, mysqli $conn): array {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return ['ok' => false, 'msg' => 'Order not found.'];
    if ($order['order_status'] !== 'Delivered')
        return ['ok' => false, 'msg' => 'Order must be Delivered before marking paid.'];
    if ($order['payment_method'] !== 'cod')
        return ['ok' => false, 'msg' => 'Only COD orders need this action.'];

    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'Paid', paid_at = NOW()
        WHERE order_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) return ['ok' => false, 'msg' => 'Failed.'];

    _logActivity('order', $order_id, $actor_id, $actor_type,
        'third_party_payment_received', 'Pending', 'Paid',
        '3rd-party COD payment collected by admin.', $conn);
    _broadcastNotif($order_id,
        "Payment received for Order #{$order['order_code']} via 3rd-party delivery.",
        $order['account_id'], null, $conn);

    return ['ok' => true, 'msg' => 'Payment marked as Paid.'];
}

// ════════════════════════════════════════════════════════════════════════════
//  RIDERS
// ════════════════════════════════════════════════════════════════════════════

function getRidersList(mysqli $conn, bool $available_only = false): array {
    $where = 'r.is_deleted=0' . ($available_only ? ' AND r.is_available=1' : '');
    // Uses renamed: rider_name, rider_phone, account_first_name, account_last_name,
    //               account_email, account_phone, delivery_status
    $sql = "
        SELECT r.rider_id, r.image, r.vehicle_type, r.vehicle_plate_number,
               r.variant_color, r.organization,
               r.rider_phone    AS contact_number,
               r.rider_name,
               r.is_available, r.current_lat, r.current_lng,
               COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS display_name,
               a.account_first_name AS first_name, a.account_last_name AS last_name,
               a.account_phone AS phone_number, a.account_email AS email,
               (SELECT COUNT(*) FROM orders o2 WHERE o2.assigned_rider_id=r.rider_id AND o2.order_status='OutForDelivery') AS active_deliveries
        FROM riders r JOIN accounts a ON a.account_id=r.account_id
        WHERE {$where}
        ORDER BY r.is_available DESC, a.account_first_name ASC
    ";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function updateRiderAvailability(int $rider_id, mysqli $conn): void {
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE assigned_rider_id={$rider_id} AND order_status='OutForDelivery'");
    $cnt = $r ? (int)$r->fetch_assoc()['cnt'] : 0;
    $conn->query("UPDATE riders SET is_available=" . ($cnt === 0 ? 1 : 0) . ", updated_at=NOW() WHERE rider_id={$rider_id}");
}

function getRiderPendingDeliveries(int $rider_account_id, mysqli $conn): array {
    // Uses renamed: delivery_status, recipient_first_name/last_name/address/phone
    $stmt = $conn->prepare("
        SELECT d.delivery_id, d.order_id, d.delivery_status AS status,
               d.assigned_at,
               o.order_code,
               o.recipient_first_name AS first_name,
               o.recipient_last_name  AS last_name,
               o.recipient_address    AS address,
               o.city,
               o.delivery_address,
               o.delivery_latitude, o.delivery_longitude,
               o.total_price,
               o.recipient_phone      AS phone_number,
               o.delivery_notes
        FROM deliveries d
        JOIN riders r ON r.rider_id=d.rider_id
        JOIN orders o ON o.order_id=d.order_id
        WHERE r.account_id=? AND d.delivery_status IN ('pending_acceptance','accepted','picked_up','in_transit')
        ORDER BY d.assigned_at DESC
    ");
    if (!$stmt) return [];
    $stmt->bind_param('i', $rider_account_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ════════════════════════════════════════════════════════════════════════════
//  ORDER QUERIES
// ════════════════════════════════════════════════════════════════════════════

function getOrderRow(int $order_id, mysqli $conn): ?array {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getOrderFull(int $order_id, mysqli $conn): ?array {
    // Uses renamed columns throughout
    $stmt = $conn->prepare("
        SELECT o.*,
               a.account_first_name AS cust_fname,
               a.account_last_name  AS cust_lname,
               a.account_email      AS cust_email,
               a.account_phone      AS cust_phone,
               COALESCE(r.rider_name, CONCAT(ra.account_first_name,' ',ra.account_last_name)) AS rider_name,
               ra.account_first_name AS rider_fname,
               ra.account_last_name  AS rider_lname,
               r.rider_phone         AS rider_direct_phone,
               ra.account_phone      AS rider_acct_phone,
               r.vehicle_type, r.vehicle_plate_number, r.variant_color,
               r.organization, r.image AS rider_image, r.is_available,
               r.current_lat AS rider_lat, r.current_lng AS rider_lng,
               d.delivery_id,
               d.delivery_status,
               d.is_third_party,
               d.third_party_name,
               d.delivery_link AS active_delivery_link,
               d.assigned_at, d.accepted_at, d.picked_up_at, d.delivered_at,
               d.estimated_time, d.estimated_distance,
               p.payment_status, p.gross_amount, p.paid_at,
               p.source_type, p.card_last4
        FROM orders o
        LEFT JOIN accounts a  ON a.account_id=o.account_id
        LEFT JOIN riders r    ON r.rider_id=o.assigned_rider_id
        LEFT JOIN accounts ra ON ra.account_id=r.account_id
        LEFT JOIN (
            SELECT * FROM deliveries d2
            WHERE d2.delivery_status NOT IN ('reassigned','cancelled')
            ORDER BY d2.assigned_at DESC
            LIMIT 18446744073709551615
        ) d ON d.order_id=o.order_id
        LEFT JOIN payments p  ON p.order_id=o.order_id
        WHERE o.order_id=?
        LIMIT 1
    ");
    if (!$stmt) return null;
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getOrderItems(int $order_id, mysqli $conn): array {
    $stmt = $conn->prepare("
        SELECT oi.*, p.product_name, p.product_unit,
               pv.variant_name, pv.unit_type, pv.stock_quantity,
               pi.image_path
        FROM order_items oi
        JOIN products p          ON p.product_id=oi.product_id
        JOIN product_variants pv ON pv.variant_id=oi.variant_id
        LEFT JOIN product_images pi ON pi.product_id=p.product_id AND pi.is_primary=1
        WHERE oi.order_id=?
    ");
    if (!$stmt) return [];
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderHistory(int $order_id, mysqli $conn): array {
    // Uses renamed: account_first_name, account_last_name
    $stmt = $conn->prepare("
        SELECT osh.*,
               a.account_first_name AS first_name,
               a.account_last_name  AS last_name
        FROM order_status_history osh
        LEFT JOIN accounts a ON a.account_id=osh.changed_by_user_id
        WHERE osh.order_id=?
        ORDER BY osh.created_at ASC
    ");
    if (!$stmt) return [];
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderCounts(mysqli $conn): array {
    $result = $conn->query("SELECT order_status, COUNT(*) AS cnt FROM orders WHERE is_deleted=0 GROUP BY order_status");
    $counts = array_fill_keys(array_keys(ORDER_STATUS_FLOW), 0);
    if ($result) while ($row = $result->fetch_assoc()) $counts[$row['order_status']] = (int)$row['cnt'];
    return $counts;
}

// ════════════════════════════════════════════════════════════════════════════
//  NOTIFICATIONS
// ════════════════════════════════════════════════════════════════════════════

function getUnreadNotifications(int $user_id, string $role, mysqli $conn, int $limit = 20): array {
    $stmt = $conn->prepare("SELECT n.*, o.order_code FROM order_notifications n JOIN orders o ON o.order_id=n.order_id WHERE n.target_role=? AND (n.target_user_id=? OR n.target_user_id IS NULL) AND n.is_read=0 ORDER BY n.created_at DESC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param('sii', $role, $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function markNotificationsRead(int $user_id, string $role, mysqli $conn, ?int $order_id = null): void {
    $sql = "UPDATE order_notifications SET is_read=1 WHERE target_role=? AND (target_user_id=? OR target_user_id IS NULL) AND is_read=0";
    $types = 'si'; $args = [$role, $user_id];
    if ($order_id) { $sql .= " AND order_id=?"; $types .= 'i'; $args[] = $order_id; }
    $stmt = $conn->prepare($sql);
    if ($stmt) { $stmt->bind_param($types, ...$args); $stmt->execute(); }
}

function generateReviewInvite(int $order_id, mysqli $conn): ?string {
    $order = getOrderRow($order_id, $conn);
    if (!$order) return null;

    // Must use same algorithm as review.php's generateReviewToken()
    $token     = strtoupper(substr(hash('sha256', 
        $order['order_code'] . $order['recipient_email'] . 'sjfbi_review_2025'
    ), 0, 12));
    
    $baseUrl   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                 . '://' . $_SERVER['HTTP_HOST'];
    $reviewUrl = $baseUrl . '/sjfbi-js/review.php?order=' . urlencode($order['order_code'])
                 . '&token=' . urlencode($token);

    $del = $conn->prepare("DELETE FROM review_invites WHERE order_id = ?");
    $del->bind_param('i', $order_id);
    $del->execute();

    $ins = $conn->prepare("INSERT INTO review_invites (order_id, review_url, sent_at) VALUES (?, ?, NOW())");
    $ins->bind_param('is', $order_id, $reviewUrl);
    $ins->execute();

    return $reviewUrl;
}

// ════════════════════════════════════════════════════════════════════════════
//  PRIVATE HELPERS
// ════════════════════════════════════════════════════════════════════════════

function _updateOrderStatusRaw(int $order_id, string $from, string $to, int $actor_id, string $actor_type, string $notes, mysqli $conn): array {
    $stmt = $conn->prepare("UPDATE orders SET order_status=?, updated_at=NOW() WHERE order_id=? AND order_status=?");
    if (!$stmt) return ['ok' => false, 'msg' => 'DB error.'];
    $stmt->bind_param('sis', $to, $order_id, $from);
    $stmt->execute();
    if ($stmt->affected_rows === 0) return ['ok' => false, 'msg' => "Order status mismatch — expected {$from}."];
    $hist = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by_user_id, changed_by_user_type, notes) VALUES (?,?,?,?,?,?)");
    if ($hist) { $hist->bind_param('issiis', $order_id, $from, $to, $actor_id, $actor_type, $notes); $hist->execute(); }
    return ['ok' => true, 'msg' => "Status updated to {$to}."];
}

function _broadcastNotif(int $order_id, string $msg, ?int $cust_id, ?int $rider_id, mysqli $conn): void {
    _insertNotif($order_id, 'super_admin', null, $msg, $conn);
    _insertNotif($order_id, 'admin', null, $msg, $conn);
    if ($cust_id) _insertNotif($order_id, 'customer', $cust_id, $msg, $conn);
    if ($rider_id) {
        $r = $conn->query("SELECT account_id FROM riders WHERE rider_id={$rider_id} LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) _insertNotif($order_id, 'rider', (int)$row['account_id'], $msg, $conn);
    }
}

function _insertNotif(int $order_id, string $role, ?int $user_id, string $msg, mysqli $conn): void {
    $stmt = $conn->prepare("INSERT INTO order_notifications (order_id, target_role, target_user_id, message) VALUES (?,?,?,?)");
    if ($stmt) { $stmt->bind_param('isis', $order_id, $role, $user_id, $msg); $stmt->execute(); }
}

function _logActivity(string $entity_type, int $entity_id, int $actor_id, string $actor_type, string $action, ?string $old_val, ?string $new_val, ?string $details, mysqli $conn): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $conn->prepare("INSERT INTO activity_log (entity_type, entity_id, user_id, user_type, action, old_value, new_value, details, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('siisssssss', $entity_type, $entity_id, $actor_id, $actor_type, $action, $old_val, $new_val, $details, $ip, $ua); $stmt->execute(); }
}