 <!-- ==================== in functions folder the super admin the order_process functions that correlates the User, Guest, Rider, Admin and Super Admin to manage the whole order_process ==================== -->

<?php
// Use absolute path instead of relative path
require_once __DIR__ . '/../../conn.php';
require_once 'activity_log_helper.php';
require_once 'review_helper.php'; // ← add this

// Set timezone
date_default_timezone_set('Asia/Manila');

// Enhanced logging function for all activities
function logActivity($conn, $entityType, $entityId, $action, $oldValue = null, $newValue = null, $details = null, $userId = null, $userType = 'system') {
    // Get IP and User Agent for security tracking
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("
        INSERT INTO activity_log (entity_type, entity_id, user_id, user_type, action, old_value, new_value, details, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("siisssssss", $entityType, $entityId, $userId, $userType, $action, $oldValue, $newValue, $details, $ipAddress, $userAgent);
    $stmt->execute();
}

// Enhanced order status update with history tracking
function updateOrderStatus($conn, $orderId, $newStatus, $userId = null, $userType = 'system', $notes = '') {
    // Get current order details
    $stmt = $conn->prepare("SELECT order_status, payment_method, account_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    $oldStatus = $order['order_status'];
    $paymentMethod = strtolower($order['payment_method']);
    $accountId = $order['account_id'];
    
    // Validate status transition
    $validTransitions = [
        'Pending' => ['Processing', 'Cancelled'],
        'Processing' => ['OutForDelivery', 'Cancelled'],
        'OutForDelivery' => ['Delivered', 'Cancelled'],
        'Delivered' => [], // Final status
        'Cancelled' => [] // Final status
    ];
    
    if (!isset($validTransitions[$oldStatus]) || !in_array($newStatus, $validTransitions[$oldStatus])) {
        return ['success' => false, 'message' => "Invalid status transition from {$oldStatus} to {$newStatus}"];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update order status - handle case where updated_at might not exist
        $updateQuery = "UPDATE orders SET order_status = ?";
        
        // Check if updated_at column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
        if ($columnCheck->num_rows > 0) {
            $updateQuery .= ", updated_at = NOW()";
        }
        $updateQuery .= " WHERE order_id = ?";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $newStatus, $orderId);
        $updateStmt->execute();
        
        // Insert into status history
        $historyStmt = $conn->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, changed_by_user_id, changed_by_user_type, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $historyStmt->bind_param("issiis", $orderId, $oldStatus, $newStatus, $userId, $userType, $notes);
        $historyStmt->execute();
        
        // Log activity
        logActivity($conn, 'order', $orderId, "Status changed", $oldStatus, $newStatus, $notes, $userId, $userType);
        
        // Special handling for different statuses
        switch ($newStatus) {
            case 'Processing':
                logActivity($conn, 'order', $orderId, "Order approved for processing", null, null, "Order moved to processing queue", $userId, $userType);
                break;
                
            case 'OutForDelivery':
                logActivity($conn, 'delivery', $orderId, "Order out for delivery", null, null, "Order dispatched for delivery", $userId, $userType);
                break;
                
            case 'Delivered':
                // Handle COD payment completion
                if ($paymentMethod === 'cod') {
                    updatePaymentStatus($conn, $orderId, 'Paid', $userId, $userType, "COD payment collected upon delivery");
                }
                logActivity($conn, 'order', $orderId, "Order delivered", null, null, "Order successfully delivered to customer", $userId, $userType);
                break;
                
            case 'Cancelled':
                // Handle automatic refund for paid online orders
                if ($paymentMethod !== 'cod') {
                    $payment = getOrderPayment($conn, $orderId);
                    if ($payment && $payment['payment_status'] === 'Paid') {
                        processRefund($conn, $orderId, $payment['amount'], "Order cancellation", $userId, $userType);
                    }
                }
                logActivity($conn, 'order', $orderId, "Order cancelled", null, null, $notes, $userId, $userType);
                break;
        }
        
        $conn->commit();
        return ['success' => true, 'message' => "Order status updated to {$newStatus}"];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()];
    }
}

// Function to update rider availability based on active deliveries
function updateRiderAvailability($conn, $riderId) {
    // Count active deliveries for this rider
    $activeStmt = $conn->prepare("
        SELECT COUNT(*) as active_count 
        FROM orders 
        WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'
    ");
    $activeStmt->bind_param("i", $riderId);
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();
    $activeCount = $activeResult->fetch_assoc()['active_count'];
    
    // Get current availability for logging
    $currentStmt = $conn->prepare("SELECT is_available FROM riders WHERE rider_id = ?");
    $currentStmt->bind_param("i", $riderId);
    $currentStmt->execute();
    $currentResult = $currentStmt->get_result();
    $currentAvailability = $currentResult->fetch_assoc()['is_available'];
    
    // Set availability based on active delivery count
    $newAvailability = ($activeCount == 0) ? 1 : 0;
    
    // Only update if there's a change
    if ($currentAvailability != $newAvailability) {
        $updateStmt = $conn->prepare("UPDATE riders SET is_available = ?, updated_at = NOW() WHERE rider_id = ?");
        $updateStmt->bind_param("ii", $newAvailability, $riderId);
        $updateStmt->execute();
        
        error_log("Rider {$riderId} availability changed from {$currentAvailability} to {$newAvailability} (active deliveries: {$activeCount})");
    } else {
        error_log("Rider {$riderId} availability unchanged at {$currentAvailability} (active deliveries: {$activeCount})");
    }
    
    return $newAvailability;
}

// Enhanced payment status update
function updatePaymentStatus($conn, $orderId, $newStatus, $userId = null, $userType = 'system', $notes = '') {
    $payment = getOrderPayment($conn, $orderId);
    if (!$payment) {
        return ['success' => false, 'message' => 'Payment record not found'];
    }
    
    $oldStatus = $payment['payment_status'];
    
    $stmt = $conn->prepare("UPDATE payments SET payment_status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);
    
    if ($stmt->execute()) {
        // Log payment status change
        logActivity($conn, 'payment', $payment['payment_id'], "Payment status changed", $oldStatus, $newStatus, $notes, $userId, $userType);
        return ['success' => true, 'message' => "Payment status updated to {$newStatus}"];
    }
    
    return ['success' => false, 'message' => 'Failed to update payment status'];
}

// Get payment record for an order
function getOrderPayment($conn, $orderId) {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Enhanced refund processing
function processRefund($conn, $orderId, $refundAmount, $refundReason, $userId = null, $userType = 'system') {
    $order = getOrderDetails($conn, $orderId);
    $payment = getOrderPayment($conn, $orderId);
    
    if (!$payment || $payment['payment_status'] !== 'Paid') {
        return ['success' => false, 'message' => 'Cannot refund unpaid order'];
    }
    
    $refundAmount = ($refundAmount === 'full') ? $order['total_price'] : floatval($refundAmount);
    
    if ($refundAmount <= 0 || $refundAmount > $order['total_price']) {
        return ['success' => false, 'message' => 'Invalid refund amount'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Update payment with refund information
        $stmt = $conn->prepare("
            UPDATE payments 
            SET refunded_amount = ?, payment_status = 'Refunded', refund_reason = ?, refund_processed_at = NOW(), updated_at = NOW() 
            WHERE order_id = ?
        ");
        $stmt->bind_param("dsi", $refundAmount, $refundReason, $orderId);
        $stmt->execute();
        
        // Update order status to cancelled if not already
        if ($order['order_status'] !== 'Cancelled') {
            updateOrderStatus($conn, $orderId, 'Cancelled', $userId, $userType, "Order cancelled due to refund: {$refundReason}");
        }
        
        // Log refund activity
        logActivity($conn, 'refund', $payment['payment_id'], "Refund processed", null, $refundAmount, 
                   "Amount: ₱" . number_format($refundAmount, 2) . " | Reason: " . $refundReason, $userId, $userType);
        
        $conn->commit();
        return ['success' => true, 'message' => 'Refund processed successfully'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to process refund: ' . $e->getMessage()];
    }
}

// Get order details
function getOrderDetails($conn, $orderId) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get all riders (no status check)
function getAvailableRiders($conn) {
    $stmt = $conn->prepare("
        SELECT r.*, a.first_name, a.last_name, a.email 
        FROM riders r 
        JOIN accounts a ON r.account_id = a.account_id 
        ORDER BY a.first_name ASC
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Assign rider to order (no rider status logic)
function assignRiderToOrder($conn, $orderId, $riderId, $userId = null, $userType = 'super_admin', $notes = '') {
    $conn->begin_transaction();
    
    try {
        // Update order with rider assignment
        $stmt = $conn->prepare("UPDATE orders SET assigned_rider_id = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("ii", $riderId, $orderId);
        $stmt->execute();
        
        // Get rider details for logging
        $riderDetails = $conn->prepare("SELECT a.first_name, a.last_name FROM riders r JOIN accounts a ON r.account_id = a.account_id WHERE r.rider_id = ?");
        $riderDetails->bind_param("i", $riderId);
        $riderDetails->execute();
        $rider = $riderDetails->get_result()->fetch_assoc();
        
        // Log assignment
        logActivity($conn, 'delivery', $orderId, "Rider assigned", null, $riderId, "Assigned to: {$rider['first_name']} {$rider['last_name']} | Notes: {$notes}", $userId, $userType);
        
        // Update order status to OutForDelivery
        updateOrderStatus($conn, $orderId, 'OutForDelivery', $userId, $userType, "Assigned to rider for delivery");
        
        $conn->commit();
        return ['success' => true, 'message' => 'Rider assigned successfully'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Failed to assign rider: ' . $e->getMessage()];
    }
}

// Validate user permissions
function validateUserPermission($userType, $action) {
    $permissions = [
        'super_admin' => ['approve_order', 'assign_rider', 'cancel_order', 'process_refund', 'mark_delivered'],
        'rider' => ['mark_delivered', 'update_location'],
        'customer' => ['cancel_order', 'confirm_receipt', 'request_refund'],
        'admin' => ['approve_order', 'assign_rider', 'cancel_order', 'process_refund'] // Add admin permissions if needed
    ];
    
    return isset($permissions[$userType]) && in_array($action, $permissions[$userType]);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['account_id'] ?? null;
    $userType = 'super_admin'; // Default for admin panel
    
    // Determine user type based on session
    if (isset($_SESSION['loggedinassupadmin'])) {
        $userType = 'super_admin';
    } elseif (isset($_SESSION['loggedinasadmin'])) {
        $userType = 'admin';
    } elseif (isset($_SESSION['loggedinasuser'])) {
        $userType = 'customer';
    } elseif (isset($_SESSION['loggedinasrider'])) {
        $userType = 'rider';
    }
    
    // Approve Order (Pending -> Processing)
    if (isset($_POST['approve_order'])) {
        $orderId    = (int)$_POST['order_id'];
        $notes      = trim($_POST['notes'] ?? 'Order approved by admin');
        $redirectTo = $_POST['redirect_to'] ?? 'referrer';

        if (!validateUserPermission($userType, 'approve_order')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        $result = updateOrderStatus($conn, $orderId, 'Processing', $userId, $userType, $notes);

        if ($result['success']) {
            // Notify customer their order is being prepared
            $notify = dispatchOrderApprovedNotification($conn, $orderId, $userId, $userType);
            error_log("[order_process] Approved notification: SMS=" . ($notify['sms_sent'] ? 'OK' : 'fail') . " Email=" . ($notify['email_sent'] ? 'OK' : 'fail'));

            $_SESSION['message'] = ['text' => 'Order approved successfully!', 'type' => 'success'];
            if ($redirectTo === 'order_manage') {
                header("Location: ../order_manage.php?order_id=" . $orderId);
            } else {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            }
        } else {
            $_SESSION['message'] = ['text' => $result['message'], 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }
        exit();
    }

    // Assign Rider (Processing -> OutForDelivery)
    elseif (isset($_POST['assign_rider'])) {
        $orderId = (int)$_POST['order_id'];
        $riderId = (int)$_POST['rider_id'];
        $notes   = trim($_POST['notes'] ?? '');

        if (!validateUserPermission($userType, 'assign_rider')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Set rider as busy immediately
        $updateRiderStmt = $conn->prepare("UPDATE riders SET is_available = 0 WHERE rider_id = ?");
        $updateRiderStmt->bind_param("i", $riderId);
        $updateRiderStmt->execute();
        error_log("Rider {$riderId} set to busy (new delivery assigned)");

        $result = assignRiderToOrder($conn, $orderId, $riderId, $userId, $userType, $notes);

        if ($result['success']) {
            // Notify customer their order is on the way (includes rider name)
            $notify = dispatchOutForDeliveryNotification($conn, $orderId, $userId, $userType);
            error_log("[order_process] OutForDelivery notification: SMS=" . ($notify['sms_sent'] ? 'OK' : 'fail') . " Email=" . ($notify['email_sent'] ? 'OK' : 'fail'));
        }

        $_SESSION['message'] = ['text' => $result['message'], 'type' => $result['success'] ? 'success' : 'error'];
        header("Location: ../order_manage.php?order_id=" . $orderId);
        exit();
    }

    // Mark as Delivered (OutForDelivery -> Delivered)
    elseif (isset($_POST['mark_delivered'])) {
        $orderId = (int)$_POST['order_id'];
        $notes   = trim($_POST['notes'] ?? 'Order delivered successfully');

        if (!validateUserPermission($userType, 'mark_delivered')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Get the rider ID for this order first
        $riderId     = null;
        $riderIdStmt = $conn->prepare("SELECT assigned_rider_id FROM orders WHERE order_id = ?");
        $riderIdStmt->bind_param("i", $orderId);
        $riderIdStmt->execute();
        $riderResult = $riderIdStmt->get_result();
        if ($riderResult->num_rows > 0) {
            $riderId = $riderResult->fetch_assoc()['assigned_rider_id'];
        }

        $result = updateOrderStatus($conn, $orderId, 'Delivered', $userId, $userType, $notes);

        if ($result['success']) {
            // Save delivery timestamp
            $deliveredStmt = $conn->prepare("UPDATE orders SET delivered_at = NOW() WHERE order_id = ?");
            $deliveredStmt->bind_param("i", $orderId);
            $deliveredStmt->execute();

            if ($riderId) {
                updateRiderAvailability($conn, $riderId);
            }

            // Notify customer + send review invite link
            $invite = dispatchReviewInvite($conn, $orderId, $userId, $userType);
            error_log("[order_process] Review invite: " . $invite['message']);
        }

        $_SESSION['message'] = ['text' => $result['message'], 'type' => $result['success'] ? 'success' : 'error'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Cancel Order
    elseif (isset($_POST['cancel_order'])) {
        $orderId = (int)$_POST['order_id'];
        $reason  = trim($_POST['reason'] ?? 'Order cancelled by user');

        if (!validateUserPermission($userType, 'cancel_order')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        $result = updateOrderStatus($conn, $orderId, 'Cancelled', $userId, $userType, $reason);

        if ($result['success']) {
            // Notify customer their order was cancelled (includes reason)
            $notify = dispatchCancelledNotification($conn, $orderId, $reason, $userId, $userType);
            error_log("[order_process] Cancelled notification: SMS=" . ($notify['sms_sent'] ? 'OK' : 'fail') . " Email=" . ($notify['email_sent'] ? 'OK' : 'fail'));
        }

        $_SESSION['message'] = ['text' => $result['message'], 'type' => $result['success'] ? 'success' : 'error'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Confirm Receipt (Customer confirmation)
    elseif (isset($_POST['confirm_receipt'])) {
        $orderId = (int)$_POST['order_id'];
        $notes = 'Customer confirmed receipt of order';
        
        if (!validateUserPermission($userType, 'confirm_receipt')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
        // Log customer confirmation (order is already delivered)
        logActivity($conn, 'order', $orderId, "Receipt confirmed by customer", null, null, $notes, $userId, $userType);
        
        $_SESSION['message'] = ['text' => 'Thank you for confirming receipt!', 'type' => 'success'];
        header("Location: " . ($_SESSION['loggedinasuser'] ? '../user/orders.php' : $_SERVER['HTTP_REFERER']));
        exit();
    }
    
    // Process Refund
    elseif (isset($_POST['process_refund'])) {
        $orderId = (int)$_POST['order_id'];
        $refundAmount = $_POST['refund_amount'] ?? 'full';
        $refundReason = trim($_POST['refund_reason'] ?? 'Refund processed by admin');
        
        if (!validateUserPermission($userType, 'process_refund')) {
            $_SESSION['message'] = ['text' => 'Insufficient permissions', 'type' => 'error'];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
        
        $result = processRefund($conn, $orderId, $refundAmount, $refundReason, $userId, $userType);
        $_SESSION['message'] = ['text' => $result['message'], 'type' => $result['success'] ? 'success' : 'error'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Get order timeline
function getOrderTimeline($conn, $orderId) {
    $stmt = $conn->prepare("
        SELECT osh.*, a.first_name, a.last_name 
        FROM order_status_history osh 
        LEFT JOIN accounts a ON osh.changed_by_user_id = a.account_id 
        WHERE osh.order_id = ? 
        ORDER BY osh.created_at DESC
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get activity log for an entity
function getActivityLog($conn, $entityType, $entityId, $limit = 50) {
    $stmt = $conn->prepare("
        SELECT al.*, a.first_name, a.last_name 
        FROM activity_log al 
        LEFT JOIN accounts a ON al.user_id = a.account_id 
        WHERE al.entity_type = ? AND al.entity_id = ? 
        ORDER BY al.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("sii", $entityType, $entityId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>