<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    header("Location: ../index.php");
    exit;
}

$product_id = $_GET['product_id'];

// Fetch product details
$query = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">Product not found.</div>';
    exit;
}

// Fetch product's categories from product_category_links
$category_query = "SELECT pcl.category_id, pcl.is_primary, pc.category_name, pc.category_level 
                  FROM product_category_links pcl
                  JOIN product_categories pc ON pcl.category_id = pc.category_id
                  WHERE pcl.product_id = ?";
$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("i", $product_id);
$category_stmt->execute();
$product_categories = $category_stmt->get_result();
$selected_categories = [];
$primary_category = 0;

while ($cat = $product_categories->fetch_assoc()) {
    $selected_categories[] = $cat['category_id'];
    if ($cat['is_primary'] == 1) {
        $primary_category = $cat['category_id'];
    }
}
$category_stmt->close();

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

// Fetch all active categories for dropdown
$all_categories = $conn->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_level, category_name");
?>

<h3 class="text-xl font-semibold mb-4 text-gray-800">Update Product</h3>
                  
<form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
    <input type="hidden" name="deleted_variants" id="deletedVariants" value="">
    <input type="hidden" name="deleted_images" id="deletedImages" value="">

    <div class="grid grid-cols-2 gap-4">
        <!-- Product Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Product Name</label>
            <input type="text" name="product_name" class="w-full px-3 py-2 border rounded-lg" 
                  value="<?= htmlspecialchars($product['product_name'] ?? '') ?>" required>
        </div>

        <!-- Product Unit -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Product Unit</label>
            <input type="text" name="product_unit" class="w-full px-3 py-2 border rounded-lg" 
                   value="<?= htmlspecialchars($product['product_unit'] ?? '') ?>">
        </div>
    </div>

    <!-- Product Description -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="product_description" rows="3" 
                  class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($product['product_description'] ?? '') ?></textarea>
    </div>

    <!-- Product Nickname -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Nickname/Tags</label>
        <input type="text" name="product_nickname" class="w-full px-3 py-2 border rounded-lg" 
                   value="<?= htmlspecialchars($product['product_nickname'] ?? '') ?>">
    </div>

    <!-- Categories Section -->
    <div class="space-y-3">
        <h4 class="font-semibold text-lg text-gray-800">Categories</h4>
        
        <!-- Selected Categories Display -->
        <div class="border rounded-lg p-4 bg-gray-50">
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Categories</label>
            <div id="selectedCategoriesList" class="flex flex-wrap gap-2 mb-3">
                <?php 
                if (!empty($selected_categories)):
                    $placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
                    $cat_stmt = $conn->prepare("SELECT * FROM product_categories WHERE category_id IN ($placeholders)");
                    $cat_stmt->bind_param(str_repeat('i', count($selected_categories)), ...$selected_categories);
                    $cat_stmt->execute();
                    $selected_cat_details = $cat_stmt->get_result();
                    while ($cat = $selected_cat_details->fetch_assoc()): 
                ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 <?= ($cat['category_id'] == $primary_category) ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-blue-100 text-blue-800 border-blue-300' ?> rounded-full text-sm border">
                        <?= htmlspecialchars($cat['category_name']) ?>
                        <?php if ($cat['category_id'] == $primary_category): ?>
                            <span class="ml-1 text-xs font-bold">(Primary)</span>
                        <?php endif; ?>
                        <button type="button" onclick="removeCategory(<?= $cat['category_id'] ?>)" class="ml-1 text-gray-500 hover:text-red-600">
                            ×
                        </button>
                    </span>
                <?php 
                    endwhile;
                    $cat_stmt->close();
                else: 
                ?>
                    <p class="text-gray-500 text-sm">No categories selected</p>
                <?php endif; ?>
            </div>
            
            <!-- Add Category Dropdown -->
            <div class="flex gap-2">
                <select id="addCategorySelect" class="flex-1 px-3 py-2 border rounded-lg text-sm">
                    <option value="">-- Add Category --</option>
                    <?php 
                    if ($all_categories && $all_categories->num_rows > 0):
                        $all_categories->data_seek(0);
                        while ($cat = $all_categories->fetch_assoc()): 
                            if (!in_array($cat['category_id'], $selected_categories)):
                                $indent = str_repeat('— ', max(0, $cat['category_level'] - 1));
                    ?>
                        <option value="<?= $cat['category_id'] ?>">
                            <?= $indent . htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php 
                            endif;
                        endwhile; 
                    endif;
                    ?>
                </select>
                <button type="button" onclick="addCategory()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm whitespace-nowrap">
                    Add Category
                </button>
            </div>
            
            <!-- Hidden inputs for categories - THESE ARE CRITICAL -->
            <div id="categoryInputs">
                <?php foreach ($selected_categories as $cat_id): ?>
                    <input type="hidden" name="product_categories[]" value="<?= $cat_id ?>">
                <?php endforeach; ?>
                <input type="hidden" name="primary_category" id="primaryCategoryInput" value="<?= $primary_category ?>">
            </div>
            
            <!-- Set Primary Button -->
            <?php if (count($selected_categories) > 1): ?>
                <div class="mt-3 text-sm" id="setPrimaryContainer">
                    <button type="button" onclick="enablePrimarySelection()" class="text-orange-600 hover:text-orange-800 font-medium">
                        Set Primary Category
                    </button>
                    <p class="text-xs text-gray-500 mt-1">Click to choose which category should be primary</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Variants Section -->
    <h4 class="font-semibold text-lg text-gray-800">Variants</h4>

    <div class="updateVariantContainer space-y-4">
        <?php if ($variants && $variants->num_rows > 0): ?>
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
                    <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition">
                        🗑 Delete
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 text-sm">No variants yet. Add your first variant below.</p>
        <?php endif; ?>
    </div>

    <!-- Add Variant Button -->
    <div class="flex justify-end mt-3">
        <button type="button" style="background-color: #22c55e;" class="addVariant py-2 px-3 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
            + Add Variant
        </button>
    </div>

    <!-- Current Images -->
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Current Product Images</label>
        <div id="currentImagesContainer" class="grid grid-cols-5 gap-2">
            <?php if ($images && $images->num_rows > 0): ?>
                <?php while ($image = $images->fetch_assoc()): ?>
                <div class="relative group current-image" data-image-id="<?= $image['image_id'] ?>">
                    <img src="../uploads/products/<?= htmlspecialchars($image['image_path']) ?>" 
                         class="w-full h-24 object-cover rounded-lg shadow">
                    <button type="button" class="delete-image-btn absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition opacity-0 group-hover:opacity-100">
                        ×
                    </button>
                    <?php if ($image['is_primary'] == 1): ?>
                        <span class="absolute bottom-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">Primary</span>
                    <?php endif; ?>
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
        <div class="flex items-center gap-3">
            <input type="file" id="newImageInput" name="product_images[]" multiple class="hidden" accept="image/*">
            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition" 
                    onclick="document.getElementById('newImageInput').click()">
                📸 Select Images
            </button>
            <span class="text-xs text-gray-500">Maximum 5 images total, up to 5MB each</span>
        </div>
    </div>

    <!-- New Images Preview -->
    <div id="newImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
        <button type="submit" name="update_product" 
                class="py-2 px-6 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-medium">
            Update Product
        </button>
        <button type="button" onclick="closeModal('editProductModal')" 
                class="py-2 px-6 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium">
            Cancel
        </button>
    </div>
