<?php
// payment_callback.php
session_start();
require_once 'conn.php';
require_once 'functions/paymongo_helper.php';
require_once 'functions/activity_log_helper.php';
require_once 'functions/order_helper.php';

// Load environment variables
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check API key
if (!isset($_ENV['PAYMONGO_SECRET_KEY']) || empty($_ENV['PAYMONGO_SECRET_KEY'])) {
    error_log("PAYMONGO_SECRET_KEY is not set or empty");
    $_SESSION['error'] = "Payment configuration error.";
    header("Location: checkout.php");
    exit();
}

$tempRef = $_GET['ref'] ?? null;
$status = $_GET['status'] ?? null;

// Log for debugging
error_log("=== PAYMENT CALLBACK ===");
error_log("GET params: " . print_r($_GET, true));
error_log("Session temp_ref: " . ($_SESSION['temp_checkout_ref'] ?? 'none'));

// Verify reference
if (!$tempRef || !isset($_SESSION['temp_checkout_ref']) || $_SESSION['temp_checkout_ref'] !== $tempRef) {
    error_log("Invalid reference - Expected: " . ($_SESSION['temp_checkout_ref'] ?? 'none') . ", Got: $tempRef");
    $_SESSION['error'] = "Invalid payment session.";
    header("Location: checkout.php");
    exit();
}

// Get the pending checkout data
$checkoutData = $_SESSION['pending_checkout'] ?? null;
if (!$checkoutData) {
    error_log("No pending checkout data found");
    $_SESSION['error'] = "Checkout data not found.";
    header("Location: checkout.php");
    exit();
}

// If status is success, create the order immediately (trust the redirect)
if ($status === 'success') {
    error_log("Payment successful - creating order");
    
    $conn->begin_transaction();
    
    try {
        // Generate order code
        $orderCode = generateOrderCode();
        $orderStatus = 'Pending';
        $accountId = $checkoutData['account_id'] ?? null;
        $isGuest = $accountId ? 0 : 1;
        
        // Insert order with 'Pending' status
        $stmt = $conn->prepare("
            INSERT INTO orders (
                account_id, email, phone_number, first_name, last_name,
                address, postal_code, city, total_price, payment_method,
                is_guest_order, order_code, delivery_notes, order_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid')
        ");
        
        $stmt->bind_param("isssssssdsiss",
            $accountId,
            $checkoutData['email'],
            $checkoutData['phone_number'],
            $checkoutData['first_name'],
            $checkoutData['last_name'],
            $checkoutData['address'],
            $checkoutData['postal_code'],
            $checkoutData['city'],
            $checkoutData['total_amount'],
            $checkoutData['payment_method'],
            $isGuest,
            $orderCode,
            $checkoutData['delivery_notes']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $conn->error);
        }
        
        $orderId = $conn->insert_id;
        
        // Insert order items
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $itemSummary = [];
        foreach ($checkoutData['cart'] as $item) {
            $itemStmt->bind_param("iiiid",
                $orderId,
                $item['product_id'],
                $item['variant_id'],
                $item['quantity'],
                $item['price']
            );
            
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to add order items");
            }
            
            $itemSummary[] = "{$item['product_name']} x{$item['quantity']}";
            
            // Update stock
            $updateStock = $conn->prepare("
                UPDATE product_variants 
                SET stock_quantity = stock_quantity - ? 
                WHERE variant_id = ?
            ");
            $updateStock->bind_param("di", $item['quantity'], $item['variant_id']);
            $updateStock->execute();
            $updateStock->close();
        }
        
        // Insert payment record
        $paymentStmt = $conn->prepare("
            INSERT INTO payments (
                order_id, currency, gross_amount, payment_status,
                source_type, created_at
            ) VALUES (?, 'PHP', ?, 'Paid', ?, NOW())
        ");
        
        $paymentStmt->bind_param("ids",
            $orderId,
            $checkoutData['total_amount'],
            $checkoutData['payment_method']
        );
        $paymentStmt->execute();
        
        // Log activity
        logActivity($conn, 'order', $orderId, 'Order created', null, 'Paid',
            "Order #{$orderCode} created after successful payment. Total: ₱" . number_format($checkoutData['total_amount'], 2) . " | Items: " . implode(', ', $itemSummary),
            $accountId, $accountId ? 'customer' : 'guest'
        );
        
        $conn->commit();
        
        // Clear session data
        unset($_SESSION['pending_checkout']);
        unset($_SESSION['temp_checkout_ref']);
        unset($_SESSION['paymongo_session_id']);
        unset($_SESSION['cart']);
        
        // Redirect to order review
        $_SESSION['success'] = "Payment successful! Your order #{$orderCode} has been confirmed.";
        $_SESSION['order_id'] = $orderId;
        $_SESSION['order_code'] = $orderCode;
        
        error_log("Redirecting to order_review.php with code: $orderCode");
        header("Location: order_review.php?order_code=" . urlencode($orderCode));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order creation error: " . $e->getMessage());
        $_SESSION['error'] = "Order creation failed. Please contact support.";
        header("Location: checkout.php");
        exit();
    }
    
} else {
    // Payment was cancelled
    error_log("Payment cancelled or failed");
    unset($_SESSION['temp_checkout_ref']);
    unset($_SESSION['paymongo_session_id']);
    $_SESSION['error'] = "Payment was cancelled.";
    header("Location: checkout.php?cancel=1");
    exit();
}
?>