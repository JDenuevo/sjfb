<?php
session_start();
include 'conn.php';
require_once __DIR__ . '/functions/mail_functions.php';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | St. Joseph Fish Brokerage Inc.</title>
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
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
  
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>
<body class="min-h-screen flex flex-col m-0">
  <?php include('./components/preloaders.php'); ?>
  <?php include('./components/navigation.php'); ?>

  <main class="flex-1 flex items-center justify-center px-4 py-10">

    <div class="fp-card grid grid-cols-1 sm:grid-cols-[1fr_1.35fr] w-full max-w-3xl rounded-[1.375rem] overflow-hidden shadow-[0_2px_4px_rgba(0,0,0,.04),0_8px_24px_rgba(0,0,0,.08),0_28px_56px_rgba(0,0,0,.07)]">

      <!-- ── Brand panel (hidden on mobile) ── -->
      <div class="fp-dots-bg hidden sm:flex relative flex-col justify-center overflow-hidden min-h-[440px] p-11
        bg-gradient-to-br from-orange-700 via-orange-600 to-amber-400">
        <div class="relative z-10">
          <img src="./assets/icons/logo.svg" alt="SJFBI" class="h-12 mb-7">

          <h2 class="text-2xl font-extrabold text-white leading-tight mb-2.5">Password Recovery</h2>
          <p class="text-sm text-white/80 leading-relaxed mb-7">
            We'll guide you through three quick steps to securely regain access to your account.
          </p>

          <div class="flex flex-col">
            <!-- Step 1 (active) -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white text-orange-600">1</div>
              <span class="text-sm font-semibold text-white">Enter your email</span>
            </div>
            <div class="w-0.5 h-[1.125rem] bg-white/20 ml-3.5"></div>
            <!-- Step 2 -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white/15 text-white/50">2</div>
              <span class="text-sm font-semibold text-white/45">Verify OTP code</span>
            </div>
            <div class="w-0.5 h-[1.125rem] bg-white/20 ml-3.5"></div>
            <!-- Step 3 -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white/15 text-white/50">3</div>
              <span class="text-sm font-semibold text-white/45">Set new password</span>
            </div>
          </div>
        </div>

        <svg class="absolute right-0 top-0 h-full w-13" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form panel ── -->
      <div class="flex flex-col bg-white">
        <div class="p-9 flex-1">

          <div class="inline-flex items-center gap-2 text-[0.7rem] font-bold tracking-[0.12em] uppercase text-orange-600 bg-orange-50 border border-orange-600/20 rounded-full px-3.5 py-1.5 mb-4">
            <span class="fp-dot size-2 rounded-full bg-orange-600"></span> Step 1 of 3
          </div>

          <div class="mb-6">
            <h1 class="text-[1.375rem] font-extrabold text-gray-900 leading-tight mb-1">Forgot Password?</h1>
            <p class="text-sm text-gray-400 leading-relaxed">Enter your registered email and we'll send you an OTP code to reset your password.</p>
          </div>

          <?php if (!empty($_SESSION['error'])): ?>
          <div class="flex items-start gap-2.5 rounded-lg px-4 py-3 text-sm font-medium mb-5 leading-relaxed bg-red-50 border border-red-200 text-red-700">
            <svg class="shrink-0 mt-0.5 size-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['success'])): ?>
          <div class="flex items-start gap-2.5 rounded-lg px-4 py-3 text-sm font-medium mb-5 leading-relaxed bg-green-50 border border-green-200 text-green-800">
            <svg class="shrink-0 mt-0.5 size-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          </div>
          <?php endif; ?>

          <div class="flex gap-3 bg-orange-50 border border-orange-600/15 rounded-xl px-4.5 py-3.5 mb-5.5">
            <svg class="shrink-0 mt-0.5 size-[15px] text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <p class="text-sm text-amber-800 leading-relaxed">
              Use the email address linked to your SJFBI account. The OTP will expire after <strong>10 minutes</strong>.
            </p>
          </div>

          <form method="POST" action="functions/update.php" class="flex flex-col gap-4">
            <input type="hidden" name="forgot_password" value="1">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg class="size-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <input type="email" name="email" placeholder="Enter your registered email"
                  required autofocus autocomplete="email"
                  class="py-2.5 pl-10 pr-3.5 block w-full border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none transition">
              </div>
            </div>
            <button type="submit" name="send_otp"
              class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl py-3.5 shadow-lg shadow-orange-600/30 hover:shadow-orange-600/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Send OTP Code
            </button>
          </form>

          <div class="text-center text-sm text-gray-400 mt-5 pt-4.5 border-t border-gray-100">
            Remember your password?
            <a href="index.php" class="font-semibold text-orange-600 hover:text-orange-700 hover:underline transition">Sign in here</a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('live_chat.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>