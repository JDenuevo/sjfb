<?php
// Function to get category hierarchy
function getCategoryHierarchy($conn, $parent_id = null, $level = 0) {
    $sql = "SELECT category_id, category_name, category_slug, category_level 
            FROM product_categories 
            WHERE parent_id " . ($parent_id === null ? "IS NULL" : "= $parent_id") . "
            AND is_active = 1
            ORDER BY sort_order ASC, category_name ASC";
    
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['children'] = getCategoryHierarchy($conn, $row['category_id'], $level + 1);
            $row['level'] = $level;
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Page mapping for breadcrumbs
$pageMap = [
    'Home' => '/sjfbi-js/index.php',
    'Shop' => '/sjfbi-js/shop.php',
    'About' => '/sjfbi-js/about.php',
    'Sustainability' => '/sjfbi-js/sustainability.php',
    'Services' => '/sjfbi-js/services.php',
    'Careers' => '/sjfbi-js/careers.php',
    'Register' => '/sjfbi-js/register.php',
];
?>

<!-- Mobile Breadcrumb Navigation -->
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto lg:hidden">
    <div class="sticky top-0 inset-x-0 z-20 bg-white border-y border-gray-200 px-4 sm:px-6 lg:px-8 lg:hidden">
        <div class="flex items-center py-2">
            <ol class="flex items-center whitespace-nowrap text-sm">
                <!-- Home -->
                <li class="flex items-center text-gray-800">
                    <a href="/sjfbi-js/index.php" class="hover:underline">Home</a>
                </li>

                <?php if (!empty($pageTitle) && $pageTitle !== 'Home' && isset($pageMap[$pageTitle])): ?>
                    <li class="flex items-center">
                        <svg class="shrink-0 mx-3 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 6l6 6-6 6"/>
                        </svg>
                        <a href="<?= $pageMap[$pageTitle]; ?>" class="hover:underline">
                            <?= htmlspecialchars($pageTitle); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Current Page (if on a product or category page) -->
                <?php if (isset($currentPage) && $currentPage !== ''): ?>
                    <li class="flex items-center">
                        <svg class="shrink-0 mx-3 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 6l6 6-6 6"/>
                        </svg>
                        <span class="text-gray-600 font-medium"><?= htmlspecialchars($currentPage); ?></span>
                    </li>
                <?php endif; ?>
            </ol>
        </div>
    </div>
</div>

<!-- Desktop Category Navigation - Place this in your header or sidebar, not in breadcrumb -->
<?php if (isset($showCategories) && $showCategories === true): ?>
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-4 hidden lg:block">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-gray-700 mr-2">Categories:</span>
            <?php 
            $categories = getCategoryHierarchy($conn);
            foreach ($categories as $category): 
            ?>
            <div class="relative group">
                <a href="/sjfbi-js/item.php?slug=<?= $category['category_slug']; ?>" 
                   class="inline-block px-3 py-1.5 text-sm text-gray-700 hover:text-orange-600 hover:bg-orange-50 rounded-md transition">
                    <?= htmlspecialchars($category['category_name']); ?>
                </a>
                
                <?php if (!empty($category['children'])): ?>
                <!-- Dropdown for subcategories -->
                <div class="absolute left-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                    <?php foreach ($category['children'] as $subcat): ?>
                    <a href="/sjfbi-js/item.php?slug=<?= $subcat['category_slug']; ?>" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 first:rounded-t-lg last:rounded-b-lg">
                        <?= htmlspecialchars($subcat['category_name']); ?>
                    </a>
                    
                    <?php if (!empty($subcat['children'])): ?>
                        <?php foreach ($subcat['children'] as $subsubcat): ?>
                        <a href="/sjfbi-js/item.php?slug=<?= $subsubcat['category_slug']; ?>" 
                           class="block px-4 py-2 pl-8 text-sm text-gray-600 hover:bg-orange-50 hover:text-orange-600">
                            └ <?= htmlspecialchars($subsubcat['category_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mobile Category Navigation - Only show when needed -->
<?php if (isset($showMobileCategories) && $showMobileCategories === true): ?>
<div class="lg:hidden max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-2">
    <div class="bg-white rounded-lg shadow-sm p-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Categories</span>
            <button onclick="toggleMobileCategories()" class="text-orange-600 text-sm hover:underline">
                View All
            </button>
        </div>
        <div id="mobileCategories" class="hidden">
            <?php 
            $categories = getCategoryHierarchy($conn);
            foreach ($categories as $category): 
            ?>
            <div class="mb-2">
                <a href="/sjfbi-js/item.php?slug=<?= $category['category_slug']; ?>" 
                   class="block py-1.5 text-sm font-medium text-gray-800 hover:text-orange-600">
                    <?= htmlspecialchars($category['category_name']); ?>
                </a>
                <?php if (!empty($category['children'])): ?>
                <div class="ml-4 mt-1 space-y-1">
                    <?php foreach ($category['children'] as $subcat): ?>
                    <a href="/sjfbi-js/item.php?slug=<?= $subcat['category_slug']; ?>" 
                       class="block py-1 text-sm text-gray-600 hover:text-orange-600">
                        <?= htmlspecialchars($subcat['category_name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleMobileCategories() {
    const mobileCategories = document.getElementById('mobileCategories');
    const button = event.currentTarget;
    if (mobileCategories.classList.contains('hidden')) {
        mobileCategories.classList.remove('hidden');
        button.textContent = 'Show Less';
    } else {
        mobileCategories.classList.add('hidden');
        button.textContent = 'View All';
    }
}
</script>
<?php endif; ?>