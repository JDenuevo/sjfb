<?php
session_start();
include '../conn.php';

$baseUrl = '/sjfbi-js/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$baseUrl}");
    exit();
}

$errors = [];

$input_username = strip_tags(trim($_POST['username'] ?? ''));
$input_password = $_POST['password_hash'] ?? '';

if (empty($input_username)) {
    $errors[] = "Username is required.";
}
if (empty($input_password)) {
    $errors[] = "Password is required.";
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: {$baseUrl}?showModal=true");
    exit();
}

// ── Fetch account — no column changes needed here (username, password_hash, role are unchanged)
$stmt = $conn->prepare("
    SELECT account_id, username, password_hash, role, is_deleted
    FROM accounts
    WHERE username = ?
    LIMIT 1
");

if (!$stmt) {
    $_SESSION['error_message'] = "A system error occurred. Please try again later.";
    header("Location: {$baseUrl}?showModal=true");
    exit();
}

$stmt->bind_param("s", $input_username);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['error_message'] = "Invalid username or password.";
    $stmt->close();
    header("Location: {$baseUrl}?showModal=true");
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

// ── Soft-deleted account ──────────────────────────────────────────────────────
if (!empty($row['is_deleted'])) {
    $_SESSION['error_message'] = "This account has been deactivated. Please contact support.";
    header("Location: {$baseUrl}?showModal=true");
    exit();
}

// ── Password check ────────────────────────────────────────────────────────────
if (!password_verify($input_password, $row['password_hash'])) {
    $_SESSION['error_message'] = "Invalid username or password.";
    header("Location: {$baseUrl}?showModal=true");
    exit();
}

// ── Set session ───────────────────────────────────────────────────────────────
session_regenerate_id(true);

$_SESSION['account_id'] = $row['account_id'];
$_SESSION['username']   = $row['username'];
$_SESSION['role']       = $row['role'];

// ── Role-based redirect ───────────────────────────────────────────────────────
switch ($row['role']) {
    case 'customer':
        $_SESSION['loggedinasuser'] = true;
        header("Location: {$baseUrl}account/shop.php");
        break;

    case 'admin':
        $_SESSION['loggedinasadmin'] = true;
        header("Location: {$baseUrl}admin/dashboard.php");
        break;

    case 'super_admin':
        $_SESSION['loggedinassupadmin'] = true;
        header("Location: {$baseUrl}supadmin/dashboard.php");
        break;

    case 'rider':
        $_SESSION['loggedinasrider'] = true;
        header("Location: {$baseUrl}rider/dashboard.php");
        break;

    default:
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error_message'] = "Your account role is not recognized. Please contact support.";
        header("Location: {$baseUrl}?showModal=true");
        break;
}

exit();
?>