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
?>