<?php
// ==================== supadmin/functions/add.php ====================
session_start();
require '../../conn.php';
include 'slug_helper.php';
require_once 'activity_log_helper.php';
require_once 'review_helper.php';
 
function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}
 
function uploadImage(array $fileArray, int $index, string $dir, string $suffix = ''): ?string {
    if (($fileArray['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $tmp      = $fileArray['tmp_name'][$index];
    $origName = $fileArray['name'][$index];
    $size     = $fileArray['size'][$index];
    $mime     = mime_content_type($tmp);
    if (strpos($mime, 'image/') !== 0)   return null;
    if ($size > 5 * 1024 * 1024)         return null;
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $filename = uniqid() . ($suffix ? "_{$suffix}" : '') . '.' . $ext;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return move_uploaded_file($tmp, $dir . $filename) ? $filename : null;
}
 
function uploadSingleImage(array $file, string $dir, string $suffix = ''): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $tmp  = $file['tmp_name'];
    $mime = mime_content_type($tmp);
    if (strpos($mime, 'image/') !== 0)    return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . ($suffix ? "_{$suffix}" : '') . '.' . $ext;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return move_uploaded_file($file['tmp_name'], $dir . $filename) ? $filename : null;
}
 
['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();
 
// ── ADD ACCOUNT ───────────────────────────────────────────────────────────────
// All field names match the form in accounts.php exactly.
if (isset($_POST['add_account'])) {
 
    $username         = trim($_POST['username']         ?? '');
    $role             = trim($_POST['role']             ?? '');
    $password         = trim($_POST['password']         ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
 
    // These match the form input names in accounts.php
    $first_name  = trim($_POST['account_first_name'] ?? '');
    $last_name   = trim($_POST['account_last_name']  ?? '');
    $email       = trim($_POST['account_email']      ?? '');
    $phone       = trim($_POST['account_phone']      ?? '');
    $address     = trim($_POST['account_address']    ?? '');
    $city        = trim($_POST['city']               ?? '');
    $postal_code = trim($_POST['postal_code']        ?? '');
 
    // Required fields
    if (empty($username))   redirectWithMessage("../accounts.php", "Username is required.", "error");
    if (empty($role))       redirectWithMessage("../accounts.php", "Role is required.", "error");
    if (empty($password))   redirectWithMessage("../accounts.php", "Password is required.", "error");
    if (empty($first_name)) redirectWithMessage("../accounts.php", "First name is required.", "error");
    if (empty($last_name))  redirectWithMessage("../accounts.php", "Last name is required.", "error");
    if (empty($email))      redirectWithMessage("../accounts.php", "Email is required.", "error");
 
    if ($password !== $confirm_password)
        redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        redirectWithMessage("../accounts.php", "Invalid email format.", "error");
 
    $hashed = password_hash($password, PASSWORD_DEFAULT);
 
    // Check for duplicates — uses renamed columns: username, account_email
    $ck = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? OR account_email = ? LIMIT 1");
    $ck->bind_param("ss", $username, $email);
    $ck->execute();
    if ($ck->get_result()->num_rows > 0)
        redirectWithMessage("../accounts.php", "Username or email already exists.", "error");
    $ck->close();
 
    $conn->begin_transaction();
    try {
        // INSERT uses all renamed columns
        $stmt = $conn->prepare("
            INSERT INTO accounts
                (username, role, password_hash,
                 account_first_name, account_last_name, account_email,
                 account_phone, account_address, city, postal_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssssss",
            $username, $role, $hashed,
            $first_name, $last_name, $email,
            $phone, $address, $city, $postal_code
        );
        if (!$stmt->execute()) throw new Exception("Failed to create account: " . $stmt->error);
        $new_id = $conn->insert_id;
        $stmt->close();
 
        // If role is rider, create a riders record too
        if ($role === 'rider') {
            $rStmt = $conn->prepare("INSERT INTO riders (account_id, vehicle_type, is_available) VALUES (?, 'motorcycle', 1)");
            $rStmt->bind_param("i", $new_id);
            if (!$rStmt->execute()) throw new Exception("Failed to create rider record: " . $rStmt->error);
            $rStmt->close();
        }
 
        logActivity($conn, 'account', $new_id, 'Account created',
            null,
            json_encode(['username' => $username, 'role' => $role, 'email' => $email]),
            "Admin created account. Name: {$first_name} {$last_name} | Role: {$role}",
            $actorId, $actorType
        );
 
        $conn->commit();
        redirectWithMessage("../accounts.php", "Account '{$username}' created successfully! (ID: {$new_id})", "success");
 
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Account creation error: " . $e->getMessage());
        redirectWithMessage("../accounts.php", "Error: " . $e->getMessage(), "error");
    }
}

elseif (isset($_POST['manage_account_groups'])) {
    header('Content-Type: application/json; charset=utf-8');
 
    if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
 
    $account_id = (int)($_POST['account_id'] ?? 0);
    $group_ids  = array_map('intval', (array)($_POST['group_ids'] ?? []));
    $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;
 
    if ($account_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid account ID.']);
        exit;
    }
 
    // Validate expiry format if provided
    if ($expires_at && !strtotime($expires_at)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiry date format.']);
        exit;
    }
 
    $conn->begin_transaction();
    try {
        // Remove all existing active group assignments for this account
        $del = $conn->prepare("DELETE FROM account_groups WHERE account_id = ?");
        $del->bind_param('i', $account_id);
        if (!$del->execute()) throw new Exception("Failed to clear existing groups: " . $conn->error);
        $del->close();
 
        // Insert newly selected groups
        if (!empty($group_ids)) {
            $ins = $conn->prepare("
                INSERT INTO account_groups (account_id, group_id, assigned_by, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($group_ids as $gid) {
                if ($gid <= 0) continue;
                $ins->bind_param('iiis', $account_id, $gid, $actorId, $expires_at);
                if (!$ins->execute()) throw new Exception("Failed to assign group {$gid}: " . $conn->error);
            }
            $ins->close();
        }
 
        // Fetch group names for the log
        $groupNames = [];
        if (!empty($group_ids)) {
            $placeholders = implode(',', array_fill(0, count($group_ids), '?'));
            $types        = str_repeat('i', count($group_ids));
            $gnStmt = $conn->prepare("SELECT group_name FROM customer_groups WHERE group_id IN ({$placeholders})");
            $gnStmt->bind_param($types, ...$group_ids);
            $gnStmt->execute();
            $gnRes = $gnStmt->get_result();
            while ($gn = $gnRes->fetch_assoc()) $groupNames[] = $gn['group_name'];
            $gnStmt->close();
        }
 
        logActivity($conn, 'account', $account_id, 'Account groups updated',
            null,
            json_encode(['groups' => $groupNames, 'expires_at' => $expires_at]),
            "Account ID {$account_id} groups updated to: " . (empty($groupNames) ? 'none' : implode(', ', $groupNames)),
            $actorId, $actorType
        );
 
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Groups updated successfully.']);
 
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Group management error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── ADD PRODUCT ───────────────────────────────────────────────────────────────
elseif (isset($_POST['add_product'])) {
    $product_name        = htmlspecialchars(trim($_POST['product_name']));
    $product_unit        = htmlspecialchars(trim($_POST['product_unit'] ?? ''));
    $product_description = htmlspecialchars(trim($_POST['product_description'] ?? ''));

    $product_nickname = '';
    if (isset($_POST['product_nickname'])) {
        $ni = trim($_POST['product_nickname']);
        if (strpos($ni, '[') === 0) {
            $arr = json_decode($ni, true);
            $product_nickname = is_array($arr) ? json_encode($arr, JSON_UNESCAPED_UNICODE) : json_encode([$ni], JSON_UNESCAPED_UNICODE);
        } elseif (strpos($ni, ',') !== false) {
            $product_nickname = json_encode(array_map('trim', explode(',', $ni)), JSON_UNESCAPED_UNICODE);
        } elseif (str_word_count($ni) > 1) {
            $product_nickname = json_encode(explode(' ', $ni), JSON_UNESCAPED_UNICODE);
        } else {
            $product_nickname = json_encode([$ni], JSON_UNESCAPED_UNICODE);
        }
    }

    $selected_categories = $_POST['product_categories'] ?? [];
    $primary_category    = isset($_POST['primary_category']) ? intval($_POST['primary_category']) : 0;

    if (empty($product_name) || empty($selected_categories)) {
        redirectWithMessage("../products.php", "Product name and at least one category are required.", "danger");
    }

    $conn->begin_transaction();
    try {
        $ck = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_name = ? AND is_deleted = 0");
        $ck->bind_param("s", $product_name);
        $ck->execute();
        $ck->bind_result($cnt); $ck->fetch(); $ck->close();
        if ($cnt > 0) { $conn->rollback(); redirectWithMessage("../products.php", "Product '$product_name' already exists.", "danger"); }

        $stmt = $conn->prepare("INSERT INTO products (product_name, product_unit, product_description, product_nickname) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $product_name, $product_unit, $product_description, $product_nickname);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        foreach ($selected_categories as $category_id) {
            $is_primary = ($category_id == $primary_category) ? 1 : 0;
            $lk = $conn->prepare("INSERT INTO product_category_links (product_id, category_id, is_primary) VALUES (?, ?, ?)");
            $lk->bind_param("iii", $product_id, $category_id, $is_primary);
            $lk->execute(); $lk->close();
        }
        if ($primary_category == 0 && !empty($selected_categories)) {
            $fc = $selected_categories[0];
            $up = $conn->prepare("UPDATE product_category_links SET is_primary = 1 WHERE product_id = ? AND category_id = ?");
            $up->bind_param("ii", $product_id, $fc); $up->execute(); $up->close();
        }

        if (!empty($_POST['variant_name'])) {
            $variant_names    = $_POST['variant_name'];
            $unit_types       = $_POST['unit_type'];
            $minimum_orders   = $_POST['minimum_order'];
            $order_increments = $_POST['order_increment'];
            $stock_quantities = $_POST['stock_quantity'];
            $variant_prices   = $_POST['variant_price'];
            $discount_prices  = $_POST['discount_price'] ?? [];
            $variant_categories = $_POST['variant_categories'] ?? [];

            $active_categories = [];
            $ar = $conn->query("SELECT category_id FROM product_categories WHERE is_active = 1");
            while ($row = $ar->fetch_assoc()) $active_categories[] = $row['category_id'];

            $existing_variants = [];
            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_name    = htmlspecialchars(trim($variant_names[$i]));
                $unit_type       = $unit_types[$i];
                $minimum_order   = floatval($minimum_orders[$i]);
                $order_increment = floatval($order_increments[$i]);
                $stock_quantity  = intval($stock_quantities[$i]);
                $variant_price   = floatval($variant_prices[$i]);
                $discount_price  = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;
                $variant_key     = $variant_name . '|' . $unit_type;

                if (in_array($variant_key, $existing_variants)) {
                    $conn->rollback();
                    redirectWithMessage("../products.php", "Duplicate variant '$variant_name ($unit_type)' found.", "danger");
                }
                $existing_variants[] = $variant_key;

                $cv = $conn->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND variant_name = ? AND unit_type = ?");
                $cv->bind_param("iss", $product_id, $variant_name, $unit_type);
                $cv->execute(); $cv->bind_result($vc); $cv->fetch(); $cv->close();
                if ($vc > 0) { $conn->rollback(); redirectWithMessage("../products.php", "Variant '$variant_name ($unit_type)' already exists.", "danger"); }

                $stock_status = $stock_quantity > 0 ? 'In Stock' : 'Out of Stock';
                $iv = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, unit_type, minimum_order, order_increment, stock_quantity, variant_price, discount_price, stock_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $iv->bind_param("issddidds", $product_id, $variant_name, $unit_type, $minimum_order, $order_increment, $stock_quantity, $variant_price, $discount_price, $stock_status);
                $iv->execute();
                $variant_id = $iv->insert_id;
                $iv->close();

                $cats_to_link = [];
                if (!empty($variant_categories) && isset($variant_categories[$i]) && !empty($variant_categories[$i])) {
                    $vc_arr = is_array($variant_categories[$i]) ? $variant_categories[$i] : [$variant_categories[$i]];
                    $cats_to_link = array_intersect($vc_arr, $active_categories);
                }
                if (empty($cats_to_link)) $cats_to_link = array_intersect($selected_categories, $active_categories);
                foreach ($cats_to_link as $cat_id) {
                    if (!empty($cat_id)) {
                        $vc_ins = $conn->prepare("INSERT INTO product_variants_categories (variant_id, category_id) VALUES (?, ?)");
                        $vc_ins->bind_param("ii", $variant_id, $cat_id);
                        $vc_ins->execute(); $vc_ins->close();
                    }
                }
            }
        }

        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../../uploads/products/";
            $firstImage = true; $uploaded_images = 0; $max_images = 5;
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded_images >= $max_images) break;
                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['product_images']['name'][$key]);
                    $file_type = mime_content_type($tmp_name);
                    if (strpos($file_type, 'image') === 0 && $_FILES['product_images']['size'][$key] <= 5*1024*1024) {
                        $ufn = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file_name);
                        if (move_uploaded_file($tmp_name, $target_dir . $ufn)) {
                            $is_primary = $firstImage ? 1 : 0; $firstImage = false;
                            $im = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $im->bind_param("isi", $product_id, $ufn, $is_primary);
                            $im->execute(); $im->close();
                            $uploaded_images++;
                        }
                    }
                }
            }
        }

        logActivity($conn, 'product', $product_id, 'Product created',
            null,
            json_encode(['product_name'=>$product_name,'unit'=>$product_unit]),
            "Product '{$product_name}' created. Categories: " . implode(',', $selected_categories),
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../products.php", "Product '$product_name' added successfully!", "success");

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding product: " . $e->getMessage());
        redirectWithMessage("../products.php", "Failed to add product: " . $e->getMessage(), "danger");
    }
}

// ── ADD CATEGORY ──────────────────────────────────────────────────────────────
elseif (isset($_POST['add_category'])) {
    $category_name        = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    $category_slug        = trim($_POST['category_slug'] ?? '');
    $parent_id            = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $sort_order           = intval($_POST['sort_order'] ?? 0);

    if (empty($category_name)) redirectWithMessage("../category.php", "Category name is required.", "error");
    if (empty($category_slug)) {
        $category_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category_name)));
    }

    $ck = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE category_name = ? AND is_active = 1");
    $ck->bind_param("s", $category_name); $ck->execute(); $ck->bind_result($cnt); $ck->fetch(); $ck->close();
    if ($cnt > 0) redirectWithMessage("../category.php", "An active category with this name already exists.", "error");

    $cks = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE category_slug = ? AND is_active = 1");
    $cks->bind_param("s", $category_slug); $cks->execute(); $cks->bind_result($sc); $cks->fetch(); $cks->close();
    if ($sc > 0) redirectWithMessage("../category.php", "A category with this slug already exists.", "error");

    $category_level = 1;
    if ($parent_id) {
        $lv = $conn->prepare("SELECT category_level FROM product_categories WHERE category_id = ?");
        $lv->bind_param("i", $parent_id); $lv->execute();
        $lvr = $lv->get_result();
        if ($p = $lvr->fetch_assoc()) $category_level = $p['category_level'] + 1;
        $lv->close();
    }

    $category_image = null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/categories/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = basename($_FILES['category_image']['name']);
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])) {
            $ufn = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file_name);
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_dir . $ufn)) {
                $category_image = $ufn;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO product_categories (category_name, category_slug, category_description, category_image, parent_id, category_level, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssiii", $category_name, $category_slug, $category_description, $category_image, $parent_id, $category_level, $sort_order);
    if ($stmt->execute()) {
        $new_cat_id = $conn->insert_id;
        logActivity($conn, 'category', $new_cat_id, 'Category created',
            null,
            json_encode(['name'=>$category_name,'slug'=>$category_slug]),
            "Category '{$category_name}' created. Level: {$category_level}" . ($parent_id ? " | Parent ID: {$parent_id}" : ''),
            $actorId, $actorType
        );
        redirectWithMessage("../category.php", "Category added successfully.", "success");
    } else {
        redirectWithMessage("../category.php", "Failed to add category: " . $stmt->error, "error");
    }
    $stmt->close();
}

// ── ADD BLOG ──────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blog'])) {
    $blog_title            = $_POST['blog_title'];
    $blog_content          = $_POST['blog_content'];
    $blog_excerpt          = $_POST['blog_excerpt'];
    $blog_author           = $_POST['blog_author'];
    $blog_meta_title       = $_POST['blog_meta_title'] ?? '';
    $blog_meta_description = $_POST['blog_meta_description'] ?? '';
    $blog_meta_keywords    = $_POST['blog_meta_keywords'] ?? '';
    $blog_status           = $_POST['blog_status'] ?? 'draft';
    $blog_slug             = getUniqueSlug($conn, $blog_title);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO blogs (blog_title, blog_slug, blog_content, blog_excerpt, blog_author, blog_published_date, blog_status, blog_meta_title, blog_meta_description, blog_meta_keywords) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $blog_title, $blog_slug, $blog_content, $blog_excerpt, $blog_author, $blog_status, $blog_meta_title, $blog_meta_description, $blog_meta_keywords);
        if (!$stmt->execute()) throw new Exception("Failed to insert blog: " . $stmt->error);
        $blog_id = $conn->insert_id;
        $stmt->close();

        $blog_featured_image = '';
        if (isset($_FILES['blog_featured_image']) && !empty($_FILES['blog_featured_image']['name'])) {
            $target_dir = "../../uploads/blogs/";
            if (!file_exists($target_dir)) { if (!mkdir($target_dir, 0777, true)) throw new Exception("Failed to create upload directory"); }
            $tmp_name = $_FILES['blog_featured_image']['tmp_name'];
            $error    = $_FILES['blog_featured_image']['error'];
            if ($error === UPLOAD_ERR_OK) {
                $file_size = $_FILES['blog_featured_image']['size'];
                $file_type = mime_content_type($tmp_name);
                if (strpos($file_type, 'image') !== 0) throw new Exception("Only image files are allowed");
                if ($file_size > 5*1024*1024) throw new Exception("File size must be less than 5MB");
                $file_ext = strtolower(pathinfo($_FILES['blog_featured_image']['name'], PATHINFO_EXTENSION));
                $ufn = uniqid() . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($tmp_name, $target_dir . $ufn)) {
                    $blog_featured_image = '/sjfbi-js/uploads/blogs/' . $ufn;
                    $up = $conn->prepare("UPDATE blogs SET blog_featured_image = ? WHERE blog_id = ?");
                    $up->bind_param("si", $blog_featured_image, $blog_id);
                    if (!$up->execute()) throw new Exception("Failed to update blog with image: " . $up->error);
                    $up->close();
                } else throw new Exception("Failed to move uploaded file");
            }
        }

        logActivity($conn, 'blog', $blog_id, 'Blog post created',
            null,
            json_encode(['title'=>$blog_title,'slug'=>$blog_slug,'status'=>$blog_status]),
            "Blog '{$blog_title}' created by {$blog_author}. Status: {$blog_status}",
            $actorId, $actorType
        );

        $conn->commit();
        $_SESSION['message'] = ['type'=>'success','text'=>"Blog post added successfully! URL: /sjfbi-js/blogs/{$blog_slug}"];
    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($blog_featured_image)) { $fp = $_SERVER['DOCUMENT_ROOT'] . $blog_featured_image; if (file_exists($fp)) unlink($fp); }
        $_SESSION['message'] = ['type'=>'error','text'=>"Error: " . $e->getMessage()];
    }
    header("Location: ../blogs.php");
    exit;
}

