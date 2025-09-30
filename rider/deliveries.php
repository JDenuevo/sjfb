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

// ✅ Get delivery history (last 10 completed deliveries, includes guests)
$historyQuery = "SELECT o.*,
        COALESCE(a.first_name, o.first_name) AS first_name,
        COALESCE(a.last_name, o.last_name) AS last_name
    FROM orders o
    LEFT JOIN accounts a ON o.account_id = a.account_id
    WHERE o.assigned_rider_id = ? AND o.order_status = 'Delivered'
    ORDER BY o.order_date DESC 
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
    <title>Deliveries | St. Joseph Fish Brokerage Inc.</title>

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

        <!-- Delivery History -->
        <?php if (!empty($deliveryHistory)): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Recent Deliveries</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($deliveryHistory as $delivery): ?>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Order #<?php echo htmlspecialchars($delivery['order_code']); ?></h3>
                            <p class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($delivery['first_name'] . ' ' . $delivery['last_name']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                Delivered on <?php echo date('M j, Y g:i A', strtotime($delivery['order_date'])); ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Delivered
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- JS PLUGINS -->
    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>