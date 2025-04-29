<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['update_profile'])) {
    $account_id = $_SESSION['account_id'];
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ Check if email already exists in another account
    $checkEmail = $conn->prepare("SELECT account_id FROM accounts WHERE email = ? AND account_id != ?");
    $checkEmail->bind_param("si", $email, $account_id);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        $conn->close();
        redirectWithMessage('../profile.php', 'Email is already taken by another account.', 'error');
    }
    $checkEmail->close();

    // ✅ Check if username already exists in another account
    $checkUsername = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? AND account_id != ?");
    $checkUsername->bind_param("si", $username, $account_id);
    $checkUsername->execute();
    $checkUsername->store_result();

    if ($checkUsername->num_rows > 0) {
        $checkUsername->close();
        $conn->close();
        redirectWithMessage('../profile.php', 'Username is already taken by another account.', 'error');
    }
    $checkUsername->close();

    // ✅ Handle password update only if provided
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            redirectWithMessage('../profile.php', 'Password and Confirm Password do not match.', 'error');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE accounts 
                SET username = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ?, password_hash = ? 
                WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $username, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $hashedPassword, $account_id);
    } else {
        $sql = "UPDATE accounts 
                SET username = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ? 
                WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $username, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $account_id);
    }

    if ($stmt->execute()) {
        redirectWithMessage('../profile.php', 'Account updated successfully.', 'success');
    } else {
        redirectWithMessage('../profile.php', 'Failed to update account.', 'error');
    }

    $stmt->close();
    $conn->close();
}
?>
