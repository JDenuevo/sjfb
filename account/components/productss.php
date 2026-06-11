<!-- accounts/components/products.php -->
<!-- JS handled by: cart_process.js (cart ops) + product_process.js (variants, search, add-to-cart, share) -->
<div class="mx-auto">

  <div class="flex items-center my-5 gap-2">

    <!-- Mobile Filter Button -->
    <button type="button" id="openFilterCanvas"
      class="md:hidden flex-shrink-0 flex items-center gap-2 py-3 px-4 rounded-full bg-white border border-gray-200 text-gray-600 text-sm font-medium shadow-sm hover:border-orange-400 hover:text-orange-600 transition-all duration-200">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
      <?php
        // Count active filters for badge (slug-based now)
        $activeSlugs = array_filter(
          isset($_GET['category']) ? explode(',', $_GET['category']) : [],
          fn($c) => $c !== 'all' && $c !== ''
        );
        $activeBadge = count($activeSlugs) + (!empty($_GET['price']) ? 1 : 0);
      ?>
      <?php if ($activeBadge > 0): ?>
      <span class="bg-orange-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center leading-none"><?= $activeBadge ?></span>
      <?php endif; ?>
    </button>

    <!-- Search input (plain div, no form — search is AJAX only) -->
    <div class="relative flex-1 min-w-0">
      <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center ps-4 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
      </span>
      <input type="text" id="searchInput"
        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
        class="py-3 ps-10 pe-10 block w-full rounded-full text-sm border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition"
        placeholder="What would you like?" autocomplete="off"/>
      <button type="button" id="clearSearch"
        class="absolute inset-y-0 right-0 flex items-center pe-4 text-gray-400 hover:text-gray-600 transition-colors <?= empty($_GET['search']) ? 'hidden' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
      </button>
      <div id="autocompleteResults" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-lg border border-gray-100 hidden"></div>
    </div>

    <!-- Search AJAX trigger button -->
    <button type="button" id="searchSubmitBtn"
      class="cursor-pointer flex-shrink-0 p-3 rounded-3xl inline-flex items-center justify-center bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
    </button>
  </div>

</div>

<?php
// ─── Shared sidebar data ──────────────────────────────────────────────────────
// Selected slugs (not IDs) from URL
$selected_slugs = isset($_GET['category']) ? array_filter(explode(',', $_GET['category']), fn($c) => $c !== 'all' && $c !== '') : [];
$selected_price = isset($_GET['price']) ? $_GET['price'] : '';

$priceOptions = [
  'under200' => 'Under ₱200',
  '200-400'  => '₱200 - ₱400',
  '400-600'  => '₱400 - ₱600',
  'over600'  => 'Over ₱600',
];

// Total product count
$total_query  = "SELECT COUNT(DISTINCT p.product_id) as total 
                 FROM products p 
                 JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0
                 WHERE p.is_deleted = 0 AND pv.stock_quantity > 0";
$total_result = $conn->query($total_query);
$total_count  = $total_result ? $total_result->fetch_assoc()['total'] : 0;

// Fetch all parent categories + their subcategories
$categories = [];
$cat_res = $conn->query(
  "SELECT pc.category_id, pc.category_name, pc.category_slug,
          COUNT(DISTINCT pcl.product_id) AS product_count
   FROM product_categories pc
   LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
   LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
   LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0 AND pv.stock_quantity > 0
   WHERE pc.parent_id IS NULL AND pc.is_active = 1
   GROUP BY pc.category_id
   ORDER BY pc.sort_order ASC, pc.category_name ASC"
);
if ($cat_res) {
  while ($row = $cat_res->fetch_assoc()) {
    $row['subs'] = [];
    $sub_stmt = $conn->prepare(
      "SELECT pc.category_id, pc.category_name, pc.category_slug,
              COUNT(DISTINCT pcl.product_id) AS product_count
       FROM product_categories pc
       LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
       LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0
       LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0 AND pv.stock_quantity > 0
       WHERE pc.parent_id = ? AND pc.is_active = 1
       GROUP BY pc.category_id ORDER BY pc.sort_order ASC, pc.category_name ASC"
    );
    $sub_stmt->bind_param('i', $row['category_id']);
    $sub_stmt->execute();
    $sub_res = $sub_stmt->get_result();
    if ($sub_res) while ($s = $sub_res->fetch_assoc()) $row['subs'][] = $s;
    $sub_stmt->close();
    $categories[] = $row;
  }
}

