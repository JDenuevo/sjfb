<?php
include '../../conn.php'; // Database connection
ob_start(); // Start output buffering
session_start(); // Ensure session is started

// Function to redirect with a session message
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type // success or error
    ];
    header("Location: $location");
    exit();
}

if (isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_description = mysqli_real_escape_string($conn, $_POST['product_description']);
    $product_size = mysqli_real_escape_string($conn, $_POST['product_size']);
    $category_id = intval($_POST['product_category']); // Ensure it's an integer
    $price = floatval($_POST['product_price']); // Ensure it's a float
    $discount_price = floatval($_POST['discount_price']); 
    $stock = intval($_POST['product_stock']); 

    // Check for duplicate product name
    $check_sql = "SELECT COUNT(*) FROM products WHERE product_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $product_name);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        redirectWithMessage("../products.php", "Product name already exists", "error");
    }

    // Insert new product
    $sql = "INSERT INTO products (product_name, product_description, product_size, product_category, product_price, discount_price, product_stock) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiddi", $product_name, $product_description, $product_size, $category_id, $price, $discount_price, $stock);
    $stmt->execute();
    $product_id = $stmt->insert_id; // Get the newly inserted product's ID
    $stmt->close();

    // Insert multiple images after adding a product
    if ($product_id > 0 && !empty($_FILES['product_images']['name'][0])) {
        $upload_dir = "../uploads/products/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $is_first_image = true; // Mark the first image as primary

        foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . "_" . basename($_FILES['product_images']['name'][$key]);
            $file_path = $upload_dir . $file_name;
            $db_path = $file_name;

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_types) || !in_array(exif_imagetype($tmp_name), [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                continue;
            }

            // Validate file size (Max: 5MB)
            if ($_FILES['product_images']['size'][$key] > 5 * 1024 * 1024) continue;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $is_primary = $is_first_image ? 1 : 0; // First image is primary
                $is_first_image = false;

                $img_sql = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("isi", $product_id, $db_path, $is_primary);
                $img_stmt->execute();
                $img_stmt->close();
            }
        }
    } else {
        // Insert default image
        $default_image = "uploads/products/default.png";
        $img_sql = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("is", $product_id, $default_image);
        $img_stmt->execute();
        $img_stmt->close();
    }

    redirectWithMessage("../products.php", "Product added successfully", "success");
}

// Add category
elseif (isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'] ?? '';
    $category_description = $_POST['category_description'] ?? '';

    if (empty($category_name)) {
        redirectWithMessage("../category.php", "Category name is required", "error");
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
        redirectWithMessage("../category.php", "Category name already exists", "error");
    }

    $sql = "INSERT INTO product_categories (category_name, category_description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category_name, $category_description);
    $stmt->execute();
    $stmt->close();

    redirectWithMessage("../category.php", "Category added successfully", "success");
}

ob_end_flush(); // End output buffering
?>
