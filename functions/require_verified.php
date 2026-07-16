<?php
/**
 * functions/require_verified.php
 *
 * Include this at the top of any page/action that should be blocked
 * until the account's email is verified — e.g. checkout, place_order
 * handler in functions/add.php, or wherever ordering happens.
 *
 * Usage from a root-level file (e.g. checkout.php):
 *   require_once 'functions/require_verified.php';
 *   requireVerifiedAccount($conn);
 *
 * Usage from a subfolder file (e.g. accounts/checkout.php):
 *   require_once '../functions/require_verified.php';
 *   requireVerifiedAccount($conn);
 *
 * Assumes session_start() and $conn (mysqli) already exist in the including file.
 */

function requireVerifiedAccount(mysqli $conn): void {
    if (!isset($_SESSION['account_id'])) {
        $_SESSION['error'] = "Please log in to continue.";
        header("Location: /register.php");
        exit();
    }

    // Trust the session flag if we already refreshed it (fast path)
    if (isset($_SESSION['email_verified']) && $_SESSION['email_verified'] === true) {
        return;
    }

    // Otherwise check the DB (covers the case where they verified in another tab/device)
    $accountId = intval($_SESSION['account_id']);
    $stmt = $conn->prepare("SELECT email_verified FROM accounts WHERE account_id = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !$row['email_verified']) {
        $_SESSION['error'] = "Please verify your email address before continuing. Check your inbox, or resend the verification email from your account page.";
        header("Location: /accounts/home.php");
        exit();
    }

    // Cache the result so we don't hit the DB on every request
    $_SESSION['email_verified'] = true;
}

/**
 * Call this in addition to requireVerifiedAccount() on pages that need
 * a fully completed profile (name, address, etc.) — e.g. checkout.
 * Redirects to accounts/complete_profile.php if it hasn't been filled in yet.
 */
function requireCompleteProfile(mysqli $conn): void {
    if (isset($_SESSION['profile_completed']) && $_SESSION['profile_completed'] === true) {
        return;
    }

    $accountId = intval($_SESSION['account_id']);
    $stmt = $conn->prepare("SELECT profile_completed FROM accounts WHERE account_id = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !$row['profile_completed']) {
        header("Location: /account/complete_profile.php");
        exit();
    }

    $_SESSION['profile_completed'] = true;
}