<?php
session_start();
include('db_connection.php');  // Make sure you have your DB connection here.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form input values
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user exists in the database
    $sql = "SELECT * FROM accounts WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, verify password
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, log the user in
            $_SESSION['user_id'] = $user['account_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to homepage or dashboard
            header("Location: ./dashboard.php");
            exit();
        } else {
            // Incorrect password
            $error = "Invalid email or password.";
        }
    } else {
        // User not found
        $error = "No account found with that email.";
    }

    // Close the statement
    $stmt->close();
}
?>

<!-- Display the error if any -->
<?php if (isset($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>
