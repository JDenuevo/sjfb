<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['add_account'])) {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal']); // Changed from $postal to $postal_code

    // Check if passwords match
    if ($password !== $confirm_password) {
        redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username or email already exists
    $checkQuery = "SELECT * FROM accounts WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        redirectWithMessage("../accounts.php", "Username or Email already exists.", "error");
    }

    // Insert new user (Updated column names)
    $insertQuery = "INSERT INTO accounts (username, role, password_hash, first_name, last_name, email, phone_number, address, city, postal_code) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssssssss", $username, $role, $hashed_password, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code);

    if ($stmt->execute()) {
        redirectWithMessage("../accounts.php", "Account successfully created!", "success");
    } else {
        redirectWithMessage("../accounts.php", "Failed to add account.", "error");
    }
}
?>
