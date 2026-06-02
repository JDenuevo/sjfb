<?php
// functions/save_checkout_draft.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}

$allowed = [
    'first_name', 'last_name', 'email', 'phone_number',
    'address', 'city', 'postal_code', 'delivery_notes', 'payment_method'
];

$existing = $_SESSION['pending_checkout'] ?? [];

foreach ($allowed as $field) {
    if (isset($_POST[$field])) {
        $existing[$field] = $_POST[$field];
    }
}

// Preserve the created_at timestamp if already set
if (empty($existing['created_at'])) {
    $existing['created_at'] = time();
}

$_SESSION['pending_checkout'] = $existing;

echo json_encode(['ok' => true]);
exit;