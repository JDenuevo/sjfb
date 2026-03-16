<?php
/**
 * rider/functions/update.php
 *
 * Handles self-profile update for the logged-in rider.
 * Called via POST from rider/my-profile.php.
 *
 * Editable fields:
 *   riders table  : full_name, vehicle_type, vehicle_plate_number, variant_color, contact_number, image
 *   accounts table: first_name, last_name, phone_number
 *   accounts table: password (optional — only if new_password is provided)
 *
 * NOT editable here: organization, is_available (admin-only)
 *
 * Upload path: sjfbi-js/uploads/riders/
 * Stored DB path (no leading slash): uploads/riders/filename.ext
 */
session_start();
require_once '../../conn.php';

// ── Auth ───────────────────────────────────────────────────────────────────
if (!isset($_SESSION['loggedinasrider']) || $_SESSION['loggedinasrider'] !== true
    || !isset($_SESSION['account_id']) || $_SESSION['role'] !== 'rider') {
    header('Location: ../../sign_in.php');
    exit;
}

$rider_account_id = (int)$_SESSION['account_id'];

// ── Only handle POST ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../my-profile.php');
    exit;
}

// ── Fetch rider + account rows (for fallbacks and checks) ─────────────────
$fetchStmt = $conn->prepare("
    SELECT r.rider_id, r.image, r.full_name, r.vehicle_type,
           r.vehicle_plate_number, r.variant_color, r.contact_number,
           r.organization, r.is_available,
           a.first_name, a.last_name, a.email, a.phone_number, a.password_hash
    FROM riders r
    JOIN accounts a ON a.account_id = r.account_id
    WHERE r.account_id = ? AND r.is_deleted = 0
    LIMIT 1
");
$fetchStmt->bind_param('i', $rider_account_id);
$fetchStmt->execute();
$current = $fetchStmt->get_result()->fetch_assoc();

if (!$current) {
    $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Rider record not found.'];
    header('Location: ../my-profile.php');
    exit;
}

$rider_id = (int)$current['rider_id'];

// ── Sanitize inputs ────────────────────────────────────────────────────────
$first_name    = trim($_POST['first_name']           ?? '');
$last_name     = trim($_POST['last_name']            ?? '');
$phone_number  = trim($_POST['phone_number']         ?? '');
$full_name     = trim($_POST['full_name']            ?? '');
$vehicle_type  = trim($_POST['vehicle_type']         ?? '');
$plate         = trim($_POST['vehicle_plate_number'] ?? '');
$variant_color = trim($_POST['variant_color']        ?? '');
$contact       = trim($_POST['contact_number']       ?? '');
$new_password  = $_POST['new_password']              ?? '';
$confirm_pw    = $_POST['confirm_password']          ?? '';

// ── Validate required fields ───────────────────────────────────────────────
if (!$first_name || !$last_name || !$vehicle_type || !$plate || !$contact) {
    $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'First name, last name, vehicle type, plate number, and contact are required.'];
    header('Location: ../my-profile.php');
    exit;
}

// ── Password change (optional) ─────────────────────────────────────────────
$newHashedPw = null;
if ($new_password !== '') {
    if (strlen($new_password) < 8) {
        $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'New password must be at least 8 characters.'];
        header('Location: ../my-profile.php');
        exit;
    }
    if ($new_password !== $confirm_pw) {
        $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Passwords do not match.'];
        header('Location: ../my-profile.php');
        exit;
    }
    $newHashedPw = password_hash($new_password, PASSWORD_DEFAULT);
}

// ── Handle photo upload ────────────────────────────────────────────────────
$image_path = $current['image']; // keep existing by default

if (!empty($_FILES['image']['tmp_name'])) {
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Photo must be JPEG, PNG, or WEBP.'];
        header('Location: ../my-profile.php');
        exit;
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Photo must be under 5 MB.'];
        header('Location: ../my-profile.php');
        exit;
    }

    $ext   = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
    $fname = 'rider_' . $rider_account_id . '_' . time() . '.' . $ext;

    // Path relative to sjfbi-js/: uploads/riders/
    // __DIR__ is rider/functions/ → go up two levels to sjfbi-js/
    $dir = __DIR__ . '/../../uploads/riders/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
        // Delete old photo if it exists and is not the default
        if (!empty($current['image']) && file_exists(__DIR__ . '/../../' . $current['image'])) {
            @unlink(__DIR__ . '/../../' . $current['image']);
        }
        $image_path = 'uploads/riders/' . $fname;
    } else {
        $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Failed to save photo. Check folder permissions.'];
        header('Location: ../my-profile.php');
        exit;
    }
}

// ── Update riders table ────────────────────────────────────────────────────
$fnVal = $full_name ?: null;

$rStmt = $conn->prepare("
    UPDATE riders
    SET image = ?, full_name = ?, vehicle_type = ?,
        vehicle_plate_number = ?, variant_color = ?,
        contact_number = ?, updated_at = NOW()
    WHERE rider_id = ?
");
$rStmt->bind_param('ssssssi',
    $image_path, $fnVal,
    $vehicle_type, $plate,
    $variant_color, $contact,
    $rider_id
);

if (!$rStmt->execute()) {
    $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Failed to update rider details.'];
    header('Location: ../my-profile.php');
    exit;
}

// ── Update accounts table ──────────────────────────────────────────────────
if ($newHashedPw) {
    $aStmt = $conn->prepare("
        UPDATE accounts
        SET first_name = ?, last_name = ?, phone_number = ?, password_hash = ?
        WHERE account_id = ?
    ");
    $aStmt->bind_param('ssssi', $first_name, $last_name, $phone_number, $newHashedPw, $rider_account_id);
} else {
    $aStmt = $conn->prepare("
        UPDATE accounts
        SET first_name = ?, last_name = ?, phone_number = ?
        WHERE account_id = ?
    ");
    $aStmt->bind_param('sssi', $first_name, $last_name, $phone_number, $rider_account_id);
}

if (!$aStmt->execute()) {
    $_SESSION['profile_msg'] = ['type' => 'error', 'text' => 'Failed to update account details.'];
    header('Location: ../my-profile.php');
    exit;
}

// ── Done ───────────────────────────────────────────────────────────────────
$_SESSION['profile_msg'] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
header('Location: ../my-profile.php');
exit;