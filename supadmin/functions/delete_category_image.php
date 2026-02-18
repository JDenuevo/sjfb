<?php
session_start();
require '../../conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
    
    // Get image path
    $query = "SELECT category_image FROM product_categories WHERE category_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = $row['category_image'];
        
        if (!empty($image_path)) {
            $full_path = "../../uploads/categories/" . $image_path;
            
            // Delete file if exists
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            
            // Update database to remove image reference
            $update = "UPDATE product_categories SET category_image = NULL WHERE category_id = ?";
            $update_stmt = $conn->prepare($update);
            $update_stmt->bind_param("i", $category_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }
            $update_stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'No image to delete']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No category ID provided']);
}

$conn->close();
?>