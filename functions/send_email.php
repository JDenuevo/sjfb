<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Correct the path for autoloader - adjust based on your actual structure
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables - adjust path as needed
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $firstName = htmlspecialchars($_POST['firstName'] ?? '');
    $lastName = htmlspecialchars($_POST['lastName'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $contact = htmlspecialchars($_POST['contact'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration with debugging
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Enable verbose debug output
        $mail->SMTPDebug = 2; // Set to 2 for detailed debug info
        $mail->Debugoutput = function($str, $level) {
            file_put_contents(__DIR__ . '/smtp_debug.log', "$level: $str\n", FILE_APPEND);
        };

        // Sender and recipient
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.'); // Send to yourself
        $mail->addReplyTo($email, $firstName . ' ' . $lastName); // Reply to user's email

        $mail->isHTML(true);
        $mail->Subject = "New Contact Form: " . $subject;
        
        // Simple email body for testing
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f4f4f4;
                        margin: 0;
                        padding: 0;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 20px auto;
                        background-color: #fff;
                        border-radius: 8px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                        overflow: hidden;
                    }
                    .email-header {
                        text-align: center;
                        background-color: #F2571B;
                        color: white;
                        padding: 10px 0;
                    }
                    .email-body {
                        padding: 20px;
                        color: #333;
                        line-height: 1.6;
                    }
                    .email-footer {
                        text-align: center;
                        padding: 10px 0;
                        background-color: #f4f4f4;
                        color: #666;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <h2>$subject</h2>
                    </div>
                    <div class='email-body'>
                        <p><strong>Name:</strong> $firstName $lastName</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Contact:</strong> $contact</p>
                        <p><strong>Message:</strong> $message</p>
                    </div>
                    <div class='email-footer'>
                        <p>St. Joseph Fish Brokerage Inc.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "New Contact Form Submission\nName: {$firstName} {$lastName}\nEmail: {$email}\nContact: {$contact}\nSubject: {$subject}\nMessage: {$message}";

        // Handle file attachments
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                    $fileName = $_FILES['attachments']['name'][$i];
                    $fileSize = $_FILES['attachments']['size'][$i];
                    
                    if ($fileSize <= 2 * 1024 * 1024) { // 2MB
                        $mail->addAttachment($fileTmpPath, $fileName);
                    }
                }
            }
        }

        if ($mail->send()) {
            echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        } else {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
