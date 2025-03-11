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

$query = "SELECT * FROM accounts";
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
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

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
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
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
        <li class="flex items-center text-sm text-gray-800">
          Application Layout
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate" aria-current="page">
          Accounts
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
    
      <!-- Table Card -->
      <?php include('./components/account_list.php'); ?>
      <!-- Table End -->

    </div>
  </div>
  <!-- End Content -->

  <!-- Add Account Modal -->
  <div id="addAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all scale-95 hover:scale-100">
      <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Account</h3>
      <form action="./functions/add.php" method="POST">
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" name="username" required class="w-full px-3 py-2 border rounded-lg">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" required class="w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="" disabled selected>Select a role</option>
              <option value="admin">Admin</option>
              <option value="customer">Customer</option>
              <option value="guest">Guest</option>
            </select>          
          </div>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="confirm_password" required class="w-full px-3 py-2 border rounded-lg">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" required class="w-full px-3 py-2 border rounded-lg">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" required class="w-full px-3 py-2 border rounded-lg">
          </div>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Phone Number</label>
          <input type="number" name="phone_number" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700">Address</label>
          <textarea class="w-full px-3 py-2 border rounded-lg" name="address" required></textarea>
        </div>
        <div class="grid grid-cols-2 gap-x-2">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">City</label>
            <input type="text" name="city" required class="w-full px-3 py-2 border rounded-lg">
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">Postal Code</label>
            <input type="number" name="postal" required class="w-full px-3 py-2 border rounded-lg">
          </div>
        </div>
        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 mt-4">
          <button type="submit" name="add_account" class="px-4 py-2 w-full bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Add Account</button>
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