// Helper: is a category slug currently selected?
function isCatSlugSelected(string $slug, array $selectedSlugs): bool {
  return in_array($slug, $selectedSlugs);
}
?>

<!-- ═══════════════════════════════════════════
     MOBILE FILTER OFFCANVAS
════════════════════════════════════════════ -->
<div id="fcBackdrop" onclick="closeFilterCanvas()"
  style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9998;"></div>

<div id="filterCanvas"
  style="display:none; position:fixed; top:0; left:0; width:300px; max-width:85vw; height:100dvh;
         background:#fff; z-index:9999; flex-direction:column;
         box-shadow: 4px 0 24px rgba(0,0,0,0.15);
         transform:translateX(-100%); transition:transform 0.3s ease;"
  class="flex md:hidden">

  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
    <div class="flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-600"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
      <h2 class="text-base font-semibold text-gray-900">Filters</h2>
    </div>
    <button type="button" onclick="closeFilterCanvas()" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-500 transition">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
    </button>
  </div>

  <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6">
    <!-- Offcanvas Categories -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        Categories
      </h3>
      <div class="space-y-1">
        <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
          <span class="cat-cb-wrap">
            <input type="checkbox" class="fc-cat" value="all" data-category-slug="all"
                   <?= empty($selected_slugs) ? 'checked' : '' ?>>
            <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
          </span>
          <span class="text-sm text-gray-700 flex-1">All Products</span>
          <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $total_count ?></span>
        </label>
        <?php foreach ($categories as $cat): ?>
        <div>
          <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
            <span class="cat-cb-wrap">
              <input type="checkbox" class="fc-cat" value="<?= htmlspecialchars($cat['category_slug']) ?>"
                     data-category-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                     <?= isCatSlugSelected($cat['category_slug'], $selected_slugs) ? 'checked' : '' ?>>
              <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
            </span>
            <span class="text-sm text-gray-700 flex-1"><?= htmlspecialchars($cat['category_name']) ?></span>
            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $cat['product_count'] ?></span>
          </label>
          <?php if (!empty($cat['subs'])): ?>
          <div class="cat-subs-group space-y-1">
            <?php foreach ($cat['subs'] as $sub): ?>
            <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
              <span class="cat-cb-wrap">
                <input type="checkbox" class="fc-cat" value="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-category-slug="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-parent-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                       <?= isCatSlugSelected($sub['category_slug'], $selected_slugs) ? 'checked' : '' ?>>
                <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
              </span>
              <span class="text-sm text-gray-600 flex-1"><?= htmlspecialchars($sub['category_name']) ?></span>
              <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $sub['product_count'] ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Offcanvas Price Range -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Price Range
      </h3>
      <div class="space-y-1">
        <?php foreach ($priceOptions as $val => $label): ?>
        <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
          <span class="cat-radio-wrap">
            <input type="radio" name="fc_price" value="<?= $val ?>" class="fc-price"
                   <?= $selected_price === $val ? 'checked' : '' ?>>
            <span class="cat-radio-box"></span>
          </span>
          <span class="text-sm text-gray-700"><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 flex gap-3">
    <button type="button" id="fcClear"
      class="flex-1 py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition flex items-center justify-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      Clear
    </button>
    <button type="button" id="fcApply"
      class="flex-1 py-2.5 px-4 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-xl transition flex items-center justify-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
      Apply
    </button>
  </div>
</div>
<!-- END OFFCANVAS -->


