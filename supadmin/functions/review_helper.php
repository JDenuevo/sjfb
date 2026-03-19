<?php
// ==================== superadmin/functions/review_helper.php ====================
// Column renames applied:
//   orders:   recipient_email, recipient_first_name, recipient_last_name, recipient_phone
//   riders:   rider_name (was full_name)
//   accounts: account_first_name, account_last_name
// =================================================================================

if (!defined('REVIEW_HELPER_LOADED')) {
    define('REVIEW_HELPER_LOADED', true);
}

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

if (!function_exists('loadEnvValue')) {
    function loadEnvValue(string $key, string $default = ''): string {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val !== false && $val !== '') return (string) $val;
        static $parsed = [];
        if (empty($parsed)) {
            $envFile = __DIR__ . '/../../.env';
            if (file_exists($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                    [$k, $v]          = explode('=', $line, 2);
                    $parsed[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                }
            }
        }
        return $parsed[$key] ?? $default;
    }
}

if (!function_exists('makeMailer')) {
    function makeMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host        = 'localhost';
        $mail->SMTPAuth    = false;
        $mail->Port        = 25;
        $mail->SMTPAutoTLS = false;
        $mail->SMTPSecure  = false;
        $mail->SMTPDebug   = 0;
        $mail->Debugoutput = 'error_log';
        $mail->Timeout     = 30;
        $mail->CharSet     = 'UTF-8';
        $mail->setFrom(
            loadEnvValue('MAIL_FROM',      'marketing@fishbrokers.net'),
            loadEnvValue('MAIL_FROM_NAME', 'St. Joseph Fish Brokerage Inc.')
        );
        return $mail;
    }
}

if (!function_exists('sendSms')) {
    function sendSms(string $phoneNumber, string $message): bool {
        $apiKey = loadEnvValue('SEMAPHORE_API_KEY');
        if (empty($apiKey)) {
            error_log('[review_helper] SEMAPHORE_API_KEY not set in .env -- SMS skipped.');
            return false;
        }
        $ch = curl_init('https://api.semaphore.co/api/v4/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query([
                'apikey'     => $apiKey,
                'number'     => $phoneNumber,
                'message'    => $message,
                'sendername' => 'SJFBI',
            ]),
        ]);
        $response  = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        curl_close($ch);
        if ($curlErrNo) { error_log("[review_helper] Semaphore cURL error #{$curlErrNo} for {$phoneNumber}"); return false; }
        $decoded = json_decode($response, true);
        $queued  = isset($decoded[0]['status']) && strtolower($decoded[0]['status']) === 'pending';
        error_log('[review_helper] SMS to ' . $phoneNumber . ' -- ' . ($queued ? 'queued OK' : 'failed: ' . $response));
        return $queued;
    }
}

if (!function_exists('generateReviewToken')) {
    function generateReviewToken(string $orderCode, string $email, string $salt = 'sjfbi_review_2025'): string {
        return strtoupper(substr(hash('sha256', $orderCode . $email . $salt), 0, 12));
    }
}

if (!function_exists('buildReviewUrl')) {
    function buildReviewUrl(string $orderCode, string $email): string {
        $token = generateReviewToken($orderCode, $email);
        $base  = rtrim(loadEnvValue('SITE_BASE_URL', 'http://localhost/sjfbi-js'), '/');
        return $base . '/review.php?order=' . urlencode($orderCode) . '&token=' . urlencode($token);
    }
}

