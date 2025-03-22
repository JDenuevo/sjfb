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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_account'])) {  
        // Redirect if the user is already logged in (only for registration)
        if (isset($_SESSION['account_id'])) {
            header("Location: ../user/orders.php");
            exit();
        }

        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $role = 'customer'; // Default role for registered customer

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('../register.php', "Invalid email format!");
        }

        // Password validation: At least 8 chars, 1 uppercase, 1 number, 1 special char
        if (strlen($password) < 8 || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[\W]/', $password)) {
            redirectWithMessage('../register.php', "Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character!");
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
        if ($stmt->get_result()->num_rows > 0) {
            redirectWithMessage('../register.php', "Email is already registered!");
        }

        // Check if username already exists
        $check_username = "SELECT * FROM accounts WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
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
            redirectWithMessage('../register.php', "Database error: Please try again later!");
        }

        // Get the inserted user's ID and redirect to details page
        $_SESSION['account_id'] = $stmt->insert_id;
        
        // Close the statement and connection before redirecting
        $stmt->close();
        $conn->close();
        
        redirectWithMessage('../details.php', "Registration successful! Please enter your details.", 'success');
    }

    // Handle complete_order form submission
    elseif (isset($_POST['complete_order'])) { 
        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            redirectWithMessage('../cart.php', "Your cart is empty!");
        }

        // Determine if the user is logged in or a guest
        $userType = isset($_SESSION['account_id']) ? 'customer' : 'guest';

        // Initialize variables for billing details
        $firstName = $lastName = $email = $phoneNumber = $address = $postalCode = $city = null;

        if ($userType === 'customer') {
            // Fetch billing details from the accounts table for logged-in users
            $accountId = $_SESSION['account_id'];
            $stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, postal_code, city FROM accounts WHERE account_id = ?");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userDetails = $result->fetch_assoc();

            if ($userDetails) {
                $firstName = $userDetails['first_name'];
                $lastName = $userDetails['last_name'];
                $email = $userDetails['email'];
                $phoneNumber = $userDetails['phone_number'];
                $address = $userDetails['address'];
                $postalCode = $userDetails['postal_code'];
                $city = $userDetails['city'];
            }
        } else {
            // Get billing details from the form for guest users
            $firstName = $_POST['first_name'] ?? null;
            $lastName = $_POST['last_name'] ?? null;
            $email = $_POST['email'] ?? null;
            $phoneNumber = $_POST['phone_number'] ?? null;
            $address = $_POST['address'] ?? null;
            $postalCode = $_POST['postal_code'] ?? null;
            $city = $_POST['city'] ?? null;

            // Debugging: Log the city value
            error_log("City value from form: " . $city);

            // Validate guest user input
            if (!$firstName || !$lastName || !$email || !$phoneNumber || !$address || !$postalCode || !$city) {
                redirectWithMessage('../checkout.php', "All fields are required for guest checkout!");
            }
        }

        // Get the payment method
        $paymentMethod = $_POST['payment_method'] ?? null;
        if (!$paymentMethod) {
            redirectWithMessage('../checkout.php', "Please select a payment method!");
        }

        // Calculate the total price of the order
        $totalPrice = (float) array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cart));

        // Insert the order into the orders table
        if ($userType === 'customer') {
            // For logged-in customers
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    account_id, user_type, email, phone_number, first_name, last_name, address, postal_code, city, total_price, payment_method
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->bind_param(
                "isssssssdss",
                $_SESSION['account_id'], // Pass account_id for customers
                $userType,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city, // Now treated as a string (s)
                $totalPrice,
                $paymentMethod
            );
        } else {
            // For guest users
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    user_type, email, phone_number, first_name, last_name, address, postal_code, city, total_price, payment_method
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            // Debugging: Log the city value
            error_log("City value before binding: " . $city);

            $stmt->bind_param(
                "ssssssssss", // Updated format string
                $userType,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city, // Now treated as a string (s)
                $totalPrice,
                $paymentMethod
            );
        }

        if (!$stmt->execute()) {
            error_log("MySQL Error: " . $stmt->error);
            redirectWithMessage('../checkout.php', "Something went wrong. Try again!");
        }

        // Get the last inserted order ID
        $orderId = $stmt->insert_id;

        // Insert the order items into the order_items table
        foreach ($cart as $item) {
            // Debugging: Log the values
            error_log("Order ID: " . $orderId);
            error_log("Product ID: " . $item['product_id']);
            error_log("Variant ID: " . $item['variant_id']);
            error_log("Quantity: " . $item['quantity']);
            error_log("Price: " . $item['price']);
            error_log("Discount Price: " . ($item['discount_price'] ?? 0.00));
        
            // Ensure discount_price is properly initialized
            $discountPrice = $item['discount_price'] ?? 0.00;
        
            // Check if variant_id exists in product_variants table
            $checkVariantQuery = "SELECT variant_id FROM product_variants WHERE variant_id = ?";
            $checkStmt = $conn->prepare($checkVariantQuery);
            $checkStmt->bind_param("i", $item['variant_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
        
            if ($checkResult->num_rows === 0) {
                // Variant ID does not exist in product_variants table
                error_log("Invalid variant_id: " . $item['variant_id']);
                redirectWithMessage('../checkout.php', "Invalid product variant. Please try again!");
            }
        
            // Insert into order_items table
            $stmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, quantity, price, discount
                ) VALUES (
                    ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->bind_param(
                "iiiidd",
                $orderId,
                $item['product_id'],
                $item['variant_id'], // Ensure this is valid
                $item['quantity'],
                $item['price'],
                $discountPrice
            );
        
            if (!$stmt->execute()) {
                error_log("MySQL Error: " . $stmt->error);
                redirectWithMessage('../checkout.php', "Something went wrong. Try again!");
            }
        }

        // Clear the cart after successful checkout
        unset($_SESSION['cart']);

        // Redirect to a success page or display a success message
        redirectWithMessage('../order_success.php', "Order placed successfully! Thank you for your purchase.", 'success');
    }
}

$conn->close();
?>