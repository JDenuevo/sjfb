<?php
// Auto-login check for remember me functionality
if (!isset($_SESSION['account_id'])) {
    require_once __DIR__ . '/remember.php';
    validateRememberToken($conn);
}
?>