<?php
session_start();
include 'conn.php';

$pageTitle = 'Reviews';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filter by rating
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT r.review_id) as total 
              FROM reviews r 
              WHERE r.status = 'approved'";

if ($rating_filter >= 1 && $rating_filter <= 5) {
    $count_sql .= " AND r.rating = $rating_filter";
}

$count_result = $conn->query($count_sql);
$total_reviews = 0;
if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_reviews = $row['total'];
}
$total_pages = ceil($total_reviews / $limit);

// Get reviews with pagination
$sql = "SELECT 
            r.review_id,
            r.full_name,
            r.position,
            r.company,
            r.rating,
            r.feedback,
            r.created_at,
            r.is_verified_purchase,
            p.product_name,
            p.product_id,
            GROUP_CONCAT(DISTINCT ra.file_path ORDER BY ra.upload_order SEPARATOR '|') as attachment_paths,
            COUNT(DISTINCT ra.attachment_id) as attachment_count,
            LEFT(r.full_name, 1) as first_initial,
            SUBSTRING_INDEX(r.full_name, ' ', -1) as last_name,
            (SELECT COUNT(*) FROM reviews rh WHERE rh.review_id = r.review_id) as helpful_count
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.product_id
        LEFT JOIN review_attachments ra ON r.review_id = ra.review_id
        WHERE r.status = 'approved'";

if ($rating_filter >= 1 && $rating_filter <= 5) {
    $sql .= " AND r.rating = $rating_filter";
}

$sql .= " GROUP BY r.review_id
          ORDER BY r.created_at DESC
          LIMIT $offset, $limit";

$result = $conn->query($sql);
$reviews = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Get rating distribution
$dist_sql = "SELECT 
                rating,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM reviews WHERE status = 'approved'), 1) as percentage
              FROM reviews 
              WHERE status = 'approved'
              GROUP BY rating
              ORDER BY rating DESC";

$dist_result = $conn->query($dist_sql);
$rating_distribution = [];
if ($dist_result && $dist_result->num_rows > 0) {
    while ($row = $dist_result->fetch_assoc()) {
        $rating_distribution[] = $row;
    }
}

// Get overall stats
$stats_sql = "SELECT 
                COUNT(DISTINCT review_id) as total_reviews,
                COALESCE(AVG(rating), 0) as avg_rating,
                COUNT(DISTINCT account_id) as unique_reviewers
              FROM reviews 
              WHERE status = 'approved'";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];
