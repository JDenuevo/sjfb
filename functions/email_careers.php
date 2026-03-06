<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Debug: Log received data
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    
    // Honeypot check
    $honeypot = $_POST['website'] ?? '';
    if (!empty($honeypot)) {
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit;
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Security validation failed.']);
        exit;
    }
    
    // Get form data
    $firstName = htmlspecialchars(trim($_POST['firstName'] ?? ''));
    $lastName = htmlspecialchars(trim($_POST['lastName'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $contact = htmlspecialchars(trim($_POST['contact'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    if (empty($firstName) || empty($lastName) || !$email || empty($subject) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Use GoDaddy's local mail relay (no authentication needed)
        $mail->isSMTP();
        $mail->Host = 'localhost'; // Use localhost for GoDaddy relay
        $mail->SMTPAuth = false;    // No authentication for local relay
        $mail->Port = 25;            // Standard SMTP port
        $mail->SMTPAutoTLS = false;  // Disable TLS for local connection
        $mail->SMTPSecure = false;   // No encryption for local
        
        // Alternative GoDaddy relay host if localhost doesn't work
        // $mail->Host = 'relay-hosting.secureserver.net';
        
        $mail->SMTPDebug = 0; // Set to 2 for debugging, 0 for production
        $mail->Debugoutput = 'error_log';
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';
        
        // Sender - Must use an email from your GoDaddy account
        $mail->setFrom('marketing@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);
        
        // Recipients
        $mail->addAddress('marketing@fishbrokers.net', 'Marketing Department');
        $mail->addAddress('stjosephbrokerage23@gmail.com', 'St. Joseph Fish Brokerage Inc.');
        
        $mail->Subject = "New Contact Form: " . $subject . " - From: " . $firstName . " " . $lastName;
        $mail->isHTML(true);
        
        // HTML Email Body with attachment info
        $attachmentInfo = '';
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $fileCount = count(array_filter($_FILES['attachments']['name']));
            $attachmentInfo = "<p><strong>📎 Attachments:</strong> {$fileCount} file(s) attached</p>";
        }
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #F2571B; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                .field { margin: 15px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #F2571B; }
                .label { font-weight: bold; color: #555; }
                .value { margin-top: 5px; }
                .message-box { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2 style='margin:0;'>📧 New Contact Form Submission</h2>
            </div>
            
            <div class='field'>
                <div class='label'>👤 Name</div>
                <div class='value'><strong>{$firstName} {$lastName}</strong></div>
            </div>
            
            <div class='field'>
                <div class='label'>📧 Email</div>
                <div class='value'><a href='mailto:{$email}' style='color: #F2571B;'>{$email}</a></div>
            </div>
            
            <div class='field'>
                <div class='label'>📞 Contact Number</div>
                <div class='value'>{$contact}</div>
            </div>
            
            <div class='field'>
                <div class='label'>📌 Subject</div>
                <div class='value'><strong>{$subject}</strong></div>
            </div>
            
            <div class='field'>
                <div class='label'>💬 Message</div>
                <div class='message-box'>{$message}</div>
            </div>
            
            {$attachmentInfo}
            
            <div class='field'>
                <div class='label'>🌐 Submission Details</div>
                <div class='value'>
                    IP Address: {$_SERVER['REMOTE_ADDR']}<br>
                    Time: " . date('F j, Y \a\t g:i A') . "<br>
                    User Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200) . "
                </div>
            </div>
            
            <div class='footer'>
                <p>This email was sent from the contact form on fishbrokers.net</p>
                <p>© " . date('Y') . " St. Joseph Fish Brokerage Inc. All rights reserved.</p>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "New Contact Form Submission\n\n" .
                        "Name: {$firstName} {$lastName}\n" .
                        "Email: {$email}\n" .
                        "Contact: {$contact}\n" .
                        "Subject: {$subject}\n" .
                        "Message: {$message}\n\n" .
                        "IP: {$_SERVER['REMOTE_ADDR']}\n" .
                        "Time: " . date('Y-m-d H:i:s');
        
        // **FIXED: Handle file attachments properly**
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $fileCount = count($_FILES['attachments']['name']);
            $successfulAttachments = 0;
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                    $fileName = $_FILES['attachments']['name'][$i];
                    $fileSize = $_FILES['attachments']['size'][$i];
                    $fileType = $_FILES['attachments']['type'][$i];
                    
                    // Validate file size (max 5MB)
                    if ($fileSize > 5 * 1024 * 1024) {
                        error_log("File too large: {$fileName} ({$fileSize} bytes)");
                        continue;
                    }
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    if (!in_array($fileType, $allowedTypes)) {
                        error_log("Invalid file type: {$fileType} for file {$fileName}");
                        continue;
                    }
                    
                    // Sanitize filename
                    $safeFileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
                    $safeFileName = substr($safeFileName, 0, 100);
                    
                    // Add attachment
                    $mail->addAttachment($fileTmpPath, $safeFileName);
                    $successfulAttachments++;
                    error_log("Added attachment: {$safeFileName}");
                } else {
                    error_log("Upload error for file {$i}: " . $_FILES['attachments']['error'][$i]);
                }
            }
            
            error_log("Successfully attached {$successfulAttachments} of {$fileCount} files");
        }
        
        // Send email
        if ($mail->send()) {
            error_log("Email sent successfully with " . (isset($successfulAttachments) ? $successfulAttachments : 0) . " attachments");
            echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        } else {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        
        // **Fallback: Try with different SMTP settings**
        try {
            $mail = new PHPMailer(true);
            
            // Try alternative GoDaddy SMTP
            $mail->isSMTP();
            $mail->Host = 'relay-hosting.secureserver.net';
            $mail->SMTPAuth = false;
            $mail->Port = 25;
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
            
            $mail->setFrom('marketing@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
            $mail->addReplyTo($email, $firstName . ' ' . $lastName);
            $mail->addAddress('marketing@fishbrokers.net', 'Marketing Department');
            $mail->addAddress('stjosephbrokerage23@gmail.com', 'St. Joseph Fish Brokerage Inc.');
            
            $mail->Subject = "New Contact Form: " . $subject;
            $mail->isHTML(true);
            $mail->Body = $mail->Body; // Reuse the HTML body
            $mail->AltBody = $mail->AltBody; // Reuse the plain text body
            
            // Add attachments again
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $mail->addAttachment($_FILES['attachments']['tmp_name'][$i], $_FILES['attachments']['name'][$i]);
                    }
                }
            }
            
            if ($mail->send()) {
                echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
                exit;
            }
        } catch (Exception $e2) {
            error_log("Fallback email also failed: " . $e2->getMessage());
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>