<?php
session_start();
include '../../conn.php'; // Adjust path as needed

// Set time zone
date_default_timezone_set('Asia/Manila');

// Helper function for redirects with messages
function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
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

// If not POST request, redirect
header("Location: ../index.php");
exit();
?>