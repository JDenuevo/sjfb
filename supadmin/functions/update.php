<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['update_account'])) {
    $account_id = $_POST['account_id'];
    $username = trim(htmlspecialchars($_POST['username']));
    $role = htmlspecialchars($_POST['role']);
    $first_name = trim(htmlspecialchars($_POST['first_name']));
    $last_name = trim(htmlspecialchars($_POST['last_name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $phone_number = trim(htmlspecialchars($_POST['phone_number']));
    $address = trim(htmlspecialchars($_POST['address']));
    $city = trim(htmlspecialchars($_POST['city']));
    $postal_code = trim(htmlspecialchars($_POST['postal_code']));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage("../accounts.php", "Invalid email format.", "error");
    }

    // Handle password update only if a new password is provided
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
        }

        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ✅ Corrected column name: password_hash instead of password
        $updateQuery = "UPDATE accounts SET username = ?, role = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ?, password_hash = ? WHERE account_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssssssssi", $username, $role, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $hashed_password, $account_id);
    } else {
        // Update query without password
        $updateQuery = "UPDATE accounts SET username = ?, role = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ? WHERE account_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssssi", $username, $role, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $account_id);
    }

    // Execute the update query
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        redirectWithMessage("../accounts.php", "Account successfully updated!", "success");
    } else {
        $stmt->close();
        $conn->close();
        redirectWithMessage("../accounts.php", "Failed to update account.", "error");
    }
}


elseif (isset($_POST['update_profile'])) {
    $account_id = $_SESSION['account_id'];
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ Check if email already exists in another account
    $checkEmail = $conn->prepare("SELECT account_id FROM accounts WHERE email = ? AND account_id != ?");
    $checkEmail->bind_param("si", $email, $account_id);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        $conn->close();
        redirectWithMessage('../profile.php', 'Email is already taken by another account.', 'error');
    }
    $checkEmail->close();

    // ✅ Check if username already exists in another account
    $checkUsername = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? AND account_id != ?");
    $checkUsername->bind_param("si", $username, $account_id);
    $checkUsername->execute();
    $checkUsername->store_result();

    if ($checkUsername->num_rows > 0) {
        $checkUsername->close();
        $conn->close();
        redirectWithMessage('../profile.php', 'Username is already taken by another account.', 'error');
    }
    $checkUsername->close();

    // ✅ Handle password update only if provided
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            redirectWithMessage('../profile.php', 'Password and Confirm Password do not match.', 'error');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE accounts 
                SET username = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ?, password_hash = ? 
                WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $username, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $hashedPassword, $account_id);
    } else {
        $sql = "UPDATE accounts 
                SET username = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ? 
                WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $username, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code, $account_id);
    }

    if ($stmt->execute()) {
        redirectWithMessage('../profile.php', 'Account updated successfully.', 'success');
    } else {
        redirectWithMessage('../profile.php', 'Failed to update account.', 'error');
    }

    $stmt->close();
    $conn->close();
}

