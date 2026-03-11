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
    $stmt = $conn->prepare("UPDATE accounts SET password_hash = ?, reset_otp = NULL, otp_expiry = NULL WHERE email = ?");
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
  .ap-sl{font-size:.8125rem;font-weight:600}
  .ap-step.done   .ap-sl{color:rgba(255,255,255,.7)}
  .ap-step.active .ap-sl{color:#fff}
  .ap-conn{width:2px;height:1.125rem;background:rgba(255,255,255,.2);margin-left:.875rem}
  .ap-wave{position:absolute;right:-1px;top:0;bottom:0;height:100%;width:52px}
  /* tip box */
  .ap-tip{background:rgba(255,255,255,.13);border-radius:.875rem;padding:1.125rem 1.25rem;margin-top:1.5rem}
  .ap-tip-title{font-size:.8rem;font-weight:700;color:#fff;margin:0 0 .5rem;display:flex;align-items:center;gap:.4rem}
  .ap-tip ul{margin:0;padding:0 0 0 1rem;display:flex;flex-direction:column;gap:.25rem}
  .ap-tip li{font-size:.78rem;color:rgba(255,255,255,.8);line-height:1.5}
  .ap-form-panel{background:#fff;display:flex;flex-direction:column}
  .ap-form-in{padding:2.5rem 2.25rem;flex:1}
  .ap-badge{display:inline-flex;align-items:center;gap:.5rem;font-size:.7rem;font-weight:700;letter-spacing:.12em;
    text-transform:uppercase;color:var(--or);background:var(--or-bg);border:1px solid rgba(234,88,12,.2);
    border-radius:9999px;padding:.3rem .875rem;margin-bottom:1rem}
  .ap-dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--or);animation:apDot 2s ease-in-out infinite}
  @keyframes apDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
  .ap-heading{margin:0 0 1.5rem}
  .ap-heading h1{font-size:1.375rem;font-weight:800;color:var(--g9);margin:0 0 .25rem;line-height:1.2}
  .ap-heading p{font-size:.8125rem;color:var(--g4);margin:0}
  .ap-alert{display:flex;align-items:flex-start;gap:.625rem;border-radius:.625rem;padding:.75rem 1rem;
    font-size:.8125rem;font-weight:500;margin-bottom:1.25rem;line-height:1.5}
  .ap-alert svg{flex-shrink:0;margin-top:.1rem}
  .ap-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
  .ap-suc{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
  .ap-fields{display:flex;flex-direction:column;gap:1rem}
  .ap-field{display:flex;flex-direction:column}
  .ap-lbl{font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.325rem;display:flex;align-items:center;gap:.25rem}
  .ap-star{color:var(--red)}
  .ap-iw{position:relative}
  .ap-ico{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--g4);display:flex;pointer-events:none;z-index:1}
  .ap-inp{width:100%;padding:.625rem .875rem .625rem 2.375rem;border:1.5px solid var(--g2);border-radius:.625rem;
    font-size:.875rem;color:var(--g9);font-family:'Lexend',sans-serif;background:#fff;
    transition:border-color .15s,box-shadow .15s;outline:none}
  .ap-inp:focus{border-color:var(--or);box-shadow:0 0 0 3px var(--or-dim)}
  .ap-inp.eb{border-color:var(--red)!important;box-shadow:0 0 0 3px rgba(239,68,68,.1)!important}
  .ap-inp.sb{border-color:var(--grn)!important;box-shadow:0 0 0 3px rgba(16,185,129,.1)!important}
  .ap-eye{position:absolute;right:.625rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--g4);display:flex;padding:.25rem;transition:color .15s}
  .ap-eye:hover{color:#4b5563}
  .ap-rlist{margin:.375rem 0 0;padding:0;list-style:none;display:flex;flex-direction:column;gap:.225rem}
  .ap-ri{display:flex;align-items:center;gap:.4rem;font-size:.74rem;color:var(--g4);transition:color .2s}
  .ap-ri svg{flex-shrink:0}
  .ap-ri.ok{color:var(--grn)}
  .ap-ferr{font-size:.75rem;color:var(--red);margin-top:.3rem;display:none}
  .ap-ferr.show{display:block}
  /* strength bar */
  .str-wrap{height:.3125rem;border-radius:9999px;background:var(--g2);overflow:hidden;margin-top:.375rem}
  #str-bar{height:100%;border-radius:9999px;transition:width .3s,background .3s;width:0}
  .ap-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;
    background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;font-family:'Lexend',sans-serif;
    font-size:.9375rem;font-weight:700;border:none;border-radius:.75rem;padding:.8125rem 1.5rem;cursor:pointer;
    margin-top:.25rem;box-shadow:0 4px 14px rgba(234,88,12,.28);transition:transform .15s,box-shadow .15s}
  .ap-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(234,88,12,.38)}
  .ap-btn:active{transform:translateY(0)}
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
          <h2>Almost there!</h2>
          <p class="ap-brand-sub">You're on the final step. Create a strong new password for your account.</p>
          <div class="ap-steps">
            <div class="ap-step done">
              <div class="ap-sn done"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg></div>
              <span class="ap-sl">Email entered</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step done">
              <div class="ap-sn done"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg></div>
              <span class="ap-sl">OTP verified</span>
            </div>
            <div class="ap-conn"></div>
            <div class="ap-step active">
              <div class="ap-sn active">3</div>
              <span class="ap-sl">Set new password</span>
            </div>
          </div>
          <div class="ap-tip">
            <p class="ap-tip-title">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Strong password tips
            </p>
            <ul>
              <li>At least 8 characters</li>
              <li>Mix uppercase and lowercase</li>
              <li>Include numbers and symbols</li>
              <li>Avoid using personal info</li>
            </ul>
          </div>
        </div>
        <svg class="ap-wave" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form panel ── -->
      <div class="ap-form-panel">
        <div class="ap-form-in">

          <div class="ap-badge"><span class="ap-dot"></span> Step 3 of 3 — Final Step</div>

          <div class="ap-heading">
            <h1>Set New Password</h1>
            <p>Choose a strong password to secure your account.</p>
          </div>

          <?php if (!empty($_SESSION['reset_error'])): ?>
          <div class="ap-alert ap-err">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($_SESSION['reset_error']); unset($_SESSION['reset_error']); ?>
          </div>
          <?php endif; ?>

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

          <form method="POST" action="reset_password.php" id="resetForm">
            <div class="ap-fields">

              <!-- New Password -->
              <div class="ap-field">
                <label class="ap-lbl">New Password <span class="ap-star">*</span></label>
                <div class="ap-iw">
                  <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                  <input type="password" id="Password" name="password" placeholder="Create a strong password" class="ap-inp" required autocomplete="new-password">
                  <button type="button" class="ap-eye" onclick="apEye('Password',this)"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg></button>
                </div>
                <div class="str-wrap"><div id="str-bar"></div></div>
                <ul class="ap-rlist">
                  <li class="ap-ri" id="plen"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>At least 8 characters</li>
                  <li class="ap-ri" id="pup"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 uppercase letter</li>
                  <li class="ap-ri" id="pnum"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 number</li>
                  <li class="ap-ri" id="pspc"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>1 special character</li>
                </ul>
                <span class="ap-ferr" id="pw-err">Password does not meet the requirements.</span>
              </div>

              <!-- Confirm Password -->
              <div class="ap-field">
                <label class="ap-lbl">Confirm Password <span class="ap-star">*</span></label>
                <div class="ap-iw">
                  <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></span>
                  <input type="password" id="ConfirmPassword" name="confirm_password" placeholder="Re-enter your password" class="ap-inp" required autocomplete="new-password">
                  <button type="button" class="ap-eye" onclick="apEye('ConfirmPassword',this)"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg></button>
                </div>
                <span class="ap-ferr" id="cp-err">Passwords do not match.</span>
              </div>

              <button type="submit" name="reset_password" class="ap-btn">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Reset Password
              </button>

            </div>
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

  function sf(inp,errEl,ok){inp.classList.toggle('eb',!ok);inp.classList.toggle('sb',ok&&inp.value.length>0);if(errEl)errEl.classList.toggle('show',!ok);}

  function vPass(){
    const p=pw.value;
    const c={l:p.length>=8,u:/[A-Z]/.test(p),n:/\d/.test(p),s:/[\W_]/.test(p)};
    document.getElementById('plen').classList.toggle('ok',c.l);
    document.getElementById('pup').classList.toggle('ok',c.u);
    document.getElementById('pnum').classList.toggle('ok',c.n);
    document.getElementById('pspc').classList.toggle('ok',c.s);
    const sc=Object.values(c).filter(Boolean).length;
    bar.style.width=(sc*25)+'%';
    bar.style.background=sc<2?'#ef4444':sc<3?'#f97316':sc<4?'#fbbf24':'#10b981';
    const ok=Object.values(c).every(Boolean);sf(pw,pwErr,ok);return ok;
  }
  function vConf(){const ok=pw.value===cp.value&&cp.value!=='';sf(cp,cpErr,ok);return ok;}

  pw.addEventListener('input',()=>{vPass();if(cp.value)vConf();});
  cp.addEventListener('input',vConf);

  document.getElementById('resetForm').addEventListener('submit',function(e){
    if(![vPass(),vConf()].every(Boolean)){e.preventDefault();document.querySelector('.eb')?.scrollIntoView({behavior:'smooth',block:'center'});}
  });
  </script>
</body>
</html>