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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    try {
        $cart = $_SESSION['cart'] ?? [];
        
        if (empty($cart)) {
            throw new Exception("Your cart is empty!");
        }

        // Get form data with proper sanitization
        $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $postalCode = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

        // Validate required fields
        $required = [
            'First Name' => $firstName,
            'Last Name' => $lastName,
            'Email' => $email,
            'Phone Number' => $phoneNumber,
            'Address' => $address,
            'Postal Code' => $postalCode,
            'City' => $city,
            'Payment Method' => $paymentMethod
        ];

        foreach ($required as $field => $value) {
            if (empty(trim($value))) {
                throw new Exception("$field is required");
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Calculate total price
        $totalPrice = 0;
        foreach ($cart as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        if ($totalPrice <= 0) {
            throw new Exception("Invalid order total");
        }

        // Start transaction
        $conn->begin_transaction();

        // Determine user type and prepare query
        $userType = isset($_SESSION['account_id']) ? 'customer' : 'guest';
        $accountId = $_SESSION['account_id'] ?? null;

        if ($userType === 'customer' && empty($accountId)) {
            throw new Exception("Customer session is invalid - please login again");
        }

        // Insert order - matches your database schema
        $orderQuery = $userType === 'customer' 
            ? "INSERT INTO orders (
                account_id, user_type, email, phone_number, first_name, last_name, 
                address, postal_code, city, total_price, payment_method, order_status, order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
            : "INSERT INTO orders (
                user_type, email, phone_number, first_name, last_name, 
                address, postal_code, city, total_price, payment_method, order_status, order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

        $stmt = $conn->prepare($orderQuery);
        
        if ($userType === 'customer') {
            $stmt->bind_param(
                "isssssssdss",
                $accountId,
                $userType,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city,
                $totalPrice,
                $paymentMethod
            );
        } else {
            $stmt->bind_param(
                "sssssssdss",
                $userType,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city,
                $totalPrice,
                $paymentMethod
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }

        $orderId = $conn->insert_id;

        // Insert order items
        foreach ($cart as $item) {
            $discountPrice = $item['discount_price'] ?? 0.00;
            
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, quantity, price, discount
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $itemStmt->bind_param(
                "iiiidd",
                $orderId,
                $item['product_id'],
                $item['variant_id'],
                $item['quantity'],
                $item['price'],
                $discountPrice
            );

            if (!$itemStmt->execute()) {
                throw new Exception("Failed to add order item: " . $itemStmt->error);
            }
        }

        // Commit transaction
        $conn->commit();

        // Clear cart and set success data
        unset($_SESSION['cart']);
        $_SESSION['order_id'] = $orderId;
        $_SESSION['success'] = "Order placed successfully! Thank you for your purchase.";

        // Redirect to success page
        header("Location: ../order_success.php");
        exit();

    } catch (Exception $e) {
        // Roll back on error
        if (isset($conn) && $conn) {
            $conn->rollback();
        }
        
        error_log("Checkout error: " . $e->getMessage());
        redirectWithMessage('../checkout.php', "Checkout failed: " . $e->getMessage());
    }
}

$conn->close();
?>