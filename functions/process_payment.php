<?php
session_start();
require_once '../conn.php';
require_once '../vendor/autoload.php';
require_once 'paymongo_helper.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

try {
    // Validate session data
    if (empty($_SESSION['payment_intent_id']) || empty($_SESSION['payment_details'])) {
        throw new Exception("Invalid payment session");
    }

    $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);

    // Handle different payment methods
    switch ($_SESSION['payment_method']) {
        case 'card':
            // For card payments, we need the payment method ID from the client
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['payment_method_id'])) {
                throw new Exception("Payment method ID is required");
            }
            
            $paymentMethodId = $input['payment_method_id'];
            break;
            
        case 'gcash':
        case 'paymaya':
        case 'grab_pay':
            // For e-wallets, create the payment method
            $paymentMethod = $paymongo->createPaymentMethod(
                $_SESSION['payment_method'],
                [
                    'billing' => [
                        'email' => $_SESSION['payment_details']['customer_email'],
                        'name' => $_SESSION['payment_details']['customer_name'],
                        'phone' => $_SESSION['payment_details']['phone_number']
                    ]
                ]
            );
            $paymentMethodId = $paymentMethod['data']['id'];
            break;
            
        default:
            throw new Exception("Unsupported payment method");
    }

    // Attach payment method to payment intent
    $response = $paymongo->attachPaymentMethod(
        $_SESSION['payment_intent_id'],
        $paymentMethodId,
        $_SESSION['payment_details']['return_url'] ?? (getenv('APP_URL') . '/order_success.php')
    );

    // Update database
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_method = ?, 
            order_status = 'Processing' 
        WHERE order_id = ?
    ");
    $stmt->bind_param("si", $_SESSION['payment_method'], $_SESSION['payment_details']['order_id']);
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
        $_SESSION['payment_details']['order_id'],
        $_SESSION['payment_intent_id'],
        $_SESSION['payment_method'],
        $_SESSION['payment_details']['total_amount'],
        'PHP',
        $response['data']['attributes']['status'],
        $_SESSION['client_key']
    );
    $paymentStmt->execute();

    // Return the response to client
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}