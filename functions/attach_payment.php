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

    $required = ['payment_intent_id', 'payment_method_id', 'payment_type'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
    $response = $paymongo->attachPaymentMethod(
        $input['payment_intent_id'],
        $input['payment_method_id'],
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
    $stmt->bind_param("si", $input['payment_type'], $_SESSION['order_id']);
    $stmt->execute();

    // Create payment record
    $paymentStmt = $conn->prepare("
        INSERT INTO payments (
            order_id, payment_intent_id, payment_method, amount, 
            currency, payment_status, client_key
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $paymentStmt->bind_param(
        "issdsss",
        $_SESSION['order_id'],
        $input['payment_intent_id'],
        $input['payment_type'],
        $_SESSION['total_amount'],
        'PHP',
        $response['data']['attributes']['status'],
        $_SESSION['client_key']
    );
    $paymentStmt->execute();

    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}