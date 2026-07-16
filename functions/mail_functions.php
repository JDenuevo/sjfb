<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendEmail($to, $subject, $message, $isHtml = false) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = trim($_ENV['MAIL_PASSWORD'], '"\' ');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;

        $mail->setFrom($_ENV['MAIL_USERNAME'], 'St. Joseph Fish Brokerage Inc.');
        $mail->addAddress($to);

        $mail->isHTML($isHtml);
        $mail->CharSet = 'UTF-8';  // ← add this line
        $mail->Subject = $mail->encodeHeader($subject);
        $mail->Body    = $message;

        if (!$isHtml) {
            $mail->AltBody = strip_tags($message);
        }

        if (!$mail->send()) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }

        error_log("Email successfully sent to: $to");
        return true;
    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  EMAIL — OTP Password Reset
//  Called from functions/update.php sendOTP()
// ─────────────────────────────────────────────────────────────────────────────

function sendOTPEmail(string $to, string $otp): bool {
    $subject = "Your Password Reset OTP — St. Joseph Fish Brokerage Inc.";

    $body = _emailHead() . "

      <!-- Header -->
      <div style='background:linear-gradient(135deg,#ea580c,#f97316);padding:32px;text-align:center;'>
        <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>Password Reset</h1>
        <p style='margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px;'>
          Your one-time password (OTP) code
        </p>
      </div>

      <div class='content'>
        <p style='font-size:14px;color:#374151;line-height:1.65;'>
          You requested a password reset for your SJFBI account. Use the OTP code below to proceed.
        </p>

        <!-- OTP Code Box -->
        <div style='margin:24px 0;padding:24px;background:#fff7ed;border:2px dashed #fed7aa;
                    border-radius:12px;text-align:center;'>
          <p style='margin:0 0 8px;font-size:12px;font-weight:700;color:#9ca3af;
                    text-transform:uppercase;letter-spacing:.08em;'>Your OTP Code</p>
          <div style='font-size:40px;font-weight:800;letter-spacing:12px;color:#ea580c;
                      font-family:monospace;line-height:1;'>
            <?= htmlspecialchars($otp) ?>
          </div>
          <p style='margin:12px 0 0;font-size:12px;color:#9ca3af;'>
            &#9200; This code expires in <strong>15 minutes</strong>
          </p>
        </div>

        <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:10px;
                    padding:12px 14px;margin-bottom:20px;font-size:13px;color:#b91c1c;'>
          &#128274; If you did not request a password reset, please ignore this email.
          Your account is safe and no changes have been made.
        </div>

        <p style='font-size:13px;color:#6b7280;line-height:1.65;'>
          Do not share this OTP with anyone. St. Joseph Fish Brokerage Inc. will never ask for your OTP.
        </p>
      </div>

    " . _emailFoot();

    // Replace PHP tag with actual OTP value
    $body = str_replace('<div style="font-size:40px;font-weight:800;letter-spacing:12px;color:#ea580c; font-family:monospace;line-height:1;">' . htmlspecialchars($otp) . '</div>', htmlspecialchars($otp), $body);

    return sendEmail($to, $subject, $body, true);
}

function sendVerificationEmail(string $to, string $username, string $verifyLink): bool {
    $subject = "Verify your St. Joseph Fish Brokerage account";
 
    $safeUsername = htmlspecialchars($username);
    $safeLink     = htmlspecialchars($verifyLink);
 
    $body = _emailHead() . "
 
      <!-- Header -->
      <div style='background:linear-gradient(135deg,#ea580c,#f97316);padding:32px;text-align:center;'>
        <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>Verify Your Email</h1>
        <p style='margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px;'>
          One more step to activate your account
        </p>
      </div>
 
      <div class='content'>
        <p style='font-size:14px;color:#374151;line-height:1.65;'>
          Hi {$safeUsername}, thanks for registering with St. Joseph Fish Brokerage Inc.
          Please confirm your email address to activate your account.
        </p>
 
        <!-- CTA Button -->
        <div style='text-align:center;margin:28px 0;'>
          <a href='{$safeLink}'
             style='background:#ea580c;color:#fff;text-decoration:none;padding:14px 32px;
                    border-radius:8px;display:inline-block;font-weight:700;font-size:14px;'>
            Verify Email
          </a>
        </div>
 
        <p style='margin:0 0 8px;font-size:12px;color:#9ca3af;text-align:center;'>
          &#9200; This link expires in <strong>24 hours</strong>
        </p>
 
        <div style='background:#fef2f2;border:1px solid #fecaca;border-radius:10px;
                    padding:12px 14px;margin:20px 0;font-size:13px;color:#b91c1c;'>
          &#128274; If you didn't create this account, you can safely ignore this email.
        </div>
 
        <p style='font-size:13px;color:#6b7280;line-height:1.65;'>
          Having trouble with the button? Copy and paste this link into your browser:<br>
          <span style='word-break:break-all;color:#ea580c;'>{$safeLink}</span>
        </p>
      </div>
 
    " . _emailFoot();
 
    return sendEmail($to, $subject, $body, true);
}