// ── ADD EVENT ────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {

    $event_title            = trim($_POST['event_title']);
    $event_slug             = getUniqueSlug($conn, $event_title, 'company_events');
    $event_status           = $_POST['event_status'] ?? 'draft';
    $event_date             = $_POST['event_date'];
    $event_end_date         = $_POST['event_end_date'] ?: null;
    $event_time             = trim($_POST['event_time']);
    $event_location         = trim($_POST['event_location']);
    $event_address          = trim($_POST['event_address']);
    $event_category         = trim($_POST['event_category']);
    $event_audience         = trim($_POST['event_audience']);
    $event_excerpt          = trim($_POST['event_excerpt']);
    $event_content          = cleanContent($_POST['event_content']);
    $event_rsvp_url         = trim($_POST['event_rsvp_url']);
    $event_rsvp_deadline    = $_POST['event_rsvp_deadline'] ?: null;
    $event_meta_title       = trim($_POST['event_meta_title'] ?? '');
    $event_meta_description = trim($_POST['event_meta_description'] ?? '');
    $event_meta_keywords    = trim($_POST['event_meta_keywords'] ?? '');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO company_events (event_title, event_slug, event_status, event_date, event_end_date, event_time, event_location, event_address, event_category, event_audience, event_excerpt, event_content, event_rsvp_url, event_rsvp_deadline, event_meta_title, event_meta_description, event_meta_keywords) VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? ) ");
        $stmt->bind_param("sssssssssssssssss", $event_title, $event_slug, $event_status, $event_date, $event_end_date, $event_time, $event_location, $event_address, $event_category, $event_audience, $event_excerpt, $event_content, $event_rsvp_url, $event_rsvp_deadline, $event_meta_title, $event_meta_description, $event_meta_keywords);
        if (!$stmt->execute()) { throw new Exception($stmt->error);}
        $event_id = $conn->insert_id;
        $stmt->close();

        // Upload Event Image
        $event_image = '';

        if ( isset($_FILES['event_image']) && !empty($_FILES['event_image']['name'])) {
            $target_dir = "../../uploads/events/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $tmp_name = $_FILES['event_image']['tmp_name'];
            if (strpos(mime_content_type($tmp_name), 'image/') !== 0) {throw new Exception("Only image files are allowed."); }
            if ($_FILES['event_image']['size'] > 5 * 1024 * 1024) { throw new Exception("File size must be less than 5MB."); }
            $ext = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '_' . time() . '.' . $ext;

            if (move_uploaded_file($tmp_name, $target_dir . $filename)) {
                $event_image = '/sjfbi-js/uploads/events/' . $filename;
                $up = $conn->prepare("UPDATE company_events SET event_image = ? WHERE event_id = ? ");
                $up->bind_param("si", $event_image, $event_id);
                $up->execute();
                $up->close();
            }
        }

        logActivity($conn, 'event', $event_id, 'Event created', null,
            json_encode([
                'title' => $event_title,
                'status' => $event_status
            ]),
            "Event '{$event_title}' created.",
            $actorId,
            $actorType
        );

        $conn->commit();

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Event created successfully!'
        ];

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => $e->getMessage()
        ];
    }
    header("Location: ../events.php");
    exit;
}

