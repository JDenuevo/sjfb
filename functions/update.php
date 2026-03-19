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

/**
 * Generate + store a 6-digit OTP, then email it.
 * Returns true on success, false on mail failure.
 * Uses renamed column: account_email (was email)
 */
function sendOTP(string $email, mysqli $conn): bool {
    $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $conn->prepare("UPDATE accounts SET reset_otp = ?, otp_expiry = ? WHERE account_email = ?");
    if (!$stmt) return false;

    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
    if (!$stmt->execute()) return false;
    $stmt->close();

    $subject = "Your Password Reset OTP – St. Joseph Fish Brokerage";
    $message = "Hello,\n\nYour OTP for password reset is:\n\n  $otp\n\nThis code is valid for 15 minutes.\n\nIf you did not request this, please ignore this email — your account is safe.\n\nSt. Joseph Fish Brokerage Inc.";

    return sendEmail($email, $subject, $message);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php");
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SEND OTP
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['send_otp'])) {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('../forgot_password.php', "Please enter a valid email address.");
    }

    // Uses renamed column: account_email (was email)
    $stmt = $conn->prepare("SELECT account_id, is_deleted FROM accounts WHERE account_email = ?");
    if (!$stmt) {
        redirectWithMessage('../forgot_password.php', "A system error occurred. Please try again.");
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        $_SESSION['success'] = "If your email is registered with us, you'll receive a reset code shortly.";
        header("Location: ../forgot_password.php");
        exit();
    }

    $account = $result->fetch_assoc();

    if (!empty($account['is_deleted'])) {
        $_SESSION['success'] = "If your email is registered with us, you'll receive a reset code shortly.";
        header("Location: ../forgot_password.php");
        exit();
    }

    if (sendOTP($email, $conn)) {
        $_SESSION['email']           = $email;
        $_SESSION['otp_sent']        = true;
        $_SESSION['last_otp_resend'] = time();
        header("Location: ../verify_otp.php");
        exit();
    } else {
        redirectWithMessage('../forgot_password.php', "Failed to send OTP. Please try again later.");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. VERIFY OTP
// ─────────────────────────────────────────────────────────────────────────────
elseif (isset($_POST['submit_otp'])) {

    if (!isset($_SESSION['email'])) {
        redirectWithMessage('../forgot_password.php', "Session expired. Please start over.");
    }

    $otp = '';
    if (!empty($_POST['full_otp']) && ctype_digit($_POST['full_otp']) && strlen($_POST['full_otp']) === 6) {
        $otp = $_POST['full_otp'];
    } else {
        for ($i = 1; $i <= 6; $i++) {
            $otp .= preg_replace('/\D/', '', $_POST["otp$i"] ?? '');
        }
    }

    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        redirectWithMessage('../verify_otp.php', "Please enter a complete 6-digit OTP code.");
    }

    $email = $_SESSION['email'];

    // Uses renamed column: account_email (was email)
    $stmt = $conn->prepare("
        SELECT account_id
        FROM accounts
        WHERE account_email = ?
          AND reset_otp = ?
          AND otp_expiry > NOW()
        LIMIT 1
    ");
    if (!$stmt) {
        redirectWithMessage('../verify_otp.php', "A system error occurred. Please try again.");
    }
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows !== 1) {
        redirectWithMessage('../verify_otp.php', "Invalid or expired OTP. Please try again or request a new code.");
    }

    // Uses renamed column: account_email (was email)
    $clear = $conn->prepare("UPDATE accounts SET reset_otp = NULL, otp_expiry = NULL WHERE account_email = ?");
    $clear->bind_param("s", $email);
    $clear->execute();
    $clear->close();

    $_SESSION['otp_verified'] = true;
    redirectWithMessage('../reset_password.php', "OTP verified! Please set your new password.", 'success');
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. UPDATE ACCOUNT DETAILS
// ─────────────────────────────────────────────────────────────────────────────
elseif (isset($_POST['update_new_account'])) {

    if (!isset($_SESSION['account_id'])) {
        redirectWithMessage('../index.php', "You must be logged in to update your details.");
    }

    $account_id = (int) $_SESSION['account_id'];
    $phone      = trim($_POST['phone_number']   ?? '');
    $fname      = trim($_POST['first_name']     ?? '');
    $lname      = trim($_POST['last_name']      ?? '');
    $address    = trim($_POST['address']        ?? '');
    $postal     = trim($_POST['postal_code']    ?? '');
    $city       = trim($_POST['city']           ?? '');

    if (empty($fname) || empty($lname)) {
        redirectWithMessage('../details.php', "First name and last name are required.");
    }
    if (strlen($fname) < 2 || strlen($lname) < 2) {
        redirectWithMessage('../details.php', "Names must be at least 2 characters.");
    }
    if (!preg_match('/^[0-9+\-\s()]{10,}$/', $phone)) {
        redirectWithMessage('../details.php', "Please enter a valid phone number.");
    }
    if (!preg_match('/^[0-9]{4,6}$/', $postal)) {
        redirectWithMessage('../details.php', "Please enter a valid 4–6 digit postal code.");
    }

    // Uses renamed columns: account_phone, account_first_name, account_last_name, account_address
    $stmt = $conn->prepare("
        UPDATE accounts
        SET account_phone        = ?,
            account_first_name   = ?,
            account_last_name    = ?,
            account_address      = ?,
            postal_code          = ?,
            city                 = ?
        WHERE account_id = ?
    ");
    if (!$stmt) {
        redirectWithMessage('../details.php', "A system error occurred. Please try again.");
    }
    $stmt->bind_param("ssssssi", $phone, $fname, $lname, $address, $postal, $city, $account_id);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage('../account/shop.php', "Details saved successfully!", 'success');
    } else {
        $stmt->close();
        redirectWithMessage('../details.php', "Update failed. Please try again.");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Fallback
// ─────────────────────────────────────────────────────────────────────────────
else {
    header("Location: ../index.php");
    exit();
}
?>