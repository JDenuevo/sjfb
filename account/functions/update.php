<?php
session_start();
include '../conn.php';   // Use relative path consistent with your structure

// Security check
if (!isset($_SESSION['loggedinasuser']) || $_SESSION['loggedinasuser'] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$account_id = $_SESSION['account_id'];

function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    // Get and sanitize input
    $username       = trim($_POST['username'] ?? '');
    $first_name     = trim($_POST['account_first_name'] ?? '');   // Match DB column
    $last_name      = trim($_POST['account_last_name'] ?? '');
    $email          = trim($_POST['account_email'] ?? '');
    $phone          = trim($_POST['account_phone'] ?? '');
    $address        = trim($_POST['account_address'] ?? '');
    $postal_code    = trim($_POST['postal_code'] ?? '');
    $city           = trim($_POST['city'] ?? '');

    $password       = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email)) {
        redirectWithMessage('../profile.php', 'Please fill in all required fields.', 'error');
    }

    // Email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('../profile.php', 'Invalid email format.', 'error');
    }

    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE account_email = ? AND account_id != ?");
    $stmt->bind_param("si", $email, $account_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectWithMessage('../profile.php', 'This email is already taken by another account.', 'error');
    }
    $stmt->close();

    // Check if username is already taken by another user
    $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? AND account_id != ?");
    $stmt->bind_param("si", $username, $account_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirectWithMessage('../profile.php', 'This username is already taken.', 'error');
    }
    $stmt->close();

    // Password handling (only if user wants to change it)
    $password_update = '';
    $types = "sssssssss";
    $params = [$username, $first_name, $last_name, $email, $phone, $address, $postal_code, $city, $account_id];

    if (!empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            redirectWithMessage('../profile.php', 'Passwords do not match.', 'error');
        }
        if (strlen($password) < 6) {
            redirectWithMessage('../profile.php', 'Password must be at least 6 characters.', 'error');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $password_update = ", password_hash = ?";
        $types = "sssssssssi";
        $params = [$username, $first_name, $last_name, $email, $phone, $address, $postal_code, $city, $hashedPassword, $account_id];
    }

    // Build and execute update query
    $sql = "UPDATE accounts 
            SET username = ?, 
                account_first_name = ?, 
                account_last_name = ?, 
                account_email = ?, 
                account_phone = ?, 
                account_address = ?, 
                postal_code = ?, 
                city = ? 
                $password_update 
            WHERE account_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        redirectWithMessage('../profile.php', 'Profile updated successfully!', 'success');
    } else {
        redirectWithMessage('../profile.php', 'Failed to update profile. Please try again.', 'error');
    }

    $stmt->close();
    $conn->close();
} else {
    // If someone accesses the file directly without POST
    header("Location: ../profile.php");
    exit();
}
?>