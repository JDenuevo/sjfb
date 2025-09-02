<?php
header('Content-Type: application/json');
session_start();
require_once '../conn.php';
require_once './vendor/autoload.php';
require_once 'paymongo_helper.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get parameters
$orderId = $_GET['order_id'] ?? null;
$paymentIntentId = $_GET['pi'] ?? null;

if (!$orderId || !$paymentIntentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Check order exists
    $stmt = $conn->prepare("SELECT order_status, payment_method FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    // If already paid in database, return success
    if ($order['order_status'] === 'Paid') {
        echo json_encode([
            'status' => 'paid',
            'message' => 'Payment already confirmed'
        ]);
        exit();
    }

    // Initialize PayMongo and check payment intent status
    $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
    $paymentIntent = $paymongo->retrievePaymentIntent($paymentIntentId);

    $paymentStatus = $paymentIntent['data']['attributes']['status'];
    
    // Update order status based on payment intent status
    switch ($paymentStatus) {
        case 'succeeded':
            // Update order status to Paid
            $updateStmt = $conn->prepare("UPDATE orders SET order_status = 'Paid', updated_at = NOW() WHERE order_id = ?");
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();
            
            // Clear cart and session data
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            unset($_SESSION['current_order_id']);
            unset($_SESSION['pending_payment_order']);
            unset($_SESSION['qrph_payment_intent']);
            unset($_SESSION['qrph_payment_method']);
            
            echo json_encode([
                'status' => 'succeeded',
                'message' => 'Payment successful'
            ]);
            break;
            
        case 'failed':
        case 'cancelled':
            // Update order status to Failed
            $updateStmt = $conn->prepare("UPDATE orders SET order_status = 'Failed', updated_at = NOW() WHERE order_id = ?");
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();
            
            echo json_encode([
                'status' => 'failed',
                'message' => 'Payment failed or cancelled'
            ]);
            break;
            
        case 'processing':
        case 'requires_action':
        case 'awaiting_payment_method':
        default:
            echo json_encode([
                'status' => 'pending',
                'message' => 'Payment is still processing'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Payment status check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to check payment status',
        'message' => $e->getMessage()
    ]);
}
?>