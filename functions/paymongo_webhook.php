<?php
// paymongo_webhook.php
require_once '../conn.php';

// Load environment variables - FIXED PATH
$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    require_once '../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
} else {
    // Fallback: try to load from current directory
    if (file_exists(__DIR__ . '/.env')) {
        require_once './vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } else {
        error_log("Webhook Error: .env file not found");
        // Set empty values to prevent errors
        $_ENV = array_merge($_ENV, [
            'PAYMONGO_SECRET_KEY' => '',
            'PAYMONGO_PUBLIC_KEY' => '',
            'PAYMONGO_WEBHOOK_SECRET' => ''
        ]);
    }
}

// Now require the helpers
require_once 'paymongo_helper.php';
require_once 'mail_functions.php';

// Enhanced logging function
function logWebhook($message) {
    $logFile = __DIR__ . '/../logs/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $message" . PHP_EOL;
    
    // Ensure logs directory exists
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    error_log($message);
}

logWebhook("=== WEBHOOK RECEIVED ===");

// Get the raw POST data
$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$webhookSecretKey = $_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? '';

// Enhanced logging
logWebhook("Payload received: " . strlen($payload) . " bytes");
logWebhook("Signature header: " . ($signatureHeader ? 'Present' : 'Missing'));
logWebhook("Webhook secret configured: " . (!empty($webhookSecretKey) ? 'Yes' : 'No'));

// Skip signature verification for testing (remove in production)
$skipSignatureVerification = true; // Set to false in production

if (!$skipSignatureVerification && !verifyWebhookSignature($payload, $signatureHeader, $webhookSecretKey)) {
    logWebhook("Webhook signature verification failed");
    http_response_code(401);
    echo 'Invalid signature';
    exit;
} else {
    logWebhook("Signature verification " . ($skipSignatureVerification ? "skipped" : "passed"));
}

// Parse the event
$event = json_decode($payload, true);

if (!$event || !isset($event['data']['type'])) {
    logWebhook("Invalid webhook payload - no event type found");
    logWebhook("Raw payload: " . $payload);
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$eventType = $event['data']['type'];
$eventId = $event['data']['id'];

logWebhook("Processing event: " . $eventType . " (ID: " . $eventId . ")");

$paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);

try {
    switch ($eventType) {
        case 'checkout_session.payment.paid':
            logWebhook("Handling successful checkout session payment");
            handleSuccessfulPayment($event, $paymongo, $conn);
            break;
            
        case 'payment.paid':
            logWebhook("Handling successful payment");
            handleDirectPayment($event, $paymongo, $conn);
            break;
            
        case 'checkout_session.payment.failed':
            logWebhook("Handling failed checkout session payment");
            handleFailedPayment($event, $paymongo, $conn);
            break;
            
        case 'payment.failed':
            logWebhook("Handling failed payment");
            handlePaymentFailed($event, $paymongo, $conn);
            break;
            
        default:
            logWebhook("Unhandled event type: " . $eventType);
            break;
    }
    
    logWebhook("Event processing completed successfully");
    
} catch (Exception $e) {
    logWebhook("Webhook processing error: " . $e->getMessage());
    logWebhook("Stack trace: " . $e->getTraceAsString());
    // Still return 200 to prevent PayMongo from retrying continuously
}

http_response_code(200);
echo 'Webhook processed successfully';

function verifyWebhookSignature($payload, $signatureHeader, $secret) {
    if (empty($signatureHeader) || empty($secret)) {
        logWebhook("Missing signature header or secret");
        return false;
    }

    $timestamp = '';
    $signature = '';
    $signatureParts = explode(',', $signatureHeader);

    foreach ($signatureParts as $part) {
        $part = trim($part);
        if (strpos($part, 't=') === 0) {
            $timestamp = substr($part, 2);
        }
        if (strpos($part, 'v1=') === 0) {
            $signature = substr($part, 3);
        }
    }

    if (empty($timestamp) || empty($signature)) {
        logWebhook("Could not extract timestamp or signature from header: " . $signatureHeader);
        return false;
    }

    $signedPayload = $timestamp . "." . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

    logWebhook("Signature verification - Expected: " . $expectedSignature . ", Received: " . $signature);

    return hash_equals($expectedSignature, $signature);
}

