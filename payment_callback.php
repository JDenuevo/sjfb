<?php
// payment_callback.php
session_start();
require_once 'conn.php';
require_once 'functions/paymongo_helper.php';
require_once 'functions/activity_log_helper.php';
require_once 'functions/order_helper.php';
require_once 'functions/discount_helper.php';

require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_ENV['PAYMONGO_SECRET_KEY']) || empty($_ENV['PAYMONGO_SECRET_KEY'])) {
    error_log("PAYMONGO_SECRET_KEY is not set or empty");
    header("Location: checkout.php?error=payment_failed");
    exit();
}

$tempRef = $_GET['ref']    ?? null;
$status  = $_GET['status'] ?? null;

error_log("=== PAYMENT CALLBACK ===");
error_log("ref=$tempRef status=$status");
error_log("Session temp_ref=" . ($_SESSION['temp_checkout_ref'] ?? 'none'));

// ── Verify reference ──────────────────────────────────────────────────────
if (!$tempRef || !isset($_SESSION['temp_checkout_ref']) || $_SESSION['temp_checkout_ref'] !== $tempRef) {
    error_log("Invalid reference mismatch");
    header("Location: checkout.php?error=invalid_session");
    exit();
}

// ── Get pending checkout data ─────────────────────────────────────────────
$cd = $_SESSION['pending_checkout'] ?? null;
if (!$cd) {
    error_log("No pending checkout data");
    header("Location: checkout.php?error=no_data");
    exit();
}

if ($status !== 'success') {
    error_log("Payment cancelled/failed");
    // Optionally notify the customer
    if ($cd && isset($cd['order_id'])) {
        require_once 'functions/mail_functions.php';
        sendPaymentFailedEmail($cd['order_id']);
    }
    unset($_SESSION['temp_checkout_ref'], $_SESSION['paymongo_session_id']);
    header("Location: checkout.php?cancel=1");
    exit();
}

// ── Payment success — create order ────────────────────────────────────────
error_log("Payment success — creating order");

$conn->begin_transaction();

