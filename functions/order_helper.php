<?php
// functions/order_helper.php

if (!defined('STATUS_LABELS')) {
    define('STATUS_LABELS', [
        'Paid'           => 'Paid - Awaiting Approval',
        'Pending'        => 'Pending Payment',
        'Processing'     => 'Processing',
        'OutForDelivery' => 'Out for Delivery',
        'Delivered'      => 'Delivered',
        'Cancelled'      => 'Cancelled'
    ]);
}

if (!defined('DELIVERY_STATUS_LABELS')) {
    define('DELIVERY_STATUS_LABELS', [
        'pending_acceptance' => 'Pending Acceptance',
        'accepted'           => 'Accepted',
        'picked_up'          => 'Picked Up',
        'delivered'          => 'Delivered',
        'failed'             => 'Failed',
        'cancelled'          => 'Cancelled'
    ]);
}

/**
 * Generate a unique order code
 */
function generateOrderCode() {
    $prefix = "ORD";
    $date   = date('ymd');
    $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random = '';
    for ($i = 0; $i < 6; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $date . $random;
}

/**
 * Get order status badge HTML
 */
function getOrderStatusBadge($status) {
    $badges = [
        'Paid'           => 'bg-green-100 text-green-800',
        'Pending'        => 'bg-yellow-100 text-yellow-800',
        'Pending Payment'=> 'bg-blue-100 text-blue-800',
        'Processing'     => 'bg-purple-100 text-purple-800',
        'OutForDelivery' => 'bg-indigo-100 text-indigo-800',
        'Delivered'      => 'bg-green-100 text-green-800',
        'Cancelled'      => 'bg-red-100 text-red-800',
        'Payment Failed' => 'bg-red-100 text-red-800'
    ];

    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-2 py-1 text-xs font-medium rounded-full $class'>" . (STATUS_LABELS[$status] ?? $status) . "</span>";
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Get full order details with rider info
 * Uses renamed columns:
 *   deliveries: delivery_status (was status)
 *   riders:     rider_name (was full_name), rider_phone (was contact_number)
 *   accounts:   account_phone (was phone_number)
 */
function getOrderFull($order_id, $conn) {
    $stmt = $conn->prepare("
        SELECT o.*,
               p.payment_status, p.paid_at,
               r.rider_id,
               r.rider_name,
               r.image          AS rider_image,
               r.vehicle_type,
               r.vehicle_plate_number,
               r.variant_color,
               r.organization,
               r.rider_phone    AS rider_direct_phone,
               a.account_phone  AS rider_acct_phone,
               d.delivery_id,
               d.delivery_status,
               d.is_third_party,
               d.third_party_name,
               d.delivery_link  AS active_delivery_link
        FROM orders o
        LEFT JOIN (
            SELECT p1.*
            FROM payments p1
            INNER JOIN (
                SELECT order_id, MAX(created_at) AS max_created
                FROM payments
                GROUP BY order_id
            ) p2 ON p1.order_id = p2.order_id AND p1.created_at = p2.max_created
        ) p ON p.order_id = o.order_id
        LEFT JOIN riders r ON r.rider_id = o.assigned_rider_id
        LEFT JOIN accounts a ON a.account_id = r.account_id
        LEFT JOIN deliveries d ON d.order_id = o.order_id AND d.delivery_id = (
            SELECT delivery_id FROM deliveries WHERE order_id = o.order_id ORDER BY assigned_at DESC LIMIT 1
        )
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

/**
 * Get order items
 */
function getOrderItems($order_id, $conn) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.product_name, pv.variant_name, pv.stock_quantity,
               (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) AS image_path
        FROM order_items oi
        LEFT JOIN products p ON p.product_id = oi.product_id
        LEFT JOIN product_variants pv ON pv.variant_id = oi.variant_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Get order status history
 * Uses renamed columns: account_first_name, account_last_name
 */
function getOrderHistory($order_id, $conn) {
    $stmt = $conn->prepare("
        SELECT h.*,
               CONCAT(a.account_first_name, ' ', a.account_last_name) AS changed_by_name
        FROM order_status_history h
        LEFT JOIN accounts a ON a.account_id = h.changed_by_user_id
        WHERE h.order_id = ?
        ORDER BY h.created_at ASC
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Get delivery proofs
 * Uses renamed column: riders.rider_name (was full_name)
 *                      accounts.account_first_name / account_last_name
 */
function getDeliveryProofs($order_id, $conn) {
    $stmt = $conn->prepare("
        SELECT dp.*,
               COALESCE(r.rider_name, CONCAT(a.account_first_name, ' ', a.account_last_name)) AS rider_name
        FROM delivery_proofs dp
        LEFT JOIN riders r ON r.rider_id = dp.rider_id
        LEFT JOIN accounts a ON a.account_id = r.account_id
        WHERE dp.order_id = ?
        ORDER BY dp.uploaded_at DESC
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Get available riders list
 * Uses renamed columns:
 *   riders:   rider_name (was full_name)
 *   accounts: account_first_name, account_last_name
 *   deliveries: delivery_status (was status)
 */
function getRidersList($conn) {
    $stmt = $conn->prepare("
        SELECT r.rider_id,
               COALESCE(r.rider_name, CONCAT(a.account_first_name, ' ', a.account_last_name)) AS display_name,
               r.vehicle_type,
               r.organization,
               (SELECT COUNT(*) FROM deliveries WHERE rider_id = r.rider_id AND delivery_status = 'pending_acceptance') AS active_deliveries
        FROM riders r
        LEFT JOIN accounts a ON a.account_id = r.account_id
        WHERE r.is_deleted = 0 AND r.is_available = 1
        ORDER BY display_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Get order counts by status
 */
function getOrderCounts($conn) {
    $counts = [];
    $result = $conn->query("
        SELECT order_status, COUNT(*) AS cnt
        FROM orders
        GROUP BY order_status
    ");
    while ($row = $result->fetch_assoc()) {
        $counts[$row['order_status']] = (int)$row['cnt'];
    }
    return $counts;
}
?>