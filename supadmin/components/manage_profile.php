<?php
/**
 * supadmin/components/manage_profile.php
 * Included by my-profile.php — expects $row from accounts query.
 */

// Initials for avatar
$firstName = $row['account_first_name'] ?? '';
$lastName  = $row['account_last_name']  ?? '';
$initials  = strtoupper(
    substr($firstName, 0, 1) . substr($lastName, 0, 1)
) ?: strtoupper(substr($row['username'] ?? '?', 0, 2));

$roleLabel = ucwords(str_replace('_', ' ', $row['role'] ?? ''));
$memberSince = !empty($row['created_at']) ? date('F j, Y', strtotime($row['created_at'])) : '—';
?>

<style>
  /* ── Profile page ────────────────────────────────────────── */
  :root {
    --or:      #f97316;
    --or-dark: #ea580c;
    --or-soft: #fff7ed;
    --or-glow: rgba(249,115,22,.14);
    --border:  #f0f0f0;
    --t1:      #111827;
    --t2:      #6b7280;
    --t3:      #9ca3af;
    --sf:      #ffffff;
    --sf2:     #fafafa;
  }

  .pf-page-title   { font-size:1.375rem; font-weight:800; color:var(--t1); letter-spacing:-.02em; }
  .pf-page-sub     { font-size:.8125rem; color:var(--t2); margin-top:.2rem; }

  /* ── Card shell ── */
  .pf-card {
    background:var(--sf);
    border:1px solid var(--border);
    border-radius:1.25rem;
    overflow:hidden;
  }
  .pf-card-header {
    padding:1rem 1.375rem;
    border-bottom:1px solid var(--border);
    background:var(--sf2);
    display:flex; align-items:center; gap:.75rem;
  }
  .pf-card-header-icon {
    width:2rem; height:2rem; border-radius:.625rem;
    background:var(--or-soft);
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .pf-card-header h3 { font-size:.9375rem; font-weight:700; color:var(--t1); }
  .pf-card-header p  { font-size:.75rem; color:var(--t2); margin-top:.1rem; }

  /* ── Left sidebar ── */
  .pf-sidebar { padding:1.75rem 1.375rem; display:flex; flex-direction:column; align-items:center; gap:0; }

  /* Avatar */
  .pf-avatar-wrap { position:relative; margin-bottom:1rem; }
  .pf-avatar {
    width:84px; height:84px; border-radius:9999px;
    background:linear-gradient(135deg, var(--or), #fb923c);
    display:flex; align-items:center; justify-content:center;
    font-size:1.625rem; font-weight:800; color:#fff; letter-spacing:-.02em;
    box-shadow:0 8px 24px rgba(249,115,22,.32);
    flex-shrink:0;
  }
  .pf-avatar-ring {
    position:absolute; inset:-4px; border-radius:9999px;
    border:2px dashed rgba(249,115,22,.3);
    animation:spin 14s linear infinite;
  }
  @keyframes spin { to { transform:rotate(360deg); } }

  .pf-name   { font-size:1.0625rem; font-weight:700; color:var(--t1); text-align:center; }
  .pf-role-badge {
    display:inline-flex; align-items:center;
    padding:.25rem .875rem; border-radius:9999px;
    background:var(--or); color:#fff;
    font-size:.6875rem; font-weight:700; letter-spacing:.02em; text-transform:uppercase;
    margin:.5rem 0 1.25rem;
  }

  /* Info rows */
  .pf-info-rows { width:100%; border-top:1px solid var(--border); padding-top:1.25rem; }
  .pf-info-row {
    display:flex; align-items:flex-start; gap:.75rem;
    padding:.625rem 0;
    border-bottom:1px solid var(--border);
  }
  .pf-info-row:last-child { border-bottom:none; }
  .pf-info-icon {
    width:1.625rem; height:1.625rem; border-radius:.5rem;
    background:var(--or-soft); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; margin-top:.05rem;
  }
  .pf-info-label { font-size:.7rem; color:var(--t3); font-weight:500; text-transform:uppercase; letter-spacing:.04em; }
  .pf-info-value { font-size:.8125rem; font-weight:600; color:var(--t1); margin-top:.1rem; word-break:break-word; }

  /* Member since chip */
  .pf-since-chip {
    display:flex; align-items:center; gap:.5rem;
    margin-top:1.25rem; padding:.625rem 1rem;
    background:var(--or-soft); border-radius:.75rem;
    border:1px dashed rgba(249,115,22,.25); width:100%;
  }
  .pf-since-chip span:first-child { font-size:.7rem; color:var(--t2); font-weight:500; }
  .pf-since-chip span:last-child  { font-size:.8rem; font-weight:700; color:var(--or-dark); margin-top:.1rem; display:block; }

  /* ── Form panel ── */
  .pf-form-body { padding:1.5rem; }

  .pf-section-label {
    font-size:.6875rem; font-weight:700; color:var(--t3);
    letter-spacing:.08em; text-transform:uppercase;
    padding-left:.625rem;
    border-left:2px solid var(--or);
    margin:0 0 1rem;
  }
  .pf-section { margin-bottom:1.75rem; }
  .pf-section:last-of-type { margin-bottom:0; }

  /* Inputs */
  .pf-field { display:flex; flex-direction:column; gap:.35rem; }
  .pf-label {
    font-size:.75rem; font-weight:600; color:#374151;
    display:flex; align-items:center; gap:.2rem;
  }
  .pf-label .req { color:var(--or); font-size:.875rem; line-height:1; }
  .pf-label .hint { font-size:.7rem; color:var(--t3); font-weight:400; margin-left:.25rem; }

  .pf-input, .pf-textarea {
    width:100%;
    padding:.5625rem .875rem;
    border:1.5px solid #e5e7eb;
    border-radius:.625rem;
    font-size:.875rem; font-family:inherit;
    color:var(--t1);
    background:#fafafa;
    outline:none;
    transition:border-color .15s, box-shadow .15s, background .15s;
  }
  .pf-textarea { resize:none; }
  .pf-input::placeholder, .pf-textarea::placeholder { color:var(--t3); }
  .pf-input:focus, .pf-textarea:focus {
    border-color:var(--or); background:#fff;
    box-shadow:0 0 0 3px var(--or-glow);
  }
  .pf-input[readonly], .pf-input[disabled] {
    background:#f3f4f6; color:var(--t3); cursor:not-allowed;
    border-color:#e5e7eb;
  }

  /* Password wrapper with show/hide toggle */
  .pf-pw-wrap { position:relative; }
  .pf-pw-wrap .pf-input { padding-right:2.75rem; }
  .pf-pw-toggle {
    position:absolute; right:.75rem; top:50%; transform:translateY(-50%);
    background:none; border:none; cursor:pointer; color:var(--t3); padding:0; line-height:1;
    transition:color .15s;
  }
  .pf-pw-toggle:hover { color:var(--or); }

  /* Grid */
  .pf-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.875rem; }
  @media(max-width:540px){ .pf-grid-2 { grid-template-columns:1fr; } }

  /* Divider */
  .pf-divider { border:none; border-top:1px solid var(--border); margin:1.5rem 0; }

  /* Footer actions */
  .pf-footer {
    padding:1rem 1.5rem;
    border-top:1px solid var(--border);
    background:var(--sf2);
    display:flex; align-items:center; justify-content:flex-end; gap:.5rem;
  }
  .pf-btn-cancel {
    padding:.5rem 1.25rem;
    background:transparent; color:var(--t2);
    border:1.5px solid #e5e7eb; border-radius:.625rem;
    font-size:.8125rem; font-weight:600; font-family:inherit;
    cursor:pointer; transition:all .15s; text-decoration:none;
    display:inline-flex; align-items:center; gap:.375rem;
  }
  .pf-btn-cancel:hover { background:#f9fafb; color:var(--t1); border-color:#d1d5db; }

  .pf-btn-save {
    padding:.5rem 1.375rem;
    background:var(--or); color:#fff;
    border:1.5px solid var(--or); border-radius:.625rem;
    font-size:.8125rem; font-weight:700; font-family:inherit;
    cursor:pointer; transition:all .15s;
    display:inline-flex; align-items:center; gap:.375rem;
    box-shadow:0 2px 8px rgba(249,115,22,.25);
  }
  .pf-btn-save:hover  { background:var(--or-dark); border-color:var(--or-dark); box-shadow:0 4px 14px rgba(249,115,22,.35); transform:translateY(-1px); }
  .pf-btn-save:active { transform:translateY(0); }

  /* Alert */
  .pf-alert {
    display:flex; align-items:center; gap:.625rem;
    padding:.875rem 1.125rem; border-radius:.875rem;
    font-size:.8125rem; font-weight:500; margin-bottom:1.25rem;
  }
  .pf-alert-success { background:#14b8a6; color:#fff; }
  .pf-alert-error   { background:#ef4444; color:#fff; }

  /* Two-col layout */
  .pf-layout {
    display:grid;
    grid-template-columns:260px 1fr;
    gap:1.25rem;
    align-items:start;
  }
  @media(max-width:767px){
    .pf-layout { grid-template-columns:1fr; }
    .pf-grid-2 { grid-template-columns:1fr; }
  }
</style>

<!-- Page title -->
<div class="mb-5">
  <h1 class="pf-page-title">My Profile</h1>
  <p class="pf-page-sub">Manage your account information and password</p>
</div>

<?php if (!empty($_SESSION['message'])):
  $msg = $_SESSION['message']; unset($_SESSION['message']);
  $alertCls = $msg['type'] === 'success' ? 'pf-alert-success' : 'pf-alert-error';
?>
<div class="pf-alert <?= $alertCls ?>">
  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
    <?php if ($msg['type'] === 'success'): ?>
    <path d="M20 6 9 17l-5-5"/>
    <?php else: ?>
    <circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>
    <?php endif; ?>
  </svg>
  <span><strong><?= ucfirst($msg['type']) ?>!</strong> <?= htmlspecialchars($msg['text']) ?></span>
</div>
<?php endif; ?>

<div class="pf-layout">

  <!-- ══════════════════════════════════════════
       LEFT SIDEBAR — Profile card
  ══════════════════════════════════════════ -->
  <div class="pf-card">
    <div class="pf-card-header">
      <div class="pf-card-header-icon">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <div>
        <h3>Profile</h3>
      </div>
    </div>

    <div class="pf-sidebar">

      <!-- Avatar -->
      <div class="pf-avatar-wrap">
        <div class="pf-avatar-ring"></div>
        <div class="pf-avatar"><?= $initials ?></div>
      </div>

      <!-- Name + role -->
      <p class="pf-name"><?= htmlspecialchars(trim("$firstName $lastName") ?: $row['username']) ?></p>
      <span class="pf-role-badge"><?= $roleLabel ?></span>

      <!-- Info rows -->
      <div class="pf-info-rows">

        <div class="pf-info-row">
          <div class="pf-info-icon">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <div>
            <p class="pf-info-label">Username</p>
            <p class="pf-info-value"><?= htmlspecialchars($row['username']) ?></p>
          </div>
        </div>

        <div class="pf-info-row">
          <div class="pf-info-icon">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div style="min-width:0">
            <p class="pf-info-label">Email</p>
            <p class="pf-info-value"><?= htmlspecialchars($row['account_email'] ?? '—') ?></p>
          </div>
        </div>

        <?php if (!empty($row['account_phone'])): ?>
        <div class="pf-info-row">
          <div class="pf-info-icon">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.69 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91A16 16 0 0 0 14 15.91l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </div>
          <div>
            <p class="pf-info-label">Phone</p>
            <p class="pf-info-value"><?= htmlspecialchars($row['account_phone']) ?></p>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($row['city'])): ?>
        <div class="pf-info-row">
          <div class="pf-info-icon">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <div>
            <p class="pf-info-label">City</p>
            <p class="pf-info-value"><?= htmlspecialchars($row['city']) ?></p>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /info rows -->

      <!-- Member since -->
      <div class="pf-since-chip">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <div>
          <span>Member Since</span>
          <span><?= $memberSince ?></span>
        </div>
      </div>

    </div>
  </div>

  <!-- ══════════════════════════════════════════
       RIGHT PANEL — Edit form
  ══════════════════════════════════════════ -->
  <div class="pf-card">
    <div class="pf-card-header">
      <div class="pf-card-header-icon">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
      </div>
      <div>
        <h3>Edit Information</h3>
        <p>Update your personal details and security settings</p>
      </div>
    </div>

    <form action="./functions/update.php" method="POST">
      <div class="pf-form-body">

        <!-- ── Basic Information ── -->
        <div class="pf-section">
          <p class="pf-section-label">Basic Information</p>
          <div class="pf-grid-2">
            <div class="pf-field">
              <label class="pf-label">First Name <span class="req">*</span></label>
              <input type="text" name="account_first_name" class="pf-input"
                     value="<?= htmlspecialchars($row['account_first_name'] ?? '') ?>"
                     placeholder="First name">
            </div>
            <div class="pf-field">
              <label class="pf-label">Last Name <span class="req">*</span></label>
              <input type="text" name="account_last_name" class="pf-input"
                     value="<?= htmlspecialchars($row['account_last_name'] ?? '') ?>"
                     placeholder="Last name">
            </div>
          </div>

          <div class="pf-grid-2" style="margin-top:.875rem">
            <div class="pf-field">
              <label class="pf-label">Username <span class="req">*</span></label>
              <input type="text" name="username" class="pf-input"
                     value="<?= htmlspecialchars($row['username']) ?>"
                     placeholder="Username">
            </div>
            <div class="pf-field">
              <label class="pf-label">Email <span class="req">*</span></label>
              <input type="email" name="account_email" class="pf-input"
                     value="<?= htmlspecialchars($row['account_email'] ?? '') ?>"
                     placeholder="you@example.com">
            </div>
          </div>

          <div class="pf-grid-2" style="margin-top:.875rem">
            <div class="pf-field">
              <label class="pf-label">Phone Number</label>
              <input type="text" name="account_phone" class="pf-input"
                     value="<?= htmlspecialchars($row['account_phone'] ?? '') ?>"
                     placeholder="+63 xxx xxx xxxx">
            </div>
            <div class="pf-field">
              <label class="pf-label">Role</label>
              <input type="text" class="pf-input"
                     value="<?= $roleLabel ?>" readonly disabled>
            </div>
          </div>
        </div>

        <hr class="pf-divider">

        <!-- ── Change Password ── -->
        <div class="pf-section">
          <p class="pf-section-label">Change Password</p>
          <div class="pf-grid-2">
            <div class="pf-field">
              <label class="pf-label">
                New Password
                <span class="hint">(leave blank to keep)</span>
              </label>
              <div class="pf-pw-wrap">
                <input type="password" name="password" id="pf-pw" class="pf-input"
                       placeholder="••••••••" autocomplete="new-password">
                <button type="button" class="pf-pw-toggle" onclick="togglePw('pf-pw','pf-eye-1')" aria-label="Toggle password">
                  <svg id="pf-eye-1" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
              <p style="font-size:.7rem;color:var(--t3);margin-top:.25rem">Minimum 6 characters</p>
            </div>
            <div class="pf-field">
              <label class="pf-label">Confirm New Password</label>
              <div class="pf-pw-wrap">
                <input type="password" name="confirm_password" id="pf-cpw" class="pf-input"
                       placeholder="••••••••" autocomplete="new-password">
                <button type="button" class="pf-pw-toggle" onclick="togglePw('pf-cpw','pf-eye-2')" aria-label="Toggle password">
                  <svg id="pf-eye-2" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <hr class="pf-divider">

        <!-- ── Address ── -->
        <div class="pf-section">
          <p class="pf-section-label">Address</p>
          <div class="pf-field" style="margin-bottom:.875rem">
            <label class="pf-label">Street Address</label>
            <textarea name="account_address" rows="2" class="pf-textarea pf-input"
                      placeholder="House no., Street, Barangay"><?= htmlspecialchars($row['account_address'] ?? '') ?></textarea>
          </div>
          <div class="pf-grid-2">
            <div class="pf-field">
              <label class="pf-label">City</label>
              <input type="text" name="city" class="pf-input"
                     value="<?= htmlspecialchars($row['city'] ?? '') ?>"
                     placeholder="City / Municipality">
            </div>
            <div class="pf-field">
              <label class="pf-label">Postal Code</label>
              <input type="text" name="postal_code" class="pf-input"
                     value="<?= htmlspecialchars($row['postal_code'] ?? '') ?>"
                     placeholder="XXXX">
            </div>
          </div>
        </div>

      </div><!-- /form body -->

      <!-- Footer actions -->
      <div class="pf-footer">
        <button type="reset" class="pf-btn-cancel">
          Cancel
        </button>
        <button type="submit" name="update_profile" class="pf-btn-save">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path d="M20 6 9 17l-5-5"/>
          </svg>
          Save Changes
        </button>
      </div>
    </form>
  </div>

</div><!-- /pf-layout -->

<script>
/* ── Password visibility toggle ── */
function togglePw(inputId, iconId) {
  var inp  = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  if (!inp) return;
  var isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  icon.innerHTML = isText
    ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
    : '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>' +
      '<path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>' +
      '<line x1="1" y1="1" x2="23" y2="23"/>';
}

/* ── Password match validation ── */
document.querySelector('form').addEventListener('submit', function(e) {
  var pw  = document.getElementById('pf-pw').value;
  var cpw = document.getElementById('pf-cpw').value;
  if ((pw || cpw) && pw !== cpw) {
    e.preventDefault();
    // Use existing showToast if available, otherwise alert
    if (typeof showToast === 'function') {
      showToast('Passwords do not match.', 'error');
    } else {
      alert('Passwords do not match.');
    }
    document.getElementById('pf-cpw').focus();
  }
});
</script>