<?php
session_start();
require '../conn.php';

// Check if rider is logged in
if (!isset($_SESSION["loggedinasrider"]) || $_SESSION["loggedinasrider"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$account_id = $_SESSION['account_id'];

// Get rider information
$riderQuery = "SELECT r.*, a.first_name, a.last_name, a.email, a.phone_number 
               FROM riders r 
               JOIN accounts a ON r.account_id = a.account_id 
               WHERE r.account_id = ?";
$stmt = $conn->prepare($riderQuery);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$rider = $stmt->get_result()->fetch_assoc();

// ✅ Get active deliveries for this rider (includes guest + customer)
$deliveriesQuery = "
    SELECT 
        o.*,
        COALESCE(a.first_name, o.first_name) AS first_name,
        COALESCE(a.last_name, o.last_name) AS last_name,
        COALESCE(a.phone_number, o.phone_number) AS phone_number,
        COALESCE(a.email, o.email) AS email,
        COALESCE(a.address, o.address) AS address,
        COALESCE(a.city, o.city) AS city,
        COALESCE(a.postal_code, o.postal_code) AS postal_code
    FROM orders o
    LEFT JOIN accounts a ON o.account_id = a.account_id
    WHERE o.assigned_rider_id = ? AND o.order_status = 'OutForDelivery'
    ORDER BY o.order_date DESC
";

$deliveryStmt = $conn->prepare($deliveriesQuery);
$deliveryStmt->bind_param("i", $rider['rider_id']);
$deliveryStmt->execute();
$deliveries = $deliveryStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ✅ Get delivery history (last 10 completed deliveries, includes guests)
$historyQuery = "
    SELECT 
        o.*,
        COALESCE(a.first_name, o.first_name) AS first_name,
        COALESCE(a.last_name, o.last_name) AS last_name
    FROM orders o
    LEFT JOIN accounts a ON o.account_id = a.account_id
    WHERE o.assigned_rider_id = ? AND o.order_status = 'Delivered'
    ORDER BY o.delivered_at DESC 
    LIMIT 10
";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $rider['rider_id']);
$historyStmt->execute();
$deliveryHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard | St. Joseph Fish Brokerage Inc.</title>

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

<body class="bg-gray-100">
    
    <?php include './components/navigation.php'?>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        <!-- Alert Messages -->
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="<?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded border" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['message']['text']; ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="rounded-full bg-blue-100 p-3 mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Deliveries</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($deliveries); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="rounded-full bg-green-100 p-3 mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Today</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                                $today = date('Y-m-d');
                                $todayCount = 0;
                                foreach ($deliveryHistory as $delivery) {
                                    if (date('Y-m-d', strtotime($delivery['delivered_at'])) === $today) {
                                        $todayCount++;
                                    }
                                }
                                echo $todayCount;
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="rounded-full bg-orange-100 p-3 mr-4">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Status</p>
                        <p class="text-lg font-bold <?php echo $rider['is_available'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $rider['is_available'] ? 'Available' : 'Busy'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Deliveries -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Active Deliveries</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if (empty($deliveries)): ?>
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No active deliveries</h3>
                        <p class="mt-1 text-sm text-gray-500">You don't have any deliveries assigned at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($deliveries as $delivery): ?>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Order #<?php echo htmlspecialchars($delivery['order_code']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($delivery['first_name'] . ' ' . $delivery['last_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($delivery['phone_number']); ?></p>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo htmlspecialchars($delivery['address'] . ', ' . $delivery['city'] . ' ' . $delivery['postal_code']); ?>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <form action="./functions/order_process.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $delivery['order_id']; ?>">
                                    <input type="hidden" name="notes" value="Delivered by rider">
                                    <button type="submit" name="mark_delivered" 
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            onclick="return confirm('Mark order #<?php echo $delivery['order_code']; ?> as delivered?')">
                                        Mark Delivered
                                    </button>
                                </form>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($delivery['address'] . ', ' . $delivery['city'] . ' ' . $delivery['postal_code']); ?>" 
                                   target="_blank"
                                   class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                    Directions
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JS PLUGINS -->
    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>