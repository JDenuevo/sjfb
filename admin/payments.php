<?php
session_start();
include '../conn.php';

// Check if the admin is logged in as admin and account_id exists
if (!isset($_SESSION["loggedinasadmin"]) || $_SESSION["loggedinasadmin"] !== true || !isset($_SESSION['account_id'])) {
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
  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">

  <!-- CSS Preline -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>


<body class="bg-gray-50">
  
  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- ========== MAIN CONTENT ========== -->
  <!-- Breadcrumb -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <!-- Navigation Toggle -->
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none " aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
        <span class="sr-only">Toggle Navigation</span>
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2" />
          <path d="M15 3v18" />
          <path d="m8 9 3 3-3 3" />
        </svg>
      </button>
      <!-- End Navigation Toggle -->

      <!-- Breadcrumb -->
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800 ">
          Application Layout
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400 " width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate " aria-current="page">
          Payments
        </li>
      </ol>
      <!-- End Breadcrumb -->
    </div>
  </div>
  <!-- End Breadcrumb -->

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

  <!-- JS PLUGINS -->
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- Preline JS -->
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  
</body>
</html>