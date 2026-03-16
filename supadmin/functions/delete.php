<?php
session_start();
require '../../conn.php';
include 'slug_helper.php';
require_once 'activity_log_helper.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

// Get actor info for logging
['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

// ==================== FORM-BASED DELETES (with redirect) ====================

// Delete Account
if (isset($_POST['delete_account'])) {
    $account_id = $_POST['account_id'];

    $deleteQuery = "DELETE FROM accounts WHERE account_id = ?";
    $stmt = $conn->prepare($deleteQuery);
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

    if ($product_id <= 0) {
        redirectWithMessage("../products.php", "Invalid product ID", "error");
    }

    $conn->begin_transaction();

    try {
        // Get product name for logging
        $name_query = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $name_query->bind_param("i", $product_id);
        $name_query->execute();
        $product_name = $name_query->get_result()->fetch_assoc()['product_name'];
        $name_query->close();

        // Step 1: Fetch and delete product images from server
        $imageQuery = "SELECT image_path FROM product_images WHERE product_id = ?";
        $imageStmt = $conn->prepare($imageQuery);
        $imageStmt->bind_param("i", $product_id);
        $imageStmt->execute();
        $imageResult = $imageStmt->get_result();

        while ($imageRow = $imageResult->fetch_assoc()) {
            $image_path = '../../uploads/products/' . $imageRow['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $imageStmt->close();

        // Step 2: Delete product image records
        $deleteImagesStmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $deleteImagesStmt->bind_param("i", $product_id);
        $deleteImagesStmt->execute();
        $deleteImagesStmt->close();

        // Step 3: Delete variant categories for all variants of this product
        $deleteVarCatsStmt = $conn->prepare(
            "DELETE pvc FROM product_variants_categories pvc
             INNER JOIN product_variants pv ON pvc.variant_id = pv.variant_id
             WHERE pv.product_id = ?"
        );
        $deleteVarCatsStmt->bind_param("i", $product_id);
        $deleteVarCatsStmt->execute();
        $deleteVarCatsStmt->close();

        // Step 4: Delete all variants of this product
        $deleteVariantsStmt = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $deleteVariantsStmt->bind_param("i", $product_id);
        $deleteVariantsStmt->execute();
        $deleteVariantsStmt->close();

        // Step 5: Delete product category links
        $deleteCatLinksStmt = $conn->prepare("DELETE FROM product_category_links WHERE product_id = ?");
        $deleteCatLinksStmt->bind_param("i", $product_id);
        $deleteCatLinksStmt->execute();
        $deleteCatLinksStmt->close();

        // Step 6: Delete market product links
        $deleteMarketLinksStmt = $conn->prepare("DELETE FROM market_products WHERE product_id = ?");
        $deleteMarketLinksStmt->bind_param("i", $product_id);
        $deleteMarketLinksStmt->execute();
        $deleteMarketLinksStmt->close();

        // Step 7: Soft delete the product
        $softDeleteStmt = $conn->prepare("UPDATE products SET is_deleted = 1, deleted_at = NOW() WHERE product_id = ?");
        $softDeleteStmt->bind_param("i", $product_id);
        $softDeleteStmt->execute();
        $softDeleteStmt->close();

        // Log activity
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

// Delete Product Variant
elseif (isset($_POST['action']) && $_POST['action'] === 'delete_variant') {
    $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

    if ($variant_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE product_variants SET is_deleted = 1 WHERE variant_id = ?");
    $stmt->bind_param("i", $variant_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Variant deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete Category
elseif (isset($_POST['delete_category'])) {
    $category_id = intval($_POST['category_id']);
    
    $conn->begin_transaction();
    
    try {
        // Get category name for logging
        $name_query = $conn->prepare("SELECT category_name FROM product_categories WHERE category_id = ?");
        $name_query->bind_param("i", $category_id);
        $name_query->execute();
        $category_name = $name_query->get_result()->fetch_assoc()['category_name'];
        $name_query->close();

        // Check if category has subcategories
        $check_sub = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE parent_id = ? AND is_active = 1");
        $check_sub->bind_param("i", $category_id);
        $check_sub->execute();
        $check_sub->bind_result($sub_count);
        $check_sub->fetch();
        $check_sub->close();
        
        if ($sub_count > 0) {
            throw new Exception("Cannot delete category with subcategories. Please reassign or delete subcategories first.");
        }
        
        // Soft delete the category
        $stmt = $conn->prepare("UPDATE product_categories SET is_active = 0 WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();
        
        // Remove category links from products
        $link_stmt = $conn->prepare("DELETE FROM product_category_links WHERE category_id = ?");
        $link_stmt->bind_param("i", $category_id);
        $link_stmt->execute();
        $link_stmt->close();
        
        // Remove variant category links
        $var_link_stmt = $conn->prepare("DELETE FROM product_variants_categories WHERE category_id = ?");
        $var_link_stmt->bind_param("i", $category_id);
        $var_link_stmt->execute();
        $var_link_stmt->close();

        // Log activity
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

elseif (isset($_POST['delete_blog'])) {
    $blog_id = (int)$_POST['blog_id'];

    if ($blog_id <= 0) {
        redirectWithMessage("../blogs.php", "Invalid blog ID.", "error");
    }

    $conn->begin_transaction();

    try {
        // Fetch blog title AND image path from DB (don't trust POST for file path)
        $fetch = $conn->prepare("SELECT blog_title, blog_featured_image FROM blogs WHERE blog_id = ?");
        $fetch->bind_param("i", $blog_id);
        $fetch->execute();
        $blog = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        if (!$blog) {
            throw new Exception("Blog post not found.");
        }

        $blog_title = $blog['blog_title'];

        // Delete the blog row
        $stmt = $conn->prepare("DELETE FROM blogs WHERE blog_id = ?");
        $stmt->bind_param("i", $blog_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete blog: " . $stmt->error);
        }
        $stmt->close();

        // Delete image file from uploads/blogs/ if it exists
        if (!empty($blog['blog_featured_image'])) {
            $stored    = ltrim($blog['blog_featured_image'], '/');
            $stored    = preg_replace('#^\.\./+#', '', $stored);
            $full_path = __DIR__ . '/../../' . $stored;

            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }

        // Log activity
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

elseif (isset($_POST['action']) && $_POST['action'] === 'delete_blog_image') {
    header('Content-Type: application/json');
    
    $blog_id = (int)$_POST['blog_id'];
    
    // Get the image path
    $query = $conn->prepare("SELECT blog_featured_image FROM blogs WHERE blog_id = ?");
    $query->bind_param("i", $blog_id);
    $query->execute();
    $result = $query->get_result();
    $blog = $result->fetch_assoc();
    $query->close();
    
    if ($blog && !empty($blog['blog_featured_image'])) {
        // Delete the file
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $blog['blog_featured_image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Update database to remove image reference
        $update = $conn->prepare("UPDATE blogs SET blog_featured_image = NULL WHERE blog_id = ?");
        $update->bind_param("i", $blog_id);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
        $update->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No image found']);
    }
    exit;
}

// Delete Cooking Suggestion
elseif (isset($_POST['delete_suggestion'])) {
    $suggestion_id = intval($_POST['suggestion_id']);

    if ($suggestion_id <= 0) {
        redirectWithMessage("../cooking_suggestions.php", "Invalid suggestion ID.", "error");
    }

    // Get suggestion details for logging
    $name_query = $conn->prepare("SELECT dish_name FROM product_cooking_suggestions WHERE suggestion_id = ?");
    $name_query->bind_param("i", $suggestion_id);
    $name_query->execute();
    $dish_name = $name_query->get_result()->fetch_assoc()['dish_name'];
    $name_query->close();

    $stmt = $conn->prepare("DELETE FROM product_cooking_suggestions WHERE suggestion_id = ?");
    $stmt->bind_param("i", $suggestion_id);

    if ($stmt->execute()) {
        // Log activity
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
    
    if ($market_id <= 0) {
        redirectWithMessage("../markets.php", "Invalid market ID", "error");
    }
    
    $conn->begin_transaction();
    
    try {
        // Get market name for logging
        $name_query = $conn->prepare("SELECT market_name FROM markets WHERE market_id = ?");
        $name_query->bind_param("i", $market_id);
        $name_query->execute();
        $market_name = $name_query->get_result()->fetch_assoc()['market_name'];
        $name_query->close();
        
        // Delete market images from server
        $image_query = $conn->prepare("SELECT main_image, gallery_images FROM markets WHERE market_id = ?");
        $image_query->bind_param("i", $market_id);
        $image_query->execute();
        $images = $image_query->get_result()->fetch_assoc();
        
        if (!empty($images['main_image'])) {
            $main_path = "../../uploads/markets/" . $images['main_image'];
            if (file_exists($main_path)) {
                unlink($main_path);
            }
        }
        
        if (!empty($images['gallery_images'])) {
            $gallery = json_decode($images['gallery_images'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) {
                    $gallery_path = "../../uploads/markets/" . $img;
                    if (file_exists($gallery_path)) {
                        unlink($gallery_path);
                    }
                }
            }
        }
        $image_query->close();
        
        // Delete market (cascade will delete members and product links)
        $stmt = $conn->prepare("DELETE FROM markets WHERE market_id = ?");
        $stmt->bind_param("i", $market_id);
        $stmt->execute();
        $stmt->close();
        
        // Log activity
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

elseif (isset($_POST['action']) && $_POST['action'] === 'delete_rider') {

    $rider_id = (int)($_POST['rider_id'] ?? 0);
    if (!$rider_id) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Missing rider ID.'];
        header('Location: ../riders.php');
        exit;
    }

    $conn->begin_transaction();
    try {
        // Block removal if rider has active deliveries
        $ca = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'");
        $ca->bind_param('i', $rider_id);
        $ca->execute();
        if ((int)$ca->get_result()->fetch_assoc()['cnt'] > 0) {
            throw new Exception('Cannot remove a rider with active deliveries. Reassign their orders first.');
        }

        // Get rider info for log + account role revert
        $gi = $conn->prepare("
            SELECT r.account_id, r.vehicle_type,
                   COALESCE(r.full_name, CONCAT(a.first_name, ' ', a.last_name)) AS display_name
            FROM riders r
            JOIN accounts a ON a.account_id = r.account_id
            WHERE r.rider_id = ? LIMIT 1
        ");
        $gi->bind_param('i', $rider_id);
        $gi->execute();
        $riderInfo = $gi->get_result()->fetch_assoc();
        if (!$riderInfo) throw new Exception('Rider not found.');

        // Soft-delete — preserves delivery history
        $sd = $conn->prepare("UPDATE riders SET is_deleted = 1, is_available = 0, deleted_at = NOW(), updated_at = NOW() WHERE rider_id = ?");
        $sd->bind_param('i', $rider_id);
        if (!$sd->execute()) throw new Exception('Failed to remove rider.');

        // Revert account role to guest
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

// ==================== AJAX DELETES (JSON response) ====================

// Handle AJAX requests
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    // Delete Market Image (main or gallery)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_market_image') {
        $market_id = intval($_POST['market_id'] ?? 0);
        $type = $_POST['image_type'] ?? '';
        
        if ($market_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid market ID']);
            exit;
        }

        if ($type === 'main') {
            // Get current main image
            $query = $conn->prepare("SELECT main_image FROM markets WHERE market_id = ?");
            $query->bind_param("i", $market_id);
            $query->execute();
            $result = $query->get_result();
            $market = $result->fetch_assoc();
            
            if (!empty($market['main_image'])) {
                $path = "../../uploads/markets/" . $market['main_image'];
                if (file_exists($path)) {
                    unlink($path);
                }
                
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
            
            // Get current gallery
            $query = $conn->prepare("SELECT gallery_images FROM markets WHERE market_id = ?");
            $query->bind_param("i", $market_id);
            $query->execute();
            $result = $query->get_result();
            $market = $result->fetch_assoc();
            
            if (!empty($market['gallery_images'])) {
                $gallery = json_decode($market['gallery_images'], true);
                if (($key = array_search($image, $gallery)) !== false) {
                    unset($gallery[$key]);
                    $gallery = array_values($gallery); // Reindex
                    
                    $path = "../../uploads/markets/" . $image;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                    
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
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_market_product') {
        if (!isset($_POST['link_id'])) {
            echo json_encode(['success' => false, 'message' => 'No link ID provided']);
            exit;
        }

        $link_id = intval($_POST['link_id']);
        
        $stmt = $conn->prepare("DELETE FROM market_products WHERE id = ?");
        $stmt->bind_param("i", $link_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    
    // Delete Market Member
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_market_member') {
        if (!isset($_POST['member_id'])) {
            echo json_encode(['success' => false, 'message' => 'No member ID provided']);
            exit;
        }

        $member_id = intval($_POST['member_id']);
        
        $stmt = $conn->prepare("DELETE FROM market_members WHERE member_id = ?");
        $stmt->bind_param("i", $member_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    
    // Delete Product Variant
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_variant') {
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

        if ($variant_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
            exit;
        }

        $conn->begin_transaction();

        try {
            // Step 1: Delete variant categories first (FK constraint)
            $stmt = $conn->prepare("DELETE FROM product_variants_categories WHERE variant_id = ?");
            $stmt->bind_param("i", $variant_id);
            $stmt->execute();
            $stmt->close();

            // Step 2: Delete the variant itself
            $stmt = $conn->prepare("DELETE FROM product_variants WHERE variant_id = ?");
            $stmt->bind_param("i", $variant_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Variant not found or already deleted.");
            }
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Variant deleted successfully']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Delete Category Image
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_category_image') {
        if (!isset($_POST['category_id'])) {
            echo json_encode(['success' => false, 'message' => 'No category ID provided']);
            exit;
        }
        
        $category_id = intval($_POST['category_id']);
        
        // Get image path
        $query = "SELECT category_image FROM product_categories WHERE category_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $image_path = $row['category_image'];
            
            if (!empty($image_path)) {
                $full_path = "../../uploads/categories/" . $image_path;
                
                // Delete file if exists
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
                
                // Update database to remove image reference
                $update = "UPDATE product_categories SET category_image = NULL WHERE category_id = ?";
                $update_stmt = $conn->prepare($update);
                $update_stmt->bind_param("i", $category_id);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }
                $update_stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'No image to delete']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
        
        $stmt->close();
        exit;
    }
    
    // If no action matched
    echo json_encode(['success' => false, 'message' => 'Invalid AJAX action']);
    exit;
}

// If no valid action found
else {
    redirectWithMessage("../dashboard.php", "Invalid delete request.", "error");
}

$conn->close();
?>