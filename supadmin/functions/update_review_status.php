<?php
// superadmin/functions/update_review_status.php
session_start();
require '../../conn.php';
require_once 'activity_log_helper.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['review_id'], $_POST['status'])) {
    redirectWithMessage("../reviews.php", "Invalid request.", "error");
}

$review_id = intval($_POST['review_id']);
$status    = $_POST['status'];
$allowed   = ['pending', 'approved', 'rejected', 'spam'];

if (!in_array($status, $allowed, true)) {
    redirectWithMessage("../reviews.php", "Invalid status.", "error");
}

// Fetch old status for the activity log
$or = $conn->prepare("SELECT status, order_id FROM reviews WHERE review_id = ?");
$or->bind_param("i", $review_id);
$or->execute();
$oldReview = $or->get_result()->fetch_assoc();
$or->close();

if (!$oldReview) {
    redirectWithMessage("../reviews.php", "Review not found.", "error");
}

$stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE review_id = ?");
$stmt->bind_param("si", $status, $review_id);

if ($stmt->execute()) {
    $stmt->close();

    ['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

    logActivity(
        $conn,
        'review',
        $review_id,
        'Review status updated',
        $oldReview['status'],
        $status,
        "Review ID {$review_id} (Order #{$oldReview['order_id']}) status changed from '{$oldReview['status']}' to '{$status}'.",
        $actorId,
        $actorType
    );

    $conn->close();
    redirectWithMessage("../reviews.php", "Review marked as " . ucfirst($status) . ".", "success");
} else {
    $stmt->close();
    $conn->close();
    redirectWithMessage("../reviews.php", "Failed to update review status.", "error");
}
?>