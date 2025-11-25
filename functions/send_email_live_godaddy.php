<?php

header("Access-Control-Allow-Origin: https://fishbrokers.net");
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
$honeypot = $_POST['website'] ?? '';

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
    
    // Validate contact number format
    if (!empty($contact) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $contact)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid contact number format.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // =============================================
        // CHOOSE ONE OF THESE OPTIONS FOR PROFESSIONAL EMAIL
        // =============================================
        
        // OPTION 1: GoDaddy Professional Email (Recommended)
        $mail->isSMTP();
        $mail->Host = 'smtp.secureserver.net'; // GoDaddy professional email server
        $mail->SMTPAuth = true;
        $mail->Username = 'contact@fishbrokers.net'; // Your professional email
        $mail->Password = 'your-email-password'; // Password for that email account
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // OPTION 2: Localhost (if professional email doesn't work)
        /*
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->SMTPAuth = false;
        $mail->Port = 25;
        $mail->SMTPSecure = '';
        */
        
        $mail->SMTPDebug = 0;
        
        // Security headers
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        
        // Professional sender configuration
        $mail->setFrom('contact@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
        
        // Send to multiple professional addresses
        $mail->addAddress('info@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.'); // Main inbox
        $mail->addAddress('contact@fishbrokers.net', 'Contact Department'); // Contact-specific
        // $mail->addAddress('management@fishbrokers.net', 'Management Team'); // Add more as needed
        
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);
        
        $mail->Subject = "Website Inquiry: " . $subject;
        $mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
                <style>
                    body { 
                        font-family: 'Arial', sans-serif; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        margin: 0; 
                        padding: 20px; 
                    }
                    .container { 
                        max-width: 700px; 
                        background: white; 
                        padding: 30px; 
                        border-radius: 15px; 
                        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                        margin: 20px auto;
                    }
                    .header { 
                        background: linear-gradient(135deg, #F2571B 0%, #FF6B35 100%);
                        color: white; 
                        padding: 25px; 
                        border-radius: 10px; 
                        text-align: center;
                        margin-bottom: 25px;
                    }
                    .header h2 {
                        margin: 0;
                        font-size: 28px;
                        font-weight: bold;
                    }
                    .company-info {
                        text-align: center;
                        margin-bottom: 20px;
                        color: #666;
                        font-style: italic;
                    }
                    .field { 
                        margin: 15px 0; 
                        padding: 12px 15px; 
                        background: #f8f9fa; 
                        border-radius: 8px; 
                        border-left: 4px solid #F2571B;
                    }
                    .label { 
                        font-weight: bold; 
                        color: #2c3e50;
                        display: block;
                        margin-bottom: 5px;
                        font-size: 14px;
                    }
                    .value {
                        color: #34495e;
                        font-size: 16px;
                    }
                    .message-content {
                        background: #fff;
                        border: 1px solid #e9ecef;
                        border-radius: 8px;
                        padding: 15px;
                        margin-top: 10px;
                        line-height: 1.6;
                    }
                    .footer {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px solid #e9ecef;
                        text-align: center;
                        color: #7f8c8d;
                        font-size: 12px;
                    }
                    .logo {
                        text-align: center;
                        margin-bottom: 15px;
                    }
                    .logo-text {
                        font-size: 24px;
                        font-weight: bold;
                        color: #F2571B;
                        margin-bottom: 5px;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='logo'>
                        <div class='logo-text'>St. Joseph Fish Brokerage Inc.</div>
                        <div style='color: #666; font-size: 14px;'>Fresh Seafood Excellence</div>
                    </div>
                    
                    <div class='header'>
                        <h2>📧 New Website Inquiry</h2>
                    </div>
                    
                    <div class='company-info'>
                        A new message has been received through the website contact form
                    </div>

                    <div class='field'>
                        <span class='label'>👤 Contact Person</span>
                        <span class='value'>{$firstName} {$lastName}</span>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>📧 Email Address</span>
                        <span class='value'>{$email}</span>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>📞 Contact Number</span>
                        <span class='value'>" . (!empty($contact) ? $contact : 'Not provided') . "</span>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>🎯 Subject</span>
                        <span class='value'>{$subject}</span>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>💬 Message</span>
                        <div class='message-content'>{$message}</div>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>🌐 Submission Details</span>
                        <div style='margin-top: 8px;'>
                            <strong>IP Address:</strong> {$_SERVER['REMOTE_ADDR']}<br>
                            <strong>Submission Time:</strong> " . date('F j, Y \a\t g:i A') . "<br>
                            <strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "
                        </div>
                    </div>

                    <div class='footer'>
                        <p>This email was automatically generated from the contact form on fishbrokers.net</p>
                        <p>© " . date('Y') . " St. Joseph Fish Brokerage Inc. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "NEW WEBSITE INQUIRY\n\n" .
            "Contact Person: {$firstName} {$lastName}\n" .
            "Email: {$email}\n" .
            "Contact: " . (!empty($contact) ? $contact : 'Not provided') . "\n" .
            "Subject: {$subject}\n" .
            "Message: {$message}\n\n" .
            "Submission Details:\n" .
            "IP Address: {$_SERVER['REMOTE_ADDR']}\n" .
            "Time: " . date('F j, Y \a\t g:i A') . "\n" .
            "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "\n\n" .
            "This inquiry was submitted through the website contact form.";

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
            error_log("Professional contact form submitted from: " . $email . " IP: " . $_SERVER['REMOTE_ADDR']);
            echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully! We will get back to you soon.']);
        } else {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        error_log("Professional email sending failed from IP: " . $_SERVER['REMOTE_ADDR'] . " Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later or contact us directly.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>