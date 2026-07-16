<?php
// checkout.php
session_start();
include '../conn.php';
require_once 'functions/cleanup_orders.php';

$pageTitle = 'Checkout';

// Handle cancelled payment return
if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {
    unset($_SESSION['temp_checkout_ref']);
    unset($_SESSION['paymongo_session_id']);
    $cancelMessage = "Payment was cancelled. You can try again with a different payment method.";
}

// Handle payment errors
if (isset($_GET['error'])) {
    $errorMessages = [
        'invalid_session'           => 'Invalid payment session. Please try again.',
        'no_data'                   => 'Checkout data not found. Please try again.',
        'payment_verification_failed' => 'Payment verification failed. Please try again.',
        'order_creation_failed'     => 'Payment successful but order creation failed. Please contact support.',
        'payment_failed'            => 'Payment failed. Please try again.',
    ];
    $errorCode    = $_GET['error'];
    $errorMessage = $errorMessages[$errorCode] ?? 'An error occurred. Please try again.';
}

// Check if there's pending checkout data (user came back after cancellation)
$savedData = [];
if (!empty($_SESSION['pending_checkout'])) {
    $savedData = $_SESSION['pending_checkout'];
}

cleanupAbandonedCheckouts();

if (isset($_SESSION['pending_checkout']['city'])) {
    $_SESSION['last_checkout_city'] = $_SESSION['pending_checkout']['city'];
}

// ── Pre-fill from logged-in account (uses renamed columns) ─────────────────
$userDetails = [];
if (isset($_SESSION['account_id'])) {
    $uid  = $_SESSION['account_id'];
    $stmt = $conn->prepare("
        SELECT account_first_name, account_last_name, account_email,
               account_phone, account_address, city, postal_code
        FROM accounts WHERE account_id = ?
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        // Normalise to short keys that getFormValue() expects
        $userDetails = [
            'first_name'   => $row['account_first_name'] ?? '',
            'last_name'    => $row['account_last_name']  ?? '',
            'email'        => $row['account_email']      ?? '',
            'phone_number' => $row['account_phone']      ?? '',
            'address'      => $row['account_address']    ?? '',
            'city'         => $row['city']               ?? '',
            'postal_code'  => $row['postal_code']        ?? '',
        ];
    }
}

// ── If we have saved data from a cancelled payment, use it to OVERRIDE ──────
// pending_checkout uses the short keys saved by add.php
if (!empty($savedData)) {
    $userDetails = [
        'first_name'    => $savedData['first_name']    ?? $userDetails['first_name']    ?? '',
        'last_name'     => $savedData['last_name']     ?? $userDetails['last_name']     ?? '',
        'email'         => $savedData['email']         ?? $userDetails['email']         ?? '',
        'phone_number'  => $savedData['phone_number']  ?? $userDetails['phone_number']  ?? '',
        'address'       => $savedData['address']       ?? $userDetails['address']       ?? '',
        'city'          => $savedData['city']          ?? $userDetails['city']          ?? '',
        'postal_code'   => $savedData['postal_code']   ?? $userDetails['postal_code']   ?? '',
        'delivery_notes'=> $savedData['delivery_notes'] ?? '',
        'payment_method'=> $savedData['payment_method'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | St. Joseph Fish Brokerage Inc.</title>
  <meta name="base-url" content="/sjfbi-js">

  <meta property="og:type"        content="website">
  <meta property="og:url"         content="https://fishbrokers.net/">
  <meta property="og:title"       content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image"       content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow">
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image"       content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon"             href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon"  href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon"          href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!--
    IMPORTANT: cart_process.js must load BEFORE any component that renders cart items.
    It defines window.updateDeliveryFee which to_checkout.php checks via typeof.
  -->
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  <style>
    body { font-family: 'Lexend', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">

<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>

<!-- Flash messages -->
<?php if (!empty($_SESSION['success']) || !empty($_SESSION['error'])): ?>
<?php
  $msg  = !empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error'];
  $type = !empty($_SESSION['success']) ? 'success' : 'error';
  $cls  = $type === 'success'
        ? 'bg-green-50 border-green-300 text-green-800'
        : 'bg-red-50 border-red-300 text-red-800';
  unset($_SESSION['success'], $_SESSION['error']);
?>
<div class="mx-auto max-w-6xl px-4 pt-4">
  <div class="flex items-center gap-3 <?= $cls ?> border rounded-xl px-4 py-3 text-sm font-medium">
    <?php if ($type === 'success'): ?>
      <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <?php else: ?>
      <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($msg) ?>
  </div>
</div>
<?php endif; ?>

<?php if (isset($cancelMessage)): ?>
<div class="mx-auto max-w-6xl px-4 pt-4">
  <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm font-medium text-amber-800">
    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    <?= htmlspecialchars($cancelMessage) ?>
  </div>
</div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
<div class="mx-auto max-w-6xl px-4 pt-4">
  <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm font-medium text-red-800">
    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($errorMessage) ?>
  </div>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="bg-white border-b border-gray-100 py-6 px-4">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Order Checkout</h1>
      <p class="text-sm text-gray-500 mt-0.5">Complete your order below.</p>
    </div>
    <div class="hidden sm:flex items-center gap-2 text-xs font-semibold">
      <span class="flex items-center gap-1.5 text-orange-500">
        <span class="size-5 rounded-full bg-orange-600 text-white flex items-center justify-center text-[10px]">1</span>Details
      </span>
      <span class="text-gray-300">——</span>
      <span class="flex items-center gap-1.5 text-gray-400">
        <span class="size-5 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-[10px]">2</span>Payment
      </span>
      <span class="text-gray-300">——</span>
      <span class="flex items-center gap-1.5 text-gray-400">
        <span class="size-5 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-[10px]">3</span>Order Review
      </span>
    </div>
  </div>
</div>

<section class="py-8 px-4">
  <?php include('./components/to_checkout.php'); ?>
</section>

<?php $conn->close(); ?>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>AOS.init();</script>
<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

<?php include('live_chat.php'); ?>
</body>
</html>