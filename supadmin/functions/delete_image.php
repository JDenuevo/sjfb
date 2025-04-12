<?php
include '../../conn.php'; // Database connection

// Function to redirect with a session message
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type // success or error
    ];
    header("Location: $location");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id']) && isset($_POST['product_id'])) {
    $image_id = intval($_POST['image_id']);

    // Fetch image path before deletion
    $query = "SELECT image_path FROM product_images WHERE image_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $image_path = '../uploads/products/' . $row['image_path']; // Adjust path if needed

        // Delete the image record from database
        $deleteQuery = "DELETE FROM product_images WHERE image_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $image_id);

        if ($deleteStmt->execute()) {
            // Attempt to delete the file from the server
            if (file_exists($image_path) && unlink($image_path)) {
                echo "success";
            } else {
                echo "Image record deleted, but file not found.";
            }
        } else {
            echo "Failed to delete from database.";
        }
    } else {
        echo "Image not found.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request. Ensure the request is POST and contains image_id and product_id.";
}
?>