if (empty($stats)) {
    $stats = [
        'total_reviews' => 0,
        'avg_rating' => 0,
        'unique_reviewers' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth"> 

<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  
  <title><?= $pageTitle ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. - Providing professional fish brokerage services with excellence and integrity.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>
<style>
    body { font-family: 'Lexend', sans-serif; }
.font-display { font-family: 'Playfair Display', serif; }

  @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
  }
  .review-card {
      animation: fadeIn 0.6s ease-out;
  }
  .line-clamp-4 {
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
      overflow: hidden;
  }
</style>
<body class="bg-gray-50">
    <?php include './components/navigation.php'; ?>

    <?php include './components/nav_crumb.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Customer Reviews</h1>
            <p class="text-lg text-gray-600">See what our customers are saying about our products</p>
        </div>

        <!-- Overall Rating Summary -->
        <div class="bg-white rounded-2xl shadow-sm p-8 mb-10">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Average Rating -->
                <div class="text-center md:text-left">
                    <div class="text-6xl font-bold text-orange-600 mb-2">
                        <?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>
                    </div>
                    <div class="flex justify-center md:justify-start items-center mb-2">
                        <?php 
                        $avg_rating = round($stats['avg_rating'] ?? 0);
                        for($i = 1; $i <= 5; $i++): 
                        ?>
                            <svg class="w-5 h-5 <?php echo $i <= $avg_rating ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <p class="text-gray-500">Based on <?php echo number_format($stats['total_reviews'] ?? 0); ?> reviews</p>
                </div>

                <!-- Rating Distribution -->
                <div class="md:col-span-2">
                    <?php 
                    $ratings = [5,4,3,2,1];
                    $rating_data = [];
                    foreach($rating_distribution as $dist) {
                        $rating_data[$dist['rating']] = $dist;
                    }
                    
                    foreach($ratings as $rating): 
                        $count = isset($rating_data[$rating]) ? $rating_data[$rating]['count'] : 0;
                        $percentage = isset($rating_data[$rating]) ? $rating_data[$rating]['percentage'] : 0;
                    ?>
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-medium text-gray-600 w-12"><?php echo $rating; ?> ★</span>
                        <div class="flex-1 mx-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <span class="text-sm text-gray-600 w-20"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-8 flex flex-wrap items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-gray-700 font-medium">Filter by:</span>
                <a href="reviews.php" class="px-4 py-2 rounded-lg <?php echo !$rating_filter ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                    All
                </a>
                <?php for($i = 5; $i >= 1; $i--): ?>
                <a href="?rating=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $rating_filter == $i ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition flex items-center">
                    <?php echo $i; ?> ★
                </a>
                <?php endfor; ?>
            </div>
            <div class="text-sm text-gray-600">
                Showing <?php echo $total_reviews > 0 ? min($offset + 1, $total_reviews) : 0; ?> - <?php echo min($offset + $limit, $total_reviews); ?> of <?php echo $total_reviews; ?> reviews
            </div>
        </div>

        <!-- Reviews Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                <div class="review-card bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <div class="p-6">
                        <!-- Rating -->
                        <div class="flex justify-between items-center mb-3">
                          <div class="flex items-center">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                          </div>
                          <div>
                            <?php if ($review['is_verified_purchase']): ?>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Verified
                            </span>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Product Name -->
                        <?php if (!empty($review['product_name'])): ?>
                        <div class="mb-2">
                            <a href="<?php echo $baseUrl; ?>item/<?php echo urlencode(strtolower(str_replace(' ', '-', $review['product_name']))); ?>" class="text-xs text-orange-600 hover:underline">
                                Product: <?php echo htmlspecialchars($review['product_name']); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Review Text -->
                        <p class="text-gray-700 mb-4 line-clamp-4">
                            <?php echo nl2br(htmlspecialchars($review['feedback'])); ?>
                        </p>

                        <!-- Attachments Preview -->
                        <?php if (!empty($review['attachment_count']) && $review['attachment_count'] > 0): ?>
                        <div class="mb-4 flex flex-wrap gap-2">
                            <?php 
                            $attachments = explode('|', $review['attachment_paths']);
                            $display_count = 0;
                            foreach($attachments as $attachment):
                                if($display_count >= 3) break;
                                if(empty($attachment)) continue;
                                $display_count++;
                            ?>
                            <div class="relative w-16 h-16 bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition" onclick="openImageModal('<?php echo $attachment; ?>')">
                                <img src="<?php echo $attachment; ?>" alt="Review attachment" class="w-full h-full object-cover">
                            </div>
                            <?php endforeach; ?>
                            <?php if ($review['attachment_count'] > 3): ?>
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 text-sm font-medium">
                                +<?php echo $review['attachment_count'] - 3; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Reviewer Info -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center font-semibold text-orange-600 uppercase">
                                    <?php 
                                    $initials = $review['first_initial'];
                                    if (!empty($review['last_name'])) {
                                        $initials .= substr($review['last_name'], 0, 1);
                                    }
                                    echo htmlspecialchars($initials); 
                                    ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($review['full_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php 
                                            echo htmlspecialchars($review['position'] ?? 'Customer');
                                            if (!empty($review['company'])) {
                                                echo ' · ' . htmlspecialchars($review['company']);
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-xs text-gray-400">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>

                        <!-- Helpful Button -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <button onclick="markHelpful(<?php echo $review['review_id']; ?>)" class="flex items-center text-xs text-gray-500 hover:text-orange-600 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                Helpful (<?php echo $review['helpful_count'] ?? 0; ?>)
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No reviews yet</h3>
                    <p class="mt-1 text-gray-500">Be the first to leave a review!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-12 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?>" 
                       class="px-4 py-2 <?php echo $i == $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?> rounded-lg">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50 flex items-center justify-center" onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-screen p-4">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img id="modalImage" src="" alt="Review attachment" class="max-w-full max-h-screen object-contain">
        </div>
    </div>

    <script>
    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function markHelpful(reviewId) {
        fetch('ajax/mark_helpful.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'review_id=' + reviewId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const btn = event.currentTarget;
                const countSpan = btn.querySelector('span');
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent.match(/\d+/)[0]);
                    countSpan.textContent = ' Helpful (' + (currentCount + 1) + ')';
                }
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                alert(data.message || 'You already marked this review as helpful');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    </script>

    <?php include './components/footer.php'; ?>

</body>
</html>