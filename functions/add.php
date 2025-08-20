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
            $requiredFields = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'city', 'postal_code', 'payment_method'];
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
                
                // For local testing, use your actual URL or ngrok
                $baseUrl = 'http://localhost/sjfbi-js'; // Update this to your actual URL

                // Prepare customer information from form data
                $customerInfo = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phoneNumber
                ];
                
                // Prepare billing address
                $billingAddress = [
                    'line1' => $address,
                    'city' => $city,
                    'postal_code' => $postalCode,
                    'state' => '', // Add state if you have it
                    'country' => 'PH'
                ];
                
                error_log("Creating checkout session for order: " . $orderId);
                error_log("Base URL: " . $baseUrl);
                error_log("Success URL: " . $baseUrl . '/order_success.php?oid={CHECKOUT_SESSION_ID}&order_id=' . $orderId);

                $response = $paymongo->createCheckoutSession(
                    $totalAmount,
                    "Order #$orderId",
                    [
                        'payment_method_types' => [$paymentMethod],
                        'success_url' => $baseUrl . '/order_success.php?oid={CHECKOUT_SESSION_ID}&order_id=' . $orderId,
                        'cancel_url' => $baseUrl . '/checkout.php',
                        'customer_info' => $customerInfo,
                        'billing' => [
                            'address' => $billingAddress,
                            'email' => $email,
                            'name' => $firstName . ' ' . $lastName,
                            'phone' => $phoneNumber
                        ],
                        'metadata' => [
                            'order_id' => $orderId,
                            'customer_email' => $email,
                            'customer_name' => "$firstName $lastName",
                            'customer_phone' => $phoneNumber,
                            'shipping_address' => $address,
                            'shipping_city' => $city,
                            'shipping_postal_code' => $postalCode,
                            'test_environment' => 'local'
                        ]
                    ]
                );

                error_log("Checkout session response: " . print_r($response, true));

                if (!isset($response['data']['attributes']['checkout_url'])) {
                    throw new Exception("Checkout session creation failed. No checkout URL returned.");
                }

                // Store order ID in session for verification
                $_SESSION['current_order_id'] = $orderId;
                $_SESSION['pending_payment_order'] = $orderId;
                
                $conn->commit();
                
                // Redirect to PayMongo's hosted checkout page
                header("Location: " . $response['data']['attributes']['checkout_url']);
                exit();

            } else {
                // For COD or other non-online payments
                $conn->commit();
                unset($_SESSION['cart']);
                $_SESSION['order_id'] = $orderId;
                header("Location: ../order_success.php?order_id=" . $orderId);
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
        
    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    }
    
}

?>