// Uses renamed columns: recipient_email, recipient_first_name, recipient_last_name,
//                       recipient_phone, rider_name (was full_name),
//                       account_first_name, account_last_name
if (!function_exists('fetchOrderForNotification')) {
    function fetchOrderForNotification($conn, int $orderId): ?array {
        $stmt = $conn->prepare("
            SELECT  o.order_code,
                    o.recipient_email      AS email,
                    o.recipient_phone      AS phone_number,
                    o.recipient_first_name AS first_name,
                    o.recipient_last_name  AS last_name,
                    o.order_status,
                    o.total_price,
                    COALESCE(r.rider_name, CONCAT(a.account_first_name, ' ', a.account_last_name)) AS rider_name
            FROM    orders o
            LEFT JOIN riders   r ON o.assigned_rider_id = r.rider_id
            LEFT JOIN accounts a ON r.account_id         = a.account_id
            WHERE   o.order_id = ?
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}

// =============================================================================
//  1.  PENDING -> PROCESSING
// =============================================================================

if (!function_exists('dispatchOrderApprovedNotification')) {
    function dispatchOrderApprovedNotification($conn, int $orderId, ?int $actorId = null, string $actorType = 'super_admin'): array {
        $order = fetchOrderForNotification($conn, $orderId);
        if (!$order) return ['success' => false, 'message' => 'Order not found.'];

        $orderCode = $order['order_code'];
        $firstName = $order['first_name'];
        $email     = $order['email'];
        $phone     = $order['phone_number'];
        $base      = rtrim(loadEnvValue('SITE_BASE_URL', 'http://localhost/sjfbi-js'), '/');
        $trackUrl  = $base . '/track.php?order_code=' . urlencode($orderCode);

        $smsMsg  = "Hi {$firstName}! Your order {$orderCode} has been approved and is now being prepared. Track: {$trackUrl} -SJFBI";
        $smsSent = !empty($phone) ? sendSms($phone, $smsMsg) : false;

        $emailSent = false;
        $safeFirst = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $safeCode  = htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8');
        $safeTrack = htmlspecialchars($trackUrl,  ENT_QUOTES, 'UTF-8');

        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:0;'>
        <div style='max-width:600px;margin:32px auto;'>
          <div style='background:linear-gradient(135deg,#F2571B,#f97316,#fbbf24);padding:28px;text-align:center;border-radius:12px 12px 0 0;'>
            <h2 style='color:white;margin:0;font-size:20px;'>Order Approved!</h2>
            <p style='color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;'>Order {$safeCode}</p>
          </div>
          <div style='background:white;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;'>
            <p style='font-size:16px;color:#374151;'>Hi <strong>{$safeFirst}</strong>,</p>
            <p style='font-size:15px;color:#6b7280;line-height:1.7;'>Great news! Your order has been approved and our team is now preparing it for delivery.</p>
            <div style='text-align:center;margin:28px 0;'>
              <a href='{$safeTrack}' style='display:inline-block;background:#ea580c;color:white;padding:12px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;'>Track My Order</a>
            </div>
            <p style='font-size:12px;color:#9ca3af;text-align:center;margin:0;border-top:1px solid #f3f4f6;padding-top:16px;'>St. Joseph Fish Brokerage Inc. - Navotas City, Philippines</p>
          </div>
        </div></body></html>";

        try {
            $mail = makeMailer();
            $mail->addAddress($email, $firstName);
            $mail->isHTML(true);
            $mail->Subject = "Your order {$orderCode} is being prepared - SJFBI";
            $mail->Body    = $html;
            $mail->AltBody = "Hi {$firstName}, your order {$orderCode} has been approved and is being prepared. Track: {$trackUrl}";
            $mail->send();
            $emailSent = true;
        } catch (MailerException $e) {
            error_log("[review_helper] Approved email FAILED -> {$email}: " . $e->getMessage());
        }

        if (function_exists('logActivity')) {
            logActivity($conn, 'order_notification', $orderId, 'Order approved notification sent',
                null, null, "SMS: " . ($smsSent ? 'OK' : 'failed') . " | Email: " . ($emailSent ? 'OK' : 'failed'),
                $actorId, $actorType);
        }
        return ['success' => true, 'sms_sent' => $smsSent, 'email_sent' => $emailSent];
    }
}

// =============================================================================
//  2.  PROCESSING -> OUT FOR DELIVERY
// =============================================================================

if (!function_exists('dispatchOutForDeliveryNotification')) {
    function dispatchOutForDeliveryNotification($conn, int $orderId, ?int $actorId = null, string $actorType = 'super_admin'): array {
        $order = fetchOrderForNotification($conn, $orderId);
        if (!$order) return ['success' => false, 'message' => 'Order not found.'];

        $orderCode = $order['order_code'];
        $firstName = $order['first_name'];
        $email     = $order['email'];
        $phone     = $order['phone_number'];
        $riderName = $order['rider_name'] ?? 'our rider';
        $base      = rtrim(loadEnvValue('SITE_BASE_URL', 'http://localhost/sjfbi-js'), '/');
        $trackUrl  = $base . '/track.php?order_code=' . urlencode($orderCode);

        $smsMsg  = "Hi {$firstName}! Order {$orderCode} is on its way! Rider: {$riderName}. Track: {$trackUrl} -SJFBI";
        $smsSent = !empty($phone) ? sendSms($phone, $smsMsg) : false;

        $emailSent = false;
        $safeFirst = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $safeCode  = htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8');
        $safeRider = htmlspecialchars($riderName, ENT_QUOTES, 'UTF-8');
        $safeTrack = htmlspecialchars($trackUrl,  ENT_QUOTES, 'UTF-8');

        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:0;'>
        <div style='max-width:600px;margin:32px auto;'>
          <div style='background:linear-gradient(135deg,#0d9488,#0891b2);padding:28px;text-align:center;border-radius:12px 12px 0 0;'>
            <h2 style='color:white;margin:0;font-size:20px;'>Your Order is On the Way!</h2>
            <p style='color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;'>Order {$safeCode}</p>
          </div>
          <div style='background:white;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;'>
            <p style='font-size:16px;color:#374151;'>Hi <strong>{$safeFirst}</strong>,</p>
            <p style='font-size:15px;color:#6b7280;line-height:1.7;'>Your order is now out for delivery! <strong>{$safeRider}</strong> is on the way.</p>
            <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;margin:20px 0;text-align:center;'>
              <p style='margin:0;font-size:13px;color:#166534;'>Rider: <strong>{$safeRider}</strong></p>
            </div>
            <div style='text-align:center;margin:24px 0;'>
              <a href='{$safeTrack}' style='display:inline-block;background:#0d9488;color:white;padding:12px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;'>Track My Order</a>
            </div>
            <p style='font-size:12px;color:#9ca3af;text-align:center;margin:0;border-top:1px solid #f3f4f6;padding-top:16px;'>St. Joseph Fish Brokerage Inc. - Navotas City, Philippines</p>
          </div>
        </div></body></html>";

        try {
            $mail = makeMailer();
            $mail->addAddress($email, $firstName);
            $mail->isHTML(true);
            $mail->Subject = "Your order {$orderCode} is out for delivery - SJFBI";
            $mail->Body    = $html;
            $mail->AltBody = "Hi {$firstName}, order {$orderCode} is on the way! Rider: {$riderName}. Track: {$trackUrl}";
            $mail->send();
            $emailSent = true;
        } catch (MailerException $e) {
            error_log("[review_helper] OutForDelivery email FAILED -> {$email}: " . $e->getMessage());
        }

        if (function_exists('logActivity')) {
            logActivity($conn, 'order_notification', $orderId, 'Out for delivery notification sent',
                null, null, "Rider: {$riderName} | SMS: " . ($smsSent ? 'OK' : 'failed') . " | Email: " . ($emailSent ? 'OK' : 'failed'),
                $actorId, $actorType);
        }
        return ['success' => true, 'sms_sent' => $smsSent, 'email_sent' => $emailSent];
    }
}

// =============================================================================
//  3.  DELIVERED + REVIEW INVITE
// =============================================================================

if (!function_exists('dispatchReviewInvite')) {
    function dispatchReviewInvite($conn, int $orderId, ?int $actorId = null, string $actorType = 'super_admin'): array {
        $order = fetchOrderForNotification($conn, $orderId);
        
        // Use same deterministic token as review.php expects
        $token     = strtoupper(substr(hash('sha256', 
            $order['order_code'] . $order['email'] . 'sjfbi_review_2025'
        ), 0, 12));
        $base      = rtrim(loadEnvValue('SITE_BASE_URL', 'http://localhost/sjfbi-js'), '/');
        $reviewUrl = $base . '/review.php?order=' . urlencode($order['order_code']) 
                        . '&token=' . urlencode($token);    
    
        $order = fetchOrderForNotification($conn, $orderId);
        if (!$order) {
            return ['success' => false, 'status' => 'error', 'message' => 'Order not found.',
                    'review_url' => null, 'sms_sent' => false, 'email_sent' => false];
        }

        $orderCode = $order['order_code'];
        $email     = $order['email'];
        $phone     = $order['phone_number'];
        $firstName = $order['first_name'];
        $reviewUrl = buildReviewUrl($orderCode, $email);

        $ins = $conn->prepare("
            INSERT INTO review_invites (order_id, review_url)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE review_url = VALUES(review_url), sent_at = NOW()
        ");
        $ins->bind_param('is', $orderId, $reviewUrl);
        $ins->execute();
        $ins->close();

        $smsMsg  = "Hi {$firstName}! Order {$orderCode} has been delivered. How was it? Leave a review: {$reviewUrl} -SJFBI";
        $smsSent = !empty($phone) ? sendSms($phone, $smsMsg) : false;

        $emailSent = false;
        $safeFirst = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $safeCode  = htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8');
        $safeUrl   = htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8');

        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:0;'>
        <div style='max-width:600px;margin:32px auto;'>
          <div style='background:linear-gradient(135deg,#F2571B,#f97316,#fbbf24);padding:28px;text-align:center;border-radius:12px 12px 0 0;'>
            <h2 style='color:white;margin:0;font-size:20px;'>How was your order?</h2>
            <p style='color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;'>Order {$safeCode} - Delivered!</p>
          </div>
          <div style='background:white;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;'>
            <p style='font-size:16px;color:#374151;'>Hi <strong>{$safeFirst}</strong>,</p>
            <p style='font-size:15px;color:#6b7280;line-height:1.7;'>Your order has been delivered! We would love to hear what you think.</p>
            <div style='text-align:center;margin:32px 0;'>
              <a href='{$safeUrl}' style='display:inline-block;background:#ea580c;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;'>Leave a Review</a>
            </div>
            <p style='font-size:12px;color:#9ca3af;text-align:center;margin:0;border-top:1px solid #f3f4f6;padding-top:16px;'>
              St. Joseph Fish Brokerage Inc. - Navotas City, Philippines<br>
              <span style='font-size:11px;'>This link is unique to your order. Do not share it.</span>
            </p>
          </div>
        </div></body></html>";

        try {
            $mail = makeMailer();
            $mail->addAddress($email, $firstName);
            $mail->isHTML(true);
            $mail->Subject = "Your order {$orderCode} was delivered - leave a review!";
            $mail->Body    = $html;
            $mail->AltBody = "Hi {$firstName}, your order {$orderCode} was delivered! Leave a review: {$reviewUrl}";
            $mail->send();
            $emailSent = true;
        } catch (MailerException $e) {
            error_log("[review_helper] Review invite email FAILED -> {$email}: " . $e->getMessage());
        }

        if (function_exists('logActivity')) {
            logActivity($conn, 'review_invite', $orderId, 'Review invite dispatched',
                null, $reviewUrl, "SMS: " . ($smsSent ? 'OK' : 'failed') . " | Email: " . ($emailSent ? 'OK' : 'failed'),
                $actorId, $actorType);
        }

        $status = ($smsSent || $emailSent) ? 'success' : 'warning';
        return [
            'success'    => true,
            'status'     => $status,
            'message'    => $status === 'success'
                                ? "Review invite sent for order {$orderCode}."
                                : "Review invite saved but notifications failed - check error log.",
            'review_url' => $reviewUrl,
            'sms_sent'   => $smsSent,
            'email_sent' => $emailSent,
        ];
    }
}

// =============================================================================
//  4.  CANCELLED
// =============================================================================

if (!function_exists('dispatchCancelledNotification')) {
    function dispatchCancelledNotification($conn, int $orderId, string $reason = '', ?int $actorId = null, string $actorType = 'super_admin'): array {
        $order = fetchOrderForNotification($conn, $orderId);
        if (!$order) return ['success' => false, 'message' => 'Order not found.'];

        $orderCode  = $order['order_code'];
        $firstName  = $order['first_name'];
        $email      = $order['email'];
        $phone      = $order['phone_number'];
        $reasonText = !empty($reason) ? $reason : 'Your order has been cancelled.';

        $smsMsg  = "Hi {$firstName}, order {$orderCode} has been cancelled. Reason: {$reasonText} Contact us for help. -SJFBI";
        $smsSent = !empty($phone) ? sendSms($phone, $smsMsg) : false;

        $emailSent  = false;
        $safeFirst  = htmlspecialchars($firstName,  ENT_QUOTES, 'UTF-8');
        $safeCode   = htmlspecialchars($orderCode,  ENT_QUOTES, 'UTF-8');
        $safeReason = htmlspecialchars($reasonText, ENT_QUOTES, 'UTF-8');

        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:0;'>
        <div style='max-width:600px;margin:32px auto;'>
          <div style='background:linear-gradient(135deg,#dc2626,#ef4444);padding:28px;text-align:center;border-radius:12px 12px 0 0;'>
            <h2 style='color:white;margin:0;font-size:20px;'>Order Cancelled</h2>
            <p style='color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;'>Order {$safeCode}</p>
          </div>
          <div style='background:white;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;'>
            <p style='font-size:16px;color:#374151;'>Hi <strong>{$safeFirst}</strong>,</p>
            <p style='font-size:15px;color:#6b7280;line-height:1.7;'>We are sorry - your order has been cancelled.</p>
            <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;margin:20px 0;'>
              <p style='margin:0;font-size:13px;color:#991b1b;'><strong>Reason:</strong> {$safeReason}</p>
            </div>
            <p style='font-size:15px;color:#6b7280;line-height:1.7;'>If you believe this is a mistake or would like to place a new order, please do not hesitate to contact us.</p>
            <p style='font-size:12px;color:#9ca3af;text-align:center;margin:24px 0 0;border-top:1px solid #f3f4f6;padding-top:16px;'>St. Joseph Fish Brokerage Inc. - Navotas City, Philippines</p>
          </div>
        </div></body></html>";

        try {
            $mail = makeMailer();
            $mail->addAddress($email, $firstName);
            $mail->isHTML(true);
            $mail->Subject = "Your order {$orderCode} has been cancelled - SJFBI";
            $mail->Body    = $html;
            $mail->AltBody = "Hi {$firstName}, order {$orderCode} has been cancelled. Reason: {$reasonText}";
            $mail->send();
            $emailSent = true;
        } catch (MailerException $e) {
            error_log("[review_helper] Cancelled email FAILED -> {$email}: " . $e->getMessage());
        }

        if (function_exists('logActivity')) {
            logActivity($conn, 'order_notification', $orderId, 'Cancellation notification sent',
                null, null, "Reason: {$reasonText} | SMS: " . ($smsSent ? 'OK' : 'failed') . " | Email: " . ($emailSent ? 'OK' : 'failed'),
                $actorId, $actorType);
        }
        return ['success' => true, 'sms_sent' => $smsSent, 'email_sent' => $emailSent];
    }
}

// =============================================================================
//  Review image upload helpers (no column changes needed)
// =============================================================================

if (!function_exists('uploadReviewImage')) {
    function uploadReviewImage(array $file, int $reviewId, int $uploadOrder = 1): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        $tmp  = $file['tmp_name'];
        $mime = mime_content_type($tmp);
        if (strpos($mime, 'image/') !== 0) { error_log("[uploadReviewImage] Rejected - not an image (MIME: {$mime})"); return null; }
        if ($file['size'] > 5 * 1024 * 1024) { error_log("[uploadReviewImage] Rejected - file too large"); return null; }
        $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowedExts, true)) { error_log("[uploadReviewImage] Rejected - disallowed extension"); return null; }
        $uploadDir = __DIR__ . '/../../uploads/reviews/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = 'review_' . $reviewId . '_' . $uploadOrder . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($tmp, $uploadDir . $filename)) { error_log("[uploadReviewImage] move_uploaded_file failed"); return null; }
        return 'uploads/reviews/' . $filename;
    }
}

if (!function_exists('insertReviewAttachment')) {
    function insertReviewAttachment($conn, int $reviewId, string $relPath, string $fileName, int $fileSize, string $mimeType, int $uploadOrder = 1): bool {
        $stmt = $conn->prepare("INSERT INTO review_attachments (review_id, file_path, file_name, file_size, mime_type, upload_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issiis', $reviewId, $relPath, $fileName, $fileSize, $mimeType, $uploadOrder);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}