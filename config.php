<?php
// config.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/';
// If your project is in a subfolder, use something like:
// $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/your-project-folder/';

/**
 * Asset helper function
 * Generates the full URL for static assets (images, CSS, JS)
 * 
 * @param string $path The path to the asset relative to the root
 * @return string Full URL to the asset
 */
function asset($path) {
    global $baseUrl;
    return $baseUrl . ltrim($path, '/');
}

// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'your_database_name');

// Other global settings
define('SITE_NAME', 'St. Joseph Fish Brokerage Inc.');
define('SITE_EMAIL', 'stjosephbrokerage23@gmail.com');

// Cart initialization if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}