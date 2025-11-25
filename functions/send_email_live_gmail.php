<?php

header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Rate limiting function
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'contact_form_' . md5($ip);
    $currentTime = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_attempt' => $currentTime
        ];
        return true;
    }
    
    $limitData = $_SESSION[$key];
    $timeWindow = 900; // 15 minutes
    
    if (($currentTime - $limitData['first_attempt']) > $timeWindow) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_attempt' => $currentTime
        ];
        return true;
    }
    
    if ($limitData['count'] >= 3) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Validate CSRF token
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Honeypot field - hidden from real users
$honeypot = $_POST['website'] ?? ''; // Changed from 'company' to 'website'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check rate limit
    if (!checkRateLimit()) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again in 15 minutes.']);
        exit;
    }
    
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Security validation failed.']);
        exit;
    }
    
    // Honeypot check - if this hidden field is filled, it's likely a bot
    if (!empty($honeypot)) {
        error_log("Bot detected via honeypot from IP: " . $_SERVER['REMOTE_ADDR']);
        // Return fake success to confuse bots
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit;
    }
    
    // Sanitize and validate inputs
    $firstName = substr(htmlspecialchars(trim($_POST['firstName'] ?? '')), 0, 50);
    $lastName = substr(htmlspecialchars(trim($_POST['lastName'] ?? '')), 0, 50);
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $contact = substr(htmlspecialchars(trim($_POST['contact'] ?? '')), 0, 20);
    $subject = substr(htmlspecialchars(trim($_POST['subject'] ?? '')), 0, 100);
    $message = substr(htmlspecialchars(trim($_POST['message'] ?? '')), 0, 2000);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || !$email || empty($subject) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    
    // Additional email validation
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $_POST['email'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }
    
    // Validate contact number format (optional field)
    if (!empty($contact) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $contact)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid contact number format.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // GoDaddy SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->SMTPAuth = false;
        $mail->Port = 25;
        $mail->SMTPSecure = '';
        $mail->SMTPDebug = 0;
        
        // Security headers
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        
        // Sender configuration
        $domain = $_SERVER['HTTP_HOST'];
        $domainEmail = 'noreply@' . $domain;
        
        if (!filter_var($domainEmail, FILTER_VALIDATE_EMAIL)) {
            $domainEmail = 'noreply@fishbrokers.net';
        }
        
        $mail->setFrom($domainEmail, 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress('denuevojhemar.sjfb@gmail.com', 'St. Joseph Fish Brokerage Inc.');
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);
        
        $mail->Subject = "New Contact Form: " . $subject;
        $mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
                <style>
                    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                    .container { max-width: 600px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: #F2571B; color: white; padding: 15px; border-radius: 5px; text-align: center; }
                    .field { margin: 10px 0; padding: 8px; background: #f9f9f9; border-radius: 4px; }
                    .label { font-weight: bold; color: #333; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Contact Form Submission</h2>
                    </div>
                    <div class='field'><span class='label'>Name:</span> {$firstName} {$lastName}</div>
                    <div class='field'><span class='label'>Email:</span> {$email}</div>
                    <div class='field'><span class='label'>Contact:</span> {$contact}</div>
                    <div class='field'><span class='label'>Subject:</span> {$subject}</div>
                    <div class='field'><span class='label'>Message:</span><br>{$message}</div>
                    <div class='field'><span class='label'>IP Address:</span> {$_SERVER['REMOTE_ADDR']}</div>
                    <div class='field'><span class='label'>Time:</span> " . date('Y-m-d H:i:s') . "</div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "New Contact Form Submission\nName: {$firstName} {$lastName}\nEmail: {$email}\nContact: {$contact}\nSubject: {$subject}\nMessage: {$message}\nIP: {$_SERVER['REMOTE_ADDR']}\nTime: " . date('Y-m-d H:i:s');

        // File attachment handling
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $totalSize = 0;
            $maxTotalSize = 5 * 1024 * 1024;
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png', 
                'application/pdf' => 'pdf'
            ];
            
            $attachmentCount = 0;
            $maxAttachments = 3;
            
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK && $attachmentCount < $maxAttachments) {
                    $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                    $fileName = $_FILES['attachments']['name'][$i];
                    $fileSize = $_FILES['attachments']['size'][$i];
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $fileType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $isValidType = in_array($fileType, array_keys($allowedTypes));
                    $isValidExt = in_array($fileExt, array_values($allowedTypes));
                    
                    if ($isValidType && $isValidExt && $fileSize <= 2 * 1024 * 1024) {
                        $totalSize += $fileSize;
                        if ($totalSize <= $maxTotalSize) {
                            $safeFileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
                            $safeFileName = substr($safeFileName, 0, 100);
                            
                            $mail->addAttachment($fileTmpPath, $safeFileName);
                            $attachmentCount++;
                        }
                    }
                }
            }
        }

        if ($mail->send()) {
            error_log("Contact form submitted from: " . $email . " IP: " . $_SERVER['REMOTE_ADDR']);
            echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        } else {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed from IP: " . $_SERVER['REMOTE_ADDR'] . " Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>