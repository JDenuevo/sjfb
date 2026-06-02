<?php
/**
 * supadmin/functions/email_helper.php
 *
 * Unified transactional email system for SJFBI.
 * Uses same SMTP config as mail_functions.php (MAIL_USERNAME / MAIL_PASSWORD).
 * Safe to include multiple times — all definitions guarded with function_exists.
 *
 * EMAIL FLOW:
 *  #1  email_order_approved()              — Admin approves order → "Being Prepared"
 *  #2  email_out_for_delivery()            — Rider accepts / 3rd-party / admin dispatches → "On the Way"
 *  #3  email_ready_for_pickup()            — Admin marks ready → "Ready for Pickup" + confirm button
 *  #4  email_delivered_confirm_receipt()   — Rider/admin marks delivered → "Order Received" confirm button
 *  #5  email_order_completed()             — Customer clicks confirm → "Thank You" + review CTA
 *  #6  email_order_cancelled()             — Admin cancels → "Order Cancelled"
 *
 * Token helper:
 *      generate_and_persist_confirm_token() — generates + saves confirm_token to orders table
 */

// ── Load .env + PHPMailer (mirrors mail_functions.php setup) ─────────────────
if (!defined('SJFBI_EMAIL_HELPER_LOADED')) {
    define('SJFBI_EMAIL_HELPER_LOADED', true);

    // Go up two levels: functions/ → supadmin/ → project root
    $sjfbi_root = dirname(__DIR__, 2);

    if (file_exists($sjfbi_root . '/vendor/autoload.php')) {
        require_once $sjfbi_root . '/vendor/autoload.php';
    }

    // Load .env only if not already loaded
    if (empty($_ENV['MAIL_USERNAME']) && file_exists($sjfbi_root . '/.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($sjfbi_root);
            $dotenv->safeLoad();
        } catch (\Throwable $e) {
            error_log('[SJFBI Email] dotenv load error: ' . $e->getMessage());
        }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  CORE SENDER — identical config to mail_functions.php sendEmail()
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('sjfbi_send_email')) {
    function sjfbi_send_email(string $to, string $toName, string $subject, string $htmlBody): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("[SJFBI Email] Invalid address: {$to}");
            return false;
        }
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->SMTPDebug  = PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST']         ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']     ?? '';
            $mail->Password   = trim($_ENV['MAIL_PASSWORD'] ?? '', '"\' ');
            $mail->SMTPSecure = ((int)($_ENV['MAIL_PORT'] ?? 587) === 465)
                                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';

            $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_USERNAME'] ?? '';
            $fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'St. Joseph Fish Brokerage Inc.';

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to, $toName);
            $mail->addReplyTo($fromAddress, $fromName);

            $mail->isHTML(true);
            $mail->Subject = $mail->encodeHeader($subject);
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

            $mail->send();
            error_log("[SJFBI Email] Sent OK → {$to} | {$subject}");
            return true;
        } catch (\Exception $e) {
            error_log("[SJFBI Email] FAILED → {$to} | " . $e->getMessage());
            return false;
        }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  TOKEN HELPER
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('generate_and_persist_confirm_token')) {
    function generate_and_persist_confirm_token(int $order_id, mysqli $conn, int $hours = 48): string {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        $stmt   = $conn->prepare("UPDATE orders SET confirm_token = ?, confirm_token_expiry = ? WHERE order_id = ?");
        if ($stmt) {
            $stmt->bind_param('ssi', $token, $expiry, $order_id);
            $stmt->execute();
        }
        return $token;
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  SHARED TEMPLATE HELPERS
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('_eh_wrap')) {
    /**
     * Full HTML email wrapper with SJFBI branding.
     * $accentColor  — hex color for the top header bar
     * $headerEmoji  — large emoji shown in header
     * $headerTitle  — bold white title
     * $headerSub    — smaller subtitle
     * $bodyContent  — everything inside the white card below the header
     */
    function _eh_wrap(
        string $accentColor,
        string $headerEmoji,
        string $headerTitle,
        string $headerSub,
        string $bodyContent,
        string $preheader = ''
    ): string {
        $siteUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $year     = date('Y');
        $pre      = $preheader ?: $headerTitle;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SJFBI</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
  <!-- preheader -->
  <div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f4f4f5;">{$pre} &zwnj;&nbsp;</div>

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px;">
    <tr><td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">

        <!-- Logo -->
        <tr><td style="padding-bottom:20px;text-align:center;">
          <img src="{$siteUrl}/assets/icons/landscape-logo.png" alt="SJFBI" width="160"
               style="display:block;margin:0 auto;height:auto;">
        </td></tr>

        <!-- Card -->
        <tr><td style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

          <!-- Header -->
          <div style="background:{$accentColor};padding:36px 32px 28px;text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;line-height:1;">{$headerEmoji}</div>
            <h1 style="margin:0;font-size:22px;font-weight:800;color:#ffffff;">{$headerTitle}</h1>
            <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,.85);">{$headerSub}</p>
          </div>

          <!-- Body -->
          <div style="padding:28px 32px;">
            {$bodyContent}
          </div>

          <!-- Footer -->
          <div style="padding:20px 32px 28px;text-align:center;border-top:1px solid #f3f4f6;">
            <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.7;">
              St. Joseph Fish Brokerage Inc. &middot; Bulungan Ave. corner HACCP St., NFPC NBBS, Navotas, Philippines<br>
              <a href="{$siteUrl}" style="color:#f97316;text-decoration:none;">{$siteUrl}</a>
              &nbsp;&middot;&nbsp;
              <a href="mailto:{$_ENV['MAIL_USERNAME']}" style="color:#9ca3af;text-decoration:none;">{$_ENV['MAIL_USERNAME']}</a>
            </p>
            <p style="margin:8px 0 0;font-size:11px;color:#d1d5db;">
              This is an automated message &mdash; please do not reply directly.<br>
              &copy; {$year} St. Joseph Fish Brokerage Inc.
            </p>
          </div>

        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}

if (!function_exists('_eh_btn')) {
    function _eh_btn(string $label, string $url, string $color = '#f97316'): string {
        return "<div style='text-align:center;margin:24px 0;'>"
             . "<a href='" . htmlspecialchars($url) . "' "
             . "style='display:inline-block;background:{$color};color:#ffffff;font-size:15px;"
             . "font-weight:800;padding:15px 38px;border-radius:12px;text-decoration:none;"
             . "box-shadow:0 4px 14px rgba(0,0,0,.15);'>{$label}</a>"
             . "</div>";
    }
}

if (!function_exists('_eh_infobox')) {
    function _eh_infobox(string $label, string $value, string $icon = '&#128205;'): string {
        return "<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;"
             . "padding:14px 16px;margin-bottom:12px;'>"
             . "<p style='margin:0;font-size:11px;font-weight:700;color:#9ca3af;"
             . "text-transform:uppercase;letter-spacing:.04em;'>{$icon} {$label}</p>"
             . "<p style='margin:5px 0 0;font-size:13px;color:#111827;line-height:1.55;'>{$value}</p>"
             . "</div>";
    }
}

if (!function_exists('_eh_order_meta')) {
    function _eh_order_meta(array $order): string {
        $methods = [
            'cod' => 'Cash on Delivery', 'cop' => 'Cash on Pickup',
            'gcash' => 'GCash', 'paymaya' => 'Maya', 'grab_pay' => 'GrabPay',
            'qrph' => 'QR Ph', 'card' => 'Credit / Debit Card',
        ];
        $method   = $methods[strtolower($order['payment_method'] ?? '')] ?? ucfirst($order['payment_method'] ?? '—');
        $type     = ($order['order_type'] ?? 'delivery') === 'pickup' ? 'Store Pickup' : 'Delivery';
        $dateStr  = !empty($order['order_date']) ? date('F j, Y', strtotime($order['order_date'])) : date('F j, Y');

        $rows = [
            ['Order Code',   '#' . htmlspecialchars($order['order_code'])],
            ['Order Date',   $dateStr],
            ['Order Type',   $type],
            ['Payment',      $method],
            ['Total',        '&#8369;' . number_format((float)($order['total_price'] ?? 0), 2)],
        ];

        $html = "<table width='100%' cellpadding='0' cellspacing='0' "
              . "style='border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:20px;'>";
        foreach ($rows as $i => $r) {
            $bg = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
            $html .= "<tr style='background:{$bg};'>"
                   . "<td style='padding:9px 14px;font-size:13px;color:#6b7280;width:45%;'>{$r[0]}</td>"
                   . "<td style='padding:9px 14px;font-size:13px;font-weight:700;color:#111827;text-align:right;'>{$r[1]}</td>"
                   . "</tr>";
        }
        return $html . "</table>";
    }
}

if (!function_exists('_eh_items_table')) {
    function _eh_items_table(array $items): string {
        $html = "<table width='100%' cellpadding='0' cellspacing='0' "
              . "style='border-collapse:collapse;margin-bottom:16px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;'>"
              . "<tr style='background:#f3f4f6;'>"
              . "<th style='padding:10px 12px;font-size:12px;font-weight:700;color:#374151;text-align:left;'>Product</th>"
              . "<th style='padding:10px 12px;font-size:12px;font-weight:700;color:#374151;text-align:center;'>Qty</th>"
              . "<th style='padding:10px 12px;font-size:12px;font-weight:700;color:#374151;text-align:right;'>Price</th>"
              . "</tr>";
        foreach ($items as $item) {
            $lineTotal = (float)$item['price'] * (float)$item['quantity'];
            $html .= "<tr style='border-top:1px solid #f3f4f6;'>"
                   . "<td style='padding:10px 12px;'>"
                   . "<p style='margin:0;font-size:13px;font-weight:600;color:#111827;'>"
                   . htmlspecialchars($item['product_name'] ?? '—') . "</p>"
                   . "<p style='margin:2px 0 0;font-size:11px;color:#9ca3af;'>"
                   . htmlspecialchars($item['variant_name'] ?? '') . "</p></td>"
                   . "<td style='padding:10px 12px;text-align:center;font-size:13px;color:#374151;'>&times;" . (int)$item['quantity'] . "</td>"
                   . "<td style='padding:10px 12px;text-align:right;font-size:13px;font-weight:600;color:#374151;'>&#8369;" . number_format($lineTotal, 2) . "</td>"
                   . "</tr>";
        }
        return $html . "</table>";
    }
}

if (!function_exists('_eh_confirm_fallback')) {
    function _eh_confirm_fallback(string $url): string {
        return "<div style='margin-top:16px;padding:14px;background:#f9fafb;border-radius:8px;text-align:center;'>"
             . "<p style='margin:0 0 6px;font-size:12px;color:#9ca3af;'>Button not working? Copy this link:</p>"
             . "<span style='font-size:11px;color:#f97316;word-break:break-all;font-family:monospace;'>"
             . htmlspecialchars($url) . "</span>"
             . "</div>"
             . "<p style='margin:12px 0 0;font-size:12px;color:#9ca3af;text-align:center;'>This link expires in 48 hours.</p>";
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #1 — ORDER APPROVED / PROCESSING
//  Trigger: Admin clicks "Approve & Process Order"
//  Covers:  Delivery AND Pickup, all payment methods
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_order_approved')) {
    function email_order_approved(array $order): bool {
        $isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';
        $isCOD    = ($order['payment_method'] ?? '') === 'cod';
        $isCOP    = ($order['payment_method'] ?? '') === 'cop';
        $name     = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code     = htmlspecialchars($order['order_code']);
        $siteUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $trackUrl = $siteUrl . '/track.php?order_code=' . urlencode($order['order_code']);

        // What happens next depends on order type
        if ($isPickup) {
            $nextStep = "We're now preparing your order. You'll receive another email once it's <strong>ready for pickup</strong> at our store.";
        } else {
            $nextStep = "We're packing your order now and will assign a delivery rider soon. You'll get another email once it's <strong>out for delivery</strong>.";
        }

        // Payment reminder for cash orders
        $cashNote = '';
        if ($isCOP) {
            $cashNote = "<div style='background:#dbeafe;border:1px solid #bfdbfe;border-radius:10px;"
                      . "padding:12px 14px;margin:16px 0;font-size:13px;color:#1e40af;'>"
                      . "&#127978; <strong>Cash on Pickup</strong> &mdash; Please prepare "
                      . "<strong>&#8369;" . number_format((float)$order['total_price'], 2) . "</strong> when you collect your order."
                      . "</div>";
        } elseif ($isCOD) {
            $cashNote = "<div style='background:#fef3c7;border:1px solid #fde68a;border-radius:10px;"
                      . "padding:12px 14px;margin:16px 0;font-size:13px;color:#92400e;'>"
                      . "&#128181; <strong>Cash on Delivery</strong> &mdash; Please prepare "
                      . "<strong>&#8369;" . number_format((float)$order['total_price'], 2) . "</strong> for the rider upon delivery."
                      . "</div>";
        }

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "Great news! Your order has been confirmed and approved. {$nextStep}</p>"
              . _eh_order_meta($order)
              . $cashNote
              . "<div style='text-align:center;margin:20px 0;'>"
              . "<a href='{$trackUrl}' style='font-size:13px;color:#2563eb;text-decoration:underline;'>View Order Status &rarr;</a>"
              . "</div>";

        $html = _eh_wrap(
            '#2563eb',
            '&#9881;&#65039;',
            'Order Approved!',
            "Order #{$code} is now being prepared",
            $body,
            "Your order #{$code} has been approved and is being prepared."
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Your Order #{$code} is Being Prepared — SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #2 — OUT FOR DELIVERY
//  Trigger: Rider accepts / 3rd-party assigned / admin sends out for delivery
//  Covers:  Delivery orders only
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_out_for_delivery')) {
    function email_out_for_delivery(array $order, ?array $rider = null): bool {
        $name         = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code         = htmlspecialchars($order['order_code']);
        $siteUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $trackUrl     = $siteUrl . '/track.php?order_code=' . urlencode($order['order_code']);
        $isThirdParty = !empty($order['is_third_party']);
        $isCOD        = ($order['payment_method'] ?? '') === 'cod';

        // Rider / delivery partner block
        $riderBlock = '';
        if ($isThirdParty && !empty($order['third_party_name'])) {
            $detail = htmlspecialchars($order['third_party_name']);
            if (!empty($order['active_delivery_link'])) {
                $detail .= "<br><a href='" . htmlspecialchars($order['active_delivery_link'])
                         . "' style='color:#f97316;font-size:12px;'>Track via "
                         . htmlspecialchars($order['third_party_name']) . " &rarr;</a>";
            }
            $riderBlock = _eh_infobox('Delivery Partner', $detail, '&#128666;');
        } elseif ($rider) {
            $riderName  = htmlspecialchars($rider['display_name'] ?? $rider['rider_name'] ?? 'Our Rider');
            $riderPhone = htmlspecialchars($rider['rider_direct_phone'] ?? $rider['contact_number'] ?? '');
            $vehicle    = ucfirst($rider['vehicle_type'] ?? '');
            if (!empty($rider['vehicle_plate_number'])) $vehicle .= ' &middot; ' . $rider['vehicle_plate_number'];
            $detail = $riderName
                    . ($riderPhone ? "<br>&#128222; {$riderPhone}" : '')
                    . ($vehicle    ? "<br>&#127949;&#65039; {$vehicle}" : '');
            $riderBlock = _eh_infobox('Your Rider', $detail, '&#129333;');
        }

        // Delivery address
        $address = htmlspecialchars(
            ($order['recipient_address'] ?? '') . ', ' .
            ($order['city'] ?? '') . ' ' .
            ($order['postal_code'] ?? '')
        );
        $addressBlock = _eh_infobox('Delivery Address', $address, '&#128205;');

        // COD reminder
        $codNote = $isCOD
            ? "<div style='background:#fef3c7;border:1px solid #fde68a;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#92400e;'>"
              . "&#128181; <strong>Reminder:</strong> Please prepare "
              . "<strong>&#8369;" . number_format((float)$order['total_price'], 2) . "</strong> in cash for the rider upon delivery."
              . "</div>"
            : '';

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "Your order is on its way! Expect delivery today.</p>"
              . $riderBlock
              . $addressBlock
              . $codNote
              . _eh_btn('&#128230; Track My Order', $trackUrl, '#7c3aed');

        $html = _eh_wrap(
            '#7c3aed',
            '&#128757;',
            'Your Order is On the Way!',
            "Order #{$code} is out for delivery",
            $body,
            "Order #{$code} is out for delivery. Expect it today!"
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Your Order #{$code} is On the Way! — SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #3 — READY FOR PICKUP
//  Trigger: Admin clicks "Mark Ready for Pickup"
//  Covers:  Pickup orders only
//  Action button: "Order Picked-Up" → confirm_order.php?type=pickup
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_ready_for_pickup')) {
    function email_ready_for_pickup(array $order): bool {
        $name    = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code    = htmlspecialchars($order['order_code']);
        $siteUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $isCOP   = ($order['payment_method'] ?? '') === 'cop';
        $total   = number_format((float)$order['total_price'], 2);

        $payNote = $isCOP
            ? "<div style='background:#fef3c7;border:1px solid #fde68a;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#92400e;'>"
              . "&#128181; <strong>Payment Due at Pickup:</strong> Please bring "
              . "<strong>&#8369;{$total}</strong> in cash when you collect."
              . "</div>"
            : "<div style='background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#15803d;'>"
              . "&#9989; <strong>Payment already confirmed.</strong> No payment needed at pickup."
              . "</div>";

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 16px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "Your order is packed and <strong>ready to collect</strong> at our store!</p>"
              . _eh_infobox('Pickup Location',
                    'St. Joseph Fish Brokerage Inc.<br>Bulungan Ave. corner HACCP St., NFPC NBBS, Navotas, Philippines<br>'
                    . 'Hours: 8:30 AM &ndash; 5:00 PM',
                    '&#127978;')
              . _eh_infobox('Bring with you',
                    'Order Code: <strong>#' . $code . '</strong><br>Valid ID for verification',
                    '&#129482;')
              . $payNote
              . "<div style='background:#fef2f2;border:1px solid #fecaca;border-radius:10px;"
              . "padding:12px 14px;margin:0 0 8px;font-size:13px;color:#b91c1c;'>"
              . "&#9200; <strong>Important:</strong> Please collect within 24 hours or your order may be forfeited."
              . "</div>";

        $html = _eh_wrap(
            '#16a34a',
            '&#127978;',
            'Ready for Pickup!',
            "Order #{$code} is waiting for you",
            $body,
            "Order #{$code} is ready! Come collect it at our Navotas store."
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Your Order #{$code} is Ready for Pickup — SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #3b — THIRD PARTY DISPATCHED / REASSIGNED
//  Trigger: Admin assigns or reassigns a 3rd-party delivery provider
//  Covers:  Delivery orders only
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_third_party_dispatched')) {
    function email_third_party_dispatched(array $order, bool $isReassignment = false): bool {
        $name         = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code         = htmlspecialchars($order['order_code']);
        $siteUrl      = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $trackUrl     = $siteUrl . '/track.php?order_code=' . urlencode($order['order_code']);
        $provider     = htmlspecialchars($order['third_party_name'] ?? '3rd-Party Courier');
        $isCOD        = ($order['payment_method'] ?? '') === 'cod';

        // Has external tracking link?
        $externalLink = trim($order['active_delivery_link'] ?? '');
        if (empty($externalLink)) {
            $externalLink = trim($order['delivery_link'] ?? '');
        }
        // Only show external tracking if it's a real URL, not the site URL
        $siteUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        if (!empty($externalLink) && rtrim($externalLink, '/') === rtrim($siteUrl, '/')) {
            $externalLink = ''; // don't show site URL as external tracking
        }

        $trackingBlock = '';
        if (!empty($externalLink)) {
            $trackingBlock = "<div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;"
                           . "padding:14px 16px;margin-bottom:12px;text-align:center;'>"
                           . "<p style='margin:0 0 8px;font-size:12px;font-weight:700;color:#1e40af;"
                           . "text-transform:uppercase;letter-spacing:.04em;'>&#128204; External Tracking</p>"
                           . "<a href='" . htmlspecialchars($externalLink) . "' "
                           . "style='display:inline-block;background:#2563eb;color:#fff;font-size:13px;"
                           . "font-weight:700;padding:10px 24px;border-radius:10px;text-decoration:none;'>"
                           . "Track via {$provider} &rarr;</a>"
                           . "<p style='margin:8px 0 0;font-size:11px;color:#6b7280;word-break:break-all;'>"
                           . htmlspecialchars($externalLink) . "</p>"
                           . "</div>";
        }

        // COD reminder
        $codNote = $isCOD
            ? "<div style='background:#fef3c7;border:1px solid #fde68a;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#92400e;'>"
              . "&#128181; <strong>Reminder:</strong> Please prepare "
              . "<strong>&#8369;" . number_format((float)$order['total_price'], 2) . "</strong> "
              . "in cash for the courier upon delivery."
              . "</div>"
            : '';

        // Reassignment notice
        $reassignNote = $isReassignment
            ? "<div style='background:#fef3c7;border:1px solid #fde68a;border-radius:10px;"
              . "padding:12px 14px;margin-bottom:16px;font-size:13px;color:#92400e;'>"
              . "&#128260; <strong>Delivery Update:</strong> Your order has been reassigned to a new delivery provider. "
              . "Please use the updated tracking information below."
              . "</div>"
            : '';

        $headerTitle = $isReassignment ? 'Delivery Reassigned' : 'Your Order is On the Way!';
        $headerSub   = $isReassignment
            ? "Order #{$code} has a new delivery provider"
            : "Order #{$code} is being delivered by {$provider}";

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 16px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . ($isReassignment
                    ? "Your order delivery has been updated. It is now being handled by <strong>{$provider}</strong>."
                    : "Your order is on its way and being delivered by <strong>{$provider}</strong>. Expect delivery today.")
              . "</p>"
              . $reassignNote
              . (($order['order_type'] ?? 'delivery') === 'pickup'
                    ? _eh_infobox('Pickup Location',
                        'St. Joseph Fish Brokerage Inc.<br>Bulungan Ave. corner HACCP St., NFPC NBBS, Navotas, Philippines<br>Hours: 8:30 AM &ndash; 5:00 PM',
                        '&#127978;')
                    : _eh_infobox('Delivery Address',
                        htmlspecialchars(
                            trim(($order['recipient_address'] ?? '') . ', ' .
                            ($order['city'] ?? '') . ' ' .
                            ($order['postal_code'] ?? ''), ', ')
                        ), '&#128205;')
                )
              . $trackingBlock
              . $codNote

              // Always show the SJFBI internal track link
              . "<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;"
              . "padding:14px 16px;margin-bottom:16px;text-align:center;'>"
              . "<p style='margin:0 0 8px;font-size:12px;font-weight:700;color:#9ca3af;"
              . "text-transform:uppercase;letter-spacing:.04em;'>&#128230; Track on SJFBI</p>"
              . "<a href='{$trackUrl}' "
              . "style='display:inline-block;background:#f97316;color:#fff;font-size:13px;"
              . "font-weight:700;padding:10px 24px;border-radius:10px;text-decoration:none;'>"
              . "View Order Status &rarr;</a>"
              . "<p style='margin:8px 0 0;font-size:11px;color:#9ca3af;font-family:monospace;'>"
              . htmlspecialchars($trackUrl) . "</p>"
              . "</div>"

              . "<p style='margin:0;font-size:12px;color:#9ca3af;text-align:center;'>"
              . "Your order code is <strong>#{$code}</strong> &mdash; use it to track on our website anytime.</p>";

        $html = _eh_wrap(
            $isReassignment ? '#d97706' : '#7c3aed',
            $isReassignment ? '&#128260;' : '&#128666;',
            $headerTitle,
            $headerSub,
            $body,
            $isReassignment
                ? "Your order #{$code} has been reassigned to {$provider}."
                : "Order #{$code} is on its way via {$provider}."
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            $isReassignment
                ? "Delivery Update: Order #{$code} Reassigned — SJFBI"
                : "Your Order #{$code} is On the Way via {$provider} — SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #4 — ORDER DELIVERED: CONFIRM RECEIPT
//  Trigger: Rider/admin marks order as Delivered
//  Covers:  Delivery orders only
//  Action button: "Order Received" → confirm_order.php?type=delivery
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_delivered_confirm_receipt')) {
    function email_delivered_confirm_receipt(array $order, string $confirm_token): bool {
        $name       = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code       = htmlspecialchars($order['order_code']);
        $siteUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $confirmUrl = $siteUrl . '/confirm_order.php?order_id=' . (int)$order['order_id']
                    . '&token=' . urlencode($confirm_token) . '&type=delivery';

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 12px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "Your order has been marked as <strong>delivered</strong>. Did you receive it?</p>"
              . "<p style='margin:0 0 24px;font-size:13px;color:#9ca3af;'>"
              . "Please tap the button below to confirm receipt. This helps us complete your order and unlocks your review.</p>"
              . _eh_btn('&#9989; Order Received', $confirmUrl, '#16a34a')
              . _eh_confirm_fallback($confirmUrl);

        $html = _eh_wrap(
            '#16a34a',
            '&#9989;',
            'Order Delivered!',
            "Please confirm you received Order #{$code}",
            $body,
            "Order #{$code} delivered — tap to confirm receipt."
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Did you receive your order? Confirm #{$code} — SJFBI",
            $html
        );
    }
}

if (!function_exists('email_pickup_completed')) {
    function email_pickup_completed(array $order, string $review_url): bool {
        $name     = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code     = htmlspecialchars($order['order_code']);
        $siteUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $shopUrl  = $siteUrl . '/shop.php';
        $isCOP    = ($order['payment_method'] ?? '') === 'cop';
        $isOnline = !in_array(strtolower($order['payment_method'] ?? ''), ['cod', 'cop']);

        // Wording differs — COP collected cash, online already paid
        $completionNote = $isCOP
            ? "Thank you for picking up your order and completing your payment! We hope you enjoy your fresh seafood."
            : "Thank you for picking up your order! Your payment was already confirmed. We hope you enjoy your fresh seafood.";

        $paymentBadge = $isOnline
            ? "<div style='background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#15803d;'>"
              . "&#9989; <strong>Payment Confirmed</strong> &mdash; Paid online via "
              . htmlspecialchars(match(strtolower($order['payment_method'] ?? '')) {
                    'gcash'    => 'GCash',
                    'paymaya'  => 'Maya',
                    'grab_pay' => 'GrabPay',
                    'qrph'     => 'QR Ph',
                    'card'     => 'Credit / Debit Card',
                    default    => ucfirst($order['payment_method'] ?? '')
                }) . ". No payment needed at pickup."
              . "</div>"
            : "<div style='background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;"
              . "padding:12px 14px;margin:16px 0;font-size:13px;color:#15803d;'>"
              . "&#128181; <strong>Cash Payment Received</strong> &mdash; Thank you for your payment at the store."
              . "</div>";

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . $completionNote . "</p>"

              . "<div style='background:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;"
              . "padding:16px 18px;margin-bottom:16px;'>"
              . "<p style='margin:0;font-size:13px;font-weight:700;color:#15803d;'>&#127978; Order Picked Up &#10003;</p>"
              . "<p style='margin:4px 0 0;font-size:12px;color:#16a34a;'>"
              . "Order <strong>#{$code}</strong> has been successfully picked up. All done!</p>"
              . "</div>"

              . $paymentBadge
              . _eh_order_meta($order)

              . "<p style='margin:0 0 6px;font-size:14px;font-weight:700;color:#374151;'>"
              . "How was your experience?</p>"
              . "<p style='margin:0 0 16px;font-size:13px;color:#6b7280;'>"
              . "Your review helps our fishermen and helps us serve you better.</p>"
              . _eh_btn('&#11088; Leave a Review', $review_url, '#f59e0b')

              . "<div style='border-top:1px solid #f3f4f6;margin-top:20px;padding-top:20px;text-align:center;'>"
              . "<p style='margin:0 0 12px;font-size:13px;color:#6b7280;'>Order something fresh again?</p>"
              . _eh_btn('Shop Again', $shopUrl, '#f97316')
              . "</div>";

        $html = _eh_wrap(
            '#16a34a',
            '&#127881;',
            'Order Complete!',
            "Order #{$code} has been picked up successfully",
            $body,
            "Order #{$code} picked up and complete. Leave us a review!"
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Order #{$code} Complete — Thank You! | SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #5 — ORDER COMPLETED
//  Trigger: Customer clicks "Order Received" or "Order Picked-Up" on confirm_order.php
//  Covers:  Delivery AND Pickup
//  Action button: "Leave a Review" + "Shop Again"
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_order_completed')) {
    function email_order_completed(array $order, string $review_url): bool {
        $isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';
        $name     = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code     = htmlspecialchars($order['order_code']);
        $siteUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $shopUrl  = $siteUrl . '/shop.php';
        $verb     = $isPickup ? 'picked up' : 'delivered';
        $emoji    = $isPickup ? '&#127978;' : '&#9989;';

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "Thank you for confirming! We hope you enjoy your fresh seafood from SJFBI.</p>"

              . "<div style='background:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;"
              . "padding:16px 18px;margin-bottom:24px;'>"
              . "<p style='margin:0;font-size:13px;font-weight:700;color:#15803d;'>Order Completed &#10003;</p>"
              . "<p style='margin:4px 0 0;font-size:12px;color:#16a34a;'>"
              . "Order #{$code} has been successfully {$verb} and confirmed.</p>"
              . "</div>"

              . "<p style='margin:0 0 6px;font-size:14px;font-weight:700;color:#374151;'>How was your experience?</p>"
              . "<p style='margin:0 0 16px;font-size:13px;color:#6b7280;'>"
              . "Your review helps our fishermen and helps us serve you better.</p>"
              . _eh_btn('&#11088; Leave a Review', $review_url, '#f59e0b')

              . "<div style='border-top:1px solid #f3f4f6;margin-top:20px;padding-top:20px;text-align:center;'>"
              . "<p style='margin:0 0 12px;font-size:13px;color:#6b7280;'>Order something fresh again?</p>"
              . _eh_btn('🐟 Shop Again', $shopUrl, '#f97316')
              . "</div>";

        $html = _eh_wrap(
            '#16a34a',
            '&#127881;',
            'Thank You!',
            "Order #{$code} is complete",
            $body,
            "Thank you! Order #{$code} is complete. Leave a review!"
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Order #{$code} Complete — Thank You! | SJFBI",
            $html
        );
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  EMAIL #6 — ORDER CANCELLED
//  Trigger: Admin cancels order
//  Covers:  All order types and payment methods
// ════════════════════════════════════════════════════════════════════════════

if (!function_exists('email_order_cancelled')) {
    function email_order_cancelled(array $order, string $reason): bool {
        $name    = htmlspecialchars($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
        $code    = htmlspecialchars($order['order_code']);
        $siteUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
        $shopUrl = $siteUrl . '/shop.php';

        $methods = [
            'cod' => 'Cash on Delivery', 'cop' => 'Cash on Pickup',
            'gcash' => 'GCash', 'paymaya' => 'Maya', 'grab_pay' => 'GrabPay',
            'qrph' => 'QR Ph', 'card' => 'Credit / Debit Card',
        ];
        $payMethod   = $methods[strtolower($order['payment_method'] ?? '')] ?? ucfirst($order['payment_method'] ?? '—');
        $isOnline    = !in_array(strtolower($order['payment_method'] ?? ''), ['cod', 'cop']);
        $refundNote  = $isOnline
            ? "<p style='margin:16px 0;font-size:13px;color:#6b7280;'>"
              . "&#128197; Since you paid online, a refund will be processed within <strong>5&ndash;7 business days</strong> to your original payment method.</p>"
            : '';

        $body = "<p style='margin:0 0 6px;font-size:15px;color:#374151;'>Hi <strong>{$name}</strong>,</p>"
              . "<p style='margin:0 0 16px;font-size:14px;color:#6b7280;line-height:1.65;'>"
              . "We're sorry, but your order has been cancelled.</p>"

              . "<div style='background:#fef2f2;border:1px solid #fecaca;border-radius:12px;"
              . "padding:14px 16px;margin-bottom:20px;'>"
              . "<p style='margin:0 0 4px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;'>Reason</p>"
              . "<p style='margin:0;font-size:13px;color:#b91c1c;'>" . htmlspecialchars($reason) . "</p>"
              . "</div>"

              . "<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;"
              . "padding:14px 16px;margin-bottom:20px;'>"
              . "<p style='margin:0 0 8px;font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;'>Order Summary</p>"
              . "<table width='100%' cellpadding='0' cellspacing='0'>"
              . "<tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Order Code</td>"
              . "<td style='font-size:13px;font-weight:700;color:#111827;text-align:right;'>#" . $code . "</td></tr>"
              . "<tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Payment</td>"
              . "<td style='font-size:13px;font-weight:600;color:#111827;text-align:right;'>" . $payMethod . "</td></tr>"
              . "<tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Total</td>"
              . "<td style='font-size:13px;font-weight:700;color:#111827;text-align:right;'>&#8369;" . number_format((float)$order['total_price'], 2) . "</td></tr>"
              . "</table></div>"

              . $refundNote

              . "<p style='margin:0 0 20px;font-size:13px;color:#374151;'>"
              . "Questions? Contact us at <a href='mailto:" . ($_ENV['MAIL_USERNAME'] ?? '') . "' "
              . "style='color:#f97316;'>" . ($_ENV['MAIL_USERNAME'] ?? 'support@fishbrokers.net') . "</a></p>"

              . _eh_btn('&#128  fish; Browse Fresh Seafood', $shopUrl, '#f97316');

        $html = _eh_wrap(
            '#dc2626',
            '&#128683;',
            'Order Cancelled',
            "Order #{$code} has been cancelled",
            $body,
            "Your order #{$code} has been cancelled."
        );

        return sjfbi_send_email(
            $order['recipient_email'],
            $order['recipient_first_name'] . ' ' . $order['recipient_last_name'],
            "Order #{$code} Cancelled — SJFBI",
            $html
        );
    }
}