</form>


<script>
    // Initialize arrays with PHP data
    let selectedCategoryIds = <?= json_encode($selected_categories) ?>;
    let primaryCategoryId = <?= $primary_category ?>;

    // Add category function
    function addCategory() {
        const select = document.getElementById('addCategorySelect');
        if (!select.value) {
            alert('Please select a category');
            return;
        }
        
        const categoryId = parseInt(select.value);
        const categoryName = select.options[select.selectedIndex].text;
        
        // Check if already added
        if (selectedCategoryIds.includes(categoryId)) {
            alert('Category already added');
            return;
        }
        
        // Add to array
        selectedCategoryIds.push(categoryId);
        
        // Add to display
        addCategoryToDisplay(categoryId, categoryName, false);
        
        // Add hidden input
        addHiddenInput(categoryId);
        
        // Remove from dropdown
        select.remove(select.selectedIndex);
        
        // Remove "No categories selected" message if it exists
        const noCategoriesMsg = document.querySelector('#selectedCategoriesList p.text-gray-500');
        if (noCategoriesMsg) {
            noCategoriesMsg.remove();
        }
        
        // Show set primary button if needed
        updateSetPrimaryButton();
    }

    // Add category to display
    function addCategoryToDisplay(categoryId, categoryName, isPrimary) {
        const list = document.getElementById('selectedCategoriesList');
        const span = document.createElement('span');
        span.className = `inline-flex items-center gap-1 px-3 py-1 ${isPrimary ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-blue-100 text-blue-800 border-blue-300'} rounded-full text-sm border`;
        span.dataset.categoryId = categoryId;
        span.innerHTML = `
            ${categoryName}
            ${isPrimary ? '<span class="ml-1 text-xs font-bold">(Primary)</span>' : ''}
            <button type="button" onclick="removeCategory(${categoryId})" class="ml-1 text-gray-500 hover:text-red-600">
                ×
            </button>
        `;
        list.appendChild(span);
    }

    // Add hidden input
    function addHiddenInput(categoryId) {
        const container = document.getElementById('categoryInputs');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_categories[]';
        input.value = categoryId;
        container.appendChild(input);
    }

    // Remove category
    function removeCategory(categoryId) {
        // Remove from array
        const index = selectedCategoryIds.indexOf(categoryId);
        if (index > -1) {
            selectedCategoryIds.splice(index, 1);
        }
        
        // Remove from display
        const span = document.querySelector(`span[data-category-id="${categoryId}"]`);
        if (span) {
            span.remove();
        }
        
        // Remove hidden input
        const hiddenInput = document.querySelector(`input[name="product_categories[]"][value="${categoryId}"]`);
        if (hiddenInput) {
            hiddenInput.remove();
        }
        
        // Add back to dropdown
        addBackToDropdown(categoryId);
        
        // If this was primary, reset primary
        if (primaryCategoryId == categoryId) {
            primaryCategoryId = 0;
            document.getElementById('primaryCategoryInput').value = '';
        }
        
        // Show "No categories selected" message if list is empty
        if (selectedCategoryIds.length === 0) {
            const list = document.getElementById('selectedCategoriesList');
            list.innerHTML = '<p class="text-gray-500 text-sm">No categories selected</p>';
        }
        
        // Update set primary button
        updateSetPrimaryButton();
    }

    // Add category back to dropdown
    function addBackToDropdown(categoryId) {
        const select = document.getElementById('addCategorySelect');
        
        // Get category name from the span
        const span = document.querySelector(`span[data-category-id="${categoryId}"]`);
        if (!span) return;
        
        let categoryName = '';
        for (let i = 0; i < span.childNodes.length; i++) {
            if (span.childNodes[i].nodeType === 3) { // Text node
                categoryName = span.childNodes[i].nodeValue.trim();
                break;
            }
        }
        
        const option = document.createElement('option');
        option.value = categoryId;
        option.textContent = categoryName;
        select.appendChild(option);
        
        // Sort options alphabetically
        const options = Array.from(select.options);
        const firstOption = options.shift(); // Remove "-- Add Category --"
        options.sort((a, b) => a.text.localeCompare(b.text));
        
        select.innerHTML = '';
        select.appendChild(firstOption);
        options.forEach(opt => select.appendChild(opt));
    }

    // Enable primary category selection mode
    function enablePrimarySelection() {
        const categories = document.querySelectorAll('#selectedCategoriesList span');
        if (categories.length < 2) return;
        
        // Remove existing click handlers and add new ones
        categories.forEach(span => {
            span.classList.add('cursor-pointer', 'hover:bg-orange-100', 'border-2', 'border-dashed', 'border-orange-300');
            span.style.cursor = 'pointer';
            
            span.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const categoryId = parseInt(this.dataset.categoryId);
                
                // Update primary category
                primaryCategoryId = categoryId;
                document.getElementById('primaryCategoryInput').value = categoryId;
                
                // Update visual styling for all categories
                categories.forEach(s => {
                    s.className = 'inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm border border-blue-300';
                    s.classList.remove('cursor-pointer', 'hover:bg-orange-100', 'border-2', 'border-dashed', 'border-orange-300');
                    s.onclick = null;
                    s.style.cursor = 'default';
                    
                    // Remove primary label
                    const primaryLabel = s.querySelector('span.text-xs.font-bold');
                    if (primaryLabel) {
                        primaryLabel.remove();
                    }
                });
                
                // Mark selected as primary
                this.className = 'inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm border border-orange-300';
                
                // Add primary label
                if (!this.innerHTML.includes('(Primary)')) {
                    this.innerHTML += ' <span class="ml-1 text-xs font-bold">(Primary)</span>';
                }
            };
        });
    }

    // Update set primary button visibility
    function updateSetPrimaryButton() {
        const setPrimaryContainer = document.getElementById('setPrimaryContainer');
        
        if (selectedCategoryIds.length > 1) {
            if (!setPrimaryContainer) {
                const container = document.querySelector('.border.rounded-lg.p-4.bg-gray-50');
                const div = document.createElement('div');
                div.id = 'setPrimaryContainer';
                div.className = 'mt-3 text-sm';
                div.innerHTML = `
                    <button type="button" onclick="enablePrimarySelection()" class="text-orange-600 hover:text-orange-800 font-medium">
                        Set Primary Category
                    </button>
                    <p class="text-xs text-gray-500 mt-1">Click to choose which category should be primary</p>
                `;
                container.appendChild(div);
            }
        } else {
            if (setPrimaryContainer) {
                setPrimaryContainer.remove();
            }
        }
    }

    // ============================================
    // VARIANT MANAGEMENT (keep your existing code)
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Add variant
        const addVariantBtn = document.querySelector('.addVariant');
        if (addVariantBtn) {
            addVariantBtn.addEventListener('click', function() {
                const container = document.querySelector('.updateVariantContainer');
                const variantHTML = `...`; // Your existing variant HTML
                container.insertAdjacentHTML('beforeend', variantHTML);
            });
        }
        
        // Remove variant
        document.querySelector('.updateVariantContainer')?.addEventListener('click', function(e) {
            if (e.target.classList.contains('removeVariant')) {
                const variantRow = e.target.closest('.variantRow');
                const variantId = variantRow.dataset.variantId;
                
                if (variantId) {
                    const deletedInput = document.getElementById('deletedVariants');
                    const currentDeleted = deletedInput.value ? deletedInput.value.split(',') : [];
                    currentDeleted.push(variantId);
                    deletedInput.value = currentDeleted.join(',');
                }
                
                variantRow.remove();
            }
        });
        
        // Image preview
        const newImageInput = document.getElementById('newImageInput');
        const newImagePreview = document.getElementById('newImagePreview');
        let selectedFiles = [];
        
        if (newImageInput && newImagePreview) {
            newImageInput.addEventListener('change', function(e) {
                const newFiles = Array.from(e.target.files);
                const currentImageCount = document.querySelectorAll('.current-image').length;
                
                if (currentImageCount + selectedFiles.length + newFiles.length > 5) {
                    alert('Maximum 5 images allowed total');
                    return;
                }
                
                selectedFiles.push(...newFiles);
                updateNewImagePreview();
            });
            
            function updateNewImagePreview() {
                newImagePreview.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative group';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg shadow">
                            <button type="button" class="remove-new-image absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition" data-index="${index}">
                                ×
                            </button>
                        `;
                        newImagePreview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
                
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => dataTransfer.items.add(file));
                newImageInput.files = dataTransfer.files;
            }
            
            newImagePreview.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-new-image')) {
                    const index = parseInt(e.target.dataset.index);
                    selectedFiles.splice(index, 1);
                    updateNewImagePreview();
                }
            });
        }
        
        // Delete existing image
        document.querySelectorAll('.delete-image-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const imageDiv = this.closest('.current-image');
                const imageId = imageDiv.dataset.imageId;
                
                if (confirm('Delete this image?')) {
                    const deletedInput = document.getElementById('deletedImages');
                    const currentDeleted = deletedInput.value ? deletedInput.value.split(',') : [];
                    currentDeleted.push(imageId);
                    deletedInput.value = currentDeleted.join(',');
                    imageDiv.remove();
                }
            });
        });
    });
</script>