// ── ADD SUGGESTION ────────────────────────────────────────────────────────────
elseif (isset($_POST['add_suggestion'])) {
    $product_id  = intval($_POST['product_id']);
    $dish_name   = htmlspecialchars(trim($_POST['dish_name']));
    $ingredients = htmlspecialchars(trim($_POST['ingredients']));
    $steps       = htmlspecialchars(trim($_POST['steps']));
    $prep_time   = !empty($_POST['prep_time_minutes'])  ? intval($_POST['prep_time_minutes'])  : null;
    $cook_time   = !empty($_POST['cook_time_minutes'])  ? intval($_POST['cook_time_minutes'])  : null;
    $difficulty  = $_POST['difficulty_level'] ?? 'Easy';

    if (!$product_id || empty($dish_name) || empty($ingredients) || empty($steps)) {
        redirectWithMessage("../cooking_suggestions.php", "Please fill in all required fields.", "error");
    }

    $ck = $conn->prepare("SELECT suggestion_id FROM product_cooking_suggestions WHERE product_id = ? AND dish_name = ?");
    $ck->bind_param("is", $product_id, $dish_name); $ck->execute();
    if ($ck->get_result()->num_rows > 0) {
        redirectWithMessage("../cooking_suggestions.php", "A suggestion with this dish name already exists for that product.", "error");
    }
    $ck->close();

    $stmt = $conn->prepare("INSERT INTO product_cooking_suggestions (product_id, dish_name, ingredients, steps, prep_time_minutes, cook_time_minutes, difficulty_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiis", $product_id, $dish_name, $ingredients, $steps, $prep_time, $cook_time, $difficulty);
    if ($stmt->execute()) {
        $new_sug_id = $conn->insert_id;
        logActivity($conn, 'cooking_suggestion', $new_sug_id, 'Cooking suggestion created',
            null,
            json_encode(['dish'=>$dish_name,'difficulty'=>$difficulty]),
            "Suggestion '{$dish_name}' added for product ID {$product_id}. Difficulty: {$difficulty}",
            $actorId, $actorType
        );
        redirectWithMessage("../cooking_suggestions.php", "Cooking suggestion '{$dish_name}' added successfully!", "success");
    } else {
        redirectWithMessage("../cooking_suggestions.php", "Failed to add suggestion: " . $stmt->error, "error");
    }
    $stmt->close();
}

