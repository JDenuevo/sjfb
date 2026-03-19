<?php
/**
 * supadmin/functions/discounts_process.php
 *
 * Fixes applied vs. original:
 *   1. promotion_group  → promotion_groups  (wrong table name, all 3 actions)
 *   2. delete_promotion — removed broken chained prepare/execute
 *   3. add_free_shipping — removed duplicate prepare; fixed bind_param 'sdssssiii'
 *   4. edit_free_shipping — fixed bind_param 'sdssssiiii' (was 'sdsssssiii')
 *   5. add_voucher — fixed bind_param 'sssdddsssiiiii' (was 'sssdddsssiiii' — missing 'i')
 */
session_start();
require '../../conn.php';
require_once 'activity_log_helper.php';

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_field') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    } else {
        header('Location: ../discounts.php');
    }
    exit;
}

['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

$action = $_POST['action'] ?? '';

function redirectWithMessage(string $location, string $msg, string $type = 'success'): void {
    $_SESSION['message'] = ['text' => $msg, 'type' => $type];
    header("Location: $location");
    exit;
}

function rdMsg(string $msg, string $type = 'success'): void {
    redirectWithMessage('../discounts.php', $msg, $type);
}

function boolPost(string $key): int {
    return isset($_POST[$key]) && $_POST[$key] !== '0' ? 1 : 0;
}

function datePost(string $key): ?string {
    $v = trim($_POST[$key] ?? '');
    if (empty($v)) return null;
    return str_replace('T', ' ', $v) . (strlen($v) === 16 ? ':00' : '');
}

function decimalPost(string $key): ?float {
    $v = trim($_POST[$key] ?? '');
    return $v !== '' ? (float)$v : null;
}

// ── TOGGLE FIELD — AJAX ──────────────────────────────────────────────────────
if ($action === 'toggle_field') {
    header('Content-Type: application/json');
    $type  = $_POST['type']  ?? '';
    $id    = (int)($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = (int)($_POST['value'] ?? 0);

    $allowed = [
        'voucher'       => ['is_active','toggle_public','toggle_stackable'],
        'promotion'     => ['is_active','toggle_auto_apply','toggle_public'],
        'free_shipping' => ['is_active','toggle_auto_apply'],
    ];
    $tableMap = [
        'voucher'       => ['vouchers',           'voucher_id'],
        'promotion'     => ['promotions',          'promotion_id'],
        'free_shipping' => ['free_shipping_rules', 'rule_id'],
    ];

    if (!isset($allowed[$type]) || !in_array($field, $allowed[$type], true) || $id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }

    [$table, $pk] = $tableMap[$type];
    $stmt = $conn->prepare("UPDATE {$table} SET {$field} = ? WHERE {$pk} = ?");
    $stmt->bind_param('ii', $value, $id);

    if ($stmt->execute()) {
        $labels = ['is_active'=>'Status','toggle_public'=>'Visibility','toggle_stackable'=>'Stackable','toggle_auto_apply'=>'Auto-apply'];
        $label = $labels[$field] ?? $field;
        $status = $value ? 'enabled' : 'disabled';
        logActivity($conn, $type, $id, "{$label} toggled", null, (string)$value,
            "{$label} {$status} for {$type} ID {$id}", $actorId, $actorType);
        echo json_encode(['success' => true, 'message' => "{$label} {$status}."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    }
    exit;
}

// ── VOUCHER — ADD ────────────────────────────────────────────────────────────
if ($action === 'add_voucher') {
    $code              = strtoupper(trim($_POST['code'] ?? ''));
    $description       = trim($_POST['description'] ?? '');
    $discount_type     = $_POST['discount_type'] ?? 'percentage';
    $discount_value    = (float)($_POST['discount_value'] ?? 0);
    $max_discount      = decimalPost('max_discount');
    $minimum_order     = (float)($_POST['minimum_order'] ?? 0);
    $applicable_groups = $_POST['applicable_groups'] ?? 'all';
    $start_date        = datePost('start_date');
    $expiry_date       = datePost('expiry_date');
    $usage_limit       = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $per_user_limit    = (int)($_POST['per_user_limit'] ?? 1);
    $is_active         = boolPost('is_active');
    $toggle_public     = boolPost('toggle_public');
    $toggle_stackable  = boolPost('toggle_stackable');

    if (empty($code))         rdMsg('Voucher code is required.', 'error');
    if ($discount_value <= 0) rdMsg('Discount value must be greater than 0.', 'error');
    if (!$start_date || !$expiry_date) rdMsg('Start and expiry dates are required.', 'error');

    $ck = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ? LIMIT 1");
    $ck->bind_param('s', $code); $ck->execute();
    if ($ck->get_result()->num_rows > 0) rdMsg("Voucher code '{$code}' already exists.", 'error');
    $ck->close();

    // FIX #5: 14 params — code(s) desc(s) type(s) val(d) min(d) max(d) groups(s)
    //   start(s) expiry(s) usage(i) per_user(i) is_active(i) pub(i) stackable(i)
    //   = 'sssdddsssiiiii' (was 'sssdddsssiiii' — missing last 'i')
    $stmt = $conn->prepare("
        INSERT INTO vouchers
            (code, description, discount_type, discount_value, minimum_order, max_discount,
             applicable_groups, start_date, expiry_date, usage_limit, per_user_limit,
             is_active, toggle_public, toggle_stackable)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssdddsssiiiii',
        $code, $description, $discount_type, $discount_value, $minimum_order, $max_discount,
        $applicable_groups, $start_date, $expiry_date,
        $usage_limit, $per_user_limit, $is_active, $toggle_public, $toggle_stackable
    );
    if ($stmt->execute()) {
        logActivity($conn, 'voucher', $conn->insert_id, 'Voucher created', null,
            json_encode(['code' => $code, 'type' => $discount_type, 'value' => $discount_value]),
            "Voucher '{$code}' created.", $actorId, $actorType);
        rdMsg("Voucher '{$code}' created successfully!");
    }
    rdMsg('Failed to create voucher: ' . $conn->error, 'error');
}

// ── VOUCHER — EDIT ───────────────────────────────────────────────────────────
if ($action === 'edit_voucher') {
    $voucher_id        = (int)($_POST['voucher_id'] ?? 0);
    $code              = strtoupper(trim($_POST['code'] ?? ''));
    $description       = trim($_POST['description'] ?? '');
    $discount_type     = $_POST['discount_type'] ?? 'percentage';
    $discount_value    = (float)($_POST['discount_value'] ?? 0);
    $max_discount      = decimalPost('max_discount');
    $minimum_order     = (float)($_POST['minimum_order'] ?? 0);
    $applicable_groups = $_POST['applicable_groups'] ?? 'all';
    $start_date        = datePost('start_date');
    $expiry_date       = datePost('expiry_date');
    $usage_limit       = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $per_user_limit    = (int)($_POST['per_user_limit'] ?? 1);
    $is_active         = boolPost('is_active');
    $toggle_public     = boolPost('toggle_public');
    $toggle_stackable  = boolPost('toggle_stackable');

    if (!$voucher_id) rdMsg('Invalid voucher.', 'error');
    if (empty($code)) rdMsg('Code is required.', 'error');

    $ck = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ? AND voucher_id != ? LIMIT 1");
    $ck->bind_param('si', $code, $voucher_id); $ck->execute();
    if ($ck->get_result()->num_rows > 0) rdMsg("Code '{$code}' is already used by another voucher.", 'error');
    $ck->close();

    // 15 params — same 14 fields + voucher_id → 'sssdddsssiiiiii' (correct, unchanged)
    $stmt = $conn->prepare("
        UPDATE vouchers SET
            code=?, description=?, discount_type=?, discount_value=?, minimum_order=?, max_discount=?,
            applicable_groups=?, start_date=?, expiry_date=?, usage_limit=?, per_user_limit=?,
            is_active=?, toggle_public=?, toggle_stackable=?
        WHERE voucher_id=?
    ");
    $stmt->bind_param('sssdddsssiiiiii',
        $code, $description, $discount_type, $discount_value, $minimum_order, $max_discount,
        $applicable_groups, $start_date, $expiry_date,
        $usage_limit, $per_user_limit, $is_active, $toggle_public, $toggle_stackable,
        $voucher_id
    );
    if ($stmt->execute()) {
        logActivity($conn, 'voucher', $voucher_id, 'Voucher updated', null, null,
            "Voucher '{$code}' updated.", $actorId, $actorType);
        rdMsg("Voucher '{$code}' updated!");
    }
    rdMsg('Failed to update voucher: ' . $conn->error, 'error');
}

// ── VOUCHER — DELETE ─────────────────────────────────────────────────────────
if ($action === 'delete_voucher') {
    $id = (int)($_POST['item_id'] ?? 0);
    if (!$id) rdMsg('Invalid ID.', 'error');

    $get = $conn->prepare("SELECT code FROM vouchers WHERE voucher_id = ?");
    $get->bind_param('i', $id); $get->execute();
    $row = $get->get_result()->fetch_assoc(); $get->close();
    if (!$row) rdMsg('Voucher not found.', 'error');

    $conn->begin_transaction();
    try {
        $d1 = $conn->prepare("DELETE FROM voucher_usage WHERE voucher_id = ?");
        $d1->bind_param('i', $id); $d1->execute(); $d1->close();
        $d2 = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
        $d2->bind_param('i', $id); $d2->execute(); $d2->close();
        $conn->commit();
        logActivity($conn, 'voucher', $id, 'Voucher deleted', json_encode(['code' => $row['code']]),
            null, "Voucher '{$row['code']}' deleted.", $actorId, $actorType);
        rdMsg("Voucher '{$row['code']}' deleted.");
    } catch (Exception $e) {
        $conn->rollback();
        rdMsg('Delete failed: ' . $e->getMessage(), 'error');
    }
}

// ── PROMOTION — ADD ──────────────────────────────────────────────────────────
if ($action === 'add_promotion') {
    $name           = trim($_POST['promotion_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $discount_type  = $_POST['discount_type'] ?? 'percentage';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $max_discount   = decimalPost('max_discount');
    $minimum_order  = (float)($_POST['minimum_order'] ?? 0);
    $applicable_to  = $_POST['applicable_to'] ?? 'all';
    $usage_limit    = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $per_customer   = (int)($_POST['per_customer_limit'] ?? 1);
    $start_date     = datePost('start_date');
    $end_date       = datePost('end_date');
    $is_active      = boolPost('is_active');
    $toggle_auto    = boolPost('toggle_auto_apply');
    $toggle_public  = boolPost('toggle_public');
    $group_ids      = array_map('intval', (array)($_POST['group_ids'] ?? []));

    if (empty($name))         rdMsg('Promotion name is required.', 'error');
    if ($discount_value <= 0) rdMsg('Discount value must be > 0.', 'error');
    if (!$start_date || !$end_date) rdMsg('Start and end dates are required.', 'error');

    $conn->begin_transaction();
    try {
        // name(s) desc(s) type(s) val(d) min(d) max(d) app_to(s) start(s) end(s)
        // usage(i) per_cust(i) is_active(i) auto(i) public(i) = 14 → 'sssdddsssiiiii'
        $stmt = $conn->prepare("
            INSERT INTO promotions
                (promotion_name, description, discount_type, discount_value, minimum_order, max_discount,
                 applicable_to, start_date, end_date, usage_limit, per_customer_limit,
                 is_active, toggle_auto_apply, toggle_public)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssdddsssiiiii',
            $name, $description, $discount_type, $discount_value, $minimum_order, $max_discount,
            $applicable_to, $start_date, $end_date,
            $usage_limit, $per_customer, $is_active, $toggle_auto, $toggle_public
        );
        if (!$stmt->execute()) throw new Exception($conn->error);
        $promo_id = $conn->insert_id;
        $stmt->close();

        // FIX #1: promotion_group → promotion_groups
        if ($applicable_to === 'specific_groups' && !empty($group_ids)) {
            $gIns = $conn->prepare("INSERT INTO promotion_groups (promotion_id, group_id) VALUES (?, ?)");
            foreach ($group_ids as $gid) {
                if ($gid > 0) { $gIns->bind_param('ii', $promo_id, $gid); $gIns->execute(); }
            }
            $gIns->close();
        }
        $conn->commit();
        logActivity($conn, 'promotion', $promo_id, 'Promotion created', null,
            json_encode(['name' => $name]), "Promotion '{$name}' created.", $actorId, $actorType);
        rdMsg("Promotion '{$name}' created!");
    } catch (Exception $e) {
        $conn->rollback();
        rdMsg('Failed: ' . $e->getMessage(), 'error');
    }
}

// ── PROMOTION — EDIT ─────────────────────────────────────────────────────────
if ($action === 'edit_promotion') {
    $promo_id       = (int)($_POST['promotion_id'] ?? 0);
    $name           = trim($_POST['promotion_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $discount_type  = $_POST['discount_type'] ?? 'percentage';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $max_discount   = decimalPost('max_discount');
    $minimum_order  = (float)($_POST['minimum_order'] ?? 0);
    $applicable_to  = $_POST['applicable_to'] ?? 'all';
    $usage_limit    = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $per_customer   = (int)($_POST['per_customer_limit'] ?? 1);
    $start_date     = datePost('start_date');
    $end_date       = datePost('end_date');
    $is_active      = boolPost('is_active');
    $toggle_auto    = boolPost('toggle_auto_apply');
    $toggle_public  = boolPost('toggle_public');
    $group_ids      = array_map('intval', (array)($_POST['group_ids'] ?? []));

    if (!$promo_id) rdMsg('Invalid promotion.', 'error');

    $conn->begin_transaction();
    try {
        // 15 params: same 14 fields + promo_id → 'sssdddsssiiiiii'
        $stmt = $conn->prepare("
            UPDATE promotions SET
                promotion_name=?, description=?, discount_type=?, discount_value=?,
                minimum_order=?, max_discount=?, applicable_to=?, start_date=?, end_date=?,
                usage_limit=?, per_customer_limit=?, is_active=?, toggle_auto_apply=?, toggle_public=?
            WHERE promotion_id=?
        ");
        $stmt->bind_param('sssdddsssiiiiii',
            $name, $description, $discount_type, $discount_value, $minimum_order, $max_discount,
            $applicable_to, $start_date, $end_date,
            $usage_limit, $per_customer, $is_active, $toggle_auto, $toggle_public,
            $promo_id
        );
        if (!$stmt->execute()) throw new Exception($conn->error);
        $stmt->close();

        // FIX #1: promotion_group → promotion_groups
        $del = $conn->prepare("DELETE FROM promotion_groups WHERE promotion_id = ?");
        $del->bind_param('i', $promo_id); $del->execute(); $del->close();

        if ($applicable_to === 'specific_groups' && !empty($group_ids)) {
            $gIns = $conn->prepare("INSERT INTO promotion_groups (promotion_id, group_id) VALUES (?, ?)");
            foreach ($group_ids as $gid) {
                if ($gid > 0) { $gIns->bind_param('ii', $promo_id, $gid); $gIns->execute(); }
            }
            $gIns->close();
        }
        $conn->commit();
        logActivity($conn, 'promotion', $promo_id, 'Promotion updated', null, null,
            "Promotion '{$name}' updated.", $actorId, $actorType);
        rdMsg("Promotion '{$name}' updated!");
    } catch (Exception $e) {
        $conn->rollback();
        rdMsg('Failed: ' . $e->getMessage(), 'error');
    }
}

// ── PROMOTION — DELETE ───────────────────────────────────────────────────────
if ($action === 'delete_promotion') {
    $id = (int)($_POST['item_id'] ?? 0);
    if (!$id) rdMsg('Invalid ID.', 'error');

    $get = $conn->prepare("SELECT promotion_name FROM promotions WHERE promotion_id = ?");
    $get->bind_param('i', $id); $get->execute();
    $row = $get->get_result()->fetch_assoc(); $get->close();
    if (!$row) rdMsg('Promotion not found.', 'error');

    $conn->begin_transaction();
    try {
        // FIX #1 + #2: correct table name, clean separate statements (no broken chaining)
        $d1 = $conn->prepare("DELETE FROM promotion_groups WHERE promotion_id = ?");
        $d1->bind_param('i', $id); $d1->execute(); $d1->close();

        $d2 = $conn->prepare("DELETE FROM promotions WHERE promotion_id = ?");
        $d2->bind_param('i', $id); $d2->execute(); $d2->close();

        $conn->commit();
        logActivity($conn, 'promotion', $id, 'Promotion deleted',
            json_encode(['name' => $row['promotion_name']]), null,
            "Promotion '{$row['promotion_name']}' deleted.", $actorId, $actorType);
        rdMsg("Promotion '{$row['promotion_name']}' deleted.");
    } catch (Exception $e) {
        $conn->rollback();
        rdMsg('Delete failed: ' . $e->getMessage(), 'error');
    }
}

// ── FREE SHIPPING — ADD ──────────────────────────────────────────────────────
if ($action === 'add_free_shipping') {
    $name              = trim($_POST['rule_name'] ?? '');
    $minimum_order     = (float)($_POST['minimum_order'] ?? 0);
    $applicable_groups = $_POST['applicable_groups'] ?? 'all';
    $applicable_cities = trim($_POST['applicable_cities'] ?? '') ?: null;
    $priority          = (int)($_POST['priority'] ?? 0);
    $start_date        = datePost('start_date');
    $end_date          = datePost('end_date');
    $is_active         = boolPost('is_active');
    $toggle_auto       = boolPost('toggle_auto_apply');

    if (empty($name)) rdMsg('Rule name is required.', 'error');
    if (!$start_date || !$end_date) rdMsg('Start and end dates are required.', 'error');

    // FIX #3: single prepare, correct types
    // name(s) min(d) groups(s) cities(s) start(s) end(s) is_active(i) auto(i) priority(i)
    // = 9 params → 'sdssssiii'
    $stmt = $conn->prepare("
        INSERT INTO free_shipping_rules
            (rule_name, minimum_order, applicable_groups, applicable_cities,
             start_date, end_date, is_active, toggle_auto_apply, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sdssssiii',
        $name, $minimum_order, $applicable_groups, $applicable_cities,
        $start_date, $end_date, $is_active, $toggle_auto, $priority
    );
    if ($stmt->execute()) {
        logActivity($conn, 'free_shipping', $conn->insert_id, 'Free shipping rule created', null,
            json_encode(['name' => $name, 'min' => $minimum_order]),
            "Free shipping rule '{$name}' created.", $actorId, $actorType);
        rdMsg("Free shipping rule '{$name}' created!");
    }
    rdMsg('Failed: ' . $conn->error, 'error');
}

// ── FREE SHIPPING — EDIT ─────────────────────────────────────────────────────
if ($action === 'edit_free_shipping') {
    $rule_id           = (int)($_POST['rule_id'] ?? 0);
    $name              = trim($_POST['rule_name'] ?? '');
    $minimum_order     = (float)($_POST['minimum_order'] ?? 0);
    $applicable_groups = $_POST['applicable_groups'] ?? 'all';
    $applicable_cities = trim($_POST['applicable_cities'] ?? '') ?: null;
    $priority          = (int)($_POST['priority'] ?? 0);
    $start_date        = datePost('start_date');
    $end_date          = datePost('end_date');
    $is_active         = boolPost('is_active');
    $toggle_auto       = boolPost('toggle_auto_apply');

    if (!$rule_id) rdMsg('Invalid rule.', 'error');

    // FIX #4: name(s) min(d) groups(s) cities(s) start(s) end(s)
    //   is_active(i) auto(i) priority(i) rule_id(i) = 10 → 'sdssssiiii'
    //   (was 'sdsssssiii' — extra 's' before the ints)
    $stmt = $conn->prepare("
        UPDATE free_shipping_rules SET
            rule_name=?, minimum_order=?, applicable_groups=?, applicable_cities=?,
            start_date=?, end_date=?, is_active=?, toggle_auto_apply=?, priority=?
        WHERE rule_id=?
    ");
    $stmt->bind_param('sdssssiiii',
        $name, $minimum_order, $applicable_groups, $applicable_cities,
        $start_date, $end_date, $is_active, $toggle_auto, $priority, $rule_id
    );
    if ($stmt->execute()) {
        logActivity($conn, 'free_shipping', $rule_id, 'Free shipping rule updated', null, null,
            "Free shipping rule '{$name}' updated.", $actorId, $actorType);
        rdMsg("Rule '{$name}' updated!");
    }
    rdMsg('Failed: ' . $conn->error, 'error');
}

// ── FREE SHIPPING — DELETE ───────────────────────────────────────────────────
if ($action === 'delete_free_shipping') {
    $id = (int)($_POST['item_id'] ?? 0);
    if (!$id) rdMsg('Invalid ID.', 'error');

    $get = $conn->prepare("SELECT rule_name FROM free_shipping_rules WHERE rule_id = ?");
    $get->bind_param('i', $id); $get->execute();
    $row = $get->get_result()->fetch_assoc(); $get->close();
    if (!$row) rdMsg('Rule not found.', 'error');

    $stmt = $conn->prepare("DELETE FROM free_shipping_rules WHERE rule_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logActivity($conn, 'free_shipping', $id, 'Free shipping rule deleted',
            json_encode(['name' => $row['rule_name']]), null,
            "Free shipping rule '{$row['rule_name']}' deleted.", $actorId, $actorType);
        rdMsg("Rule '{$row['rule_name']}' deleted.");
    }
    rdMsg('Delete failed: ' . $conn->error, 'error');
}

rdMsg('Unknown action.', 'error');