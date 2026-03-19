<!-- activity_log_helper.php -->

<?php
if (!function_exists('logActivity')) {

    /**
     * Write one row to activity_log.
     *
     * @param mysqli      $conn
     * @param string      $entityType   Table / domain this log entry belongs to
     * @param int         $entityId     Primary key of the affected record
     * @param string      $action       Short description of what happened
     * @param string|null $oldValue     Previous value (optional)
     * @param string|null $newValue     New value (optional)
     * @param string|null $details      Additional free-form context
     * @param int|null    $userId       account_id of acting user (null if guest/system)
     * @param string      $userType     Role string ('super_admin', 'admin', 'customer', 'rider', 'system')
     * @return bool
     */
    function logActivity(
        $conn,
        $entityType,
        $entityId,
        $action,
        $oldValue  = null,
        $newValue  = null,
        $details   = null,
        $userId    = null,
        $userType  = 'system'
    ): bool {
        $ipAddress = $_SERVER['REMOTE_ADDR']      ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT']   ?? null;

        $stmt = $conn->prepare("
            INSERT INTO activity_log
                (entity_type, entity_id, user_id, user_type, action,
                 old_value, new_value, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log('[logActivity] prepare failed: ' . $conn->error);
            return false;
        }

        $stmt->bind_param(
            'siisssssss',
            $entityType,   // s
            $entityId,     // i
            $userId,       // i  (nullable int — PHP will cast null → null OK with 'i')
            $userType,     // s
            $action,       // s
            $oldValue,     // s
            $newValue,     // s
            $details,      // s
            $ipAddress,    // s
            $userAgent     // s
        );

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('[logActivity] execute failed: ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }
}

/**
 * Resolve the acting user's ID and type from the current session.
 * Returns ['userId' => int|null, 'userType' => string]
 */
if (!function_exists('getActorFromSession')) {
    function getActorFromSession(): array {
        $userId   = $_SESSION['account_id'] ?? null;
        $userType = 'system';

        if      (isset($_SESSION['loggedinassupadmin'])) $userType = 'super_admin';
        elseif  (isset($_SESSION['loggedinasadmin']))    $userType = 'admin';
        elseif  (isset($_SESSION['loggedinasrider']))    $userType = 'rider';
        elseif  (isset($_SESSION['loggedinasuser']))     $userType = 'customer';

        return ['userId' => $userId, 'userType' => $userType];
    }
}