<?php
session_start();
include '../conn.php';

// Check if the supadmin is logged in as supadmin and account_id exists
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

// Retrieve the logged-in customer's account_id
$account_id = $_SESSION['account_id'];

// Query to fetch only the logged-in user's account info
$query = "SELECT * FROM accounts WHERE account_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile | St. Joseph Fish Brokerage Inc.</title>

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

  <style>
    /* Import products.php design language */
    .form-label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.375rem;
    }

    .form-input {
      width: 100%;
      padding: 0.5rem 0.75rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      font-size: 0.875rem;
      color: #111827;
      transition: border-color 0.15s, box-shadow 0.15s;
      outline: none;
      background-color: white;
    }

    .form-input:focus {
      border-color: #ea580c;
      box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
    }

    .form-input[readonly] {
      background-color: #f9fafb;
      cursor: not-allowed;
    }

    .section-title {
      font-size: 0.9375rem;
      font-weight: 700;
      color: #111827;
      border-left: 3px solid #ea580c;
      padding-left: 0.625rem;
      margin: 1.25rem 0 0.75rem;
    }

    .btn-primary {
      padding: 0.5rem 1.25rem;
      background: #ea580c;
      color: white;
      border-radius: 0.625rem;
      border: none;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary:hover {
      background: #c2410c;
    }

    .btn-primary:active {
      transform: scale(0.97);
    }

    .btn-secondary {
      padding: 0.5rem 1.25rem;
      background: white;
      color: #374151;
      border-radius: 0.625rem;
      border: 1px solid #e5e7eb;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.15s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-secondary:hover {
      background: #f9fafb;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-green {
      background-color: #dcfce7;
      color: #166534;
    }

    .badge-yellow {
      background-color: #fef3c7;
      color: #92400e;
    }

    /* Profile card styles */
    .profile-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 1rem;
      overflow: hidden;
      transition: all 0.2s ease;
    }

    .profile-card:hover {
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .stat-card {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 0.75rem;
      padding: 1rem;
      transition: all 0.2s ease;
    }

    .stat-card:hover {
      background-color: white;
      border-color: #ea580c;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Alert styles */
    .alert {
      border-radius: 0.75rem;
      padding: 1rem;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-success {
      background-color: #14b8a6;
      color: white;
    }

    .alert-error {
      background-color: #ef4444;
      color: white;
    }
  </style>
</head>

<body class="bg-gray-50">

  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- Sidebar -->
  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      <?php include('./components/manage_profile.php'); ?>
    </div>
  </div>
  <!-- End Content -->

  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  
  <!-- Form validation script -->
  <script>
    // Password match validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
      const password = document.querySelector('input[name="password"]').value;
      const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
      
      if (password || confirmPassword) {
        if (password !== confirmPassword) {
          e.preventDefault();
          alert('Passwords do not match!');
        }
      }
    });
  </script>
</body>
</html>