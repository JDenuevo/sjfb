<div class="space-y-4">

  <!-- Header -->
  <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Products</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> total products
        </p>
      </div>
      <button class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm"
              data-modal-target="addProductModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M5 12h14"/><path d="M12 5v14"/>
        </svg>
        Add Product
      </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead>
          <tr class="bg-gray-50">
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Categories</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Variants</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Prices</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Last Updated</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="product-row hover:bg-orange-50/40 transition-colors">

              <!-- Product Name + Unit -->
              <td class="px-6 py-4">
                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($row['product_name']) ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($row['product_unit'] ?? '') ?></p>
              </td>

              <!-- Categories -->
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <?php
                  $cats = array_filter(explode(', ', $row['category_names'] ?? ''));
                  if (!empty($cats)):
                    foreach ($cats as $cat):
                  ?>
                    <span class="badge badge-gray"><?= htmlspecialchars($cat) ?></span>
                  <?php
                    endforeach;
                  else:
                  ?>
                    <span class="text-xs text-gray-400">—</span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Stock Status -->
              <td class="px-6 py-4">
                <?php if ($row['stock_status'] > 0): ?>
                  <span class="badge badge-green">
                    <svg class="mr-1" width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                    In Stock
                  </span>
                <?php else: ?>
                  <span class="badge badge-red">
                    <svg class="mr-1" width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                    Out of Stock
                  </span>
                <?php endif; ?>
              </td>

              <!-- Variants -->
              <td class="px-6 py-4">
                <p class="text-sm text-gray-700"><?= !empty($row['variants']) ? htmlspecialchars($row['variants']) : '<span class="text-gray-400">—</span>' ?></p>
              </td>

              <!-- Prices -->
              <td class="px-6 py-4">
                <p class="text-sm font-medium text-gray-800"><?= !empty($row['prices']) ? '₱' . htmlspecialchars($row['prices']) : '<span class="text-gray-400">—</span>' ?></p>
              </td>

              <!-- Last Updated -->
              <td class="px-6 py-4">
                <p class="text-xs text-gray-500"><?= $row['last_updated'] ? date("M j, Y", strtotime($row['last_updated'])) : '—' ?></p>
                <p class="text-xs text-gray-400"><?= $row['last_updated'] ? date("g:i a", strtotime($row['last_updated'])) : '' ?></p>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-1.5">
                  <button onclick="openEditModal(<?= $row['product_id'] ?>)"
                          class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                    </svg>
                  </button>
                  <button onclick="openDeleteModal(<?= $row['product_id'] ?>, '<?= htmlspecialchars(addslashes($row['product_name'])) ?>')"
                          class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                      <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                      <line x1="3" y1="6" x2="21" y2="6"/>
                      <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                  </div>
                  <p class="text-sm font-semibold text-gray-700">No products yet</p>
                  <p class="text-xs text-gray-400">Click "Add Product" to get started</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
      <p class="text-sm text-gray-500">
        Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span> of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> products
      </p>
      <div class="flex items-center gap-1.5">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
          </a>
        <?php else: ?>
          <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
          </span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1) echo '<a href="?page=1" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
        if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
        for ($i = $start; $i <= $end; $i++):
        ?>
          <a href="?page=<?= $i ?>" class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
            <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
            <?= $i ?>
          </a>
        <?php
        endfor;
        if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
        if ($end < $totalPages) echo '<a href="?page=' . $totalPages . '" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">' . $totalPages . '</a>';
        ?>

        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
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
</div>