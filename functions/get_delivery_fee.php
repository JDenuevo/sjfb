<?php
// functions/get_delivery_fee.php
session_start();
header('Content-Type: application/json');
require_once '../conn.php';
require_once 'discount_helper.php';

$city       = trim($_GET['city']       ?? '');
$cart_total = (float)($_GET['cart_total'] ?? 0);

if (empty($city)) {
    echo json_encode([
        'success'               => false,
        'message'               => 'City is required',
        'delivery_fee'          => 250.00,
        'free_shipping_eligible'=> false,
    ]);
    exit;
}

$delivery_fee = getDeliveryFee($city, $cart_total, $conn);

// Fetch city row for display info
$stmt = $conn->prepare("
    SELECT city, area_type, base_fee, free_shipping_threshold
    FROM delivery_fees
    WHERE city = ? AND is_active = 1
    LIMIT 1
");
$stmt->bind_param('s', $city);
$stmt->execute();
$city_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// free_shipping_eligible is TRUE only when:
//   (a) the city has a threshold explicitly set (not NULL, not 0), AND
//   (b) the cart total actually meets or exceeds that threshold
$threshold = $city_info ? (float)($city_info['free_shipping_threshold'] ?? 0) : 0;
$free_shipping_eligible = ($threshold > 0 && $cart_total >= $threshold);

// Also check free_shipping_rules table (global rules)
// Only set eligible if the fee actually came back as 0 AND there's a real rule
if (!$free_shipping_eligible && $delivery_fee === 0.0) {
    // Verify a global rule actually triggered (not just a ₱0 base fee city)
    $ruleCheck = $conn->prepare("
        SELECT rule_id FROM free_shipping_rules
        WHERE is_active = 1
          AND toggle_auto_apply = 1
          AND NOW() BETWEEN start_date AND end_date
          AND minimum_order <= ?
          AND applicable_groups = 'all'
        LIMIT 1
    ");
    $ruleCheck->bind_param('d', $cart_total);
    $ruleCheck->execute();
    $rule = $ruleCheck->get_result()->fetch_assoc();
    $ruleCheck->close();
    if ($rule) {
        $free_shipping_eligible = true;
    }
    // If no rule either, fee is just ₱0.00 (e.g. city's base_fee is 0)
    // free_shipping_eligible stays false — show ₱0.00, not "FREE" banner
}

echo json_encode([
    'success'                => true,
    'delivery_fee'           => $delivery_fee,
    'city'                   => $city_info ? $city_info['city'] : $city,
    'area_type'              => $city_info['area_type'] ?? 'Unknown',
    'base_fee'               => $city_info ? (float)$city_info['base_fee'] : 250.00,
    'free_shipping_threshold'=> $threshold > 0 ? $threshold : null,
    'free_shipping_eligible' => $free_shipping_eligible,
]);