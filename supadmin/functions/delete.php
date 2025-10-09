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

elseif (isset($_POST['delete_product'], $_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);

    if ($product_id <= 0) {
        redirectWithMessage("../products.php", "Invalid product ID", "error");
    }

    $conn->begin_transaction();

    try {
        // Step 1: Fetch and delete product images from server
        $imageQuery = "SELECT image_path FROM product_images WHERE product_id = ?";
        $imageStmt = $conn->prepare($imageQuery);
        $imageStmt->bind_param("i", $product_id);
        $imageStmt->execute();
        $imageResult = $imageStmt->get_result();

        while ($imageRow = $imageResult->fetch_assoc()) {
            $image_path = '../../uploads/products/' . $imageRow['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $imageStmt->close();

        // Step 2: Delete product image records
        $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = ?";
        $deleteImagesStmt = $conn->prepare($deleteImagesQuery);
        $deleteImagesStmt->bind_param("i", $product_id);
        $deleteImagesStmt->execute();
        $deleteImagesStmt->close();

        // Step 3: SOFT DELETE product (archive it instead of hard delete)
        $softDeleteQuery = "UPDATE products SET is_deleted = 1, deleted_at = NOW() WHERE product_id = ?";
        $stmt = $conn->prepare($softDeleteQuery);
        $stmt->bind_param("i", $product_id);
        $stmt->execute(); // ✅ This was missing
        $stmt->close();

        $conn->commit();
        redirectWithMessage("../products.php", "Product deleted successfully", "success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../products.php", "Failed to deleted product: " . $e->getMessage(), "error");
    }
    exit();
}

// DELETE CATEGORY
elseif (isset($_POST['delete_category'], $_POST['category_id'])) {
    $category_id = intval($_POST['category_id']); // Ensure category_id is an integer

    // Validate category_id
    if ($category_id <= 0) {
        redirectWithMessage("../category.php", "Invalid category ID", "error");
    }

    // Check if category exists before deletion
    $query = "SELECT * FROM product_categories WHERE category_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Delete category
        $delete_category = "DELETE FROM product_categories WHERE category_id = ?";
        $stmt = $conn->prepare($delete_category);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();

        redirectWithMessage("../category.php", "Category deleted successfully", "success");
    } else {
        redirectWithMessage("../category.php", "Category not found", "error");
    }
    exit();
}

// Default redirect if no valid action is found
redirectWithMessage("../products.php", "Invalid request", "error");
?>
