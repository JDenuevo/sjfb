<!-- sign_in.php — Login Modal -->
<div id="hs-modal-signin" class="modal-overlay hidden">
  <div class="modal-box" id="signin-white-bg">

    <!-- Left panel (decorative) + Right panel (form) -->
    <div class="signin-grid">

      <!-- ── Left: brand panel ── -->
      <div class="signin-brand">
        <div class="signin-brand-inner">
          <div class="signin-logo">
            <img src="./assets/icons/logo.svg" alt="SJFBI" class="signin-logo-img">
          </div>
          <h2 class="signin-brand-title">Welcome back</h2>
          <p class="signin-brand-sub">Sign in to your St. Joseph Fish Brokerage account to manage orders and track deliveries.</p>

          <div class="signin-perks">
            <?php
            $perks = [
              ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0', 'text' => 'Order history & tracking'],
              ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0', 'text' => 'Exclusive member discounts'],
              ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0', 'text' => 'Faster checkout'],
            ];
            foreach ($perks as $p): ?>
              <div class="signin-perk">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path d="<?= $p['icon'] ?>"/>
                </svg>
                <span><?= $p['text'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Wave decoration -->
        <svg class="signin-wave" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.06)"/>
        </svg>
      </div>

      <!-- ── Right: form panel ── -->
      <div class="signin-form-panel">

        <!-- Close -->
        <div class="signin-close-row">
          <button type="button" class="modal-close" onclick="closeModal()" title="Close">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
          </button>
        </div>

        <div class="signin-form-inner">
          <div class="signin-form-heading">
            <h3>Sign In</h3>
            <p>Enter your credentials to continue.</p>
          </div>

          <!-- Flash error -->
          <?php if (!empty($_SESSION['error_message'])): ?>
          <div class="signin-alert">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($_SESSION['error_message']) ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>

          <form action="./functions/checker.php" method="POST" autocomplete="on" id="signin-form" name="login">
            <div class="signin-fields">

              <!-- Username -->
              <div class="signin-field">
                <label for="username" class="form-label">
                  Username
                  <span class="signin-required">*</span>
                </label>
                <div class="signin-input-wrap">
                  <span class="signin-input-icon">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                  </span>
                  <input type="text" name="username" id="username"
                    placeholder="Your username"
                    autocomplete="username"
                    class="form-input signin-padded"
                    required>
                </div>
              </div>

              <!-- Password -->
              <div class="signin-field">
                <label for="password" class="form-label">
                  Password
                  <span class="signin-required">*</span>
                </label>
                <div class="signin-input-wrap">
                  <span class="signin-input-icon">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                  </span>
                  <input type="password" id="password" name="password_hash"
                    placeholder="Your password"
                    autocomplete="current-password"
                    class="form-input signin-padded signin-password-input"
                    required>
                  <button type="button" class="signin-eye" onclick="togglePasswordVisibility()" title="Toggle password">
                    <svg id="eye-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/>
                      <path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Remember me + Forgot password -->
              <div class="signin-row-between">
                <label class="signin-remember">
                  <span class="signin-cb-wrap">
                    <input type="checkbox" id="remember-me" name="remember-me" class="signin-cb-real">
                    <span class="signin-cb-box">
                      <svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg>
                    </span>
                  </span>
                  <span>Remember me</span>
                </label>
                <a href="/sjfbi-js/forgot_password.php" class="signin-forgot">Forgot password?</a>
              </div>

              <!-- Submit -->
              <button type="submit" class="signin-submit">
                <span>Sign In</span>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
              </button>

            </div>
          </form>

          <p class="signin-footer-text">
            Don't have an account?
            <a href="/sjfbi-js/register.php" class="signin-link">Sign up here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ────────────────────────────────────────────────── -->
<style>
/* ── Overlay ── */
.modal-overlay {
  position: fixed; inset: 0; z-index: 999;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.55);
  backdrop-filter: blur(4px);
  overflow-y: auto;
  padding: 2rem 1rem;
}
.modal-overlay.hidden { display: none; }