// ─────────────────────────────────────────────────────────────────────────────
//  DB HELPERS
// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('getOrderDetails')) {
    function getOrderDetails(int $orderId, $conn): ?array {
        $stmt = $conn->prepare("
            SELECT o.*,
                   COALESCE(a.account_first_name, o.recipient_first_name) AS customer_first_name,
                   COALESCE(a.account_last_name,  o.recipient_last_name)  AS customer_last_name,
                   COALESCE(a.account_email,      o.recipient_email)      AS customer_email
            FROM orders o
            LEFT JOIN accounts a ON o.account_id = a.account_id
            WHERE o.order_id = ?
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  EMAIL TEMPLATES — shared header/footer snippets
// ─────────────────────────────────────────────────────────────────────────────

function _emailHead(string $extraStyle = ''): string {
    return "<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <style>
    body{margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
    .content{padding:28px 32px;}
    .details-box{background:#f8f9fa;border-radius:8px;padding:16px;margin:16px 0;}
    .items-table{width:100%;border-collapse:collapse;margin:16px 0;}
    .items-table th,.items-table td{border:1px solid #e5e7eb;padding:9px 10px;text-align:left;font-size:13px;}
    .items-table th{background:#f3f4f6;font-weight:700;color:#374151;}
    .footer{padding:20px 32px;text-align:center;border-top:1px solid #f3f4f6;}
    .footer p{font-size:12px;color:#9ca3af;margin:0;}
    {$extraStyle}
  </style>
</head>
<body><div class='wrap'>";
}

function _emailFoot(): string {
    return "<div class='footer'>
      <p>This is an automated email. Please do not reply to this message.</p>
      <p style='margin-top:6px;'>&copy; " . date('Y') . " St. Joseph Fish Brokerage Inc.</p>
    </div>
  </div>
</body></html>";
}

// ─────────────────────────────────────────────────────────────────────────────
//  EMAIL 1 — Payment / Order Confirmation
//  Sent for all payment methods after a successful order.
// ─────────────────────────────────────────────────────────────────────────────

function sendPaymentConfirmationEmail(int $orderId, string $confirmToken = ''): bool {
    global $conn;

    try {
        $order = getOrderDetails($orderId, $conn);

        if (!$order) {
            error_log("sendPaymentConfirmationEmail: order $orderId not found");
            return false;
        }

        $items         = getOrderItems($orderId, $conn);
        $isPickup      = ($order['order_type'] ?? 'delivery') === 'pickup';
        $paymentMethod = strtolower($order['payment_method'] ?? '');
        $isCash        = in_array($paymentMethod, ['cod', 'cop']);
        $isCOP         = $paymentMethod === 'cop';
        $isOnline      = !$isCash;

        // ── Dynamic subject, title, subtitle, closing note ────────────────
        if ($isCash && $isPickup) {
            $subject     = "Order Confirmed — #" . $order['order_code'];
            $headerTitle = "Order Confirmed!";
            $headerSub   = "Your pickup order has been received.";
            $headerColor = "#ea580c";
            $closingNote = "Your order is being prepared for store pickup. We will notify you once it's ready to collect.";
        } elseif ($isCash && !$isPickup) {
            $subject     = "Order Confirmed — #" . $order['order_code'];
            $headerTitle = "Order Confirmed!";
            $headerSub   = "Your order has been received and will be delivered.";
            $headerColor = "#ea580c";
            $closingNote = "Your order will be prepared and delivered to your address. Payment is collected upon delivery.";
        } else {
            $subject     = "Payment Confirmed — #" . $order['order_code'];
            $headerTitle = "Payment Confirmed!";
            $headerSub   = "Your payment has been successfully processed.";
            $headerColor = "#2563eb";

            $closingNote = $isPickup
                ? "Your order is being prepared for store pickup. We will notify you once it's ready to collect."
                : "Your order will be processed and prepared for delivery. You will receive another email with tracking information once your order ships.";
        }

        $methodLabels = [
            'cod'      => 'Cash on Delivery',
            'cop'      => 'Cash on Pickup',
            'gcash'    => 'GCash',
            'paymaya'  => 'Maya',
            'grab_pay' => 'GrabPay',
            'card'     => 'Credit / Debit Card',
            'qrph'     => 'QR Ph',
        ];

        $methodLabel = $methodLabels[$paymentMethod] ?? ucfirst($paymentMethod);

        // ── Item rows ─────────────────────────────────────────────────────
        $itemRows = '';

        foreach ($items as $item) {
            $variant   = htmlspecialchars($item['variant_name'] ?? 'Standard');
            $lineTotal = (float)$item['quantity'] * (float)$item['price'];

            $itemRows .= "
              <tr>
                <td>" . htmlspecialchars($item['product_name']) . "</td>
                <td>{$variant}</td>
                <td>" . (int)$item['quantity'] . "</td>
                <td>&#8369;" . number_format((float)$item['price'], 2) . "</td>
                <td>&#8369;" . number_format($lineTotal, 2) . "</td>
              </tr>";
        }

        // ── Totals breakdown ──────────────────────────────────────────────
        $subtotal  = (float)($order['subtotal']        ?? $order['total_price'] ?? 0);
        $delivery  = (float)($order['delivery_fee']    ?? 0);
        $discount  = (float)($order['discount_amount'] ?? 0);
        $total     = (float)($order['total_price']     ?? 0);

        $totalsRows = "
          <tr>
            <td style='padding:7px 0;font-size:13px;color:#6b7280;'>Subtotal</td>
            <td style='padding:7px 0;font-size:13px;font-weight:600;color:#374151;text-align:right;'>
              &#8369;" . number_format($subtotal, 2) . "
            </td>
          </tr>";

        if ($discount > 0) {
            $totalsRows .= "
          <tr>
            <td style='padding:7px 0;font-size:13px;color:#16a34a;'>Discount</td>
            <td style='padding:7px 0;font-size:13px;font-weight:600;color:#16a34a;text-align:right;'>
              -&#8369;" . number_format($discount, 2) . "
            </td>
          </tr>";
        }

        $totalsRows .= $isPickup
          ? "
          <tr>
            <td style='padding:7px 0;font-size:13px;color:#6b7280;'>Delivery Fee</td>
            <td style='padding:7px 0;font-size:13px;font-weight:600;color:#16a34a;text-align:right;'>
              FREE (Pickup)
            </td>
          </tr>"
            : "
          <tr>
            <td style='padding:7px 0;font-size:13px;color:#6b7280;'>Delivery Fee</td>
            <td style='padding:7px 0;font-size:13px;font-weight:600;color:#374151;text-align:right;'>
              &#8369;" . number_format($delivery, 2) . "
            </td>
          </tr>";

        $totalsRows .= "
          <tr style='border-top:2px solid #e5e7eb;'>
            <td style='padding:10px 0;font-size:15px;font-weight:800;color:#111827;'>Total</td>
            <td style='padding:10px 0;font-size:15px;font-weight:800;color:{$headerColor};text-align:right;'>
              &#8369;" . number_format($total, 2) . "
            </td>
          </tr>";

        $totalsBlock = "
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='border-top:1px solid #e5e7eb;margin-top:8px;margin-bottom:20px;'>
            {$totalsRows}
          </table>";

        // ── Delivery / pickup block ───────────────────────────────────────
        if ($isPickup) {

            $deliveryTitle = 'Pickup Information';

            $deliveryBlock = "
              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Order Type:</strong> Store Pickup
              </p>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Payment:</strong> {$methodLabel}"
              . ($isOnline ? " &mdash; <span style='color:#16a34a;font-weight:600;'>Paid Online &#10003;</span>" : "")
              . "
              </p>

              <div style='margin:12px 0 0;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;'>
                <p style='margin:0 0 4px;font-size:12px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em;'>
                  &#127978; Pickup Location
                </p>

                <p style='margin:0;font-size:13px;color:#1e40af;line-height:1.6;'>
                  St. Joseph Fish Brokerage Inc.<br>
                  Navotas Fish Port, Metro Manila<br>
                  Hours: 8:30 AM &ndash; 5:00 PM
                </p>
              </div>

              <p style='margin:10px 0 0;font-size:13px;color:#6b7280;line-height:1.65;'>
                Please bring this email or your order code
                <strong>#" . $order['order_code'] . "</strong>
                and a valid ID when you collect your order.
              </p>";

        } else {

            $deliveryTitle = 'Delivery Information';

            $deliveryBlock = "
              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Delivery Address:</strong><br>
                " . htmlspecialchars($order['recipient_address'] ?? '') . "<br>
                " . htmlspecialchars($order['city'] ?? '') . ", " . htmlspecialchars($order['postal_code'] ?? '') . "
              </p>

              <p style='margin:8px 0 0;font-size:13px;color:#374151;'>
                <strong>Contact Number:</strong>
                " . htmlspecialchars($order['recipient_phone'] ?? '') . "
              </p>";
        }

        // ── COP confirm button ────────────────────────────────────────────
        $confirmBlock = '';

        if ($isCOP && $isPickup && !empty($confirmToken)) {

            $siteUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');

            $confirmUrl = $siteUrl
                        . '/confirm_order.php?order_id=' . (int)$order['order_id']
                        . '&token=' . urlencode($confirmToken)
                        . '&type=pickup';

            $confirmBlock = "
              <div style='margin:24px 0;padding:20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;text-align:center;'>

                <p style='margin:0 0 6px;font-size:14px;font-weight:700;color:#15803d;'>
                  &#9989; Confirm Your Order
                </p>

                <p style='margin:0 0 16px;font-size:13px;color:#6b7280;line-height:1.65;'>
                  Please tap the button below to confirm your order.
                  This lets us know you've acknowledged your pickup order.
                </p>

                <a href='" . htmlspecialchars($confirmUrl) . "'
                   style='display:inline-block;background:#16a34a;color:#fff;font-size:15px;font-weight:800;
                          padding:14px 36px;border-radius:12px;text-decoration:none;
                          box-shadow:0 4px 14px rgba(22,163,74,.25);'>
                  &#9989; Confirm Order
                </a>

                <p style='margin:12px 0 0;font-size:11px;color:#9ca3af;'>
                  This link expires in 48 hours.
                </p>

                <div style='margin-top:12px;padding:10px;background:#f9fafb;border-radius:8px;'>
                  <p style='margin:0 0 4px;font-size:11px;color:#9ca3af;'>
                    Button not working? Copy this link:
                  </p>

                  <span style='font-size:10px;color:#ea580c;word-break:break-all;font-family:monospace;'>"
                  . htmlspecialchars($confirmUrl) . "
                  </span>
                </div>

              </div>";
        }

        $body = _emailHead() . "

          <!-- Header -->
          <div style='background:{$headerColor};padding:32px;text-align:center;'>

            <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>
              {$headerTitle}
            </h1>

            <p style='margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px;'>
              {$headerSub}
            </p>

            <p style='margin:6px 0 0;color:rgba(255,255,255,.7);font-size:13px;'>
              Thank you,
              " . htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']) . "
            </p>

          </div>

          <div class='content'>

            <div class='details-box'>

              <h3 style='margin:0 0 10px;font-size:14px;font-weight:700;color:#111827;'>
                Order Details
              </h3>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Order Code:</strong>
                #" . $order['order_code'] . "
              </p>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Order Date:</strong>
                " . date('F j, Y', strtotime($order['order_date'])) . "
              </p>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Order Type:</strong>
                " . ($isPickup ? 'Store Pickup' : 'Delivery') . "
              </p>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Payment Method:</strong>
                {$methodLabel}
              </p>

              <p style='margin:4px 0;font-size:13px;color:#374151;'>
                <strong>Order Status:</strong>
                " . htmlspecialchars($order['order_status']) . "
              </p>

            </div>

            <h3 style='font-size:14px;font-weight:700;color:#111827;margin:20px 0 4px;'>
              Items Ordered
            </h3>

            <table class='items-table'>
              <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
              </tr>

              {$itemRows}
            </table>

            {$totalsBlock}

            <h3 style='font-size:14px;font-weight:700;color:#111827;margin:0 0 4px;'>
              {$deliveryTitle}
            </h3>

            <div class='details-box'>
              {$deliveryBlock}
            </div>

            {$confirmBlock}

            <p style='font-size:13px;color:#6b7280;line-height:1.65;'>
              {$closingNote}
            </p>

            <p style='font-size:13px;color:#374151;'>
              Thank you for choosing St. Joseph Fish Brokerage Inc.!
            </p>

          </div>

        " . _emailFoot();

        return sendEmail($order['customer_email'], $subject, $body, true);

    } catch (Exception $e) {

        error_log("sendPaymentConfirmationEmail error: " . $e->getMessage());

        return false;
    }
}
// ─────────────────────────────────────────────────────────────────────────────
//  EMAIL 2 — COD / COP Confirm-Order Action Button
//  Sent alongside the receipt for cash orders so the customer can confirm
//  receipt/pickup via a one-click link.
// ─────────────────────────────────────────────────────────────────────────────

function email_cod_confirm_order(array $order, string $confirmUrl): bool {
    $isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';
    $isCOP    = ($order['payment_method'] ?? '') === 'cop';

    // For COP pickup — no confirm button needed at order placement
    if ($isPickup && $isCOP) {
        return false; // ← just return, don't send anything
    }

    // COD delivery only — confirm button makes sense here
    $firstName   = htmlspecialchars($order['recipient_first_name'] ?? $order['customer_first_name'] ?? '');
    $orderCode   = htmlspecialchars($order['order_code']);
    $payLabel    = 'Cash on Delivery';
    $actionLabel = 'Confirm Order';
    $actionIcon  = '&#9989;';
    $orderType   = 'Delivery';   // ← add this
    $introLine   = "Your order is on its way. Once you receive your items and complete payment, please click the button below to confirm.";
    $total       = number_format((float)($order['total_price'] ?? $order['total_amount'] ?? 0), 2);
    $subject     = "Confirm Your Order #{$orderCode} — SJFBI";
    $safeUrl     = htmlspecialchars($confirmUrl);

    $body = _emailHead() . "

      <!-- Header -->
      <div style='background:linear-gradient(135deg,#ea580c,#f97316);padding:36px 32px;text-align:center;'>
        <div style='font-size:48px;margin-bottom:12px;'>{$actionIcon}</div>
        <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>Order Received!</h1>
        <p style='margin:8px 0 0;color:#fed7aa;font-size:14px;'>#{$orderCode} &nbsp;&middot;&nbsp; {$payLabel}</p>
      </div>

      <div class='content'>

        <!-- Greeting -->
        <p style='margin:0 0 6px;font-size:16px;font-weight:700;color:#111827;'>Hi {$firstName},</p>
        <p style='margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.65;'>
          Thank you for your order! {$introLine}
        </p>

        <!-- CTA -->
        <div style='text-align:center;margin:0 0 24px;'>
          <a href='{$safeUrl}'
             style='display:inline-block;background:#16a34a;color:#fff;font-size:16px;font-weight:800;
                    padding:16px 40px;border-radius:12px;text-decoration:none;'>
            {$actionIcon} {$actionLabel}
          </a>
          <p style='margin:12px 0 0;font-size:12px;color:#9ca3af;'>
            This link expires in 48 hours. If you did not place this order, please ignore this email.
          </p>
        </div>

        <hr style='border:none;border-top:1px solid #f3f4f6;margin:0 0 20px;'>

        <!-- Order summary -->
        <h3 style='margin:0 0 12px;font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;'>Order Summary</h3>
        <table width='100%' cellpadding='0' cellspacing='0'>
          <tr>
            <td style='font-size:13px;color:#6b7280;padding:5px 0;'>Order Code</td>
            <td style='font-size:13px;color:#111827;font-weight:700;text-align:right;'>#{$orderCode}</td>
          </tr>
          <tr>
            <td style='font-size:13px;color:#6b7280;padding:5px 0;'>Payment Method</td>
            <td style='font-size:13px;color:#111827;font-weight:600;text-align:right;'>{$payLabel}</td>
          </tr>
          <tr>
            <td style='font-size:13px;color:#6b7280;padding:5px 0;'>Order Type</td>
            <td style='font-size:13px;color:#111827;font-weight:600;text-align:right;'>{$orderType}</td>
          </tr>
          <tr style='border-top:2px solid #f3f4f6;'>
            <td style='font-size:14px;font-weight:800;color:#111827;padding:12px 0 0;'>Total Amount</td>
            <td style='font-size:14px;font-weight:800;color:#ea580c;text-align:right;padding:12px 0 0;'>&#8369;{$total}</td>
          </tr>
        </table>

        <!-- Fallback link -->
        <div style='margin-top:24px;padding:14px;background:#f9fafb;border-radius:8px;text-align:center;'>
          <p style='margin:0 0 6px;font-size:12px;color:#9ca3af;'>Having trouble with the button? Copy and paste this link:</p>
          <span style='font-size:11px;color:#ea580c;word-break:break-all;'>{$safeUrl}</span>
        </div>

      </div>

    " . _emailFoot();

    $email = $order['recipient_email'] ?? $order['customer_email'] ?? '';
    return sendEmail($email, $subject, $body, true);
}

// ─────────────────────────────────────────────────────────────────────────────
//  EMAIL 3 — Payment Failed
//  Optionally sent when an online payment is cancelled or fails.
// ─────────────────────────────────────────────────────────────────────────────

function sendPaymentFailedEmail(int $orderId): bool {
    global $conn;
    try {
        $order = getOrderDetails($orderId, $conn);
        if (!$order) {
            error_log("sendPaymentFailedEmail: order $orderId not found");
            return false;
        }

        $subject = "Payment Failed — Order #" . $order['order_code'];

        $body = _emailHead() . "

          <!-- Header -->
          <div style='background:#dc2626;padding:32px;text-align:center;'>
            <h1 style='margin:0;color:#fff;font-size:22px;font-weight:800;'>Payment Failed</h1>
            <p style='margin:8px 0 0;color:#fecaca;font-size:14px;'>Order #" . $order['order_code'] . "</p>
          </div>

          <div class='content'>
            <p style='font-size:14px;color:#374151;'>
              Dear " . htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']) . ",
            </p>
            <p style='font-size:14px;color:#6b7280;line-height:1.65;'>
              We were unable to process your payment for Order #" . $order['order_code'] . ". This could be due to:
            </p>
            <ul style='font-size:14px;color:#6b7280;line-height:1.8;padding-left:20px;'>
              <li>Insufficient funds</li>
              <li>Expired payment method</li>
              <li>Network connectivity issues</li>
              <li>Payment method declined by your bank</li>
            </ul>

            <div class='details-box'>
              <h3 style='margin:0 0 10px;font-size:14px;font-weight:700;color:#111827;'>Order Details</h3>
              <p style='margin:4px 0;font-size:13px;color:#374151;'><strong>Order Code:</strong> #" . $order['order_code'] . "</p>
              <p style='margin:4px 0;font-size:13px;color:#374151;'><strong>Order Date:</strong> " . date('F j, Y', strtotime($order['order_date'])) . "</p>
              <p style='margin:4px 0;font-size:13px;color:#374151;'><strong>Total Amount:</strong> &#8369;" . number_format((float)$order['total_price'], 2) . "</p>
              <p style='margin:4px 0;font-size:13px;color:#374151;'><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>
            </div>

            <p style='font-size:14px;font-weight:700;color:#111827;margin:20px 0 6px;'>What's Next?</p>
            <ul style='font-size:14px;color:#6b7280;line-height:1.8;padding-left:20px;'>
              <li>Try paying again with the same payment method</li>
              <li>Use a different payment method</li>
              <li>Contact your bank to ensure the payment method is working</li>
              <li>Contact us for assistance</li>
            </ul>

            <p style='font-size:14px;color:#374151;margin-top:20px;'>
              If you need help, reach us at:<br>
              <strong>Email:</strong> " . ($_ENV['MAIL_FROM'] ?? '') . "<br>
              <strong>Phone:</strong> [Your Phone Number]
            </p>
          </div>

        " . _emailFoot();

        return sendEmail($order['customer_email'], $subject, $body, true);

    } catch (Exception $e) {
        error_log("sendPaymentFailedEmail error: " . $e->getMessage());
        return false;
    }
}