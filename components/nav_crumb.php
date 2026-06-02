<?php
// Page mapping for breadcrumbs
$pageMap = [
    'Home' => '/sjfbi-js/index.php',
    'Shop' => '/sjfbi-js/shop.php',
    'About' => '/sjfbi-js/about.php',
    'Sustainability' => '/sjfbi-js/sustainability.php',
    'Services' => '/sjfbi-js/services.php',
    'Careers' => '/sjfbi-js/careers.php',
    'Register' => '/sjfbi-js/register.php',
    'Blogs' => '/sjfbi-js/blogs/',
    'Reviews' => '/sjfbi-js/reviews.php/',
    'Track' => '/sjfbi-js/track.php/',
    'Contact' => '/sjfbi-js/contact.php/',
    'Checkout' => '/sjfbi-js/checkout.php/',
    'Order Review' => '/sjfbi-js/order_review.php/',
];

// Detect current page from URL
$currentUri = $_SERVER['REQUEST_URI'];
$isBlogPost = strpos($currentUri, '/blogs/') !== false && $currentUri !== '/sjfbi-js/blogs/' && $currentUri !== '/sjfbi-js/blogs/index.php';
$isBlogListing = $currentUri === '/sjfbi-js/blogs/' || $currentUri === '/sjfbi-js/blogs/index.php';
$isShopPage = strpos($currentUri, 'shop') !== false || strpos($currentUri, 'shop.php') !== false;
$isProductPage = strpos($currentUri, '/item/') !== false || strpos($currentUri, 'item.php') !== false;
?>

<!-- Breadcrumb Navigation -->
<div class="max-w-[85rem] pb-4 
     <?= ($isBlogListing || $isBlogPost || $isProductPage) 
          ? 'block' // Always show on blog pages AND product pages 
          : 'block sm:block md:hidden lg:hidden xl:hidden' // Show only on SM/XS for other pages
     ?>">
    <div class="bg-white rounded-lg shadow-sm py-3 px-4">
        <ol class="flex items-center flex-wrap text-sm" itemscope itemtype="https://schema.org/BreadcrumbList">
            <!-- Home -->
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center text-gray-800">
                <a href="/sjfbi-js/index.php" itemprop="item" class="hover:text-orange-600">
                    <span itemprop="name">Home</span>
                </a>
                <meta itemprop="position" content="1" />
            </li>

            <!-- Blog Listing (if on blog page) -->
            <?php if ($isBlogListing || $isBlogPost): ?>
            <li class="flex items-center">
                <svg class="shrink-0 mx-2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center text-gray-800">
                <a href="/sjfbi-js/blogs/" itemprop="item" class="hover:text-orange-600">
                    <span itemprop="name">Blogs</span>
                </a>
                <meta itemprop="position" content="2" />
            </li>
            <?php endif; ?>

            <!-- Shop/Product Breadcrumb -->
            <?php if ($isShopPage || $isProductPage): ?>
            <li class="flex items-center">
                <svg class="shrink-0 mx-2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center text-gray-800">
                <a href="/sjfbi-js/shop.php" itemprop="item" class="hover:text-orange-600">
                    <span itemprop="name">Seafood Shop</span>
                </a>
                <meta itemprop="position" content="2" />
            </li>
            <?php endif; ?>

            <!-- Regular Pages (except shop and blog) -->
            <?php if (!empty($pageTitle) && $pageTitle !== 'Home' && isset($pageMap[$pageTitle]) && !$isBlogPost && !$isBlogListing && !$isShopPage && !$isProductPage): ?>
            <li class="flex items-center">
                <svg class="shrink-0 mx-2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center text-gray-800">
                <a href="<?= $pageMap[$pageTitle]; ?>" itemprop="item" class="hover:text-orange-600">
                    <span itemprop="name"><?= htmlspecialchars($pageTitle); ?></span>
                </a>
                <meta itemprop="position" content="2" />
            </li>
            <?php endif; ?>

            <!-- Current Page (Blog Post Title or Product Name) -->
            <?php if (isset($currentPage) && $currentPage !== ''): ?>
            <li class="flex items-center">
                <svg class="shrink-0 mx-2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center text-gray-800">
                <span itemprop="name" class="text-gray-600 font-medium"><?= htmlspecialchars($currentPage); ?></span>
                <meta itemprop="position" content="3" />
            </li>
            <?php endif; ?>
        </ol>
    </div>
</div>

