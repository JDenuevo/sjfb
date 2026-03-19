<?php
// ==================== supadmin/functions/update.php ====================
session_start();
require '../../conn.php';
include 'slug_helper.php';
require_once 'activity_log_helper.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

function cleanContent($content) {
    if (empty($content)) return '';
    return stripslashes($content);
}

// ── UPDATE ACCOUNT ────────────────────────────────────────────────────────────
// Uses renamed columns: account_email, account_phone, account_first_name,
//                       account_last_name, account_address
if (isset($_POST['update_account'])) {
    $account_id = (int)($_POST['account_id'] ?? 0);
 
    // These names match the edit modal form inputs in accounts.php
    $username    = trim($_POST['username']          ?? '');
    $role        = trim($_POST['role']              ?? '');
    $first_name  = trim($_POST['account_first_name']?? '');
    $last_name   = trim($_POST['account_last_name'] ?? '');
    $email       = trim($_POST['account_email']     ?? '');
    $phone       = trim($_POST['account_phone']     ?? '');   // ← was 'phone_number' / 'account_phone_number'
    $address     = trim($_POST['account_address']   ?? '');   // ← was 'address'
    $city        = trim($_POST['city']              ?? '');
    $postal_code = trim($_POST['postal_code']       ?? '');
 
    if (!$account_id)
        redirectWithMessage("../accounts.php", "Invalid account ID.", "error");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        redirectWithMessage("../accounts.php", "Invalid email format.", "error");
 
    // Fetch old values for the activity log — uses renamed columns
    $old = $conn->prepare("SELECT username, role, account_email FROM accounts WHERE account_id = ? LIMIT 1");
    $old->bind_param("i", $account_id);
    $old->execute();
    $oldData = $old->get_result()->fetch_assoc();
    $old->close();
 
    $password = trim($_POST['password']         ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
 
    if (!empty($password)) {
        if ($password !== $confirm)
            redirectWithMessage("../accounts.php", "Passwords do not match.", "error");
        $hashed = password_hash($password, PASSWORD_DEFAULT);
 
        $stmt = $conn->prepare("
            UPDATE accounts
            SET username = ?, role = ?,
                account_first_name = ?, account_last_name = ?,
                account_email = ?, account_phone = ?,
                account_address = ?, city = ?, postal_code = ?,
                password_hash = ?
            WHERE account_id = ?
        ");
        $stmt->bind_param("ssssssssssi",
            $username, $role,
            $first_name, $last_name,
            $email, $phone,
            $address, $city, $postal_code,
            $hashed, $account_id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE accounts
            SET username = ?, role = ?,
                account_first_name = ?, account_last_name = ?,
                account_email = ?, account_phone = ?,
                account_address = ?, city = ?, postal_code = ?
            WHERE account_id = ?
        ");
        $stmt->bind_param("sssssssssi",
            $username, $role,
            $first_name, $last_name,
            $email, $phone,
            $address, $city, $postal_code,
            $account_id
        );
    }
 
    if ($stmt->execute()) {
        logActivity($conn, 'account', $account_id, 'Account updated',
            json_encode([
                'username' => $oldData['username'],
                'role'     => $oldData['role'],
                'email'    => $oldData['account_email'],
            ]),
            json_encode([
                'username' => $username,
                'role'     => $role,
                'email'    => $email,
            ]),
            "Account ID {$account_id} updated. Name: {$first_name} {$last_name}",
            $actorId, $actorType
        );
        $stmt->close();
        redirectWithMessage("../accounts.php", "Account updated successfully!", "success");
    } else {
        $err = $stmt->error;
        $stmt->close();
        redirectWithMessage("../accounts.php", "Failed to update account: {$err}", "error");
    }
}

// ── UPDATE PROFILE (self-service) ─────────────────────────────────────────────
elseif (isset($_POST['update_profile'])) {
    $account_id   = $_SESSION['account_id'];
    $username     = $_POST['username'];
    $first_name   = $_POST['first_name'];
    $last_name    = $_POST['last_name'];
    $email        = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address      = $_POST['address'];
    $city         = $_POST['city'];
    $postal_code  = $_POST['postal_code'];
    $password     = $_POST['password'];
    $confirm      = $_POST['confirm_password'];

    // Uses renamed: account_email
    $ck = $conn->prepare("SELECT account_id FROM accounts WHERE account_email = ? AND account_id != ?");
    $ck->bind_param("si", $email, $account_id); $ck->execute(); $ck->store_result();
    if ($ck->num_rows > 0) { $ck->close(); redirectWithMessage('../profile.php','Email already taken.','error'); }
    $ck->close();

    $cku = $conn->prepare("SELECT account_id FROM accounts WHERE username = ? AND account_id != ?");
    $cku->bind_param("si", $username, $account_id); $cku->execute(); $cku->store_result();
    if ($cku->num_rows > 0) { $cku->close(); redirectWithMessage('../profile.php','Username already taken.','error'); }
    $cku->close();

    if (!empty($password) || !empty($confirm)) {
        if ($password !== $confirm) redirectWithMessage('../profile.php','Passwords do not match.','error');
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE accounts
            SET username=?, account_first_name=?, account_last_name=?,
                account_email=?, account_phone=?, account_address=?,
                city=?, postal_code=?, password_hash=?
            WHERE account_id=?
        ");
        $stmt->bind_param("sssssssssi",
            $username, $first_name, $last_name, $email,
            $phone_number, $address, $city, $postal_code, $hashed, $account_id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE accounts
            SET username=?, account_first_name=?, account_last_name=?,
                account_email=?, account_phone=?, account_address=?,
                city=?, postal_code=?
            WHERE account_id=?
        ");
        $stmt->bind_param("ssssssssi",
            $username, $first_name, $last_name, $email,
            $phone_number, $address, $city, $postal_code, $account_id
        );
    }

    if ($stmt->execute()) {
        logActivity($conn, 'account', $account_id, 'Profile updated',
            null,
            json_encode(['username'=>$username,'email'=>$email]),
            "User ID {$account_id} updated their own profile.",
            $account_id, 'customer'
        );
        redirectWithMessage('../profile.php','Account updated successfully.','success');
    } else {
        redirectWithMessage('../profile.php','Failed to update account.','error');
    }
    $stmt->close(); $conn->close();
}

// ── UPDATE PRODUCT ────────────────────────────────────────────────────────────
elseif (isset($_POST['update_product'])) {
    $product_id          = intval($_POST['product_id']);
    $product_name        = htmlspecialchars(trim($_POST['product_name']));
    $product_description = htmlspecialchars(trim($_POST['product_description']));
    $product_unit        = htmlspecialchars(trim($_POST['product_unit'] ?? ''));
    $selected_categories = $_POST['product_categories'] ?? [];
    $primary_category    = intval($_POST['primary_category'] ?? 0);

    if (empty($product_name) || empty($selected_categories)) {
        redirectWithMessage("../products.php","Product name and at least one category are required.","error");
    }

    $op = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $op->bind_param("i",$product_id); $op->execute();
    $oldProd = $op->get_result()->fetch_assoc(); $op->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE products SET product_name=?,product_description=?,product_unit=? WHERE product_id=?");
        $stmt->bind_param("sssi",$product_name,$product_description,$product_unit,$product_id);
        $stmt->execute(); $stmt->close();

        $del = $conn->prepare("DELETE FROM product_category_links WHERE product_id=?");
        $del->bind_param("i",$product_id); $del->execute(); $del->close();
        foreach ($selected_categories as $cat_id) {
            $ip = ($cat_id == $primary_category) ? 1 : 0;
            $lk = $conn->prepare("INSERT INTO product_category_links (product_id,category_id,is_primary) VALUES (?,?,?)");
            $lk->bind_param("iii",$product_id,$cat_id,$ip); $lk->execute(); $lk->close();
        }
        if ($primary_category == 0 && !empty($selected_categories)) {
            $fc = $selected_categories[0];
            $up = $conn->prepare("UPDATE product_category_links SET is_primary=1 WHERE product_id=? AND category_id=?");
            $up->bind_param("ii",$product_id,$fc); $up->execute(); $up->close();
        }

        if (isset($_POST['variant_name']) && is_array($_POST['variant_name'])) {
            $variant_ids     = $_POST['variant_id'];
            $variant_names   = $_POST['variant_name'];   $unit_types      = $_POST['unit_type'];
            $minimum_orders  = $_POST['minimum_order'];  $order_increments= $_POST['order_increment'];
            $stock_quantities= $_POST['stock_quantity']; $variant_prices  = $_POST['variant_price'];
            $discount_prices = $_POST['discount_price'] ?? [];
            for ($i = 0; $i < count($variant_names); $i++) {
                $variant_id     = !empty($variant_ids[$i]) ? intval($variant_ids[$i]) : null;
                $variant_name   = htmlspecialchars(trim($variant_names[$i]));
                $unit_type      = $unit_types[$i];
                $minimum_order  = floatval($minimum_orders[$i]);
                $order_increment= floatval($order_increments[$i]);
                $stock_quantity = intval($stock_quantities[$i]);
                $variant_price  = floatval($variant_prices[$i]);
                $discount_price = !empty($discount_prices[$i]) ? floatval($discount_prices[$i]) : null;
                $stock_status   = $stock_quantity > 0 ? 'In Stock' : 'Out of Stock';
                if ($variant_id) {
                    $s = $conn->prepare("UPDATE product_variants SET variant_name=?,unit_type=?,minimum_order=?,order_increment=?,stock_quantity=?,variant_price=?,discount_price=?,stock_status=? WHERE variant_id=?");
                    $s->bind_param("ssddiddsi",$variant_name,$unit_type,$minimum_order,$order_increment,$stock_quantity,$variant_price,$discount_price,$stock_status,$variant_id);
                } else {
                    $s = $conn->prepare("INSERT INTO product_variants (product_id,variant_name,unit_type,minimum_order,order_increment,stock_quantity,variant_price,discount_price,stock_status) VALUES (?,?,?,?,?,?,?,?,?)");
                    $s->bind_param("issddidds",$product_id,$variant_name,$unit_type,$minimum_order,$order_increment,$stock_quantity,$variant_price,$discount_price,$stock_status);
                }
                $s->execute(); $s->close();
            }
        }

        if (!empty($_POST['deleted_images'])) {
            $deletedImages = explode(',', $_POST['deleted_images']);
            $ck_p = $conn->prepare("SELECT image_id FROM product_images WHERE image_id IN (" . implode(',', array_fill(0,count($deletedImages),'?')) . ") AND is_primary=1");
            $types = str_repeat('i',count($deletedImages));
            $ck_p->bind_param($types,...$deletedImages); $ck_p->execute();
            $wasPrimaryDeleted = $ck_p->get_result()->num_rows > 0; $ck_p->close();
            foreach ($deletedImages as $imageId) {
                $imageId = intval($imageId);
                if ($imageId > 0) {
                    $gi = $conn->prepare("SELECT image_path FROM product_images WHERE image_id=?");
                    $gi->bind_param("i",$imageId); $gi->execute();
                    if ($row = $gi->get_result()->fetch_assoc()) { $p = "../../uploads/products/".$row['image_path']; if (file_exists($p)) unlink($p); }
                    $gi->close();
                    $di = $conn->prepare("DELETE FROM product_images WHERE image_id=?");
                    $di->bind_param("i",$imageId); $di->execute(); $di->close();
                }
            }
            if ($wasPrimaryDeleted) {
                $fnp = $conn->prepare("SELECT image_id FROM product_images WHERE product_id=? ORDER BY image_id ASC LIMIT 1");
                $fnp->bind_param("i",$product_id); $fnp->execute();
                if ($nr = $fnp->get_result()->fetch_assoc()) {
                    $sp = $conn->prepare("UPDATE product_images SET is_primary=1 WHERE image_id=?");
                    $sp->bind_param("i",$nr['image_id']); $sp->execute(); $sp->close();
                }
                $fnp->close();
            }
        }

        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../../uploads/products/";
            $ecnt = $conn->prepare("SELECT COUNT(*) as c FROM product_images WHERE product_id=?");
            $ecnt->bind_param("i",$product_id); $ecnt->execute();
            $existing = $ecnt->get_result()->fetch_assoc()['c']; $ecnt->close();
            $isPrimary = ($existing == 0) ? 1 : 0;
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fn = basename($_FILES['product_images']['name'][$key]);
                    $ft = mime_content_type($tmp_name);
                    if (strpos($ft,'image')===0 && $_FILES['product_images']['size'][$key] <= 5*1024*1024) {
                        $ufn = uniqid().'_'.$fn;
                        if (move_uploaded_file($tmp_name, $target_dir.$ufn)) {
                            $ins = $conn->prepare("INSERT INTO product_images (product_id,image_path,is_primary) VALUES (?,?,?)");
                            $ins->bind_param("isi",$product_id,$ufn,$isPrimary); $ins->execute(); $ins->close();
                            $isPrimary = 0;
                        }
                    }
                }
            }
        }

        logActivity($conn, 'product', $product_id, 'Product updated',
            json_encode(['product_name'=>$oldProd['product_name']]),
            json_encode(['product_name'=>$product_name,'unit'=>$product_unit]),
            "Product ID {$product_id} updated. Old name: '{$oldProd['product_name']}' → New name: '{$product_name}'",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../products.php","Product updated successfully!","success");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update error: " . $e->getMessage());
        redirectWithMessage("../products.php","Failed to update product: ".$e->getMessage(),"error");
    }
}

