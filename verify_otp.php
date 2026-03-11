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
    padding:2.75rem 2.25rem;display:flex;flex-direction:column;justify-content:center;overflow:hidden;min-height:460px}
  .ap-brand::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.055) 1px,transparent 1px);background-size:22px 22px}
  .ap-brand-in{position:relative;z-index:1}
  .ap-logo{display:block;height:48px;filter:brightness(0) invert(1);margin-bottom:1.875rem}
  .ap-brand h2{font-size:1.5rem;font-weight:800;color:#fff;line-height:1.2;margin:0 0 .625rem}
  .ap-brand-sub{font-size:.8125rem;color:rgba(255,255,255,.8);line-height:1.7;margin:0 0 1.75rem}
  .ap-steps{display:flex;flex-direction:column;gap:0}
  .ap-step{display:flex;align-items:center;gap:.75rem}
  .ap-sn{width:1.75rem;height:1.75rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0}
  .ap-sn.done{background:rgba(255,255,255,.35);color:#fff}
  .ap-sn.active{background:#fff;color:var(--or)}
  .ap-sn.next{background:rgba(255,255,255,.15);color:rgba(255,255,255,.5)}
  .ap-sl{font-size:.8125rem;font-weight:600}
  .ap-step.done   .ap-sl{color:rgba(255,255,255,.7)}
  .ap-step.active .ap-sl{color:#fff}
  .ap-step.next   .ap-sl{color:rgba(255,255,255,.4)}
  .ap-conn{width:2px;height:1.125rem;background:rgba(255,255,255,.2);margin-left:.875rem}
  .ap-wave{position:absolute;right:-1px;top:0;bottom:0;height:100%;width:52px}
  /* otp hint on brand */
  .ap-otp-hint{background:rgba(255,255,255,.13);border-radius:.875rem;padding:1.125rem 1.25rem;margin-top:1.5rem}
  .ap-otp-hint p{font-size:.8125rem;color:rgba(255,255,255,.85);margin:0;line-height:1.65}
  .ap-otp-email{font-weight:700;color:#fff;word-break:break-all}
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
  /* OTP boxes */
  .otp-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:.5rem;margin-bottom:1.25rem}
  .otp-box{width:100%;aspect-ratio:1;text-align:center;font-size:1.4rem;font-weight:700;color:var(--g9);
    font-family:'Lexend',sans-serif;border:2px solid var(--g2);border-radius:.625rem;background:#fff;
    outline:none;padding:0;transition:border-color .15s,box-shadow .15s,transform .1s;-moz-appearance:textfield}
  .otp-box::-webkit-inner-spin-button,.otp-box::-webkit-outer-spin-button{-webkit-appearance:none}
  .otp-box:focus{border-color:var(--or);box-shadow:0 0 0 3px var(--or-dim);transform:scale(1.06)}
  .otp-box.filled{border-color:var(--or);background:var(--or-bg);color:var(--or)}
  .otp-box.shake{animation:shake .3s ease}
  @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
  /* timer */
  .otp-timer-row{display:flex;align-items:center;justify-content:space-between;margin-top:.5rem}
  .otp-timer{font-size:.8125rem;color:var(--g4)}
  .otp-timer strong{color:var(--or)}
  .otp-resend{font-size:.8125rem;font-weight:600;color:var(--g4);background:none;border:none;cursor:default;padding:0;transition:color .15s}
  .otp-resend.ready{color:var(--or);cursor:pointer}
  .otp-resend.ready:hover{color:#c2410c;text-decoration:underline}
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
          <img src="./assets/icons/logo.svg" alt="SJFBI" class="ap-logo">
          <h2>Verify Your Identity</h2>
          <p class="ap-brand-sub">A one-time code has been sent to your registered email address.</p>
          <div class="ap-steps">
            <div class="ap-step done">
              <div class="ap-sn done">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
              </div>
              <span class="ap-sl">Email entered</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step active">
              <div class="ap-sn active">2</div>
              <span class="ap-sl">Verify OTP code</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step next">
              <div class="ap-sn next">3</div>
              <span class="ap-sl">Set new password</span>
            </div>
          </div>
          <div class="ap-otp-hint">
            <p>Code sent to:<br><span class="ap-otp-email"><?= htmlspecialchars($_SESSION['email']) ?></span></p>
          </div>
        </div>
        <svg class="ap-wave" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form panel ── -->
      <div class="ap-form-panel">
        <div class="ap-form-in">

          <div class="ap-badge"><span class="ap-dot"></span> Step 2 of 3</div>

          <div class="ap-heading">
            <h1>Enter OTP Code</h1>
            <p>Type the 6-digit code we sent to your email. It expires in <strong>10 minutes</strong>.</p>
          </div>

          <?php if (!empty($_SESSION['message'])): $m=$_SESSION['message']; unset($_SESSION['message']); ?>
          <div class="ap-alert <?= $m['type']==='success'?'ap-suc':'ap-err' ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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

            <div class="otp-grid" id="otp-container">
              <?php for($i=1;$i<=6;$i++): ?>
              <input type="text" inputmode="numeric" name="otp<?= $i ?>" maxlength="1" pattern="\d"
                class="otp-box" autocomplete="off"
                <?= $i===1?'autofocus':'' ?>
                id="otp<?= $i ?>">
              <?php endfor; ?>
            </div>

            <div class="otp-timer-row">
              <span class="otp-timer">Code expires in <strong id="countdown">10:00</strong></span>
              <button type="button" class="otp-resend" id="resendBtn" disabled onclick="resendOTP()">Resend code</button>
            </div>

            <button type="submit" name="submit_otp" class="ap-btn" style="margin-top:1.5rem">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              Verify OTP
            </button>
          </form>

          <div class="ap-foot">Wrong email? <a href="forgot_password.php" class="ap-a">Go back</a></div>
        </div>
      </div>

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('live_chat.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
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
      cd.style.color = '#ef4444';
      rb.disabled = false; rb.classList.add('ready');
    }
    // Enable resend after 60 s
    if (seconds <= 9*60) { rb.disabled = false; rb.classList.add('ready'); }
  }, 1000);

  function resendOTP() {
    window.location.href = 'verify_otp.php?resend=1';
  }

  // ── Autofocus ───────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => boxes[0].focus());
  </script>
</body>
</html>