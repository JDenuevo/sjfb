<?php
session_start();
include '../conn.php';

// Auto-login check
if (!isset($_SESSION['account_id'])) {
    require_once '../functions/remember.php';
    validateRememberToken($conn);
}

// Check if the supadmin is logged in as supadmin and account_id exists
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];
$role = $_SESSION['role']; // super_admin, admin, customer, rider

// FIX: $adminName was never set — monitoring.php banner always showed "Super Admin" fallback
$adminNameStmt = $conn->prepare("SELECT account_first_name, account_last_name FROM accounts WHERE account_id = ?");
$adminNameStmt->bind_param("i", $account_id);
$adminNameStmt->execute();
$adminNameRow = $adminNameStmt->get_result()->fetch_assoc();
$adminNameStmt->close();
$adminName = trim(($adminNameRow['account_first_name'] ?? '') . ' ' . ($adminNameRow['account_last_name'] ?? ''));
if (empty($adminName)) $adminName = $_SESSION['username'] ?? 'Admin';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25; // Logs per page
$offset = ($page - 1) * $perPage;

// Count total activity_log
if ($role === 'super_admin') {
    $countQuery = "SELECT COUNT(*) as total FROM activity_log";
    $countResult = $conn->query($countQuery);
    $totalItems = $countResult->fetch_assoc()['total'];

    // Join orders to get order_code if entity_type = 'order'
    $query = "
        SELECT al.*, o.order_code
        FROM activity_log al
        LEFT JOIN orders o ON al.entity_type = 'order' AND al.entity_id = o.order_id
        ORDER BY al.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $conn->prepare($query);

} else {
    $countQuery = "SELECT COUNT(*) as total FROM activity_log WHERE user_id = ?";
    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->bind_param("i", $account_id);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $totalItems = $countResult->fetch_assoc()['total'];
    $stmtCount->close();

    // Join orders for normal users too
    $query = "
        SELECT al.*, o.order_code
        FROM activity_log al
        LEFT JOIN orders o ON al.entity_type = 'order' AND al.entity_id = o.order_id
        WHERE al.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $account_id);
}

$stmt->execute();
$result = $stmt->get_result();
$activity_log = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalPages = ceil($totalItems / $perPage);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
</head>


<body class="bg-gray-50">

  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- Sidebar -->
  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <!-- Monitoring Card Grid -->
      <?php include('./components/monitoring.php'); ?>
      <!-- Monitoring Card End -->

    </div>
  </div>
  <!-- End Content -->
   
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- Apexcharts -->
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://preline.co/assets/js/hs-apexcharts-helpers.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>