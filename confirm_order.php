<?php
/**
 * confirm_order.php
 *
 * Called when customer clicks:
 *  - "Confirm Order"    (from email #1 — order confirmation)
 *  - "Order Received"   (from email #5 — delivery)
 *  - "Order Picked-Up"  (from email #4 — pickup ready)
 *
 * Query params: ?order_id=X&token=Y&type=confirm|delivery|pickup
 *
 * On valid token:
 *   1. Sets recipient_confirmed_at = NOW()
 *   2. Logs to order_status_history
 *   3. Sends email_order_completed() with review link
 *   4. Redirects to review.php
 *
 * On expired/invalid: shows a clean error page with track link.
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/supadmin/functions/email_helper.php';

date_default_timezone_set('Asia/Manila');

$order_id = (int)($_GET['order_id'] ?? 0);
$token    = trim($_GET['token']    ?? '');
$type     = trim($_GET['type']     ?? 'confirm'); // confirm | delivery | pickup

// ── helpers ──────────────────────────────────────────────────────────────────
function render_confirm_page(string $status, string $title, string $message, string $icon, string $order_code = '', string $review_url = '', bool $is_pickup = false): void {
    $track_url  = $order_code
        ? htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/track.php?order_code=' . urlencode($order_code))
        : '#';
    $accent     = $status === 'success' ? '#16a34a' : ($status === 'error' ? '#dc2626' : '#f97316');
    $review_btn = ($status === 'success' && $review_url)
        ? '<a href="' . htmlspecialchars($review_url) . '" style="display:inline-block;margin-top:12px;background:#f59e0b;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none">⭐ Leave a Review</a>'
        : '';
    $track_btn  = $order_code
        ? '<a href="' . $track_url . '" style="display:inline-block;margin-top:12px;background:#f9fafb;color:#374151;border:1.5px solid #e5e7eb;font-size:14px;font-weight:600;padding:12px 28px;border-radius:10px;text-decoration:none">📦 Track My Order</a>'
        : '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?> | SJFBI</title>
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <link rel="shortcut icon" href="./assets/icons/logo.ico"><link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">

  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Lexend',Helvetica,Arial,sans-serif;background:#f4f4f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{background:#fff;border-radius:20px;max-width:480px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,.10);overflow:hidden;text-align:center}
    .card-top{background:<?= $accent ?>;padding:40px 32px 28px;color:#fff}
    .card-top .icon{font-size:56px;margin-bottom:16px;display:block}
    .card-top h1{font-size:22px;font-weight:800;margin-bottom:6px}
    .card-top p{font-size:14px;opacity:.88;line-height:1.55}
    .card-body{padding:28px 32px}
    .card-body p{font-size:14px;color:#6b7280;line-height:1.65;margin-bottom:16px}
    .order-badge{display:inline-block;background:#fff7ed;color:#ea580c;font-size:13px;font-weight:700;padding:6px 16px;border-radius:9999px;border:1.5px solid #fed7aa;margin-bottom:20px}
    .actions{display:flex;flex-direction:column;align-items:center;gap:10px;margin-top:8px}
    .logo{padding:20px 0;border-top:1px solid #f3f4f6;margin-top:20px}
    .logo img{height:28px;opacity:.6}
  </style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <span class="icon"><?= $icon ?></span>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
  </div>
  <div class="card-body">
    <?php if ($order_code): ?>
    <div class="order-badge">Order #<?= htmlspecialchars($order_code) ?></div>
    <?php endif; ?>
    <?php if ($status === 'success'): ?>
    <p>Thank you for confirming! We hope you enjoy your fresh seafood from SJFBI. Your feedback means a lot to us and our fishermen.</p>
    <?php elseif ($status === 'expired'): ?>
    <p>This confirmation link has expired (links are valid for 48 hours). If you still need to confirm your order, please contact us at <a href="mailto:support@fishbrokers.net" style="color:#f97316">support@fishbrokers.net</a>.</p>
    <?php else: ?>
    <p>Something went wrong with this link. It may have already been used or is invalid. Please contact us if you need assistance.</p>
    <?php endif; ?>
    <div class="actions">
      <?= $review_btn ?>
      <?= $track_btn ?>
    </div>
    <div class="logo">
      <img src="/sjfbi-js/assets/icons/landscape-logo.png" alt="SJFBI">
    </div>
  </div>
</div>
</body>
</html>
<?php
}

// ── Validate inputs ───────────────────────────────────────────────────────────
if (!$order_id || strlen($token) < 10) {
    render_confirm_page('error', 'Invalid Link', 'This confirmation link is not valid.', '⚠️');
    exit;
}

// ── Fetch order ───────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    render_confirm_page('error', 'Order Not Found', 'We could not find this order.', '❌');
    exit;
}

$code     = $order['order_code'];
$isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';

// ── Check token ───────────────────────────────────────────────────────────────
if (!hash_equals((string)($order['confirm_token'] ?? ''), $token)) {
    render_confirm_page('error', 'Invalid Link', 'This confirmation link is invalid or has already been used.', '🔒', $code);
    exit;
}

// ── Check expiry ──────────────────────────────────────────────────────────────
if (!empty($order['confirm_token_expiry']) && strtotime($order['confirm_token_expiry']) < time()) {
    render_confirm_page('expired', 'Link Expired', 'This confirmation link has expired.', '⏰', $code);
    exit;
}

// ── Already confirmed ─────────────────────────────────────────────────────────
if (!empty($order['recipient_confirmed_at'])) {
    // Build review URL and show success again — idempotent
    $reviewToken = strtoupper(substr(hash('sha256', $order['order_code'] . $order['recipient_email'] . 'sjfbi_review_2025'), 0, 12));
    $baseUrl     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $reviewUrl   = $baseUrl . '/sjfbi-js/review.php?order=' . urlencode($order['order_code']) . '&token=' . urlencode($reviewToken);

    $verb  = $isPickup ? '🏪 Order Picked Up' : '✅ Order Confirmed';
    $msg   = $isPickup ? 'You already confirmed picking up this order.' : 'You already confirmed receiving this order.';
    render_confirm_page('success', $verb, $msg, $isPickup ? '🏪' : '✅', $code, $reviewUrl, $isPickup);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  PROCESS CONFIRMATION
// ══════════════════════════════════════════════════════════════════════════════

$conn->begin_transaction();
try {
    // 1. Mark confirmed
    $upd = $conn->prepare("UPDATE orders SET recipient_confirmed_at = NOW(), confirm_token = NULL, updated_at = NOW() WHERE order_id = ?");
    $upd->bind_param('i', $order_id);
    $upd->execute();

    // 2. Log to order_status_history
    $note    = $isPickup ? 'Customer confirmed pickup via email link.' : 'Customer confirmed receipt via email link.';
    $oldStat = $order['order_status'];
    $hist    = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by_user_id, changed_by_user_type, notes) VALUES (?, ?, ?, ?, 'customer', ?)");
    $custId  = (int)($order['account_id'] ?? 0);
    $hist->bind_param('issis', $order_id, $oldStat, $oldStat, $custId, $note);  // status unchanged — just log
    $hist->execute();

    // 3. Log to activity_log
    $ip    = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua    = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $aLog  = $conn->prepare("INSERT INTO activity_log (entity_type, entity_id, user_id, user_type, action, details, ip_address, user_agent) VALUES ('order', ?, ?, 'customer', ?, ?, ?, ?)");
    $actN  = $isPickup ? 'customer_confirm_pickup' : 'customer_confirm_receipt';
    $aLog->bind_param('iissss', $order_id, $custId, $actN, $note, $ip, $ua);
    $aLog->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("[confirm_order] DB error for order {$order_id}: " . $e->getMessage());
    render_confirm_page('error', 'Something Went Wrong', 'We could not process your confirmation. Please try again or contact support.', '⚠️', $code);
    exit;
}

// ── Build review URL ─────────────────────────────────────────────────────────
$reviewToken = strtoupper(substr(hash('sha256', $order['order_code'] . $order['recipient_email'] . 'sjfbi_review_2025'), 0, 12));
$baseUrl     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$reviewUrl   = $baseUrl . '/sjfbi-js/review.php?order=' . urlencode($order['order_code']) . '&token=' . urlencode($reviewToken);

// ── Send "Order Complete" thank-you email ────────────────────────────────────
// For COP pickup orders: skip here — review email is sent after payment is
// collected via markCOPPaymentReceived() in order_process.php instead.
$isCOPPickup = ($order['payment_method'] ?? '') === 'cop'
            && ($order['order_type']     ?? '') === 'pickup';

if (!$isCOPPickup) {
    try {
        email_order_completed($order, $reviewUrl);
    } catch (Exception $e) {
        error_log("[confirm_order] email_order_completed failed for order {$order_id}: " . $e->getMessage());
    }
}

// ── Show success page (auto-redirects to review after 4 seconds) ─────────────
$successTitle = $isPickup ? 'Order Picked Up!' : 'Order Confirmed!';
$successMsg   = $isPickup
    ? 'Thank you for collecting your order. We hope you enjoy your purchase!'
    : 'Thank you for confirming receipt of your order. We hope you enjoy your seafood!';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $successTitle ?> | SJFBI</title>
  <meta http-equiv="refresh" content="4;url=<?= htmlspecialchars($reviewUrl) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Lexend',Helvetica,Arial,sans-serif;background:#f4f4f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{background:#fff;border-radius:20px;max-width:480px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,.10);overflow:hidden;text-align:center}
    .card-top{background:linear-gradient(135deg,#16a34a,#4ade80);padding:40px 32px 28px;color:#fff}
    .card-top .icon{font-size:64px;margin-bottom:16px;display:block;animation:pop .5s cubic-bezier(.34,1.56,.64,1) both}
    @keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
    .card-top h1{font-size:24px;font-weight:800;margin-bottom:6px}
    .card-top p{font-size:14px;opacity:.9;line-height:1.55}
    .card-body{padding:28px 32px}
    .order-badge{display:inline-block;background:#fff7ed;color:#ea580c;font-size:14px;font-weight:700;padding:8px 20px;border-radius:9999px;border:1.5px solid #fed7aa;margin-bottom:20px}
    .card-body p{font-size:14px;color:#6b7280;line-height:1.65;margin-bottom:16px}
    .redirect-note{font-size:12px;color:#9ca3af;margin-bottom:0}
    .actions{display:flex;flex-direction:column;align-items:center;gap:10px;margin-top:8px}
    .logo{padding:20px 0;border-top:1px solid #f3f4f6;margin-top:20px}
    .logo img{height:28px;opacity:.6}
    .progress-bar{height:3px;background:#e5e7eb;border-radius:9999px;overflow:hidden;margin:16px 0}
    .progress-fill{height:100%;background:linear-gradient(90deg,#16a34a,#4ade80);animation:fill 4s linear both}
    @keyframes fill{from{width:0}to{width:100%}}
  </style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <span class="icon"><?= $isPickup ? '🏪' : '✅' ?></span>
    <h1><?= $successTitle ?></h1>
    <p><?= $successMsg ?></p>
  </div>
  <div class="card-body">
    <div class="order-badge">Order #<?= htmlspecialchars($code) ?></div>
    <p>Your order has been marked as complete. We hope you enjoy your fresh catch!</p>
    <div class="progress-bar"><div class="progress-fill"></div></div>
    <p class="redirect-note">Redirecting you to leave a review in a moment…</p>
    <div class="actions">
      <a href="<?= htmlspecialchars($reviewUrl) ?>" style="display:inline-block;background:#f59e0b;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none">⭐ Leave a Review Now</a>
      <a href="<?= htmlspecialchars($baseUrl . '/sjfbi-js/track.php?order_code=' . urlencode($code)) ?>" style="display:inline-block;background:#f9fafb;color:#374151;border:1.5px solid #e5e7eb;font-size:14px;font-weight:600;padding:10px 24px;border-radius:10px;text-decoration:none">📦 Track My Order</a>
    </div>
    <div class="logo">
      <img src="/sjfbi-js/assets/icons/landscape-logo.png" alt="SJFBI">
    </div>
  </div>
</div>
</body>
</html>
<?php
exit;