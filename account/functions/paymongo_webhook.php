<?php
// paymongo_webhook.php
require_once '../../conn.php';
require_once '../../vendor/autoload.php';
require_once 'paymongo_helper.php';
require_once 'mail_functions.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function logWebhook($message) {
    $logFile = __DIR__ . '/../logs/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] $message" . PHP_EOL;
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
    file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    error_log($message);
}

logWebhook("=== WEBHOOK RECEIVED ===");

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$webhookSecretKey = $_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? '';

// Verify signature (enable in production)
$skipSignatureVerification = true; // Set to false in production

if (!$skipSignatureVerification && !verifyWebhookSignature($payload, $signatureHeader, $webhookSecretKey)) {
    logWebhook("Webhook signature verification failed");
    http_response_code(401);
    echo 'Invalid signature';
    exit;
}

$event = json_decode($payload, true);
if (!$event || !isset($event['data']['type'])) {
    logWebhook("Invalid webhook payload");
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$eventType = $event['data']['type'];
$eventId = $event['data']['id'];

logWebhook("Processing event: $eventType (ID: $eventId)");

$paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);

try {
    switch ($eventType) {
        case 'checkout_session.payment.paid':
        case 'payment.paid':
            handlePaymentPaid($event, $conn);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($event, $conn);
            break;
            
        case 'payment.refunded':
        case 'refund.created':
        case 'refund.updated':
        case 'refund.completed':
            handleRefundEvent($event, $conn);
            break;
            
        default:
            logWebhook("Unhandled event type: $eventType");
            break;
    }
    
    http_response_code(200);
    echo 'Webhook processed successfully';
    
} catch (Exception $e) {
    logWebhook("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo 'Error processing webhook';
}

function handlePaymentPaid($event, $conn) {
    $attributes = $event['data']['attributes'];
    $paymentId = $event['data']['id'];
    
    logWebhook("Processing paid payment: $paymentId");
    
    // Extract payment data from PayMongo response
    $orderId = $attributes['metadata']['order_id'] ?? null;
    $amount = ($attributes['amount'] ?? 0) / 100; // Convert from cents
    $fee = ($attributes['fee'] ?? 0) / 100;
    $vat = ($attributes['vat'] ?? 0) / 100;
    $netAmount = $amount - $fee - $vat;
    $currency = $attributes['currency'] ?? 'PHP';
    $paidAt = date('Y-m-d H:i:s', $attributes['paid_at'] ?? time());
    $availableAt = isset($attributes['available_at']) ? date('Y-m-d H:i:s', $attributes['available_at']) : null;
    
    // Source/payment method details
    $source = $attributes['source'] ?? [];
    $sourceType = $source['type'] ?? null;
    $sourceId = $source['id'] ?? null;
    
    // Card details if applicable
    $cardDetails = $source['card'] ?? [];
    $cardBrand = $cardDetails['brand'] ?? null;
    $cardCountry = $cardDetails['country'] ?? null;
    $cardLast4 = $cardDetails['last4'] ?? null;
    
    // QR PH details if applicable
    $qrId = $source['qr_id'] ?? null;
    
    // Billing details
    $billing = $attributes['billing'] ?? [];
    $billingAddress = $billing['address'] ?? [];
    
    // Check if payment already exists
    $checkStmt = $conn->prepare("SELECT payment_id FROM payments WHERE provider_id = ?");
    $checkStmt->bind_param("s", $paymentId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        // Update existing payment
        $stmt = $conn->prepare("
            UPDATE payments SET
                gross_amount = ?,
                fee = ?,
                vat = ?,
                total_fee = ?,
                net_amount = ?,
                payment_status = 'Paid',
                paid_at = ?,
                available_at = ?,
                source_type = ?,
                source_id = ?,
                card_brand = ?,
                card_country = ?,
                card_last4 = ?,
                qr_id_reference = ?,
                billing_name = ?,
                billing_email = ?,
                billing_phone = ?,
                billing_line1 = ?,
                billing_city = ?,
                billing_postal_code = ?,
                billing_country = ?,
                updated_at = NOW()
            WHERE provider_id = ?
        ");
        
        $billingName = $billing['name'] ?? null;
        $billingEmail = $billing['email'] ?? null;
        $billingPhone = $billing['phone'] ?? null;
        $billingLine1 = $billingAddress['line1'] ?? null;
        $billingCity = $billingAddress['city'] ?? null;
        $billingPostal = $billingAddress['postal_code'] ?? null;
        $billingCountry = $billingAddress['country'] ?? null;
        
        $stmt->bind_param("dddddssssssssssssssss",
            $amount, $fee, $vat, $fee, $netAmount,
            $paidAt, $availableAt,
            $sourceType, $sourceId,
            $cardBrand, $cardCountry, $cardLast4,
            $qrId,
            $billingName, $billingEmail, $billingPhone,
            $billingLine1, $billingCity, $billingPostal, $billingCountry,
            $paymentId
        );
        
    } else {
        // Insert new payment
        $stmt = $conn->prepare("
            INSERT INTO payments (
                order_id, provider_id, currency, gross_amount, fee, vat,
                total_fee, net_amount, payment_status, paid_at, available_at,
                source_type, source_id, card_brand, card_country, card_last4,
                qr_id_reference, billing_name, billing_email, billing_phone,
                billing_line1, billing_city, billing_postal_code, billing_country,
                mode, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Paid', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $billingName = $billing['name'] ?? null;
        $billingEmail = $billing['email'] ?? null;
        $billingPhone = $billing['phone'] ?? null;
        $billingLine1 = $billingAddress['line1'] ?? null;
        $billingCity = $billingAddress['city'] ?? null;
        $billingPostal = $billingAddress['postal_code'] ?? null;
        $billingCountry = $billingAddress['country'] ?? null;
        $mode = 'live'; // or 'test' based on your key
        
        $stmt->bind_param("isddddddsssssssssssssssss",
            $orderId, $paymentId, $currency,
            $amount, $fee, $vat, $fee, $netAmount,
            $paidAt, $availableAt,
            $sourceType, $sourceId,
            $cardBrand, $cardCountry, $cardLast4,
            $qrId,
            $billingName, $billingEmail, $billingPhone,
            $billingLine1, $billingCity, $billingPostal, $billingCountry,
            $mode
        );
    }
    
    if ($stmt->execute()) {
        logWebhook("Payment record updated/inserted for order $orderId");
        
        // Optionally update order status if needed
        if ($orderId) {
            $updateOrder = $conn->prepare("UPDATE orders SET payment_status = 'Paid' WHERE order_id = ?");
            $updateOrder->bind_param("i", $orderId);
            $updateOrder->execute();
        }
        
        // Send confirmation email
        sendPaymentConfirmationEmail($orderId);
    } else {
        logWebhook("Failed to insert/update payment: " . $stmt->error);
    }
    $stmt->close();
}

function handlePaymentFailed($event, $conn) {
    $attributes = $event['data']['attributes'];
    $paymentId = $event['data']['id'];
    
    logWebhook("Processing failed payment: $paymentId");
    
    $orderId = $attributes['metadata']['order_id'] ?? null;
    $failedCode = $attributes['failure_code'] ?? null;
    $failedMessage = $attributes['failure_message'] ?? null;
    
    $stmt = $conn->prepare("
        UPDATE payments SET
            payment_status = 'Failed',
            failed_code = ?,
            description = ?
        WHERE provider_id = ?
    ");
    
    $stmt->bind_param("sss", $failedCode, $failedMessage, $paymentId);
    $stmt->execute();
    
    if ($orderId) {
        $updateOrder = $conn->prepare("UPDATE orders SET payment_status = 'Failed' WHERE order_id = ?");
        $updateOrder->bind_param("i", $orderId);
        $updateOrder->execute();
    }
    
    logWebhook("Payment marked as failed for order $orderId");
    sendPaymentFailedEmail($orderId);
}

function handleRefundEvent($event, $conn) {
    $attributes = $event['data']['attributes'];
    $refundId = $event['data']['id'];
    $refundAmount = ($attributes['amount'] ?? 0) / 100;
    $paymentId = $attributes['payment_id'] ?? null;
    $reason = $attributes['reason'] ?? null;
    $status = $attributes['status'] ?? null; // pending, processing, completed, failed
    
    logWebhook("Processing refund: $refundId, status: $status");
    
    if (!$paymentId) return;
    
    // Get the payment record
    $getPayment = $conn->prepare("SELECT order_id, refunded_amount FROM payments WHERE provider_id = ?");
    $getPayment->bind_param("s", $paymentId);
    $getPayment->execute();
    $payment = $getPayment->get_result()->fetch_assoc();
    $getPayment->close();
    
    if (!$payment) return;
    
    $orderId = $payment['order_id'];
    $currentRefunded = $payment['refunded_amount'] ?? 0;
    $newRefunded = $currentRefunded + $refundAmount;
    
    // Map PayMongo refund status to your refund_status enum
    $refundStatus = match($status) {
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Rejected',
        default => 'Pending'
    };
    
    // Update payment record
    $stmt = $conn->prepare("
        UPDATE payments SET
            refunded_amount = ?,
            refund_status = ?,
            refund_reason = ?,
            refund_requested_at = CASE WHEN ? = 'pending' THEN NOW() ELSE refund_requested_at END,
            refund_processed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE refund_processed_at END
        WHERE provider_id = ?
    ");
    
    $stmt->bind_param("dsssss",
        $newRefunded,
        $refundStatus,
        $reason,
        $status,
        $status,
        $paymentId
    );
    $stmt->execute();
    
    // If refund is completed, update payment status
    if ($status === 'completed') {
        $updatePayment = $conn->prepare("UPDATE payments SET payment_status = 'Refunded' WHERE provider_id = ?");
        $updatePayment->bind_param("s", $paymentId);
        $updatePayment->execute();
    }
    
    logWebhook("Refund recorded for payment $paymentId");
}

function verifyWebhookSignature($payload, $signatureHeader, $secret) {
    if (empty($signatureHeader) || empty($secret)) return false;
    
    $parts = explode(',', $signatureHeader);
    $timestamp = '';
    $signature = '';
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, 't=') === 0) $timestamp = substr($part, 2);
        if (strpos($part, 'v1=') === 0) $signature = substr($part, 3);
    }
    
    if (empty($timestamp) || empty($signature)) return false;
    
    $signedPayload = $timestamp . "." . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
    
    return hash_equals($expectedSignature, $signature);
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