<?php
session_start();
include '../conn.php';

// Check if the admin is logged in as admin and account_id exists
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

// Retrieve the logged-in admin's account_id
$account_id = $_SESSION['account_id'];

// Pagination setup
$itemsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Base query
$whereConditions = [];
$params = [];
$types = "";

// Payment Status Filter
if (!empty($_GET['payment_status'])) {
    $whereConditions[] = "p.payment_status = ?";
    $params[] = $_GET['payment_status'];
    $types .= "s";
}

// Search Filter
if (!empty($_GET['search'])) {
    $whereConditions[] = "(o.order_code LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ? OR p.provider_id LIKE ? OR p.billing_email LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "sssss";
}

// Build WHERE clause
$whereSQL = "";
if (count($whereConditions) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereConditions);
}

// Count total records
$countSql = "
    SELECT COUNT(*) as total 
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    $whereSQL
";

$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$countResult = $stmt->get_result()->fetch_assoc();
$totalItems = $countResult['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$stmt->close();

// Main query with pagination
$sql = "
    SELECT p.*, o.order_code, o.first_name, o.last_name
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    $whereSQL
    ORDER BY p.created_at DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);

// Add pagination params
$paramsWithPagination = $params; 
$typesWithPagination = $types . "ii";
$paramsWithPagination[] = $offset;
$paramsWithPagination[] = $itemsPerPage;

if ($types) {
    $stmt->bind_param($typesWithPagination, ...$paramsWithPagination);
} else {
    $stmt->bind_param("ii", $offset, $itemsPerPage);
}

$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
</head>

<style>
  .modal-overlay {
    position: fixed; inset: 0; z-index: 999;
    display: flex; align-items: flex-start; justify-content: center;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    overflow-y: auto;
    padding: 2rem 1rem;
  }
  .modal-overlay.hidden { display: none; }

  .modal-box {
    background: white;
    width: 100%; max-width: 56rem;
    border-radius: 1.25rem;
    box-shadow: 0 25px 60px rgba(0,0,0,0.2);
    overflow: hidden;
  }

  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
  }
  .modal-header h3 { font-size: 1.125rem; font-weight: 700; color: #111827; }
  .modal-header p { font-size: 0.75rem; color: #6b7280; margin-top: 1px; }

  .modal-close {
    width: 2rem; height: 2rem;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; background: #f3f4f6;
    color: #6b7280; border: none; cursor: pointer;
    transition: background 0.15s, color 0.15s;
  }
  .modal-close:hover { background: #fee2e2; color: #dc2626; }

  .modal-body { padding: 1.5rem; max-height: 75vh; overflow-y: auto; }
  .modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex; justify-content: flex-end; gap: 0.625rem;
  }
</style>
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
      
      <!-- End Select -->
    
      <!-- Payment List Start -->
      <?php include('./components/payment_list.php'); ?>
      <!-- Payment List End -->

    </div>
  </div>
  <!-- End Content -->
   
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  
</body>
</html>