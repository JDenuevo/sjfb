<?php
session_start();
require '../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

if (isset($_POST['add_account'])) {
    // Get and validate form data
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal'] ?? '');

    // Validate required fields
    $required_fields = [
        'username' => $username,
        'role' => $role,
        'password' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            redirectWithMessage("../accounts.php", "Field '$field' is required.", "error");
        }
    }

    if ($password !== $confirm_password) {
        redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check for existing username/email
    $checkQuery = "SELECT account_id FROM accounts WHERE username = ? OR email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        redirectWithMessage("../accounts.php", "Username or Email already exists.", "error");
    }

    // Start transaction for data consistency
    $conn->begin_transaction();

    try {
        // Insert account
        $insertQuery = "INSERT INTO accounts (username, role, password_hash, first_name, last_name, email, phone_number, address, city, postal_code) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssssssss", $username, $role, $hashed_password, $first_name, $last_name, $email, $phone_number, $address, $city, $postal_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert account: " . $stmt->error);
        }

        // Get the new account ID
        $new_account_id = $conn->insert_id;
        
        if ($new_account_id === 0) {
            // If insert_id is 0, try to get the ID by querying
            $getIdQuery = "SELECT account_id FROM accounts WHERE username = ? AND email = ? ORDER BY account_id DESC LIMIT 1";
            $getIdStmt = $conn->prepare($getIdQuery);
            $getIdStmt->bind_param("ss", $username, $email);
            $getIdStmt->execute();
            $result = $getIdStmt->get_result();
            
            if ($result->num_rows > 0) {
                $new_account_id = $result->fetch_assoc()['account_id'];
            } else {
                throw new Exception("Could not retrieve new account ID");
            }
        }

        // Create rider record if role is rider
        if ($role === 'rider') {
            $riderQuery = "INSERT INTO riders (account_id, vehicle_type, license_number, is_available) 
                           VALUES (?, 'motorcycle', 'PENDING', 1)";
            $riderStmt = $conn->prepare($riderQuery);
            $riderStmt->bind_param("i", $new_account_id);
            
            if (!$riderStmt->execute()) {
                throw new Exception("Failed to create rider record: " . $riderStmt->error);
            }
        }

        // Commit transaction
        $conn->commit();
        
        redirectWithMessage("../accounts.php", "Account successfully created! ID: " . $new_account_id, "success");

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Account creation error: " . $e->getMessage());
        redirectWithMessage("../accounts.php", "Error: " . $e->getMessage(), "error");
    }
}

elseif (isset($_POST['add_product'])) {
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $product_description = htmlspecialchars(trim($_POST['product_description']));
    $product_category = intval($_POST['product_category']);

    if (empty($product_name) || empty($product_description) || empty($product_category)) {
        redirectWithMessage("../products.php", "All fields are required.", "danger");
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Check for duplicate product name (including soft-deleted products)
        $check_product_query = "SELECT COUNT(*) FROM products WHERE product_name = ? AND is_deleted = 0";
        $stmt = $conn->prepare($check_product_query);
        $stmt->bind_param("s", $product_name);
        $stmt->execute();
        $stmt->bind_result($product_count);
        $stmt->fetch();
        $stmt->close();

        if ($product_count > 0) {
            $conn->rollback();
            redirectWithMessage("../products.php", "Error: Product name '$product_name' already exists.", "danger");
        }

        // Insert Product
        $insert_product_query = "INSERT INTO products (product_name, product_description, product_category) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_product_query);
        $stmt->bind_param("ssi", $product_name, $product_description, $product_category);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert Variants with duplicate checking
        if (!empty($_POST['variant_name'])) {
            $variant_names = $_POST['variant_name'];
            $unit_types = $_POST['unit_type'];
            $minimum_orders = $_POST['minimum_order'];
            $order_increments = $_POST['order_increment'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices = $_POST['variant_price'];
            $discount_prices = $_POST['discount_price'] ?? [];

            $existing_variants = []; // Track variants to prevent duplicates
            
            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_name = htmlspecialchars(trim($variant_names[$i]));
                $unit_type = $unit_types[$i];
                $minimum_order = floatval($minimum_orders[$i]);
                $order_increment = floatval($order_increments[$i]);
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price = floatval($variant_prices[$i]);
                $discount_price = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;
                
                // Create unique variant identifier
                $variant_key = $variant_name . '|' . $unit_type;
                
                // Check for duplicate variant in the same form submission
                if (in_array($variant_key, $existing_variants)) {
                    $conn->rollback();
                    redirectWithMessage("../products.php", "Error: Duplicate variant '$variant_name ($unit_type)' found in the same product.", "danger");
                }
                $existing_variants[] = $variant_key;

                // Check for existing variant with same name+unit in database for this product
                $check_variant_query = "SELECT COUNT(*) FROM product_variants 
                                      WHERE product_id = ? AND variant_name = ? AND unit_type = ?";
                $stmt = $conn->prepare($check_variant_query);
                $stmt->bind_param("iss", $product_id, $variant_name, $unit_type);
                $stmt->execute();
                $stmt->bind_result($variant_count);
                $stmt->fetch();
                $stmt->close();

                if ($variant_count > 0) {
                    $conn->rollback();
                    redirectWithMessage("../products.php", "Error: Variant '$variant_name ($unit_type)' already exists for this product.", "danger");
                }

                // Determine stock status
                $stock_status = $stock_quantity > 0 ? 'In Stock' : 'Out of Stock';

                // Insert variant
                $insert_variant_query = "INSERT INTO product_variants 
                    (product_id, variant_name, unit_type, minimum_order, order_increment, stock_quantity, variant_price, discount_price, stock_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_variant_query);
                $stmt->bind_param("issddidds", $product_id, $variant_name, $unit_type, $minimum_order, $order_increment, $stock_quantity, $variant_price, $discount_price, $stock_status);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Image Uploads
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../../uploads/products/";
            $firstImage = true;
            $uploaded_images = 0;
            $max_images = 5;

            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded_images >= $max_images) {
                    break; // Stop if maximum images reached
                }

                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['product_images']['name'][$key]);
                    $file_size = $_FILES['product_images']['size'][$key];
                    $file_type = mime_content_type($tmp_name);

                    // Validate image
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
                            
                            $uploaded_images++;
                        }
                    }
                }
            }
        }

        // Commit transaction
        $conn->commit();
        redirectWithMessage("../products.php", "Product '$product_name' added successfully!", "success");
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding product: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to add product: " . $e->getMessage(), "danger");
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

    $sql = "INSERT INTO product_categories (category_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $stmt->close();

    redirectWithMessage("../category.php", "Category added successfully.", "success");
} else {
    redirectWithMessage("../products.php", "Invalid request.", "danger");
}

$conn->close();
?>

