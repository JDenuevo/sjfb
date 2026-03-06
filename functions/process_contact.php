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

// Database connection
require_once __DIR__ . '/../conn.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to save inquiry to database
function saveInquiryToDB($data, $type) {
    global $conn;
    
    $firstName = mysqli_real_escape_string($conn, $data['firstName']);
    $lastName = mysqli_real_escape_string($conn, $data['lastName']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $contact = mysqli_real_escape_string($conn, $data['contact'] ?? '');
    $subject = mysqli_real_escape_string($conn, $data['subject'] ?? '');
    $message = mysqli_real_escape_string($conn, $data['message'] ?? '');
    $sender_type = mysqli_real_escape_string($conn, $data['sender_type'] ?? '');
    $market = mysqli_real_escape_string($conn, $data['market'] ?? '');
    $ip_address = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
    $user_agent = mysqli_real_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255));
    
    $sql = "INSERT INTO contact_inquiries (
        form_type, first_name, last_name, email, contact, 
        subject, message, sender_type, market, ip_address, user_agent, created_at
    ) VALUES (
        '$type', '$firstName', '$lastName', '$email', '$contact',
        '$subject', '$message', '$sender_type', '$market', '$ip_address', '$user_agent', NOW()
    )";
    
    return mysqli_query($conn, $sql);
}

