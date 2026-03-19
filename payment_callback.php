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

// ── Handle cancellation ───────────────────────────────────────────────────
if ($status !== 'success') {
    error_log("Payment cancelled/failed");
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

    // Pull pricing fields — these were saved by add.php after server-side verification
    $serverSubtotal      = (float)($cd['subtotal']        ?? $cd['total_amount'] ?? 0);
    $deliveryFee         = (float)($cd['delivery_fee']    ?? 0);
    $discountAmount      = (float)($cd['discount_amount'] ?? 0);
    $voucherCode         = trim($cd['voucher_code']       ?? '');
    $totalAmount         = (float)($cd['total_amount']    ?? 0);

    // Re-verify delivery fee server-side (guard against session tampering)
    $serverDeliveryFee = round(getDeliveryFee($cd['city'], $serverSubtotal, $conn), 2);
    if (abs($serverDeliveryFee - $deliveryFee) > 1.00) {
        error_log("Callback delivery fee mismatch — session: {$deliveryFee}, server: {$serverDeliveryFee}. Using server.");
        $deliveryFee = $serverDeliveryFee;
        $totalAmount = round($serverSubtotal - $discountAmount + $deliveryFee, 2);
    }

    // ── Insert order using RENAMED columns ───────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO orders (
            account_id,
            recipient_email, recipient_phone,
            recipient_first_name, recipient_last_name,
            recipient_address, postal_code, city,
            subtotal, delivery_fee, discount_amount, voucher_code,
            total_price, payment_method,
            is_guest_order, order_code, delivery_notes,
            order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid')
    ");

    $stmt->bind_param("isssssssdddsdsiss",
        $accountId,
        $cd['email'],           // not recipient_email
        $cd['phone_number'],    // not recipient_phone
        $cd['first_name'],      // not recipient_first_name
        $cd['last_name'],       // not recipient_last_name
        $cd['address'],         // not recipient_address
        $cd['postal_code'],
        $cd['city'],
        $serverSubtotal,
        $deliveryFee,
        $discountAmount,
        $voucherCode,
        $totalAmount,
        $cd['payment_method'],
        $isGuest,
        $orderCode,
        $cd['delivery_notes']
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $conn->error);
    }
    $orderId = $conn->insert_id;
    $stmt->close();

    // ── Insert order items + deduct stock ────────────────────────────────
    $itemStmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)"
    );
    $itemSummary = [];

    foreach ($cd['cart'] as $item) {
        // Last-second availability check
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
            $orderId,
            $item['product_id'],
            $item['variant_id'],
            $item['quantity'],
            $item['price']
        );
        if (!$itemStmt->execute()) {
            throw new Exception("Failed to insert order item: " . $conn->error);
        }

        // Deduct stock
        $stock = $conn->prepare("
            UPDATE product_variants
            SET stock_quantity = stock_quantity - ?,
                stock_status   = IF(stock_quantity - ? <= 0, 'Out of Stock', 'In Stock')
            WHERE variant_id = ? AND stock_quantity >= ?
        ");
        $stock->bind_param("didi", $item['quantity'], $item['quantity'], $item['variant_id'], $item['quantity']);
        if (!$stock->execute() || $stock->affected_rows === 0) {
            throw new Exception("Stock deduction failed for \"{$item['product_name']}\".");
        }
        $stock->close();

        $itemSummary[] = "{$item['product_name']} x{$item['quantity']}";
    }
    $itemStmt->close();

    // ── Record voucher usage ─────────────────────────────────────────────
    if (!empty($voucherCode) && $discountAmount > 0) {
        $vStmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ? AND is_active = 1 LIMIT 1");
        $vStmt->bind_param("s", $voucherCode);
        $vStmt->execute();
        $vRow = $vStmt->get_result()->fetch_assoc();
        $vStmt->close();
        if ($vRow) {
            $vuStmt = $conn->prepare(
                "INSERT INTO voucher_usage (voucher_id, account_id, order_id, discount_amount) VALUES (?, ?, ?, ?)"
            );
            $vuStmt->bind_param("iiid", $vRow['voucher_id'], $accountId, $orderId, $discountAmount);
            $vuStmt->execute();
            $vuStmt->close();
        }
    }

    // ── Insert payment record ────────────────────────────────────────────
    $billingName = trim($cd['first_name'] . ' ' . $cd['last_name']);
    $payStmt = $conn->prepare("
        INSERT INTO payments (
            order_id, currency, gross_amount, payment_status,
            mode, billing_name, billing_email, billing_phone,
            billing_line1, billing_city, billing_postal_code, billing_country,
            source_type, created_at
        ) VALUES (?, 'PHP', ?, 'Paid', 'live', ?, ?, ?, ?, ?, ?, 'PH', ?, NOW())
    ");
    // 9 ? placeholders → 9 values → "idsssssss" (i=order_id, d=amount, 7×s)
    $payStmt->bind_param("idsssssss",
        $orderId,           // i
        $totalAmount,       // d
        $billingName,       // s — billing_name
        $cd['email'],       // s — billing_email
        $cd['phone_number'],// s — billing_phone
        $cd['address'],     // s — billing_line1
        $cd['city'],        // s — billing_city
        $cd['postal_code'], // s — billing_postal_code
        $cd['payment_method'] // s — source_type
    );
    if (!$payStmt->execute()) {
        error_log("Payment record insert error: " . $payStmt->error);
    }
    $payStmt->close();

    // ── Log activity ─────────────────────────────────────────────────────
    logActivity(
        $conn, 'order', $orderId, 'Order created after payment', null, 'Paid',
        "Order #{$orderCode} | Method: {$cd['payment_method']}" .
        " | Subtotal: ₱" . number_format($serverSubtotal, 2) .
        ($discountAmount > 0 ? " | Discount: -₱" . number_format($discountAmount, 2) : '') .
        " | Delivery: ₱" . number_format($deliveryFee, 2) .
        " | Total: ₱" . number_format($totalAmount, 2) .
        " | Items: " . implode(', ', $itemSummary),
        $accountId,
        $accountId ? 'customer' : 'guest'
    );

    $conn->commit();

    // ── Clean up session ─────────────────────────────────────────────────
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