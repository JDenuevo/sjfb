<?php
session_start();
include '../conn.php'; // Adjust the path to your actual file

// Set the time zone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Function to redirect with a message
function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

if (isset($_POST['update_new_account'])) {
    $account_id = $_SESSION['account_id'];
    $phone_number = trim($_POST['phone_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $postal_code = trim($_POST['postal_code']);
    $city = trim($_POST['city']);
  
    // Update account details query
    $update_query = "UPDATE accounts SET phone_number = ?, first_name = ?, last_name = ?, address = ?, postal_code = ?, city = ? WHERE account_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssi", $phone_number, $first_name, $last_name, $address, $postal_code, $city, $account_id);
  
    if ($stmt->execute()) {
      $_SESSION['success'] = "Details updated successfully!";
      header("Location: details.php"); // Redirect to the same page to display the message
      exit();
    } else {
        $_SESSION['error'] = "Failed to update details. Try again!";
        header("Location: details.php"); // Redirect back with error message
        exit();
    }
  
    $stmt->close();
    $conn->close();
  }
?>
