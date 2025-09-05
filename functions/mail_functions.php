<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendEmail($to, $subject, $message, $isHtml = false) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = trim($_ENV['MAIL_PASSWORD'], '"\' ');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;

        // Recipients
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        if (!$isHtml) {
            $mail->AltBody = strip_tags($message);
        }

        if (!$mail->send()) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        
        error_log("Email successfully sent to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        return false;
    }
}

function getOrderDetails($orderId, $conn) {
    $stmt = $conn->prepare("
        SELECT o.*, 
               COALESCE(a.first_name, o.first_name) as customer_first_name,
               COALESCE(a.last_name, o.last_name) as customer_last_name,
               COALESCE(a.email, o.email) as customer_email
        FROM orders o 
        LEFT JOIN accounts a ON o.account_id = a.account_id 
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getOrderItems($orderId, $conn) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.product_name, pv.variant_name, pv.weight
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function sendPaymentConfirmationEmail($orderId) {
    global $conn;
    
    try {
        $order = getOrderDetails($orderId, $conn);
        if (!$order) {
            error_log("Order not found for confirmation email: " . $orderId);
            return false;
        }
        
        $items = getOrderItems($orderId, $conn);
        
        $subject = "Payment Confirmation - Order #" . $orderId;
        
        // Create HTML email content
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; }
                .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background-color: #007bff; color: white; }
                .total { font-weight: bold; font-size: 18px; color: #007bff; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Payment Confirmed!</h1>
                <p>Thank you for your order, " . htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']) . "</p>
            </div>
            
            <div class='content'>
                <p>We're pleased to confirm that your payment has been successfully processed.</p>
                
                <div class='order-details'>
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #" . $orderId . "</p>
                    <p><strong>Order Date:</strong> " . date('F j, Y', strtotime($order['order_date'])) . "</p>
                    <p><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>
                    <p><strong>Order Status:</strong> " . $order['order_status'] . "</p>
                </div>
                
                <h3>Items Ordered</h3>
                <table class='items-table'>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>";
        
        foreach ($items as $item) {
            $variantInfo = $item['variant_name'] ? $item['variant_name'] . ' (' . $item['weight'] . ')' : 'Standard';
            $subtotal = $item['quantity'] * $item['price'];
            $message .= "
                    <tr>
                        <td>" . htmlspecialchars($item['product_name']) . "</td>
                        <td>" . htmlspecialchars($variantInfo) . "</td>
                        <td>" . $item['quantity'] . "</td>
                        <td>₱" . number_format($item['price'], 2) . "</td>
                        <td>₱" . number_format($subtotal, 2) . "</td>
                    </tr>";
        }
        
        $message .= "
                </table>
                
                <div class='total'>
                    <p>Total Amount: ₱" . number_format($order['total_price'], 2) . "</p>
                </div>
                
                <h3>Delivery Information</h3>
                <div class='order-details'>
                    <p><strong>Delivery Address:</strong><br>
                    " . htmlspecialchars($order['address']) . "<br>
                    " . htmlspecialchars($order['city']) . ", " . htmlspecialchars($order['postal_code']) . "</p>
                    <p><strong>Contact Number:</strong> " . htmlspecialchars($order['phone_number']) . "</p>
                </div>
                
                <p>Your order will be processed and prepared for delivery. You will receive another email with tracking information once your order ships.</p>
                
                <p>Thank you for choosing St. Joseph Fish Brokerage Inc.!</p>
                
                <hr>
                <p><small>This is an automated email. Please do not reply to this message.</small></p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['customer_email'], $subject, $message, true);
        
    } catch (Exception $e) {
        error_log("Error sending payment confirmation email: " . $e->getMessage());
        return false;
    }
}

function sendPaymentFailedEmail($orderId) {
    global $conn;
    
    try {
        $order = getOrderDetails($orderId, $conn);
        if (!$order) {
            error_log("Order not found for failure email: " . $orderId);
            return false;
        }
        
        $subject = "Payment Failed - Order #" . $orderId;
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background-color: #f8f9fa; padding: 15px; margin: 15px 0; }
                .retry-button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Payment Failed</h1>
                <p>Order #" . $orderId . "</p>
            </div>
            
            <div class='content'>
                <p>Dear " . htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']) . ",</p>
                
                <p>We were unable to process your payment for Order #" . $orderId . ". This could be due to:</p>
                <ul>
                    <li>Insufficient funds</li>
                    <li>Expired payment method</li>
                    <li>Network connectivity issues</li>
                    <li>Payment method declined by your bank</li>
                </ul>
                
                <div class='order-details'>
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #" . $orderId . "</p>
                    <p><strong>Order Date:</strong> " . date('F j, Y', strtotime($order['order_date'])) . "</p>
                    <p><strong>Total Amount:</strong> ₱" . number_format($order['total_price'], 2) . "</p>
                    <p><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>
                </div>
                
                <p><strong>What's Next?</strong></p>
                <p>Your order is currently on hold. You can:</p>
                <ul>
                    <li>Try paying again with the same payment method</li>
                    <li>Use a different payment method</li>
                    <li>Contact your bank to ensure the payment method is working</li>
                    <li>Contact us for assistance</li>
                </ul>
                
                <p>If you need help or have questions about this order, please contact us at:</p>
                <p>Email: " . $_ENV['MAIL_USERNAME'] . "<br>
                Phone: [Your Phone Number]</p>
                
                <p>Thank you for your understanding.</p>
                
                <hr>
                <p><small>This is an automated email. Please do not reply to this message.</small></p>
            </div>
        </body>
        </html>";
        
        return sendEmail($order['customer_email'], $subject, $message, true);
        
    } catch (Exception $e) {
        error_log("Error sending payment failed email: " . $e->getMessage());
        return false;
    }
}
?>