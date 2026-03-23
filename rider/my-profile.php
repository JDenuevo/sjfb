<?php
/**
 * rider/my-profile.php — redesigned
 */
session_start();
require_once '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinasrider']) || $_SESSION['loggedinasrider'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../sign_in.php'); exit;
}
if ($_SESSION['role'] !== 'rider') { header('Location: ../index.php'); exit; }

$rider_account_id = (int)$_SESSION['account_id'];

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

$rider['full_name'] = $rider['rider_name'];
$initials = strtoupper(substr($rider['first_name'],0,1).substr($rider['last_name'],0,1));

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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    :root {
      --or:#f97316; --or-dark:#ea580c; --or-soft:#fff7ed;
      --or-glow:rgba(249,115,22,.15);
      --surface:#ffffff; --sf2:#f8fafc; --border:#e8ecf0;
      --t1:#0f172a; --t2:#64748b; --t3:#94a3b8;
      --green:#22c55e; --radius:1rem;
    }
    *{box-sizing:border-box}
    body{font-family:'Lexend',sans-serif;background:#f1f5f9;min-height:100vh}

    /* Header */
    .rp-header{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:.75rem 1.25rem;display:flex;align-items:center;justify-content:space-between}
    .rp-header-left{display:flex;align-items:center;gap:.75rem}
    .rp-back-btn{width:2.25rem;height:2.25rem;border-radius:.75rem;background:var(--sf2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--t2);text-decoration:none;transition:background .15s,color .15s,transform .15s}
    .rp-back-btn:hover{background:var(--or-soft);color:var(--or);transform:translateX(-2px)}
    .rp-header-title{font-size:.9375rem;font-weight:800;color:var(--t1);letter-spacing:-.02em}
    .rp-header-sub{font-size:.7rem;color:var(--t3);margin-top:.05rem}
    .rp-status-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.3125rem .875rem;border-radius:9999px;font-size:.6875rem;font-weight:700;letter-spacing:.02em}
    .rp-status-pill.available{background:#dcfce7;color:#15803d}
    .rp-status-pill.offline{background:#f1f5f9;color:var(--t3)}
    .rp-status-dot{width:.4375rem;height:.4375rem;border-radius:9999px;flex-shrink:0}
    .available .rp-status-dot{background:var(--green);animation:dotPulse 1.6s ease-in-out infinite}
    .offline .rp-status-dot{background:var(--t3)}
    @keyframes dotPulse{0%,100%{opacity:1}50%{opacity:.4}}

    /* Hero */
    .rp-hero{margin:1rem 1rem 0;border-radius:1.375rem;background:linear-gradient(135deg,#1e293b 0%,#0f172a 60%,#1e1b4b 100%);padding:1.625rem 1.5rem 1.375rem;position:relative;overflow:hidden;animation:fadeUp .32s ease both}
    .rp-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 80% 0%,rgba(249,115,22,.18) 0%,transparent 55%),radial-gradient(ellipse at 20% 100%,rgba(99,102,241,.14) 0%,transparent 50%);pointer-events:none}
    .rp-hero-grid{display:grid;grid-template-columns:auto 1fr;gap:1.125rem;align-items:center;position:relative;z-index:1}
    .rp-avatar-ring{width:5rem;height:5rem;border-radius:1.25rem;padding:3px;background:linear-gradient(135deg,var(--or),#facc15);flex-shrink:0}
    .rp-avatar-inner{width:100%;height:100%;border-radius:calc(1.25rem - 3px);overflow:hidden;background:#1e293b;display:flex;align-items:center;justify-content:center}
    .rp-avatar-inner img{width:100%;height:100%;object-fit:cover}
    .rp-avatar-initials{font-size:1.5rem;font-weight:800;color:var(--or);letter-spacing:-.03em}
    .rp-hero-name{font-size:1.1875rem;font-weight:800;color:#fff;letter-spacing:-.025em;line-height:1.2}
    .rp-hero-email{font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem}
    .rp-hero-org{font-size:.7rem;font-weight:600;color:#fbbf24;margin-top:.3rem;display:inline-flex;align-items:center;gap:.3rem}
    .rp-hero-actions{display:flex;gap:.5rem;margin-top:.875rem;position:relative;z-index:1}
    .rp-photo-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.4375rem .875rem;border-radius:.625rem;font-size:.7rem;font-weight:700;cursor:pointer;transition:all .15s;font-family:'Lexend',sans-serif;border:none}
    .rp-photo-btn.change{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.18)}
    .rp-photo-btn.change:hover{background:rgba(255,255,255,.2)}
    .rp-photo-btn.remove{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.25)}
    .rp-photo-btn.remove:hover{background:rgba(239,68,68,.25)}
    .rp-photo-note{font-size:.65rem;color:rgba(255,255,255,.3);margin-top:.4rem;position:relative;z-index:1}
    .rp-hero-circle1{position:absolute;width:8rem;height:8rem;border-radius:9999px;background:rgba(249,115,22,.08);top:-2rem;right:-2rem;pointer-events:none}
    .rp-hero-circle2{position:absolute;width:5rem;height:5rem;border-radius:9999px;background:rgba(99,102,241,.1);bottom:-1rem;left:30%;pointer-events:none}

    /* Sections */
    .rp-sections{padding:.875rem 1rem 6rem;display:flex;flex-direction:column;gap:.875rem}
    .rp-section{background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.05);transition:box-shadow .2s;animation:fadeUp .32s ease both}
    .rp-section:nth-child(1){animation-delay:.05s}
    .rp-section:nth-child(2){animation-delay:.1s}
    .rp-section:nth-child(3){animation-delay:.15s}
    .rp-section:focus-within{box-shadow:0 0 0 3px var(--or-glow),0 4px 16px rgba(15,23,42,.07)}
    .rp-sec-head{display:flex;align-items:center;gap:.625rem;padding:.875rem 1.125rem .75rem;border-bottom:1px solid #f1f5f9;background:linear-gradient(to bottom,#fafbfc,#fff)}
    .rp-sec-icon{width:1.875rem;height:1.875rem;border-radius:.5625rem;background:var(--or-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .rp-sec-title{font-size:.8125rem;font-weight:800;color:var(--t1);letter-spacing:-.01em}
    .rp-sec-sub{font-size:.6875rem;color:var(--t3);margin-top:.1rem}
    .rp-sec-body{padding:1rem 1.125rem;display:flex;flex-direction:column;gap:.875rem}
    .rp-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
    @media(max-width:400px){.rp-grid-2{grid-template-columns:1fr}}
    .rp-field{display:flex;flex-direction:column;gap:.3rem}
    .rp-label{font-size:.7rem;font-weight:700;color:var(--t2);display:flex;align-items:center;gap:.25rem;text-transform:uppercase;letter-spacing:.05em}
    .rp-label .req{color:var(--or);font-size:.8rem;line-height:1}
    .rp-label .hint{font-size:.65rem;font-weight:500;color:var(--t3);text-transform:none;letter-spacing:0;margin-left:.25rem}
    .rp-input,.rp-select{width:100%;padding:.625rem .875rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;font-family:'Lexend',sans-serif;color:var(--t1);outline:none;transition:border-color .15s,background .15s,box-shadow .15s;appearance:none}
    .rp-input::placeholder{color:var(--t3)}
    .rp-input:focus,.rp-select:focus{border-color:var(--or);background:#fff;box-shadow:0 0 0 3px var(--or-glow)}
    .rp-input.locked{background:#f8fafc;color:var(--t3);border-color:#e8ecf0;cursor:not-allowed}
    .rp-hint{font-size:.6875rem;color:var(--t3);line-height:1.5;padding-left:.125rem}
    .rp-rule{border:none;border-top:1.5px dashed #f1f5f9;margin:.25rem 0}
    .rp-locked-notice{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;background:#f8fafc;border:1px solid #e8ecf0;border-radius:.5rem;margin-top:-.25rem}
    .rp-locked-notice span{font-size:.6875rem;color:var(--t3)}
    .rp-pw-wrap{position:relative}
    .rp-pw-wrap .rp-input{padding-right:2.75rem}
    .rp-pw-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--t3);padding:0;display:flex;align-items:center;transition:color .15s}
    .rp-pw-eye:hover{color:var(--or)}
    .rp-pw-match{font-size:.6875rem;margin-top:.2rem;padding-left:.125rem}
    .rp-pw-match.ok{color:#16a34a}
    .rp-pw-match.err{color:#dc2626}

    /* Save */
    .rp-save-wrap{padding:0 1rem}
    .rp-save-btn{width:100%;padding:.875rem;background:linear-gradient(135deg,var(--or) 0%,#fb923c 100%);color:#fff;border:none;border-radius:var(--radius);font-size:.9375rem;font-weight:800;font-family:'Lexend',sans-serif;cursor:pointer;letter-spacing:-.01em;display:flex;align-items:center;justify-content:center;gap:.5rem;box-shadow:0 4px 16px rgba(249,115,22,.35);transition:all .18s}
    .rp-save-btn:hover{background:linear-gradient(135deg,var(--or-dark) 0%,var(--or) 100%);box-shadow:0 6px 22px rgba(249,115,22,.45);transform:translateY(-1px)}
    .rp-save-btn:active{transform:translateY(0)}
    .rp-save-btn:disabled{background:#fed7aa;box-shadow:none;cursor:not-allowed;transform:none}
    .rp-since{text-align:center;font-size:.7rem;color:var(--t3);margin-top:.625rem;padding-bottom:.25rem}

    /* Flash */
    .rp-flash{margin:.75rem 1rem 0;border-radius:.875rem;padding:.875rem 1rem;font-size:.8125rem;font-weight:500;display:flex;align-items:center;gap:.625rem}
    .rp-flash.success{background:#f0fdf4;border:1px solid #86efac;color:#15803d}
    .rp-flash.error{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c}

    /* Toast */
    #toast-wrap{position:fixed;bottom:5.5rem;right:1.25rem;display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;z-index:9999;pointer-events:none}
    @media(min-width:640px){#toast-wrap{right:1.5rem}}
    .toast{pointer-events:auto;display:flex;align-items:flex-start;gap:.75rem;min-width:230px;max-width:340px;padding:.8rem 1rem;border-radius:.875rem;border-left:4px solid currentColor;background:#fff;box-shadow:0 8px 28px rgba(0,0,0,.12),0 2px 8px rgba(0,0,0,.06);position:relative;overflow:hidden;animation:tIn .28s cubic-bezier(.34,1.4,.64,1) both}
    .toast::after{content:'';position:absolute;bottom:0;left:0;height:2px;width:100%;background:currentColor;opacity:.2;transform-origin:left;animation:tBar 4.5s linear forwards}
    @keyframes tIn{from{opacity:0;transform:translateX(24px) scale(.96)}to{opacity:1;transform:translateX(0) scale(1)}}
    @keyframes tOut{to{opacity:0;transform:translateX(24px) scale(.94);max-height:0;padding:0;margin:0}}
    @keyframes tBar{from{transform:scaleX(1)}to{transform:scaleX(0)}}
    .toast.t-success{color:#16a34a}.toast.t-error{color:#dc2626}.toast.t-info{color:#ea580c}.toast.t-warning{color:#d97706}
    .toast-icon{font-size:1rem;flex-shrink:0;margin-top:.05rem;line-height:1}
    .toast-body{flex:1;min-width:0}
    .toast-title{font-size:.8125rem;font-weight:700;color:#111827;line-height:1.3}
    .toast-msg{font-size:.75rem;color:#6b7280;margin-top:.15rem;line-height:1.4}
    .toast-close{background:none;border:none;padding:0;color:#9ca3af;cursor:pointer;font-size:.875rem;flex-shrink:0;line-height:1;transition:color .1s}
    .toast-close:hover{color:#111827}
    .toast.leaving{animation:tOut .22s ease forwards}

    @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
  </style>
</head>
<body>

<header class="rp-header">
  <div class="rp-header-left">
    <a href="dashboard.php" class="rp-back-btn" aria-label="Back">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <div>
      <p class="rp-header-title">My Profile</p>
      <p class="rp-header-sub">Edit your details</p>
    </div>
  </div>
  <span class="rp-status-pill <?= $rider['is_available'] ? 'available' : 'offline' ?>">
    <span class="rp-status-dot"></span>
    <?= $rider['is_available'] ? 'Available' : 'Offline' ?>
  </span>
</header>

<div id="toast-wrap"></div>

<?php if ($flash): ?>
<div class="rp-flash <?= $flash['type'] ?>">
  <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
  <span><?= htmlspecialchars($flash['text']) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="functions/update.php" enctype="multipart/form-data" id="profile-form">
  <input type="hidden" name="remove_photo" id="remove-photo-flag" value="0">

  <!-- Hero banner -->
  <div class="rp-hero" style="margin-top:<?= $flash ? '.5rem' : '1rem' ?>">
    <div class="rp-hero-circle1"></div>
    <div class="rp-hero-circle2"></div>
    <div class="rp-hero-grid">
      <div class="rp-avatar-ring">
        <div class="rp-avatar-inner" id="avatar-inner">
          <?php if (!empty($rider['image'])): ?>
          <img id="avatar-img" src="../<?= $v('image') ?>" alt="Profile photo">
          <?php else: ?>
          <span class="rp-avatar-initials" id="avatar-initials"><?= $initials ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <p class="rp-hero-name"><?= htmlspecialchars($rider['full_name'] ?: ($rider['first_name'].' '.$rider['last_name'])) ?></p>
        <p class="rp-hero-email"><?= $v('email') ?></p>
        <?php if (!empty($rider['organization'])): ?>
        <p class="rp-hero-org">
          <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <?= $v('organization') ?>
        </p>
        <?php endif; ?>
      </div>
    </div>
    <div class="rp-hero-actions">
      <label class="rp-photo-btn change">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Change Photo
        <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewAvatar(this)">
      </label>
      <button type="button" id="remove-avatar-btn" onclick="removeAvatar()"
              class="rp-photo-btn remove <?= empty($rider['image']) ? 'hidden' : '' ?>">
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        Remove
      </button>
    </div>
    <p class="rp-photo-note">JPEG, PNG or WEBP — max 5 MB</p>
  </div>

  <div class="rp-sections">

    <!-- Personal Information -->
    <div class="rp-section">
      <div class="rp-sec-head">
        <div class="rp-sec-icon">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <p class="rp-sec-title">Personal Information</p>
          <p class="rp-sec-sub">Your name and contact details</p>
        </div>
      </div>
      <div class="rp-sec-body">
        <div class="rp-grid-2">
          <div class="rp-field">
            <label class="rp-label" for="first_name">First Name <span class="req">*</span></label>
            <input class="rp-input" id="first_name" name="first_name" type="text" value="<?= $v('first_name') ?>" required maxlength="50" placeholder="First name">
          </div>
          <div class="rp-field">
            <label class="rp-label" for="last_name">Last Name <span class="req">*</span></label>
            <input class="rp-input" id="last_name" name="last_name" type="text" value="<?= $v('last_name') ?>" required maxlength="50" placeholder="Last name">
          </div>
        </div>
        <div class="rp-field">
          <label class="rp-label" for="full_name">Display Name <span class="hint">(optional override)</span></label>
          <input class="rp-input" id="full_name" name="full_name" type="text" value="<?= $v('full_name') ?>" maxlength="100" placeholder="Leave blank to use First + Last name">
          <p class="rp-hint">Shown on delivery cards and in the admin panel.</p>
        </div>
        <div class="rp-field">
          <label class="rp-label" for="phone_number">Phone Number</label>
          <input class="rp-input" id="phone_number" name="phone_number" type="tel" value="<?= $v('phone_number') ?>" maxlength="20" placeholder="+63 9XX XXX XXXX">
        </div>
        <hr class="rp-rule">
        <div class="rp-grid-2">
          <div class="rp-field">
            <label class="rp-label">Email</label>
            <input class="rp-input locked" type="text" value="<?= $v('email') ?>" disabled>
          </div>
          <div class="rp-field">
            <label class="rp-label">Username</label>
            <input class="rp-input locked" type="text" value="<?= $v('username') ?>" disabled>
          </div>
        </div>
        <div class="rp-locked-notice">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <span>Email and username can only be changed by an administrator.</span>
        </div>
      </div>
    </div>

    <!-- Vehicle Details -->
    <div class="rp-section">
      <div class="rp-sec-head">
        <div class="rp-sec-icon">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div>
          <p class="rp-sec-title">Vehicle Details</p>
          <p class="rp-sec-sub">Your vehicle and rider contact info</p>
        </div>
      </div>
      <div class="rp-sec-body">
        <div class="rp-grid-2">
          <div class="rp-field">
            <label class="rp-label" for="vehicle_type">Vehicle Type <span class="req">*</span></label>
            <select class="rp-input rp-select" id="vehicle_type" name="vehicle_type" required>
              <?php foreach (['Motorcycle','Bicycle','E-Bike','Scooter','Tricycle','Van','Truck','Other'] as $vt): ?>
              <option value="<?= htmlspecialchars($vt) ?>" <?= $rider['vehicle_type']===$vt?'selected':'' ?>><?= htmlspecialchars($vt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="rp-field">
            <label class="rp-label" for="vehicle_plate_number">Plate Number <span class="req">*</span></label>
            <input class="rp-input" id="vehicle_plate_number" name="vehicle_plate_number" type="text" value="<?= $v('vehicle_plate_number') ?>" required maxlength="20" placeholder="ABC 1234" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
          </div>
        </div>
        <div class="rp-field">
          <label class="rp-label" for="variant_color">Vehicle Color / Variant</label>
          <input class="rp-input" id="variant_color" name="variant_color" type="text" value="<?= $v('variant_color') ?>" maxlength="60" placeholder="e.g. Red Honda Click 160">
        </div>
        <div class="rp-field">
          <label class="rp-label" for="contact_number">Rider Contact Number <span class="req">*</span></label>
          <input class="rp-input" id="contact_number" name="contact_number" type="tel" value="<?= $v('contact_number') ?>" required maxlength="20" placeholder="+63 9XX XXX XXXX">
          <p class="rp-hint">Shown to admins and customers on the tracking page.</p>
        </div>
        <hr class="rp-rule">
        <div class="rp-grid-2">
          <div class="rp-field">
            <label class="rp-label">Organization</label>
            <input class="rp-input locked" type="text" value="<?= $v('organization') ?: 'Not set' ?>" disabled>
          </div>
          <div class="rp-field">
            <label class="rp-label">Availability</label>
            <input class="rp-input locked" type="text" value="<?= $rider['is_available'] ? 'Available' : 'Offline' ?>" disabled>
          </div>
        </div>
        <div class="rp-locked-notice">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <span>Organization and availability are managed by your administrator.</span>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="rp-section">
      <div class="rp-sec-head">
        <div class="rp-sec-icon">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div>
          <p class="rp-sec-title">Change Password</p>
          <p class="rp-sec-sub">Leave blank to keep your current password</p>
        </div>
      </div>
      <div class="rp-sec-body">
        <div class="rp-field">
          <label class="rp-label" for="new_password">New Password</label>
          <div class="rp-pw-wrap">
            <input class="rp-input" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" placeholder="Min. 8 characters">
            <button type="button" class="rp-pw-eye" onclick="togglePw('new_password','eye1')">
              <svg id="eye1" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <div class="rp-field">
          <label class="rp-label" for="confirm_password">Confirm New Password</label>
          <div class="rp-pw-wrap">
            <input class="rp-input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Repeat new password">
            <button type="button" class="rp-pw-eye" onclick="togglePw('confirm_password','eye2')">
              <svg id="eye2" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <p id="pw-match-hint" class="rp-pw-match hidden"></p>
        </div>
      </div>
    </div>

    <!-- Save -->
    <div class="rp-save-wrap">
      <button type="submit" class="rp-save-btn" id="save-btn">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Changes
      </button>
      <p class="rp-since">Rider since <?= date('F j, Y', strtotime($rider['rider_since'])) ?></p>
    </div>

  </div>
</form>

<?php include './components/navigation.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script>
var _TOAST_META = {
  success:{icon:'\u2713',title:'Success',cls:'t-success'},
  error:{icon:'\u2715',title:'Error',cls:'t-error'},
  info:{icon:'\u2139',title:'Notice',cls:'t-info'},
  warning:{icon:'\u26a0',title:'Warning',cls:'t-warning'},
};
function showToast(msg,type,title){
  type=type||'info';var m=_TOAST_META[type]||_TOAST_META.info;title=title||m.title;
  var wrap=document.getElementById('toast-wrap');if(!wrap)return;
  var t=document.createElement('div');t.className='toast '+m.cls;
  t.innerHTML='<span class="toast-icon">'+m.icon+'</span><div class="toast-body"><p class="toast-title">'+_escT(title)+'</p><p class="toast-msg">'+msg+'</p></div><button class="toast-close" aria-label="Dismiss">\u2715</button>';
  t.querySelector('.toast-close').addEventListener('click',function(){_dismissToast(t);});
  wrap.appendChild(t);t._timer=setTimeout(function(){_dismissToast(t);},4500);
}
function _dismissToast(el){if(!el||el._gone)return;el._gone=true;clearTimeout(el._timer);el.classList.add('leaving');el.addEventListener('animationend',function(){el.remove();},{once:true});}
function _escT(v){return v==null?'':String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function toast(msg,type){showToast(msg,type);}

function previewAvatar(input){
  if(!input.files?.[0])return;
  var file=input.files[0];
  if(file.size>5*1024*1024){showToast('Photo exceeds 5 MB limit.','error');input.value='';return;}
  var reader=new FileReader();
  reader.onload=function(e){
    var inner=document.getElementById('avatar-inner');
    inner.innerHTML='<img id="avatar-img" src="'+e.target.result+'" alt="Profile photo" style="width:100%;height:100%;object-fit:cover">';
    document.getElementById('remove-avatar-btn').classList.remove('hidden');
    document.getElementById('remove-photo-flag').value='0';
  };
  reader.readAsDataURL(file);
}
function removeAvatar(){
  var fn=(document.getElementById('first_name')?.value?.[0]??'?');
  var ln=(document.getElementById('last_name')?.value?.[0]??'?');
  document.getElementById('avatar-inner').innerHTML='<span class="rp-avatar-initials">'+(fn+ln).toUpperCase()+'</span>';
  document.getElementById('image-input').value='';
  document.getElementById('remove-photo-flag').value='1';
  document.getElementById('remove-avatar-btn').classList.add('hidden');
}
function togglePw(inputId,iconId){
  var inp=document.getElementById(inputId);var showing=inp.type==='text';
  inp.type=showing?'password':'text';
  document.getElementById(iconId).innerHTML=showing
    ?'<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
    :'<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
}
var newPwEl=document.getElementById('new_password');
var confPwEl=document.getElementById('confirm_password');
var hint=document.getElementById('pw-match-hint');
function checkMatch(){
  var np=newPwEl.value,cp=confPwEl.value;
  if(!np&&!cp){hint.classList.add('hidden');return;}
  hint.classList.remove('hidden');
  if(np===cp){hint.textContent='\u2713 Passwords match';hint.className='rp-pw-match ok';}
  else{hint.textContent='\u2715 Passwords do not match';hint.className='rp-pw-match err';}
}
newPwEl.addEventListener('input',checkMatch);
confPwEl.addEventListener('input',checkMatch);
document.getElementById('profile-form').addEventListener('submit',function(e){
  var np=newPwEl.value,cp=confPwEl.value;
  if(np&&np.length<8){e.preventDefault();showToast('New password must be at least 8 characters.','error');newPwEl.focus();return;}
  if(np&&np!==cp){e.preventDefault();showToast('Passwords do not match.','error');confPwEl.focus();return;}
  var btn=document.getElementById('save-btn');btn.disabled=true;
  btn.innerHTML='<svg class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path fill="currentColor" opacity=".75" d="M4 12a8 8 0 018-8v8z"/></svg> Saving\u2026';
});
<?php if($flash): ?>
setTimeout(function(){showToast(<?=json_encode($flash['text'])?>,<?=json_encode($flash['type'])?>);},300);
<?php endif; ?>
</script>
</body>
</html>