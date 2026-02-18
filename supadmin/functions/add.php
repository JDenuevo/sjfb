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
    $product_unit = htmlspecialchars(trim($_POST['product_unit'] ?? ''));
    $product_description = htmlspecialchars(trim($_POST['product_description'] ?? ''));
    
    // Handle product_nickname - can be comma-separated, space-separated, or JSON array
    $product_nickname = '';
    if (isset($_POST['product_nickname'])) {
        $nickname_input = trim($_POST['product_nickname']);
        
        // Check if it's a JSON array
        if (strpos($nickname_input, '[') === 0) {
            $nicknames = json_decode($nickname_input, true);
            if (is_array($nicknames)) {
                // Store as JSON for structured data
                $product_nickname = json_encode($nicknames, JSON_UNESCAPED_UNICODE);
            }
        } 
        // Check if it's comma-separated
        elseif (strpos($nickname_input, ',') !== false) {
            $nicknames = array_map('trim', explode(',', $nickname_input));
            $product_nickname = json_encode($nicknames, JSON_UNESCAPED_UNICODE);
        }
        // Check if it's space-separated (multiple words)
        elseif (str_word_count($nickname_input) > 1) {
            $nicknames = explode(' ', $nickname_input);
            $product_nickname = json_encode($nicknames, JSON_UNESCAPED_UNICODE);
        }
        // Single nickname
        else {
            $product_nickname = json_encode([$nickname_input], JSON_UNESCAPED_UNICODE);
        }
    }
    
    // Get selected categories
    $selected_categories = isset($_POST['product_categories']) ? $_POST['product_categories'] : [];
    $primary_category = isset($_POST['primary_category']) ? intval($_POST['primary_category']) : 0;

    if (empty($product_name) || empty($selected_categories)) {
        redirectWithMessage("../products.php", "Product name and at least one category are required.", "danger");
    }

    $conn->begin_transaction();
    
    try {
        // Check for duplicate product name
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

        // Insert Product with product_nickname
        $insert_product_query = "INSERT INTO products (product_name, product_unit, product_description, product_nickname) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_product_query);
        $stmt->bind_param("ssss", $product_name, $product_unit, $product_description, $product_nickname);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Link categories to product
        foreach ($selected_categories as $category_id) {
            $is_primary = ($category_id == $primary_category) ? 1 : 0;
            $link_query = "INSERT INTO product_category_links (product_id, category_id, is_primary) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($link_query);
            $stmt->bind_param("iii", $product_id, $category_id, $is_primary);
            $stmt->execute();
            $stmt->close();
        }

        // If no primary category set, set the first one as primary
        if ($primary_category == 0 && !empty($selected_categories)) {
            $first_category = $selected_categories[0];
            $update_primary = "UPDATE product_category_links SET is_primary = 1 WHERE product_id = ? AND category_id = ?";
            $stmt = $conn->prepare($update_primary);
            $stmt->bind_param("ii", $product_id, $first_category);
            $stmt->execute();
            $stmt->close();
        }

        // Insert Variants with duplicate checking and variant categories
        if (!empty($_POST['variant_name'])) {
            $variant_names = $_POST['variant_name'];
            $unit_types = $_POST['unit_type'];
            $minimum_orders = $_POST['minimum_order'];
            $order_increments = $_POST['order_increment'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices = $_POST['variant_price'];
            $discount_prices = $_POST['discount_price'] ?? [];
            
            // Get variant categories if any
            $variant_categories = isset($_POST['variant_categories']) ? $_POST['variant_categories'] : [];

            // Get all active category IDs first
            $active_categories = [];
            $active_query = "SELECT category_id FROM product_categories WHERE is_active = 1";
            $active_result = $conn->query($active_query);
            while ($active_row = $active_result->fetch_assoc()) {
                $active_categories[] = $active_row['category_id'];
            }

            $existing_variants = [];
            
            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_name = htmlspecialchars(trim($variant_names[$i]));
                $unit_type = $unit_types[$i];
                $minimum_order = floatval($minimum_orders[$i]);
                $order_increment = floatval($order_increments[$i]);
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price = floatval($variant_prices[$i]);
                $discount_price = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;
                
                $variant_key = $variant_name . '|' . $unit_type;
                
                if (in_array($variant_key, $existing_variants)) {
                    $conn->rollback();
                    redirectWithMessage("../products.php", "Error: Duplicate variant '$variant_name ($unit_type)' found in the same product.", "danger");
                }
                $existing_variants[] = $variant_key;

                // Check for existing variant
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

                $stock_status = $stock_quantity > 0 ? 'In Stock' : 'Out of Stock';

                // Insert variant
                $insert_variant_query = "INSERT INTO product_variants 
                    (product_id, variant_name, unit_type, minimum_order, order_increment, stock_quantity, variant_price, discount_price, stock_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_variant_query);
                $stmt->bind_param("issddidds", $product_id, $variant_name, $unit_type, $minimum_order, $order_increment, $stock_quantity, $variant_price, $discount_price, $stock_status);
                $stmt->execute();
                $variant_id = $stmt->insert_id;
                $stmt->close();
                
                // Link variant to categories - ONLY ACTIVE CATEGORIES
                $categories_to_link = [];
                
                if (!empty($variant_categories) && isset($variant_categories[$i]) && !empty($variant_categories[$i])) {
                    // Use variant-specific categories
                    $var_cats = is_array($variant_categories[$i]) ? $variant_categories[$i] : [$variant_categories[$i]];
                    // Filter to only active categories
                    $categories_to_link = array_intersect($var_cats, $active_categories);
                } 
                
                // If no variant-specific categories or all were invalid, inherit from product
                if (empty($categories_to_link)) {
                    $categories_to_link = array_intersect($selected_categories, $active_categories);
                }
                
                // Insert the valid categories
                foreach ($categories_to_link as $cat_id) {
                    if (!empty($cat_id)) {
                        $insert_variant_cat = "INSERT INTO product_variants_categories (variant_id, category_id) VALUES (?, ?)";
                        $vcat_stmt = $conn->prepare($insert_variant_cat);
                        $vcat_stmt->bind_param("ii", $variant_id, $cat_id);
                        $vcat_stmt->execute();
                        $vcat_stmt->close();
                    }
                }
            }
        }

        // Image Uploads
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../../uploads/products/";
            $firstImage = true;
            $uploaded_images = 0;
            $max_images = 5;

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded_images >= $max_images) break;

                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['product_images']['name'][$key]);
                    $file_size = $_FILES['product_images']['size'][$key];
                    $file_type = mime_content_type($tmp_name);

                    if (strpos($file_type, 'image') === 0 && $file_size <= 5 * 1024 * 1024) {
                        $unique_file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file_name);
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

        $conn->commit();
        redirectWithMessage("../products.php", "Product '$product_name' added successfully!", "success");
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding product: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to add product: " . $e->getMessage(), "danger");
    }
}

