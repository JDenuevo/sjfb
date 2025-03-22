<?php
session_start();
include '../conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $input_username = strip_tags($_POST["username"]);
    $input_password = $_POST["password"];

    if (empty($input_username)) {
        $errors[] = "Username is required";
    }
    if (empty($input_password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT account_id, username, password_hash, role FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($input_password, $row['password_hash'])) {
                $_SESSION['account_id'] = $row['account_id'];

                switch ($row['role']) {
                    case 'customer':
                        $_SESSION["loggedinasuser"] = true;
                        header("Location: ../user/orders.php");
                        exit();
                    case 'admin':
                        $_SESSION["loggedinasadmin"] = true;
                        header("Location: ../admin/dashboard.php");
                        exit();
                    case 'super_admin':
                        $_SESSION["loggedinassupadmin"] = true;
                        header("Location: ../supadmin/dashboard.php");
                        exit();
                }
            } else {
                $_SESSION['error_message'] = "Invalid username or password.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid username or password.";
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }

    header("Location: ../index.php?showModal=true");
    exit();
}
?>