// Function to save job application to database
function saveApplicationToDB($data) {
    global $conn;
    
    $firstName = mysqli_real_escape_string($conn, $data['firstName']);
    $lastName = mysqli_real_escape_string($conn, $data['lastName']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $contact = mysqli_real_escape_string($conn, $data['contact']);
    $age = mysqli_real_escape_string($conn, $data['age'] ?? '');
    $address = mysqli_real_escape_string($conn, $data['address']);
    $apply_location = mysqli_real_escape_string($conn, $data['apply_location']);
    $position = mysqli_real_escape_string($conn, $data['position'] ?? '');
    $position_other = mysqli_real_escape_string($conn, $data['position_other'] ?? '');
    $experience_years = mysqli_real_escape_string($conn, $data['experience_years']);
    $industry_tags = mysqli_real_escape_string($conn, $data['industry_tags'] ?? '');
    $work_history = mysqli_real_escape_string($conn, $data['work_history']);
    $start_date = mysqli_real_escape_string($conn, $data['start_date']);
    $work_type = mysqli_real_escape_string($conn, $data['work_type'] ?? '');
    $expected_salary = mysqli_real_escape_string($conn, $data['expected_salary'] ?? '');
    $extra_notes = mysqli_real_escape_string($conn, $data['extra_notes'] ?? '');
    $ip_address = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
    $user_agent = mysqli_real_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255));
    
    // Generate application reference number
    $app_ref = 'APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    $sql = "INSERT INTO job_applications (
        application_ref, first_name, last_name, email, contact, age, address,
        apply_location, position, position_other, experience_years, industry_tags,
        work_history, start_date, work_type, expected_salary, extra_notes,
        ip_address, user_agent, created_at
    ) VALUES (
        '$app_ref', '$firstName', '$lastName', '$email', '$contact', '$age', '$address',
        '$apply_location', '$position', '$position_other', '$experience_years', '$industry_tags',
        '$work_history', '$start_date', '$work_type', '$expected_salary', '$extra_notes',
        '$ip_address', '$user_agent', NOW()
    )";
    
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'app_ref' => $app_ref, 'id' => mysqli_insert_id($conn)];
    } else {
        error_log("DB Error: " . mysqli_error($conn));
        return ['success' => false, 'error' => mysqli_error($conn)];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Debug: Log received data
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    
    // Honeypot check (for both forms)
    $honeypot = $_POST['website'] ?? $_POST['website2'] ?? '';
    if (!empty($honeypot)) {
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit;
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Security validation failed. Please refresh the page.']);
        exit;
    }
    
    // Determine form type
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type === 'inquiry') {
        // Handle General Inquiry
        $firstName = htmlspecialchars(trim($_POST['firstName'] ?? ''));
        $lastName = htmlspecialchars(trim($_POST['lastName'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $contact = htmlspecialchars(trim($_POST['contact'] ?? ''));
        $sender_type = htmlspecialchars(trim($_POST['sender_type'] ?? ''));
        $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
        $message = htmlspecialchars(trim($_POST['message'] ?? ''));
        $market = htmlspecialchars(trim($_POST['market'] ?? ''));
        
        if (empty($firstName) || empty($lastName) || !$email || empty($sender_type) || empty($subject) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled out.']);
            exit;
        }
        
        // Save to database
        $db_saved = saveInquiryToDB($_POST, 'inquiry');
        
        $mail = new PHPMailer(true);
        
        try {
            // Use GoDaddy's local mail relay
            $mail->isSMTP();
            $mail->Host = 'localhost';
            $mail->SMTPAuth = false;
            $mail->Port = 25;
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
            
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'error_log';
            $mail->Timeout = 30;
            $mail->CharSet = 'UTF-8';
            
            // Sender
            $mail->setFrom('marketing@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
            $mail->addReplyTo($email, $firstName . ' ' . $lastName);
            
            // Recipients - General Inquiry only goes to marketing and gmail
            $mail->addAddress('marketing@fishbrokers.net', 'Marketing Department');
            $mail->addAddress('stjosephbrokerage23@gmail.com', 'St. Joseph Fish Brokerage Inc.');
            
            $mail->Subject = "General Inquiry: " . $subject . " - From: " . $firstName . " " . $lastName;
            $mail->isHTML(true);
            
            // Get sender type label
            $senderTypeLabels = [
                'buyer' => 'Buyer / Restaurant / Business',
                'fisherman' => 'Fisherman / Supplier',
                'processor' => 'Fish Processor / Exporter',
                'partner' => 'Potential Business Partner',
                'media' => 'Media / Researcher',
                'other' => 'Other'
            ];
            $senderTypeLabel = $senderTypeLabels[$sender_type] ?? $sender_type;
            
            // Market location label
            $marketLabels = [
                'navotas' => 'Navotas Fish Port Complex',
                'malabon' => 'Malabon Consignacion',
                'davao' => 'Davao Toril Fish Port'
            ];
            $marketLabel = $marketLabels[$market] ?? ($market ?: 'Not specified');
            
            // HTML Email Body
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
                    .header { background: linear-gradient(135deg, #F2571B 0%, #FF6B35 100%); color: white; padding: 25px; border-radius: 10px; margin-bottom: 25px; text-align: center; }
                    .field { margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #F2571B; border-radius: 5px; }
                    .label { font-weight: bold; color: #555; display: block; margin-bottom: 5px; }
                    .value { color: #1a2634; }
                    .message-box { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2 style='margin:0;'>📧 General Inquiry</h2>
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
                    <div class='label'>👤 I am a...</div>
                    <div class='value'>{$senderTypeLabel}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>📌 Subject</div>
                    <div class='value'><strong>{$subject}</strong></div>
                </div>
                
                <div class='field'>
                    <div class='label'>📍 Preferred Market</div>
                    <div class='value'>{$marketLabel}</div>
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
                        " . ($db_saved ? "✅ Saved to database" : "⚠️ Database save failed") . "
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This inquiry was sent from the contact form on fishbrokers.net</p>
                    <p>© 2024 St. Joseph Fish Brokerage Inc. All rights reserved.</p>
                </div>
            </body>
            </html>";
            
            $mail->AltBody = "General Inquiry\n\n" .
                            "Name: {$firstName} {$lastName}\n" .
                            "Email: {$email}\n" .
                            "Contact: {$contact}\n" .
                            "I am a: {$senderTypeLabel}\n" .
                            "Subject: {$subject}\n" .
                            "Market: {$marketLabel}\n" .
                            "Message: {$message}\n\n" .
                            "IP: {$_SERVER['REMOTE_ADDR']}\n" .
                            "Time: " . date('Y-m-d H:i:s');
            
            // Handle file attachments
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $fileCount = count($_FILES['attachments']['name']);
                $successfulAttachments = 0;
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['attachments']['tmp_name'][$i];
                        $fileName = $_FILES['attachments']['name'][$i];
                        $fileSize = $_FILES['attachments']['size'][$i];
                        
                        // Validate file size (max 5MB)
                        if ($fileSize > 5 * 1024 * 1024) {
                            error_log("File too large: {$fileName} ({$fileSize} bytes)");
                            continue;
                        }
                        
                        // Sanitize filename
                        $safeFileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
                        $safeFileName = substr($safeFileName, 0, 100);
                        
                        $mail->addAttachment($fileTmpPath, $safeFileName);
                        $successfulAttachments++;
                    }
                }
            }
            
            if ($mail->send()) {
                echo json_encode(['status' => 'success', 'message' => 'Your inquiry has been sent successfully!']);
            } else {
                throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            error_log("Inquiry email failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to send inquiry. Please try again later.']);
        }
        
    } elseif ($form_type === 'career') {
        // Handle Career Application
        $firstName = htmlspecialchars(trim($_POST['firstName'] ?? ''));
        $lastName = htmlspecialchars(trim($_POST['lastName'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $contact = htmlspecialchars(trim($_POST['contact'] ?? ''));
        $address = htmlspecialchars(trim($_POST['address'] ?? ''));
        $apply_location = htmlspecialchars(trim($_POST['apply_location'] ?? ''));
        $position = $_POST['position'] ?? '';
        $experience_years = htmlspecialchars(trim($_POST['experience_years'] ?? ''));
        $work_history = htmlspecialchars(trim($_POST['work_history'] ?? ''));
        $start_date = htmlspecialchars(trim($_POST['start_date'] ?? ''));
        
        if (empty($firstName) || empty($lastName) || !$email || empty($contact) || 
            empty($address) || empty($apply_location) || empty($position) || 
            empty($experience_years) || empty($work_history) || empty($start_date)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled out.']);
            exit;
        }
        
        // Save to database first
        $db_result = saveApplicationToDB($_POST);
        
        if (!$db_result['success']) {
            error_log("Failed to save application to database");
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Use GoDaddy's local mail relay
            $mail->isSMTP();
            $mail->Host = 'localhost';
            $mail->SMTPAuth = false;
            $mail->Port = 25;
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
            
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'error_log';
            $mail->Timeout = 30;
            $mail->CharSet = 'UTF-8';
            
            // Sender
            $mail->setFrom('marketing@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
            $mail->addReplyTo($email, $firstName . ' ' . $lastName);
            
            // Recipients - Career applications go to HRD and backup
            $mail->addAddress('hrd@fishbrokers.net', 'HR Department');
            $mail->addAddress('stjosephbrokerage23@gmail.com', 'St. Joseph Fish Brokerage Inc.');
            
            // Application reference
            $app_ref = $db_result['app_ref'] ?? ('APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
            
            $mail->Subject = "Job Application: {$position} - {$firstName} {$lastName} - {$app_ref}";
            $mail->isHTML(true);
            
            // Position label
            $positionLabels = [
                'broker' => 'Fish Broker',
                'coordinator' => 'Market Coordinator',
                'logistics' => 'Logistics Officer',
                'accounting' => 'Finance & Accounting',
                'quality' => 'Quality Control',
                'operations' => 'Port Operations'
            ];
            $positionLabel = $positionLabels[$position] ?? $position;
            if (!empty($_POST['position_other'])) {
                $positionLabel .= " (Other: " . htmlspecialchars($_POST['position_other']) . ")";
            }
            
            // Location label
            $locationLabels = [
                'navotas' => 'Navotas Fish Port Complex',
                'malabon' => 'Malabon Consignacion',
                'davao' => 'Davao Toril Fish Port'
            ];
            $locationLabel = $locationLabels[$apply_location] ?? $apply_location;
            
            // Experience label
            $expLabels = [
                'fresh' => 'Fresh Graduate / No experience',
                '1-2' => '1–2 years',
                '3-5' => '3–5 years',
                '5+' => '5+ years'
            ];
            $expLabel = $expLabels[$experience_years] ?? $experience_years;
            
            // Start date label
            $startLabels = [
                'immediately' => 'Immediately',
                '2-weeks' => 'In 2 weeks',
                '1-month' => 'In 1 month',
                'negotiable' => 'Negotiable'
            ];
            $startLabel = $startLabels[$start_date] ?? $start_date;
            
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background: linear-gradient(135deg, #1a6fa8 0%, #0d9488 100%); color: white; padding: 25px; border-radius: 10px; margin-bottom: 25px; text-align: center; }
                    .app-ref { background: #1a2634; color: white; padding: 12px 20px; border-radius: 8px; text-align: center; margin-bottom: 25px; font-size: 18px; font-weight: bold; }
                    .field { margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #0d9488; border-radius: 5px; }
                    .label { font-weight: bold; color: #555; display: block; margin-bottom: 5px; }
                    .value { color: #1a2634; }
                    .message-box { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2 style='margin:0;'>👔 New Job Application</h2>
                </div>
                
                <div class='app-ref'>
                    Application Ref: {$app_ref}
                </div>
                
                <div class='field'>
                    <div class='label'>👤 Full Name</div>
                    <div class='value'><strong>{$firstName} {$lastName}</strong></div>
                </div>
                
                <div class='field'>
                    <div class='label'>📧 Email</div>
                    <div class='value'><a href='mailto:{$email}' style='color: #0d9488;'>{$email}</a></div>
                </div>
                
                <div class='field'>
                    <div class='label'>📞 Contact Number</div>
                    <div class='value'>{$contact}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>🎂 Age</div>
                    <div class='value'>" . htmlspecialchars($_POST['age'] ?? 'Not specified') . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>📍 Address</div>
                    <div class='value'>{$address}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>🏢 Location Applying For</div>
                    <div class='value'>{$locationLabel}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>💼 Position</div>
                    <div class='value'><strong>{$positionLabel}</strong></div>
                </div>
                
                <div class='field'>
                    <div class='label'>⏱️ Years of Experience</div>
                    <div class='value'>{$expLabel}</div>
                </div>";
            
            if (!empty($_POST['industry_tags'])) {
                $mail->Body .= "
                <div class='field'>
                    <div class='label'>🏷️ Industry Background</div>
                    <div class='value'>" . htmlspecialchars($_POST['industry_tags']) . "</div>
                </div>";
            }
            
            $mail->Body .= "
                <div class='field'>
                    <div class='label'>📝 Work History</div>
                    <div class='message-box'>{$work_history}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>📅 Available to Start</div>
                    <div class='value'>{$startLabel}</div>
                </div>";
            
            if (!empty($_POST['work_type'])) {
                $mail->Body .= "
                <div class='field'>
                    <div class='label'>⚙️ Work Type</div>
                    <div class='value'>" . htmlspecialchars($_POST['work_type']) . "</div>
                </div>";
            }
            
            if (!empty($_POST['expected_salary'])) {
                $mail->Body .= "
                <div class='field'>
                    <div class='label'>💰 Expected Salary</div>
                    <div class='value'>" . htmlspecialchars($_POST['expected_salary']) . "</div>
                </div>";
            }
            
            if (!empty($_POST['extra_notes'])) {
                $mail->Body .= "
                <div class='field'>
                    <div class='label'>📌 Additional Notes</div>
                    <div class='message-box'>" . htmlspecialchars($_POST['extra_notes']) . "</div>
                </div>";
            }
            
            // Handle resume
            $resumeAttached = false;
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $resumeTmp = $_FILES['resume']['tmp_name'];
                $resumeName = $_FILES['resume']['name'];
                $safeResumeName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $resumeName);
                $mail->addAttachment($resumeTmp, "RESUME_" . $safeResumeName);
                $resumeAttached = true;
            }
            
            // Handle additional documents
            $docCount = 0;
            if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
                for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        $docTmp = $_FILES['documents']['tmp_name'][$i];
                        $docName = $_FILES['documents']['name'][$i];
                        $safeDocName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $docName);
                        $mail->addAttachment($docTmp, "DOC_" . ($i+1) . "_" . $safeDocName);
                        $docCount++;
                    }
                }
            }
            
            $mail->Body .= "
                <div class='field'>
                    <div class='label'>📎 Attachments</div>
                    <div class='value'>
                        " . ($resumeAttached ? "✅ Resume attached<br>" : "❌ No resume") . "
                        " . ($docCount > 0 ? "✅ {$docCount} supporting document(s) attached" : "ℹ️ No additional documents") . "
                    </div>
                </div>
                
                <div class='field'>
                    <div class='label'>🌐 Submission Details</div>
                    <div class='value'>
                        IP Address: {$_SERVER['REMOTE_ADDR']}<br>
                        Time: " . date('F j, Y \a\t g:i A') . "<br>
                        " . ($db_result['success'] ? "✅ Saved to database (ID: {$db_result['id']})" : "⚠️ Database save failed") . "
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This application was submitted from the careers page on fishbrokers.net</p>
                    <p>© " . date('Y') . " St. Joseph Fish Brokerage Inc. All rights reserved.</p>
                </div>
            </body>
            </html>";
            
            $mail->AltBody = "Job Application: {$positionLabel}\n\n" .
                            "Application Ref: {$app_ref}\n" .
                            "Name: {$firstName} {$lastName}\n" .
                            "Email: {$email}\n" .
                            "Contact: {$contact}\n" .
                            "Age: " . ($_POST['age'] ?? 'Not specified') . "\n" .
                            "Address: {$address}\n" .
                            "Location: {$locationLabel}\n" .
                            "Position: {$positionLabel}\n" .
                            "Experience: {$expLabel}\n" .
                            "Industry Tags: " . ($_POST['industry_tags'] ?? 'None') . "\n" .
                            "Work History: {$work_history}\n" .
                            "Start Date: {$startLabel}\n" .
                            "Work Type: " . ($_POST['work_type'] ?? 'Not specified') . "\n" .
                            "Expected Salary: " . ($_POST['expected_salary'] ?? 'Not specified') . "\n" .
                            "Extra Notes: " . ($_POST['extra_notes'] ?? 'None') . "\n\n" .
                            "Resume: " . ($resumeAttached ? "Attached" : "Not attached") . "\n" .
                            "Documents: " . ($docCount > 0 ? "{$docCount} attached" : "None") . "\n\n" .
                            "IP: {$_SERVER['REMOTE_ADDR']}\n" .
                            "Time: " . date('Y-m-d H:i:s');
            
            if ($mail->send()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Your application has been submitted successfully!',
                    'app_ref' => $app_ref
                ]);
            } else {
                throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            error_log("Career application email failed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit application. Please try again later.']);
        }
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid form type.']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>