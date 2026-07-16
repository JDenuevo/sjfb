<?php
session_start();
include 'conn.php';
require_once __DIR__ . '/functions/mail_functions.php';

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['email'])) {
    header("Location: forgot_password.php");
    exit();
}

if (isset($_GET['resend']) && isset($_SESSION['email'])) {
    if (!isset($_SESSION['last_otp_resend']) || time() - $_SESSION['last_otp_resend'] > 60) {
        require_once __DIR__ . '/functions/update.php';
        $email = $_SESSION['email'];
        if (sendOTP($email, $conn)) {
            $_SESSION['last_otp_resend'] = time();
            $_SESSION['message'] = ['type'=>'success','text'=>'New OTP has been sent to your email.'];
        } else {
            $_SESSION['message'] = ['type'=>'error','text'=>'Failed to send new OTP. Please try again.'];
        }
    } else {
        $remaining = 60 - (time() - $_SESSION['last_otp_resend']);
        $_SESSION['message'] = ['type'=>'error','text'=>"Please wait $remaining seconds before requesting a new OTP."];
    }
    header("Location: verify_otp.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP | St. Joseph Fish Brokerage Inc.</title>
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
  
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  
  <style>
    /* Only what Tailwind's core utilities genuinely can't express */
    .otp-box.shake { animation: shake .3s ease; }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      75% { transform: translateX(4px); }
    }
    .otp-box::-webkit-inner-spin-button,
    .otp-box::-webkit-outer-spin-button { -webkit-appearance: none; }
  </style>
</head>
<body class="font-lexend m-0 min-h-screen flex flex-col">
  <?php include('./components/preloaders.php'); ?>
  <?php include('./components/navigation.php'); ?>

  <main class="flex-1 flex items-center justify-center px-4 py-10
    bg-[radial-gradient(ellipse_at_80%_15%,rgba(251,146,60,.08)_0%,transparent_55%),radial-gradient(ellipse_at_10%_85%,rgba(234,88,12,.05)_0%,transparent_55%)]">

    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1.35fr] w-full max-w-3xl rounded-[1.375rem] overflow-hidden
      shadow-[0_2px_4px_rgba(0,0,0,.04),0_8px_24px_rgba(0,0,0,.08),0_28px_56px_rgba(0,0,0,.07)]
      animate-[fadeInUp_.3s_cubic-bezier(.22,.61,.36,1)_both]">

      <!-- ── Brand panel (hidden on mobile) ── -->
      <div class="hidden sm:flex relative flex-col justify-center overflow-hidden min-h-[460px] p-11
          bg-[linear-gradient(to_bottom_right,#c2410c,#ea580c_55%,#fbbf24),radial-gradient(circle,rgba(255,255,255,.055)_1px,transparent_1px)]
          [background-size:auto,22px_22px]">

        <div class="relative z-10">
          <img src="./assets/icons/logo.svg" alt="SJFBI" class="h-16 mb-7">

          <h2 class="text-2xl font-extrabold text-white leading-tight mb-2.5">Verify Your Identity</h2>
          <p class="text-sm text-white/80 leading-relaxed mb-7">
            A one-time code has been sent to your registered email address.
          </p>

          <div class="flex flex-col">
            <!-- Step 1 (done) -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center shrink-0 bg-white/35 text-white">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
              </div>
              <span class="text-sm font-semibold text-white/70">Email entered</span>
            </div>
            <div class="w-0.5 h-[1.125rem] bg-white/20 ml-3.5"></div>
            <!-- Step 2 (active) -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white text-orange-600">2</div>
              <span class="text-sm font-semibold text-white">Verify OTP code</span>
            </div>
            <div class="w-0.5 h-[1.125rem] bg-white/20 ml-3.5"></div>
            <!-- Step 3 -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white/15 text-white/40">3</div>
              <span class="text-sm font-semibold text-white/40">Set new password</span>
            </div>
          </div>

          <div class="bg-white/[.13] rounded-2xl px-5 py-4.5 mt-6">
            <p class="text-sm text-white/85 leading-relaxed">
              Code sent to:<br>
              <span class="font-bold text-white break-all"><?= htmlspecialchars($_SESSION['email']) ?></span>
            </p>
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
            <span class="size-2 rounded-full bg-orange-600 animate-pulse"></span> Step 2 of 3
          </div>

          <div class="mb-6">
            <h1 class="text-[1.375rem] font-extrabold text-gray-900 leading-tight mb-1">Enter OTP Code</h1>
            <p class="text-sm text-gray-400 leading-relaxed">
              Type the 6-digit code we sent to your email. It expires in <strong>10 minutes</strong>.
            </p>
          </div>

          <?php if (!empty($_SESSION['message'])): $m=$_SESSION['message']; unset($_SESSION['message']); ?>
          <div class="flex items-start gap-2.5 rounded-lg px-4 py-3 text-sm font-medium mb-5 leading-relaxed
            <?= $m['type']==='success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-700' ?>">
            <svg class="shrink-0 mt-0.5 size-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <?= $m['type']==='success'
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?>
            </svg>
            <?= htmlspecialchars($m['text']) ?>
          </div>
          <?php endif; ?>

          <form method="POST" action="functions/update.php" id="otpForm">
            <input type="hidden" name="verify_otp" value="1">
            <input type="hidden" name="full_otp" id="full_otp">

            <div class="grid grid-cols-6 gap-2 mb-5" id="otp-container">
              <?php for($i=1;$i<=6;$i++): ?>
              <input type="text" inputmode="numeric" name="otp<?= $i ?>" maxlength="1" pattern="\d"
                class="otp-box w-full aspect-square text-center text-[1.4rem] font-bold text-gray-900 font-lexend
                  border-2 border-gray-200 rounded-lg bg-white outline-none p-0
                  transition-all duration-150 focus:border-orange-500 focus:ring-4 focus:ring-orange-100 focus:scale-[1.06]
                  [&.filled]:border-orange-500 [&.filled]:bg-orange-50 [&.filled]:text-orange-600"
                autocomplete="off"
                <?= $i===1?'autofocus':'' ?>
                id="otp<?= $i ?>">
              <?php endfor; ?>
            </div>

            <div class="flex items-center justify-between mt-2">
              <span class="text-sm text-gray-400">Code expires in <strong id="countdown" class="text-orange-600">10:00</strong></span>
              <button type="button" id="resendBtn" disabled onclick="resendOTP()"
                class="text-sm font-semibold text-gray-400 bg-transparent border-none p-0 transition-colors
                  disabled:cursor-default
                  enabled:text-orange-600 enabled:cursor-pointer enabled:hover:text-orange-700 enabled:hover:underline">
                Resend code
              </button>
            </div>

            <button type="submit" name="submit_otp"
              class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl py-3.5 mt-6 shadow-lg shadow-orange-600/30 hover:shadow-orange-600/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              Verify OTP
            </button>
          </form>

          <div class="text-center text-sm text-gray-400 mt-5 pt-4.5 border-t border-gray-100">
            Wrong email? <a href="forgot_password.php" class="font-semibold text-orange-600 hover:text-orange-700 hover:underline transition">Go back</a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('live_chat.php'); ?>
  
  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true });</script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

  <script>
  // ── OTP box navigation ──────────────────────────────────────────────────────
  const boxes = Array.from(document.querySelectorAll('.otp-box'));

  boxes.forEach((box, i) => {
    box.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g,'').slice(-1);
      this.classList.toggle('filled', this.value !== '');
      if (this.value && i < boxes.length - 1) boxes[i+1].focus();
      syncOTP();
    });
    box.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && !this.value && i > 0) { boxes[i-1].focus(); boxes[i-1].value=''; boxes[i-1].classList.remove('filled'); syncOTP(); }
      if (e.key === 'ArrowLeft'  && i > 0) boxes[i-1].focus();
      if (e.key === 'ArrowRight' && i < boxes.length-1) boxes[i+1].focus();
    });
  });

  // Paste support
  document.getElementById('otp-container').addEventListener('paste', function(e) {
    e.preventDefault();
    const data = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
    boxes.forEach((b,i) => { b.value = data[i]||''; b.classList.toggle('filled',!!b.value); });
    syncOTP();
    (boxes[Math.min(data.length, 5)] || boxes[5]).focus();
  });

  function syncOTP() {
    document.getElementById('full_otp').value = boxes.map(b=>b.value).join('');
  }

  // ── Countdown timer ─────────────────────────────────────────────────────────
  let seconds = 10 * 60;
  const cd = document.getElementById('countdown');
  const rb = document.getElementById('resendBtn');

  const timer = setInterval(function() {
    seconds--;
    const m = Math.floor(seconds / 60), s = seconds % 60;
    cd.textContent = m + ':' + String(s).padStart(2,'0');
    if (seconds <= 0) {
      clearInterval(timer);
      cd.textContent = '0:00';
      cd.classList.remove('text-orange-600');
      cd.classList.add('text-red-500');
      rb.disabled = false;
    }
    // Enable resend after 60 s
    if (seconds <= 9*60) { rb.disabled = false; }
  }, 1000);

  function resendOTP() {
    window.location.href = 'verify_otp.php?resend=1';
  }

  // ── Autofocus ───────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => boxes[0].focus());
  </script>

  <style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</body>
</html>