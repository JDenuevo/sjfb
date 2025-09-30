<?php
session_start();
include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Sanitize inputs
    $input_username = strip_tags(trim($_POST["username"] ?? ''));
    $input_password = $_POST["password"] ?? '';

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
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Redirect based on role
                if ($row['role'] === 'customer') {
                    $_SESSION["loggedinasuser"] = true;
                    header("Location: ../user/products.php");
                    exit();
                } elseif ($row['role'] === 'admin') {
                    $_SESSION["loggedinasadmin"] = true;
                    header("Location: ../admin/dashboard.php");
                    exit();
                } elseif ($row['role'] === 'super_admin') {
                    $_SESSION["loggedinassupadmin"] = true;
                    header("Location: ../supadmin/dashboard.php");
                    exit();
                } elseif ($row['role'] === 'rider') {
                    $_SESSION["loggedinasrider"] = true;
                    header("Location: ../rider/dashboard.php");
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

    // Redirect to login page with modal open
    header("Location: ../?showModal=true");
    exit();
}
?>
