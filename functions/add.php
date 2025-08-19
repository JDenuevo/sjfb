<?php
session_start();
require_once '../conn.php';
require_once '../vendor/autoload.php';
require_once 'paymongo_helper.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

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
            // Validate cart and form data
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) {
                throw new Exception("Your cart is empty");
            }

            // Process form data
            $requiredFields = ['first_name', 'last_name', 'phone_number', 'address', 'city', 'postal_code', 'payment_method'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields");
                }
            }

            // Calculate total
            $totalAmount = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

            // Start transaction
            $conn->begin_transaction();

            // Prepare values for order insertion
            $accountId = isset($_SESSION['account_id']) ? $_SESSION['account_id'] : null;
            $isGuest = $accountId ? 0 : 1;
            $email = $_POST['email'] ?? '';
            $phoneNumber = $_POST['phone_number'];
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $address = $_POST['address'];
            $postalCode = $_POST['postal_code'];
            $city = $_POST['city'];
            $paymentMethod = $_POST['payment_method'];

            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    account_id, email, phone_number, first_name, last_name, 
                    address, postal_code, city, total_price, payment_method, is_guest_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Bind parameters correctly
            $stmt->bind_param(
                "isssssssdsi", 
                $accountId, 
                $email, 
                $phoneNumber, 
                $firstName, 
                $lastName,
                $address, 
                $postalCode, 
                $city, 
                $totalAmount, 
                $paymentMethod, 
                $isGuest
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create order: " . $conn->error);
            }

            $orderId = $conn->insert_id;

            // Create order items
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, quantity, price
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($cart as $item) {
                // Extract item values
                $itemProductId = $item['product_id'];
                $itemVariantId = $item['variant_id'];
                $itemQuantity = $item['quantity'];
                $itemPrice = $item['price'];
                
                $itemStmt->bind_param(
                    "iiiid", 
                    $orderId, 
                    $itemProductId, 
                    $itemVariantId, 
                    $itemQuantity, 
                    $itemPrice
                );
                
                if (!$itemStmt->execute()) {
                    throw new Exception("Failed to add order items: " . $conn->error);
                }
            }

            // Handle payment method
            if (in_array($paymentMethod, ['gcash', 'paymaya', 'grab_pay', 'card'])) {
                $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
                
                // Store payment details in session
                $_SESSION['payment_details'] = [
                    'order_id' => $orderId,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'customer_email' => $email,
                    'customer_name' => "$firstName $lastName",
                    'phone_number' => $phoneNumber
                ];

                // Create payment intent
                $response = $paymongo->createPaymentIntent(
                    $totalAmount,
                    "Order #$orderId",
                    [
                        'order_id' => $orderId,
                        'customer_email' => $email,
                        'customer_name' => "$firstName $lastName"
                    ]
                );

                if (!isset($response['data']['id'])) {
                    throw new Exception("Payment initialization failed");
                }

                // Store payment intent details in session
                $_SESSION['payment_intent_id'] = $response['data']['id'];
                $_SESSION['client_key'] = $response['data']['attributes']['client_key'];
                $_SESSION['payment_method'] = $paymentMethod;

                $conn->commit();
                header("Location: ../payment.php");
                exit();
            } else {
                // For COD or other non-online payments
                $conn->commit();
                unset($_SESSION['cart']);
                $_SESSION['order_id'] = $orderId;
                header("Location: ../order_success.php");
                exit();
            }

        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../checkout.php");
            exit();
        }
    }
}

?>