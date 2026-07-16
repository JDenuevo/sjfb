<?php
// Get the base URL for your site
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
?>

<!-- sign_in.php — Login Modal (Tailwind + Preline UI) -->
<div id="hs-modal-signin" class="modal-overlay hidden fixed inset-0 z-[999] flex items-center justify-center bg-black/55 backdrop-blur-sm overflow-y-auto p-4">
  <div id="signin-white-bg" class="signin-animate w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden">

    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1.2fr]">

      <!-- ── Left: brand panel (hidden on mobile) ── -->
      <div class="hidden sm:flex relative flex-col justify-center overflow-hidden min-h-[420px] p-10 bg-gradient-to-br from-orange-600 via-orange-500 to-amber-400">
        <div class="relative z-10">
          <img src="<?= $baseUrl ?>/assets/icons/logo.svg" alt="SJFBI" class="h-16 mb-6">

          <h2 class="text-2xl font-extrabold text-white leading-tight mb-3">Welcome back</h2>
          <p class="text-sm text-white/80 leading-relaxed mb-8">
            Sign in to your St. Joseph Fish Brokerage account to manage orders and track deliveries.
          </p>

          <div class="flex flex-col gap-3">
            <?php
            $perks = [
              'Order history & tracking',
              'Exclusive member discounts',
              'Faster checkout',
            ];
            foreach ($perks as $perk): ?>
              <div class="flex items-center gap-2.5 text-sm font-semibold text-white/95">
                <svg class="shrink-0 size-4 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/>
                </svg>
                <span><?= $perk ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Decorative wave -->
        <svg class="absolute right-0 top-0 h-full w-16" viewBox="0 0 200 400" preserveAspectRatio="none">
          <path d="M200,0 L200,400 L0,400 Q60,300 30,200 Q0,100 60,0 Z" fill="rgba(255,255,255,0.08)"/>
        </svg>
      </div>

      <!-- ── Right: form panel ── -->
      <div class="flex flex-col bg-white">

        <!-- Close -->
        <div class="flex justify-end px-4 pt-3.5">
          <button type="button"
            class="flex justify-center items-center size-7 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition disabled:opacity-50 disabled:pointer-events-none"
            onclick="closeSignInModal()" title="Close">
            <span class="sr-only">Close</span>
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" d="M18 6 6 18"/><path stroke-linecap="round" d="m6 6 12 12"/>
            </svg>
          </button>
        </div>

        <div class="px-8 pb-8 pt-1 flex-1">
          <div class="mb-6">
            <h3 class="text-xl font-extrabold text-gray-900">Sign In</h3>
            <p class="text-sm text-gray-500 mt-0.5">Enter your credentials to continue.</p>
          </div>

          <!-- Flash error -->
          <?php $hasLoginError = !empty($_SESSION['error_message']); ?>
          <?php if ($hasLoginError): ?>
          <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm font-medium rounded-lg px-4 py-3 mb-5">
            <svg class="shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($_SESSION['error_message']) ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>

          <form action="<?= $baseUrl ?>/functions/checker.php" method="POST" autocomplete="on" id="signin-form" name="login">
            <div class="flex flex-col gap-4">

              <!-- Username -->
              <div>
                <label for="username" class="block text-sm font-semibold text-gray-700 mb-1.5">
                  Username <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                  </span>
                  <input type="text" name="username" id="username"
                    placeholder="Your username"
                    autocomplete="username"
                    required
                    class="py-2.5 pl-10 pr-3 block w-full border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none transition">
                </div>
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                  Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                  </span>
                  <input type="password" id="password" name="password_hash"
                    placeholder="Your password"
                    autocomplete="current-password"
                    required
                    class="py-2.5 pl-10 pr-10 block w-full border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none transition">
                  <button type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition"
                    onclick="togglePasswordVisibility()" title="Toggle password">
                    <svg id="eye-icon" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/>
                      <path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Remember me + Forgot password -->
              <div class="flex items-center justify-between mt-0.5">
                <label for="remember-me" class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                  <input type="checkbox" id="remember-me" name="remember-me"
                    class="shrink-0 size-4 rounded border-gray-300 text-orange-600 focus:ring-orange-400 focus:ring-offset-0 cursor-pointer">
                  Remember me
                </label>
                <a href="/sjfbi-js/forgot_password.php" class="text-sm font-medium text-orange-600 hover:text-orange-700 hover:underline transition">
                  Forgot password?
                </a>
              </div>

              <!-- Submit -->
              <button type="submit"
                class="w-full mt-1 inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl py-3.5 shadow-lg shadow-orange-600/30 hover:shadow-orange-600/40 hover:-translate-y-0.5 active:translate-y-0 transition-all disabled:opacity-50 disabled:pointer-events-none">
                Sign In
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
              </button>

            </div>
          </form>

          <p class="text-center text-sm text-gray-500 mt-5">
            Don't have an account?
            <a href="/sjfbi-js/register.php" class="font-semibold text-orange-600 hover:text-orange-700 hover:underline transition">Sign up here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Minimal custom CSS — only for the overlay/box animation & fallback hidden state,
     since Tailwind's utility classes can't define keyframes without a config extension. -->
<style>
.modal-overlay.hidden { display: none; }
.signin-animate { animation: signinSlideIn .28s cubic-bezier(.22,.61,.36,1) both; }
@keyframes signinSlideIn {
  from { opacity: 0; transform: translateY(-18px) scale(.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes signinSlideOut {
  from { opacity: 1; transform: translateY(0) scale(1); }
  to   { opacity: 0; transform: translateY(-14px) scale(.97); }
}
</style>

<script>
function openSignInModal() {
  document.getElementById('hs-modal-signin').classList.remove('hidden');
  const rememberMe = localStorage.getItem('rememberMeChecked');
  if (rememberMe === 'true') {
    document.getElementById('remember-me').checked = true;
  }
  setTimeout(function () {
    document.getElementById('username').focus();
  }, 180);
}

function closeSignInModal() {
  var overlay = document.getElementById('hs-modal-signin');
  var box     = document.getElementById('signin-white-bg');
  box.style.animation = 'signinSlideOut .2s cubic-bezier(.55,0,.1,1) both';
  setTimeout(function () {
    overlay.classList.add('hidden');
    box.style.animation = '';
  }, 200);
}

document.getElementById('hs-modal-signin').addEventListener('click', function (e) {
  if (e.target === this) closeSignInModal();
});

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeSignInModal();
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

document.addEventListener('DOMContentLoaded', function () {
  var form       = document.getElementById('signin-form');
  var rememberCb = document.getElementById('remember-me');

  if (localStorage.getItem('rememberMeChecked') === 'true' && rememberCb) {
    rememberCb.checked = true;
  }

  if (form) {
    form.addEventListener('submit', function () {
      localStorage.setItem('rememberMeChecked', rememberCb.checked);
    });
  }

  // ── Auto-open modal when checker.php flashed a login error ──────────────
  <?php if ($hasLoginError): ?>
  openSignInModal();
  <?php endif; ?>

  // Clean up "?showModal=true" from the address bar without reloading
  if (window.location.search.includes('showModal=true')) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
</script>