<?php
include '../../conn.php'; // Database connection

// DELETE PRODUCT
if (isset($_POST['delete_product'], $_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // Delete product images first (to remove files from the server)
    $query = "SELECT image_path FROM product_images WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $image_path = '../uploads/products/' . $row['image_path']; // Ensure correct path
        if (file_exists($image_path)) {
            unlink($image_path); // Delete image file
        }
    }

    // Delete images from the database
    $delete_images = "DELETE FROM product_images WHERE product_id = ?";
    $stmt = $conn->prepare($delete_images);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // Delete the product itself
    $delete_product = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($delete_product);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $stmt->close();
    header("Location: ../products.php?success=Product deleted successfully");
    exit();
}

// DELETE CATEGORY
elseif (isset($_POST['delete_category'], $_POST['category_id'])) {
    $category_id = $_POST['category_id'];

    // Check if category exists before deletion
    $query = "SELECT * FROM product_categories WHERE category_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $delete_category = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($delete_category);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../categories.php?success=Category deleted successfully");
        exit();
    } else {
        header("Location: ../categories.php?error=Category not found");
        exit();
    }
}

// DELETE IMAGE
if (isset($_POST['delete_image'], $_POST['image_id'], $_POST['product_id'])) {
    $image_id = $_POST['image_id'];
    $product_id = $_POST['product_id'];

    // Fetch image path
    $query = "SELECT image_path FROM product_images WHERE image_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $image_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = '../uploads/products/' . $row['image_path']; // Ensure correct path

        // Delete the image file
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete from the database
        $delete_query = "DELETE FROM product_images WHERE image_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../products.php?success=Image deleted successfully");
        exit();
    } else {
        header("Location: ../products.php?error=Image not found");
        exit();
    }
}

?>
