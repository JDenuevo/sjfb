<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    header("Location: ../index.php");
    exit;
}

$category_id = intval($_GET['category_id']);

// Fetch category details
$query = "SELECT * FROM product_categories WHERE category_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch all categories for parent dropdown
$categories = $conn->query("SELECT category_id, category_name, category_level FROM product_categories WHERE is_active = 1 ORDER BY category_level, category_name");
?>

<h3 class="text-xl font-semibold mb-4 text-gray-800">Edit Category</h3>

<form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
    
    <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Category Name *</label>
            <input type="text" name="category_name" required 
                   value="<?= htmlspecialchars($category['category_name']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>
        
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" name="category_slug" 
                   value="<?= htmlspecialchars($category['category_slug']) ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>
        
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Parent Category</label>
            <select name="parent_id" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">— No Parent (Top Level) —</option>
                <?php while ($parent = $categories->fetch_assoc()): 
                    if ($parent['category_id'] == $category['category_id']) continue;
                    $indent = str_repeat('— ', $parent['category_level'] - 1);
                ?>
                    <option value="<?= $parent['category_id'] ?>" <?= $parent['category_id'] == $category['parent_id'] ? 'selected' : '' ?>>
                        <?= $indent . htmlspecialchars($parent['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="category_description" rows="3" 
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"><?= htmlspecialchars($category['category_description']) ?></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
            <input type="number" name="sort_order" value="<?= $category['sort_order'] ?? 0 ?>" min="0"
                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="is_active" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="1" <?= $category['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $category['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>
    
    <?php if ($category['category_image']): ?>
    <div class="mt-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
        <div class="relative inline-block">
            <img src="../uploads/categories/<?= htmlspecialchars($category['category_image']) ?>" 
                 class="w-32 h-32 object-cover rounded-lg border">
            <button type="button" onclick="deleteCategoryImage(<?= $category['category_id'] ?>)" 
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                ×
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <div>
        <label class="block text-sm font-medium text-gray-700">Change Image</label>
        <input type="file" name="category_image" accept="image/*" class="hidden" id="editCategoryImage">
        <button type="button" onclick="document.getElementById('editCategoryImage').click()"
                class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            📸 Upload New Image
        </button>
    </div>
    
    <div id="editCategoryImagePreview" class="hidden mt-2">
        <img src="" alt="Preview" class="w-32 h-32 object-cover rounded-lg border">
    </div>
    
    <div class="flex justify-end space-x-3 mt-6">
        <button type="submit" name="update_category" 
                class="py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
            Update Category
        </button>
        <button type="button" onclick="closeModal('editCategoryModal')" 
                class="py-2 px-4 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
            Cancel
        </button>
    </div>
</form>

<script>
    document.getElementById('editCategoryImage')?.addEventListener('change', function(e) {
        const preview = document.getElementById('editCategoryImagePreview');
        const img = preview.querySelector('img');
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    window.deleteCategoryImage = function(categoryId) {
        if (confirm('Delete this image?')) {
            fetch('./functions/delete_category_image.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'category_id=' + categoryId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete image');
                }
            });
        }
    };
</script>