elseif (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    $category_slug = isset($_POST['category_slug']) ? trim($_POST['category_slug']) : '';
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
    
    if (empty($category_name)) {
        redirectWithMessage("../category.php", "Category name is required.", "error");
    }
    
    // Auto-generate slug if not provided
    if (empty($category_slug)) {
        $category_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category_name)));
    }

    // Check for duplicate category name ONLY among ACTIVE categories
    // This allows reusing names from soft-deleted categories
    $check_sql = "SELECT COUNT(*) FROM product_categories WHERE category_name = ? AND is_active = 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        redirectWithMessage("../category.php", "An active category with this name already exists.", "error");
    }
    
    // Check for duplicate slug if provided
    if (!empty($category_slug)) {
        $check_slug_sql = "SELECT COUNT(*) FROM product_categories WHERE category_slug = ? AND is_active = 1";
        $check_slug_stmt = $conn->prepare($check_slug_sql);
        $check_slug_stmt->bind_param("s", $category_slug);
        $check_slug_stmt->execute();
        $check_slug_stmt->bind_result($slug_count);
        $check_slug_stmt->fetch();
        $check_slug_stmt->close();
        
        if ($slug_count > 0) {
            redirectWithMessage("../category.php", "A category with this slug already exists.", "error");
        }
    }
    
    // Calculate category level based on parent
    $category_level = 1;
    if ($parent_id) {
        $level_query = "SELECT category_level FROM product_categories WHERE category_id = ?";
        $level_stmt = $conn->prepare($level_query);
        $level_stmt->bind_param("i", $parent_id);
        $level_stmt->execute();
        $level_result = $level_stmt->get_result();
        if ($parent = $level_result->fetch_assoc()) {
            $category_level = $parent['category_level'] + 1;
        }
        $level_stmt->close();
    }

    // Handle image upload
    $category_image = null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/categories/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['category_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $unique_file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file_name);
            $target_file = $target_dir . $unique_file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_file)) {
                $category_image = $unique_file_name;
            }
        }
    }

    // Insert new category with all fields
    $sql = "INSERT INTO product_categories (category_name, category_slug, category_description, category_image, parent_id, category_level, sort_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiii", 
        $category_name, 
        $category_slug, 
        $category_description, 
        $category_image, 
        $parent_id, 
        $category_level, 
        $sort_order
    );
    
    if ($stmt->execute()) {
        redirectWithMessage("../category.php", "Category added successfully.", "success");
    } else {
        redirectWithMessage("../category.php", "Failed to add category: " . $stmt->error, "error");
    }
    
    $stmt->close();
}
?>

