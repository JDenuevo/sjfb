<?php
session_start();
include '../conn.php';
include './functions/order_process.php';

// Check if the admin is logged in as admin and account_id exists
if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$user_id = $_SESSION['account_id'];

// Get order ID from URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid order ID'];
    header("Location: orders.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Get detailed order information
$order_query = "SELECT 
                    o.*,
                    p.payment_status,
                    o.payment_method as payment_gateway,
                    p.gross_amount as payment_amount,
                    p.paid_at,
                    p.refunded_amount,
                    r.rider_id,
                    ra.first_name as rider_first_name,
                    ra.last_name as rider_last_name,
                    ra.phone_number as rider_phone,
                    r.vehicle_type,
                    r.vehicle_plate_number
                FROM orders o
                LEFT JOIN payments p ON o.order_id = p.order_id
                LEFT JOIN riders r ON o.assigned_rider_id = r.rider_id
                LEFT JOIN accounts ra ON r.account_id = ra.account_id
                WHERE o.order_id = ?";

$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found'];
    header("Location: orders.php");
    exit;
}

$order = $order_result->fetch_assoc();

$items_query = "SELECT 
                    oi.*,
                    p.product_name,
                    pv.variant_name,
                    pv.variant_price
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
                WHERE oi.order_id = ?";

$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);

// Get available riders
$available_riders = getAvailableRiders($conn);

// Get order timeline
$order_timeline = getOrderTimeline($conn, $order_id);

// Get activity log
$activity_log = getActivityLog($conn, 'order', $order_id, 20);
$availableRidersQuery = "SELECT r.rider_id, r.vehicle_type, r.vehicle_plate_number,
                                a.first_name, a.last_name, a.phone_number,
                                (SELECT COUNT(*) FROM orders 
                                 WHERE assigned_rider_id = r.rider_id 
                                 AND order_status = 'OutForDelivery') as active_orders
                         FROM riders r 
                         JOIN accounts a ON r.account_id = a.account_id 
                         ORDER BY active_orders ASC, a.first_name, a.last_name";


$availableRidersResult = $conn->query($availableRidersQuery);
$available_riders = $availableRidersResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - <?php echo htmlspecialchars($order['order_code']); ?> | St. Joseph Fish Brokerage Inc.</title>

    <!-- Favicons -->
    <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
    <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link href="../style.css" rel="stylesheet">
    <link href="../output.css" rel="stylesheet">

    <!-- CSS Preline -->
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include('./components/header.php'); ?>
    
    <!-- Sidebar -->
    <?php include('./components/sidebar.php'); ?>

    <!-- Content -->
    <div class="w-full lg:ps-64">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            <?php
                if (!empty($_SESSION['message'])) {
                $message = $_SESSION['message'];
                $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
            
                echo '
                <div class="mt-2 ' . $alertType . ' text-sm rounded-lg p-4" role="alert">
                    <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
                </div>';
            
                // Clear message after displaying it
                unset($_SESSION['message']);
                }
            ?>
            <?php include('./components/order_summary.php'); ?>
        </div>
    </div>

    <!-- Required plugins -->
    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>
                            