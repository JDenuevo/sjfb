<?php
session_start();
include '../conn.php';
require_once '../functions/remember.php';

// Logout and clear remember me token
logoutAndClearRemember($conn);

// Redirect to homepage
header("Location: ../index.php");
exit();
?>