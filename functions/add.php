<?php
session_start();
require_once '../conn.php';
require_once '../vendor/autoload.php';
require_once 'paymongo_helper.php';
require_once '../functions/activity_log_helper.php';
require_once '../functions/order_helper.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

date_default_timezone_set('Asia/Manila');

function redirectWithMessage($location, $message, $type = 'error') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

/**
 * Validate every cart item against current DB state.
 */
function validateCartItems(mysqli $conn, array $cart): array {
    $errors = [];
    foreach ($cart as $item) {
        $productId = intval($item['product_id']);
        $variantId = intval($item['variant_id']);
        $qty       = floatval($item['quantity']);
        $name      = $item['product_name'];
        $varName   = $item['variant_name'] ?? '';

        $stmt = $conn->prepare("
            SELECT
                p.is_deleted,
                p.product_name,
                pv.variant_id,
                pv.variant_name,
                pv.stock_status,
                pv.stock_quantity
            FROM products p
            LEFT JOIN product_variants pv
                   ON pv.variant_id = ? AND pv.product_id = p.product_id
            WHERE p.product_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $variantId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $errors[] = ['product_name'=>$name,'variant_name'=>$varName,
                'message'=>"\"$name\" no longer exists. Please remove it to proceed."];
            continue;
        }
        if ($row['is_deleted']) {
            $errors[] = ['product_name'=>$name,'variant_name'=>$varName,
                'message'=>"\"$name\" has been removed from our store. Please remove it to proceed."];
            continue;
        }
        if ($row['variant_id'] === null) {
            $errors[] = ['product_name'=>$name,'variant_name'=>$varName,
                'message'=>"The selected size/variant of \"$name\" is no longer available. Please remove it to proceed."];
            continue;
        }
        if ($row['stock_status'] !== 'In Stock') {
            $vLabel = $varName ? " ($varName)" : '';
            $errors[] = ['product_name'=>$name,'variant_name'=>$varName,
                'message'=>"\"$name{$vLabel}\" is currently out of stock. Please remove it to proceed."];
            continue;
        }
        $stock = floatval($row['stock_quantity']);
        if ($stock < $qty) {
            $vLabel = $varName ? " ($varName)" : '';
            $avail  = $stock > 0 ? "Only ".number_format($stock,0)." available." : "No stock available.";
            $errors[] = ['product_name'=>$name,'variant_name'=>$varName,
                'message'=>"\"$name{$vLabel}\" — not enough stock. $avail Please update your quantity or remove it to proceed."];
        }
    }
    return $errors;
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // ─────────────────────────────────────────────────────────────────────────
        // REGISTER
        // ─────────────────────────────────────────────────────────────────────────
        if (isset($_POST['register_account'])) {

        if (isset($_SESSION['account_id'])) {
            header("Location: ../account/shop.php");
            exit();
        }

        $email            = trim($_POST['email']            ?? '');
        $username         = trim($_POST['username']         ?? '');
        $password         = trim($_POST['password']         ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $role             = 'customer';

        if (empty($email) || empty($username) || empty($password) || empty($confirm_password))
            redirectWithMessage('../register.php', "All fields are required.");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            redirectWithMessage('../register.php', "Please enter a valid email address.");

        if (!preg_match('/^[a-zA-Z0-9_]{5,}$/', $username))
            redirectWithMessage('../register.php', "Username must be at least 5 characters and contain only letters, numbers, or underscores.");

        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password))
            redirectWithMessage('../register.php', "Password must be at least 8 characters and include an uppercase letter, a number, and a special character.");

        if ($password !== $confirm_password)
            redirectWithMessage('../register.php', "Passwords do not match.");

        $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE account_email = ? LIMIT 1");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("s", $email); $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            redirectWithMessage('../register.php', "That email address is already registered.");
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? LIMIT 1");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("s", $username); $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            redirectWithMessage('../register.php', "That username is already taken.");
        }
        $stmt->close();

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO accounts (account_email, username, password_hash, role) VALUES (?, ?, ?, ?)");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("ssss", $email, $username, $hashed, $role);
        if (!$stmt->execute()) {
            error_log("Register error: " . $stmt->error);
            $stmt->close();
            redirectWithMessage('../register.php', "Registration failed. Please try again.");
        }
        $newAccountId = $stmt->insert_id;
        $stmt->close();

        if (function_exists('logActivity')) {
            logActivity($conn, 'account', $newAccountId, 'Account registered', null, null,
                "New customer account. Username: {$username} | Email: {$email}",
                $newAccountId, 'customer');
        }

        session_regenerate_id(true);
        $_SESSION['account_id']     = $newAccountId;
        $_SESSION['username']       = $username;
        $_SESSION['role']           = 'customer';
        $_SESSION['loggedinasuser'] = true;
        $conn->close();
        redirectWithMessage('../account/shop.php', "Welcome aboard, {$username}! Your account has been created.", 'success');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COMPLETE ORDER
    // ─────────────────────────────────────────────────────────────────────────
    elseif (isset($_POST['complete_order'])) {
        try {
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) throw new Exception("Your cart is empty.");
    
            // ── Order type ────────────────────────────────────────────────────────
            $orderType = trim($_POST['order_type'] ?? 'delivery');
            if (!in_array($orderType, ['delivery', 'pickup'])) $orderType = 'delivery';
            $isPickup = ($orderType === 'pickup');
    
            // ── Field validation ───────────────────────────────────────────────
            // Contact fields always required
            $alwaysRequired = [
                'recipient_first_name', 'recipient_last_name',
                'recipient_email', 'recipient_phone', 'payment_method'
            ];
            foreach ($alwaysRequired as $field)
                if (empty($_POST[$field]))
                    throw new Exception("Please fill in all required fields.");
    
            // Address fields only required for delivery
            if (!$isPickup) {
                $deliveryRequired = ['recipient_address', 'city', 'postal_code'];
                foreach ($deliveryRequired as $field)
                    if (empty($_POST[$field]))
                        throw new Exception("Please fill in all required delivery address fields.");
            }
    
            $email = filter_var(trim($_POST['recipient_email']), FILTER_VALIDATE_EMAIL);
            if (!$email) throw new Exception("Please provide a valid email address.");
    
            $phoneNumber = trim($_POST['recipient_phone']);
            if (!preg_match('/^[0-9+\-\s()]+$/', $phoneNumber) || strlen($phoneNumber) < 10)
                throw new Exception("Please provide a valid phone number.");
    
            $firstName  = trim($_POST['recipient_first_name']);
            $lastName   = trim($_POST['recipient_last_name']);
            if (strlen($firstName) < 2 || strlen($lastName) < 2)
                throw new Exception("First and last name must be at least 2 characters.");
    
            // For pickup, address / city / postal are optional — default to empty strings
            $address       = trim($_POST['recipient_address'] ?? '');
            $city          = trim($_POST['city']              ?? '');
            $postalCode    = trim($_POST['postal_code']       ?? '');
            $deliveryNotes = trim($_POST['delivery_notes']    ?? '');
    
            if (!$isPickup) {
                if (strlen($address) < 10)
                    throw new Exception("Please provide a complete address.");
                if (strlen($city) < 2)
                    throw new Exception("Please provide a valid city name.");
                if (!preg_match('/^[0-9]{4,6}$/', $postalCode))
                    throw new Exception("Please provide a valid postal code (4-6 digits).");
            }
    
            $paymentMethod       = $_POST['payment_method'];
            $validPaymentMethods = ['cod', 'cop', 'gcash', 'paymaya', 'grab_pay', 'card', 'qrph'];
            if (!in_array($paymentMethod, $validPaymentMethods))
                throw new Exception("Invalid payment method selected.");
    
            // ── Pricing inputs from checkout form ──────────────────────────────
            $submittedSubtotal    = round((float)($_POST['subtotal']        ?? 0), 2);
            $submittedDeliveryFee = round((float)($_POST['delivery_fee']    ?? 0), 2);
            $submittedDiscount    = round((float)($_POST['discount_amount'] ?? 0), 2);
            $voucherCode          = trim($_POST['applied_voucher_code'] ?? $_POST['voucher_code'] ?? '');
    
            // Pickup always has zero delivery fee — ignore whatever the client sent
            if ($isPickup) $submittedDeliveryFee = 0.00;
    
            // ── Server-side subtotal verification ─────────────────────────────
            $serverSubtotal = round(array_sum(
                array_map(fn($i) => (float)$i['price'] * (float)$i['quantity'], $cart)
            ), 2);
    
            if (abs($serverSubtotal - $submittedSubtotal) > 1.00) {
                error_log("Subtotal mismatch — client: {$submittedSubtotal}, server: {$serverSubtotal}");
                throw new Exception("Order total mismatch. Please refresh and try again.");
            }
    
            // ── Server-side delivery fee verification ──────────────────────────
            require_once '../functions/discount_helper.php';
            // ── Server-side delivery fee verification ──────────────────────────────────
            if ($isPickup) {
                $submittedDeliveryFee = 0.00;  // always zero for pickup, no DB lookup needed
            } else {
                if (empty($city)) {
                    // Delivery requires a city — this shouldn't happen but guard anyway
                    throw new Exception("Please select a delivery city.");
                }
                $serverDeliveryFee = round(getDeliveryFee($city, $serverSubtotal, $conn), 2);
                if (abs($serverDeliveryFee - $submittedDeliveryFee) > 1.00) {
                    error_log("Delivery fee mismatch — client: {$submittedDeliveryFee}, server: {$serverDeliveryFee}. Using server value.");
                    $submittedDeliveryFee = $serverDeliveryFee;
                }
            }
    
            // ── Server-side voucher + promotion verification ───────────────────
            $verifiedDiscount  = 0.00;
            $verifiedVoucherId = null;
            $verifiedPromoId   = null;
    
            $accountId  = $_SESSION['account_id'] ?? null;
            $userGroups = getUserGroups($accountId, $conn);
    
            if (!empty($voucherCode)) {
                $voucher = validateVoucher($voucherCode, $serverSubtotal, $userGroups, $accountId, $conn);
    
                if ($voucher && !isset($voucher['error'])) {
                    $verifiedDiscount  = calculateDiscount(
                        $voucher['discount_type'],
                        $voucher['discount_value'],
                        $serverSubtotal,
                        $voucher['max_discount'] ?? null
                    );
                    $verifiedVoucherId = (int)$voucher['voucher_id'];
    
                    if (!empty($voucher['free_shipping']) || $verifiedDiscount >= $serverSubtotal) {
                        $submittedDeliveryFee = 0.00;
                    }
    
                    if (!$isPickup) {
                        $freeShippingRule = checkFreeShipping(
                            $serverSubtotal - $verifiedDiscount,
                            $userGroups, $city, $conn
                        );
                        if ($freeShippingRule) $submittedDeliveryFee = 0.00;
                    }
                } else {
                    $voucherError = $voucher['error'] ?? 'Unknown validation failure';
                    error_log("Voucher '{$voucherCode}' rejected server-side: {$voucherError}. Discount cleared.");
                    $voucherCode      = '';
                    $verifiedDiscount = 0.00;
                }
            } else {
                // No voucher — check free shipping (delivery only)
                if (!$isPickup) {
                    $freeShippingRule = checkFreeShipping($serverSubtotal, $userGroups, $city, $conn);
                    if ($freeShippingRule) $submittedDeliveryFee = 0.00;
                }
            }
    
            // ── Auto-apply promotions ──────────────────────────────────────────
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
                    SELECT p.*
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
                $bestPromo = $promoStmt->get_result()->fetch_assoc();
                $promoStmt->close();
    
                if ($bestPromo) {
                    $promoDiscount = calculateDiscount(
                        $bestPromo['discount_type'],
                        $bestPromo['discount_value'],
                        $serverSubtotal,
                        $bestPromo['max_discount'] ?? null
                    );
                    if ($promoDiscount > 0) {
                        $verifiedDiscount += $promoDiscount;
                        $verifiedPromoId   = (int)$bestPromo['promotion_id'];
                    }
                }
            }
    
            // Cap discount — never exceed subtotal
            $verifiedDiscount = min($verifiedDiscount, $serverSubtotal);
    
            // ── Final total (server-authoritative) ────────────────────────────
            $totalAmount = round($serverSubtotal - $verifiedDiscount + $submittedDeliveryFee, 2);
            if ($totalAmount < 0) $totalAmount = 0.00;
            if ($totalAmount <= 0 && $verifiedDiscount < $serverSubtotal)
                throw new Exception("Invalid order total.");
    
            // ── Cart validation ────────────────────────────────────────────────
            $cartErrors = validateCartItems($conn, $cart);
            if (!empty($cartErrors)) {
                $_SESSION['cart_errors'] = $cartErrors;
                header("Location: ../checkout.php");
                exit();
            }
    
            $conn->begin_transaction();
    
            $accountId = $_SESSION['account_id'] ?? null;
            $isGuest   = $accountId ? 0 : 1;
            $orderCode = generateOrderCode();
    
            // ── Insert order ───────────────────────────────────────────────────
            // Added: order_type column
            // bind_param: "isssssssdddsiidssiss" (21 params)
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    account_id, recipient_email, recipient_phone,
                    recipient_first_name, recipient_last_name,
                    recipient_address, postal_code, city,
                    subtotal, delivery_fee, discount_amount, voucher_code, voucher_id, promotion_id,
                    total_price, payment_method,
                    is_guest_order, order_code, delivery_notes,
                    order_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssssdddsiidsssss",
                $accountId, $email, $phoneNumber,
                $firstName, $lastName,
                $address, $postalCode, $city,
                $serverSubtotal, $submittedDeliveryFee, $verifiedDiscount, $voucherCode,
                $verifiedVoucherId, $verifiedPromoId,
                $totalAmount, $paymentMethod,
                $isGuest, $orderCode, $deliveryNotes,
                $orderType
            );
            if (!$stmt->execute()) throw new Exception("Failed to create order: " . $conn->error);
            $orderId = $conn->insert_id;
            $stmt->close();
    
            // ── Insert order items ─────────────────────────────────────────────
            $itemStmt = $conn->prepare(
                "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)"
            );
            $itemSummary = [];
            foreach ($cart as $item) {
                $chk = $conn->prepare(
                    "SELECT variant_id FROM product_variants WHERE variant_id = ? AND stock_status = 'In Stock' LIMIT 1"
                );
                $chk->bind_param("i", $item['variant_id']);
                $chk->execute();
                if ($chk->get_result()->num_rows === 0) {
                    $chk->close();
                    $conn->rollback();
                    $_SESSION['cart_errors'] = [[
                        'product_name' => $item['product_name'],
                        'variant_name' => $item['variant_name'] ?? '',
                        'message'      => "\"{$item['product_name']}\" became unavailable just now. Please remove it.",
                    ]];
                    header("Location: ../checkout.php");
                    exit();
                }
                $chk->close();
    
                $itemStmt->bind_param(
                    "iiiid",
                    $orderId, $item['product_id'], $item['variant_id'],
                    $item['quantity'], $item['price']
                );
                if (!$itemStmt->execute()) throw new Exception("Failed to add order items: " . $conn->error);
                $itemSummary[] = "{$item['product_name']} x{$item['quantity']}";
            }
            $itemStmt->close();
    
            // ── Record voucher usage ───────────────────────────────────────────
            if ($verifiedVoucherId && $verifiedDiscount > 0) {
                $voucherDiscountOnly = $verifiedPromoId
                    ? calculateDiscount(
                        $voucher['discount_type'] ?? 'fixed',
                        $voucher['discount_value'] ?? 0,
                        $serverSubtotal,
                        $voucher['max_discount'] ?? null
                    )
                    : $verifiedDiscount;
    
                $vuStmt = $conn->prepare(
                    "INSERT INTO voucher_usage (voucher_id, account_id, order_id, discount_amount) VALUES (?, ?, ?, ?)"
                );
                $vuStmt->bind_param("iiid", $verifiedVoucherId, $accountId, $orderId, $voucherDiscountOnly);
                $vuStmt->execute();
                $vuStmt->close();
            }
    
            // ── Build discount summary for activity log ────────────────────────
            $discountSummary = '';
            if ($verifiedDiscount > 0) {
                $parts = [];
                if ($verifiedVoucherId && !empty($voucherCode))
                    $parts[] = "Voucher: {$voucherCode}";
                if ($verifiedPromoId)
                    $parts[] = "Promotion ID: {$verifiedPromoId}";
                $discountSummary = " | Discount: -₱".number_format($verifiedDiscount, 2)." (".implode(', ', $parts).")";
            }
    
            logActivity(
                $conn, 'order', $orderId, 'Order created', null,
                $paymentMethod === 'cod' ? 'Pending' : 'Paid',
                "Order #{$orderCode} created. " .
                ($isGuest ? "Guest" : "Account ID: {$accountId}") .
                " | Type: {$orderType}" .
                " | Payment: {$paymentMethod}" .
                " | Subtotal: ₱".number_format($serverSubtotal, 2) .
                $discountSummary .
                " | Delivery: ₱".number_format($submittedDeliveryFee, 2) .
                " | Total: ₱".number_format($totalAmount, 2) .
                " | Items: ".implode(', ', $itemSummary),
                $accountId, 'customer'
            );
    
            // ── Save pending checkout (includes order_type for cancel/return) ──
            $_SESSION['pending_checkout'] = [
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'email'           => $email,
                'phone_number'    => $phoneNumber,
                'address'         => $address,
                'postal_code'     => $postalCode,
                'city'            => $city,
                'delivery_notes'  => $deliveryNotes,
                'payment_method'  => $paymentMethod,
                'order_type'      => $orderType,       // ← new
                'subtotal'        => $serverSubtotal,
                'delivery_fee'    => $submittedDeliveryFee,
                'discount_amount' => $verifiedDiscount,
                'voucher_code'    => $voucherCode,
                'total_amount'    => $totalAmount,
                'cart'            => $cart,
                'account_id'      => $accountId,
                'is_guest'        => $isGuest,
                'created_at'      => time(),
            ];
            $_SESSION['last_checkout_city'] = $city;
    
            // ── Online payment ─────────────────────────────────────────────────
            if (in_array($paymentMethod, ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph'])) {
    
                $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
                $baseUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
    
                $tempReference = 'TMP_' . uniqid() . '_' . time();
                $_SESSION['temp_checkout_ref']   = $tempReference;
                $_SESSION['paymongo_session_id'] = null;
    
                // For pickup orders billing address falls back to a placeholder
                $billingAddress = $isPickup ? 'Store Pickup' : $address;
                $billingCity    = $isPickup ? 'N/A'          : $city;
                $billingPostal  = $isPickup ? '0000'         : $postalCode;
    
                $response = $paymongo->createCheckoutSession(
                    $totalAmount,
                    "Order #{$orderCode} Payment",
                    [
                        'payment_method_types' => [$paymentMethod],
                        'success_url' => $baseUrl . '/payment_callback.php?ref=' . $tempReference . '&status=success',
                        'cancel_url'  => $baseUrl . '/checkout.php?cancel=1&ref=' . $tempReference,
                        'customer_info' => [
                            'first_name' => $firstName,
                            'last_name'  => $lastName,
                            'email'      => $email,
                            'phone'      => $phoneNumber,
                        ],
                        'billing' => [
                            'address' => [
                                'line1'       => $billingAddress,
                                'city'        => $billingCity,
                                'postal_code' => $billingPostal,
                                'state'       => '',
                                'country'     => 'PH',
                            ],
                            'email' => $email,
                            'name'  => "$firstName $lastName",
                            'phone' => $phoneNumber,
                        ],
                        'metadata' => [
                            'temp_reference' => $tempReference,
                            'order_id'       => $orderId,
                            'order_code'     => $orderCode,
                            'customer_email' => $email,
                            'customer_name'  => "$firstName $lastName",
                            'payment_method' => $paymentMethod,
                            'order_type'     => $orderType,   // ← new
                        ],
                    ]
                );
    
                if (!isset($response['data']['attributes']['checkout_url'])) {
                    unset($_SESSION['pending_checkout'], $_SESSION['temp_checkout_ref']);
                    throw new Exception("Checkout session creation failed. No checkout URL returned.");
                }
    
                $_SESSION['paymongo_session_id'] = $response['data']['id'];
                error_log("Payment initiated — Ref: {$tempReference}, Order: {$orderCode}, Method: {$paymentMethod}, Type: {$orderType}");
    
                // Rollback DB order — will be re-created in payment_callback.php
                $conn->rollback();
    
                header("Location: " . $response['data']['attributes']['checkout_url']);
                exit();
    
            // ── Cash on Delivery or Cash on Pickup ────────────────────────────────────────────────────────────
            } elseif ($paymentMethod === 'cod' || $paymentMethod === 'cop') {

                $billingAddress = $isPickup ? 'Store Pickup' : $address;
                $billingCity    = $isPickup ? 'N/A'          : $city;
                $billingPostal  = $isPickup ? '0000'         : $postalCode;
                $billingName    = "$firstName $lastName";

                $codStmt = $conn->prepare("
                    INSERT INTO payments (
                        order_id, currency, gross_amount, payment_status,
                        mode, billing_name, billing_email, billing_phone,
                        billing_line1, billing_city, billing_postal_code, billing_country,
                        source_type, created_at
                    ) VALUES (?, 'PHP', ?, 'Pending', 'live', ?, ?, ?, ?, ?, ?, 'PH', ?, NOW())
                ");
                $codStmt->bind_param(
                    "idsssssss",
                    $orderId, $totalAmount,
                    $billingName, $email, $phoneNumber,
                    $billingAddress, $billingCity, $billingPostal,
                    $paymentMethod
                );
                if (!$codStmt->execute())
                    error_log("COD payment insert error: " . $codStmt->error);
                $codStmt->close();

                // ── Generate confirm token ────────────────────────────────────────────
                $confirmToken = bin2hex(random_bytes(32));
                $tokenExpiry  = date('Y-m-d H:i:s', strtotime('+48 hours'));

                $tkStmt = $conn->prepare("UPDATE orders SET confirm_token = ?, confirm_token_expiry = ? WHERE order_id = ?");
                $tkStmt->bind_param('ssi', $confirmToken, $tokenExpiry, $orderId);
                $tkStmt->execute();
                $tkStmt->close();

                logActivity(
                    $conn, 'payment', $orderId, 'COD order placed', null, 'Pending',
                    strtoupper($paymentMethod) . " {$orderType} order #{$orderCode}. Total: ₱" . number_format($totalAmount, 2),
                    $accountId, 'customer'
                );

                $conn->commit();

                // ── Build confirm URL ─────────────────────────────────────────────────
                $baseUrl    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sjfbi-js', '/');
                $confirmUrl = $baseUrl . '/confirm_order.php?order_id=' . $orderId
                            . '&token=' . urlencode($confirmToken)
                            . '&type=' . ($isPickup ? 'pickup' : 'confirm');

                // After $conn->commit() and token generation:
                require_once '../functions/mail_functions.php';

                $isCOPPickup = ($paymentMethod === 'cop' && $isPickup);

                if ($isCOPPickup) {
                    // COP pickup — send receipt only, no confirm button
                    sendPaymentConfirmationEmail($orderId, $confirmToken);
                } else {
                    // COD delivery — send receipt + confirm button email
                    sendPaymentConfirmationEmail($orderId);

                    $orderRow = [
                        'order_id'             => $orderId,
                        'order_code'           => $orderCode,
                        'order_type'           => $orderType,
                        'recipient_first_name' => $firstName,
                        'recipient_email'      => $email,
                        'total_price'          => $totalAmount,
                        'payment_method'       => $paymentMethod,
                    ];
                    email_cod_confirm_order($orderRow, $confirmUrl);
                }

                // ── Clean up and redirect ────────────────────────────────────────────
                unset(
                    $_SESSION['cart'],
                    $_SESSION['cart_errors'],
                    $_SESSION['pending_checkout'],
                    $_SESSION['last_checkout_city']
                );
                $_SESSION['order_id']   = $orderId;
                $_SESSION['order_code'] = $orderCode;

                header("Location: ../order_review.php?order_code=" . $orderCode);
                exit();
    
            } else {
                throw new Exception("Unsupported payment method: " . $paymentMethod);
            }
    
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollback();
            error_log("Order processing error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../checkout.php");
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUBMIT REVIEW
    // ─────────────────────────────────────────────────────────────────────────
    elseif (isset($_POST['submit_review'])) {
        $orderCode = trim($_POST['order_code'] ?? '');
        $token     = trim($_POST['token']      ?? '');

        function generateReviewToken(string $orderCode, string $email, string $salt = 'sjfbi_review_2025'): string {
            return strtoupper(substr(hash('sha256', $orderCode . $email . $salt), 0, 12));
        }

        $redirectBase = "../review.php?order={$orderCode}&token={$token}";

        $stmt = $conn->prepare("
            SELECT o.*
            FROM orders o
            WHERE o.order_code = ? AND o.order_status = 'Delivered'
            LIMIT 1
        ");
        $stmt->bind_param('s', $orderCode); $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order)
            redirectWithMessage($redirectBase, 'Order not found or not yet delivered.', 'error');

        $expectedToken = generateReviewToken($orderCode, $order['recipient_email']);
        if (!hash_equals($expectedToken, strtoupper($token)))
            redirectWithMessage($redirectBase, 'Invalid review token.', 'error');

        if (strtoupper($_POST['token']) !== strtoupper($token))
            redirectWithMessage($redirectBase, 'Security check failed.', 'error');

        $iStmt = $conn->prepare("
            SELECT oi.*, p.product_name, p.product_id,
                pv.variant_name, pv.variant_price
            FROM order_items oi
            LEFT JOIN products p  ON oi.product_id  = p.product_id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
            WHERE oi.order_id = ? AND oi.is_reviewed = 0
        ");
        $iStmt->bind_param('i', $order['order_id']); $iStmt->execute();
        $orderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $iStmt->close();

        $validationErrors = [];
        $reviewedCount    = 0;
        $reviewerPosition = trim($_POST['position'] ?? '');
        $reviewerCompany  = trim($_POST['company']  ?? '');

        foreach ($orderItems as $item) {
            $itemId    = $item['order_item_id'];
            $productId = $item['product_id'];
            $rating    = intval($_POST["rating_{$itemId}"] ?? 0);
            $feedback  = trim($_POST["feedback_{$itemId}"] ?? '');

            if (!$rating && !$feedback) continue;

            if ($rating < 1 || $rating > 5) {
                $validationErrors[] = "Please select a star rating for ".htmlspecialchars($item['product_name']);
                continue;
            }
            if (strlen($feedback) < 10) {
                $validationErrors[] = "Review for \"".htmlspecialchars($item['product_name'])."\" must be at least 10 characters.";
                continue;
            }

            $fullName = trim($order['recipient_first_name'].' '.$order['recipient_last_name']);
            $email    = $order['recipient_email'];
            $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua       = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $rStmt = $conn->prepare("
                INSERT INTO reviews
                    (order_id, order_item_id, product_id,
                    reviewer_name, reviewer_email, rating, feedback,
                    position, company,
                    is_verified_purchase, status,
                    reviewer_ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', ?, ?)
            ");
            $rStmt->bind_param('iiisissssss',
                $order['order_id'], $itemId, $productId,
                $fullName, $email, $rating, $feedback,
                $reviewerPosition, $reviewerCompany,
                $ip, $ua
            );

            if ($rStmt->execute()) {
                $reviewId = $conn->insert_id;
                $rStmt->close();

                $photoKey = "photos_{$itemId}";
                if (!empty($_FILES[$photoKey]['name'][0])) {
                    $uploadDir    = __DIR__ . '/../uploads/reviews/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
                    foreach ($_FILES[$photoKey]['tmp_name'] as $idx => $tmpPath) {
                        if (!is_uploaded_file($tmpPath)) continue;
                        $mimeType = $_FILES[$photoKey]['type'][$idx];
                        $fileSize = $_FILES[$photoKey]['size'][$idx];
                        if ($fileSize > 5 * 1024 * 1024)        continue;
                        if (!in_array($mimeType, $allowedMimes)) continue;
                        $origName = $_FILES[$photoKey]['name'][$idx];
                        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $fileName = 'review_'.$reviewId.'_'.$idx.'_'.uniqid().'.'.$ext;
                        $destPath = $uploadDir . $fileName;
                        if (move_uploaded_file($tmpPath, $destPath)) {
                            $relPath   = 'uploads/reviews/'.$fileName;
                            $uploadOrd = $idx + 1;
                            $aStmt = $conn->prepare("
                                INSERT INTO review_attachments
                                    (review_id, file_path, file_name, file_size, mime_type, upload_order)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $aStmt->bind_param('issiis', $reviewId, $relPath, $fileName, $fileSize, $mimeType, $uploadOrd);
                            $aStmt->execute(); $aStmt->close();
                        }
                    }
                }

                $uStmt = $conn->prepare("UPDATE order_items SET is_reviewed = 1, review_id = ? WHERE order_item_id = ?");
                $uStmt->bind_param('ii', $reviewId, $itemId); $uStmt->execute(); $uStmt->close();
                $reviewedCount++;
            } else {
                $rStmt->close();
                $validationErrors[] = "Failed to save review for \"".htmlspecialchars($item['product_name'])."\".";
            }
        }

        if (empty($validationErrors) && $reviewedCount > 0) {
            $_SESSION['review_submitted'] = true;
            header("Location: ../review.php?order={$orderCode}&token={$token}");
            exit();
        }
        if (empty($validationErrors) && $reviewedCount === 0)
            $validationErrors[] = "Please fill in at least one product review before submitting.";

        $_SESSION['review_errors'] = $validationErrors;
        header("Location: ../review.php?order={$orderCode}&token={$token}");
        exit();
    }
}