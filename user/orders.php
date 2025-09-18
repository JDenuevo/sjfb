<?php
session_start();
include '../conn.php';

// Check if the customer is logged in
if (!isset($_SESSION['loggedinasuser']) || $_SESSION['loggedinasuser'] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// Add filter handling for user's orders only
$whereConditions = ["o.account_id = ?"];
$params = [$account_id];
$types = "i";

// Handle status filter
if (isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'All Statuses') {
    $whereConditions[] = "o.order_status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Handle search
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(o.order_code LIKE ? OR CONCAT(o.first_name, ' ', o.last_name) LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Build the WHERE clause
$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Pagination variables
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM orders o " . $whereClause;
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalItems / $perPage);

// Main query with pagination - only user's orders
$query = "SELECT
    o.order_id,
    o.order_code,
    o.order_date,
    o.order_status,
    o.total_price,
    o.payment_method,
    o.first_name,
    o.last_name,
    o.address,
    o.city,
    o.postal_code,
    o.email,
    p.payment_status,
    p.paid_at
FROM orders o
LEFT JOIN payments p 
    ON p.order_id = o.order_id
    AND p.payment_id = (
        SELECT MAX(p2.payment_id) 
        FROM payments p2 
        WHERE p2.order_id = o.order_id
    )
" . $whereClause . "
ORDER BY o.order_date DESC
LIMIT ? OFFSET ?";

// Add limit and offset to params
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
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
      <!-- Orders List -->
      <?php include('./components/order_list.php'); ?>
      <!-- End Orders List -->

    </div>
  </div>
  <!-- End Content -->

  <!-- JS PLUGINS -->
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  
</body>
</html>