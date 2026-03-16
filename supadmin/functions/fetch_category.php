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

<form id="editCategoryForm" action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
  <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
  
  <p class="section-title">Basic Information</p>
  
  <div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
      <label class="form-label">Category Name <span class="text-red-500">*</span></label>
      <input type="text" name="category_name" required 
             value="<?= htmlspecialchars($category['category_name']) ?>"
             class="form-input">
    </div>
    
    <div class="col-span-2">
      <label class="form-label">Slug (URL-friendly name)</label>
      <input type="text" name="category_slug" 
             value="<?= htmlspecialchars($category['category_slug']) ?>"
             class="form-input"
             placeholder="e.g., fresh-fish">
    </div>
    
    <div class="col-span-2">
      <label class="form-label">Description</label>
      <textarea name="category_description" rows="3" 
                class="form-input" style="resize:none"><?= htmlspecialchars($category['category_description']) ?></textarea>
    </div>
  </div>
  
  <p class="section-title">Category Settings</p>
  
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="form-label">Parent Category</label>
      <select name="parent_id" class="form-input">
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
    
    <div>
      <label class="form-label">Sort Order</label>
      <input type="number" name="sort_order" value="<?= $category['sort_order'] ?? 0 ?>" min="0"
             class="form-input">
    </div>
    
    <div>
      <label class="form-label">Status</label>
      <select name="is_active" class="form-input">
        <option value="1" <?= $category['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= $category['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
  </div>
  
  <p class="section-title">Category Image</p>
  
  <?php if ($category['category_image']): ?>
  <div>
    <label class="form-label">Current Image</label>
    <div class="relative inline-block">
      <img src="../uploads/categories/<?= htmlspecialchars($category['category_image']) ?>" 
           class="w-24 h-24 object-cover rounded-lg border border-gray-200">
          <button type="button" data-delete-category-image="<?= $category['category_id'] ?>"
                  class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
            ×
          </button>
    </div>
  </div>
  <?php endif; ?>
  
  <div>
    <label class="form-label">Change Image</label>
    <input type="file" id="editCategoryImage" name="category_image" accept="image/*" class="hidden">
    <button type="button" onclick="document.getElementById('editCategoryImage').click()"
            class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
      📸 Click to upload new image
    </button>
    <div id="editCategoryImagePreview" class="hidden mt-3">
      <img src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border">
    </div>
  </div>
  
  <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
    <button type="button" onclick="closeModal('editCategoryModal')" class="btn-secondary">Cancel</button>
    <button type="submit" name="update_category" class="btn-primary">Update Category</button>
  </div>
</form>