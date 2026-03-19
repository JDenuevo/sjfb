<?php
session_start();
include '../conn.php';
session_unset();

session_destroy();

// Redirect to homepage
header("Location: ../index.php");
exit();
?>