function isEventProcessed($eventId, $conn) {
    $stmt = $conn->prepare("SELECT id FROM processed_events WHERE event_id = ?");
    $stmt->bind_param("s", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isProcessed = $result->num_rows > 0;
    logWebhook("Event " . $eventId . " processed status: " . ($isProcessed ? 'Already processed' : 'New event'));
    
    return $isProcessed;
}

function markEventAsProcessed($eventId, $orderId, $eventType, $conn) {
    $stmt = $conn->prepare("INSERT INTO processed_events (event_id, order_id, event_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE processed_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("sis", $eventId, $orderId, $eventType);
    
    if ($stmt->execute()) {
        logWebhook("Marked event " . $eventId . " as processed for order " . $orderId);
    } else {
        logWebhook("Failed to mark event as processed: " . $conn->error);
    }
}

function handleSuccessfulPayment($event, $paymongo, $conn) {
    $sessionId = $event['data']['id'];
    $eventId = $event['data']['id'];
    $eventType = $event['data']['type'];
    
    logWebhook("Processing successful payment for session: " . $sessionId);
    
    try {
        // Check if this event has already been processed
        if (isEventProcessed($eventId, $conn)) {
            logWebhook("Event already processed: " . $eventId);
            return;
        }
        
        $session = $paymongo->retrieveCheckoutSession($sessionId);
        logWebhook("Retrieved session data: " . json_encode($session, JSON_PRETTY_PRINT));
        
        $orderId = $session['data']['attributes']['metadata']['order_id'] ?? null;
        
        if (!$orderId) {
            logWebhook("No order_id found in session metadata");
            logWebhook("Available metadata: " . json_encode($session['data']['attributes']['metadata'] ?? [], JSON_PRETTY_PRINT));
            return;
        }
        
        logWebhook("Found order ID: " . $orderId);
        
        // Check current order status
        $checkStmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ?");
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            logWebhook("Order not found in database: " . $orderId);
            return;
        }
        
        $currentOrder = $result->fetch_assoc();
        logWebhook("Current order status: " . $currentOrder['order_status']);
        
        // Update order status to 'Paid'
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            logWebhook("Successfully updated order " . $orderId . " status to 'Paid'");
            logWebhook("Affected rows: " . $conn->affected_rows);
            
            // Mark event as processed
            markEventAsProcessed($eventId, $orderId, $eventType, $conn);
            
            // Send confirmation email
            sendPaymentConfirmationEmail($orderId);
        } else {
            logWebhook("Failed to update order status: " . $conn->error);
        }
        
    } catch (Exception $e) {
        logWebhook("Error processing successful payment: " . $e->getMessage());
        throw $e;
    }
}

function handleDirectPayment($event, $paymongo, $conn) {
    $paymentId = $event['data']['id'];
    $eventId = $event['data']['id'];
    $eventType = $event['data']['type'];
    
    logWebhook("Processing direct payment: " . $paymentId);
    
    try {
        // Check if this event has already been processed
        if (isEventProcessed($eventId, $conn)) {
            logWebhook("Event already processed: " . $eventId);
            return;
        }
        
        $payment = $paymongo->retrievePayment($paymentId);
        logWebhook("Retrieved payment data: " . json_encode($payment, JSON_PRETTY_PRINT));
        
        $orderId = $payment['data']['attributes']['metadata']['order_id'] ?? null;
        
        if ($orderId) {
            logWebhook("Found order ID in payment: " . $orderId);
            
            // Update order status to 'Paid'
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'Paid' WHERE order_id = ?");
            $stmt->bind_param("i", $orderId);
            
            if ($stmt->execute()) {
                logWebhook("Successfully updated order " . $orderId . " status to 'Paid' via direct payment");
                
                // Mark event as processed
                markEventAsProcessed($eventId, $orderId, $eventType, $conn);
                
                // Send confirmation email
                sendPaymentConfirmationEmail($orderId);
            } else {
                logWebhook("Failed to update order status: " . $conn->error);
            }
        } else {
            logWebhook("No order_id found in payment metadata");
        }
        
    } catch (Exception $e) {
        logWebhook("Error processing direct payment: " . $e->getMessage());
        throw $e;
    }
}

function handleFailedPayment($event, $paymongo, $conn) {
    $sessionId = $event['data']['id'];
    $eventId = $event['data']['id'];
    $eventType = $event['data']['type'];
    
    logWebhook("Processing failed payment for session: " . $sessionId);
    
    try {
        // Check if this event has already been processed
        if (isEventProcessed($eventId, $conn)) {
            logWebhook("Event already processed: " . $eventId);
            return;
        }
        
        $session = $paymongo->retrieveCheckoutSession($sessionId);
        $orderId = $session['data']['attributes']['metadata']['order_id'] ?? null;
        
        if ($orderId) {
            logWebhook("Marking order " . $orderId . " as payment failed");
            
            // Update order status to 'Payment Failed'
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'Payment Failed' WHERE order_id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            // Mark event as processed
            markEventAsProcessed($eventId, $orderId, $eventType, $conn);
            
            logWebhook("Order " . $orderId . " marked as payment failed via webhook");
            
            // Send failure notification email
            sendPaymentFailedEmail($orderId);
        }
    } catch (Exception $e) {
        logWebhook("Error processing failed payment: " . $e->getMessage());
        throw $e;
    }
}

function handlePaymentFailed($event, $paymongo, $conn) {
    $paymentId = $event['data']['id'];
    $eventId = $event['data']['id'];
    $eventType = $event['data']['type'];
    
    logWebhook("Processing generic payment failure: " . $paymentId);
    
    try {
        // Check if this event has already been processed
        if (isEventProcessed($eventId, $conn)) {
            logWebhook("Event already processed: " . $eventId);
            return;
        }
        
        $payment = $paymongo->retrievePayment($paymentId);
        $orderId = $payment['data']['attributes']['metadata']['order_id'] ?? null;
        
        if ($orderId) {
            logWebhook("Marking order " . $orderId . " as payment failed (generic)");
            
            // Update order status to 'Payment Failed'
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'Payment Failed' WHERE order_id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            // Mark event as processed
            markEventAsProcessed($eventId, $orderId, $eventType, $conn);
            
            logWebhook("Order " . $orderId . " marked as payment failed via webhook (payment.failed)");
            
            // Send failure notification email
            sendPaymentFailedEmail($orderId);
        }
    } catch (Exception $e) {
    logWebhook("Error processing payment failure: " . $e->getMessage());
    throw $e;
    }
}

function sendPaymentConfirmationEmail($orderId) {
    logWebhook("Sending confirmation email for order: " . $orderId);
    // Implement your email sending logic here
}

function sendPaymentFailedEmail($orderId) {
    logWebhook("Sending payment failed email for order: " . $orderId);
    // Implement your email sending logic here
}
?>