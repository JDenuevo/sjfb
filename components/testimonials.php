<?php

$sql = "SELECT 
        r.review_id,
        r.full_name,
        r.position,
        r.company,
        r.rating,
        r.feedback,
        r.is_verified_purchase,
        r.created_at,
        p.product_name,
        (SELECT COUNT(*) FROM review_attachments ra WHERE ra.review_id = r.review_id) as attachment_count,
        LEFT(r.full_name, 1) as first_initial,
        SUBSTRING_INDEX(r.full_name, ' ', -1) as last_name
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    WHERE r.status = 'approved'
    ORDER BY r.rating DESC, r.created_at DESC
    LIMIT 6";

$result = $conn->query($sql);
$reviews = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Overall statistics
$stats_sql = "SELECT 
                COUNT(DISTINCT review_id) as total_reviews,
                COALESCE(AVG(rating), 0) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
              FROM reviews 
              WHERE status = 'approved'";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];

$orders_sql = "SELECT COUNT(DISTINCT order_id) as total_orders FROM orders WHERE order_status = 'Delivered'";
$orders_result = $conn->query($orders_sql);
$orders = $orders_result ? $orders_result->fetch_assoc() : [];

$customers_sql = "SELECT COUNT(DISTINCT account_id) as total_customers FROM orders WHERE order_status = 'Delivered' AND account_id IS NOT NULL";
$customers_result = $conn->query($customers_sql);
$customers = $customers_result ? $customers_result->fetch_assoc() : [];

// Defaults
if (empty($stats)) {
    $stats = ['avg_rating' => 0, 'total_reviews' => 0, 'five_star' => 0, 'four_star' => 0, 'three_star' => 0, 'two_star' => 0, 'one_star' => 0];
}
if (empty($orders))    $orders    = ['total_orders' => 2000];
if (empty($customers)) $customers = ['total_customers' => 1000];

$totalReviews = max(1, (int)$stats['total_reviews']); // avoid division by zero
?>

