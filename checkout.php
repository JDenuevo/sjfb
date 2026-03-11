<!-- checkout.php -->
<?php
session_start();
include 'conn.php';

$pageTitle = 'Checkout';

// Pre-fill form if user is logged in
$userDetails = [];
if (isset($_SESSION['account_id'])) {
    $uid  = $_SESSION['account_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, city, postal_code FROM accounts WHERE account_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $userDetails = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

// NOTE: $conn intentionally NOT closed here — to_checkout.php needs it to query stock quantities.
// It will be closed after to_checkout.php finishes rendering.
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | St. Joseph Fish Brokerage Inc.</title>

  <!-- !! IMPORTANT: cart_core.js uses this to build fetch URLs !! -->
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

  <link rel="shortcut icon"            href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon"         href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!--
    cart_process.js — single source of truth for all cart JS.
    Must load BEFORE any component that renders cart items.
  -->
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

<!-- Page header with step indicator -->
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

<!-- Main checkout body -->
<section class="py-8 px-4">
  <?php include('./components/to_checkout.php'); ?>
</section>

<?php $conn->close(); // Close connection after to_checkout.php has finished with it ?>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>AOS.init();</script>
<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

<?php include('live_chat.php'); ?>
</body>
</html>