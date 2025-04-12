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

    elseif (isset($_POST['complete_order'])) { 
        
    try {
        // Verify cart exists and has items
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            throw new Exception("Your cart is empty!");
        }

        // Initialize variables
        $account_id = null;
        $is_guest_order = 1;
        $user_type = 'guest';
        
        // Check if user is logged in (customer)
        if (isset($_SESSION['account_id']) && isset($_SESSION['loggedinasuser']) && $_SESSION['loggedinasuser'] === true) {
            $account_id = $_SESSION['account_id'];
            $is_guest_order = 0;
            $user_type = 'customer';
        }

        // Get form data
        $paymentMethod = trim($_POST['payment_method']);
        
        // Validate payment method
        $validPaymentMethods = ['ewallet', 'cod', 'bank'];
        if (!in_array($paymentMethod, $validPaymentMethods)) {
            throw new Exception("Invalid payment method selected");
        }

        // Prepare user data based on user type
        if ($is_guest_order) {
            // Guest order - get data from form
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phoneNumber = trim($_POST['phone_number']);
            $address = trim($_POST['address']);
            $postalCode = trim($_POST['postal_code']);
            $city = trim($_POST['city']);

            // Validate required fields for guest
            $required = [
                'First Name' => $firstName,
                'Last Name' => $lastName,
                'Email' => $email,
                'Phone Number' => $phoneNumber,
                'Address' => $address,
                'Postal Code' => $postalCode,
                'City' => $city
            ];
        } else {
            // Customer order - get data from account
            $stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, postal_code, city FROM accounts WHERE account_id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (!$user) {
                throw new Exception("Could not retrieve user details");
            }

            $firstName = $user['first_name'];
            $lastName = $user['last_name'];
            $email = $user['email'];
            $phoneNumber = $user['phone_number'];
            $address = $user['address'];
            $postalCode = $user['postal_code'];
            $city = $user['city'];
        }

        // Validate common fields
        foreach ([
            'First Name' => $firstName,
            'Last Name' => $lastName,
            'Email' => $email,
            'Phone Number' => $phoneNumber,
            'Address' => $address,
            'Postal Code' => $postalCode,
            'City' => $city
        ] as $field => $value) {
            if (empty($value)) {
                throw new Exception("$field is required");
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Calculate total price
        $totalPrice = 0;
        foreach ($cart as $item) {
            if (!isset($item['price']) || !isset($item['quantity'])) {
                throw new Exception("Invalid cart item data");
            }
            $totalPrice += floatval($item['price']) * intval($item['quantity']);
        }

        if ($totalPrice <= 0) {
            throw new Exception("Invalid order total");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert order
            $orderQuery = "INSERT INTO orders (
                account_id, 
                email, 
                phone_number, 
                first_name, 
                last_name, 
                address, 
                postal_code, 
                city, 
                total_price, 
                payment_method, 
                is_guest_order,
                order_status, 
                order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

            $stmt = $conn->prepare($orderQuery);
            $stmt->bind_param(
                "isssssssdsi",
                $account_id,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city,
                $totalPrice,
                $paymentMethod,
                $is_guest_order
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create order: " . $stmt->error);
            }

            $orderId = $conn->insert_id;

            // Insert order items
            foreach ($cart as $item) {
                if (!isset($item['product_id']) || !isset($item['variant_id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception("Invalid cart item data");
                }
                
                $discountPrice = $item['discount_price'] ?? 0.00;
                
                $itemStmt = $conn->prepare("
                    INSERT INTO order_items (
                        order_id, 
                        product_id, 
                        variant_id, 
                        quantity, 
                        price, 
                        discount
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

                // Update stock quantity (optional)
                $updateStmt = $conn->prepare("
                    UPDATE product_variants 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE variant_id = ? AND stock_quantity >= ?
                ");
                $updateStmt->bind_param("iii", $item['quantity'], $item['variant_id'], $item['quantity']);
                $updateStmt->execute();
            }
            
            // Commit transaction
            $conn->commit();

            // Clear cart and set success data
            unset($_SESSION['cart']);
            $_SESSION['order_id'] = $orderId;
            $_SESSION['success'] = "Order placed successfully! Your order ID is #$orderId";

            // Redirect to success page
            header("Location: ../order_success.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Checkout error: " . $e->getMessage());
        $_SESSION['error'] = "Checkout failed: " . $e->getMessage();
        header("Location: ../checkout.php");
        exit();
    }
}

}

?>