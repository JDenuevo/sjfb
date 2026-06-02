<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecomv2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Add to conn.php after the connection setup
if (!function_exists('img_url')) {
    function img_url(?string $filename, string $fallback = 'default.png'): string {
        $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'];
        $f     = !empty($filename) ? basename($filename) : $fallback;
        return $proto . '://' . $host . '/sjfbi-js/functions/img.php?f=' . urlencode($f);
    }
}

?>
