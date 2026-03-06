<!-- ==================== in functions folder the super admin the update_review_status function manages the reviews the super admin will manage it ==================== -->

<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status'])) {
    $review_id = intval($_POST['review_id']);
    $status    = $_POST['status'];

    $allowed = ['pending', 'approved', 'rejected', 'spam'];
    if (!in_array($status, $allowed)) {
        redirectWithMessage("../reviews.php", "Invalid status.", "error");
    }

    $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE review_id = ?");
    $stmt->bind_param("si", $status, $review_id);

    if ($stmt->execute()) {
        $label = ucfirst($status);
        redirectWithMessage("../reviews.php", "Review has been marked as $label.", "success");
    } else {
        redirectWithMessage("../reviews.php", "Failed to update review status.", "error");
    }
    $stmt->close();
} else {
    redirectWithMessage("../reviews.php", "Invalid request.", "error");
}
?>