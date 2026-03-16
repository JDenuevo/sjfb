<?php
// functions/cleanup_orders.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../conn.php';

/**
 * Cancel any pending orders that have been abandoned
 * Run this via a cron job or on checkout page load
 */
function cleanupAbandonedOrders($conn) {
    // Cancel orders that are "Pending Payment" and older than 30 minutes
    $stmt = $conn->prepare("
        UPDATE orders 
        SET order_status = 'Cancelled' 
        WHERE order_status = 'Pending Payment' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $stmt->execute();
    $stmt->close();
    
    // Also cancel any orders that were in "Pending" status with no payment method
    // and are older than 24 hours
    $stmt2 = $conn->prepare("
        UPDATE orders 
        SET order_status = 'Cancelled' 
        WHERE order_status = 'Pending' 
        AND payment_method IS NULL
        AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt2->execute();
    $stmt2->close();
}

/**
 * Clean up abandoned checkout sessions in session data
 * Only removes if older than 2 hours (increased from 1 hour)
 */
function cleanupAbandonedCheckouts() {
    if (isset($_SESSION['pending_checkout'])) {
        // Remove pending checkout data older than 2 hours
        $createdAt = $_SESSION['pending_checkout']['created_at'] ?? 0;
        if (time() - $createdAt > 7200) { // 2 hours
            unset($_SESSION['pending_checkout']);
            unset($_SESSION['temp_checkout_ref']);
            unset($_SESSION['paymongo_session_id']);
        }
    }
}

/**
 * Get stored checkout data for pre-filling forms
 */
function getStoredCheckoutData() {
    return $_SESSION['pending_checkout'] ?? null;
}

/**
 * Clear checkout data on successful order
 */
function clearCheckoutData() {
    unset($_SESSION['pending_checkout']);
    unset($_SESSION['temp_checkout_ref']);
    unset($_SESSION['paymongo_session_id']);
}
?>