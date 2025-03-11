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

// Redirect if the user is already logged in
if (isset($_SESSION['account_id'])) {
    header("Location: ../user/orders.php"); // Adjust the destination as needed
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_account'])) {  
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $role = 'customer'; // Default role for registered customer

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('../register.php', "Invalid email format!");
        }

        // Password validation: At least 8 chars, 1 uppercase letter, 1 number
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            redirectWithMessage('../register.php', "Password must be at least 8 characters long, contain at least one uppercase letter and one number!");
        }

        // Confirm password match
        if ($password !== $confirm_password) {
            redirectWithMessage('../register.php', "Passwords do not match!");
        }

        // Check if email already exists
        $check_query = "SELECT * FROM accounts WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            redirectWithMessage('../register.php', "Email is already registered!");
        }

        // Check if username already exists
        $check_username = "SELECT * FROM accounts WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            redirectWithMessage('../register.php', "Username is already taken!");
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user data into accounts table
        $insert_query = "INSERT INTO accounts (email, username, password_hash, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $email, $username, $hashed_password, $role);

        if (!$stmt->execute()) {
            error_log("MySQL Error: " . $stmt->error);
            redirectWithMessage('../register.php', "Something went wrong. Try again!");
        }

        // Get the inserted user's ID and redirect to details page
        $_SESSION['account_id'] = $stmt->insert_id;
        redirectWithMessage('../details.php', "Registration successful! Please enter your details.", 'success');

        $stmt->close();
        $conn->close();
    } 
    
    elseif (isset($_POST['add_to_cart'])) { // If the button clicked is "Add to Cart"
        $product_id = intval($_POST['product_id']);
        $product_name = htmlspecialchars($_POST['product_name'], ENT_QUOTES, 'UTF-8');
        $product_description = htmlspecialchars($_POST['product_description'], ENT_QUOTES, 'UTF-8');
        $product_price = floatval($_POST['product_price']);
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; // Get user-inputted quantity, default to 1
    
        // Initialize the cart session if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    
        // Check if the product is already in the cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) {
                $item['quantity'] += $quantity; // Increase quantity based on user input
                $found = true;
                break;
            }
        }
    
        // If the product is not in the cart, add it
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'product_name' => $product_name,
                'product_description' => $product_description,
                'product_price' => $product_price,
                'quantity' => $quantity
            ];
        }
    
        // Redirect back to the product page with a success message
        redirectWithMessage('../products.php', "Product added to cart!", 'success');
    }
}
?>
