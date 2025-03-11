<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['update_account'])) {
    $account_id = $_POST['account_id'];
    $username = trim(htmlspecialchars($_POST['username']));
    $role = htmlspecialchars($_POST['role']);
    $first_name = trim(htmlspecialchars($_POST['first_name']));
    $last_name = trim(htmlspecialchars($_POST['last_name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $phone_number = trim(htmlspecialchars($_POST['phone_number']));
    $address = trim(htmlspecialchars($_POST['address']));
    $city = trim(htmlspecialchars($_POST['city']));
    $postal_code = trim(htmlspecialchars($_POST['postal_code']));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage("../accounts.php", "Invalid email format.", "error");
    }

    // Handle password update only if a new password is provided
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
        }

        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update query with password
        $updateQuery = "UPDATE accounts SET username = ?, role = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ?, password = ? WHERE account_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssssssssi", $username, $role, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $hashed_password, $account_id);
    } else {
        // Update query without password
        $updateQuery = "UPDATE accounts SET username = ?, role = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ? WHERE account_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssssi", $username, $role, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $account_id);
    }

    // Execute the update query
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        redirectWithMessage("../accounts.php", "Account successfully updated!", "success");
    } else {
        $stmt->close();
        $conn->close();
        redirectWithMessage("../accounts.php", "Failed to update account.", "error");
    }
}
?>
