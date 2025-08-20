<?php
require_once '../conn.php';
require_once './vendor/autoload.php';
require_once 'paymongo_helper.php';
require_once 'mail_functions.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verify webhook signature (important for security)
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

// Get the raw POST data
$event = json_decode($payload, true);

if ($event && isset($event['type'])) {
    error_log("Webhook received: " . $event['type']);
    
    $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
    
    if ($event['type'] === 'checkout_session.payment.paid') {
        $sessionId = $event['data']['id'];
        
        try {
            $session = $paymongo->retrieveCheckoutSession($sessionId);
            $orderId = $session['data']['attributes']['metadata']['order_id'] ?? null;
            
            if ($orderId) {
                // Update order status to 'Paid'
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE order_id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                
                error_log("Order $orderId marked as paid via webhook");
            }
        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo 'Webhook processed';
?>