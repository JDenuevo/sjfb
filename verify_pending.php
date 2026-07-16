<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['account_id'])) {
    header("Location: register.php");
    exit();
}

// If they're already verified, don't leave them stuck here.
$accountId = intval($_SESSION['account_id']);
$stmt = $conn->prepare("SELECT email_verified, profile_completed, account_email FROM accounts WHERE account_id = ? LIMIT 1");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if ($account && $account['email_verified']) {
    header("Location: " . ($account['profile_completed'] ? "accounts/home.php" : "accounts/complete_profile.php"));
    exit();
}

$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Your Email — St. Joseph Fish Brokerage Inc.</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="St. Joseph Fish Brokerage Inc. – Verify your Code">
<meta property="og:type" content="website">
<meta property="og:url" content="https://fishbrokers.net/verify_pending">
<meta property="og:title" content="Verify Email | St. Joseph Fish Brokerage Inc.">
<meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
<meta name="twitter:card" content="summary_large_image">
<meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow">

<link rel="shortcut icon" href="./assets/icons/logo.ico">
<link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
<link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
<link rel="apple-touch-icon" href="./assets/icons/logo.svg">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
<link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
<link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>window.CART_BASE = '';</script>
<script src="./functions/cart_process.js"></script>

<noscript>
<iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">

        <div class="mx-auto w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-[#E85D20]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="text-xl font-bold text-gray-900 mb-2">Check your inbox</h1>
        <p class="text-sm text-gray-500 mb-6">
            We sent a verification link to
            <span class="font-medium text-gray-700"><?= htmlspecialchars($account['account_email'] ?? '') ?></span>.
            Click the link to activate your account. This page will update automatically once you're verified.
        </p>

        <?php if ($successMsg): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2 mb-4">
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2 mb-4">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="functions/add.php" method="POST" class="mb-3">
            <button type="submit" name="resend_verification"
                    class="w-full bg-[#E85D20] text-white text-sm font-medium rounded-lg py-2.5 hover:bg-orange-600 transition">
                Resend verification email
            </button>
        </form>

        <a href="verify_pending.php" class="text-sm text-gray-500 hover:text-[#E85D20] underline">
            I've verified — refresh this page
        </a>

    </div>

    <!-- Auto-check every 8 seconds so they don't have to click refresh -->
    <script>
        setTimeout(() => window.location.reload(), 8000);
    </script>

</body>
</html>