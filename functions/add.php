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

// Function to generate order code
function generateOrderCode() {
    $prefix = "ORD"; 
    $date   = date('ymd'); 
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random = '';
    for ($i = 0; $i < 6; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $date . $random;
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

            // Process form data with enhanced validation
            $requiredFields = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'city', 'postal_code', 'payment_method'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields");
                }
            }

            // Enhanced email validation
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                throw new Exception("Please provide a valid email address");
            }

            // Phone number validation (basic)
            $phoneNumber = trim($_POST['phone_number']);
            if (!preg_match('/^[0-9+\-\s()]+$/', $phoneNumber) || strlen($phoneNumber) < 10) {
                throw new Exception("Please provide a valid phone number");
            }

            // Name validation
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            if (strlen($firstName) < 2 || strlen($lastName) < 2) {
                throw new Exception("First name and last name must be at least 2 characters long");
            }

            // Address validation
            $address = trim($_POST['address']);
            $city = trim($_POST['city']);
            $postalCode = trim($_POST['postal_code']);
            
            if (strlen($address) < 10) {
                throw new Exception("Please provide a complete address");
            }
            
            if (strlen($city) < 2) {
                throw new Exception("Please provide a valid city name");
            }
            
            if (!preg_match('/^[0-9]{4,6}$/', $postalCode)) {
                throw new Exception("Please provide a valid postal code (4-6 digits)");
            }

            $paymentMethod = $_POST['payment_method'];
            $validPaymentMethods = ['cod', 'gcash', 'paymaya', 'grab_pay', 'card', 'qrph'];
            
            if (!in_array($paymentMethod, $validPaymentMethods)) {
                throw new Exception("Invalid payment method selected");
            }

            // Calculate total
            $totalAmount = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));
            
            if ($totalAmount <= 0) {
                throw new Exception("Invalid order total");
            }

            // Start transaction
            $conn->begin_transaction();

            // Prepare values for order insertion
            $accountId = isset($_SESSION['account_id']) ? $_SESSION['account_id'] : null;
            $isGuest = $accountId ? 0 : 1;
           
            // Generate custom order code
            $orderCode = generateOrderCode();
            
            // Create order with proper status
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    account_id, email, phone_number, first_name, last_name, 
                    address, postal_code, city, total_price, payment_method, 
                    is_guest_order, order_code
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Bind parameters correctly
            $stmt->bind_param(
                "isssssssdsis", 
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
                $isGuest,
                $orderCode
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

            // Handle ALL online payment methods (including QRPH) using Checkout Session
            if (in_array($paymentMethod, ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph'])) {
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
                error_log("Payment method: " . $paymentMethod);
                error_log("Base URL: " . $baseUrl);

                // Create the checkout session with success and cancel URLs
                $response = $paymongo->createCheckoutSession(
                    $totalAmount,
                    "Order #$orderCode",  // Use friendly order code
                    [
                        'payment_method_types' => [$paymentMethod],
                        'success_url' => $baseUrl . '/order_receipt.php?session_id={CHECKOUT_SESSION_ID}&order_code=' . $orderCode . '&status=success',
                        'cancel_url' => $baseUrl . '/order_receipt.php?session_id={CHECKOUT_SESSION_ID}&order_code=' . $orderCode . '&status=cancelled',
                        'customer_info' => $customerInfo,
                        'billing' => [
                            'address' => $billingAddress,
                            'email' => $email,
                            'name' => $firstName . ' ' . $lastName,
                            'phone' => $phoneNumber
                        ],
                        'metadata' => [
                            'order_id' => $orderId,       // system ID
                            'order_code' => $orderCode,   // friendly code
                            'customer_email' => $email,
                            'customer_name' => "$firstName $lastName",
                            'customer_phone' => $phoneNumber,
                            'shipping_address' => $address,
                            'shipping_city' => $city,
                            'shipping_postal_code' => $postalCode,
                            'payment_method' => $paymentMethod,
                        ]
                    ]
                );

                error_log("Checkout session response: " . print_r($response, true));

                if (!isset($response['data']['attributes']['checkout_url'])) {
                    throw new Exception("Checkout session creation failed. No checkout URL returned.");
                }

                // Store checkout session ID for later retrieval
                $checkoutSessionId = $response['data']['id'];
                
                // Create payment record with Pending status for online payments
                $paymentStmt = $conn->prepare("
                    INSERT INTO payments (
                        order_id, currency, gross_amount, payment_status, 
                        mode, billing_name, billing_email, billing_phone,
                        billing_line1, billing_city, billing_postal_code, billing_country,
                        source_type, provider_id, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $billingName = $firstName . ' ' . $lastName;
                $currency = 'PHP';
                $paymentStatus = 'Pending';
                $mode = 'live';
                $billingCountry = 'PH';
                $sourceType = $paymentMethod;
                
                $paymentStmt->bind_param(
                    "isdsssssssssss", 
                    $orderId, 
                    $currency,
                    $totalAmount,
                    $paymentStatus,
                    $mode,
                    $billingName,
                    $email,
                    $phoneNumber,
                    $address,
                    $city,
                    $postalCode,
                    $billingCountry,
                    $sourceType,
                    $checkoutSessionId
                );
                
                if (!$paymentStmt->execute()) {
                    error_log("Payment insert error: " . $paymentStmt->error);
                    // Don't throw exception as order is already created
                }
                
                // Store order ID and code in session for verification
                $_SESSION['current_order_id'] = $orderId;
                $_SESSION['current_order_code'] = $orderCode;
                $_SESSION['pending_payment_order'] = $orderId;
                $_SESSION['payment_method'] = $paymentMethod;
                $_SESSION['checkout_session_id'] = $checkoutSessionId;
                
                $conn->commit();

                // Clear cart only after successful order creation
                unset($_SESSION['cart']);
                
                // Redirect to PayMongo's hosted checkout page
                header("Location: " . $response['data']['attributes']['checkout_url']);
                exit();

            } elseif ($paymentMethod === 'cod') {
                // For COD orders, create a payment record with Pending status
                $codStmt = $conn->prepare("
                    INSERT INTO payments (
                        order_id, currency, gross_amount, payment_status, 
                        mode, billing_name, billing_email, billing_phone,
                        billing_line1, billing_city, billing_postal_code, billing_country,
                        source_type, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $billingName = $firstName . ' ' . $lastName;
                $currency = 'PHP';
                $paymentStatusCod = 'Pending';
                $mode = 'live';
                $billingCountry = 'PH';
                $sourceType = 'cod';
                
                $codStmt->bind_param(
                    "isdssssssssss", 
                    $orderId, 
                    $currency,
                    $totalAmount,
                    $paymentStatusCod,
                    $mode,
                    $billingName,
                    $email,
                    $phoneNumber,
                    $address,
                    $city,
                    $postalCode,
                    $billingCountry,
                    $sourceType
                );
                
                if (!$codStmt->execute()) {
                    error_log("COD payment insert error: " . $codStmt->error);
                }
                
                $conn->commit();
                
                // Clear cart only after successful order creation
                unset($_SESSION['cart']);
                
                // Store order ID and code for confirmation page
                $_SESSION['order_id'] = $orderId;
                $_SESSION['order_code'] = $orderCode;
                
                // Redirect to order confirmation page
                header("Location: ../order_receipt.php?order_code=" . $orderCode);
                exit();
                
            } else {
                // Invalid payment method
                throw new Exception("Unsupported payment method: " . $paymentMethod);
            }

        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            error_log("Order processing error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../checkout.php");
            exit();
        }
    }   
}
?>