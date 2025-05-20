<?php
// functions/update.php
session_start();
include '../conn.php';
require_once __DIR__ . '/mail_functions.php';

date_default_timezone_set('Asia/Manila');

function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

function sendOTP($email, $conn) {
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $conn->prepare("UPDATE accounts SET reset_otp = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
    $stmt->execute();
    
    $subject = "Password Reset OTP";
    $message = "Your OTP: $otp (Valid for 15 minutes)";
    return sendEmail($email, $subject, $message);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['send_otp'])) {
        
        $email = trim($_POST['email']);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format!";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Don't reveal if email exists for security
                $_SESSION['success'] = "If your email exists in our system, you'll receive a password reset OTP.";
            } else {
                // Generate 6-digit OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store OTP in database
                $update_stmt = $conn->prepare("UPDATE accounts SET reset_otp = ?, otp_expiry = ? WHERE email = ?");
                $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);
                $update_stmt->execute();
                
                // Send OTP via email
                $subject = "Your Password Reset OTP";
                $message = "Your OTP for password reset is: $otp\n\nThis OTP is valid for 15 minutes.\n\nIf you didn't request this, please ignore this email.";
                
                if (sendEmail($email, $subject, $message)) {
                    $_SESSION['email'] = $email;
                    $_SESSION['otp_sent'] = true;
                    header("Location: ../verify_otp.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to send OTP. Please try again later.";
                }
            }
        }
    }

    // Verify OTP
    elseif (isset($_POST['submit_otp'])) {
        if (!isset($_SESSION['email'])) {
            redirectWithMessage('../forgot_password.php', "Session expired");
        }

        // Combine OTP digits
        $otp = '';
        for ($i = 1; $i <= 6; $i++) {
            $otp .= $_POST["otp$i"] ?? '';
        }

        // Validate OTP format
        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            redirectWithMessage('../verify_otp.php', "Please enter a valid 6-digit OTP");
        }

        $email = $_SESSION['email'];
        
        // Check OTP against database
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ? AND reset_otp = ? AND otp_expiry > NOW()");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 1) {
            $_SESSION['otp_verified'] = true;
            // Clear the OTP after successful verification
            $conn->query("UPDATE accounts SET reset_otp = NULL, otp_expiry = NULL WHERE email = '$email'");
            redirectWithMessage('../reset_password.php', "OTP verified!", 'success');
        } else {
            redirectWithMessage('../verify_otp.php', "Invalid or expired OTP");
        }
    }

    // Update Account Information
    elseif (isset($_POST['update_new_account'])) {
        $account_id = $_SESSION['account_id'];
        $phone = trim($_POST['phone_number']);
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $address = trim($_POST['address']);
        $postal = trim($_POST['postal_code']);
        $city = trim($_POST['city']);
      
        $stmt = $conn->prepare("UPDATE accounts SET phone_number = ?, first_name = ?, last_name = ?, address = ?, postal_code = ?, city = ? WHERE account_id = ?");
        $stmt->bind_param("ssssssi", $phone, $fname, $lname, $address, $postal, $city, $account_id);
      
        if ($stmt->execute()) {
            redirectWithMessage('../index.php', "Details updated!", 'success');
        } else {
            redirectWithMessage('../index.php', "Update failed");
        }
    }
}
?>
