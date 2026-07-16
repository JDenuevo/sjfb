<?php
session_start();
require_once 'conn.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $_SESSION['error'] = "Invalid verification link.";
    header("Location: register.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT account_id, username, email_verified, profile_completed, verification_expiry
    FROM accounts
    WHERE verification_token = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    $_SESSION['error'] = "This verification link is invalid or has already been used.";
    header("Location: register.php");
    exit();
}

if ($account['email_verified']) {
    $_SESSION['success'] = "Your email is already verified. You can log in.";
    header("Location: register.php");
    exit();
}

$expired = strtotime($account['verification_expiry']) < time();

if ($expired) {
    $_SESSION['error'] = "This verification link has expired. Please request a new one from your account page.";
    header("Location: register.php");
    exit();
}

// Token valid and not expired — activate the account
$stmt = $conn->prepare("
    UPDATE accounts
    SET email_verified = 1,
        verification_token = NULL,
        verification_expiry = NULL
    WHERE account_id = ?
");
$stmt->bind_param("i", $account['account_id']);
$stmt->execute();
$stmt->close();

// If they're logged in as this account in the current browser, refresh the session flag
if (isset($_SESSION['account_id']) && intval($_SESSION['account_id']) === intval($account['account_id'])) {
    $_SESSION['email_verified'] = true;
}

if (function_exists('logActivity')) {
    require_once 'functions/activity_log_helper.php';
    logActivity($conn, 'account', $account['account_id'], 'Email verified', null, null,
        "Account email verified: {$account['username']}",
        $account['account_id'], 'customer');
}

$conn->close();

$_SESSION['success'] = "Your email has been verified!";

if (!isset($_SESSION['account_id'])) {
    // Verified from a different browser/device than the one they registered in
    header("Location: register.php");
    exit();
}

header("Location: " . ($account['profile_completed'] ? "accounts/home.php" : "accounts/complete_profile.php"));
exit();