<?php
// paymongo_webhook.php

require_once 'conn.php';

// Verify webhook signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret_key';

$computedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $computedSignature)) {
    http_response_code(401);
    die('Invalid signature');
}

$event = json_decode($payload, true);

// Handle payment intent success
if ($event['event'] === 'payment_intent.success') {
    $paymentIntent = $event['data']['attributes']['data'];
    $orderId = $paymentIntent['metadata']['order_id'] ?? null;
    
    if ($orderId) {
        // Update order status in database
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Approved', payment_method = 'ewallet' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // You might want to send a confirmation email here
    }
}

http_response_code(200);
echo 'Webhook received';
?>