// ── UPDATE CATEGORY ───────────────────────────────────────────────────────────
elseif (isset($_POST['update_category'])) {
    $category_id          = intval($_POST['category_id']);
    $category_name        = trim($_POST['category_name']);
    $category_slug        = trim($_POST['category_slug'] ?? '');
    $category_description = trim($_POST['category_description'] ?? '');
    $parent_id            = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $sort_order           = intval($_POST['sort_order'] ?? 0);
    $is_active            = intval($_POST['is_active'] ?? 1);
    if (empty($category_name)) redirectWithMessage("../category.php","Category name is required","error");
    if (empty($category_slug)) $category_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/','-',$category_name)));

    $ck = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE category_name=? AND category_id!=? AND is_active=1");
    $ck->bind_param("si",$category_name,$category_id); $ck->execute(); $ck->bind_result($cnt); $ck->fetch(); $ck->close();
    if ($cnt > 0) redirectWithMessage("../category.php","Category name already exists","error");

    $cks = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE category_slug=? AND category_id!=? AND is_active=1");
    $cks->bind_param("si",$category_slug,$category_id); $cks->execute(); $cks->bind_result($sc); $cks->fetch(); $cks->close();
    if ($sc > 0) redirectWithMessage("../category.php","Category slug already exists","error");

    $category_level = 1;
    if ($parent_id) {
        $lv = $conn->prepare("SELECT category_level FROM product_categories WHERE category_id=?");
        $lv->bind_param("i",$parent_id); $lv->execute();
        if ($p = $lv->get_result()->fetch_assoc()) $category_level = $p['category_level'] + 1;
        $lv->close();
    }

    $oc = $conn->prepare("SELECT category_name, is_active FROM product_categories WHERE category_id=?");
    $oc->bind_param("i",$category_id); $oc->execute();
    $oldCat = $oc->get_result()->fetch_assoc(); $oc->close();

    $category_image = null; $upload_new_image = false;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/categories/";
        if (!file_exists($target_dir)) mkdir($target_dir,0777,true);
        $fn = basename($_FILES['category_image']['name']);
        $ext= strtolower(pathinfo($fn,PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
            $oi = $conn->prepare("SELECT category_image FROM product_categories WHERE category_id=?");
            $oi->bind_param("i",$category_id); $oi->execute();
            $old_img = $oi->get_result()->fetch_assoc(); $oi->close();
            $ufn = uniqid().'_'.preg_replace('/[^a-zA-Z0-9.]/','',$fn);
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_dir.$ufn)) {
                $category_image = $ufn; $upload_new_image = true;
                if (!empty($old_img['category_image'])) { $op = $target_dir.$old_img['category_image']; if (file_exists($op)) unlink($op); }
            }
        }
    }

    if ($upload_new_image) {
        $stmt = $conn->prepare("UPDATE product_categories SET category_name=?,category_slug=?,category_description=?,category_image=?,parent_id=?,category_level=?,sort_order=?,is_active=? WHERE category_id=?");
        $stmt->bind_param("ssssiiiii",$category_name,$category_slug,$category_description,$category_image,$parent_id,$category_level,$sort_order,$is_active,$category_id);
    } else {
        $stmt = $conn->prepare("UPDATE product_categories SET category_name=?,category_slug=?,category_description=?,parent_id=?,category_level=?,sort_order=?,is_active=? WHERE category_id=?");
        $stmt->bind_param("sssiiiii",$category_name,$category_slug,$category_description,$parent_id,$category_level,$sort_order,$is_active,$category_id);
    }
    if ($stmt->execute()) {
        logActivity($conn,'category',$category_id,'Category updated',
            json_encode(['name'=>$oldCat['category_name'],'is_active'=>$oldCat['is_active']]),
            json_encode(['name'=>$category_name,'slug'=>$category_slug,'is_active'=>$is_active]),
            "Category ID {$category_id} updated. Old name: '{$oldCat['category_name']}' → '{$category_name}'",
            $actorId, $actorType
        );
        redirectWithMessage("../category.php","Category updated successfully","success");
    } else {
        redirectWithMessage("../category.php","Failed to update category: ".$stmt->error,"error");
    }
    $stmt->close();
}

