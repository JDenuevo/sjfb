<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendBuyerProducerNotification($data) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration for GoDaddy Hosting
        $mail->isSMTP();
        $mail->Host = 'smtp.secureserver.net'; // GoDaddy SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'marketing@fishbrokers.net'; // Your professional email
        $mail->Password = ''; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Alternative: If using Gmail with GoDaddy hosting
        /*
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password'; // Use App Password, not regular password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        */
        
        $mail->SMTPDebug = 0;
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';
        
        // Security headers
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion());
        $mail->addCustomHeader('X-Priority', '3');
        
        // Sender configuration
        $mail->setFrom('contact@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
        $mail->addReplyTo($data['email'], $data['fullName']);
        
        // Recipients - Internal team
        $mail->addAddress('info@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress('contact@fishbrokers.net', 'Contact Department');
        $mail->addAddress('procurement@fishbrokers.net', 'Procurement Team');
        
        // CC for management (optional)
        // $mail->addCC('management@fishbrokers.net', 'Management Team');
        
        // Subject
        $type = $data['type'];
        $mail->Subject = "🎣 New $type Inquiry - {$data['location']} - {$data['inquiryCode']}";
        $mail->Encoding = 'base64';
        
        // HTML Email Body
        $mail->isHTML(true);
        $mail->Body = generateEmailHTML($data);
        
        // Plain text alternative
        $mail->AltBody = generateEmailPlainText($data);
        
        $mail->send();
        error_log("Buyer/Producer email sent successfully. Code: {$data['inquiryCode']}");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send Buyer/Producer email: " . $e->getMessage());
        
        // Try fallback SMTP if primary fails
        try {
            return sendEmailFallback($data);
        } catch (Exception $e2) {
            error_log("Fallback email also failed: " . $e2->getMessage());
            return false;
        }
    }
}

// Fallback email method
function sendEmailFallback($data) {
    $mail = new PHPMailer(true);
    
    // Using PHP mail() as fallback
    $mail->isMail();
    $mail->setFrom('noreply@fishbrokers.net', 'St. Joseph Fish Brokerage Inc.');
    $mail->addAddress('info@fishbrokers.net');
    $mail->addReplyTo($data['email'], $data['fullName']);
    
    $type = $data['type'];
    $mail->Subject = "New $type Inquiry - {$data['location']} - {$data['inquiryCode']}";
    $mail->isHTML(true);
    $mail->Body = generateEmailHTML($data);
    $mail->AltBody = generateEmailPlainText($data);
    
    $mail->send();
    return true;
}

