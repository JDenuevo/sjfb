<!-- components/products.php -->
<!-- JS handled by: cart_process.js (cart ops) + product_process.js (variants, search, add-to-cart, share) -->

<div class="mx-auto">
  <div class="flex items-center my-5 gap-2">

    <!-- Mobile Filter Button -->
    <button type="button" id="openFilterCanvas"
      class="md:hidden flex-shrink-0 inline-flex items-center gap-x-2 py-2.5 px-4 rounded-full
             bg-white border border-gray-200 text-gray-600 text-sm font-medium shadow-sm
             hover:border-orange-400 hover:text-orange-600 transition-all duration-200
             focus:outline-none focus:ring-2 focus:ring-orange-300">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>
      </svg>
      <?php
        $activeSlugs = array_filter(
            isset($_GET['category']) ? explode(',', $_GET['category']) : [],
            fn($c) => $c !== 'all' && $c !== ''
        );
        $activeBadge = count($activeSlugs) + (!empty($_GET['price']) ? 1 : 0);
      ?>
      <?php if ($activeBadge > 0): ?>
      <span class="inline-flex items-center justify-center size-5 rounded-full
                   bg-orange-600 text-white text-[10px] font-bold leading-none">
        <?= $activeBadge ?>
      </span>
      <?php endif; ?>
    </button>

    <!-- Search input -->
    <div class="relative flex-1 min-w-0">
      <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none">
        <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>
        </svg>
      </div>
      <input type="text" id="searchInput"
        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
        class="py-2.5 ps-10 pe-10 block w-full rounded-full text-sm
               border border-gray-200 bg-white
               focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none
               placeholder:text-gray-400 transition-all duration-200"
        placeholder="What would you like?" autocomplete="off"/>
      <button type="button" id="clearSearch"
        class="absolute inset-y-0 end-0 flex items-center pe-4
               text-gray-400 hover:text-gray-600 transition-colors
               <?= empty($_GET['search']) ? 'hidden' : '' ?>">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
        </svg>
      </button>
      <div id="autocompleteResults"
           class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-lg border border-gray-100 hidden
                  max-h-96 overflow-y-auto">
      </div>
    </div>

    <!-- Search submit button -->
    <button type="button" id="searchSubmitBtn"
      class="flex-shrink-0 size-10 inline-flex items-center justify-center rounded-full
             bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white
             shadow-sm transition-all duration-200
             focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
      <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>
      </svg>
    </button>

  </div>
</div>


<?php
// ─── Shared data needed by both modes ────────────────────────────────────────
$selected_slugs = isset($_GET['category']) ? array_filter(explode(',', $_GET['category']), fn($c) => $c !== 'all' && $c !== '') : [];
$selected_price = isset($_GET['price']) ? $_GET['price'] : '';

$priceOptions = [
    'under200' => 'Under ₱200',
    '200-400'  => '₱200 – ₱400',
    '400-600'  => '₱400 – ₱600',
    'over600'  => 'Over ₱600',
];

if (!function_exists('isCatSlugSelected')) {
    function isCatSlugSelected(string $slug, array $selectedSlugs): bool {
        return in_array($slug, $selectedSlugs);
    }
}

