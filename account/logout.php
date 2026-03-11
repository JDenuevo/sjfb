<?php
session_start();
include '../conn.php';

session_unset();
// Redirect to homepage
header("Location: ../index.php");
exit();
?>