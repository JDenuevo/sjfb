<?php
session_start();
include 'conn.php';
$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. – Create your account.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/register">
  <meta property="og:title" content="Register | St. Joseph Fish Brokerage Inc.">
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
    body { font-family: 'Lexend', sans-serif; }

    /* Password strength bar */
    #str-bar { height: 100%; border-radius: 9999px; transition: width .3s, background .3s; width: 0; }

    /* Requirement list item OK state */
    .req-ok  { color: #10b981; }
    .req-ok svg path { stroke: #10b981; }

    /* Show/hide field error */
    .field-err { display: none; }
    .field-err.show { display: block; }

    /* Pulse dot animation */
    @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.65)} }
    .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

    /* Card slide-in */
    @keyframes card-in { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
    .card-in { animation: card-in .3s cubic-bezier(.22,.61,.36,1) both; }
  </style>
</head>

<body class="bg-[#f8f6f3] min-h-screen flex flex-col">

  <?php include('./components/navigation.php'); ?>
  <?php include('./components/nav_crumb.php'); ?>
  <?php include('./components/preloaders.php'); ?>

  <!-- ══════════════════════════════════════════════
       MAIN CONTENT
  ══════════════════════════════════════════════ -->
  <main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="card-in w-full max-w-4xl rounded-[1.375rem] overflow-hidden shadow-2xl grid grid-cols-1 sm:grid-cols-[1fr_1.4fr]">

      <!-- ── Brand Panel ── -->
      <div class="relative hidden sm:flex flex-col justify-center px-9 py-11 overflow-hidden"
           style="background: linear-gradient(148deg,#c2410c 0%,#ea580c 38%,#f97316 72%,#fbbf24 100%)">

        <!-- Dot grid overlay -->
        <div class="absolute inset-0 opacity-20"
             style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:22px 22px"></div>

        <div class="relative z-10">
          <h2 class="text-[1.5rem] font-extrabold text-white leading-tight mb-2">
            Join our growing<br>community
          </h2>
          <p class="text-[.8125rem] text-white/80 leading-relaxed mb-7">
            Create your account and unlock exclusive access to fresh seafood, member discounts, and easy reordering.
          </p>

          <ul class="flex flex-col gap-2.5">
            <?php foreach(['Member-only discounts','Order history & tracking','Faster checkout','Priority customer support'] as $perk): ?>
            <li class="flex items-center gap-2 text-[.8rem] font-semibold text-white/90">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5" class="shrink-0">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/>
              </svg>
              <?= $perk ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Wave accent -->
        <svg class="absolute right-0 top-0 h-full w-12" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form Panel ── -->
      <div class="bg-white flex flex-col">
        <div class="flex-1 overflow-y-auto px-8 py-9">

          <!-- Badge -->
          <span class="inline-flex items-center gap-2 text-[.7rem] font-bold tracking-[.12em] uppercase text-orange-600 bg-orange-50 border border-orange-200/60 rounded-full px-3.5 py-1.5 mb-4">
            <span class="pulse-dot size-2 rounded-full bg-orange-500 inline-block"></span>
            New Account
          </span>

          <div class="mb-6">
            <h1 class="text-[1.375rem] font-extrabold text-gray-900 leading-tight mb-1">Create Your Account</h1>
            <p class="text-[.8125rem] text-gray-400">Join us and experience the freshness of our seafood!</p>
          </div>

          <!-- Flash messages -->
          <?php
          if (!empty($_SESSION['success']) || !empty($_SESSION['error'])):
            $isSuccess = !empty($_SESSION['success']);
            $msg = $isSuccess ? $_SESSION['success'] : $_SESSION['error'];
            unset($_SESSION['success'], $_SESSION['error']);
          ?>
          <div class="flex items-start gap-2.5 rounded-xl px-4 py-3 text-[.8125rem] font-medium mb-5 <?= $isSuccess ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="shrink-0 mt-px">
              <?= $isSuccess
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?>
            </svg>
            <?= htmlspecialchars($msg) ?>
          </div>
          <?php endif; ?>

          <!-- Form -->
          <form action="./functions/add.php" method="POST" id="regForm" class="space-y-4">
            <!-- Email -->
            <div>
              <label class="block text-[.8125rem] font-semibold text-gray-700 mb-1.5">
                Email address <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <input type="email" id="Email" name="email" placeholder="juan@email.com" required autocomplete="email"
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-[.875rem] text-gray-900 font-[Lexend] outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20">
              </div>
              <p class="field-err text-[.75rem] text-red-500 mt-1" id="email-error">Please enter a valid email address.</p>
            </div>

            <!-- Username -->
            <div>
              <label class="block text-[.8125rem] font-semibold text-gray-700 mb-1.5">
                Username <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <input type="text" id="Username" name="username" placeholder="juandelacruz" required autocomplete="username"
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-[.875rem] text-gray-900 font-[Lexend] outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20">
              </div>
              <ul class="mt-1.5 flex flex-col gap-1">
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400 transition-colors" id="ulen">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                  At least 5 characters
                </li>
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400 transition-colors" id="uchars">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                  Only letters, numbers, and underscores
                </li>
              </ul>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-[.8125rem] font-semibold text-gray-700 mb-1.5">
                Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" id="Password" name="password" placeholder="Create a strong password" required autocomplete="new-password"
                       class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg text-[.875rem] text-gray-900 font-[Lexend] outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20">
                <button type="button" onclick="apEye('Password',this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>
                  </svg>
                </button>
              </div>

              <!-- Strength bar -->
              <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-2">
                <div id="str-bar"></div>
              </div>

              <ul class="mt-1.5 flex flex-col gap-1">
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400" id="plen"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>At least 8 characters</li>
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400" id="pup"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 uppercase letter</li>
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400" id="pnum"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 number</li>
                <li class="req-item flex items-center gap-1.5 text-[.74rem] text-gray-400" id="pspc"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 special character</li>
              </ul>
              <p class="field-err text-[.75rem] text-red-500 mt-1" id="password-error">Password does not meet the requirements above.</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-[.8125rem] font-semibold text-gray-700 mb-1.5">
                Confirm Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </span>
                <input type="password" id="Confirm_password" name="confirm_password" placeholder="Re-enter your password" required autocomplete="new-password"
                       class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg text-[.875rem] text-gray-900 font-[Lexend] outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20">
                <button type="button" onclick="apEye('Confirm_password',this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>
                  </svg>
                </button>
              </div>
              <p class="field-err text-[.75rem] text-red-500 mt-1" id="confirm-password-error">Passwords do not match.</p>
            </div>

            <!-- Submit -->
            <button type="submit" name="register_account"
                    class="w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl text-white text-[.9375rem] font-bold transition-all duration-150 active:scale-[.97] shadow-[0_4px_14px_rgba(234,88,12,.28)] hover:shadow-[0_6px_20px_rgba(234,88,12,.38)] hover:-translate-y-px"
                    style="background:linear-gradient(135deg,#ea580c,#f97316)">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
              Create Account
            </button>

          </form>

          <div class="text-center text-[.8125rem] text-gray-400 mt-5 pt-5 border-t border-gray-100">
            Already have an account?
            <button type="button" onclick="openSignInModal()" class="text-orange-600 font-semibold hover:text-orange-700 hover:underline transition-colors">Sign in here</button>
          </div>

        </div>
      </div>
      <!-- /Form Panel -->

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('./components/sign_in.php'); ?>
  <?php include('live_chat.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true });</script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

  <script>
  // ── Eye toggle ──────────────────────────────────────────────
  const EO = '<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>';
  const EC = '<path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1-4.681 1.327c-3.6 0-6.6-2-9-6c1.272-2.12 2.712-3.678 4.32-4.674m2.86-1.146a9.055 9.055 0 0 1 1.82-.18c3.6 0 6.6 2 9 6c-.666 1.11-1.379 2.067-2.138 2.87"/><path d="M3 3l18 18"/>';
  function apEye(id, btn) {
    const i = document.getElementById(id), show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    btn.querySelector('svg').innerHTML = show ? EC : EO;
  }

  // ── Validation ───────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    const em  = document.getElementById('Email');
    const un  = document.getElementById('Username');
    const pw  = document.getElementById('Password');
    const cp  = document.getElementById('Confirm_password');
    const bar = document.getElementById('str-bar');

    // Mark input valid/invalid with Tailwind border classes
    function setValid(inp, errEl, ok) {
      inp.classList.toggle('!border-red-400',   !ok);
      inp.classList.toggle('!ring-red-400/20',  !ok);
      inp.classList.toggle('!border-emerald-400', ok && inp.value.length > 0);
      inp.classList.toggle('!ring-emerald-400/20', ok && inp.value.length > 0);
      if (errEl) errEl.classList.toggle('show', !ok && inp.value.length > 0);
    }

    // Mark requirement item ok
    function req(id, ok) {
      const el = document.getElementById(id);
      el.classList.toggle('req-ok', ok);
      el.classList.toggle('text-gray-400', !ok);
    }

    function vEmail() {
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim());
      setValid(em, document.getElementById('email-error'), ok);
      return ok;
    }

    function vUser() {
      const v = un.value.trim();
      req('ulen',   v.length >= 5);
      req('uchars', /^[a-zA-Z0-9_]+$/.test(v));
      const ok = /^[a-zA-Z0-9_]{5,}$/.test(v);
      setValid(un, null, ok);
      return ok;
    }

    function vPass() {
      const p = pw.value;
      const c = { l: p.length >= 8, u: /[A-Z]/.test(p), n: /\d/.test(p), s: /[\W_]/.test(p) };
      req('plen', c.l); req('pup', c.u); req('pnum', c.n); req('pspc', c.s);
      const sc = Object.values(c).filter(Boolean).length;
      bar.style.width = (sc * 25) + '%';
      bar.style.background = sc < 2 ? '#ef4444' : sc < 3 ? '#f97316' : sc < 4 ? '#fbbf24' : '#10b981';
      const ok = Object.values(c).every(Boolean);
      setValid(pw, document.getElementById('password-error'), ok);
      return ok;
    }

    function vConf() {
      const ok = pw.value === cp.value && cp.value !== '';
      setValid(cp, document.getElementById('confirm-password-error'), ok);
      return ok;
    }

    em.addEventListener('input', vEmail);
    un.addEventListener('input', vUser);
    pw.addEventListener('input', () => { vPass(); if (cp.value) vConf(); });
    cp.addEventListener('input', vConf);

    document.getElementById('regForm').addEventListener('submit', function (e) {
      if (![vEmail(), vUser(), vPass(), vConf()].every(Boolean)) {
        e.preventDefault();
        document.querySelector('.!border-red-400')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });
  </script>
</body>
</html>