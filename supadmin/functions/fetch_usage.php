<?php
// functions/fetch_usage.php
session_start();
header('Content-Type: application/json');
require_once '../../conn.php';

// Disable error display to prevent HTML in JSON
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($type === 'voucher') {
    // Get voucher info
    $v = $conn->prepare("SELECT code, usage_limit FROM vouchers WHERE voucher_id = ?");
    $v->bind_param("i", $id);
    $v->execute();
    $voucher = $v->get_result()->fetch_assoc();
    $v->close();
    
    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Voucher not found']);
        exit;
    }
    
    // Get total usage
    $total = $conn->prepare("SELECT COUNT(*) as total_uses, COALESCE(SUM(discount_amount), 0) as total_discount FROM voucher_usage WHERE voucher_id = ?");
    $total->bind_param("i", $id);
    $total->execute();
    $stats = $total->get_result()->fetch_assoc();
    $total->close();
    
    // Convert to numbers
    $stats['total_uses'] = (int)$stats['total_uses'];
    $stats['total_discount'] = (float)$stats['total_discount'];
    
    // Get recent usage
    $recent = $conn->prepare("
        SELECT vu.*, o.order_code, 
               COALESCE(a.account_email, 'Guest') as email
        FROM voucher_usage vu
        LEFT JOIN orders o ON vu.order_id = o.order_id
        LEFT JOIN accounts a ON vu.account_id = a.account_id
        WHERE vu.voucher_id = ?
        ORDER BY vu.used_at DESC
        LIMIT 20
    ");
    $recent->bind_param("i", $id);
    $recent->execute();
    $stats['recent_uses'] = $recent->get_result()->fetch_all(MYSQLI_ASSOC);
    $recent->close();
    
    // Calculate remaining uses
    if ($voucher['usage_limit']) {
        $stats['remaining_uses'] = max(0, (int)$voucher['usage_limit'] - $stats['total_uses']);
    }
    
    echo json_encode(['success' => true] + $stats);
    exit;
    
} elseif ($type === 'promotion') {
    // Get promotion info
    $p = $conn->prepare("SELECT promotion_name, usage_limit FROM promotions WHERE promotion_id = ?");
    $p->bind_param("i", $id);
    $p->execute();
    $promotion = $p->get_result()->fetch_assoc();
    $p->close();
    
    if (!$promotion) {
        echo json_encode(['success' => false, 'message' => 'Promotion not found']);
        exit;
    }
    
    // Check if promotion_usage table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'promotion_usage'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(['success' => true, 'total_uses' => 0, 'total_discount' => 0, 'recent_uses' => []]);
        exit;
    }
    
    // Get total usage
    $total = $conn->prepare("SELECT COUNT(*) as total_uses, COALESCE(SUM(discount_amount), 0) as total_discount FROM promotion_usage WHERE promotion_id = ?");
    $total->bind_param("i", $id);
    $total->execute();
    $stats = $total->get_result()->fetch_assoc();
    $total->close();
    
    // Convert to numbers
    $stats['total_uses'] = (int)$stats['total_uses'];
    $stats['total_discount'] = (float)$stats['total_discount'];
    
    // Get recent usage
    $recent = $conn->prepare("
        SELECT pu.*, o.order_code,
               COALESCE(a.account_email, 'Guest') as email
        FROM promotion_usage pu
        LEFT JOIN orders o ON pu.order_id = o.order_id
        LEFT JOIN accounts a ON pu.account_id = a.account_id
        WHERE pu.promotion_id = ?
        ORDER BY pu.used_at DESC
        LIMIT 20
    ");
    $recent->bind_param("i", $id);
    $recent->execute();
    $stats['recent_uses'] = $recent->get_result()->fetch_all(MYSQLI_ASSOC);
    $recent->close();
    
    // Calculate remaining uses
    if ($promotion['usage_limit']) {
        $stats['remaining_uses'] = max(0, (int)$promotion['usage_limit'] - $stats['total_uses']);
    }
    
    echo json_encode(['success' => true] + $stats);
    exit;
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}
?>