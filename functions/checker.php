<?php
session_start();
include '../conn.php';
require_once 'remember.php';

// Get the base URL
$baseUrl = '/sjfbi-js/';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Sanitize inputs
    $input_username = strip_tags(trim($_POST["username"] ?? ''));
    $input_password = $_POST["password"] ?? '';
    $remember_me = isset($_POST["remember-me"]);

    // Basic validation
    if (empty($input_username)) {
        $errors[] = "Username is required.";
    }
    if (empty($input_password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT account_id, username, password_hash, role FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($input_password, $row['password_hash'])) {
                // Set session variables
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Create remember me token if checkbox was checked
                if ($remember_me) {
                    createRememberToken($conn, $row['account_id']);
                } else {
                    // Clear any existing remember me token
                    deleteRememberToken($conn);
                    clearRememberCookie();
                }

                // Clean up expired tokens
                cleanupExpiredTokens($conn);

                // Redirect based on role
                if ($row['role'] === 'customer') {
                    $_SESSION["loggedinasuser"] = true;
                    header("Location: {$baseUrl}user/products.php");
                    exit();
                } elseif ($row['role'] === 'admin') {
                    $_SESSION["loggedinasadmin"] = true;
                    header("Location: {$baseUrl}admin/dashboard.php");
                    exit();
                } elseif ($row['role'] === 'super_admin') {
                    $_SESSION["loggedinassupadmin"] = true;
                    header("Location: {$baseUrl}supadmin/dashboard.php");
                    exit();
                } elseif ($row['role'] === 'rider') {
                    $_SESSION["loggedinasrider"] = true;
                    header("Location: {$baseUrl}rider/dashboard.php");
                    exit();
                } else {
                    $_SESSION['error_message'] = "Unknown user role.";
                }
            } else {
                $_SESSION['error_message'] = "Invalid username or password.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid username or password.";
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }

    // Redirect to homepage with modal open
    header("Location: {$baseUrl}?showModal=true");
    exit();
}
?>