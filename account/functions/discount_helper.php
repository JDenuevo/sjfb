<?php
// functions/discount_helper.php

/**
 * Get user's group codes
 */
function getUserGroups($account_id, $conn) {
    if (!$account_id) return ['regular']; // Guest treated as regular
    
    $stmt = $conn->prepare("
        SELECT g.group_code 
        FROM account_groups ag
        JOIN customer_groups g ON ag.group_id = g.group_id
        WHERE ag.account_id = ? AND (ag.expires_at IS NULL OR ag.expires_at > NOW())
        ORDER BY g.priority DESC
    ");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $groups = ['regular'];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row['group_code'];
    }
    return $groups;
}

/**
 * Validate voucher code
 */
function validateVoucher($code, $cart_total, $user_groups, $account_id, $conn) {
    $stmt = $conn->prepare("
        SELECT * FROM vouchers 
        WHERE code = ? 
          AND is_active = 1
          AND NOW() BETWEEN start_date AND expiry_date
          AND minimum_order <= ?
    ");
    $stmt->bind_param("sd", $code, $cart_total);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();
    
    if (!$voucher) return null;
    
    // Check group eligibility
    if ($voucher['applicable_groups'] !== 'all') {
        $group_map = [
            'vip_only' => 'vip',
            'employee_only' => 'employee',
            'subscriber_only' => 'subscriber'
        ];
        $required_group = $group_map[$voucher['applicable_groups']] ?? null;
        if ($required_group && !in_array($required_group, $user_groups)) {
            return ['error' => 'This voucher is not valid for your account type'];
        }
    }
    
    // Check usage limits
    if ($voucher['usage_limit']) {
        $usage = $conn->prepare("SELECT COUNT(*) as used FROM voucher_usage WHERE voucher_id = ?");
        $usage->bind_param("i", $voucher['voucher_id']);
        $usage->execute();
        if ($usage->get_result()->fetch_assoc()['used'] >= $voucher['usage_limit']) {
            return ['error' => 'Voucher usage limit reached'];
        }
    }
    
    // Check per-user limit
    if ($account_id && $voucher['per_user_limit']) {
        $user_usage = $conn->prepare("
            SELECT COUNT(*) as used FROM voucher_usage 
            WHERE voucher_id = ? AND account_id = ?
        ");
        $user_usage->bind_param("ii", $voucher['voucher_id'], $account_id);
        $user_usage->execute();
        if ($user_usage->get_result()->fetch_assoc()['used'] >= $voucher['per_user_limit']) {
            return ['error' => 'You have already used this voucher'];
        }
    }
    
    return $voucher;
}

/**
 * Calculate discount amount
 */
function calculateDiscount($type, $value, $cart_total, $max_discount = null) {
    if ($type === 'percentage') {
        $discount = $cart_total * ($value / 100);
        if ($max_discount && $discount > $max_discount) {
            $discount = $max_discount;
        }
    } else {
        $discount = min($value, $cart_total);
    }
    return round($discount, 2);
}

/**
 * Check free shipping eligibility
 */
function checkFreeShipping($cart_total, $user_groups, $city, $conn) {
    $group_list = "'" . implode("','", $user_groups) . "'";
    
    $stmt = $conn->prepare("
        SELECT * FROM free_shipping_rules 
        WHERE is_active = 1
          AND toggle_auto_apply = 1
          AND NOW() BETWEEN start_date AND end_date
          AND minimum_order <= ?
          AND (
              applicable_groups = 'all'
              OR applicable_groups IN (
                  SELECT CONCAT(group_code, '_only') FROM customer_groups 
                  WHERE group_code IN ($group_list)
              )
          )
        ORDER BY priority DESC, minimum_order ASC
        LIMIT 1
    ");
    $stmt->bind_param("d", $cart_total);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get delivery fee based on city and cart total
 */
function getDeliveryFee($city, $cart_total, $conn) {
    if (empty($city)) return 250.00;

    $city = trim($city);

    // ── Step 1: get the base fee for this city ─────────────────────────────
    $fee_data = null;

    $stmt = $conn->prepare("
        SELECT base_fee, free_shipping_threshold
        FROM delivery_fees
        WHERE city = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $fee_data = $result->fetch_assoc();
    } else {
        // Fuzzy fallback for typos
        $stmt2 = $conn->prepare("
            SELECT city, base_fee, free_shipping_threshold,
                   MATCH(city) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
            FROM delivery_fees
            WHERE is_active = 1
            ORDER BY relevance DESC
            LIMIT 1
        ");
        $stmt2->bind_param("s", $city);
        $stmt2->execute();
        $r2 = $stmt2->get_result();
        if ($r2->num_rows > 0) {
            $fee_data = $r2->fetch_assoc();
        }
    }

    $base_fee = $fee_data ? (float)$fee_data['base_fee'] : 250.00;

    // ── Step 2: check city-level free shipping threshold ───────────────────
    if ($fee_data && $fee_data['free_shipping_threshold'] && $cart_total >= (float)$fee_data['free_shipping_threshold']) {
        return 0.00;
    }

    // ── Step 3: check global free_shipping_rules table ────────────────────
    // This is where "Free Shipping Over ₱1000" lives
    $ruleStmt = $conn->prepare("
        SELECT rule_id
        FROM free_shipping_rules
        WHERE is_active = 1
          AND toggle_auto_apply = 1
          AND NOW() BETWEEN start_date AND end_date
          AND minimum_order <= ?
          AND applicable_groups = 'all'
        ORDER BY priority DESC
        LIMIT 1
    ");
    $ruleStmt->bind_param("d", $cart_total);
    $ruleStmt->execute();
    $rule = $ruleStmt->get_result()->fetch_assoc();

    if ($rule) {
        return 0.00; // Global free shipping rule matched
    }

    return $base_fee;
}

/**
 * Get all available cities for autocomplete
 */
function getAvailableCities($conn) {
    $result = $conn->query("
        SELECT city, area_type, base_fee, free_shipping_threshold 
        FROM delivery_fees 
        WHERE is_active = 1 
        ORDER BY area_type, city
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}