// ── UPDATE BLOG ───────────────────────────────────────────────────────────────
elseif (isset($_POST['update_blog'])) {
    $blog_id               = (int)$_POST['blog_id'];
    $blog_title            = trim($_POST['blog_title']);
    $blog_content          = cleanContent($_POST['blog_content']);
    $blog_excerpt          = trim($_POST['blog_excerpt']);
    $blog_author           = trim($_POST['blog_author']);
    $blog_status           = $_POST['blog_status'] ?? 'draft';
    $blog_meta_title       = trim($_POST['blog_meta_title'] ?? '');
    $blog_meta_description = trim($_POST['blog_meta_description'] ?? '');
    $blog_meta_keywords    = trim($_POST['blog_meta_keywords'] ?? '');

    $current_query = $conn->prepare("SELECT blog_slug, blog_featured_image, blog_title FROM blogs WHERE blog_id = ?");
    $current_query->bind_param("i", $blog_id);
    $current_query->execute();
    $current_blog = $current_query->get_result()->fetch_assoc();
    $current_query->close();

    if (!$current_blog) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Blog post not found.'];
        header("Location: ../blogs.php");
        exit;
    }

    $blog_slug = ($current_blog['blog_title'] !== $blog_title)
        ? getUniqueSlug($conn, $blog_title, 'blogs', $blog_id)
        : $current_blog['blog_slug'];

    $conn->begin_transaction();
    try {
        $query  = "UPDATE blogs SET blog_title=?,blog_slug=?,blog_content=?,blog_excerpt=?,blog_author=?,blog_status=?,blog_meta_title=?,blog_meta_description=?,blog_meta_keywords=?";
        $params = [$blog_title,$blog_slug,$blog_content,$blog_excerpt,$blog_author,$blog_status,$blog_meta_title,$blog_meta_description,$blog_meta_keywords];
        $types  = "sssssssss";

        $new_image_uploaded = isset($_FILES['blog_featured_image']) && $_FILES['blog_featured_image']['error'] === UPLOAD_ERR_OK;
        if ($new_image_uploaded) { $query .= ", blog_featured_image = ?"; }
        $query .= " WHERE blog_id = ?";
        $types .= $new_image_uploaded ? "si" : "i";

        $stmt = $conn->prepare($query);
        if ($new_image_uploaded) {
            $target_dir = "../../uploads/blogs/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $file_type = mime_content_type($_FILES['blog_featured_image']['tmp_name']);
            if (strpos($file_type, 'image/') !== 0) throw new Exception("Only image files are allowed");
            if ($_FILES['blog_featured_image']['size'] > 5 * 1024 * 1024) throw new Exception("File size must be less than 5MB");
            $file_ext = strtolower(pathinfo($_FILES['blog_featured_image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '_' . time() . '.' . $file_ext;
            $target_path = $target_dir . $filename;
            if (move_uploaded_file($_FILES['blog_featured_image']['tmp_name'], $target_path)) {
                $blog_featured_image = '/sjfbi-js/uploads/blogs/' . $filename;
                $params[] = $blog_featured_image;
            } else throw new Exception("Failed to upload image");
        }
        $params[] = $blog_id;
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) throw new Exception("Failed to update blog: " . $stmt->error);

        if ($new_image_uploaded && !empty($current_blog['blog_featured_image'])) {
            $old_image_path = $_SERVER['DOCUMENT_ROOT'] . $current_blog['blog_featured_image'];
            if (file_exists($old_image_path)) unlink($old_image_path);
        }

        $actorData = getActorFromSession();
        logActivity($conn, 'blog', $blog_id, 'Blog updated',
            json_encode(['old_title' => $current_blog['blog_title']]),
            json_encode(['title' => $blog_title, 'status' => $blog_status]),
            "Blog '{$blog_title}' updated. Status: {$blog_status}",
            $actorData['userId'], $actorData['userType']
        );

        $conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Blog post updated successfully!'];
    } catch (Exception $e) {
        $conn->rollback();
        if (isset($target_path) && file_exists($target_path)) unlink($target_path);
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
    }
    header("Location: ../blogs.php");
    exit;
}

// ── UPDATE SUGGESTION ─────────────────────────────────────────────────────────
elseif (isset($_POST['update_suggestion'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    $product_id    = intval($_POST['product_id']);
    $dish_name     = htmlspecialchars(trim($_POST['dish_name']));
    $ingredients   = htmlspecialchars(trim($_POST['ingredients']));
    $steps         = htmlspecialchars(trim($_POST['steps']));
    $prep_time     = !empty($_POST['prep_time_minutes']) ? intval($_POST['prep_time_minutes']) : null;
    $cook_time     = !empty($_POST['cook_time_minutes']) ? intval($_POST['cook_time_minutes']) : null;
    $difficulty    = $_POST['difficulty_level'] ?? 'Easy';

    if (!$suggestion_id||!$product_id||empty($dish_name)||empty($ingredients)||empty($steps)) {
        redirectWithMessage("../cooking_suggestions.php","Please fill in all required fields.","error");
    }
    $ck = $conn->prepare("SELECT suggestion_id FROM product_cooking_suggestions WHERE product_id=? AND dish_name=? AND suggestion_id!=?");
    $ck->bind_param("isi",$product_id,$dish_name,$suggestion_id); $ck->execute();
    if ($ck->get_result()->num_rows > 0) redirectWithMessage("../cooking_suggestions.php","Dish name already exists for that product.","error");
    $ck->close();

    $os = $conn->prepare("SELECT dish_name, difficulty_level FROM product_cooking_suggestions WHERE suggestion_id=?");
    $os->bind_param("i",$suggestion_id); $os->execute();
    $oldSug = $os->get_result()->fetch_assoc(); $os->close();

    $stmt = $conn->prepare("UPDATE product_cooking_suggestions SET product_id=?,dish_name=?,ingredients=?,steps=?,prep_time_minutes=?,cook_time_minutes=?,difficulty_level=? WHERE suggestion_id=?");
    $stmt->bind_param("isssiisi",$product_id,$dish_name,$ingredients,$steps,$prep_time,$cook_time,$difficulty,$suggestion_id);
    if ($stmt->execute()) {
        logActivity($conn,'cooking_suggestion',$suggestion_id,'Cooking suggestion updated',
            json_encode(['dish_name'=>$oldSug['dish_name'],'difficulty'=>$oldSug['difficulty_level']]),
            json_encode(['dish_name'=>$dish_name,'difficulty'=>$difficulty]),
            "Suggestion ID {$suggestion_id} updated. Old dish: '{$oldSug['dish_name']}' → '{$dish_name}'",
            $actorId, $actorType
        );
        redirectWithMessage("../cooking_suggestions.php","Suggestion '{$dish_name}' updated successfully!","success");
    } else {
        redirectWithMessage("../cooking_suggestions.php","Failed to update suggestion: ".$stmt->error,"error");
    }
    $stmt->close();
}

// ── UPDATE REVIEW STATUS ──────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status'])) {
    $review_id = intval($_POST['review_id']);
    $status    = $_POST['status'];
    $allowed   = ['pending','approved','rejected','spam'];
    if (!in_array($status, $allowed, true)) redirectWithMessage("../reviews.php", "Invalid status.", "error");

    $or = $conn->prepare("SELECT status FROM reviews WHERE review_id = ?");
    $or->bind_param("i", $review_id); $or->execute();
    $oldReview = $or->get_result()->fetch_assoc(); $or->close();
    if (!$oldReview) redirectWithMessage("../reviews.php", "Review not found.", "error");

    $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE review_id = ?");
    $stmt->bind_param("si", $status, $review_id);
    if ($stmt->execute()) {
        logActivity($conn,'review',$review_id,'Review status updated',
            $oldReview['status'], $status,
            "Review ID {$review_id} status changed from '{$oldReview['status']}' to '{$status}'.",
            $actorId, $actorType
        );
        redirectWithMessage("../reviews.php", "Review marked as " . ucfirst($status) . ".", "success");
    } else {
        redirectWithMessage("../reviews.php", "Failed to update review status.", "error");
    }
    $stmt->close();
}

// ── UPDATE MARKET ─────────────────────────────────────────────────────────────
elseif (isset($_POST['update_market'])) {
    $marketsDir = "../../uploads/markets/";
    $membersDir = "../../uploads/members/";

    $market_id     = intval($_POST['market_id']);
    $market_name   = htmlspecialchars(trim($_POST['market_name']));
    $location_short= htmlspecialchars(trim($_POST['location_short']));
    $location_full = htmlspecialchars(trim($_POST['location_full']));
    $description   = htmlspecialchars(trim($_POST['description']));
    $stall_count   = intval($_POST['stall_count']);
    $map_embed     = htmlspecialchars(trim($_POST['map_embed'] ?? ''));
    $accent_color  = $_POST['accent_color'] ?? '#f97316';
    $display_order = intval($_POST['display_order'] ?? 0);

    $highlights_lines = explode("\n", trim($_POST['highlights']));
    $highlights_json  = json_encode(array_values(array_filter(array_map('trim', $highlights_lines))));

    $doUpload = function (array $file, string $dir, string $suffix = ''): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        $mime = mime_content_type($file['tmp_name']);
        if (strpos($mime, 'image/') !== 0)    return null;
        if ($file['size'] > 5 * 1024 * 1024) return null;
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . ($suffix ? "_{$suffix}" : '') . '.' . $ext;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return move_uploaded_file($file['tmp_name'], $dir . $filename) ? $filename : null;
    };

    $doUploadIndexed = function (array $filesArray, int $i, string $dir, string $suffix = '') use ($doUpload): ?string {
        return $doUpload([
            'error'    => $filesArray['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
            'tmp_name' => $filesArray['tmp_name'][$i] ?? '',
            'name'     => $filesArray['name'][$i]     ?? '',
            'size'     => $filesArray['size'][$i]     ?? 0,
        ], $dir, $suffix);
    };

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            UPDATE markets
            SET market_name=?, location_short=?, location_full=?, description=?,
                highlights=?, stall_count=?, map_embed=?, accent_color=?, display_order=?
            WHERE market_id=?
        ");
        $stmt->bind_param("sssssisssi",
            $market_name, $location_short, $location_full, $description, $highlights_json,
            $stall_count, $map_embed, $accent_color, $display_order, $market_id
        );
        if (!$stmt->execute()) throw new Exception("Update market info failed: " . $stmt->error);
        $stmt->close();

        if (!empty($_FILES['main_image']['name'])) {
            $saved = $doUpload($_FILES['main_image'], $marketsDir, 'main');
            if ($saved === null) throw new Exception("Main image upload failed.");
            $old = $conn->prepare("SELECT main_image FROM markets WHERE market_id=?");
            $old->bind_param("i", $market_id); $old->execute();
            $oldImg = $old->get_result()->fetch_assoc()['main_image'] ?? null; $old->close();
            if ($oldImg && file_exists($marketsDir . $oldImg)) unlink($marketsDir . $oldImg);
            $s = $conn->prepare("UPDATE markets SET main_image=? WHERE market_id=?");
            $s->bind_param("si", $saved, $market_id); $s->execute(); $s->close();
        }

        if (!empty($_FILES['gallery_images']['name'][0])) {
            $gq = $conn->prepare("SELECT gallery_images FROM markets WHERE market_id=?");
            $gq->bind_param("i", $market_id); $gq->execute();
            $row = $gq->get_result()->fetch_assoc(); $gq->close();
            $existing_gallery = !empty($row['gallery_images']) ? (json_decode($row['gallery_images'], true) ?: []) : [];
            $count = count($_FILES['gallery_images']['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                $saved = $doUploadIndexed($_FILES['gallery_images'], $i, $marketsDir, "gallery_{$i}");
                if ($saved) $existing_gallery[] = $saved;
            }
            $gallery_json = json_encode(array_values($existing_gallery));
            $s = $conn->prepare("UPDATE markets SET gallery_images=? WHERE market_id=?");
            $s->bind_param("si", $gallery_json, $market_id); $s->execute(); $s->close();
        }

        // Update EXISTING team members — uses renamed column: member_name (was name)
        if (isset($_POST['member_id'])) {
            $memberFilesByKey = [];
            if (!empty($_FILES['member_image_file'])) {
                foreach ($_FILES['member_image_file']['error'] as $memberId => $err) {
                    $memberFilesByKey[$memberId] = [
                        'error'    => $err,
                        'tmp_name' => $_FILES['member_image_file']['tmp_name'][$memberId] ?? '',
                        'name'     => $_FILES['member_image_file']['name'][$memberId]     ?? '',
                        'size'     => $_FILES['member_image_file']['size'][$memberId]     ?? 0,
                    ];
                }
            }

            foreach ($_POST['member_id'] as $idx => $member_id) {
                $member_id = intval($member_id);
                $name      = htmlspecialchars(trim($_POST['member_name'][$idx]     ?? ''));
                $position  = htmlspecialchars(trim($_POST['member_position'][$idx] ?? ''));
                $order     = intval($_POST['member_order'][$idx]                   ?? 0);

                $new_image_url = null;
                if (isset($memberFilesByKey[$member_id]) && $memberFilesByKey[$member_id]['error'] === UPLOAD_ERR_OK) {
                    $saved = $doUpload($memberFilesByKey[$member_id], $membersDir, "member_{$member_id}");
                    if ($saved) {
                        $og = $conn->prepare("SELECT image_url FROM market_members WHERE member_id=?");
                        $og->bind_param("i", $member_id); $og->execute();
                        $oldPhoto = $og->get_result()->fetch_assoc()['image_url'] ?? null; $og->close();
                        if ($oldPhoto && file_exists($membersDir . $oldPhoto)) unlink($membersDir . $oldPhoto);
                        $new_image_url = $saved;
                    }
                }

                // Uses renamed column: member_name (was name)
                if ($new_image_url !== null) {
                    $s = $conn->prepare("UPDATE market_members SET member_name=?, position=?, image_url=?, display_order=? WHERE member_id=? AND market_id=?");
                    $s->bind_param("sssiii", $name, $position, $new_image_url, $order, $member_id, $market_id);
                } else {
                    $s = $conn->prepare("UPDATE market_members SET member_name=?, position=?, display_order=? WHERE member_id=? AND market_id=?");
                    $s->bind_param("ssiii", $name, $position, $order, $member_id, $market_id);
                }
                $s->execute(); $s->close();
            }
        }

        // Add NEW team members — uses renamed column: member_name (was name)
        if (!empty($_POST['new_member_name'])) {
            $newMemberFiles = $_FILES['new_member_image_file'] ?? null;
            foreach ($_POST['new_member_name'] as $i => $name) {
                $name = htmlspecialchars(trim($name));
                if (empty($name)) continue;
                $position  = htmlspecialchars(trim($_POST['new_member_position'][$i] ?? ''));
                $order     = intval($_POST['new_member_order'][$i] ?? 0);
                $image_url = null;
                if ($newMemberFiles && isset($newMemberFiles['tmp_name'][$i])
                    && ($newMemberFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $saved = $doUploadIndexed($newMemberFiles, $i, $membersDir, "newmember_{$i}");
                    if ($saved) $image_url = $saved;
                }
                // Uses renamed column: member_name (was name)
                $s = $conn->prepare("INSERT INTO market_members (market_id, member_name, position, image_url, display_order) VALUES (?, ?, ?, ?, ?)");
                $s->bind_param("isssi", $market_id, $name, $position, $image_url, $order);
                $s->execute(); $s->close();
            }
        }

        // Product links — uses renamed PK: market_product_id (was id)
        if (isset($_POST['product_link_id'])) {
            foreach ($_POST['product_link_id'] as $i => $link_id) {
                $order = intval($_POST['product_order'][$i] ?? 0);
                $s = $conn->prepare("UPDATE market_products SET display_order=? WHERE market_product_id=?");
                $s->bind_param("ii", $order, $link_id); $s->execute(); $s->close();
            }
        }

        if (!empty($_POST['new_product_ids'])) {
            $new_order = intval($_POST['new_products_order'] ?? 0);
            foreach ($_POST['new_product_ids'] as $product_id) {
                if (empty($product_id)) continue;
                $ck = $conn->prepare("SELECT market_product_id FROM market_products WHERE market_id=? AND product_id=?");
                $ck->bind_param("ii", $market_id, $product_id); $ck->execute();
                if ($ck->get_result()->num_rows === 0) {
                    $s = $conn->prepare("INSERT INTO market_products (market_id, product_id, display_order) VALUES (?, ?, ?)");
                    $s->bind_param("iii", $market_id, $product_id, $new_order);
                    $s->execute(); $s->close();
                    $new_order++;
                }
                $ck->close();
            }
        }

        logActivity($conn, 'market', $market_id, 'Market updated',
            null,
            json_encode(['market_name' => $market_name]),
            "Market ID {$market_id} updated",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../markets.php", "Market '{$market_name}' updated successfully!", "success");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating market: " . $e->getMessage());
        redirectWithMessage("../markets.php", "Failed to update market: " . $e->getMessage(), "error");
    }
}

// ── UPDATE RIDER ──────────────────────────────────────────────────────────────
// Uses renamed columns: rider_name (was full_name), rider_phone (was contact_number)
elseif (isset($_POST['action']) && $_POST['action'] === 'update_rider') {

    $rider_id      = (int)($_POST['rider_id']            ?? 0);
    $vehicle_type  = trim($_POST['vehicle_type']         ?? '');
    $variant_color = trim($_POST['variant_color']        ?? '');
    $plate         = trim($_POST['vehicle_plate_number'] ?? '');
    $contact       = trim($_POST['contact_number']       ?? '');
    $organization  = trim($_POST['organization']         ?? '');
    $full_name     = trim($_POST['full_name']             ?? '');
    $is_available  = (int)($_POST['is_available']        ?? 1);

    if (!$rider_id || !$vehicle_type || !$variant_color || !$plate || !$contact || !$organization) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'All required fields must be filled.'];
        header('Location: ../riders.php');
        exit;
    }

    if ($is_available === 0) {
        $ca = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'");
        $ca->bind_param('i', $rider_id); $ca->execute();
        if ((int)$ca->get_result()->fetch_assoc()['cnt'] > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Cannot mark rider as offline while they have active deliveries.'];
            header('Location: ../riders.php');
            exit;
        }
    }

    $ov = $conn->prepare("SELECT * FROM riders WHERE rider_id = ? LIMIT 1");
    $ov->bind_param('i', $rider_id); $ov->execute();
    $old = $ov->get_result()->fetch_assoc();
    if (!$old) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Rider not found.'];
        header('Location: ../riders.php');
        exit;
    }

    $image_path = $old['image'];
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
        $fname = 'rider_' . $old['account_id'] . '_' . time() . '.' . $ext;
        $dir   = __DIR__ . '/../../uploads/riders/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
            if ($old['image'] && file_exists(__DIR__ . '/../../' . $old['image'])) unlink(__DIR__ . '/../../' . $old['image']);
            $image_path = 'uploads/riders/' . $fname;
        }
    }

    $fnVal = $full_name ?: null;
    // Uses renamed columns: rider_name (was full_name), rider_phone (was contact_number)
    $stmt = $conn->prepare("
        UPDATE riders SET
            image = ?, rider_name = ?, vehicle_type = ?, vehicle_plate_number = ?,
            variant_color = ?, rider_phone = ?, organization = ?,
            is_available = ?, updated_at = NOW()
        WHERE rider_id = ?
    ");
    $stmt->bind_param('sssssssii',
        $image_path, $fnVal,
        $vehicle_type, $plate, $variant_color,
        $contact, $organization,
        $is_available, $rider_id
    );

    if ($stmt->execute()) {
        logActivity($conn, 'rider', $rider_id, 'Rider updated',
            json_encode(['vehicle_type' => $old['vehicle_type'], 'plate' => $old['vehicle_plate_number'], 'is_available' => $old['is_available']]),
            json_encode(['vehicle_type' => $vehicle_type, 'plate' => $plate, 'is_available' => $is_available]),
            "Rider ID {$rider_id} updated. Availability: " . ($is_available ? 'available' : 'offline') . " | Vehicle: {$vehicle_type} ({$plate}), Org: {$organization}",
            $actorId, $actorType
        );
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Rider updated successfully!'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update rider.'];
    }
    header('Location: ../riders.php');
    exit;
}
?>