<?php
session_start();
include '../conn.php';

// Check if the supadmin is logged in
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

// Retrieve the logged-in super admin's account_id
$account_id = $_SESSION['account_id'];

// Get available accounts that can become riders (not already riders and not admin/super_admin)
$availableAccountsQuery = "SELECT a.account_id, a.first_name, a.last_name, a.email, a.role
                          FROM accounts a 
                          LEFT JOIN riders r ON a.account_id = r.account_id 
                          WHERE r.account_id IS NULL 
                          AND a.role NOT IN ('admin', 'super_admin' 'customer')
                          ORDER BY a.first_name, a.last_name";
$availableAccountsResult = $conn->query($availableAccountsQuery);
$availableAccounts = $availableAccountsResult->fetch_all(MYSQLI_ASSOC);

// Get all riders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countQuery = "SELECT COUNT(*) as total FROM riders";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);

$query = "SELECT r.*, a.first_name, a.last_name, a.email, a.phone_number 
          FROM riders r 
          JOIN accounts a ON r.account_id = a.account_id 
          ORDER BY r.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$result = $conn->query($query);
$riders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Management | St. Joseph Fish Brokerage Inc.</title>

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

            <?php include './components/rider_list.php'?>
           

        </div>
    </div>


      <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>