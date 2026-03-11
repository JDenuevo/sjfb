<?php
session_start();
include '../conn.php';

// Check if the supadmin is logged in as supadmin and account_id exists
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

// Retrieve the logged-in supadmin's account_id
$account_id = $_SESSION['account_id'];

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Items per page

// Get the total count of customers, admins, and riders
$countQuery = "SELECT COUNT(*) as total FROM accounts WHERE role IN ('customer','admin','rider')";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);

// Main query with pagination
$offset = ($page - 1) * $perPage;
$query = "SELECT * FROM accounts WHERE role IN ('customer','admin','rider') LIMIT $perPage OFFSET $offset";
$result = $conn->query($query);

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accounts | St. Joseph Fish Brokerage Inc.</title>

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
    
      <!-- Table Card -->
      <?php include('./components/account_list.php'); ?>
      <!-- Table End -->

    </div>
  </div>
  <!-- End Content -->

  <!-- Add Account Modal -->
  <div id="addAccountModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
    <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
      <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Account</h3>
      <form action="./functions/add.php" method="POST">
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" name="username" required class="w-full px-3 py-2 border rounded-lg" placeholder="Username">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" required class="w-full px-3 py-2 border rounded-lg">
              <option value="" disabled selected>Select a role</option>
              <option value="admin">Admin</option>
              <option value="rider">Rider</option>
              <option value="customer">Customer</option>
              <option value="guest">Guest</option>
            </select>          
          </div>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg" placeholder="Password">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="confirm_password" required class="w-full px-3 py-2 border rounded-lg" placeholder="Confirm Password">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" required class="w-full px-3 py-2 border rounded-lg" placeholder="First Name">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" required class="w-full px-3 py-2 border rounded-lg" placeholder="Last Name">
          </div>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg" placeholder="Email">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Phone Number</label>
          <input type="number" name="phone_number" required class="w-full px-3 py-2 border rounded-lg" placeholder="Phone Number" maxlength="11">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <textarea class="w-full px-3 py-2 border rounded-lg" name="address" required></textarea>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">City</label>
            <input type="text" name="city" required class="w-full px-3 py-2 border rounded-lg" placeholder="City">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Postal Code</label>
            <input type="number" name="postal_code" required class="w-full px-3 py-2 border rounded-lg" placeholder="Postal Code">
          </div>
        </div>
        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 mt-4">
          <button type="submit" name="add_account" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Add Account</button>
          <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('addAccountModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    document.querySelectorAll('[data-modal-target]').forEach(button => {
      button.addEventListener('click', function() {
        const modalId = this.getAttribute('data-modal-target');
        document.getElementById(modalId).classList.remove('hidden');
      });
    });

    function closeModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
    }
  </script>


  <?php $conn->close(); ?>

  <!-- JS Implementing Plugins -->

  <!-- JS PLUGINS -->
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

