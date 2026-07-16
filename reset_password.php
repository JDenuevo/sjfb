<?php
session_start();
include 'conn.php';

date_default_timezone_set('Asia/Manila');

function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['email'])) {
        redirectWithMessage('forgot_password.php', "Session expired");
    }

    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);
    $email    = $_SESSION['email'];

    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W]/',  $password)) {
        $_SESSION['reset_error'] = "Password must be at least 8 characters with an uppercase letter, number, and special character.";
        header("Location: reset_password.php"); exit();
    }

    if ($password !== $confirm) {
        $_SESSION['reset_error'] = "Passwords don't match!";
        header("Location: reset_password.php"); exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    // Uses renamed column: account_email (was email)
    $stmt = $conn->prepare("UPDATE accounts SET password_hash = ?, reset_otp = NULL, otp_expiry = NULL WHERE account_email = ?");
    $stmt->bind_param("ss", $hash, $email);

    if ($stmt->execute()) {
        session_unset(); session_destroy();
        session_start();
        $_SESSION['success'] = "Password reset successfully! You can now login with your new password.";
        header("Location: index.php"); exit();
    } else {
        $_SESSION['reset_error'] = "Password reset failed. Please try again.";
        header("Location: reset_password.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | St. Joseph Fish Brokerage Inc.</title>
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
  
  <style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
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

        <div class="relative z-10">
          <img src="./assets/icons/logo.svg" alt="SJFBI" class="h-12 mb-7 brightness-0 invert">

          <h2 class="text-2xl font-extrabold text-white leading-tight mb-2.5">Almost there!</h2>
          <p class="text-sm text-white/80 leading-relaxed mb-7">
            You're on the final step. Create a strong new password for your account.
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
            <!-- Step 2 (done) -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center shrink-0 bg-white/35 text-white">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
              </div>
              <span class="text-sm font-semibold text-white/70">OTP verified</span>
            </div>
            <div class="w-0.5 h-[1.125rem] bg-white/20 ml-3.5"></div>
            <!-- Step 3 (active) -->
            <div class="flex items-center gap-3">
              <div class="size-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-white text-orange-600">3</div>
              <span class="text-sm font-semibold text-white">Set new password</span>
            </div>
          </div>

          <div class="bg-white/[.13] rounded-2xl px-5 py-4.5 mt-6">
            <p class="text-[0.8rem] font-bold text-white mb-2 flex items-center gap-1.5">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Strong password tips
            </p>
            <ul class="m-0 pl-4 flex flex-col gap-1 list-disc">
              <li class="text-[0.78rem] text-white/80 leading-relaxed">At least 8 characters</li>
              <li class="text-[0.78rem] text-white/80 leading-relaxed">Mix uppercase and lowercase</li>
              <li class="text-[0.78rem] text-white/80 leading-relaxed">Include numbers and symbols</li>
              <li class="text-[0.78rem] text-white/80 leading-relaxed">Avoid using personal info</li>
            </ul>
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
            <span class="size-2 rounded-full bg-orange-600 animate-pulse"></span> Step 3 of 3 — Final Step
          </div>

          <div class="mb-6">
            <h1 class="text-[1.375rem] font-extrabold text-gray-900 leading-tight mb-1">Set New Password</h1>
            <p class="text-sm text-gray-400">Choose a strong password to secure your account.</p>
          </div>

          <?php if (!empty($_SESSION['reset_error'])): ?>
          <div class="flex items-start gap-2.5 rounded-lg px-4 py-3 text-sm font-medium mb-5 leading-relaxed bg-red-50 border border-red-200 text-red-700">
            <svg class="shrink-0 mt-0.5 size-[15px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($_SESSION['reset_error']); unset($_SESSION['reset_error']); ?>
          </div>
          <?php endif; ?>

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

          <form method="POST" action="reset_password.php" id="resetForm" class="flex flex-col gap-4">

            <!-- New Password -->
            <div>
              <label class="flex items-center gap-1 text-sm font-semibold text-gray-700 mb-1.5">
                New Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" id="Password" name="password" placeholder="Create a strong password"
                  required autocomplete="new-password"
                  class="py-2.5 pl-10 pr-10 block w-full border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none transition
                    invalid:border-red-500">
                <button type="button" class="ap-eye absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors p-1" onclick="apEye('Password',this)">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg>
                </button>
              </div>
              <div class="h-[0.3125rem] rounded-full bg-gray-200 overflow-hidden mt-1.5">
                <div id="str-bar" class="h-full rounded-full transition-all duration-300" style="width:0"></div>
              </div>
              <ul class="mt-1.5 p-0 list-none flex flex-col gap-1">
                <li class="flex items-center gap-1.5 text-[0.74rem] text-gray-400 transition-colors" id="plen">
                  <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>At least 8 characters
                </li>
                <li class="flex items-center gap-1.5 text-[0.74rem] text-gray-400 transition-colors" id="pup">
                  <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 uppercase letter
                </li>
                <li class="flex items-center gap-1.5 text-[0.74rem] text-gray-400 transition-colors" id="pnum">
                  <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 number
                </li>
                <li class="flex items-center gap-1.5 text-[0.74rem] text-gray-400 transition-colors" id="pspc">
                  <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 special character
                </li>
              </ul>
              <span class="text-xs text-red-500 mt-1 hidden" id="pw-err">Password does not meet the requirements.</span>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="flex items-center gap-1 text-sm font-semibold text-gray-700 mb-1.5">
                Confirm Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </span>
                <input type="password" id="ConfirmPassword" name="confirm_password" placeholder="Re-enter your password"
                  required autocomplete="new-password"
                  class="py-2.5 pl-10 pr-10 block w-full border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none transition">
                <button type="button" class="ap-eye absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors p-1" onclick="apEye('ConfirmPassword',this)">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg>
                </button>
              </div>
              <span class="text-xs text-red-500 mt-1 hidden" id="cp-err">Passwords do not match.</span>
            </div>

            <button type="submit" name="reset_password"
              class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl py-3.5 mt-1 shadow-lg shadow-orange-600/30 hover:shadow-orange-600/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Reset Password
            </button>
          </form>
        </div>
      </div>

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('live_chat.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script>
  const EO='<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>';
  const EC='<path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1-4.681 1.327c-3.6 0-6.6-2-9-6c1.272-2.12 2.712-3.678 4.32-4.674m2.86-1.146a9.055 9.055 0 0 1 1.82-.18c3.6 0 6.6 2 9 6c-.666 1.11-1.379 2.067-2.138 2.87"/><path d="M3 3l18 18"/>';
  function apEye(id,btn){const i=document.getElementById(id),s=i.type==='password';i.type=s?'text':'password';btn.querySelector('svg').innerHTML=s?EC:EO;}

  const pw   = document.getElementById('Password');
  const cp   = document.getElementById('ConfirmPassword');
  const bar  = document.getElementById('str-bar');
  const pwErr= document.getElementById('pw-err');
  const cpErr= document.getElementById('cp-err');

  function setFieldState(inp, errEl, ok) {
    inp.classList.toggle('border-red-500', !ok);
    inp.classList.toggle('ring-2', !ok);
    inp.classList.toggle('ring-red-100', !ok);
    inp.classList.toggle('border-green-500', ok && inp.value.length > 0);
    if (errEl) errEl.classList.toggle('hidden', ok);
  }

  function setReqState(el, ok) {
    el.classList.toggle('text-green-600', ok);
    el.classList.toggle('text-gray-400', !ok);
  }

  function vPass(){
    const p=pw.value;
    const c={l:p.length>=8,u:/[A-Z]/.test(p),n:/\d/.test(p),s:/[\W_]/.test(p)};
    setReqState(document.getElementById('plen'), c.l);
    setReqState(document.getElementById('pup'),  c.u);
    setReqState(document.getElementById('pnum'), c.n);
    setReqState(document.getElementById('pspc'), c.s);
    const sc=Object.values(c).filter(Boolean).length;
    bar.style.width=(sc*25)+'%';
    bar.style.background=sc<2?'#ef4444':sc<3?'#f97316':sc<4?'#fbbf24':'#10b981';
    const ok=Object.values(c).every(Boolean); setFieldState(pw,pwErr,ok); return ok;
  }
  function vConf(){const ok=pw.value===cp.value&&cp.value!=='';setFieldState(cp,cpErr,ok);return ok;}

  pw.addEventListener('input',()=>{vPass();if(cp.value)vConf();});
  cp.addEventListener('input',vConf);

  document.getElementById('resetForm').addEventListener('submit',function(e){
    if(![vPass(),vConf()].every(Boolean)){
      e.preventDefault();
      document.querySelector('.border-red-500')?.scrollIntoView({behavior:'smooth',block:'center'});
    }
  });
  </script>
</body>
</html>