// ── ADD MARKET ────────────────────────────────────────────────────────────────
elseif (isset($_POST['add_market'])) {

    $marketsDir = "../../uploads/markets/";
    $membersDir = "../../uploads/members/";

    $market_key    = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $_POST['market_key'])));
    $market_name   = htmlspecialchars(trim($_POST['market_name']));
    $location_short= htmlspecialchars(trim($_POST['location_short']));
    $location_full = htmlspecialchars(trim($_POST['location_full']));
    $description   = htmlspecialchars(trim($_POST['description']));
    $stall_count   = intval($_POST['stall_count']);
    $raw_map_input = trim($_POST['map_embed'] ?? '');
    preg_match('/src=["\']([^"\']+)["\']/', $raw_map_input, $map_matches);
    $map_embed = isset($map_matches[1])
        ? filter_var($map_matches[1], FILTER_SANITIZE_URL)
        : filter_var($raw_map_input, FILTER_SANITIZE_URL);
        
    $accent_color  = $_POST['accent_color'] ?? '#f97316';
    $display_order = intval($_POST['display_order'] ?? 0);

    $highlights_lines = explode("\n", trim($_POST['highlights']));
    $highlights_json  = json_encode(array_values(array_filter(array_map('trim', $highlights_lines))));

    $check = $conn->prepare("SELECT market_id FROM markets WHERE market_key = ?");
    $check->bind_param("s", $market_key);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        redirectWithMessage("../markets.php", "Market key '{$market_key}' already exists.", "error");
    }
    $check->close();

    $conn->begin_transaction();
    try {
        $main_image = null;
        if (!empty($_FILES['main_image']['name'])) {
            $saved = uploadSingleImage($_FILES['main_image'], $marketsDir, 'main');
            if ($saved === null) throw new Exception("Main image upload failed. Use JPG/PNG/WEBP under 5 MB.");
            $main_image = $saved;
        }

        $gallery_images = [];
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $count = count($_FILES['gallery_images']['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                $saved = uploadImage($_FILES['gallery_images'], $i, $marketsDir, "gallery_{$i}");
                if ($saved) $gallery_images[] = $saved;
            }
        }
        $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;

        $stmt = $conn->prepare("
            INSERT INTO markets
                (market_key, market_name, location_short, location_full, description,
                 highlights, stall_count, main_image, gallery_images, map_embed,
                 accent_color, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssissssi",
            $market_key, $market_name, $location_short, $location_full, $description,
            $highlights_json, $stall_count, $main_image, $gallery_json, $map_embed,
            $accent_color, $display_order
        );
        if (!$stmt->execute()) throw new Exception("Insert market failed: " . $stmt->error);
        $market_id = $conn->insert_id;
        $stmt->close();

        // Uses renamed column: member_name (was name)
        if (!empty($_POST['new_member_name'])) {
            $memberNames     = $_POST['new_member_name'];
            $memberPositions = $_POST['new_member_position'] ?? [];
            $memberOrders    = $_POST['new_member_order']    ?? [];
            $memberFileInput = $_FILES['new_member_image_file'] ?? null;

            foreach ($memberNames as $i => $name) {
                $name = htmlspecialchars(trim($name));
                if (empty($name)) continue;
                $position  = htmlspecialchars(trim($memberPositions[$i] ?? ''));
                $order     = intval($memberOrders[$i] ?? 0);
                $image_url = null;

                if ($memberFileInput && isset($memberFileInput['tmp_name'][$i])
                    && $memberFileInput['error'][$i] === UPLOAD_ERR_OK) {
                    $saved = uploadImage($memberFileInput, $i, $membersDir, "member_{$i}");
                    if ($saved) $image_url = $saved;
                }

                // Uses renamed column: member_name (was name)
                $ms = $conn->prepare("
                    INSERT INTO market_members (market_id, member_name, position, image_url, display_order)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $ms->bind_param("isssi", $market_id, $name, $position, $image_url, $order);
                $ms->execute();
                $ms->close();
            }
        }

        logActivity($conn, 'market', $market_id, 'Market created',
            null,
            json_encode(['market_name' => $market_name, 'market_key' => $market_key]),
            "Market '{$market_name}' created with key '{$market_key}'",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../markets.php", "Market '{$market_name}' added successfully!", "success");

    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($main_image) && file_exists($marketsDir . $main_image)) unlink($marketsDir . $main_image);
        foreach ($gallery_images ?? [] as $f) { if (file_exists($marketsDir . $f)) unlink($marketsDir . $f); }
        error_log("Error adding market: " . $e->getMessage());
        redirectWithMessage("../markets.php", "Failed to add market: " . $e->getMessage(), "error");
    }
}

// ── ADD RIDER ─────────────────────────────────────────────────────────────────
elseif (isset($_POST['action']) && $_POST['action'] === 'add_rider') {

    $account_id    = (int)($_POST['account_id']          ?? 0);
    $vehicle_type  = trim($_POST['vehicle_type']         ?? '');
    $variant_color = trim($_POST['variant_color']        ?? '');
    $plate         = trim($_POST['vehicle_plate_number'] ?? '');
    $contact       = trim($_POST['contact_number']       ?? '');
    $organization  = trim($_POST['organization']         ?? '');
    $full_name     = trim($_POST['full_name']             ?? '');

    $errors = [];
    if ($account_id <= 0)      $errors[] = 'Please select a valid account.';
    if (empty($vehicle_type))  $errors[] = 'Vehicle type is required.';
    if (empty($variant_color)) $errors[] = 'Vehicle color is required.';
    if (empty($plate))         $errors[] = 'Plate number is required.';
    if (empty($contact))       $errors[] = 'Contact number is required.';
    if (empty($organization))  $errors[] = 'Organization is required.';
    if (!empty($errors)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => implode(' ', $errors)];
        header('Location: ../riders.php');
        exit;
    }

    $image_path = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Photo must be JPEG, PNG or WEBP.'];
            header('Location: ../riders.php');
            exit;
        }
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Photo must be under 5MB.'];
            header('Location: ../riders.php');
            exit;
        }
        $ext   = match($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
        $fname = 'rider_' . $account_id . '_' . time() . '.' . $ext;
        $dir   = __DIR__ . '/../../uploads/riders/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to save rider photo.'];
            header('Location: ../riders.php');
            exit;
        }
        $image_path = 'uploads/riders/' . $fname;
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Rider photo is required.'];
        header('Location: ../riders.php');
        exit;
    }

    $conn->begin_transaction();
    try {
        $ck = $conn->prepare("SELECT role FROM accounts WHERE account_id = ? AND is_deleted = 0 LIMIT 1");
        $ck->bind_param('i', $account_id);
        $ck->execute();
        $acc = $ck->get_result()->fetch_assoc();
        if (!$acc) throw new Exception('Selected account does not exist.');
        if (in_array($acc['role'], ['admin', 'super_admin'], true)) throw new Exception('Admin accounts cannot be made riders.');

        $cr = $conn->prepare("SELECT rider_id FROM riders WHERE account_id = ? AND is_deleted = 0 LIMIT 1");
        $cr->bind_param('i', $account_id);
        $cr->execute();
        if ($cr->get_result()->num_rows > 0) throw new Exception('This account is already registered as a rider.');

        $ur = $conn->prepare("UPDATE accounts SET role = 'rider' WHERE account_id = ?");
        $ur->bind_param('i', $account_id);
        if (!$ur->execute()) throw new Exception('Failed to update account role.');

        // Uses renamed columns: rider_name (was full_name), rider_phone (was contact_number)
        $ir = $conn->prepare("
            INSERT INTO riders
                (account_id, image, rider_name, vehicle_type, vehicle_plate_number,
                 variant_color, rider_phone, organization, is_available, is_deleted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
        ");
        $fnVal = $full_name ?: null;
        $ir->bind_param('isssssss',
            $account_id, $image_path, $fnVal,
            $vehicle_type, $plate, $variant_color,
            $contact, $organization
        );
        if (!$ir->execute()) throw new Exception('Failed to create rider record.');
        $new_rider_id = (int)$conn->insert_id;

        // Uses renamed columns: account_first_name, account_last_name
        $na = $conn->prepare("SELECT account_first_name, account_last_name FROM accounts WHERE account_id = ?");
        $na->bind_param('i', $account_id);
        $na->execute();
        $nameRow = $na->get_result()->fetch_assoc();
        $nameStr = $nameRow ? "{$nameRow['account_first_name']} {$nameRow['account_last_name']}" : "Account #{$account_id}";

        logActivity($conn, 'rider', $new_rider_id, 'Rider created',
            null,
            json_encode(['vehicle_type' => $vehicle_type, 'plate' => $plate, 'org' => $organization]),
            "Rider created for {$nameStr}. Vehicle: {$vehicle_type} ({$plate}), Org: {$organization}",
            $actorId, $actorType
        );

        $conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => "Rider {$nameStr} added successfully!"];
        header('Location: ../riders.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        if ($image_path && file_exists(__DIR__ . '/../../' . $image_path)) unlink(__DIR__ . '/../../' . $image_path);
        $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
        header('Location: ../riders.php');
        exit;
    }
}
?>