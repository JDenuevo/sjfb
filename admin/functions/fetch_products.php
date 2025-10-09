<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinasadmin"]) || $_SESSION["loggedinasadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$product_id = $_GET['product_id'];

// Fetch product details
$query = "SELECT p.*, c.category_id, c.category_name 
          FROM products p
          LEFT JOIN product_categories c ON p.product_category = c.category_id
          WHERE p.product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

// Fetch variants
$variant_query = "SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id";
$variant_stmt = $conn->prepare($variant_query);
$variant_stmt->bind_param("i", $product_id);
$variant_stmt->execute();
$variants = $variant_stmt->get_result();
$variant_stmt->close();

// Fetch images
$image_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
$image_stmt = $conn->prepare($image_query);
$image_stmt->bind_param("i", $product_id);
$image_stmt->execute();
$images = $image_stmt->get_result();
$image_stmt->close();

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM product_categories ORDER BY category_name");
?>

<h3 class="text-xl font-semibold mb-4 text-gray-800">Update Product</h3>
                  
<form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
    <input type="hidden" name="deleted_variants" id="deletedVariants" value="">
    <input type="hidden" name="deleted_images" id="deletedImages" value="">

    <!-- Product Name -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Product Name</label>
        <input type="text" name="product_name" class="w-full px-3 py-2 border rounded-lg" 
               value="<?= htmlspecialchars($product['product_name']) ?>" required>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <!-- Product Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Product Description</label>
            <input type="text" name="product_description" class="w-full px-3 py-2 border rounded-lg" 
                   value="<?= htmlspecialchars($product['product_description']) ?>" required>
        </div>

        <!-- Product Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Category</label>
            <select name="product_category" required class="w-full px-3 py-2 border rounded-lg">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category_id'] ?>" 
                            <?= $cat['category_id'] == $product['product_category'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <!-- Variants Section -->
    <h4 class="font-semibold text-lg text-gray-800">Variants</h4>

    <div class="updateVariantContainer">
        <?php while ($variant = $variants->fetch_assoc()): ?>
        <div class="grid grid-cols-4 gap-2 py-2 pb-4 border-b variantRow" data-variant-id="<?= $variant['variant_id'] ?>">
            <input type="hidden" name="variant_id[]" value="<?= $variant['variant_id'] ?>">
            
            <div>
                <label class="block text-xs font-medium text-gray-700">Size</label>
                <input type="text" name="variant_name[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= htmlspecialchars($variant['variant_name']) ?>" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Unit</label>
                <select name="unit_type[]" class="w-full px-3 py-2 border rounded-lg text-sm" required>
                    <option value="piece" <?= $variant['unit_type'] == 'piece' ? 'selected' : '' ?>>Piece</option>
                    <option value="kg" <?= $variant['unit_type'] == 'kg' ? 'selected' : '' ?>>Kilogram</option>
                    <option value="gram" <?= $variant['unit_type'] == 'gram' ? 'selected' : '' ?>>Gram</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Price</label>
                <input type="number" name="variant_price[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= $variant['variant_price'] ?>" step="0.01" min="0" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Discount</label>
                <input type="number" name="discount_price[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= $variant['discount_price'] ?>" step="0.01" min="0">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Min Order</label>
                <input type="number" name="minimum_order[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= $variant['minimum_order'] ?>" step="0.01" min="0.01" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Increment</label>
                <input type="number" name="order_increment[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= $variant['order_increment'] ?>" step="0.01" min="0.01" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Stock</label>
                <input type="number" name="stock_quantity[]" class="w-full px-3 py-2 border rounded-lg text-sm" 
                       value="<?= $variant['stock_quantity'] ?>" min="0" required>
            </div>

            <!-- Delete Variant Button -->
            <div>
                <label class="block text-xs font-medium text-gray-700">Action</label>
                <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white text-sm rounded-lg">
                🗑 Delete
                </button>
            </div>

            
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Add Variant Button -->
    <div class="flex justify-end mt-3">
        <button type="button" style="background-color: #22c55e;" class="addVariant py-2 px-3 text-sm font-medium rounded-lg bg-orange-500 text-white hover:bg-green-600">
            + Add Variant
        </button>
    </div>

    <!-- Current Images -->
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Current Product Images</label>
        <div id="currentImagesContainer" class="grid grid-cols-5 gap-2">
            <?php if ($images->num_rows > 0): ?>
                <?php while ($image = $images->fetch_assoc()): ?>
                <div class="relative group current-image" data-image-id="<?= $image['image_id'] ?>">
                    <img src="../uploads/products/<?= htmlspecialchars($image['image_path']) ?>" 
                         class="w-full h-24 object-cover rounded-lg shadow">
                    <button type="button" class="delete-image-btn absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                        ×
                    </button>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-500 text-sm col-span-5">No images yet</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload New Images -->
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700">Add New Images</label>
        <input type="file" id="newImageInput" name="product_images[]" multiple class="hidden" accept="image/*">
        <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" 
                onclick="document.getElementById('newImageInput').click()">
            Select Images
        </button>
        <p class="text-xs text-gray-500 mt-1">You can select up to 5 images total</p>
    </div>

    <!-- New Images Preview -->
    <div id="newImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 mt-4">
        <button type="submit" name="update_product" 
                class="py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            Update Product
        </button>
        <button type="button" onclick="closeModal('editProductModal')" 
                class="py-2 px-4 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
            Cancel
        </button>
    </div>
</form>