/* ── Box ── */
.modal-box {
  background: white;
  width: 100%; max-width: 52rem;
  border-radius: 1.25rem;
  box-shadow: 0 25px 60px rgba(0,0,0,0.25);
  overflow: hidden;
  animation: signinSlideIn .28s cubic-bezier(.22,.61,.36,1) both;
}
@keyframes signinSlideIn {
  from { opacity:0; transform:translateY(-18px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── Two-column grid ── */
.signin-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
}
@media (max-width: 640px) {
  .signin-grid { grid-template-columns: 1fr; }
  .signin-brand { display: none; }
}

/* ── Brand (left) panel ── */
.signin-brand {
  position: relative;
  background: linear-gradient(145deg, #ea580c 0%, #f97316 50%, #fbbf24 100%);
  padding: 2.5rem 2rem;
  display: flex; flex-direction: column; justify-content: center;
  overflow: hidden;
  min-height: 420px;
}
.signin-brand-inner { position: relative; z-index: 1; }
.signin-logo { margin-bottom: 1.5rem; }
.signin-logo-img { height: 52px;}
.signin-brand-title {
  font-size: 1.6rem; font-weight: 800;
  color: white; line-height: 1.2;
  margin-bottom: .75rem;
}
.signin-brand-sub {
  font-size: .8125rem; color: rgba(255,255,255,.8);
  line-height: 1.65; margin-bottom: 2rem;
}
.signin-perks { display: flex; flex-direction: column; gap: .75rem; }
.signin-perk {
  display: flex; align-items: center; gap: .625rem;
  font-size: .8125rem; font-weight: 600; color: rgba(255,255,255,.95);
}
.signin-perk svg { color: rgba(255,255,255,.9); flex-shrink: 0; }
.signin-wave {
  position: absolute; right: -1px; top: 0; bottom: 0;
  width: 60px; height: 100%;
}

/* ── Form (right) panel ── */
.signin-form-panel {
  display: flex; flex-direction: column;
  background: #fff;
}
.signin-close-row {
  display: flex; justify-content: flex-end;
  padding: .875rem 1rem .25rem;
}
.modal-close {
  width: 1.875rem; height: 1.875rem;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; background: #f3f4f6;
  color: #6b7280; border: none; cursor: pointer;
  transition: background .15s, color .15s;
}
.modal-close:hover { background: #fee2e2; color: #dc2626; }
.signin-form-inner { padding: .5rem 2rem 2rem; flex: 1; }

.signin-form-heading { margin-bottom: 1.5rem; }
.signin-form-heading h3 {
  font-size: 1.25rem; font-weight: 800; color: #111827; margin-bottom: .25rem;
}
.signin-form-heading p { font-size: .8125rem; color: #6b7280; }

/* ── Alert ── */
.signin-alert {
  display: flex; align-items: center; gap: .625rem;
  background: #fef2f2; border: 1px solid #fecaca;
  border-radius: .625rem; padding: .75rem 1rem;
  font-size: .8125rem; font-weight: 500; color: #b91c1c;
  margin-bottom: 1.25rem;
}

/* ── Fields ── */
.signin-fields { display: flex; flex-direction: column; gap: 1rem; }
.signin-field { display: flex; flex-direction: column; }

.form-label {
  display: block; font-size: .8125rem; font-weight: 600;
  color: #374151; margin-bottom: .375rem;
}
.signin-required { color: #ef4444; margin-left: .125rem; }

.signin-input-wrap { position: relative; }
.signin-input-icon {
  position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
  color: #9ca3af; display: flex; pointer-events: none;
}
.form-input {
  width: 100%; padding: .625rem .75rem;
  border: 1.5px solid #e5e7eb; border-radius: .625rem;
  font-size: .875rem; color: #111827;
  transition: border-color .15s, box-shadow .15s;
  outline: none; background: #fff;
  box-sizing: border-box;
}
.form-input:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,.1); }
.signin-padded { padding-left: 2.375rem; }
.signin-password-input { padding-right: 2.5rem; }

.signin-eye {
  position: absolute; right: .625rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #9ca3af; display: flex; padding: .25rem;
  transition: color .15s;
}
.signin-eye:hover { color: #4b5563; }

/* ── Remember / Forgot row ── */
.signin-row-between {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: .25rem;
}
.signin-remember {
  display: flex; align-items: center; gap: .5rem;
  font-size: .8125rem; color: #374151; cursor: pointer;
}
.signin-cb-wrap { position: relative; width: 1rem; height: 1rem; flex-shrink: 0; }
.signin-cb-real {
  position: absolute; opacity: 0; width: 100%; height: 100%;
  margin: 0; cursor: pointer; z-index: 1;
}
.signin-cb-box {
  display: flex; align-items: center; justify-content: center;
  width: 1rem; height: 1rem; border-radius: 3px;
  border: 1.5px solid #d1d5db; background: #fff;
  transition: border-color .15s, background .15s; pointer-events: none;
}
.signin-cb-real:checked ~ .signin-cb-box { background: #ea580c; border-color: #ea580c; }
.signin-cb-box svg { display: none; width: 10px; height: 10px; stroke: white; stroke-width: 3; fill: none; }
.signin-cb-real:checked ~ .signin-cb-box svg { display: block; }

.signin-forgot {
  font-size: .8125rem; color: #ea580c; font-weight: 500;
  text-decoration: none; transition: color .15s;
}
.signin-forgot:hover { color: #c2410c; text-decoration: underline; }

/* ── Submit ── */
.signin-submit {
  width: 100%; display: flex; align-items: center; justify-content: center; gap: .5rem;
  background: linear-gradient(135deg, #ea580c, #f97316);
  color: white; font-size: .9375rem; font-weight: 700;
  border: none; border-radius: .75rem; padding: .8125rem 1.5rem;
  cursor: pointer; margin-top: .5rem;
  box-shadow: 0 4px 14px rgba(234,88,12,.3);
  transition: transform .15s, box-shadow .15s;
}
.signin-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(234,88,12,.4);
}
.signin-submit:active { transform: translateY(0); }

/* ── Footer ── */
.signin-footer-text {
  text-align: center; font-size: .8125rem; color: #6b7280; margin-top: 1.25rem;
}
.signin-link {
  color: #ea580c; font-weight: 600; text-decoration: none;
  transition: color .15s;
}
.signin-link:hover { color: #c2410c; text-decoration: underline; }
</style>

<!-- ────────────────────────────────────────────────── -->
<script>
function openModal() {
  document.getElementById('hs-modal-signin').classList.remove('hidden');
  const rememberMe = localStorage.getItem('rememberMeChecked');
  if (rememberMe === 'true') {
    document.getElementById('remember-me').checked = true;
  }
  // Focus username after animation
  setTimeout(function() {
    document.getElementById('username').focus();
  }, 180);
}

function closeModal() {
  var overlay = document.getElementById('hs-modal-signin');
  var box     = document.getElementById('signin-white-bg');
  box.style.animation = 'signinSlideOut .2s cubic-bezier(.55,0,.1,1) both';
  setTimeout(function() {
    overlay.classList.add('hidden');
    box.style.animation = '';
  }, 200);
}

// Close on backdrop click
document.getElementById('hs-modal-signin').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

function togglePasswordVisibility() {
  var field = document.getElementById('password');
  var icon  = document.getElementById('eye-icon');
  var isHidden = field.type === 'password';
  field.type = isHidden ? 'text' : 'password';
  icon.innerHTML = isHidden
    ? '<path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1-4.681 1.327c-3.6 0-6.6-2-9-6c1.272-2.12 2.712-3.678 4.32-4.674m2.86-1.146a9.055 9.055 0 0 1 1.82-.18c3.6 0 6.6 2 9 6c-.666 1.11-1.379 2.067-2.138 2.87"/><path d="M3 3l18 18"/>'
    : '<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>';
}

document.addEventListener('DOMContentLoaded', function() {
  var form       = document.getElementById('signin-form');
  var rememberCb = document.getElementById('remember-me');

  // Restore remember-me state
  if (localStorage.getItem('rememberMeChecked') === 'true' && rememberCb) {
    rememberCb.checked = true;
  }

  if (form) {
    form.addEventListener('submit', function() {
      localStorage.setItem('rememberMeChecked', rememberCb.checked);
    });
  }
});

// Slide-out keyframe (added dynamically to avoid duplication)
(function() {
  var s = document.createElement('style');
  s.textContent = '@keyframes signinSlideOut { from{opacity:1;transform:translateY(0) scale(1)} to{opacity:0;transform:translateY(-14px) scale(.97)} }';
  document.head.appendChild(s);
})();
</script>