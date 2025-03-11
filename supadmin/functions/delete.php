<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['delete_account'])) {
    $account_id = $_POST['account_id'];

    $deleteQuery = "DELETE FROM accounts WHERE account_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $account_id);

    if ($stmt->execute()) {
        redirectWithMessage("../accounts.php", "Account successfully deleted!", "success");
    } else {
        redirectWithMessage("../accounts.php", "Failed to delete account.", "error");
    }
}
?>