if (!isset($fp_limit)) {
    $total_query  = "SELECT COUNT(DISTINCT p.product_id) as total
                     FROM products p
                     JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0 AND pv.is_hidden = 0
                     WHERE p.is_deleted = 0 AND p.is_hidden = 0 AND pv.stock_quantity > 0";
    $total_result = $conn->query($total_query);
    $total_count  = $total_result ? $total_result->fetch_assoc()['total'] : 0;

    $categories = [];
    $cat_res = $conn->query(
        "SELECT pc.category_id, pc.category_name, pc.category_slug,
                COUNT(DISTINCT pcl.product_id) AS product_count
         FROM product_categories pc
         LEFT JOIN product_category_links pcl ON pc.category_id = pcl.category_id
         LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0 AND p.is_hidden = 0
         LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0 AND pv.is_hidden = 0 AND pv.stock_quantity > 0
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
                 LEFT JOIN products p ON pcl.product_id = p.product_id AND p.is_deleted = 0 AND p.is_hidden = 0
                 LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0 AND pv.is_hidden = 0 AND pv.stock_quantity > 0
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
}
?>


<?php if (!isset($fp_limit)): ?>
<!-- ══════════════════════════ FULL SHOP PAGE ══════════════════════════════ -->

<?php
$activeSlugs = array_filter(
    isset($_GET['category']) ? explode(',', $_GET['category']) : [],
    fn($c) => $c !== 'all' && $c !== ''
);
$activeBadge = count($activeSlugs) + (!empty($_GET['price']) ? 1 : 0);
?>

<!-- ── Mobile bottom-sheet filter drawer ─────────────────────────────────── -->
<div id="filterDrawer"
     class="md:hidden fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-2xl shadow-2xl border-t border-gray-100
            transform translate-y-full transition-transform duration-300 ease-out max-h-[82vh] overflow-y-auto">

  <!-- Drawer handle + header -->
  <div class="sticky top-0 bg-white z-10 rounded-t-2xl border-b border-gray-100">
    <div class="flex justify-center pt-3 pb-1">
      <div class="w-8 h-1 rounded-full bg-gray-300"></div>
    </div>
    <div class="flex items-center justify-between px-4 py-3">
      <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
        Filters
        <?php if ($activeBadge > 0): ?>
        <span class="inline-flex items-center justify-center size-5 rounded-full
                     bg-orange-600 text-white text-[10px] font-bold leading-none">
          <?= $activeBadge ?>
        </span>
        <?php endif; ?>
      </h2>
      <div class="flex items-center gap-3">
        <button type="button" onclick="clearAllFilters()"
                class="text-xs text-orange-600 font-semibold hover:text-orange-800 transition-colors
                       focus:outline-none focus:underline">
          Clear all
        </button>
        <button type="button" id="closeFilterDrawer"
                class="size-7 inline-flex items-center justify-center rounded-full
                       bg-gray-100 hover:bg-gray-200 text-gray-500 text-lg font-medium
                       transition-colors leading-none focus:outline-none focus:ring-2 focus:ring-gray-300">
          ×
        </button>
      </div>
    </div>
  </div>

  <!-- Drawer body -->
  <div class="p-4 pb-8 space-y-6">

    <!-- Categories section -->
    <div>
      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2.5">Categories</p>
      <div class="space-y-0.5">

        <!-- All Products -->
        <label class="flex items-center gap-3 px-2 py-2.5 hover:bg-orange-50 rounded-lg cursor-pointer transition-colors group">
          <span class="cat-cb-wrap">
            <input type="checkbox" class="category-filter" value="all" data-category-slug="all"
                   <?= empty($selected_slugs) ? 'checked' : '' ?> onchange="handleCategoryChange(this)">
            <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
          </span>
          <span class="text-sm text-gray-700 flex-1 group-hover:text-orange-700 transition-colors">All Products</span>
          <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">
            <?= $total_count ?>
          </span>
        </label>

        <?php foreach ($categories as $cat): ?>
        <div class="category-group">
          <label class="flex items-center gap-3 px-2 py-2.5 hover:bg-orange-50 rounded-lg cursor-pointer transition-colors group">
            <span class="cat-cb-wrap">
              <input type="checkbox" class="category-filter"
                     value="<?= htmlspecialchars($cat['category_slug']) ?>"
                     data-category-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                     <?= isCatSlugSelected($cat['category_slug'], $selected_slugs) ? 'checked' : '' ?>
                     onchange="handleCategoryChange(this)">
              <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
            </span>
            <span class="text-sm text-gray-700 flex-1 group-hover:text-orange-700 transition-colors">
              <?= htmlspecialchars($cat['category_name']) ?>
            </span>
            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">
              <?= $cat['product_count'] ?>
            </span>
          </label>

          <?php if (!empty($cat['subs'])): ?>
          <div class="cat-subs-group pl-6 mt-0.5 space-y-0.5 border-l-2 border-gray-100 ms-3">
            <?php foreach ($cat['subs'] as $sub): ?>
            <label class="flex items-center gap-3 px-2 py-2 hover:bg-orange-50 rounded-lg cursor-pointer transition-colors group">
              <span class="cat-cb-wrap">
                <input type="checkbox" class="category-filter"
                       value="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-category-slug="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-parent-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                       <?= isCatSlugSelected($sub['category_slug'], $selected_slugs) ? 'checked' : '' ?>
                       onchange="handleCategoryChange(this)">
                <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
              </span>
              <span class="text-sm text-gray-500 flex-1 group-hover:text-orange-600 transition-colors">
                <?= htmlspecialchars($sub['category_name']) ?>
              </span>
              <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">
                <?= $sub['product_count'] ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Price Range section -->
    <div>
      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2.5">Price Range</p>
      <div class="space-y-0.5">
        <?php foreach ($priceOptions as $val => $label): ?>
        <label class="flex items-center gap-3 px-2 py-2.5 hover:bg-orange-50 rounded-lg cursor-pointer transition-colors group">
          <span class="cat-radio-wrap">
            <input type="radio" name="price_mobile" value="<?= $val ?>" class="price-filter"
                   <?= $selected_price === $val ? 'checked' : '' ?>>
            <span class="cat-radio-box"></span>
          </span>
          <span class="text-sm text-gray-700 group-hover:text-orange-700 transition-colors"><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Apply button -->
    <button type="button" id="applyFiltersBtn"
            class="w-full py-3 px-4 inline-flex items-center justify-center
                   rounded-xl border border-transparent bg-orange-600 hover:bg-orange-700
                   active:scale-95 text-white text-sm font-semibold
                   shadow-sm transition-all duration-150
                   focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
      Show Results
    </button>
  </div>
</div>

<!-- Drawer backdrop -->
<div id="filterBackdrop"
     class="md:hidden fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300">
</div>

<!-- Drawer JS -->
<script>
(function () {
    var openBtn  = document.getElementById('openFilterCanvas');
    var closeBtn = document.getElementById('closeFilterDrawer');
    var applyBtn = document.getElementById('applyFiltersBtn');
    var drawer   = document.getElementById('filterDrawer');
    var backdrop = document.getElementById('filterBackdrop');
    if (!drawer || !backdrop) return;

    function openDrawer() {
        drawer.classList.remove('translate-y-full');
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.add('translate-y-full');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
    }

    if (openBtn)  openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (applyBtn) applyBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    var startY = 0;
    drawer.addEventListener('touchstart', function (e) { startY = e.touches[0].clientY; }, { passive: true });
    drawer.addEventListener('touchend',   function (e) { if (e.changedTouches[0].clientY - startY > 60) closeDrawer(); }, { passive: true });
})();
</script>

<!-- ── Main layout: sidebar + products ───────────────────────────────────── -->
<div class="flex gap-5 lg:gap-6 mt-4">

  <!-- Desktop Sidebar -->
  <aside class="hidden md:flex flex-col gap-4 w-52 lg:w-56 xl:w-60 shrink-0 self-start sticky top-4">

    <!-- Categories card -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
      <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <svg class="size-4 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Categories
      </h3>
      <div class="space-y-0.5">

        <label class="flex items-center gap-2.5 px-2 py-1.5 hover:bg-orange-50 rounded-lg transition-colors cursor-pointer group">
          <span class="cat-cb-wrap">
            <input type="checkbox" class="category-filter" value="all" data-category-slug="all"
                   <?= empty($selected_slugs) ? 'checked' : '' ?> onchange="handleCategoryChange(this)">
            <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
          </span>
          <span class="text-xs text-gray-700 flex-1 group-hover:text-orange-700 transition-colors">All Products</span>
          <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-400">
            <?= $total_count ?>
          </span>
        </label>

        <?php foreach ($categories as $cat): ?>
        <div class="category-group">
          <label class="flex items-center gap-2.5 px-2 py-1.5 hover:bg-orange-50 rounded-lg transition-colors cursor-pointer group">
            <span class="cat-cb-wrap">
              <input type="checkbox" class="category-filter"
                     value="<?= htmlspecialchars($cat['category_slug']) ?>"
                     data-category-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                     <?= isCatSlugSelected($cat['category_slug'], $selected_slugs) ? 'checked' : '' ?>
                     onchange="handleCategoryChange(this)">
              <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
            </span>
            <span class="text-xs text-gray-700 flex-1 group-hover:text-orange-700 transition-colors">
              <?= htmlspecialchars($cat['category_name']) ?>
            </span>
            <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-400">
              <?= $cat['product_count'] ?>
            </span>
          </label>

          <?php if (!empty($cat['subs'])): ?>
          <div class="cat-subs-group mt-0.5 ms-3 ps-3 space-y-0.5 border-s-2 border-gray-100">
            <?php foreach ($cat['subs'] as $sub): ?>
            <label class="flex items-center gap-2.5 px-2 py-1.5 hover:bg-orange-50 rounded-lg transition-colors cursor-pointer group">
              <span class="cat-cb-wrap">
                <input type="checkbox" class="category-filter"
                       value="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-category-slug="<?= htmlspecialchars($sub['category_slug']) ?>"
                       data-parent-slug="<?= htmlspecialchars($cat['category_slug']) ?>"
                       <?= isCatSlugSelected($sub['category_slug'], $selected_slugs) ? 'checked' : '' ?>
                       onchange="handleCategoryChange(this)">
                <span class="cat-cb-box"><svg viewBox="0 0 12 12"><polyline points="1.5,6 5,9.5 10.5,2.5"/></svg></span>
              </span>
              <span class="text-xs text-gray-400 flex-1 group-hover:text-orange-600 transition-colors">
                <?= htmlspecialchars($sub['category_name']) ?>
              </span>
              <span class="inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-400">
                <?= $sub['product_count'] ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Price Range card -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
      <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <svg class="size-4 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Price Range
      </h3>
      <div class="space-y-0.5">
        <?php foreach ($priceOptions as $val => $label): ?>
        <label class="flex items-center gap-2.5 px-2 py-1.5 hover:bg-orange-50 rounded-lg transition-colors cursor-pointer group">
          <span class="cat-radio-wrap">
            <input type="radio" name="price" value="<?= $val ?>" class="price-filter"
                   <?= $selected_price === $val ? 'checked' : '' ?>>
            <span class="cat-radio-box"></span>
          </span>
          <span class="text-xs text-gray-700 group-hover:text-orange-700 transition-colors"><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Clear filters -->
    <button type="button" onclick="clearAllFilters()"
            class="w-full py-2 px-4 inline-flex items-center justify-center gap-x-1.5
                   rounded-lg border border-transparent bg-gray-100 text-gray-500
                   text-xs font-medium
                   hover:bg-red-50 hover:text-red-600 hover:border-red-200
                   transition-all duration-150
                   focus:outline-none focus:ring-2 focus:ring-red-200">
      <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
      Clear Filters
    </button>
  </aside>

  <!-- Products column -->
  <div class="flex-1 min-w-0">

<?php else: ?>
<!-- ══════════════════════════ PREVIEW MODE ════════════════════════════════ -->
<div class="w-full">
  <div>
<?php endif; ?>

    <!-- Products container -->
    <div id="productsContainer" class="relative <?= !isset($fp_limit) ? 'min-h-96' : '' ?>">

      <!-- Loading overlay -->
      <?php if (!isset($fp_limit)): ?>
      <div id="productsLoading"
           class="hidden absolute inset-0 z-10 flex flex-col items-center justify-center
                  bg-white/80 rounded-xl backdrop-blur-sm pointer-events-none">
        <div class="animate-spin size-9 rounded-full border-2 border-gray-200 border-t-orange-600 mb-3"></div>
        <p class="text-gray-400 text-xs font-medium">Loading products…</p>
      </div>
      <?php endif; ?>

      <div id="productsContent" class="transition-opacity duration-300">
        <?php
        $fp_get_backup = $_GET;

        if (!empty($selected_slugs)) {
            $_GET['category'] = implode(',', $selected_slugs);
        } else {
            unset($_GET['category']);
        }

        ob_start();

        $fp_search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $fp_query = "SELECT
                p.product_id, p.product_name, p.product_unit, p.product_nickname,
                p.created_at,
                pi.image_path,
                v.variant_id, v.variant_name, v.variant_price, v.discount_price,
                v.unit_type, v.minimum_order, v.order_increment, v.stock_quantity,
                GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS category_names
              FROM products p
              LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
              LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0 AND v.is_hidden = 0
              LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
              LEFT JOIN product_categories c ON pcl.category_id = c.category_id AND c.is_active = 1
              WHERE p.is_deleted = 0 AND p.is_hidden = 0";

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
                case 'under200': $fp_query .= " AND v.variant_price < 200";               break;
                case '200-400':  $fp_query .= " AND v.variant_price BETWEEN 200 AND 400"; break;
                case '400-600':  $fp_query .= " AND v.variant_price BETWEEN 400 AND 600"; break;
                case 'over600':  $fp_query .= " AND v.variant_price > 600";               break;
            }
        }

        if (!empty($fp_search)) {
            $fp_query .= " AND (p.product_name LIKE ? OR p.product_unit LIKE ? OR c.category_name LIKE ? OR v.variant_name LIKE ? OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)";
            $fp_st = '%' . $fp_search . '%';
            $fp_types .= 'sssss';
            $fp_params = array_merge($fp_params, [$fp_st, $fp_st, $fp_st, $fp_st, $fp_search]);
        }

        $fp_limit_clause = isset($fp_limit) ? " LIMIT " . ((int)$fp_limit * 5) : "";
        $fp_query .= " GROUP BY p.product_id, v.variant_id ORDER BY p.created_at DESC" . $fp_limit_clause;

        $fp_stmt = $conn->prepare($fp_query);
        if (!empty($fp_params)) $fp_stmt->bind_param($fp_types, ...$fp_params);
        $fp_stmt->execute();
        $fp_result = $fp_stmt->get_result();

        $fp_products = [];
        while ($row = $fp_result->fetch_assoc()) {
            $pid = $row['product_id'];
            if (!isset($fp_products[$pid])) {
                $fp_products[$pid] = [
                    'product_name'     => $row['product_name'],
                    'product_unit'     => $row['product_unit'],
                    'product_nickname' => $row['product_nickname'],
                    'created_at'       => $row['created_at'],
                    'image_url'        => !empty($row['image_path'])
                        ? $baseUrl . 'uploads/products/' . $row['image_path']
                        : $baseUrl . 'uploads/products/default.png',
                    'category_names'   => $row['category_names'],
                    'variants'         => [],
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

        if (isset($fp_limit)) {
            $fp_products = array_slice($fp_products, 0, (int)$fp_limit, true);
        }

        $gridClass = isset($fp_limit)
            ? 'grid grid-cols-2 min-[480px]:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4'
            : 'grid grid-cols-2 min-[480px]:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4';

        echo '<div class="' . $gridClass . '">';
        include __DIR__ . '/products_card.php';
        echo '</div>';

        echo ob_get_clean();

        $_GET = $fp_get_backup;
        ?>
      </div><!-- #productsContent -->
    </div><!-- #productsContainer -->

<?php if (!isset($fp_limit)): ?>
  </div><!-- flex-1 products column -->
</div><!-- flex sidebar + products -->
<?php else: ?>
  </div>
</div>
<?php endif; ?>

<style>
/* ── Custom checkbox / radio controls (no Tailwind equivalent for checked state styling) ── */
.cat-cb-wrap { position:relative;width:1rem;height:1rem;flex-shrink:0; }
.cat-cb-wrap input[type="checkbox"] { position:absolute;opacity:0;width:100%;height:100%;margin:0;padding:0;cursor:pointer;z-index:1; }
.cat-cb-box { display:flex;align-items:center;justify-content:center;width:1rem;height:1rem;border-radius:4px;border:1.5px solid #d1d5db;background:#fff;transition:border-color .15s,background .15s;pointer-events:none; }
.cat-cb-wrap input[type="checkbox"]:checked ~ .cat-cb-box { background:#ea580c;border-color:#ea580c; }
.cat-cb-box svg { display:none;width:10px;height:10px;stroke:white;stroke-width:3;fill:none; }
.cat-cb-wrap input[type="checkbox"]:checked ~ .cat-cb-box svg { display:block; }

.cat-radio-wrap { position:relative;width:1rem;height:1rem;flex-shrink:0; }
.cat-radio-wrap input[type="radio"] { position:absolute;opacity:0;width:100%;height:100%;margin:0;padding:0;cursor:pointer;z-index:1; }
.cat-radio-box { display:flex;align-items:center;justify-content:center;width:1rem;height:1rem;border-radius:9999px;border:1.5px solid #d1d5db;background:#fff;transition:border-color .15s,border-width .15s;pointer-events:none; }
.cat-radio-wrap input[type="radio"]:checked ~ .cat-radio-box { border-color:#ea580c;border-width:4px; }
label:hover .cat-radio-box { border-color:#f97316; }

/* ── Variant buttons on product cards ── */
.variant-button { background-color:#fff;border:1px solid #d1d5db;color:#374151;transition:all .2s ease; }
.variant-button.selected-variant { background-color:#f59e0b;border-color:#f59e0b;color:#fff; }

/* ── Autocomplete dropdown ── */
#autocompleteResults { max-height:400px;overflow-y:auto; }
.autocomplete-item:hover { background-color:#f3f5f6;transform:translateX(2px); }

/* ── Hide number input spinners ── */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none;margin:0; }
</style>

<?php if (!isset($fp_limit)): ?>
<script>
document.getElementById('searchSubmitBtn').addEventListener('click', function () {
    var val = document.getElementById('searchInput').value.trim();
    if (typeof fetchFilteredProducts === 'function') {
        window._activeFilters = window._activeFilters || {};
        window._activeFilters.search = val;
        fetchFilteredProducts();
    }
});
</script>
<?php endif; ?>