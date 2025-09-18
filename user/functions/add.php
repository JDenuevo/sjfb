<?php
session_start();
require_once '../../conn.php';
require_once '../../vendor/autoload.php';
require_once '../../functions/paymongo_helper.php'; // Adjust path as needed

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
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

// Main order processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    try {
        // Verify cart exists and has items
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            throw new Exception("Your cart is empty!");
        }

        // Initialize variables
        $account_id = null;
        $is_guest_order = 1;
        
        // Check if user is logged in (customer)
        if (isset($_SESSION['account_id']) && isset($_SESSION['loggedinasuser']) && $_SESSION['loggedinasuser'] === true) {
            $account_id = $_SESSION['account_id'];
            $is_guest_order = 0;
        }

        // Get form data
        $paymentMethod = trim($_POST['payment_method']);
        
        // Validate payment method
        $validPaymentMethods = ['cod', 'gcash', 'paymaya', 'grab_pay', 'card', 'qrph'];
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
            
            // Enhanced validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please provide a valid email address");
            }

            if (!preg_match('/^[0-9+\-\s()]+$/', $phoneNumber) || strlen($phoneNumber) < 10) {
                throw new Exception("Please provide a valid phone number");
            }

            if (strlen($firstName) < 2 || strlen($lastName) < 2) {
                throw new Exception("First name and last name must be at least 2 characters long");
            }

            if (strlen($address) < 10) {
                throw new Exception("Please provide a complete address");
            }
            
            if (strlen($city) < 2) {
                throw new Exception("Please provide a valid city name");
            }
            
            if (!preg_match('/^[0-9]{4,6}$/', $postalCode)) {
                throw new Exception("Please provide a valid postal code (4-6 digits)");
            }
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
            
            // Validate customer data
            if (empty($firstName) || empty($lastName) || empty($email) || empty($phoneNumber) || 
                empty($address) || empty($postalCode) || empty($city)) {
                throw new Exception("Your account details are incomplete. Please update your profile before placing an order.");
            }
        }

        // Calculate total price
        $totalAmount = 0;
        foreach ($cart as $item) {
            if (!isset($item['price']) || !isset($item['quantity'])) {
                throw new Exception("Invalid cart item data");
            }
            $totalAmount += floatval($item['price']) * intval($item['quantity']);
        }

        if ($totalAmount <= 0) {
            throw new Exception("Invalid order total");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Generate custom order code
            $orderCode = generateOrderCode();
            
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
                order_code,
                order_status, 
                order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

            $stmt = $conn->prepare($orderQuery);
            $stmt->bind_param(
                "isssssssdsis",
                $account_id,
                $email,
                $phoneNumber,
                $firstName,
                $lastName,
                $address,
                $postalCode,
                $city,
                $totalAmount,
                $paymentMethod,
                $is_guest_order,
                $orderCode
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create order: " . $stmt->error);
            }

            $orderId = $conn->insert_id;

            // Insert order items
            $itemStmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, variant_id, quantity, price
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($cart as $item) {
                if (!isset($item['product_id']) || !isset($item['variant_id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception("Invalid cart item data");
                }
                
                $itemStmt->bind_param(
                    "iiiid", 
                    $orderId, 
                    $item['product_id'],
                    $item['variant_id'],
                    $item['quantity'],
                    $item['price']
                );

                if (!$itemStmt->execute()) {
                    throw new Exception("Failed to add order item: " . $itemStmt->error);
                }

                // Update stock quantity
                $updateStmt = $conn->prepare("
                    UPDATE product_variants 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE variant_id = ? AND stock_quantity >= ?
                ");
                $updateStmt->bind_param("iii", $item['quantity'], $item['variant_id'], $item['quantity']);
                $updateStmt->execute();
            }
            
            // Handle ALL online payment methods (including QRPH) using Checkout Session
            if (in_array($paymentMethod, ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph'])) {
                $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);

                // Prepare customer information
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

                // Create the checkout session with relative paths
                $response = $paymongo->createCheckoutSession(
                    $totalAmount,
                    "Order #$orderCode",  // Use friendly order code
                    [
                        'payment_method_types' => [$paymentMethod],
                        'success_url' => 'http://localhost/sjfbi-js/user/order_receipt.php?session_id={CHECKOUT_SESSION_ID}&order_code=' . $orderCode . '&status=success',
                        'cancel_url' => 'http://localhost/sjfbi-js/user/order_receipt.php?session_id={CHECKOUT_SESSION_ID}&order_code=' . $orderCode . '&status=cancelled',
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

// If not POST request, redirect
header("Location: ../products.php");
exit();
?>