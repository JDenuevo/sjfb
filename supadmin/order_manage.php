<?php
session_start();
include '../conn.php';
include './functions/order_process.php';

// Auth
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['account_id'];

// Validate order ID
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid order ID'];
    header("Location: orders.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// ── Order ─────────────────────────────────────────────────────────────────────
$order_stmt = $conn->prepare("
    SELECT o.*,
           p.payment_status,
           o.payment_method   AS payment_gateway,
           p.gross_amount     AS payment_amount,
           p.paid_at,
           p.refunded_amount,
           r.rider_id,
           ra.first_name      AS rider_first_name,
           ra.last_name       AS rider_last_name,
           ra.phone_number    AS rider_phone,
           r.vehicle_type,
           r.vehicle_plate_number
    FROM orders o
    LEFT JOIN payments p  ON o.order_id = p.order_id
    LEFT JOIN riders r    ON o.assigned_rider_id = r.rider_id
    LEFT JOIN accounts ra ON r.account_id = ra.account_id
    WHERE o.order_id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found'];
    header("Location: orders.php");
    exit;
}
$order = $order_result->fetch_assoc();
$order_stmt->close();

// ── Order items ───────────────────────────────────────────────────────────────
$items_stmt = $conn->prepare("
    SELECT oi.*,
           p.product_name,
           pv.variant_name,
           pv.variant_price
    FROM order_items oi
    LEFT JOIN products p        ON oi.product_id = p.product_id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// ── Available riders ──────────────────────────────────────────────────────────
$available_riders = $conn->query("
    SELECT r.rider_id, r.vehicle_type, r.vehicle_plate_number,
           a.first_name, a.last_name, a.phone_number,
           (SELECT COUNT(*) FROM orders
            WHERE assigned_rider_id = r.rider_id AND order_status = 'OutForDelivery') AS active_orders
    FROM riders r
    JOIN accounts a ON r.account_id = a.account_id
    ORDER BY active_orders ASC, a.first_name, a.last_name
")->fetch_all(MYSQLI_ASSOC);

// ── Order status timeline (order_status_history for this order only) ──────────
$timeline_stmt = $conn->prepare("
    SELECT osh.*,
           a.first_name,
           a.last_name
    FROM order_status_history osh
    LEFT JOIN accounts a ON osh.changed_by_user_id = a.account_id
    WHERE osh.order_id = ?
    ORDER BY osh.created_at ASC
");
$timeline_stmt->bind_param("i", $order_id);
$timeline_stmt->execute();
$order_timeline = $timeline_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$timeline_stmt->close();

// ── Activity log — SCOPED to this order only (entity_type='order', entity_id=order_id) ─
// FIX: previously used getActivityLog() which returned all recent logs.
// Now we query directly so only events for this specific order_id are shown.
$log_stmt = $conn->prepare("
    SELECT al.*,
           CONCAT(a.first_name, ' ', a.last_name) AS actor_name
    FROM activity_log al
    LEFT JOIN accounts a ON al.user_id = a.account_id
    WHERE al.entity_type = 'order'
      AND al.entity_id   = ?
    ORDER BY al.created_at DESC
    LIMIT 50
");
$log_stmt->bind_param("i", $order_id);
$log_stmt->execute();
$activity_log = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$log_stmt->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order — <?= htmlspecialchars($order['order_code']) ?> | St. Joseph Fish Brokerage Inc.</title>
    <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
    <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
    <link href="../output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<body class="bg-gray-50">

    <?php include('./components/header.php'); ?>
    <?php include('./components/sidebar.php'); ?>

    <div class="w-full lg:ps-64">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            <?php if (!empty($_SESSION['message'])):
                $msg  = $_SESSION['message'];
                $cls  = $msg['type'] === 'success' ? 'bg-teal-500' : 'bg-red-500';
                unset($_SESSION['message']);
            ?>
            <div class="mt-2 <?= $cls ?> text-white text-sm rounded-lg p-4" role="alert">
                <span class="font-bold"><?= ucfirst($msg['type']) ?>!</span> <?= htmlspecialchars($msg['text']) ?>
            </div>
            <?php endif; ?>

            <?php include('./components/order_summary.php'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>