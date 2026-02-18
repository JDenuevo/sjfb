<?php
$sql = "SELECT 
        r.review_id,
        r.full_name,
        r.position,
        r.company,
        r.rating,
        r.feedback,
        r.created_at,
        p.product_name,
        (SELECT COUNT(*) FROM review_attachments ra WHERE ra.review_id = r.review_id) as attachment_count,
        LEFT(r.full_name, 1) as first_initial,
        SUBSTRING_INDEX(r.full_name, ' ', -1) as last_name
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 6";

$result = $conn->query($sql);
$reviews = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Get overall statistics
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

// Get total orders count for stats
$orders_sql = "SELECT COUNT(DISTINCT order_id) as total_orders FROM orders WHERE order_status = 'Delivered'";
$orders_result = $conn->query($orders_sql);
$orders = $orders_result ? $orders_result->fetch_assoc() : [];

// Get unique customers count
$customers_sql = "SELECT COUNT(DISTINCT account_id) as total_customers FROM orders WHERE order_status = 'Delivered' AND account_id IS NOT NULL";
$customers_result = $conn->query($customers_sql);
$customers = $customers_result ? $customers_result->fetch_assoc() : [];

// Set default values if no data yet
if (empty($stats)) {
    $stats = [
        'avg_rating' => 0,
        'total_reviews' => 0,
        'five_star' => 0,
        'four_star' => 0,
        'three_star' => 0,
        'two_star' => 0,
        'one_star' => 0
    ];
}

if (empty($orders)) {
    $orders = ['total_orders' => 2000];
}

if (empty($customers)) {
    $customers = ['total_customers' => 1000];
}
?>

<!-- Testimonials -->
<div class="overflow-hidden bg-orange-100">
  <div class="relative max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <!-- Title -->
    <div class="max-w-2xl w-3/4 lg:w-1/2 mb-6 sm:mb-10 md:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl text-slate-500 font-semibold">
        Loved by businesses and individuals across the globe
      </h2>
      
      <!-- Rating Summary -->
      <div class="mt-4 flex items-center gap-4">
        <div class="flex items-center">
          <span class="text-3xl font-bold text-orange-600"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></span>
          <span class="text-gray-600 ml-1">/5.0</span>
        </div>
        <div class="flex items-center">
          <?php 
          $avg_rating = round($stats['avg_rating'] ?? 0);
          for($i = 1; $i <= 5; $i++): 
          ?>
            <svg class="w-5 h-5 <?php echo $i <= $avg_rating ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
          <?php endfor; ?>
          <span class="text-gray-600 ml-2">(<?php echo number_format($stats['total_reviews'] ?? 0); ?> reviews)</span>
        </div>
      </div>
    </div>
    <!-- End Title -->

    <!-- Latest 8 Reviews Grid -->
    <?php if (!empty($reviews)): ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($reviews as $review): ?>
      <!-- Card -->
      <div class="flex h-auto">
        <div class="flex flex-col bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow w-full">
          <div class="flex-auto p-4 md:p-6">
            <!-- Star Rating -->
            <div class="flex justify-between items-center mb-3">
              <div class="flex items-center">
                <?php for($i = 1; $i <= 5; $i++): ?>
                  <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                <?php endfor; ?>
              </div>
              <div>
                <?php if (!empty($review['product_name'])): ?>
                  <span class="ml-2 text-xs text-gray-500">for <?php echo htmlspecialchars($review['product_name']); ?></span>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Quote Icon & Feedback -->
            <p class="text-base italic md:text-lg text-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="inline-block text-orange-400 mr-1">
                <path d="M9 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z" />
                <path d="M18 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z" />
              </svg>
              <?php echo htmlspecialchars(substr($review['feedback'], 0, 150)) . (strlen($review['feedback']) > 150 ? '...' : ''); ?>
            </p>
            
            <!-- Attachment Indicator -->
            <?php if (!empty($review['attachment_count']) && $review['attachment_count'] > 0): ?>
            <div class="mt-2 flex items-center text-xs text-gray-500">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
              <?php echo $review['attachment_count']; ?> photo<?php echo $review['attachment_count'] > 1 ? 's' : ''; ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="p-4 bg-orange-400 rounded-b-xl md:px-7">
            <div class="flex items-center gap-x-3">
              <!-- Avatar with Initials -->
              <div class="size-8 sm:size-11.5 rounded-full bg-white flex items-center justify-center font-semibold text-orange-600 uppercase">
                <?php 
                $initials = $review['first_initial'];
                if (!empty($review['last_name'])) {
                    $initials .= substr($review['last_name'], 0, 1);
                }
                echo htmlspecialchars($initials); 
                ?>
              </div>

              <div class="grow">
                <p class="text-sm sm:text-base font-semibold text-white">
                  <?php echo htmlspecialchars($review['full_name']); ?>
                </p>
                <p class="text-xs text-white/90">
                  <?php 
                    echo htmlspecialchars($review['position'] ?? 'Customer');
                    if (!empty($review['company'])) {
                      echo ' | ' . htmlspecialchars($review['company']);
                    }
                  ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- End Card -->
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- No Reviews Yet -->
    <div class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
      </svg>
      <h3 class="mt-4 text-lg font-medium text-gray-900">No reviews yet</h3>
      <p class="mt-1 text-gray-500">Be the first to leave a review!</p>
    </div>
    <?php endif; ?>
    <!-- End Grid -->

    <!-- View All Reviews Link -->
    <?php if (!empty($reviews)): ?>
    <div class="mt-8 text-center">
      <a href="reviews.php" class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors">
        View All Reviews
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-narrow-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M15 16l4 -4" /><path d="M15 8l4 4" /></svg>
      </a>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="mt-20 grid gap-6 grid-cols-2 sm:gap-12 lg:grid-cols-3 lg:gap-8">
      <!-- Stats -->
      <div>
        <h4 class="text-lg sm:text-xl font-semibold text-slate-500">Accuracy rate</h4>
        <p class="mt-2 sm:mt-3 text-4xl sm:text-6xl font-bold text-orange-600">99.95%</p>
        <p class="mt-1 text-slate-400">in fulfilling orders</p>
      </div>

      <!-- Stats -->
      <div>
        <h4 class="text-lg sm:text-xl font-semibold text-slate-500">Orders fulfilled</h4>
        <p class="mt-2 sm:mt-3 text-4xl sm:text-6xl font-bold text-orange-600">10<?php echo number_format($orders['total_orders']); ?>+</p>
        <p class="mt-1 text-slate-400">successful deliveries</p>
      </div>

      <!-- Stats -->
      <div>
        <h4 class="text-lg sm:text-xl font-semibold text-slate-500">Happy customers</h4>
        <p class="mt-2 sm:mt-3 text-4xl sm:text-6xl font-bold text-orange-600">2,00<?php echo number_format($customers['total_customers']); ?>+</p>
        <p class="mt-1 text-slate-400">and growing daily</p>
      </div>
    </div>
    <!-- End Grid -->
  </div>
</div>
<!-- End Testimonials -->