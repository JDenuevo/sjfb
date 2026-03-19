<?php
// functions/validate_voucher.php
session_start();
header('Content-Type: application/json');
require_once '../conn.php';
require_once 'discount_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$code = $_POST['code'] ?? '';
$cart_total = floatval($_POST['cart_total'] ?? 0);
$city = $_POST['city'] ?? '';
$account_id = $_SESSION['account_id'] ?? null;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a voucher code']);
    exit;
}

// Get user groups
$user_groups = getUserGroups($account_id, $conn);

// Validate voucher
$voucher = validateVoucher($code, $cart_total, $user_groups, $account_id, $conn);

if (isset($voucher['error'])) {
    echo json_encode(['success' => false, 'message' => $voucher['error']]);
    exit;
}

if (!$voucher) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired voucher code']);
    exit;
}

// Calculate discount
$discount_amount = calculateDiscount(
    $voucher['discount_type'],
    $voucher['discount_value'],
    $cart_total,
    $voucher['max_discount'] ?? null
);

// Get delivery fee (you'll need to implement this based on your delivery fee logic)
$delivery_fee = getDeliveryFee($city, $cart_total - $discount_amount, $conn);

// Check free shipping
$free_shipping_rule = checkFreeShipping($cart_total - $discount_amount, $user_groups, $city, $conn);
$free_shipping_applied = $free_shipping_rule ? true : false;
$final_delivery_fee = $free_shipping_applied ? 0 : $delivery_fee;

// Calculate final total
$final_total = $cart_total - $discount_amount + $final_delivery_fee;

echo json_encode([
    'success' => true,
    'message' => 'Voucher applied successfully!',
    'voucher' => [
        'code' => $voucher['code'],
        'discount_type' => $voucher['discount_type'],
        'discount_value' => $voucher['discount_value'],
        'discount_amount' => $discount_amount,
        'description' => $voucher['description']
    ],
    'cart_total' => $cart_total,
    'discount_amount' => $discount_amount,
    'delivery_fee' => $final_delivery_fee,
    'free_shipping_applied' => $free_shipping_applied,
    'final_total' => $final_total
]);