<!-- Main Content Grid: Sidebar + Products -->
<div class="grid md:grid-cols-4 gap-8">

  <!-- ── Desktop Sidebar ── -->
  <div class="md:col-span-1 hidden md:block">
    <aside class="space-y-6 sticky top-4">

      <!-- Categories -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 me-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          Categories
        </h3>
        <div class="space-y-2">
          <!-- All Products -->
          <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
            <span class="cat-cb-wrap">
              <input type="checkbox" class="category-filter" value="all" data-category-slug="all"
                     <?= empty($selected_slugs) ? 'checked' : '' ?> onchange="handleCategoryChange(this)">
              <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
            </span>
            <span class="text-sm text-gray-700 flex-1">All Products</span>
            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $total_count ?></span>
          </label>

          <?php foreach ($categories as $cat): ?>
          <div class="category-group">
            <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
              <span class="cat-cb-wrap">
                <input type="checkbox" class="category-filter" value="<?= htmlspecialchars($cat['category_slug']) ?>"
                       data-category-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                       <?= isCatSlugSelected($cat['category_slug'], $selected_slugs) ? 'checked' : '' ?> onchange="handleCategoryChange(this)">
                <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
              </span>
              <span class="text-sm text-gray-700 flex-1"><?= htmlspecialchars($cat['category_name']) ?></span>
              <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $cat['product_count'] ?></span>
            </label>

            <?php if (!empty($cat['subs'])): ?>
            <div class="cat-subs-group space-y-1">
              <?php foreach ($cat['subs'] as $sub): ?>
              <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
                <span class="cat-cb-wrap">
                  <input type="checkbox" class="category-filter" value="<?= htmlspecialchars($sub['category_slug']) ?>"
                         data-category-slug="<?= htmlspecialchars($sub['category_slug']) ?>"
                         data-parent-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                         <?= isCatSlugSelected($sub['category_slug'], $selected_slugs) ? 'checked' : '' ?> onchange="handleCategoryChange(this)">
                  <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
                </span>
                <span class="text-sm text-gray-600 flex-1"><?= htmlspecialchars($sub['category_name']) ?></span>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?= $sub['product_count'] ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Price Range -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
          <svg class="w-5 h-5 me-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Price Range
        </h3>
        <div class="space-y-2">
          <?php foreach ($priceOptions as $val => $label): ?>
          <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition cursor-pointer">
            <span class="cat-radio-wrap">
              <input type="radio" name="price" value="<?= $val ?>" class="price-filter"
                     <?= $selected_price === $val ? 'checked' : '' ?>>
              <span class="cat-radio-box"></span>
            </span>
            <span class="text-sm text-gray-700"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Clear Filters -->
      <button type="button" onclick="clearAllFilters()"
              class="w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition flex items-center justify-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear All Filters
      </button>
    </aside>
  </div>

  <!-- ── Products Grid ── -->
  <div class="md:col-span-3">
    <div id="productsContainer" class="relative min-h-[500px]">

      <!-- Loading spinner (hidden by default) -->
      <div id="productsLoading" class="hidden absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 rounded-xl">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mb-4"></div>
        <p class="text-gray-600 text-sm font-medium">Loading products...</p>
      </div>

      <!-- Products are rendered here on first load AND replaced by AJAX -->
      <div id="productsContent">
        <?php
        // ── Initial server-side render ──────────────────────────────────────
        // Re-use the same fetch_products.php logic via include so the
        // first paint is identical to what AJAX returns.
        //
        // We need $conn available (it is, from the parent page).
        // We temporarily set $_GET so fetch_products can read filters.
        // fetch_products.php outputs only the grid HTML, so we capture it.

        // Build the same URL params that AJAX would send
        $fp_get_backup = $_GET;

        // Category: if selected_slugs exist, pass them; otherwise clear
        if (!empty($selected_slugs)) {
            $_GET['category'] = implode(',', $selected_slugs);
        } else {
            unset($_GET['category']);
        }

        // Capture output of fetch_products (it echoes the grid)
        ob_start();
        // We inline the query logic here so we don't need a separate HTTP call
        // and so $conn is available. This mirrors fetch_products.php exactly.

        $fp_search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $fp_query = "SELECT 
                p.product_id, p.product_name, p.product_unit, p.product_nickname,
                pi.image_path, 
                v.variant_id, v.variant_name, v.variant_price, v.discount_price,
                v.unit_type, v.minimum_order, v.order_increment, v.stock_quantity,
                GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS category_names
              FROM products p
              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0
              LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
              LEFT JOIN product_categories c ON pcl.category_id = c.category_id AND c.is_active = 1
              WHERE p.is_deleted = 0";

        $fp_params = []; $fp_types = '';

        if (!empty($selected_slugs)) {
            $fp_slugStr = implode(',', array_fill(0, count($selected_slugs), '?'));
            $fp_idQuery = "SELECT category_id FROM product_categories WHERE category_slug IN ($fp_slugStr) AND is_active = 1
                           UNION
                           SELECT pc2.category_id FROM product_categories pc2
                           INNER JOIN product_categories pc1 ON pc2.parent_id = pc1.category_id
                           WHERE pc1.category_slug IN ($fp_slugStr) AND pc2.is_active = 1";
            $fp_idStmt = $conn->prepare($fp_idQuery);
            $fp_allSlugs = array_merge($selected_slugs, $selected_slugs);
            $fp_idStmt->bind_param(str_repeat('s', count($fp_allSlugs)), ...$fp_allSlugs);
            $fp_idStmt->execute();
            $fp_idRes = $fp_idStmt->get_result();
            $fp_catIds = [];
            while ($r = $fp_idRes->fetch_assoc()) $fp_catIds[] = intval($r['category_id']);
            $fp_idStmt->close();

            if (!empty($fp_catIds)) {
                $fp_idPH = implode(',', array_fill(0, count($fp_catIds), '?'));
                $fp_query .= " AND p.product_id IN (SELECT product_id FROM product_category_links WHERE category_id IN ($fp_idPH))";
                $fp_types .= str_repeat('i', count($fp_catIds));
                $fp_params = array_merge($fp_params, $fp_catIds);
            } else {
                $fp_query .= " AND 1=0";
            }
        }

        if (!empty($selected_price)) {
            switch ($selected_price) {
                case 'under200': $fp_query .= " AND v.variant_price < 200"; break;
                case '200-400':  $fp_query .= " AND v.variant_price BETWEEN 200 AND 400"; break;
                case '400-600':  $fp_query .= " AND v.variant_price BETWEEN 400 AND 600"; break;
                case 'over600':  $fp_query .= " AND v.variant_price > 600"; break;
            }
        }

        if (!empty($fp_search)) {
            $fp_query .= " AND (p.product_name LIKE ? OR p.product_unit LIKE ? OR c.category_name LIKE ? OR v.variant_name LIKE ? OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)";
            $fp_st = '%' . $fp_search . '%';
            $fp_types .= 'sssss';
            $fp_params = array_merge($fp_params, [$fp_st, $fp_st, $fp_st, $fp_st, $fp_search]);
        }

        $fp_query .= " GROUP BY p.product_id, v.variant_id ORDER BY p.created_at DESC";
        $fp_stmt = $conn->prepare($fp_query);
        if (!empty($fp_params)) $fp_stmt->bind_param($fp_types, ...$fp_params);
        $fp_stmt->execute();
        $fp_result = $fp_stmt->get_result();

        // ── Build products array ──
        $fp_products = [];
        while ($row = $fp_result->fetch_assoc()) {
            $pid = $row['product_id'];
            if (!isset($fp_products[$pid])) {
                $fp_products[$pid] = [
                    'product_name'     => $row['product_name'],
                    'product_unit'     => $row['product_unit'],
                    'product_nickname' => $row['product_nickname'],
                    'image_url' => !empty($row['image_path']) ? "http://localhost/sjfbi-js/uploads/products/" . $row['image_path'] : "http://localhost/sjfbi-js/uploads/products/default.png",                    'variants'         => [],
                    'has_stock'        => false,
                ];
            }
            if (!empty($row['variant_id'])) {
                $sq  = intval($row['stock_quantity'] ?? 0);
                $hsk = $sq > 0;
                $fp_products[$pid]['variants'][] = [
                    'variant_id'      => $row['variant_id'],
                    'variant_name'    => $row['variant_name'],
                    'variant_price'   => $row['variant_price'],
                    'discount_price'  => $row['discount_price'],
                    'unit_type'       => $row['unit_type'] ?? 'piece',
                    'minimum_order'   => $row['minimum_order'] ?? 1,
                    'order_increment' => $row['order_increment'] ?? 1,
                    'stock_quantity'  => $sq,
                    'has_stock'       => $hsk,
                ];
                if ($hsk) $fp_products[$pid]['has_stock'] = true;
            }
        }
        $fp_stmt->close();

        // ── Render product cards — same grid wrapper as fetch_products.php output ──
        echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">';
        include __DIR__ . '/products_card.php'; // components/ folder — same dir as products.php
        echo '</div>';

        $fp_html = ob_get_clean();
        echo $fp_html;

        // Restore $_GET
        $_GET = $fp_get_backup;
        ?>
      </div><!-- #productsContent -->
    </div><!-- #productsContainer -->
  </div>