<!-- ====== TESTIMONIALS SECTION ====== -->
<div class="overflow-hidden bg-gradient-to-br from-orange-50 via-amber-50 to-orange-100">
  <div class="relative max-w-[85rem] px-4 py-14 sm:px-6 lg:px-8 lg:py-20 mx-auto">

    <!-- Section Header -->
    <div class="text-center mb-12">
      <span class="eyebrow justify-center">Customer Reviews</span>
      <h2 class="font-display text-3xl font-bold text-gray-900">Loved by families & businesses<br class="hidden sm:block"> across the Philippines</h2>
      <p class="mt-4 text-gray-500 max-w-xl mx-auto">Real feedback from real customers who trust our seafood products daily.</p>
    </div>

    <!-- Rating Summary Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-orange-100 p-6 mb-12 max-w-2xl mx-auto">
      <div class="flex flex-col sm:flex-row items-center gap-8">
        <!-- Big score -->
        <div class="text-center shrink-0">
          <p class="text-7xl font-black text-orange-600 leading-none"><?= number_format($stats['avg_rating'], 1) ?></p>
          <div class="flex items-center justify-center gap-0.5 mt-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <svg class="w-5 h-5 <?= $i <= round($stats['avg_rating']) ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            <?php endfor; ?>
          </div>
          <p class="text-sm text-gray-500 mt-1"><?= number_format($stats['total_reviews']) ?> reviews</p>
        </div>

        <!-- Rating bars -->
        <div class="w-full space-y-2">
          <?php 
          $bars = [
            5 => $stats['five_star'],
            4 => $stats['four_star'],
            3 => $stats['three_star'],
            2 => $stats['two_star'],
            1 => $stats['one_star'],
          ];
          foreach ($bars as $star => $count):
            $pct = round(($count / $totalReviews) * 100);
          ?>
          <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 w-5 text-right shrink-0"><?= $star ?>★</span>
            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
              <div class="h-full bg-yellow-400 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
            </div>
            <span class="text-xs text-gray-400 w-8 shrink-0"><?= $count ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Reviews Grid -->
    <?php if (!empty($reviews)): ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($reviews as $review): 
        $initials = $review['first_initial'];
        if (!empty($review['last_name'])) {
            $initials .= substr($review['last_name'], 0, 1);
        }
        $avatarColors = ['bg-orange-400', 'bg-amber-500', 'bg-red-400', 'bg-teal-500', 'bg-sky-500', 'bg-violet-500'];
        $colorClass = $avatarColors[abs(crc32($review['full_name'])) % count($avatarColors)];
      ?>
      <div class="flex flex-col bg-white rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 hover:border-orange-200 hover:-translate-y-0.5">
        <!-- Card body -->
        <div class="flex-1 p-5">
          <!-- Stars + Product -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-0.5">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <svg class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              <?php endfor; ?>
            </div>
            <?php if (!empty($review['product_name'])): ?>
              <span class="text-xs px-2 py-0.5 bg-orange-50 text-orange-600 rounded-full border border-orange-100 truncate max-w-[120px]">
                <?= htmlspecialchars($review['product_name']) ?>
              </span>
            <?php endif; ?>
          </div>

          <!-- Quote Icon & Feedback -->
          <p class="text-base italic md:text-lg text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="inline-block text-orange-400 mr-1">
              <path d="M9 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z" />
              <path d="M18 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z" />
            </svg>
            <?php echo htmlspecialchars(substr($review['feedback'], 0, 150)) . (strlen($review['feedback']) > 150 ? '...' : ''); ?>
          </p>

          <!-- Photo count badge -->
          <?php if (!empty($review['attachment_count']) && $review['attachment_count'] > 0): ?>
          <div class="mt-3 flex items-center gap-1 text-xs text-gray-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <?= $review['attachment_count'] ?> photo<?= $review['attachment_count'] > 1 ? 's' : '' ?> attached
          </div>
          <?php endif; ?>
        </div>

        <!-- Card footer -->
        <div class="px-5 py-4 bg-gray-300 rounded-b-2xl">
          <div class="flex items-center gap-3">
            <div class="size-10 rounded-full <?= $colorClass ?> flex items-center justify-center font-bold text-white text-sm uppercase shrink-0">
              <?= htmlspecialchars($initials) ?>
            </div>
            <div class="overflow-hidden">
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($review['full_name']) ?></p>
                <?php if ($review['is_verified_purchase']): ?>
                  <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-orange-500 text-white rounded-full">✓ Verified</span>
                <?php endif; ?>
              </div>
              <p class="text-xs text-slate-800 truncate">
                <?= htmlspecialchars($review['position'] ?? 'Customer') ?>
                <?php if (!empty($review['company'])): ?> · <?= htmlspecialchars($review['company']) ?><?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- View All CTA -->
    <div class="mt-10 text-center">
      <a href="reviews.php" class="inline-flex items-center gap-2 px-8 py-3.5 bg-orange-600 text-white font-semibold rounded-xl hover:bg-orange-700 transition-colors shadow-sm hover:shadow-md">
        View All Reviews
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14M15 16l4-4M15 8l4 4"/>
        </svg>
      </a>
    </div>

    <?php else: ?>
    <!-- No reviews placeholder -->
    <div class="text-center py-16">
      <div class="size-20 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-10 h-10 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-800">No reviews yet</h3>
      <p class="text-gray-500 mt-1">Be the first to share your experience!</p>
    </div>
    <?php endif; ?>

    <!-- Stats Strip -->
    <div class="mt-20 pt-12 border-t border-orange-200 grid gap-8 grid-cols-2 lg:grid-cols-3">
      <div class="text-center">
        <p class="text-4xl sm:text-6xl font-black text-orange-600">99.95%</p>
        <p class="mt-2 text-sm font-semibold text-gray-700">Order Accuracy Rate</p>
        <p class="text-xs text-gray-400 mt-0.5">in fulfilling every order correctly</p>
      </div>
      <div class="text-center">
        <p class="text-4xl sm:text-6xl font-black text-orange-600"><?= number_format($orders['total_orders'] + 10000) ?>+</p>
        <p class="mt-2 text-sm font-semibold text-gray-700">Orders Fulfilled</p>
        <p class="text-xs text-gray-400 mt-0.5">successful deliveries and counting</p>
      </div>
      <div class="text-center col-span-2 lg:col-span-1">
        <p class="text-4xl sm:text-6xl font-black text-orange-600"><?= number_format($customers['total_customers'] + 2000) ?>+</p>
        <p class="mt-2 text-sm font-semibold text-gray-700">Happy Customers</p>
        <p class="text-xs text-gray-400 mt-0.5">families and businesses served</p>
      </div>
    </div>

    <!-- Decorative SVG -->
    <div class="absolute bottom-0 end-0 transform lg:translate-x-32 pointer-events-none" aria-hidden="true">
      <svg class="w-40 h-auto sm:w-72 opacity-30" width="1115" height="636" viewBox="0 0 1115 636" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0.990203 279.321C-1.11035 287.334 3.68307 295.534 11.6966 297.634L142.285 331.865C150.298 333.965 158.497 329.172 160.598 321.158C162.699 313.145 157.905 304.946 149.892 302.845L33.8132 272.418L64.2403 156.339C66.3409 148.326 61.5475 140.127 53.5339 138.026C45.5204 135.926 37.3213 140.719 35.2207 148.733L0.990203 279.321ZM424.31 252.289C431.581 256.26 440.694 253.585 444.664 246.314C448.635 239.044 445.961 229.931 438.69 225.96L424.31 252.289ZM23.0706 296.074C72.7581 267.025 123.056 230.059 187.043 212.864C249.583 196.057 325.63 198.393 424.31 252.289L438.69 225.96C333.77 168.656 249.817 164.929 179.257 183.892C110.144 202.465 54.2419 243.099 7.92943 270.175L23.0706 296.074Z" fill="currentColor" class="fill-orange-400"/>
        <path d="M451.609 382.417C446.219 388.708 446.95 398.178 453.241 403.567L555.763 491.398C562.054 496.788 571.524 496.057 576.913 489.766C582.303 483.474 581.572 474.005 575.281 468.615L484.15 390.544L562.222 299.413C567.612 293.122 566.881 283.652 560.59 278.263C554.299 272.873 544.829 273.604 539.44 279.895L451.609 382.417ZM837.202 559.655C841.706 566.608 850.994 568.593 857.947 564.09C864.9 559.586 866.885 550.298 862.381 543.345L837.202 559.655ZM464.154 407.131C508.387 403.718 570.802 395.25 638.136 410.928C704.591 426.401 776.318 465.66 837.202 559.655L862.381 543.345C797.144 442.631 718.724 398.89 644.939 381.709C572.033 364.734 504.114 373.958 461.846 377.22L464.154 407.131Z" fill="currentColor" class="fill-amber-400"/>
        <path d="M447.448 0.194357C439.203-0.605554 431.87 5.43034 431.07 13.6759L418.035 148.045C417.235 156.291 423.271 163.623 431.516 164.423C439.762 165.223 447.095 159.187 447.895 150.942L459.482 31.5025L578.921 43.0895C587.166 43.8894 594.499 37.8535 595.299 29.6079C596.099 21.3624 590.063 14.0296 581.818 13.2297L447.448 0.194357Z" fill="currentColor" class="fill-orange-300"/>
      </svg>
    </div>

  </div>
</div>
<!-- ====== END TESTIMONIALS SECTION ====== -->