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
    $product_id = intval($_POST['product_id']); // Ensure product_id is an integer

    // Validate product_id
    if ($product_id <= 0) {
        redirectWithMessage("../products.php", "Invalid product ID", "error");
    }

    // Begin transaction for atomicity
    $conn->begin_transaction();

    try {
        // Step 1: Fetch and delete product images
        $imageQuery = "SELECT image_path FROM product_images WHERE product_id = ?";
        $imageStmt = $conn->prepare($imageQuery);
        $imageStmt->bind_param("i", $product_id);
        $imageStmt->execute();
        $imageResult = $imageStmt->get_result();

        // Delete image files from the server
        while ($imageRow = $imageResult->fetch_assoc()) {
            $image_path = '../../uploads/products/' . $imageRow['image_path']; // Adjust path if needed
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the file
            }
        }

        // Delete image records from the database
        $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = ?";
        $deleteImagesStmt = $conn->prepare($deleteImagesQuery);
        $deleteImagesStmt->bind_param("i", $product_id);
        $deleteImagesStmt->execute();
        $deleteImagesStmt->close();

        // Step 2: Delete product variants
        $deleteVariantsQuery = "DELETE FROM product_variants WHERE product_id = ?";
        $deleteVariantsStmt = $conn->prepare($deleteVariantsQuery);
        $deleteVariantsStmt->bind_param("i", $product_id);
        $deleteVariantsStmt->execute();
        $deleteVariantsStmt->close();

        // Step 3: Delete the product
        $deleteProductQuery = "DELETE FROM products WHERE product_id = ?";
        $deleteProductStmt = $conn->prepare($deleteProductQuery);
        $deleteProductStmt->bind_param("i", $product_id);
        $deleteProductStmt->execute();
        $deleteProductStmt->close();

        // Commit the transaction
        $conn->commit();

        redirectWithMessage("../products.php", "Product and associated data deleted successfully", "success");
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        redirectWithMessage("../products.php", "Failed to delete product: " . $e->getMessage(), "error");
    }
    exit();
}

// DELETE IMAGE
elseif (isset($_POST['delete_image'], $_POST['image_id'], $_POST['product_id'])) {
    $image_id = intval($_POST['image_id']); // Ensure image_id is an integer
    $product_id = intval($_POST['product_id']); // Ensure product_id is an integer

    // Validate image_id and product_id
    if ($image_id <= 0 || $product_id <= 0) {
        redirectWithMessage("../products.php", "Invalid image or product ID", "error");
    }

    // Fetch image path
    $query = "SELECT image_path FROM product_images WHERE image_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $image_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = '../../uploads/products/' . $row['image_path'];

        // Delete the image file from the server
        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                redirectWithMessage("../products.php", "Failed to delete image file", "error");
            }
        }

        // Delete from the database
        $delete_query = "DELETE FROM product_images WHERE image_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $stmt->close();

        redirectWithMessage("../products.php", "Image deleted successfully", "success");
    } else {
        redirectWithMessage("../products.php", "Image not found", "error");
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
