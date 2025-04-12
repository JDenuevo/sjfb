<?php
session_start();
include '../conn.php';

// Check if the customer is logged in
if (!isset($_SESSION['loggedinasuser']) || $_SESSION['loggedinasuser'] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// Get orders for this customer
$query = "SELECT 
            o.order_id, 
            o.order_date, 
            o.total_price, 
            o.order_status,
            o.payment_method,
            COUNT(oi.order_item_id) as item_count
          FROM orders o
          LEFT JOIN order_items oi ON o.order_id = oi.order_id
          WHERE o.account_id = ?
          GROUP BY o.order_id
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders | St. Joseph Fish Brokerage Inc.</title>

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
  <?php include('./components/navigation.php'); ?>

  
  <!-- Content -->
  <div class="w-full">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      <!-- Monitoring Card Grid -->
        <?php include('./components/order_list.php'); ?>
      <!-- Monitoring Card End -->

    </div>
  </div>
  <!-- End Content -->

</body>
</html>