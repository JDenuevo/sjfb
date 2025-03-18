<?php
session_start();
include '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['update_product'])) {
    // Sanitize inputs
    $product_id = intval($_POST['product_id']);
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $product_description = htmlspecialchars(trim($_POST['product_description']));
    $product_category = intval($_POST['product_category']);

    // Validate required fields
    if (empty($product_name) || empty($product_description) || empty($product_category)) {
        redirectWithMessage("../products.php", "All fields are required.", "danger");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update product details
        $update_product_query = "UPDATE products SET product_name = ?, product_description = ?, product_category = ? WHERE product_id = ?";
        $stmt = $conn->prepare($update_product_query);
        $stmt->bind_param("ssii", $product_name, $product_description, $product_category, $product_id);
        $stmt->execute();
        $stmt->close();

        // Handle variants
        if (isset($_POST['variant_name']) && is_array($_POST['variant_name'])) {
            $variant_ids = $_POST['variant_id'];
            $variant_names = $_POST['variant_name'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices = $_POST['variant_price'];
            $discount_prices = isset($_POST['discount_price']) ? $_POST['discount_price'] : [];

            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_id = !empty($variant_ids[$i]) ? intval($variant_ids[$i]) : null;
                $variant_name = htmlspecialchars(trim($variant_names[$i]));
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price = floatval($variant_prices[$i]);
                $discount_price = (!empty($discount_prices[$i]) && $discount_prices[$i] != "0") ? floatval($discount_prices[$i]) : null;

                if ($variant_id) {
                    // Update existing variant
                    $update_variant_query = "UPDATE product_variants SET variant_name = ?, stock_quantity = ?, variant_price = ?, discount_price = ? WHERE variant_id = ?";
                    $stmt = $conn->prepare($update_variant_query);
                    $stmt->bind_param("siddi", $variant_name, $stock_quantity, $variant_price, $discount_price, $variant_id);
                } else {
                    // Insert new variant
                    $insert_variant_query = "INSERT INTO product_variants (product_id, variant_name, stock_quantity, variant_price, discount_price) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_variant_query);
                    $stmt->bind_param("isidd", $product_id, $variant_name, $stock_quantity, $variant_price, $discount_price);
                }
                $stmt->execute();
                $stmt->close();
            }
        }

        // Handle image uploads
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../uploads/products/";
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['product_images']['name'][$key]);
                $file_size = $_FILES['product_images']['size'][$key];
                $file_type = mime_content_type($tmp_name);

                // Validate file type and size
                if (strpos($file_type, 'image') === 0 && $file_size <= 5 * 1024 * 1024) { // 5MB limit
                    $unique_file_name = uniqid() . '_' . $file_name;
                    $target_file = $target_dir . $unique_file_name;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        // Insert new image into the database
                        $insert_image_query = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                        $stmt = $conn->prepare($insert_image_query);
                        $stmt->bind_param("is", $product_id, $unique_file_name);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Commit transaction
        $conn->commit();
        redirectWithMessage("../products.php", "Product updated successfully!", "success");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating product: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to update product.", "danger");
    }
}

elseif (isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    if (empty($category_name)) {
        redirectWithMessage("../category.php", "Category name is required", "error");
    }

    // Check for duplicate category name (excluding the current category)
    $check_sql = "SELECT COUNT(*) FROM product_categories WHERE category_name = ? AND category_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $category_name, $category_id);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        redirectWithMessage("../category.php", "Category name already exists", "error");
    }

    // Update category
    $sql = "UPDATE product_categories SET category_name = ?, category_description = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category_name, $category_description, $category_id);

    if ($stmt->execute()) {
        redirectWithMessage("../category.php", "Category updated successfully", "success");
    } else {
        redirectWithMessage("../category.php", "Failed to update category", "error");
    }
}
?>
