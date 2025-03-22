<?php
include '../../conn.php';
session_start();

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

// ✅ Add Product
if (isset($_POST['add_product'])) {
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $product_description = htmlspecialchars(trim($_POST['product_description']));
    $product_category = intval($_POST['product_category']);

    if (empty($product_name) || empty($product_description) || empty($product_category)) {
        redirectWithMessage("../products.php", "All fields are required.", "danger");
    }

    // Check for duplicate product name
    $check_product_query = "SELECT COUNT(*) FROM products WHERE product_name = ?";
    $stmt = $conn->prepare($check_product_query);
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $stmt->bind_result($product_count);
    $stmt->fetch();
    $stmt->close();

    if ($product_count > 0) {
        redirectWithMessage("../products.php", "Error: Product name already exists.", "danger");
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Insert Product
        $insert_product_query = "INSERT INTO products (product_name, product_description, product_category) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_product_query);
        $stmt->bind_param("ssi", $product_name, $product_description, $product_category);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert Variants
        if (!empty($_POST['variant_name'])) {
            $variant_names = $_POST['variant_name'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices = $_POST['variant_price'];
            $discount_prices = $_POST['discount_price'] ?? [];

            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_name = htmlspecialchars(trim($variant_names[$i]));
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price = floatval($variant_prices[$i]);
                $discount_price = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;

                $insert_variant_query = "INSERT INTO product_variants (product_id, variant_name, stock_quantity, variant_price, discount_price) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_variant_query);
                $stmt->bind_param("isidd", $product_id, $variant_name, $stock_quantity, $variant_price, $discount_price);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Image Uploads with Primary Flag
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../uploads/products/";
            $firstImage = true;

            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['product_images']['name'][$key]);
                $file_size = $_FILES['product_images']['size'][$key];
                $file_type = mime_content_type($tmp_name);

                if (strpos($file_type, 'image') === 0 && $file_size <= 5 * 1024 * 1024) {
                    $unique_file_name = uniqid() . '_' . $file_name;
                    $target_file = $target_dir . $unique_file_name;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $is_primary = $firstImage ? 1 : 0;
                        $firstImage = false;

                        $insert_image_query = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_image_query);
                        $stmt->bind_param("isi", $product_id, $unique_file_name, $is_primary);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Commit transaction
        $conn->commit();
        redirectWithMessage("../products.php", "Product added successfully!", "success");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding product: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to add product.", "danger");
    }
}

// ✅ Add Category
elseif (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    if (empty($category_name)) {
        redirectWithMessage("../category.php", "Category name is required.", "error");
    }

    // Check for duplicate category
    $check_sql = "SELECT COUNT(*) FROM product_categories WHERE category_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        redirectWithMessage("../category.php", "Category name already exists.", "error");
    }

    $sql = "INSERT INTO product_categories (category_name, category_description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category_name, $category_description);
    $stmt->execute();
    $stmt->close();

    redirectWithMessage("../category.php", "Category added successfully.", "success");
} else {
    redirectWithMessage("../products.php", "Invalid request.", "danger");
}

$conn->close();
?>
