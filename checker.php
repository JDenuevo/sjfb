<?php
session_start();
include 'conn.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store errors
    $errors = [];

   // Sanitize and validate inputs
    $input_username = strip_tags($_POST["username"]); // Remove any HTML tags
    $input_password = $_POST["password"]; // Passwords should not be sanitized as they are hashed


    if (empty($input_username)) {
        $errors[] = "Username is required";
    }

    if (empty($input_password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        // Prepare and execute the query
        $stmt = $conn->prepare("SELECT accountID, username, password, role FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $accountID = $row['accountID'];
            $username = $row['username'];
            $password = $row['password'];
            $role = $row['role'];

            if (password_verify($input_password, $password)) {
                $_SESSION['accountID'] = $accountID;

                // Reordered the roles to process User first
                switch ($role) {
                    case 'admin':
                        $_SESSION["loggedinasadmin"] = true;
                        header("Location: /admin/dashboard.php");
                        break;
                    case 'user':
                        $_SESSION["loggedinasuser"] = true;
                        header("Location: /user/orders.php");
                        break;
                    case 'Staff':
                        $_SESSION["loggedinasstaff"] = true;
                        header("Location: pages/staff/dashboard.php");
                        break;
                    default:
                        $error_message = "<p style='color: red;'>Unexpected user role: " . $role . "</p>";
                }
                exit();
            } else {
                $error_message = "<p style='color: red;'>Invalid username or password.</p>";
            }
        } else {
            $error_message = "<p style='color: red;'>Invalid username or password.</p>";
        }
    } else {
        $error_message = "<p style='color: red;'>" . implode("<br>", $errors) . "</p>";
    }
}
?>
