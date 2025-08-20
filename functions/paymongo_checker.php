<?php
require_once 'paymongo_helper.php';
require_once './vendor/autoload.php';

function verifyPayMongoPayment($sessionId, $orderId = null) {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    $result = [
        'success' => false,
        'order_id' => $orderId,
        'payment_method' => null,
        'error' => null,
        'session_data' => null
    ];
    
    try {
        $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
        $session = $paymongo->retrieveCheckoutSession($sessionId);
        
        $result['session_data'] = $session;
        
        // Check if payment was successful
        if (isset($session['data']['attributes']['payments']) && 
            count($session['data']['attributes']['payments']) > 0 &&
            $session['data']['attributes']['payments'][0]['attributes']['status'] === 'paid') {
            
            $result['success'] = true;
            $result['payment_method'] = $session['data']['attributes']['payment_method_types'][0] ?? 'card';
            
            // Get order ID from metadata if not provided
            if (!$result['order_id'] && isset($session['data']['attributes']['metadata']['order_id'])) {
                $result['order_id'] = $session['data']['attributes']['metadata']['order_id'];
            }
        } else {
            $result['error'] = "Payment was not completed successfully.";
        }
    } catch (Exception $e) {
        error_log("PayMongo verification error: " . $e->getMessage());
        $result['error'] = "Unable to verify payment: " . $e->getMessage();
    }
    
    return $result;
}

function updateOrderPaymentStatus($conn, $orderId, $paymentMethod, $status = 'paid') {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET order_status = ?, 
            payment_method = ? 
        WHERE order_id = ?
    ");
    $stmt->bind_param("ssi", $status, $paymentMethod, $orderId);
    return $stmt->execute();
}

function clearCartOnSuccess() {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    // Clear payment session variables
    unset($_SESSION['current_order_id']);
    unset($_SESSION['pending_payment_order']);
}

function preserveCartOnFailure() {
    // Cart is automatically preserved since we don't unset it
    // You can add any additional failure handling here
    error_log("Payment failed - cart preserved for potential retry");
}

// Add to paymongo_checker.php
function handlePaymentResult($conn, $verificationResult) {
    if ($verificationResult['success']) {
        if ($verificationResult['order_id']) {
            updateOrderPaymentStatus($conn, $verificationResult['order_id'], $verificationResult['payment_method']);
            clearCartOnSuccess();
            return [
                'success' => true,
                'order_id' => $verificationResult['order_id'],
                'message' => 'Payment verified successfully'
            ];
        }
        return [
            'success' => false,
            'error' => 'Order ID not found in payment verification'
        ];
    }
    
    preserveCartOnFailure();
    return [
        'success' => false,
        'error' => $verificationResult['error'] ?? 'Payment verification failed'
    ];
}

function logPaymentAttempt($conn, $orderId, $status, $details = '') {
    $stmt = $conn->prepare("
        INSERT INTO payment_attempts (order_id, status, details) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $orderId, $status, $details);
    $stmt->execute();
}

?>