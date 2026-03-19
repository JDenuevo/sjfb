<?php
/**
 * rider/my-profile.php
 * Column renames applied:
 *   riders:   rider_name (was full_name), rider_phone (was contact_number)
 *   accounts: account_first_name, account_last_name, account_email, account_phone
 */
session_start();
require_once '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinasrider']) || $_SESSION['loggedinasrider'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../sign_in.php'); exit;
}
if ($_SESSION['role'] !== 'rider') { header('Location: ../index.php'); exit; }

$rider_account_id = (int)$_SESSION['account_id'];

// ── Fetch full rider + account ─────────────────────────────────────────────
// Uses renamed columns: rider_name, rider_phone, account_first_name/last_name/email/phone
$stmt = $conn->prepare("
    SELECT r.rider_id, r.image,
           r.rider_name,
           r.vehicle_type, r.vehicle_plate_number,
           r.variant_color,
           r.rider_phone      AS contact_number,
           r.organization, r.is_available,
           r.created_at       AS rider_since,
           a.account_first_name AS first_name,
           a.account_last_name  AS last_name,
           a.account_email      AS email,
           a.account_phone      AS phone_number,
           a.username
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.account_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$stmt->bind_param('i', $rider_account_id);
$stmt->execute();
$rider = $stmt->get_result()->fetch_assoc();
if (!$rider) { header('Location: ../index.php'); exit; }

// Re-alias rider_name as full_name for the form field (form still uses full_name as POST key)
$rider['full_name'] = $rider['rider_name'];

$flash = $_SESSION['profile_msg'] ?? null;
unset($_SESSION['profile_msg']);

$v = fn(string $k) => htmlspecialchars($rider[$k] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | SJFBI Rider</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body { font-family: 'Lexend', sans-serif; }
    .field-label { display:block; font-size:.75rem; font-weight:600; color:#6b7280; margin-bottom:.35rem; }
    .field-input { width:100%; font-size:.875rem; border:1.5px solid #e5e7eb; border-radius:.75rem; padding:.625rem .875rem; outline:none; transition:border-color .15s; font-family:'Lexend',sans-serif; color:#1f2937; background:#fff; }
    .field-input:focus { border-color:#f97316; }
    .field-input:disabled { background:#f9fafb; color:#9ca3af; cursor:not-allowed; }
    .field-input.readonly { background:#f9fafb; color:#6b7280; cursor:default; }
    .section-title { font-size:.8rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.07em; margin-bottom:.75rem; }
    #avatar-preview { width:96px;height:96px;border-radius:1.25rem;object-fit:cover;border:3px solid #f3f4f6; }
    .avatar-placeholder { width:96px;height:96px;border-radius:1.25rem;background:#ede9fe;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#7c3aed;border:3px solid #f3f4f6;flex-shrink:0; }
    .avatar-btn { font-size:.7rem;font-weight:600;padding:.35rem .85rem;border-radius:.5rem;border:1.5px solid #e5e7eb;color:#6b7280;cursor:pointer;background:#fff;transition:all .15s; }
    .avatar-btn:hover { border-color:#f97316;color:#f97316; }
    .pw-wrap { position:relative; }
    .pw-wrap input { padding-right:2.5rem; }
    .pw-eye { position:absolute;right:.75rem;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;background:none;border:none;padding:0;display:flex;align-items:center; }
    .pw-eye:hover { color:#f97316; }
    .save-btn { background:#f97316;color:#fff;border:none;border-radius:.875rem;padding:.75rem 2rem;font-size:.875rem;font-weight:700;cursor:pointer;transition:background .15s;font-family:'Lexend',sans-serif;width:100%; }
    .save-btn:hover { background:#ea6c0a; }
    .save-btn:disabled { background:#fdba74;cursor:not-allowed; }
    .flash-success { background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46; }
    .flash-error   { background:#fef2f2;border:1px solid #fca5a5;color:#991b1b; }
    .flash-box { border-radius:1rem;padding:.875rem 1rem;font-size:.85rem;font-weight:500;display:flex;align-items:center;gap:.625rem; }
    .section-divider { border:none;border-top:1.5px solid #f3f4f6;margin:1.25rem 0; }
  </style>
</head>
<body class="bg-gray-50">

<header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
  <div class="flex items-center gap-3">
    <a href="dashboard.php" class="size-8 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
      <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div>
      <div class="text-sm font-bold text-gray-800">My Profile</div>
      <div class="text-xs text-gray-400">Edit your details</div>
    </div>
  </div>
  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $rider['is_available'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
    <span class="size-2 rounded-full inline-block <?= $rider['is_available'] ? 'bg-green-500 animate-pulse' : 'bg-gray-400' ?>"></span>
    <?= $rider['is_available'] ? 'Available' : 'Offline' ?>
  </span>
</header>

<div id="toast-wrap" class="fixed bottom-20 right-4 flex flex-col gap-2 z-[60]"></div>

<div class="max-w-2xl mx-auto px-4 py-5 pb-28 space-y-4">

  <?php if ($flash): ?>
  <div class="flash-box flash-<?= $flash['type'] ?>">
    <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
    <span><?= htmlspecialchars($flash['text']) ?></span>
  </div>
  <?php endif; ?>

  <form method="POST" action="functions/update.php" enctype="multipart/form-data" id="profile-form">

    <!-- Avatar card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-5">
      <div class="flex items-center gap-5">
        <div id="avatar-wrap" class="shrink-0">
          <?php if (!empty($rider['image'])): ?>
          <img id="avatar-preview" src="../<?= $v('image') ?>" alt="Profile photo">
          <?php else: ?>
          <div class="avatar-placeholder" id="avatar-placeholder">
            <?= strtoupper(substr($rider['first_name'],0,1).substr($rider['last_name'],0,1)) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-base font-bold text-gray-800 truncate">
            <?= htmlspecialchars($rider['full_name'] ?: ($rider['first_name'].' '.$rider['last_name'])) ?>
          </p>
          <p class="text-xs text-gray-400 mt-0.5"><?= $v('email') ?></p>
          <?php if (!empty($rider['organization'])): ?>
          <p class="text-xs text-indigo-600 font-medium mt-1"><?= $v('organization') ?></p>
          <?php endif; ?>
          <div class="flex gap-2 mt-2.5 flex-wrap">
            <label class="avatar-btn" for="image-input">
              📷 Change Photo
              <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewAvatar(this)">
            </label>
            <button type="button" onclick="removeAvatar()" id="remove-avatar-btn" class="avatar-btn text-red-400 border-red-200 hover:border-red-400 hover:text-red-500 <?= empty($rider['image']) ? 'hidden' : '' ?>">
              ✕ Remove
            </button>
          </div>
          <p class="text-[10px] text-gray-400 mt-1.5">JPEG, PNG or WEBP — max 5 MB</p>
          <input type="hidden" name="remove_photo" id="remove-photo-flag" value="0">
        </div>
      </div>
    </div>

    <!-- Personal info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-5 space-y-4">
      <p class="section-title">Personal Information</p>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="field-label" for="first_name">First Name <span class="text-red-400">*</span></label>
          <input class="field-input" id="first_name" name="first_name" type="text" value="<?= $v('first_name') ?>" required maxlength="50">
        </div>
        <div>
          <label class="field-label" for="last_name">Last Name <span class="text-red-400">*</span></label>
          <input class="field-input" id="last_name" name="last_name" type="text" value="<?= $v('last_name') ?>" required maxlength="50">
        </div>
      </div>
      <div>
        <label class="field-label" for="full_name">Display Name <span class="text-gray-400">(optional override)</span></label>
        <input class="field-input" id="full_name" name="full_name" type="text" placeholder="Leave blank to use First + Last name" value="<?= $v('full_name') ?>" maxlength="100">
        <p class="text-[11px] text-gray-400 mt-1">This is shown on delivery cards and the admin panel.</p>
      </div>
      <div>
        <label class="field-label" for="phone_number">Phone Number</label>
        <input class="field-input" id="phone_number" name="phone_number" type="tel" value="<?= $v('phone_number') ?>" maxlength="20" placeholder="+63 9XX XXX XXXX">
      </div>
      <hr class="section-divider">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="field-label">Email</label>
          <input class="field-input readonly" type="text" value="<?= $v('email') ?>" disabled>
        </div>
        <div>
          <label class="field-label">Username</label>
          <input class="field-input readonly" type="text" value="<?= $v('username') ?>" disabled>
        </div>
      </div>
      <p class="text-[11px] text-gray-400 -mt-2">Email and username can only be changed by an administrator.</p>
    </div>

    <!-- Vehicle details -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-5 space-y-4">
      <p class="section-title">Vehicle Details</p>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="field-label" for="vehicle_type">Vehicle Type <span class="text-red-400">*</span></label>
          <select class="field-input" id="vehicle_type" name="vehicle_type" required>
            <?php
            $vtypes = ['Motorcycle','Bicycle','E-Bike','Scooter','Tricycle','Van','Truck','Other'];
            foreach ($vtypes as $vt):
              $sel = ($rider['vehicle_type'] === $vt) ? 'selected' : '';
            ?>
            <option value="<?= htmlspecialchars($vt) ?>" <?= $sel ?>><?= htmlspecialchars($vt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field-label" for="vehicle_plate_number">Plate Number <span class="text-red-400">*</span></label>
          <input class="field-input" id="vehicle_plate_number" name="vehicle_plate_number" type="text" value="<?= $v('vehicle_plate_number') ?>" required maxlength="20" placeholder="ABC 1234" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
        </div>
      </div>
      <div>
        <label class="field-label" for="variant_color">Vehicle Color / Variant</label>
        <input class="field-input" id="variant_color" name="variant_color" type="text" value="<?= $v('variant_color') ?>" maxlength="60" placeholder="e.g. Red Honda Click 160">
      </div>
      <div>
        <label class="field-label" for="contact_number">Rider Contact Number <span class="text-red-400">*</span></label>
        <input class="field-input" id="contact_number" name="contact_number" type="tel" value="<?= $v('contact_number') ?>" required maxlength="20" placeholder="+63 9XX XXX XXXX">
        <p class="text-[11px] text-gray-400 mt-1">Shown to admins and customers on the tracking page.</p>
      </div>
      <hr class="section-divider">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="field-label">Organization</label>
          <input class="field-input readonly" type="text" value="<?= $v('organization') ?: 'Not set' ?>" disabled>
        </div>
        <div>
          <label class="field-label">Availability</label>
          <input class="field-input readonly" type="text" value="<?= $rider['is_available'] ? 'Available' : 'Offline' ?>" disabled>
        </div>
      </div>
      <p class="text-[11px] text-gray-400 -mt-2">Organization and availability are managed by your administrator.</p>
    </div>

    <!-- Change password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-5 space-y-4">
      <div class="flex items-center justify-between">
        <p class="section-title mb-0">Change Password</p>
        <span class="text-[11px] text-gray-400">Leave blank to keep current password</span>
      </div>
      <div>
        <label class="field-label" for="new_password">New Password</label>
        <div class="pw-wrap">
          <input class="field-input" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" placeholder="Min. 8 characters">
          <button type="button" class="pw-eye" onclick="togglePw('new_password','eye1')">
            <svg id="eye1" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div>
        <label class="field-label" for="confirm_password">Confirm New Password</label>
        <div class="pw-wrap">
          <input class="field-input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Repeat new password">
          <button type="button" class="pw-eye" onclick="togglePw('confirm_password','eye2')">
            <svg id="eye2" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <p id="pw-match-hint" class="text-[11px] mt-1 hidden"></p>
      </div>
    </div>

    <!-- Save -->
    <div class="pt-1">
      <button type="submit" class="save-btn" id="save-btn">Save Changes</button>
      <p class="text-center text-xs text-gray-400 mt-2">Rider since <?= date('F j, Y', strtotime($rider['rider_since'])) ?></p>
    </div>

  </form>
</div>

<?php include './components/navigation.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script>
function previewAvatar(input) {
  if (!input.files?.[0]) return;
  const file = input.files[0];
  if (file.size > 5 * 1024 * 1024) { showToast('Photo exceeds 5 MB limit.', 'error'); input.value = ''; return; }
  const reader = new FileReader();
  reader.onload = e => {
    const wrap = document.getElementById('avatar-wrap');
    wrap.innerHTML = `<img id="avatar-preview" src="${e.target.result}" alt="Profile photo" style="width:96px;height:96px;border-radius:1.25rem;object-fit:cover;border:3px solid #f3f4f6;">`;
    document.getElementById('remove-avatar-btn').classList.remove('hidden');
    document.getElementById('remove-photo-flag').value = '0';
  };
  reader.readAsDataURL(file);
}

function removeAvatar() {
  const wrap = document.getElementById('avatar-wrap');
  const initials = ((document.getElementById('first_name')?.value?.[0] ?? '?') + (document.getElementById('last_name')?.value?.[0] ?? '?')).toUpperCase();
  wrap.innerHTML = `<div class="avatar-placeholder">${initials}</div>`;
  document.getElementById('image-input').value = '';
  document.getElementById('remove-photo-flag').value = '1';
  document.getElementById('remove-avatar-btn').classList.add('hidden');
}

function togglePw(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const showing = inp.type === 'text';
  inp.type = showing ? 'password' : 'text';
  document.getElementById(iconId).innerHTML = showing
    ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
    : '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
}

const newPwEl   = document.getElementById('new_password');
const confPwEl  = document.getElementById('confirm_password');
const matchHint = document.getElementById('pw-match-hint');
function checkMatch() {
  const np = newPwEl.value, cp = confPwEl.value;
  if (!np && !cp) { matchHint.classList.add('hidden'); return; }
  matchHint.classList.remove('hidden');
  if (np === cp) { matchHint.textContent = '✓ Passwords match'; matchHint.className = 'text-[11px] mt-1 text-green-600'; }
  else           { matchHint.textContent = '✕ Passwords do not match'; matchHint.className = 'text-[11px] mt-1 text-red-500'; }
}
newPwEl.addEventListener('input', checkMatch);
confPwEl.addEventListener('input', checkMatch);

document.getElementById('profile-form').addEventListener('submit', function(e) {
  const np = newPwEl.value, cp = confPwEl.value;
  if (np && np.length < 8) { e.preventDefault(); showToast('New password must be at least 8 characters.', 'error'); newPwEl.focus(); return; }
  if (np && np !== cp)     { e.preventDefault(); showToast('Passwords do not match.', 'error'); confPwEl.focus(); return; }
  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
});

function showToast(msg, type = 'info') {
  const c = { success:'bg-teal-600', error:'bg-red-600', info:'bg-gray-800', warning:'bg-orange-500' };
  const el = document.createElement('div');
  el.className = `${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-start gap-2 min-w-52 max-w-sm`;
  el.innerHTML = `<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none">✕</button>`;
  document.getElementById('toast-wrap').prepend(el);
  setTimeout(() => el?.remove(), 5000);
}

<?php if ($flash): ?>
setTimeout(() => showToast(<?= json_encode($flash['text']) ?>, <?= json_encode($flash['type']) ?>), 300);
<?php endif; ?>
</script>
</body>
</html>