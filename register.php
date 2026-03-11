<?php
session_start();
include 'conn.php';
$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. – Create your account.">
  <meta property="og:type" content="website"><meta property="og:url" content="https://fishbrokers.net/register">
  <meta property="og:title" content="Register | St. Joseph Fish Brokerage Inc.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow">
  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <style>
  :root{--or:#ea580c;--or2:#f97316;--or3:#fbbf24;--or-bg:#fff7ed;--or-dim:rgba(234,88,12,.12);--red:#ef4444;--grn:#10b981;--g1:#f3f4f6;--g2:#e5e7eb;--g4:#9ca3af;--g9:#111827}
  *,*::before,*::after{box-sizing:border-box}
  body{font-family:'Lexend',sans-serif;background:#f8f6f3;margin:0;min-height:100vh;display:flex;flex-direction:column}
  .ap-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;
    background:radial-gradient(ellipse at 80% 15%,rgba(251,146,60,.08) 0%,transparent 55%),
               radial-gradient(ellipse at 10% 85%,rgba(234,88,12,.05) 0%,transparent 55%),#f8f6f3}
  .ap-card{display:grid;grid-template-columns:1fr 1.4fr;width:100%;max-width:56rem;border-radius:1.375rem;
    overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.08),0 28px 56px rgba(0,0,0,.07);
    animation:apIn .3s cubic-bezier(.22,.61,.36,1) both}
  @keyframes apIn{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
  @media(max-width:620px){.ap-card{grid-template-columns:1fr}.ap-brand{display:none}}
  /* brand */
  .ap-brand{position:relative;background:linear-gradient(148deg,#c2410c 0%,#ea580c 38%,#f97316 72%,#fbbf24 100%);
    padding:2.75rem 2.25rem;display:flex;flex-direction:column;justify-content:center;overflow:hidden}
  .ap-brand::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.055) 1px,transparent 1px);background-size:22px 22px}
  .ap-brand-in{position:relative;z-index:1}
  .ap-logo{display:block;height:48px;filter:brightness(0) invert(1);margin-bottom:1.875rem}
  .ap-brand h2{font-size:1.5rem;font-weight:800;color:#fff;line-height:1.2;margin:0 0 .625rem}
  .ap-brand-sub{font-size:.8125rem;color:rgba(255,255,255,.8);line-height:1.7;margin:0 0 1.75rem}
  .ap-perks{display:flex;flex-direction:column;gap:.625rem}
  .ap-perk{display:flex;align-items:center;gap:.5rem;font-size:.8rem;font-weight:600;color:rgba(255,255,255,.92)}
  .ap-perk svg{flex-shrink:0}
  .ap-wave{position:absolute;right:-1px;top:0;bottom:0;height:100%;width:52px}
  /* form */
  .ap-form-panel{background:#fff;display:flex;flex-direction:column}
  .ap-form-in{padding:2.25rem 2.25rem 2rem;flex:1;overflow-y:auto}
  .ap-badge{display:inline-flex;align-items:center;gap:.5rem;font-size:.7rem;font-weight:700;letter-spacing:.12em;
    text-transform:uppercase;color:var(--or);background:var(--or-bg);border:1px solid rgba(234,88,12,.2);
    border-radius:9999px;padding:.3rem .875rem;margin-bottom:1rem}
  .ap-dot{width:.5rem;height:.5rem;border-radius:50%;background:var(--or);animation:apDot 2s ease-in-out infinite}
  @keyframes apDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
  .ap-heading{margin:0 0 1.375rem}
  .ap-heading h1{font-size:1.375rem;font-weight:800;color:var(--g9);margin:0 0 .25rem;line-height:1.2}
  .ap-heading p{font-size:.8125rem;color:var(--g4);margin:0}
  .ap-alert{display:flex;align-items:flex-start;gap:.625rem;border-radius:.625rem;padding:.75rem 1rem;
    font-size:.8125rem;font-weight:500;margin-bottom:1.125rem;line-height:1.5}
  .ap-alert svg{flex-shrink:0;margin-top:.1rem}
  .ap-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
  .ap-suc{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
  .ap-fields{display:flex;flex-direction:column;gap:.875rem}
  .ap-field{display:flex;flex-direction:column}
  .ap-lbl{font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.325rem;display:flex;align-items:center;gap:.25rem}
  .ap-star{color:var(--red)}
  .ap-iw{position:relative}
  .ap-ico{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--g4);display:flex;pointer-events:none;z-index:1}
  .ap-inp{width:100%;padding:.6rem .875rem .6rem 2.375rem;border:1.5px solid var(--g2);border-radius:.625rem;
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
  .str-wrap{height:.3125rem;border-radius:9999px;background:var(--g2);overflow:hidden;margin-top:.375rem}
  #str-bar{height:100%;border-radius:9999px;transition:width .3s,background .3s;width:0}
  .ap-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;
    background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;font-family:'Lexend',sans-serif;
    font-size:.9375rem;font-weight:700;border:none;border-radius:.75rem;padding:.8125rem 1.5rem;cursor:pointer;
    margin-top:.25rem;box-shadow:0 4px 14px rgba(234,88,12,.28);transition:transform .15s,box-shadow .15s}
  .ap-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(234,88,12,.38)}
  .ap-btn:active{transform:translateY(0)}
  .ap-foot{text-align:center;font-size:.8125rem;color:var(--g4);margin-top:1.125rem;padding-top:1.125rem;border-top:1px solid var(--g1)}
  .ap-a{color:var(--or);font-weight:600;text-decoration:none;transition:color .15s}
  .ap-a:hover{color:#c2410c;text-decoration:underline}
  </style>
</head>
<body>
  <?php include('./components/navigation.php'); ?>
  <?php include('./components/nav_crumb.php'); ?>
  <?php include('./components/preloaders.php'); ?>

  <main class="ap-wrap">
    <div class="ap-card">

      <!-- ── Brand panel ── -->
      <div class="ap-brand">
        <div class="ap-brand-in">
          <h2>Join our growing<br>community</h2>
          <p class="ap-brand-sub">Create your account and unlock exclusive access to fresh seafood, member discounts, and easy reordering.</p>
          <div class="ap-perks">
            <?php foreach(['Member-only discounts','Order history & tracking','Faster checkout','Priority customer support'] as $t): ?>
            <div class="ap-perk">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/></svg>
              <span><?= $t ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <svg class="ap-wave" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.07)"/>
        </svg>
      </div>

      <!-- ── Form panel ── -->
      <div class="ap-form-panel">
        <div class="ap-form-in">

          <div class="ap-badge"><span class="ap-dot"></span> New Account</div>

          <div class="ap-heading">
            <h1>Create Your Account</h1>
            <p>Join us and experience the freshness of our seafood!</p>
          </div>

          <?php
          if (!empty($_SESSION['success']) || !empty($_SESSION['error'])):
            $msg  = !empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error'];
            $cls  = !empty($_SESSION['success']) ? 'ap-suc' : 'ap-err';
            $icon = !empty($_SESSION['success'])
              ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
              : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            unset($_SESSION['success'],$_SESSION['error']);
          ?>
          <div class="ap-alert <?= $cls ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><?= $icon ?></svg>
            <?= htmlspecialchars($msg) ?>
          </div>
          <?php endif; ?>

          <form action="./functions/add.php" method="POST" id="regForm">
            <div class="ap-fields">

              <!-- Email -->
              <div class="ap-field">
                <label class="ap-lbl">Email address <span class="ap-star">*</span></label>
                <div class="ap-iw">
                  <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                  <input type="email" id="Email" name="email" placeholder="juan@email.com" class="ap-inp" required autocomplete="email">
                </div>
                <span class="ap-ferr" id="email-error">Please enter a valid email address.</span>
              </div>

              <!-- Username -->
              <div class="ap-field">
                <label class="ap-lbl">Username <span class="ap-star">*</span></label>
                <div class="ap-iw">
                  <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                  <input type="text" id="Username" name="username" placeholder="juandelacruz" class="ap-inp" required autocomplete="username">
                </div>
                <ul class="ap-rlist">
                  <li class="ap-ri" id="ulen"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>At least 5 characters</li>
                  <li class="ap-ri" id="uchars"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>Only letters, numbers, and underscores</li>
                </ul>
              </div>

              <!-- Password -->
              <div class="ap-field">
                <label class="ap-lbl">Password <span class="ap-star">*</span></label>
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
                <span class="ap-ferr" id="password-error">Password does not meet the requirements above.</span>
              </div>

              <!-- Confirm Password -->
              <div class="ap-field">
                <label class="ap-lbl">Confirm Password <span class="ap-star">*</span></label>
                <div class="ap-iw">
                  <span class="ap-ico"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></span>
                  <input type="password" id="Confirm_password" name="confirm_password" placeholder="Re-enter your password" class="ap-inp" required autocomplete="new-password">
                  <button type="button" class="ap-eye" onclick="apEye('Confirm_password',this)"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg></button>
                </div>
                <span class="ap-ferr" id="confirm-password-error">Passwords do not match.</span>
              </div>

              <button type="submit" name="register_account" class="ap-btn">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Create Account
              </button>

            </div>
          </form>

          <div class="ap-foot">Already have an account? <a href="#" class="ap-a" onclick="openModal();return false;">Sign in here</a></div>
        </div>
      </div>

    </div>
  </main>

  <?php include('./components/footer.php'); ?>
  <?php include('./components/sign_in.php'); ?>
  <?php include('live_chat.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({once:true});</script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script>
  const EO='<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>';
  const EC='<path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1-4.681 1.327c-3.6 0-6.6-2-9-6c1.272-2.12 2.712-3.678 4.32-4.674m2.86-1.146a9.055 9.055 0 0 1 1.82-.18c3.6 0 6.6 2 9 6c-.666 1.11-1.379 2.067-2.138 2.87"/><path d="M3 3l18 18"/>';
  function apEye(id,btn){const i=document.getElementById(id),s=i.type==='password';i.type=s?'text':'password';btn.querySelector('svg').innerHTML=s?EC:EO;}

  document.addEventListener('DOMContentLoaded',function(){
    const em=document.getElementById('Email'),un=document.getElementById('Username');
    const pw=document.getElementById('Password'),cp=document.getElementById('Confirm_password');
    const bar=document.getElementById('str-bar');
    function sf(inp,errEl,ok){inp.classList.toggle('eb',!ok);inp.classList.toggle('sb',ok&&inp.value.length>0);if(errEl)errEl.classList.toggle('show',!ok);}
    function vEmail(){const ok=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim());sf(em,document.getElementById('email-error'),ok);return ok;}
    function vUser(){const v=un.value.trim();document.getElementById('ulen').classList.toggle('ok',v.length>=5);document.getElementById('uchars').classList.toggle('ok',/^[a-zA-Z0-9_]+$/.test(v));const ok=/^[a-zA-Z0-9_]{5,}$/.test(v);sf(un,null,ok);return ok;}
    function vPass(){const p=pw.value,c={l:p.length>=8,u:/[A-Z]/.test(p),n:/\d/.test(p),s:/[\W_]/.test(p)};
      document.getElementById('plen').classList.toggle('ok',c.l);document.getElementById('pup').classList.toggle('ok',c.u);document.getElementById('pnum').classList.toggle('ok',c.n);document.getElementById('pspc').classList.toggle('ok',c.s);
      const sc=Object.values(c).filter(Boolean).length;bar.style.width=(sc*25)+'%';bar.style.background=sc<2?'#ef4444':sc<3?'#f97316':sc<4?'#fbbf24':'#10b981';
      const ok=Object.values(c).every(Boolean);sf(pw,document.getElementById('password-error'),ok);return ok;}
    function vConf(){const ok=pw.value===cp.value&&cp.value!=='';sf(cp,document.getElementById('confirm-password-error'),ok);return ok;}
    em.addEventListener('input',vEmail);un.addEventListener('input',vUser);
    pw.addEventListener('input',()=>{vPass();if(cp.value)vConf();});cp.addEventListener('input',vConf);
    document.getElementById('regForm').addEventListener('submit',function(e){
      if(![vEmail(),vUser(),vPass(),vConf()].every(Boolean)){e.preventDefault();document.querySelector('.eb')?.scrollIntoView({behavior:'smooth',block:'center'});}
    });
  });
  </script>
</body>
</html>