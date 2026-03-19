
<!-- fetch_products.php -->
<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    header("Location: ../index.php");
    exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) {
    echo '<p class="text-red-500 p-4">Invalid product ID.</p>';
    exit;
}

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Product not found.</div>';
    exit;
}

// Fetch product categories
$cat_stmt = $conn->prepare("SELECT pcl.category_id, pcl.is_primary, pc.category_name
    FROM product_category_links pcl
    JOIN product_categories pc ON pcl.category_id = pc.category_id
    WHERE pcl.product_id = ?");
$cat_stmt->bind_param("i", $product_id);
$cat_stmt->execute();
$cat_res = $cat_stmt->get_result();
$selected_categories = [];
$primary_category = 0;
while ($cat = $cat_res->fetch_assoc()) {
    $selected_categories[] = $cat['category_id'];
    if ($cat['is_primary'] == 1) $primary_category = $cat['category_id'];
}
$cat_stmt->close();

// Fetch variants
$var_stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_deleted = 0 ORDER BY variant_id");
$var_stmt->bind_param("i", $product_id);
$var_stmt->execute();
$variants_result = $var_stmt->get_result();
$variants = [];
while ($v = $variants_result->fetch_assoc()) {
    $variants[] = $v;
}
$var_stmt->close();

// Fetch variant categories for all variants at once
$variant_categories_map = [];
if (!empty($variants)) {
    $variant_ids = array_column($variants, 'variant_id');
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
    $types = str_repeat('i', count($variant_ids));
    $vc_stmt = $conn->prepare("SELECT variant_id, category_id FROM product_variants_categories WHERE variant_id IN ($placeholders)");
    $vc_stmt->bind_param($types, ...$variant_ids);
    $vc_stmt->execute();
    $vc_res = $vc_stmt->get_result();
    while ($vc = $vc_res->fetch_assoc()) {
        $variant_categories_map[$vc['variant_id']][] = $vc['category_id'];
    }
    $vc_stmt->close();
}

// Fetch images
$img_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$img_stmt->bind_param("i", $product_id);
$img_stmt->execute();
$images = $img_stmt->get_result();
$img_stmt->close();

// All categories
$all_cats_result = $conn->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_level, category_name");
$all_cats = [];
while ($c = $all_cats_result->fetch_assoc()) {
    $all_cats[] = $c;
}
?>

<form id="editProductForm" action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-1">
  <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
  <input type="hidden" name="deleted_images" id="deletedImages" value="">

  <p class="section-title">Basic Information</p>
  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="form-label">Product Name <span class="text-red-500">*</span></label>
      <input type="text" name="product_name" class="form-input"
             value="<?= htmlspecialchars($product['product_name'] ?? '') ?>" required>
    </div>
    <div>
      <label class="form-label">Product Unit</label>
      <input type="text" name="product_unit" class="form-input"
             value="<?= htmlspecialchars($product['product_unit'] ?? '') ?>">
    </div>
  </div>

  <div class="mt-3">
    <label class="form-label">Description</label>
    <textarea name="product_description" rows="2" class="form-input" style="resize:none"><?= htmlspecialchars($product['product_description'] ?? '') ?></textarea>
  </div>

  <div class="mt-3">
    <label class="form-label">Nickname / Tags</label>
    <input type="text" name="product_nickname" class="form-input"
           value="<?= htmlspecialchars($product['product_nickname'] ?? '') ?>">
  </div>

  <p class="section-title">Product Categories <span class="text-red-500">*</span></p>
  <div class="space-y-3">
    <div>
      <label class="form-label">Select Categories (you can select multiple)</label>
      <select name="product_categories[]" multiple class="form-input" size="5" required>
        <?php foreach ($all_cats as $c): ?>
          <option value="<?= $c['category_id'] ?>" 
            <?= in_array($c['category_id'], $selected_categories) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple categories</p>
    </div>

    <div>
      <label class="form-label">Primary Category</label>
      <select name="primary_category" class="form-input">
        <option value="0">— None —</option>
        <?php foreach ($all_cats as $c): ?>
          <option value="<?= $c['category_id'] ?>" <?= $c['category_id'] == $primary_category ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="text-xs text-gray-400 mt-1">Optional: If not selected, first category will be primary</p>
    </div>
  </div>

  <p class="section-title">Variants</p>
  <div class="updateVariantContainer space-y-2">
    <?php if (!empty($variants)): ?>
      <?php foreach ($variants as $v):
        $v_cats = $variant_categories_map[$v['variant_id']] ?? [];
      ?>
      <div class="variant-row" data-variant-id="<?= $v['variant_id'] ?>">
        <input type="hidden" name="variant_id[]" value="<?= $v['variant_id'] ?>">
        <div class="grid grid-cols-4 gap-2">
          <div>
            <label class="form-label">Size / Name <span class="text-red-500">*</span></label>
            <input type="text" name="variant_name[]" class="form-input"
                   value="<?= htmlspecialchars($v['variant_name']) ?>" required>
          </div>
          <div>
            <label class="form-label">Unit <span class="text-red-500">*</span></label>
            <select name="unit_type[]" class="form-input" required>
              <option value="kg"    <?= $v['unit_type'] == 'kg'    ? 'selected' : '' ?>>Kilogram</option>
              <option value="piece" <?= $v['unit_type'] == 'piece' ? 'selected' : '' ?>>Piece</option>
              <option value="gram"  <?= $v['unit_type'] == 'gram'  ? 'selected' : '' ?>>Gram</option>
              <option value="pack"  <?= $v['unit_type'] == 'pack'  ? 'selected' : '' ?>>Pack</option>
              <option value="box"  <?= $v['unit_type'] == 'box'  ? 'selected' : '' ?>>Box</option>
              <option value="banyera"  <?= $v['unit_type'] == 'banyera'  ? 'selected' : '' ?>>Banyera</option>
              <option value="sack"  <?= $v['unit_type'] == 'sack'  ? 'selected' : '' ?>>Sack</option>
              <option value="tray"  <?= $v['unit_type'] == 'tray'  ? 'selected' : '' ?>>Tray</option>
            </select>
          </div>
          <div>
            <label class="form-label">Price <span class="text-red-500">*</span></label>
            <input type="number" name="variant_price[]" class="form-input"
                   value="<?= $v['variant_price'] ?>" step="0.01" min="0" required>
          </div>
          <div>
            <label class="form-label">Discount</label>
            <input type="number" name="discount_price[]" class="form-input"
                   value="<?= $v['discount_price'] ?>" step="0.01" min="0">
          </div>
          <div>
            <label class="form-label">Min Order <span class="text-red-500">*</span></label>
            <input type="number" name="minimum_order[]" class="form-input"
                   value="<?= $v['minimum_order'] ?>" step="0.01" min="0.01" required>
          </div>
          <div>
            <label class="form-label">Increment <span class="text-red-500">*</span></label>
            <input type="number" name="order_increment[]" class="form-input"
                   value="<?= $v['order_increment'] ?>" step="0.01" min="0.01" required>
          </div>
          <div>
            <label class="form-label">Stock <span class="text-red-500">*</span></label>
            <input type="number" name="stock_quantity[]" class="form-input"
                   value="<?= $v['stock_quantity'] ?>" min="0" required>
          </div>
          <div>
            <label class="form-label">Action</label>
            <!-- data-variant-id on button triggers AJAX DELETE in initEditModal() -->
            <button type="button"
                    class="btn-danger removeEditVariant"
                    data-variant-id="<?= $v['variant_id'] ?>">
              🗑 Remove
            </button>
          </div>
        </div>

        <!-- Variant Categories — keyed by variant_id so update.php can map them -->
        <div class="mt-2">
          <label class="form-label">Variant Categories</label>
          <select name="variant_categories[<?= $v['variant_id'] ?>][]" multiple class="form-input text-sm" size="2">
            <option value="">Inherit from product</option>
            <?php foreach ($all_cats as $c): ?>
              <option value="<?= $c['category_id'] ?>"
                      <?= in_array($c['category_id'], $v_cats) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-400 mt-1">Leave empty to inherit product categories</p>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-gray-400 text-sm" id="noVariantsMsg">No variants yet. Add one below.</p>
    <?php endif; ?>
  </div>

  <div class="flex justify-end mt-2">
    <button type="button" class="btn-success addEditVariantBtn">+ Add Variant</button>
  </div>

  <p class="section-title">Current Images</p>
  <div id="currentImagesContainer" class="grid grid-cols-5 gap-2">
    <?php if ($images && $images->num_rows > 0): ?>
      <?php while ($img = $images->fetch_assoc()): ?>
      <div class="image-thumb current-image" data-image-id="<?= $img['image_id'] ?>">
        <img src="../uploads/products/<?= htmlspecialchars($img['image_path']) ?>" alt="">
        <button type="button" class="del-btn delete-image-btn">×</button>
        <?php if ($img['is_primary'] == 1): ?>
          <span style="position:absolute;bottom:4px;left:4px;background:#3b82f6;color:white;font-size:0.6rem;padding:2px 6px;border-radius:9999px">Primary</span>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-gray-400 text-sm col-span-5">No images yet</p>
    <?php endif; ?>
  </div>

  <p class="section-title">Add New Images</p>
  <div>
    <input type="file" id="newImageInput" name="product_images[]" multiple class="hidden" accept="image/*">
    <button type="button" onclick="document.getElementById('newImageInput').click()"
            class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
      📸 Click to add more images (max 5 total)
    </button>
    <div id="newImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
  </div>

  <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;border-top:1px solid #f3f4f6;background:#fafafa;padding:1rem 1.5rem;display:flex;justify-content:flex-end;gap:0.625rem;">
    <button type="button" onclick="closeModal('editProductModal')" class="btn-secondary">Cancel</button>
    <button type="submit" name="update_product" class="btn-primary">Save Changes</button>
  </div>
</form>