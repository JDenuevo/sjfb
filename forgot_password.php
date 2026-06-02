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
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">
  
  <!-- ✅ UNIFIED CART CORE — must load before cart.php / products.php -->
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <style>
  :root{--or:#ea580c;--or2:#f97316;--or-bg:#fff7ed;--or-dim:rgba(234,88,12,.12);--red:#ef4444;--grn:#10b981;--g1:#f3f4f6;--g2:#e5e7eb;--g4:#9ca3af;--g9:#111827}
  *,*::before,*::after{box-sizing:border-box}
  body{font-family:'Lexend',sans-serif;background:#f8f6f3;margin:0;min-height:100vh;display:flex;flex-direction:column}
  .ap-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:2.5rem 1rem;
    background:radial-gradient(ellipse at 80% 15%,rgba(251,146,60,.08) 0%,transparent 55%),
               radial-gradient(ellipse at 10% 85%,rgba(234,88,12,.05) 0%,transparent 55%),#f8f6f3}
  .ap-card{display:grid;grid-template-columns:1fr 1.35fr;width:100%;max-width:52rem;border-radius:1.375rem;
    overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.08),0 28px 56px rgba(0,0,0,.07);
    animation:apIn .3s cubic-bezier(.22,.61,.36,1) both}
  @keyframes apIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
  @media(max-width:620px){.ap-card{grid-template-columns:1fr}.ap-brand{display:none}}
  .ap-brand{position:relative;background:linear-gradient(148deg,#c2410c 0%,#ea580c 38%,#f97316 72%,#fbbf24 100%);
    padding:2.75rem 2.25rem;display:flex;flex-direction:column;justify-content:center;overflow:hidden;min-height:440px}
  .ap-brand::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.055) 1px,transparent 1px);background-size:22px 22px}
  .ap-brand-in{position:relative;z-index:1}
  .ap-logo{display:block;height:48px;filter:brightness(0) invert(1);margin-bottom:1.875rem}
  .ap-brand h2{font-size:1.5rem;font-weight:800;color:#fff;line-height:1.2;margin:0 0 .625rem}
  .ap-brand-sub{font-size:.8125rem;color:rgba(255,255,255,.8);line-height:1.7;margin:0 0 1.75rem}
  /* progress steps */
  .ap-steps{display:flex;flex-direction:column;gap:0}
  .ap-step{display:flex;align-items:center;gap:.75rem}
  .ap-sn{width:1.75rem;height:1.75rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0}
  .ap-sn.active{background:#fff;color:var(--or)}
  .ap-sn.next{background:rgba(255,255,255,.15);color:rgba(255,255,255,.5)}
  .ap-sl{font-size:.8125rem;font-weight:600}
  .ap-step.active .ap-sl{color:#fff}
  .ap-step.next   .ap-sl{color:rgba(255,255,255,.45)}
  .ap-conn{width:2px;height:1.125rem;background:rgba(255,255,255,.2);margin-left:.875rem}
  .ap-wave{position:absolute;right:-1px;top:0;bottom:0;height:100%;width:52px}
  .ap-form-panel{background:#fff;display:flex;flex-direction:column}
  .ap-form-in{padding:2.5rem 2.25rem;flex:1}
  .ap-badge{display:inline-flex;align-items:center;gap:.5rem;font-size:.7rem;font-weight:700;letter-spacing:.12em;
    text-transform:uppercase;color:var(--or);background:var(--or-bg);border:1px solid rgba(234,88,12,.2);
    border-radius:9999px;padding:.3rem .875rem;margin-bottom:1rem}
  .ap-dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--or);animation:apDot 2s ease-in-out infinite}
  @keyframes apDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
  .ap-heading{margin:0 0 1.5rem}
  .ap-heading h1{font-size:1.375rem;font-weight:800;color:var(--g9);margin:0 0 .25rem;line-height:1.2}
  .ap-heading p{font-size:.8125rem;color:var(--g4);margin:0;line-height:1.6}
  .ap-alert{display:flex;align-items:flex-start;gap:.625rem;border-radius:.625rem;padding:.75rem 1rem;
    font-size:.8125rem;font-weight:500;margin-bottom:1.25rem;line-height:1.5}
  .ap-alert svg{flex-shrink:0;margin-top:.1rem}
  .ap-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
  .ap-suc{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
  .ap-info{display:flex;gap:.75rem;background:var(--or-bg);border:1px solid rgba(234,88,12,.15);
    border-radius:.75rem;padding:.875rem 1.125rem;margin-bottom:1.375rem}
  .ap-info svg{flex-shrink:0;color:var(--or);margin-top:.1rem}
  .ap-info p{font-size:.8125rem;color:#92400e;margin:0;line-height:1.6}
  .ap-fields{display:flex;flex-direction:column;gap:1rem}
  .ap-lbl{font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.35rem;display:block}
  .ap-iw{position:relative}
  .ap-ico{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--g4);display:flex;pointer-events:none}
  .ap-inp{width:100%;padding:.65rem .875rem .65rem 2.375rem;border:1.5px solid var(--g2);border-radius:.625rem;
    font-size:.875rem;color:var(--g9);font-family:'Lexend',sans-serif;background:#fff;
    transition:border-color .15s,box-shadow .15s;outline:none}
  .ap-inp:focus{border-color:var(--or);box-shadow:0 0 0 3px var(--or-dim)}
  .ap-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;
    background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;font-family:'Lexend',sans-serif;
    font-size:.9375rem;font-weight:700;border:none;border-radius:.75rem;padding:.8125rem 1.5rem;cursor:pointer;
    box-shadow:0 4px 14px rgba(234,88,12,.28);transition:transform .15s,box-shadow .15s}
  .ap-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(234,88,12,.38)}
  .ap-btn:active{transform:translateY(0)}
  .ap-foot{text-align:center;font-size:.8125rem;color:var(--g4);margin-top:1.25rem;padding-top:1.125rem;border-top:1px solid var(--g1)}
  .ap-a{color:var(--or);font-weight:600;text-decoration:none;transition:color .15s}
  .ap-a:hover{color:#c2410c;text-decoration:underline}
  </style>
</head>
<body>
  <?php include('./components/preloaders.php'); ?>
  <?php include('./components/navigation.php'); ?>

  <main class="ap-wrap">
    <div class="ap-card">

      <!-- ── Brand panel ── -->
      <div class="ap-brand">
        <div class="ap-brand-in">
          <h2>Password Recovery</h2>
          <p class="ap-brand-sub">We'll guide you through three quick steps to securely regain access to your account.</p>
          <div class="ap-steps">
            <div class="ap-step active">
              <div class="ap-sn active">1</div>
              <span class="ap-sl">Enter your email</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step next">
              <div class="ap-sn next">2</div>
              <span class="ap-sl">Verify OTP code</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step next">
              <div class="ap-sn next">3</div>
              <span class="ap-sl">Set new password</span>
            </div>
          </div>
        </div>
        <svg class="ap-wave" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form panel ── -->
      <div class="ap-form-panel">
        <div class="ap-form-in">

          <div class="ap-badge"><span class="ap-dot"></span> Step 1 of 3</div>

          <div class="ap-heading">
            <h1>Forgot Password?</h1>
            <p>Enter your registered email and we'll send you an OTP code to reset your password.</p>
          </div>

          <?php if (!empty($_SESSION['error'])): ?>
          <div class="ap-alert ap-err">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['success'])): ?>
          <div class="ap-alert ap-suc">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          </div>
          <?php endif; ?>

          <div class="ap-info">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <p>Use the email address linked to your SJFBI account. The OTP will expire after <strong>10 minutes</strong>.</p>
          </div>

          <form method="POST" action="functions/update.php" class="ap-fields">
            <input type="hidden" name="forgot_password" value="1">
            <div>
              <label class="ap-lbl">Email Address</label>
              <div class="ap-iw">
                <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                <input type="email" name="email" placeholder="Enter your registered email" class="ap-inp" required autofocus autocomplete="email">
              </div>
            </div>
            <button type="submit" name="send_otp" class="ap-btn">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Send OTP Code
            </button>
          </form>

          <div class="ap-foot">Remember your password? <a href="index.php" class="ap-a">Sign in here</a></div>
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