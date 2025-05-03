<?php
include '../../conn.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id']) && isset($_POST['product_id'])) {
    $image_id = intval($_POST['image_id']);
    $product_id = intval($_POST['product_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Check if this is the primary image
        $checkPrimaryQuery = "SELECT is_primary FROM product_images WHERE image_id = ?";
        $checkPrimaryStmt = $conn->prepare($checkPrimaryQuery);
        $checkPrimaryStmt->bind_param("i", $image_id);
        $checkPrimaryStmt->execute();
        $checkPrimaryResult = $checkPrimaryStmt->get_result();
        
        if ($checkPrimaryResult->num_rows === 0) {
            throw new Exception("Image not found");
        }
        
        $isPrimary = $checkPrimaryResult->fetch_assoc()['is_primary'];
        $checkPrimaryStmt->close();

        // 2. Get image path for file deletion
        $query = "SELECT image_path FROM product_images WHERE image_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image_path = '../../uploads/products/' . $row['image_path'];
        $stmt->close();

        // 3. Delete the image record
        $deleteQuery = "DELETE FROM product_images WHERE image_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $image_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // 4. Check how many images remain for this product
        $countQuery = "SELECT COUNT(*) AS count FROM product_images WHERE product_id = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("i", $product_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $remainingImages = $countResult->fetch_assoc()['count'];
        $countStmt->close();

        // 5. Handle primary image status
        if ($isPrimary == 1 || $remainingImages == 1) {
            // If we deleted the primary OR there's only one image left (regardless of its primary status)
            
            if ($remainingImages > 0) {
                // Find the first remaining image for this product
                $findNewPrimaryQuery = "SELECT image_id FROM product_images 
                                      WHERE product_id = ? 
                                      ORDER BY image_id ASC 
                                      LIMIT 1";
                $findNewPrimaryStmt = $conn->prepare($findNewPrimaryQuery);
                $findNewPrimaryStmt->bind_param("i", $product_id);
                $findNewPrimaryStmt->execute();
                $newPrimaryResult = $findNewPrimaryStmt->get_result();
                
                if ($newPrimaryResult->num_rows > 0) {
                    $newPrimaryId = $newPrimaryResult->fetch_assoc()['image_id'];
                    
                    // First reset all primary flags for this product to 0
                    $resetPrimaryQuery = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
                    $resetPrimaryStmt = $conn->prepare($resetPrimaryQuery);
                    $resetPrimaryStmt->bind_param("i", $product_id);
                    $resetPrimaryStmt->execute();
                    $resetPrimaryStmt->close();
                    
                    // Then set the new primary image
                    $updatePrimaryQuery = "UPDATE product_images SET is_primary = 1 WHERE image_id = ?";
                    $updatePrimaryStmt = $conn->prepare($updatePrimaryQuery);
                    $updatePrimaryStmt->bind_param("i", $newPrimaryId);
                    $updatePrimaryStmt->execute();
                    $updatePrimaryStmt->close();
                }
                $findNewPrimaryStmt->close();
            }
        }

        // Delete the physical file if it exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request";
}
$conn->close();
?>