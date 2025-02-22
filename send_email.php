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
use Dotenv\Dotenv; // Import Dotenv class

require './vendor/autoload.php'; // Autoload dependencies

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
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
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];  // From .env file
        $mail->Password   = $_ENV['MAIL_PASSWORD'];  // From .env file
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress('denuevojhemar.sjfb@gmail.com', 'St. Joseph Fish Brokerage Inc.');
        $mail->addReplyTo($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.');

        $mail->isHTML(true);
        $mail->Subject = $subject;
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

        // Handle multiple file attachments
        if (isset($_FILES['attachments']) && count($_FILES['attachments']['name']) > 0) {
            $fileCount = count($_FILES['attachments']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    if ($_FILES['attachments']['size'][$i] <= 2 * 1024 * 1024) { // Max file size: 2 MB
                        $mail->addAttachment($_FILES['attachments']['tmp_name'][$i], $_FILES['attachments']['name'][$i]);
                    } else {
                        echo json_encode(['status' => 'warning', 'message' => 'Some files exceeded the size limit of 2MB.']);
                    }
                }
            }
        }

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