// Generate HTML Email
function generateEmailHTML($data) {
    $type = $data['type'];
    $typeLower = strtolower($type);
    $badgeColor = ($type == 'Buyer') ? '#27ae60' : '#e67e22';
    $icon = ($type == 'Buyer') ? '🛒' : '🎣';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <style>
            @media only screen and (max-width: 600px) {
                .container { width: 100% !important; padding: 15px !important; }
                .header { padding: 20px !important; }
                .header h2 { font-size: 24px !important; }
                .field { margin: 10px 0 !important; padding: 10px !important; }
            }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                background: #f6f9fc;
                margin: 0; 
                padding: 20px; 
                line-height: 1.6;
            }
            .container { 
                max-width: 700px; 
                background: white; 
                padding: 30px; 
                border-radius: 20px; 
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
                margin: 20px auto;
                border: 1px solid #eef2f6;
            }
            .header { 
                background: linear-gradient(135deg, #F2571B 0%, #FF6B35 100%);
                color: white; 
                padding: 30px; 
                border-radius: 16px; 
                text-align: center;
                margin-bottom: 30px;
            }
            .header h2 {
                margin: 0;
                font-size: 28px;
                font-weight: 700;
                letter-spacing: -0.5px;
            }
            .badge {
                display: inline-block;
                background: rgba(255,255,255,0.15);
                padding: 8px 24px;
                border-radius: 100px;
                margin-top: 12px;
                font-size: 14px;
                font-weight: 500;
                backdrop-filter: blur(4px);
            }
            .company-info {
                text-align: center;
                margin-bottom: 25px;
                color: #5a6a7a;
                font-size: 15px;
            }
            .inquiry-card {
                background: #f8fafd;
                border-radius: 16px;
                padding: 25px;
                margin-bottom: 25px;
                border: 1px solid #e9ecef;
            }
            .inquiry-code {
                background: #1a2634;
                color: white;
                padding: 15px 25px;
                border-radius: 12px;
                text-align: center;
                margin-bottom: 25px;
                font-size: 20px;
                letter-spacing: 2px;
                font-weight: 600;
            }
            .field { 
                margin: 15px 0; 
                padding: 15px 20px; 
                background: white; 
                border-radius: 12px; 
                border-left: 4px solid #F2571B;
                box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            }
            .label { 
                font-weight: 600; 
                color: #2c3e50;
                display: block;
                margin-bottom: 6px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .value {
                color: #1a2634;
                font-size: 16px;
                font-weight: 500;
            }
            .type-badge {
                display: inline-block;
                padding: 8px 20px;
                background: {$badgeColor};
                color: white;
                border-radius: 100px;
                font-size: 14px;
                font-weight: 600;
            }
            .message-box {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 12px;
                padding: 20px;
                margin-top: 10px;
                line-height: 1.7;
                color: #2c3e50;
            }
            .next-steps {
                background: #fff9e6;
                border-radius: 16px;
                padding: 25px;
                margin-top: 30px;
                border: 1px solid #ffe4b5;
            }
            .next-steps h4 {
                margin: 0 0 15px 0;
                color: #8a6d2b;
                font-size: 16px;
                font-weight: 600;
            }
            .next-steps ul {
                margin: 0;
                padding-left: 20px;
            }
            .next-steps li {
                margin-bottom: 10px;
                color: #5a4a2a;
            }
            .footer {
                margin-top: 40px;
                padding-top: 30px;
                border-top: 1px solid #e9ecef;
                text-align: center;
                color: #8a9aa8;
                font-size: 13px;
            }
            .logo {
                text-align: center;
                margin-bottom: 20px;
            }
            .logo-text {
                font-size: 26px;
                font-weight: 700;
                color: #F2571B;
                margin-bottom: 5px;
                letter-spacing: -0.5px;
            }
            .meta-info {
                background: #f8fafd;
                padding: 15px 20px;
                border-radius: 12px;
                font-size: 13px;
                color: #5a6a7a;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='logo'>
                <div class='logo-text'>St. Joseph Fish Brokerage Inc.</div>
                <div style='color: #5a6a7a; font-size: 14px;'>Fresh Seafood • Trusted Brokerage • Nationwide</div>
            </div>
            
            <div class='header'>
                <h2>{$icon} New {$type} Inquiry</h2>
                <div class='badge'>Matching & Sourcing Request</div>
            </div>
            
            <div class='company-info'>
                A new {$typeLower} has submitted a sourcing/partnership inquiry
            </div>
            
            <div class='inquiry-code'>
                🔔 Inquiry Code: {$data['inquiryCode']}
            </div>

            <div class='inquiry-card'>
                <div class='field'>
                    <span class='label'>👤 Contact Person</span>
                    <span class='value'>{$data['fullName']}</span>
                </div>
                
                <div class='field'>
                    <span class='label'>🎯 Type</span>
                    <span class='type-badge'>{$type}</span>
                </div>
                
                <div class='field'>
                    <span class='label'>📧 Email Address</span>
                    <span class='value'><a href='mailto:{$data['email']}' style='color: #F2571B; text-decoration: none;'>{$data['email']}</a></span>
                </div>
                
                <div class='field'>
                    <span class='label'>📞 Contact Number</span>
                    <span class='value'><a href='tel:{$data['contactNumber']}' style='color: #1a2634; text-decoration: none;'>{$data['contactNumber']}</a></span>
                </div>
                
                <div class='field'>
                    <span class='label'>📍 Location/Origin</span>
                    <span class='value'>{$data['location']}</span>
                </div>
                
                <div class='field'>
                    <span class='label'>💬 Requirements / Details</span>
                    <div class='message-box'>{$data['details']}</div>
                </div>
            </div>
            
            <div class='next-steps'>
                <h4>📋 Action Required - Next Steps:</h4>
                <ul>
                    <li>✓ Review inquiry details and requirements</li>
                    <li>✓ Check product availability / sourcing capabilities</li>
                    <li>✓ Contact the {$typeLower} within 24-48 hours</li>
                    <li>✓ Update inquiry status in admin dashboard</li>
                    <li>✓ Log all communications for documentation</li>
                </ul>
            </div>
            
            <div class='meta-info'>
                <strong>🌐 Submission Details:</strong><br>
                IP Address: {$data['ip']}<br>
                Submission Time: " . date('F j, Y \a\t g:i A') . "<br>
                User Agent: {$data['userAgent']}<br>
                Source: Website - Buyer/Producer Form
            </div>

            <div class='footer'>
                <p style='margin: 0;'>This is an automated notification from fishbrokers.net</p>
                <p style='margin: 10px 0 0 0;'>© " . date('Y') . " St. Joseph Fish Brokerage Inc. All rights reserved.</p>
                <p style='margin: 10px 0 0 0; font-size: 11px;'>2nd Floor, Teoxon Building, Brgy. 7, Bayawan City, Negros Oriental</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Generate Plain Text Email
function generateEmailPlainText($data) {
    $type = $data['type'];
    $typeLower = strtolower($type);
    
    return str_repeat("=", 60) . "\n" .
        "NEW " . strtoupper($type) . " INQUIRY\n" .
        str_repeat("=", 60) . "\n\n" .
        "Inquiry Code: {$data['inquiryCode']}\n" .
        "Date: " . date('F j, Y') . "\n\n" .
        "CONTACT INFORMATION:\n" .
        str_repeat("-", 30) . "\n" .
        "Name: {$data['fullName']}\n" .
        "Type: {$type}\n" .
        "Email: {$data['email']}\n" .
        "Contact: {$data['contactNumber']}\n" .
        "Location: {$data['location']}\n\n" .
        "REQUIREMENTS/DETAILS:\n" .
        str_repeat("-", 30) . "\n" .
        "{$data['details']}\n\n" .
        "ACTION REQUIRED:\n" .
        str_repeat("-", 30) . "\n" .
        "1. Review inquiry details\n" .
        "2. Check product availability/sourcing\n" .
        "3. Contact {$typeLower} within 24-48 hours\n" .
        "4. Update status in admin dashboard\n\n" .
        "SUBMISSION DETAILS:\n" .
        str_repeat("-", 30) . "\n" .
        "IP Address: {$data['ip']}\n" .
        "Time: " . date('F j, Y \a\t g:i A') . "\n" .
        "User Agent: {$data['userAgent']}\n" .
        "Source: Website - Buyer/Producer Form\n\n" .
        str_repeat("=", 60) . "\n" .
        "St. Joseph Fish Brokerage Inc.\n" .
        "This is an automated notification.\n" .
        str_repeat("=", 60);
}
?>