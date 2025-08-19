<?php
require_once 'paymongo_helper.php';
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    $required = ['payment_intent_id', 'payment_method', 'amount', 'order_id'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Initialize PayMongo
    $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);

    // Create payment method
    $paymentMethod = $paymongo->createPaymentMethod($input['payment_method'], [
        'billing' => [
            'email' => $_SESSION['email'] ?? '',
            'name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
            'phone' => $_SESSION['phone_number']
        ]
    ]);

    // Attach to payment intent
    $response = $paymongo->attachPaymentMethod(
        $input['payment_intent_id'],
        $paymentMethod['data']['id'],
        $input['return_url'] ?? null
    );

    // Update database
    require_once '../conn.php';
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_method = ?, 
            order_status = 'Processing' 
        WHERE order_id = ?
    ");
    $stmt->bind_param("si", $input['payment_method'], $input['order_id']);
    $stmt->execute();

    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}