</div>

<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
#autocompleteResults { max-height: 400px; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
.autocomplete-item:hover { background-color: #f3f5f6; transform: translateX(2px); }
#productsContent { transition: opacity 0.3s ease; }
.variant-select { appearance: auto; max-width: 100%; }
.price-display { min-height: 1.75rem; line-height: 1.2; }
.product-actions .add-cart-btn svg { flex-shrink: 0; }
.product-actions button { min-height: 2.5rem; }

@media (max-width: 420px) {
  #productsContent .grid { gap: .35rem; }
  .variant-control label,
  .minimum-order-text { font-size: 10px; }
  .variant-select { min-height: 2.25rem; font-size: 11px; padding-left: .45rem; padding-right: .45rem; }
  .quantity-control { display: none; }
  .price-display span { font-size: .875rem !important; }
  .product-actions { margin-top: .5rem; padding-top: .5rem; }
  .product-actions .flex { gap: .35rem; }
  .product-actions .add-cart-btn { min-height: 2.25rem; padding-left: .35rem; padding-right: .35rem; }
  .product-actions .add-cart-btn svg { margin-right: 0; width: 17px; height: 17px; }
  .product-actions .add-cart-btn { font-size: 0; }
  .product-actions .add-cart-btn::after { content: "Add"; font-size: 11px; margin-left: .25rem; }
  .product-actions button[title^="Share"] { width: 2.25rem; flex: 0 0 2.25rem; }
}
#productsLoading { pointer-events: none; }

/* Custom checkbox — identical on index.php & shop.php regardless of Preline */
.cat-cb-wrap { position: relative; width: 1rem; height: 1rem; flex-shrink: 0; }
.cat-cb-wrap input[type="checkbox"] {
  position: absolute; opacity: 0; width: 100%; height: 100%;
  margin: 0; padding: 0; cursor: pointer; z-index: 1;
}
.cat-cb-box {
  display: flex; align-items: center; justify-content: center;
  width: 1rem; height: 1rem; border-radius: 4px;
  border: 1.5px solid #d1d5db; background: #fff;
  transition: border-color .15s, background .15s; pointer-events: none;
}
.cat-cb-wrap input[type="checkbox"]:checked ~ .cat-cb-box {
  background: #ea580c; border-color: #ea580c;
}
.cat-cb-box svg { display: none; width: 10px; height: 10px; stroke: white; stroke-width: 3; fill: none; }
.cat-cb-wrap input[type="checkbox"]:checked ~ .cat-cb-box svg { display: block; }

/* Subcategory tree line */
.cat-subs-group {
  margin-left: 1.25rem; margin-top: .25rem;
  padding-left: .75rem; border-left: 2px solid #f3f4f6;
}

/* Custom radio button — matches checkbox style, immune to Preline */
.cat-radio-wrap { position: relative; width: 1rem; height: 1rem; flex-shrink: 0; }
.cat-radio-wrap input[type="radio"] {
  position: absolute; opacity: 0; width: 100%; height: 100%;
  margin: 0; padding: 0; cursor: pointer; z-index: 1;
}
.cat-radio-box {
  display: flex; align-items: center; justify-content: center;
  width: 1rem; height: 1rem; border-radius: 50%;
  border: 1.5px solid #d1d5db; background: #fff;
  transition: border-color .15s, background .15s; pointer-events: none;
}
.cat-radio-wrap input[type="radio"]:checked ~ .cat-radio-box {
  border-color: #ea580c; border-width: 4px; background: #fff;
}
/* Hover state */
label:hover .cat-radio-box { border-color: #f97316; }
</style>

<script>
// Wire up the search submit button to AJAX (not form submit)
document.getElementById('searchSubmitBtn').addEventListener('click', function () {
    var val = document.getElementById('searchInput').value.trim();
    if (typeof fetchFilteredProducts === 'function') {
        window._activeFilters = window._activeFilters || {};
        window._activeFilters.search = val;
        fetchFilteredProducts();
    }
});
</script>
