<?php
session_start();
include '../conn.php';

// Redirect to homepage
header("Location: ../index.php");
exit();
?>