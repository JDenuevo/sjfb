<?php
session_start();
include './conn.php'; // Adjust the path to your actual file

// Set the time zone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Get the current date and time
$date = new DateTime();
$formattedDate = date('Y-m-d H:i:s'); // Correct format for inserting into a datetime field

// Function to redirect with a message
function redirectWithMessage($location, $message) {
    $_SESSION['message'] = $message;
    header("Location: $location");
    exit(); // Terminate the script
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {  // If the form is for registration
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $role = 'user'; // Default role

        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match!";
            header("Location: ./register.php");
            exit();
        }

        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Email is already registered!";
            header("Location: ./register.php");
            exit();
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user data including username
        $insert_query = "INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $email, $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ./login.php");
        } else {
            $_SESSION['error'] = "Something went wrong. Try again!";
            header("Location: ./register.php");
        }

        $stmt->close();
        $conn->close();
    } elseif (isset($_POST['add_to_cart'])) { // If the button clicked is "Add to Cart"
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $product_description = $_POST['product_description'];
        $product_price = $_POST['product_price'];
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; // Get user-inputted quantity, default to 1
    
        // Initialize the cart session if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    
        // Check if the product is already in the cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
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
        $_SESSION['success'] = "Product added to cart!";
        header("Location: ./products.php");
        exit();
    }
}
?>
