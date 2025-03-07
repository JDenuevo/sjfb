<?php
session_start();
include '../../conn.php';

if (isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_description = $_POST['product_description'];
    $product_size = $_POST['product_size'];
    $category_id = (int)$_POST['product_category'];
    $price = floatval($_POST['product_price']);
    $discount_price = floatval($_POST['discount_price']);
    $stock = (int)$_POST['product_stock'];

    $sql = "UPDATE products SET product_name = ?, product_description = ?, product_size = ?, product_category = ?, product_price = ?, discount_price = ?, product_stock = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssiddii", $product_name, $product_description, $product_size, $category_id, $price, $discount_price, $stock, $product_id);
        if ($stmt->execute()) {
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message_type'] = "fail";
        }
        $stmt->close();
    } else {
        $_SESSION['message_type'] = "fail";
    }

    if (!empty($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_id) {
            $get_image = "SELECT image_path FROM product_images WHERE image_id = ?";
            $img_stmt = $conn->prepare($get_image);
            $img_stmt->bind_param("i", $image_id);
            $img_stmt->execute();
            $result = $img_stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $image_path = "../../" . $row['image_path'];

                if (file_exists($image_path)) {
                    unlink($image_path);
                }

                $delete_image = "DELETE FROM product_images WHERE image_id = ?";
                $del_stmt = $conn->prepare($delete_image);
                $del_stmt->bind_param("i", $image_id);
                $del_stmt->execute();
                $del_stmt->close();
            }
            $img_stmt->close();
        }
    }

    if (!empty($_FILES['product_images']['name'][0])) {
        $upload_dir = "../uploads/products/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . "_" . basename($_FILES['product_images']['name'][$key]);
            $file_path = $upload_dir . $file_name;
            $db_path = "uploads/products/" . $file_name;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $insert_image = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                $img_stmt = $conn->prepare($insert_image);
                $img_stmt->bind_param("is", $product_id, $db_path);
                $img_stmt->execute();
                $img_stmt->close();
            }
        }
    }

    header("Location: ../products.php");
    exit();
}

if (isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    if (empty($category_name)) {
        $_SESSION['message_type'] = "fail";
        header("Location: ../categories.php");
        exit();
    }

    $check_sql = "SELECT COUNT(*) FROM product_categories WHERE category_name = ? AND category_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $category_name, $category_id);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        $_SESSION['message_type'] = "fail";
        header("Location: ../categories.php");
        exit();
    }

    $sql = "UPDATE product_categories SET category_name = ?, category_description = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category_name, $category_description, $category_id);

    if ($stmt->execute()) {
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message_type'] = "fail";
    }

    header("Location: ../categories.php");
    exit();
}
?>