elseif (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $product_description = htmlspecialchars(trim($_POST['product_description']));
    $product_category = intval($_POST['product_category']);

    if (empty($product_name) || empty($product_description) || empty($product_category)) {
        redirectWithMessage("../products.php", "All fields are required.", "error");
    }

    $conn->begin_transaction();

    try {
        // Update product
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_description = ?, product_category = ? WHERE product_id = ?");
        $stmt->bind_param("ssii", $product_name, $product_description, $product_category, $product_id);
        $stmt->execute();
        $stmt->close();

        // Handle deleted variants
        if (!empty($_POST['deleted_variants'])) {
            $deletedVariants = explode(',', $_POST['deleted_variants']);
            foreach ($deletedVariants as $variantId) {
                $variantId = intval($variantId);
                if ($variantId > 0) {
                    $stmt = $conn->prepare("DELETE FROM product_variants WHERE variant_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $variantId, $product_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Handle variants (update existing and insert new)
        if (isset($_POST['variant_name']) && is_array($_POST['variant_name'])) {
            $variant_ids = $_POST['variant_id'];
            $variant_names = $_POST['variant_name'];
            $unit_types = $_POST['unit_type'];
            $minimum_orders = $_POST['minimum_order'];
            $order_increments = $_POST['order_increment'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices = $_POST['variant_price'];
            $discount_prices = $_POST['discount_price'] ?? [];

            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_id = !empty($variant_ids[$i]) ? intval($variant_ids[$i]) : null;
                $variant_name = htmlspecialchars(trim($variant_names[$i]));
                $unit_type = $unit_types[$i];
                $minimum_order = floatval($minimum_orders[$i]);
                $order_increment = floatval($order_increments[$i]);
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price = floatval($variant_prices[$i]);
                $discount_price = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;
                $stock_status = $stock_quantity > 0 ? 'In Stock' : 'Out of Stock';

                if ($variant_id) {
                    // Update existing
                    $stmt = $conn->prepare("UPDATE product_variants SET variant_name = ?, unit_type = ?, minimum_order = ?, order_increment = ?, stock_quantity = ?, variant_price = ?, discount_price = ?, stock_status = ? WHERE variant_id = ?");
                    $stmt->bind_param("ssddiddsi", $variant_name, $unit_type, $minimum_order, $order_increment, $stock_quantity, $variant_price, $discount_price, $stock_status, $variant_id);
                } else {
                    // Insert new
                    $stmt = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, unit_type, minimum_order, order_increment, stock_quantity, variant_price, discount_price, stock_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issddidds", $product_id, $variant_name, $unit_type, $minimum_order, $order_increment, $stock_quantity, $variant_price, $discount_price, $stock_status);
                }
                $stmt->execute();
                $stmt->close();
            }
        }

        // Handle deleted images - FIXED PRIMARY IMAGE LOGIC
        if (!empty($_POST['deleted_images'])) {
            $deletedImages = explode(',', $_POST['deleted_images']);
            
            // Check if any of the deleted images is primary
            $checkPrimaryQuery = "SELECT image_id FROM product_images WHERE image_id IN (" . implode(',', array_fill(0, count($deletedImages), '?')) . ") AND is_primary = 1";
            $checkPrimaryStmt = $conn->prepare($checkPrimaryQuery);
            
            // Bind parameters
            $types = str_repeat('i', count($deletedImages));
            $checkPrimaryStmt->bind_param($types, ...$deletedImages);
            $checkPrimaryStmt->execute();
            $primaryResult = $checkPrimaryStmt->get_result();
            $wasPrimaryDeleted = $primaryResult->num_rows > 0;
            $checkPrimaryStmt->close();

            // Delete the images
            foreach ($deletedImages as $imageId) {
                $imageId = intval($imageId);
                if ($imageId > 0) {
                    // Get image path
                    $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE image_id = ?");
                    $stmt->bind_param("i", $imageId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $imagePath = "../../uploads/products/" . $row['image_path'];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    $stmt->close();
                    
                    // Delete from database
                    $stmt = $conn->prepare("DELETE FROM product_images WHERE image_id = ?");
                    $stmt->bind_param("i", $imageId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // If primary image was deleted, set a new primary image
            if ($wasPrimaryDeleted) {
                // Find the first remaining image for this product
                $findNewPrimaryQuery = "SELECT image_id FROM product_images WHERE product_id = ? ORDER BY image_id ASC LIMIT 1";
                $findNewPrimaryStmt = $conn->prepare($findNewPrimaryQuery);
                $findNewPrimaryStmt->bind_param("i", $product_id);
                $findNewPrimaryStmt->execute();
                $newPrimaryResult = $findNewPrimaryStmt->get_result();
                
                if ($newPrimaryResult->num_rows > 0) {
                    $newPrimaryId = $newPrimaryResult->fetch_assoc()['image_id'];
                    
                    // Set the new primary image
                    $updatePrimaryQuery = "UPDATE product_images SET is_primary = 1 WHERE image_id = ?";
                    $updatePrimaryStmt = $conn->prepare($updatePrimaryQuery);
                    $updatePrimaryStmt->bind_param("i", $newPrimaryId);
                    $updatePrimaryStmt->execute();
                    $updatePrimaryStmt->close();
                }
                $findNewPrimaryStmt->close();
            }
        }

        // Handle new images
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../../uploads/products/";
            
            // Check how many images exist after deletions
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $existingCount = $stmt->get_result()->fetch_assoc()['count'];
            $stmt->close();
            
            // If no images exist after deletions, the first new image becomes primary
            $isPrimary = ($existingCount == 0) ? 1 : 0;
            
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['product_images']['name'][$key]);
                    $file_size = $_FILES['product_images']['size'][$key];
                    $file_type = mime_content_type($tmp_name);

                    if (strpos($file_type, 'image') === 0 && $file_size <= 5 * 1024 * 1024) {
                        $unique_file_name = uniqid() . '_' . $file_name;
                        $target_file = $target_dir . $unique_file_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $stmt->bind_param("isi", $product_id, $unique_file_name, $isPrimary);
                            $stmt->execute();
                            $stmt->close();
                            
                            // Only the first image should be primary
                            $isPrimary = 0;
                        }
                    }
                }
            }
        }

        $conn->commit();
        redirectWithMessage("../products.php", "Product updated successfully!", "success");

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update error: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to update product: " . $e->getMessage(), "error");
    }
}

elseif (isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);

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
    $sql = "UPDATE product_categories SET category_name = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $category_name, $category_id);

    if ($stmt->execute()) {
        redirectWithMessage("../category.php", "Category updated successfully", "success");
    } else {
        redirectWithMessage("../category.php", "Failed to update category", "error");
    }
}

?>
