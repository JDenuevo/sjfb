<?php
// category_list.php — improved
$searchCat = $_GET['search_cat'] ?? '';

// Stats
$catStats = [];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE is_active=1"); $catStats['active'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE parent_id IS NULL AND is_active=1"); $catStats['top'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM product_categories WHERE parent_id IS NOT NULL AND is_active=1"); $catStats['sub'] = (int)$r->fetch_assoc()['v'];
?>

<!-- Stats strip -->
<div class="grid grid-cols-3 gap-3 mb-4">
  <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-orange-700"><?= $catStats['active'] ?></div>
    <div class="text-xs text-orange-600">Total Active</div>
  </div>
  <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-blue-700"><?= $catStats['top'] ?></div>
    <div class="text-xs text-blue-600">Top Level</div>
  </div>
  <div class="bg-purple-50 border border-purple-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-purple-700"><?= $catStats['sub'] ?></div>
    <div class="text-xs text-purple-600">Subcategories</div>
  </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
  <!-- Header -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
    <div class="flex-1">
      <h2 class="text-lg font-semibold text-gray-800">Categories</h2>
      <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> categories</p>
    </div>
    <div class="flex gap-2">
      <form method="GET">
        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
          <input type="text" name="search_cat" value="<?= htmlspecialchars($searchCat) ?>" placeholder="Search categories…" class="text-sm px-3 py-2 focus:outline-none w-40">
          <button type="submit" class="px-3 py-2 text-orange-500 hover:bg-orange-50">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>
        </div>
      </form>
      <button type="button" data-modal-target="addCategoryModal"
        class="flex items-center gap-x-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Add Category
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Level</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Parent</th>
          <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</th>
          <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Sort</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php while ($row = $result->fetch_assoc()):
          $levelConf = [1=>['bg-orange-100 text-orange-700','Main'],2=>['bg-blue-100 text-blue-700','Sub'],3=>['bg-purple-100 text-purple-700','Sub-sub']];
          [$lBadge, $lLabel] = $levelConf[$row['category_level']] ?? ['bg-gray-100 text-gray-700','Level '.$row['category_level']];
          $indent = str_repeat('— ', ($row['category_level']-1));
        ?>
        <tr class="category-row hover:bg-orange-50/30 transition-colors">
          <!-- Category name + image -->
          <td class="px-6 py-3">
            <div class="flex items-center gap-3">
              <?php if ($row['category_image']): ?>
              <img src="../uploads/categories/<?= htmlspecialchars($row['category_image']) ?>" class="size-9 rounded-lg object-cover border border-gray-100" alt="">
              <?php else: ?>
              <div class="size-9 rounded-lg bg-orange-100 flex items-center justify-center">
                <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6h-6z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6h-6z"/><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0-6 0"/></svg>
              </div>
              <?php endif; ?>
              <div>
                <div class="text-sm font-semibold text-gray-800"><?= $indent . htmlspecialchars($row['category_name']) ?></div>
                <?php if ($row['category_description']): ?>
                <div class="text-xs text-gray-400 max-w-[200px] truncate"><?= htmlspecialchars($row['category_description']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <!-- Level -->
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $lBadge ?>"><?= $lLabel ?></span>
          </td>
          <!-- Parent -->
          <td class="px-4 py-3">
            <span class="text-sm text-gray-600"><?= htmlspecialchars($row['parent_name'] ?? '—') ?></span>
          </td>
          <!-- Product count -->
          <td class="px-4 py-3 text-center">
            <span class="text-sm font-bold text-gray-800"><?= (int)$row['product_count'] ?></span>
          </td>
          <!-- Sort order -->
          <td class="px-4 py-3 text-center">
            <span class="text-xs text-gray-500"><?= $row['sort_order'] ?></span>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right">
            <div class="inline-flex gap-1">
              <button type="button" onclick="openEditCategoryModal(<?= $row['category_id'] ?>)"
                class="size-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button type="button" data-modal-target="deleteCategoryModal<?= $row['category_id'] ?>"
                class="size-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Delete">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              </button>
            </div>
          </td>
        </tr>

        <!-- Delete Modal -->
        <div id="deleteCategoryModal<?= $row['category_id'] ?>" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 hidden p-4">
          <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="size-10 bg-red-100 rounded-xl flex items-center justify-center">
                <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-semibold text-gray-800">Delete Category</h3>
                <p class="text-xs text-gray-500">This cannot be undone.</p>
              </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Delete <strong><?= htmlspecialchars($row['category_name']) ?></strong>? Products linked to this category will need to be reassigned.</p>
            <form action="./functions/delete.php" method="POST">
              <input type="hidden" name="category_id" value="<?= $row['category_id'] ?>">
              <div class="flex gap-2">
                <button type="button" onclick="closeModal('deleteCategoryModal<?= $row['category_id'] ?>')" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" name="delete_category" class="flex-1 px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg">Delete</button>
              </div>
            </form>
          </div>
        </div>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> categories</p>
    <div class="flex gap-1">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
      <?php endif; ?>
      <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
           class="px-3 py-1.5 text-xs border rounded-lg <?= $i==$page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Edit Category Modal (AJAX-loaded) -->
<div id="editCategoryModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 hidden p-4">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800">Edit Category</h3>
      <button onclick="closeModal('editCategoryModal')" class="text-gray-400 hover:text-gray-600">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <div id="editCategoryContent" class="p-6">
      <div class="flex items-center justify-center py-8">
        <div class="size-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    </div>
  </div>
</div>

<script>
function openEditCategoryModal(categoryId) {
  const modal = document.getElementById('editCategoryModal');
  const content = document.getElementById('editCategoryContent');
  modal.classList.remove('hidden');
  content.innerHTML = '<div class="flex items-center justify-center py-8"><div class="size-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div></div>';
  fetch('./functions/fetch_category.php?category_id=' + categoryId)
    .then(r => r.text())
    .then(html => { content.innerHTML = html; })
    .catch(() => { content.innerHTML = '<p class="text-red-500 text-sm">Failed to load.</p>'; });
}
document.getElementById('editCategoryModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeModal('editCategoryModal');
});
document.querySelectorAll('[data-modal-target]').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById(this.getAttribute('data-modal-target'))?.classList.remove('hidden');
  });
});
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }
</script>