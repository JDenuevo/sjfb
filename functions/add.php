<?php
session_start();
require_once '../conn.php';
require_once '../vendor/autoload.php';
require_once 'paymongo_helper.php';
require_once '../functions/activity_log_helper.php';
require_once '../functions/order_helper.php'; // Add this line

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
 * Returns array of error objects: [['product_name'=>…, 'variant_name'=>…, 'message'=>…], …]
 * Empty array means all items are orderable.
 */
function validateCartItems(mysqli $conn, array $cart): array {
    $errors = [];
    foreach ($cart as $item) {
        $productId = intval($item['product_id']);
        $variantId = intval($item['variant_id']);
        $qty       = floatval($item['quantity']);
        $name      = $item['product_name'];
        $varName   = $item['variant_name'] ?? '';

        // Pull product + its specific variant in one query
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

        // Product does not exist at all
        if (!$row) {
            $errors[] = [
                'product_name' => $name,
                'variant_name' => $varName,
                'message'      => "\"$name\" no longer exists. Please remove it to proceed.",
            ];
            continue;
        }

        // Product was soft-deleted
        if ($row['is_deleted']) {
            $errors[] = [
                'product_name' => $name,
                'variant_name' => $varName,
                'message'      => "\"$name\" has been removed from our store. Please remove it to proceed.",
            ];
            continue;
        }

        // Variant itself is gone (FK would blow up here without this check)
        if ($row['variant_id'] === null) {
            $errors[] = [
                'product_name' => $name,
                'variant_name' => $varName,
                'message'      => "The selected size/variant of \"$name\" is no longer available. Please remove it to proceed.",
            ];
            continue;
        }

        // Variant marked out of stock
        if ($row['stock_status'] !== 'In Stock') {
            $vLabel = $varName ? " ($varName)" : '';
            $errors[] = [
                'product_name' => $name,
                'variant_name' => $varName,
                'message'      => "\"$name{$vLabel}\" is currently out of stock. Please remove it to proceed.",
            ];
            continue;
        }

        // Not enough stock for requested quantity
        $stock = floatval($row['stock_quantity']);
        if ($stock < $qty) {
            $vLabel  = $varName ? " ($varName)" : '';
            $avail   = $stock > 0
                ? "Only " . number_format($stock, 0) . " available."
                : "No stock available.";
            $errors[] = [
                'product_name' => $name,
                'variant_name' => $varName,
                'message'      => "\"$name{$vLabel}\" — not enough stock. $avail Please update your quantity or remove it to proceed.",
            ];
        }
    }
    return $errors;
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // ─────────────────────────────────────────────────────────────────────────
        // REGISTER
        // ─────────────────────────────────────────────────────────────────────────
        if (isset($_POST['register_account'])) {

        // Already logged in? Just go to shop.
        if (isset($_SESSION['account_id'])) {
            header("Location: ../account/shop.php");
            exit();
        }

        $email            = trim($_POST['email']            ?? '');
        $username         = trim($_POST['username']         ?? '');
        $password         = trim($_POST['password']         ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $role             = 'customer';

        // ── Field-level validation ────────────────────────────────────────────────
        if (empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
            redirectWithMessage('../register.php', "All fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('../register.php', "Please enter a valid email address.");
        }

        if (!preg_match('/^[a-zA-Z0-9_]{5,}$/', $username)) {
            redirectWithMessage('../register.php', "Username must be at least 5 characters and contain only letters, numbers, or underscores.");
        }

        if (
            strlen($password) < 8          ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[\W_]/',  $password)
        ) {
            redirectWithMessage('../register.php', "Password must be at least 8 characters and include an uppercase letter, a number, and a special character.");
        }

        if ($password !== $confirm_password) {
            redirectWithMessage('../register.php', "Passwords do not match.");
        }

        // ── Duplicate checks ──────────────────────────────────────────────────────
        $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE email = ? LIMIT 1");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            redirectWithMessage('../register.php', "That email address is already registered.");
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? LIMIT 1");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            redirectWithMessage('../register.php', "That username is already taken.");
        }
        $stmt->close();

        // ── Insert new account ────────────────────────────────────────────────────
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO accounts (email, username, password_hash, role)
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt) redirectWithMessage('../register.php', "A system error occurred. Please try again.");
        $stmt->bind_param("ssss", $email, $username, $hashed, $role);

        if (!$stmt->execute()) {
            error_log("Register error: " . $stmt->error);
            $stmt->close();
            redirectWithMessage('../register.php', "Registration failed. Please try again.");
        }

        $newAccountId = $stmt->insert_id;
        $stmt->close();

        // ── Activity log (only if helper exists) ─────────────────────────────────
        if (function_exists('logActivity')) {
            logActivity(
                $conn, 'account', $newAccountId,
                'Account registered', null, null,
                "New customer account. Username: {$username} | Email: {$email}",
                $newAccountId, 'customer'
            );
        }

        // ── Auto-login: set session immediately ───────────────────────────────────
        session_regenerate_id(true); // prevent session fixation

        $_SESSION['account_id']      = $newAccountId;
        $_SESSION['username']        = $username;
        $_SESSION['role']            = 'customer';
        $_SESSION['loggedinasuser']  = true;

        $conn->close();

        // ── Redirect to shop (no details step required) ───────────────────────────
        redirectWithMessage('../account/shop.php', "Welcome aboard, {$username}! Your account has been created.", 'success');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COMPLETE ORDER
    // ─────────────────────────────────────────────────────────────────────────
    elseif (isset($_POST['complete_order'])) {
        try {
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) throw new Exception("Your cart is empty.");

            // ── Field validation ───────────────────────────────────────────
            $requiredFields = ['first_name','last_name','email','phone_number',
                               'address','city','postal_code','payment_method'];
            foreach ($requiredFields as $field)
                if (empty($_POST[$field]))
                    throw new Exception("Please fill in all required fields.");

            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) throw new Exception("Please provide a valid email address.");

            $phoneNumber = trim($_POST['phone_number']);
            if (!preg_match('/^[0-9+\-\s()]+$/', $phoneNumber) || strlen($phoneNumber) < 10)
                throw new Exception("Please provide a valid phone number.");

            $firstName  = trim($_POST['first_name']);
            $lastName   = trim($_POST['last_name']);
            if (strlen($firstName) < 2 || strlen($lastName) < 2)
                throw new Exception("First name and last name must be at least 2 characters long.");

            $address    = trim($_POST['address']);
            $city       = trim($_POST['city']);
            $postalCode = trim($_POST['postal_code']);
            $deliveryNotes = trim($_POST['delivery_notes']);

            if (strlen($address) < 10) throw new Exception("Please provide a complete address.");
            if (strlen($city) < 2)     throw new Exception("Please provide a valid city name.");
            if (!preg_match('/^[0-9]{4,6}$/', $postalCode))
                throw new Exception("Please provide a valid postal code (4-6 digits).");

            $paymentMethod       = $_POST['payment_method'];
            $validPaymentMethods = ['cod','gcash','paymaya','grab_pay','card','qrph'];
            if (!in_array($paymentMethod, $validPaymentMethods))
                throw new Exception("Invalid payment method selected.");

            // ── Stock & availability guard (prevents FK constraint error) ──
            $cartErrors = validateCartItems($conn, $cart);
            if (!empty($cartErrors)) {
                $_SESSION['cart_errors'] = $cartErrors;
                header("Location: ../checkout.php");
                exit();
            }

            $totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
            if ($totalAmount <= 0) throw new Exception("Invalid order total.");

            $conn->begin_transaction();

            $accountId = $_SESSION['account_id'] ?? null;
            $isGuest   = $accountId ? 0 : 1;
            $orderCode = generateOrderCode();

            // ── Insert order ───────────────────────────────────────────────
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    account_id, email, phone_number, first_name, last_name,
                    address, postal_code, city, total_price, payment_method,
                    is_guest_order, order_code, delivery_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssssdsiss",
                $accountId, $email, $phoneNumber, $firstName, $lastName,
                $address, $postalCode, $city, $totalAmount,
                $paymentMethod, $isGuest, $orderCode, $deliveryNotes);
            if (!$stmt->execute()) throw new Exception("Failed to create order: " . $conn->error);

            $orderId = $conn->insert_id;

            // ── Insert order items (second race-condition guard) ───────────
            $itemStmt    = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $itemSummary = [];
            foreach ($cart as $item) {
                // Re-check variant right before insert to handle race conditions
                $chk = $conn->prepare("
                    SELECT variant_id FROM product_variants
                    WHERE variant_id = ? AND stock_status = 'In Stock'
                    LIMIT 1
                ");
                $chk->bind_param("i", $item['variant_id']);
                $chk->execute();
                if ($chk->get_result()->num_rows === 0) {
                    $chk->close();
                    $conn->rollback();
                    $_SESSION['cart_errors'] = [[
                        'product_name' => $item['product_name'],
                        'variant_name' => $item['variant_name'] ?? '',
                        'message'      => "\"{$item['product_name']}\" became unavailable just now. Please remove it to proceed.",
                    ]];
                    header("Location: ../checkout.php");
                    exit();
                }
                $chk->close();

                $itemStmt->bind_param("iiiid",
                    $orderId, $item['product_id'], $item['variant_id'],
                    $item['quantity'], $item['price']);
                if (!$itemStmt->execute()) throw new Exception("Failed to add order items: " . $conn->error);
                $itemSummary[] = "{$item['product_name']} x{$item['quantity']}";
            }

            logActivity($conn, 'order', $orderId, 'Order created', null, 'Pending',
                "Order #{$orderCode} created. " .
                ($isGuest ? "Guest order" : "Account ID: {$accountId}") .
                " | Payment: {$paymentMethod}" .
                " | Total: ₱" . number_format($totalAmount, 2) .
                " | Items: " . implode(', ', $itemSummary),
                $accountId, 'customer');

            // ── Online payment ─────────────────────────────────────────────────────
            if (in_array($paymentMethod, ['gcash','paymaya','grab_pay','card','qrph'])) {

                // Store checkout data in session temporarily (NO ORDER CREATED YET)
                $_SESSION['pending_checkout'] = [
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'address' => $address,
                    'postal_code' => $postalCode,
                    'city' => $city,
                    'delivery_notes' => $deliveryNotes,
                    'payment_method' => $paymentMethod,
                    'total_amount' => $totalAmount,
                    'cart' => $cart, // Store the cart items
                    'account_id' => $accountId,
                    'is_guest' => $isGuest,
                    'created_at' => time()
                ];
                
                $paymongo = new PayMongoHelper($_ENV['PAYMONGO_SECRET_KEY'], $_ENV['PAYMONGO_PUBLIC_KEY']);
                $baseUrl = 'http://localhost/sjfbi-js';
                
                $customerInfo = ['first_name'=>$firstName,'last_name'=>$lastName,'email'=>$email,'phone'=>$phoneNumber];
                $billingAddress = ['line1'=>$address,'city'=>$city,'postal_code'=>$postalCode,'state'=>'','country'=>'PH'];
                
                // Generate a temporary reference for this checkout attempt
                $tempReference = 'TMP_' . uniqid() . '_' . time();
                $_SESSION['temp_checkout_ref'] = $tempReference;
                
                $response = $paymongo->createCheckoutSession(
                    $totalAmount,
                    "Order Payment",
                    [
                        'payment_method_types' => [$paymentMethod],
                        'success_url' => $baseUrl . '/payment_callback.php?ref=' . $tempReference . '&status=success',
                        'cancel_url' => $baseUrl . '/checkout.php?cancel=1&ref=' . $tempReference,
                        'customer_info' => $customerInfo,
                        'billing' => [
                            'address' => $billingAddress,
                            'email' => $email,
                            'name' => "$firstName $lastName",
                            'phone' => $phoneNumber,
                        ],
                        'metadata' => [
                            'temp_reference' => $tempReference,
                            'customer_email' => $email,
                            'customer_name' => "$firstName $lastName",
                            'payment_method' => $paymentMethod,
                        ],
                    ]
                );
                
                if (!isset($response['data']['attributes']['checkout_url'])) {
                    // If PayMongo fails, clear the pending checkout and show error
                    unset($_SESSION['pending_checkout']);
                    unset($_SESSION['temp_checkout_ref']);
                    throw new Exception("Checkout session creation failed. No checkout URL returned.");
                }
                
                $checkoutSessionId = $response['data']['id'];
                $_SESSION['paymongo_session_id'] = $checkoutSessionId;
                
                // Log the attempt (optional)
                error_log("Payment initiated - Temp Ref: $tempReference, Method: $paymentMethod");
                
                // IMPORTANT: Rollback the transaction since we're not creating the order yet
                $conn->rollback();
                
                // Redirect to PayMongo - NO ORDER CREATED YET
                header("Location: " . $response['data']['attributes']['checkout_url']);
                exit();

            // ── COD ────────────────────────────────────────────────────────
            } elseif ($paymentMethod === 'cod') {
                $codStmt = $conn->prepare("
                    INSERT INTO payments (
                        order_id, currency, gross_amount, payment_status,
                        mode, billing_name, billing_email, billing_phone,
                        billing_line1, billing_city, billing_postal_code, billing_country,
                        source_type, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $billingName    = "$firstName $lastName";
                $currency       = 'PHP';
                $codPayStatus   = 'Pending';
                $mode           = 'live';
                $billingCountry = 'PH';
                $sourceType     = 'cod';
                $codStmt->bind_param("isdssssssssss",
                    $orderId, $currency, $totalAmount, $codPayStatus, $mode,
                    $billingName, $email, $phoneNumber,
                    $address, $city, $postalCode, $billingCountry, $sourceType);
                if (!$codStmt->execute())
                    error_log("COD payment insert error: " . $codStmt->error);

                logActivity($conn, 'payment', $orderId, 'COD order confirmed', null, 'Pending',
                    "Cash on Delivery order #{$orderCode} placed. Total: ₱" . number_format($totalAmount, 2),
                    $accountId, 'customer');

                $conn->commit();
                unset($_SESSION['cart'], $_SESSION['cart_errors']);
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

        // Fetch order
        $stmt = $conn->prepare("
            SELECT o.*
            FROM orders o
            WHERE o.order_code = ? AND o.order_status = 'Delivered'
            LIMIT 1
        ");
        $stmt->bind_param('s', $orderCode);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            redirectWithMessage($redirectBase, 'Order not found or not yet delivered.', 'error');
        }

        // Validate token
        $expectedToken = generateReviewToken($orderCode, $order['email']);
        if (!hash_equals($expectedToken, strtoupper($token))) {
            redirectWithMessage($redirectBase, 'Invalid review token.', 'error');
        }

        // CSRF check
        if (strtoupper($_POST['token']) !== strtoupper($token)) {
            redirectWithMessage($redirectBase, 'Security check failed.', 'error');
        }

        // Fetch unreviewed items
        $iStmt = $conn->prepare("
            SELECT oi.*, p.product_name, p.product_id,
                pv.variant_name, pv.variant_price
            FROM order_items oi
            LEFT JOIN products p  ON oi.product_id  = p.product_id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
            WHERE oi.order_id = ? AND oi.is_reviewed = 0
        ");
        $iStmt->bind_param('i', $order['order_id']);
        $iStmt->execute();
        $orderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $iStmt->close();

        $validationErrors = [];
        $reviewedCount    = 0;

        // Optional context fields
        $reviewerPosition = trim($_POST['position'] ?? '');
        $reviewerCompany  = trim($_POST['company']  ?? '');

        foreach ($orderItems as $item) {
            $itemId    = $item['order_item_id'];
            $productId = $item['product_id'];
            $rating    = intval($_POST["rating_{$itemId}"] ?? 0);
            $feedback  = trim($_POST["feedback_{$itemId}"] ?? '');

            // Skip if user left this item blank (optional per-item)
            if (!$rating && !$feedback) continue;

            if ($rating < 1 || $rating > 5) {
                $validationErrors[] = "Please select a star rating for " . htmlspecialchars($item['product_name']);
                continue;
            }
            if (strlen($feedback) < 10) {
                $validationErrors[] = "Review for \"" . htmlspecialchars($item['product_name']) . "\" must be at least 10 characters.";
                continue;
            }

            $fullName = trim($order['first_name'] . ' ' . $order['last_name']);
            $email    = $order['email'];
            $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua       = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // INSERT — includes position and company
            $rStmt = $conn->prepare("
                INSERT INTO reviews
                    (order_id, order_item_id, product_id,
                    full_name, email, rating, feedback,
                    position, company,
                    is_verified_purchase, status,
                    reviewer_ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', ?, ?)
            ");
            // i  i  i  s  s  i  s  s  s  s  s
            $rStmt->bind_param('iiisissssss',
                $order['order_id'], $itemId, $productId,
                $fullName, $email, $rating, $feedback,
                $reviewerPosition, $reviewerCompany,
                $ip, $ua
            );

            if ($rStmt->execute()) {
                $reviewId = $conn->insert_id;
                $rStmt->close();

                // Photo uploads
                $photoKey = "photos_{$itemId}";
                if (!empty($_FILES[$photoKey]['name'][0])) {
                    $uploadDir = __DIR__ . '/../uploads/reviews/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
                    foreach ($_FILES[$photoKey]['tmp_name'] as $idx => $tmpPath) {
                        if (!is_uploaded_file($tmpPath)) continue;
                        $mimeType = $_FILES[$photoKey]['type'][$idx];
                        $fileSize = $_FILES[$photoKey]['size'][$idx];
                        if ($fileSize > 5 * 1024 * 1024)       continue; // 5 MB max
                        if (!in_array($mimeType, $allowedMimes)) continue;

                        $origName = $_FILES[$photoKey]['name'][$idx];
                        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        $fileName = 'review_' . $reviewId . '_' . $idx . '_' . uniqid() . '.' . $ext;
                        $destPath = $uploadDir . $fileName;

                        if (move_uploaded_file($tmpPath, $destPath)) {
                            $relPath   = 'uploads/reviews/' . $fileName;
                            $uploadOrd = $idx + 1;
                            $aStmt = $conn->prepare("
                                INSERT INTO review_attachments
                                    (review_id, file_path, file_name, file_size, mime_type, upload_order)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $aStmt->bind_param('ississi',   // ← wait, review_id is int
                                $reviewId, $relPath, $fileName, $fileSize, $mimeType, $uploadOrd
                            );
                            // Correct: i s s i s i
                            $aStmt = $conn->prepare("
                                INSERT INTO review_attachments
                                    (review_id, file_path, file_name, file_size, mime_type, upload_order)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $aStmt->bind_param('issiis',
                                $reviewId, $relPath, $fileName, $fileSize, $mimeType, $uploadOrd
                            );
                            $aStmt->execute();
                            $aStmt->close();
                        }
                    }
                }

                // Mark item as reviewed
                $uStmt = $conn->prepare("
                    UPDATE order_items SET is_reviewed = 1, review_id = ?
                    WHERE order_item_id = ?
                ");
                $uStmt->bind_param('ii', $reviewId, $itemId);
                $uStmt->execute();
                $uStmt->close();

                $reviewedCount++;
            } else {
                $rStmt->close();
                $validationErrors[] = "Failed to save review for \"" . htmlspecialchars($item['product_name']) . "\".";
            }
        }

        if (empty($validationErrors) && $reviewedCount > 0) {
            $_SESSION['review_submitted'] = true;
            header("Location: ../review.php?order={$orderCode}&token={$token}");
            exit();
        }

        if (empty($validationErrors) && $reviewedCount === 0) {
            $validationErrors[] = "Please fill in at least one product review before submitting.";
        }

        $_SESSION['review_errors'] = $validationErrors;
        header("Location: ../review.php?order={$orderCode}&token={$token}");
        exit();
    }
}