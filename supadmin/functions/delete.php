<?php
// ==================== delete.php ====================
ob_start(); // ← very first line, before anything else
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

// ==================== FORM-BASED DELETES ====================

// Delete Account
if (isset($_POST['delete_account'])) {
    $account_id = $_POST['account_id'];
    $stmt = $conn->prepare("DELETE FROM accounts WHERE account_id = ?");
    $stmt->bind_param("i", $account_id);
    if ($stmt->execute()) {
        redirectWithMessage("../accounts.php", "Account successfully deleted!", "success");
    } else {
        redirectWithMessage("../accounts.php", "Failed to delete account.", "error");
    }
}

// Delete Product
elseif (isset($_POST['delete_product'], $_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    if ($product_id <= 0) redirectWithMessage("../products.php", "Invalid product ID", "error");

    $conn->begin_transaction();
    try {
        $name_query = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $name_query->bind_param("i", $product_id);
        $name_query->execute();
        $product_name = $name_query->get_result()->fetch_assoc()['product_name'];
        $name_query->close();

        $imageStmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $imageStmt->bind_param("i", $product_id);
        $imageStmt->execute();
        $imageResult = $imageStmt->get_result();
        while ($imageRow = $imageResult->fetch_assoc()) {
            $image_path = '../../uploads/products/' . $imageRow['image_path'];
            if (file_exists($image_path)) unlink($image_path);
        }
        $imageStmt->close();

        $conn->prepare("DELETE FROM product_images WHERE product_id = ?")->execute() ?: null;
        $di = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $di->bind_param("i", $product_id); $di->execute(); $di->close();

        $dvc = $conn->prepare("DELETE pvc FROM product_variants_categories pvc INNER JOIN product_variants pv ON pvc.variant_id = pv.variant_id WHERE pv.product_id = ?");
        $dvc->bind_param("i", $product_id); $dvc->execute(); $dvc->close();

        $dv = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $dv->bind_param("i", $product_id); $dv->execute(); $dv->close();

        $dcl = $conn->prepare("DELETE FROM product_category_links WHERE product_id = ?");
        $dcl->bind_param("i", $product_id); $dcl->execute(); $dcl->close();

        $dml = $conn->prepare("DELETE FROM market_products WHERE product_id = ?");
        $dml->bind_param("i", $product_id); $dml->execute(); $dml->close();

        $sd = $conn->prepare("UPDATE products SET is_deleted = 1, deleted_at = NOW() WHERE product_id = ?");
        $sd->bind_param("i", $product_id); $sd->execute(); $sd->close();

        logActivity($conn, 'product', $product_id, 'Product deleted',
            json_encode(['product_name' => $product_name]),
            null,
            "Product '{$product_name}' (ID: {$product_id}) soft deleted",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../products.php", "Product deleted successfully.", "success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../products.php", "Failed to delete product: " . $e->getMessage(), "error");
    }
    exit();
}

// Delete Category
elseif (isset($_POST['delete_category'])) {
    $category_id = intval($_POST['category_id']);
    $conn->begin_transaction();
    try {
        $name_query = $conn->prepare("SELECT category_name FROM product_categories WHERE category_id = ?");
        $name_query->bind_param("i", $category_id);
        $name_query->execute();
        $category_name = $name_query->get_result()->fetch_assoc()['category_name'];
        $name_query->close();

        $check_sub = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE parent_id = ? AND is_active = 1");
        $check_sub->bind_param("i", $category_id);
        $check_sub->execute();
        $check_sub->bind_result($sub_count);
        $check_sub->fetch();
        $check_sub->close();
        if ($sub_count > 0) throw new Exception("Cannot delete category with subcategories. Please reassign or delete subcategories first.");

        $s1 = $conn->prepare("UPDATE product_categories SET is_active = 0 WHERE category_id = ?");
        $s1->bind_param("i", $category_id); $s1->execute(); $s1->close();

        $s2 = $conn->prepare("DELETE FROM product_category_links WHERE category_id = ?");
        $s2->bind_param("i", $category_id); $s2->execute(); $s2->close();

        $s3 = $conn->prepare("DELETE FROM product_variants_categories WHERE category_id = ?");
        $s3->bind_param("i", $category_id); $s3->execute(); $s3->close();

        logActivity($conn, 'category', $category_id, 'Category deleted',
            json_encode(['category_name' => $category_name]),
            null,
            "Category '{$category_name}' (ID: {$category_id}) soft deleted",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../category.php", "Category deleted successfully.", "success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../category.php", "Error: " . $e->getMessage(), "error");
    }
}

// Delete Blog
elseif (isset($_POST['delete_blog'])) {
    $blog_id = (int)$_POST['blog_id'];
    if ($blog_id <= 0) redirectWithMessage("../blogs.php", "Invalid blog ID.", "error");

    $conn->begin_transaction();
    try {
        $fetch = $conn->prepare("SELECT blog_title, blog_featured_image FROM blogs WHERE blog_id = ?");
        $fetch->bind_param("i", $blog_id);
        $fetch->execute();
        $blog = $fetch->get_result()->fetch_assoc();
        $fetch->close();
        if (!$blog) throw new Exception("Blog post not found.");

        $blog_title = $blog['blog_title'];
        $stmt = $conn->prepare("DELETE FROM blogs WHERE blog_id = ?");
        $stmt->bind_param("i", $blog_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete blog: " . $stmt->error);
        $stmt->close();

        if (!empty($blog['blog_featured_image'])) {
            $stored    = ltrim($blog['blog_featured_image'], '/');
            $stored    = preg_replace('#^\.\./+#', '', $stored);
            $full_path = __DIR__ . '/../../' . $stored;
            if (file_exists($full_path)) unlink($full_path);
        }

        logActivity($conn, 'blog', $blog_id, 'Blog deleted',
            json_encode(['blog_title' => $blog_title]),
            null,
            "Blog '{$blog_title}' (ID: {$blog_id}) permanently deleted",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../blogs.php", "Blog post deleted successfully!", "success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../blogs.php", "Error: " . $e->getMessage(), "error");
    }
}

// Delete Blog Image (AJAX)
elseif (isset($_POST['action']) && $_POST['action'] === 'delete_blog_image') {
    header('Content-Type: application/json');
    $blog_id = (int)$_POST['blog_id'];
    $query = $conn->prepare("SELECT blog_featured_image FROM blogs WHERE blog_id = ?");
    $query->bind_param("i", $blog_id);
    $query->execute();
    $blog = $query->get_result()->fetch_assoc();
    $query->close();
    if ($blog && !empty($blog['blog_featured_image'])) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $blog['blog_featured_image'];
        if (file_exists($file_path)) unlink($file_path);
        $update = $conn->prepare("UPDATE blogs SET blog_featured_image = NULL WHERE blog_id = ?");
        $update->bind_param("i", $blog_id);
        echo json_encode($update->execute()
            ? ['success' => true, 'message' => 'Image deleted successfully']
            : ['success' => false, 'message' => 'Failed to update database']);
        $update->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No image found']);
    }
    exit;
}

// Delete Cooking Suggestion
elseif (isset($_POST['delete_suggestion'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    if ($suggestion_id <= 0) redirectWithMessage("../cooking_suggestions.php", "Invalid suggestion ID.", "error");

    $name_query = $conn->prepare("SELECT dish_name FROM product_cooking_suggestions WHERE suggestion_id = ?");
    $name_query->bind_param("i", $suggestion_id);
    $name_query->execute();
    $dish_name = $name_query->get_result()->fetch_assoc()['dish_name'];
    $name_query->close();

    $stmt = $conn->prepare("DELETE FROM product_cooking_suggestions WHERE suggestion_id = ?");
    $stmt->bind_param("i", $suggestion_id);
    if ($stmt->execute()) {
        logActivity($conn, 'cooking_suggestion', $suggestion_id, 'Cooking suggestion deleted',
            json_encode(['dish_name' => $dish_name]),
            null,
            "Cooking suggestion '{$dish_name}' (ID: {$suggestion_id}) permanently deleted",
            $actorId, $actorType
        );
        redirectWithMessage("../cooking_suggestions.php", "Cooking suggestion deleted successfully.", "success");
    } else {
        redirectWithMessage("../cooking_suggestions.php", "Failed to delete suggestion: " . $stmt->error, "error");
    }
    $stmt->close();
}

// Delete Market
elseif (isset($_POST['delete_market'])) {
    $market_id = intval($_POST['market_id']);
    if ($market_id <= 0) redirectWithMessage("../markets.php", "Invalid market ID", "error");

    $conn->begin_transaction();
    try {
        $name_query = $conn->prepare("SELECT market_name FROM markets WHERE market_id = ?");
        $name_query->bind_param("i", $market_id);
        $name_query->execute();
        $market_name = $name_query->get_result()->fetch_assoc()['market_name'];
        $name_query->close();

        $image_query = $conn->prepare("SELECT main_image, gallery_images FROM markets WHERE market_id = ?");
        $image_query->bind_param("i", $market_id);
        $image_query->execute();
        $images = $image_query->get_result()->fetch_assoc();
        if (!empty($images['main_image'])) {
            $main_path = "../../uploads/markets/" . $images['main_image'];
            if (file_exists($main_path)) unlink($main_path);
        }
        if (!empty($images['gallery_images'])) {
            $gallery = json_decode($images['gallery_images'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) { $gp = "../../uploads/markets/" . $img; if (file_exists($gp)) unlink($gp); }
            }
        }
        $image_query->close();

        $stmt = $conn->prepare("DELETE FROM markets WHERE market_id = ?");
        $stmt->bind_param("i", $market_id);
        $stmt->execute();
        $stmt->close();

        logActivity($conn, 'market', $market_id, 'Market deleted',
            json_encode(['market_name' => $market_name]),
            null,
            "Market '{$market_name}' (ID: {$market_id}) permanently deleted",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../markets.php", "Market deleted successfully.", "success");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting market: " . $e->getMessage());
        redirectWithMessage("../markets.php", "Failed to delete market: " . $e->getMessage(), "error");
    }
}

// Delete Rider
elseif (isset($_POST['action']) && $_POST['action'] === 'delete_rider') {
    $rider_id = (int)($_POST['rider_id'] ?? 0);
    if (!$rider_id) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Missing rider ID.'];
        header('Location: ../riders.php');
        exit;
    }

    $conn->begin_transaction();
    try {
        $ca = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'");
        $ca->bind_param('i', $rider_id);
        $ca->execute();
        if ((int)$ca->get_result()->fetch_assoc()['cnt'] > 0) {
            throw new Exception('Cannot remove a rider with active deliveries. Reassign their orders first.');
        }

        // Uses renamed columns: rider_name (was full_name),
        //                       account_first_name, account_last_name
        $gi = $conn->prepare("
            SELECT r.account_id, r.vehicle_type,
                   COALESCE(r.rider_name, CONCAT(a.account_first_name, ' ', a.account_last_name)) AS display_name
            FROM riders r
            JOIN accounts a ON a.account_id = r.account_id
            WHERE r.rider_id = ? LIMIT 1
        ");
        $gi->bind_param('i', $rider_id);
        $gi->execute();
        $riderInfo = $gi->get_result()->fetch_assoc();
        if (!$riderInfo) throw new Exception('Rider not found.');

        $sd = $conn->prepare("UPDATE riders SET is_deleted = 1, is_available = 0, deleted_at = NOW(), updated_at = NOW() WHERE rider_id = ?");
        $sd->bind_param('i', $rider_id);
        if (!$sd->execute()) throw new Exception('Failed to remove rider.');

        $ur = $conn->prepare("UPDATE accounts SET role = 'guest' WHERE account_id = ?");
        $ur->bind_param('i', $riderInfo['account_id']);
        if (!$ur->execute()) throw new Exception('Failed to revert account role.');

        logActivity($conn, 'rider', $rider_id, 'Rider removed',
            json_encode(['rider_id' => $rider_id, 'name' => $riderInfo['display_name'], 'vehicle' => $riderInfo['vehicle_type']]),
            null,
            "Rider ID {$rider_id} ({$riderInfo['display_name']}) removed. Account ID {$riderInfo['account_id']} role reverted to guest.",
            $actorId, $actorType
        );

        $conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => "Rider {$riderInfo['display_name']} removed. Account reverted to guest."];
        header('Location: ../riders.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
        header('Location: ../riders.php');
        exit;
    }
}

// ==================== AJAX DELETES ====================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    header('Content-Type: application/json');

    if (isset($_POST['action']) && $_POST['action'] === 'delete_market_image') {
        $market_id = intval($_POST['market_id'] ?? 0);
        $type = $_POST['image_type'] ?? '';
        if ($market_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid market ID']); exit; }

        if ($type === 'main') {
            $query = $conn->prepare("SELECT main_image FROM markets WHERE market_id = ?");
            $query->bind_param("i", $market_id);
            $query->execute();
            $market = $query->get_result()->fetch_assoc();
            if (!empty($market['main_image'])) {
                $path = "../../uploads/markets/" . $market['main_image'];
                if (file_exists($path)) unlink($path);
                $update = $conn->prepare("UPDATE markets SET main_image = NULL WHERE market_id = ?");
                $update->bind_param("i", $market_id);
                $update->execute();
                $update->close();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No main image to delete']);
            }
            $query->close();
        } elseif ($type === 'gallery' && isset($_POST['image'])) {
            $image = $_POST['image'];
            $query = $conn->prepare("SELECT gallery_images FROM markets WHERE market_id = ?");
            $query->bind_param("i", $market_id);
            $query->execute();
            $market = $query->get_result()->fetch_assoc();
            if (!empty($market['gallery_images'])) {
                $gallery = json_decode($market['gallery_images'], true);
                if (($key = array_search($image, $gallery)) !== false) {
                    unset($gallery[$key]);
                    $gallery = array_values($gallery);
                    $path = "../../uploads/markets/" . $image;
                    if (file_exists($path)) unlink($path);
                    $gallery_json = json_encode($gallery);
                    $update = $conn->prepare("UPDATE markets SET gallery_images = ? WHERE market_id = ?");
                    $update->bind_param("si", $gallery_json, $market_id);
                    $update->execute();
                    $update->close();
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Image not found in gallery']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No gallery images']);
            }
            $query->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        }
        exit;
    }

    // Delete Market Product Link
    // Uses renamed column: market_product_id (was id)
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_market_product') {
        if (!isset($_POST['link_id'])) { echo json_encode(['success' => false, 'message' => 'No link ID provided']); exit; }
        $link_id = intval($_POST['link_id']);
        $stmt = $conn->prepare("DELETE FROM market_products WHERE market_product_id = ?");
        $stmt->bind_param("i", $link_id);
        echo json_encode($stmt->execute()
            ? ['success' => true]
            : ['success' => false, 'message' => $stmt->error]);
        $stmt->close();
        exit;
    }

    // Delete Market Member
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_market_member') {
        if (!isset($_POST['member_id'])) { echo json_encode(['success' => false, 'message' => 'No member ID provided']); exit; }
        $member_id = intval($_POST['member_id']);
        $stmt = $conn->prepare("DELETE FROM market_members WHERE member_id = ?");
        $stmt->bind_param("i", $member_id);
        echo json_encode($stmt->execute()
            ? ['success' => true]
            : ['success' => false, 'message' => $stmt->error]);
        $stmt->close();
        exit;
    }

    // Delete Product Variant (AJAX path)
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_variant') {
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

        if ($variant_id <= 0) {
            ob_clean(); // ← clear stray output
            echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE product_variants SET is_deleted = 1 WHERE variant_id = ? AND is_deleted = 0");
        $stmt->bind_param("i", $variant_id);
        $stmt->execute();

        ob_clean(); // ← clear stray output before JSON
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Variant not found or already deleted.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Variant deleted successfully']);
        }

        $stmt->close();
        exit;
    }

    // Delete Category Image
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_category_image') {
        if (!isset($_POST['category_id'])) { echo json_encode(['success' => false, 'message' => 'No category ID provided']); exit; }
        $category_id = intval($_POST['category_id']);
        $stmt = $conn->prepare("SELECT category_image FROM product_categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['category_image'])) {
                $full_path = "../../uploads/categories/" . $row['category_image'];
                if (file_exists($full_path)) unlink($full_path);
                $update = $conn->prepare("UPDATE product_categories SET category_image = NULL WHERE category_id = ?");
                $update->bind_param("i", $category_id);
                echo json_encode($update->execute()
                    ? ['success' => true]
                    : ['success' => false, 'message' => 'Database update failed']);
                $update->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'No image to delete']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid AJAX action']);
    exit;
}

else {
    redirectWithMessage("../dashboard.php", "Invalid delete request.", "error");
}

$conn->close();
?>