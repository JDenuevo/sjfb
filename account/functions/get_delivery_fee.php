<?php
session_start();
header('Content-Type: application/json');
require_once '../../conn.php';
require_once 'discount_helper.php';

$city       = trim($_GET['city']       ?? '');
$cart_total = (float)($_GET['cart_total'] ?? 0);

if (empty($city)) {
    echo json_encode([
        'success'                => false,
        'message'                => 'City is required',
        'delivery_fee'           => 0,
        'free_shipping_eligible' => false,
    ]);
    exit;
}

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

if (!$city_info) {
    echo json_encode([
        'success'                => false,
        'message'                => 'City not found.',
        'delivery_fee'           => 0,
        'free_shipping_eligible' => false,
    ]);
    exit;
}

$delivery_fee = getDeliveryFee($city, $cart_total, $conn);
$threshold    = (float)($city_info['free_shipping_threshold'] ?? 0);
$free_shipping_eligible = ($threshold > 0 && $cart_total >= $threshold);

if (!$free_shipping_eligible && $delivery_fee === 0.0) {
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
    if ($rule) $free_shipping_eligible = true;
}

echo json_encode([
    'success'                => true,
    'delivery_fee'           => $delivery_fee,
    'city'                   => $city_info['city'],
    'area_type'              => $city_info['area_type'] ?? 'Unknown',
    'base_fee'               => (float)$city_info['base_fee'],
    'free_shipping_threshold'=> $threshold > 0 ? $threshold : null,
    'free_shipping_eligible' => $free_shipping_eligible,
]);