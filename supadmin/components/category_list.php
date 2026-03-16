<?php
// category_list.php - redesigned with products.php design language

$searchCat = $_GET['search_cat'] ?? '';

// Stats
$catStats = [];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE is_active=1"); 
$catStats['active'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE parent_id IS NULL AND is_active=1"); 
$catStats['top'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE parent_id IS NOT NULL AND is_active=1"); 
$catStats['sub'] = (int)$r->fetch_assoc()['v'];
?>

<!-- Stats Cards - matching products.php style -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <!-- Total Active -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Total Active</p>
        <p class="text-2xl font-bold text-gray-900 mt-1"><?= $catStats['active'] ?></p>
        <p class="text-xs text-gray-400 mt-1">categories</p>
      </div>
      <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 text-xl">📁</div>
    </div>
  </div>
  
  <!-- Top Level -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Top Level</p>
        <p class="text-2xl font-bold text-blue-600 mt-1"><?= $catStats['top'] ?></p>
        <p class="text-xs text-gray-400 mt-1">parent categories</p>
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl">📂</div>
    </div>
  </div>
  
  <!-- Subcategories -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Subcategories</p>
        <p class="text-2xl font-bold text-purple-600 mt-1"><?= $catStats['sub'] ?></p>
        <p class="text-xs text-gray-400 mt-1">child categories</p>
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 text-xl">📄</div>
    </div>
  </div>
</div>

<!-- Main Card -->
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
  
  <!-- Header with search and add button -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
    <div>
      <h2 class="text-xl font-bold text-gray-900">Categories</h2>
      <p class="text-sm text-gray-500 mt-0.5">
        <span class="font-semibold text-gray-800"><?= $totalItems ?></span> total categories
      </p>
    </div>
    
    <div class="flex gap-2">
      <form method="GET" class="relative">
        <input type="text" name="search_cat" value="<?= htmlspecialchars($searchCat) ?>" 
               placeholder="Search categories..." 
               class="ps-9 pe-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
        <svg class="absolute ms-3 left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
      </form>
      
      <button class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm"
              data-modal-target="addCategoryModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M5 12h14"/><path d="M12 5v14"/>
        </svg>
        Add Category
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Level</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Parent</th>
          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Products</th>
          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Sort</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        <?php 
        $result->data_seek(0); // Reset pointer
        if ($result->num_rows === 0): 
        ?>
        <tr>
          <td colspan="6" class="px-6 py-16 text-center">
            <div class="flex flex-col items-center gap-3">
              <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                  <path d="M4 4h6v6h-6z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6h-6z"/><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/>
                </svg>
              </div>
              <p class="text-sm font-semibold text-gray-700">No categories yet</p>
              <p class="text-xs text-gray-400">Click "Add Category" to get started</p>
            </div>
          </td>
        </tr>
        <?php else: while ($row = $result->fetch_assoc()):
          $levelColors = [
            1 => ['badge-orange', 'Main'],
            2 => ['badge-blue', 'Sub'],
            3 => ['badge-purple', 'Sub-sub']
          ];
          [$levelBadge, $levelLabel] = $levelColors[$row['category_level']] ?? ['badge-gray', 'Level '.$row['category_level']];
          $indent = str_repeat('— ', ($row['category_level'] - 1));
        ?>
        <tr class="category-row hover:bg-orange-50/40 transition-colors">
          <!-- Category name + image -->
          <td class="px-6 py-4">
            <div class="flex items-center gap-3">
              <?php if ($row['category_image']): ?>
              <img src="../uploads/categories/<?= htmlspecialchars($row['category_image']) ?>" 
                   class="w-9 h-9 rounded-lg object-cover border border-gray-100" alt="">
              <?php else: ?>
              <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path d="M3 7h18M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7M8 5v2M16 5v2"/>
                </svg>
              </div>
              <?php endif; ?>
              <div>
                <div class="text-sm font-semibold text-gray-900"><?= $indent . htmlspecialchars($row['category_name']) ?></div>
                <?php if ($row['category_description']): ?>
                <div class="text-xs text-gray-400 max-w-[200px] truncate"><?= htmlspecialchars($row['category_description']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          
          <!-- Level -->
          <td class="px-4 py-4">
            <span class="badge <?= $levelBadge ?>"><?= $levelLabel ?></span>
          </td>
          
          <!-- Parent -->
          <td class="px-4 py-4">
            <span class="text-sm text-gray-600"><?= htmlspecialchars($row['parent_name'] ?? '—') ?></span>
          </td>
          
          <!-- Product count -->
          <td class="px-4 py-4 text-center">
            <span class="text-sm font-bold text-gray-900"><?= (int)$row['product_count'] ?></span>
            <span class="text-xs text-gray-400 ml-1">(<?= $row['variant_count'] ?> var.)</span>
          </td>
          
          <!-- Sort order -->
          <td class="px-4 py-4 text-center">
            <span class="text-xs text-gray-500"><?= $row['sort_order'] ?></span>
          </td>
          
          <!-- Actions -->
          <td class="px-4 py-4 text-right">
            <div class="flex items-center justify-end gap-1.5">
              <button onclick="openEditCategoryModal(<?= $row['category_id'] ?>)"
                      class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
              </button>
              <button onclick="openDeleteCategoryModal(<?= $row['category_id'] ?>, '<?= htmlspecialchars(addslashes($row['category_name'])) ?>')"
                      class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination (matching products.php style) -->
  <?php if ($totalPages > 1): ?>
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
    <p class="text-sm text-gray-500">
      Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span> 
      of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> categories
    </p>
    
    <div class="flex items-center gap-1.5">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?><?= $searchCat ? '&search_cat='.urlencode($searchCat) : '' ?>" 
           class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
        </a>
      <?php else: ?>
        <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
        </span>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      $queryParam = $searchCat ? '&search_cat='.urlencode($searchCat) : '';
      
      if ($start > 1) {
        echo '<a href="?page=1'.$queryParam.'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
      }
      if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
      
      for ($i = $start; $i <= $end; $i++):
      ?>
        <a href="?page=<?= $i ?><?= $queryParam ?>" 
           class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
           <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
          <?= $i ?>
        </a>
      <?php
      endfor;
      
      if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
      if ($end < $totalPages) {
        echo '<a href="?page='.$totalPages.$queryParam.'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">'.$totalPages.'</a>';
      }
      ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?><?= $queryParam ?>" 
           class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      <?php else: ?>
        <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Delete Category Modal (redesigned to match products.php delete modal) -->
<div id="deleteCategoryModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:28rem">
    <div class="modal-header">
      <div>
        <h3>Delete Category</h3>
        <p>This action cannot be undone</p>
      </div>
      <button class="modal-close" onclick="closeModal('deleteCategoryModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>
    
    <div class="modal-body text-center">
      <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        </svg>
      </div>
      
      <form action="./functions/delete.php" method="POST" id="deleteCategoryForm">
        <input type="hidden" name="category_id" id="deleteCategoryId">
        <p id="deleteCategoryName" class="text-sm font-semibold text-gray-800 mb-1"></p>
        <p class="text-xs text-red-500 mb-5">
          This will remove the category from all products and variants.
        </p>
        <div class="flex gap-3 justify-center">
          <button type="button" onclick="closeModal('deleteCategoryModal')" class="btn-secondary">Cancel</button>
          <button type="submit" name="delete_category" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Category Modal (redesigned) -->
<div id="editCategoryModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div>
        <h3>Edit Category</h3>
        <p>Update category details below</p>
      </div>
      <button class="modal-close" onclick="closeModal('editCategoryModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>
    
    <div id="editCategoryContent" class="modal-body">
      <div class="flex items-center justify-center py-12 text-gray-400">
        <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Loading category data...
      </div>
    </div>
  </div>
</div>

<script>
// Delete modal functions
window.openDeleteCategoryModal = function(categoryId, categoryName) {
  document.getElementById('deleteCategoryId').value = categoryId;
  document.getElementById('deleteCategoryName').textContent = `Are you sure you want to delete "${categoryName}"?`;
  document.getElementById('deleteCategoryModal').classList.remove('hidden');
};

// Edit modal functions
window.openEditCategoryModal = function(categoryId) {
  const modal = document.getElementById('editCategoryModal');
  const content = document.getElementById('editCategoryContent');
  
  content.innerHTML = `
    <div class="flex items-center justify-center py-12 text-gray-400">
      <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
      </svg>
      Loading category data...
    </div>`;
  modal.classList.remove('hidden');
  
  fetch('./functions/fetch_category.php?category_id=' + categoryId)
    .then(r => r.text())
    .then(html => {
      content.innerHTML = html;
    })
    .catch(() => {
      content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load category.</p>';
    });
};

// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) closeModal(this.id);
  });
});
</script>