try {
    $accountId = $cd['account_id'] ?? null;
    $isGuest   = $accountId ? 0 : 1;
    $orderCode = generateOrderCode();

    // Pull server-verified pricing saved by add.php
    // These were already server-validated before the PayMongo session was created
    $serverSubtotal  = (float)($cd['subtotal']        ?? 0);
    $deliveryFee     = (float)($cd['delivery_fee']    ?? 0);
    $discountAmount  = (float)($cd['discount_amount'] ?? 0);
    $voucherCode     = trim($cd['voucher_code']       ?? '');
    $totalAmount     = (float)($cd['total_amount']    ?? 0);

    // ── Re-verify delivery fee (guard against session tampering) ─────────
    $isPickup = ($cd['order_type'] ?? 'delivery') === 'pickup';

    if ($isPickup) {
        // Pickup orders always have zero delivery fee — never call getDeliveryFee()
        // which would return 250 fallback for empty city
        if ($deliveryFee !== 0.00) {
            error_log("Callback: pickup order had non-zero delivery fee {$deliveryFee} in session — forcing 0.");
            $deliveryFee = 0.00;
            $totalAmount = round($serverSubtotal - $discountAmount, 2);
        }
    } else {
        $serverDeliveryFee = round(getDeliveryFee($cd['city'], $serverSubtotal, $conn), 2);
        if (abs($serverDeliveryFee - $deliveryFee) > 1.00) {
            error_log("Callback delivery fee mismatch — session: {$deliveryFee}, server: {$serverDeliveryFee}. Using server.");
            $deliveryFee = $serverDeliveryFee;
            $totalAmount = round($serverSubtotal - $discountAmount + $deliveryFee, 2);
        }
    }

    // ── Resolve voucher_id from code ──────────────────────────────────────
    $verifiedVoucherId = null;
    if (!empty($voucherCode)) {
        $vStmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ? AND is_active = 1 LIMIT 1");
        $vStmt->bind_param('s', $voucherCode);
        $vStmt->execute();
        $vRow = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();
        if ($vRow) $verifiedVoucherId = (int)$vRow['voucher_id'];
    }

    // ── Resolve promotion_id (same logic as add.php) ──────────────────────
    // The promotion was already validated and applied in add.php.
    // We just need to find the same promotion to record the FK.
    $verifiedPromoId = null;
    $userGroups = getUserGroups($accountId, $conn);

    // Check if a stackable voucher allows promo stacking, or no voucher was used
    $voucherIsStackable = false;
    if ($verifiedVoucherId) {
        $ckStack = $conn->prepare("SELECT toggle_stackable FROM vouchers WHERE voucher_id = ? LIMIT 1");
        $ckStack->bind_param('i', $verifiedVoucherId);
        $ckStack->execute();
        $stackRow = $ckStack->get_result()->fetch_assoc();
        $ckStack->close();
        $voucherIsStackable = !empty($stackRow['toggle_stackable']);
    }

    if ($verifiedVoucherId === null || $voucherIsStackable) {
        $promoStmt = $conn->prepare("
            SELECT p.promotion_id
            FROM promotions p
            WHERE p.is_active         = 1
              AND p.toggle_auto_apply = 1
              AND NOW() BETWEEN p.start_date AND p.end_date
              AND p.minimum_order     <= ?
              AND (
                  p.applicable_to = 'all'
                  OR (
                      p.applicable_to = 'specific_groups'
                      AND EXISTS (
                          SELECT 1 FROM promotion_groups pg
                          JOIN account_groups ag ON ag.group_id = pg.group_id
                          WHERE pg.promotion_id = p.promotion_id
                            AND ag.account_id   = ?
                      )
                  )
              )
            ORDER BY p.discount_value DESC
            LIMIT 1
        ");
        $promoStmt->bind_param('di', $serverSubtotal, $accountId);
        $promoStmt->execute();
        $promoRow = $promoStmt->get_result()->fetch_assoc();
        $promoStmt->close();
        if ($promoRow) $verifiedPromoId = (int)$promoRow['promotion_id'];
    }

    // ── Insert order ──────────────────────────────────────────────────────
    // Matches add.php INSERT exactly — same columns, same bind_param
    // "isssssssdddsiidsiss" (19 params)
    //   account_id(i) email(s) phone(s) first(s) last(s)
    //   address(s) postal(s) city(s)
    //   subtotal(d) delivery_fee(d) discount(d) voucher_code(s)
    //   voucher_id(i) promotion_id(i)
    //   total_price(d) payment_method(s)
    //   is_guest(i) order_code(s) delivery_notes(s)
    $stmt = $conn->prepare("
        INSERT INTO orders (
            account_id,
            recipient_email, recipient_phone,
            recipient_first_name, recipient_last_name,
            recipient_address, postal_code, city,
            subtotal, delivery_fee, discount_amount, voucher_code, voucher_id, promotion_id,
            total_price, payment_method,
            is_guest_order, order_code, delivery_notes,
            order_type,
            order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid')
    ");
    $orderType = $cd['order_type'] ?? 'delivery';
    $stmt->bind_param("isssssssdddsiidsisss",  // ← was "isssssssdddsiidsiss" (19), now 20
        $accountId,
        $cd['email'],
        $cd['phone_number'],
        $cd['first_name'],
        $cd['last_name'],
        $cd['address'],
        $cd['postal_code'],
        $cd['city'],
        $serverSubtotal,
        $deliveryFee,
        $discountAmount,
        $voucherCode,
        $verifiedVoucherId,
        $verifiedPromoId,
        $totalAmount,
        $cd['payment_method'],
        $isGuest,
        $orderCode,
        $cd['delivery_notes'],
        $orderType              // ← 20th param
    );
    if (!$stmt->execute()) throw new Exception("Failed to create order: " . $conn->error);
    $orderId = $conn->insert_id;
    $stmt->close();

    // ── Insert order items + deduct stock ─────────────────────────────────
    $itemStmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)"
    );
    $itemSummary = [];

    foreach ($cd['cart'] as $item) {
        $chk = $conn->prepare(
            "SELECT variant_id FROM product_variants WHERE variant_id = ? AND stock_status = 'In Stock' LIMIT 1"
        );
        $chk->bind_param("i", $item['variant_id']);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $chk->close();
            throw new Exception("Item \"{$item['product_name']}\" became unavailable during payment.");
        }
        $chk->close();

        $itemStmt->bind_param("iiiid",
            $orderId, $item['product_id'], $item['variant_id'],
            $item['quantity'], $item['price']
        );
        if (!$itemStmt->execute()) throw new Exception("Failed to insert order item: " . $conn->error);

        $stock = $conn->prepare("
            UPDATE product_variants
            SET stock_quantity = stock_quantity - ?,
                stock_status   = IF(stock_quantity - ? <= 0, 'Out of Stock', 'In Stock')
            WHERE variant_id = ? AND stock_quantity >= ?
        ");
        $stock->bind_param("didi", $item['quantity'], $item['quantity'], $item['variant_id'], $item['quantity']);
        if (!$stock->execute() || $stock->affected_rows === 0)
            throw new Exception("Stock deduction failed for \"{$item['product_name']}\".");
        $stock->close();

        $itemSummary[] = "{$item['product_name']} x{$item['quantity']}";
    }
    $itemStmt->close();

    // ── Record voucher usage ──────────────────────────────────────────────
    if ($verifiedVoucherId && $discountAmount > 0) {
        $vuStmt = $conn->prepare(
            "INSERT INTO voucher_usage (voucher_id, account_id, order_id, discount_amount) VALUES (?, ?, ?, ?)"
        );
        $vuStmt->bind_param("iiid", $verifiedVoucherId, $accountId, $orderId, $discountAmount);
        $vuStmt->execute();
        $vuStmt->close();
    }

    // ── Insert payment record ─────────────────────────────────────────────
    $billingName = trim($cd['first_name'] . ' ' . $cd['last_name']);
    $payStmt = $conn->prepare("
        INSERT INTO payments (
            order_id, currency, gross_amount, payment_status,
            mode, billing_name, billing_email, billing_phone,
            billing_line1, billing_city, billing_postal_code, billing_country,
            source_type, created_at
        ) VALUES (?, 'PHP', ?, 'Paid', 'live', ?, ?, ?, ?, ?, ?, 'PH', ?, NOW())
    ");
    // i=order_id d=amount s×7=name,email,phone,address,city,postal,method
    $payStmt->bind_param("idsssssss",
        $orderId,
        $totalAmount,
        $billingName,
        $cd['email'],
        $cd['phone_number'],
        $cd['address'],
        $cd['city'],
        $cd['postal_code'],
        $cd['payment_method']
    );
    if (!$payStmt->execute())
        error_log("Payment record insert error: " . $payStmt->error);
    $payStmt->close();

    // ── Log activity ──────────────────────────────────────────────────────
    $discountSummary = '';
    if ($discountAmount > 0) {
        $parts = [];
        if (!empty($voucherCode))  $parts[] = "Voucher: {$voucherCode}";
        if ($verifiedPromoId)      $parts[] = "Promo ID: {$verifiedPromoId}";
        $discountSummary = " | Discount: -₱".number_format($discountAmount, 2)
            . (!empty($parts) ? " (".implode(', ', $parts).")" : '');
    }

    logActivity(
        $conn, 'order', $orderId, 'Order created after payment', null, 'Paid',
        "Order #{$orderCode} | Method: {$cd['payment_method']}" .
        " | Subtotal: ₱".number_format($serverSubtotal, 2) .
        $discountSummary .
        " | Delivery: ₱".number_format($deliveryFee, 2) .
        " | Total: ₱".number_format($totalAmount, 2) .
        " | Items: ".implode(', ', $itemSummary),
        $accountId,
        $accountId ? 'customer' : 'guest'
    );

    $conn->commit();

    // ── Send confirmation email ───────────────────────────────────────────────
    require_once './functions/mail_functions.php';
    sendPaymentConfirmationEmail($orderId);

    // ── Clean up session ──────────────────────────────────────────────────
    unset(
        $_SESSION['pending_checkout'],
        $_SESSION['temp_checkout_ref'],
        $_SESSION['paymongo_session_id'],
        $_SESSION['cart'],
        $_SESSION['cart_errors'],
        $_SESSION['last_checkout_city']
    );

    $_SESSION['success']    = "Payment successful! Your order #{$orderCode} has been confirmed.";
    $_SESSION['order_id']   = $orderId;
    $_SESSION['order_code'] = $orderCode;

    error_log("Order created successfully: #{$orderCode} (ID: {$orderId})");
    header("Location: order_review.php?order_code=" . urlencode($orderCode));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order creation error in callback: " . $e->getMessage());
    $_SESSION['error'] = "Order creation failed. Please contact support. Error: " . $e->getMessage();
    header("Location: checkout.php?error=order_creation_failed");
    exit();
}