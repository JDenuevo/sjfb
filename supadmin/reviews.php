<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$page          = isset($_GET['page'])       ? (int)$_GET['page']       : 1;
$perPage       = 10;
$search        = isset($_GET['search'])     ? trim($_GET['search'])     : '';
$filterStatus  = isset($_GET['status'])     ? $_GET['status']           : '';
$filterRating  = isset($_GET['rating'])     ? (int)$_GET['rating']      : 0;
$filterProduct = isset($_GET['product_id']) ? (int)$_GET['product_id']  : 0;

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = '';

if (!empty($search)) {
    $where   .= " AND (r.full_name LIKE ? OR r.feedback LIKE ? OR p.product_name LIKE ?)";
    $s        = "%$search%";
    $params   = array_merge($params, [$s, $s, $s]);
    $types   .= 'sss';
}
if (!empty($filterStatus)) {
    $where   .= " AND r.status = ?";
    $params[] = $filterStatus;
    $types   .= 's';
}
if ($filterRating > 0) {
    $where   .= " AND r.rating = ?";
    $params[] = $filterRating;
    $types   .= 'i';
}
if ($filterProduct > 0) {
    $where   .= " AND r.product_id = ?";
    $params[] = $filterProduct;
    $types   .= 'i';
}

// ── Count distinct orders (one row per order in the table) ────────────────────
$countQuery = "
    SELECT COUNT(DISTINCT r.order_id) as total
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    $where
";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$countStmt->close();

// ── Fetch one representative row per order (for the table display) ────────────
// Shows: reviewer name, order_id, number of products, lowest status, avg rating,
//        total attachment count, date of first review in order.
$offset = ($page - 1) * $perPage;
$query  = "
    SELECT
        r.order_id,
        r.full_name,
        r.email,
        r.is_verified_purchase,
        r.created_at,
        COUNT(DISTINCT r.review_id)   AS review_count,
        COUNT(DISTINCT r.product_id)  AS product_count,
        ROUND(AVG(r.rating), 1)       AS avg_rating,
        -- roll-up status: if any pending → pending; else if any approved → approved; else rejected/spam
        CASE
            WHEN SUM(r.status = 'pending')  > 0 THEN 'pending'
            WHEN SUM(r.status = 'approved') > 0 THEN 'approved'
            WHEN SUM(r.status = 'rejected') > 0 THEN 'rejected'
            ELSE 'spam'
        END AS rolled_status,
        GROUP_CONCAT(DISTINCT p.product_name ORDER BY p.product_name SEPARATOR ', ') AS product_names,
        SUM(ra_count.cnt) AS attachment_count
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    LEFT JOIN (
        SELECT review_id, COUNT(*) AS cnt FROM review_attachments GROUP BY review_id
    ) ra_count ON ra_count.review_id = r.review_id
    $where
    GROUP BY r.order_id
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ── Stats ─────────────────────────────────────────────────────────────────────
$statsResult = $conn->query("SELECT
  COUNT(*) as total,
  SUM(status='pending')  as pending,
  SUM(status='approved') as approved,
  SUM(status='rejected') as rejected,
  SUM(status='spam')     as spam,
  ROUND(AVG(rating),1)   as avg_rating
  FROM reviews");
$stats = $statsResult->fetch_assoc();

$productsResult = $conn->query("SELECT product_id, product_name FROM products WHERE is_deleted = 0 ORDER BY product_name");
$allProducts = [];
while ($p = $productsResult->fetch_assoc()) $allProducts[] = $p;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews | St. Joseph Fish Brokerage Inc.</title>
  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  
</head>

<style>
  select[multiple] { appearance: none; -webkit-appearance: none; background-image: none; }

  .product-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
  .product-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

  .modal-overlay {
    position: fixed; inset: 0; z-index: 999;
    display: flex; align-items: flex-start; justify-content: center;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    overflow-y: auto;
    padding: 2rem 1rem;
  }
  .modal-overlay.hidden { display: none; }

  .modal-box {
    background: white;
    width: 100%; max-width: 56rem;
    border-radius: 1.25rem;
    box-shadow: 0 25px 60px rgba(0,0,0,0.2);
    overflow: hidden;
  }

  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
  }
  .modal-header h3 { font-size: 1.125rem; font-weight: 700; color: #111827; }
  .modal-header p { font-size: 0.75rem; color: #6b7280; margin-top: 1px; }

  .modal-close {
    width: 2rem; height: 2rem;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; background: #f3f4f6;
    color: #6b7280; border: none; cursor: pointer;
    transition: background 0.15s, color 0.15s;
  }
  .modal-close:hover { background: #fee2e2; color: #dc2626; }

  .modal-body { padding: 1.5rem; max-height: 75vh; overflow-y: auto; }
  .modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex; justify-content: flex-end; gap: 0.625rem;
  }

  .form-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #374151; margin-bottom: 0.375rem; }
  .form-input {
    width: 100%; padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb; border-radius: 0.5rem;
    font-size: 0.875rem; color: #111827;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
  }
  .form-input:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,0.1); }

  .review-row { transition: all 0.2s ease; border-left: 4px solid transparent; }
  .review-row:hover { border-left-color: #f97316; background-color: #fffbf7; }

</style>
<body class="bg-gray-50">
  <?php include('./components/header.php'); ?>

  <!-- Mobile nav toggle -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <button type="button" class="size-8 flex justify-center items-center border border-gray-200 text-gray-800 rounded-lg"
        aria-haspopup="dialog" data-hs-overlay="#hs-application-sidebar">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/>
        </svg>
      </button>
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">Navigation
          <svg class="shrink-0 mx-3 size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14"
              stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate">Reviews</li>
      </ol>
    </div>
  </div>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4">

      <?php if (!empty($_SESSION['message'])):
        $msg = $_SESSION['message'];
        $cls = $msg['type'] === 'success' ? 'bg-teal-500' : 'bg-red-500';
        echo "<div class='mt-2 $cls text-white text-sm rounded-lg p-4'><span class='font-bold'>"
           . ucfirst($msg['type']) . "!</span> " . htmlspecialchars($msg['text']) . "</div>";
        unset($_SESSION['message']);
      endif; ?>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
          <p class="text-xs text-gray-500 mt-1">Total Reviews</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-yellow-700"><?= $stats['pending'] ?></p>
          <p class="text-xs text-yellow-600 mt-1">Pending</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-green-700"><?= $stats['approved'] ?></p>
          <p class="text-xs text-green-600 mt-1">Approved</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-red-700"><?= $stats['rejected'] ?></p>
          <p class="text-xs text-red-600 mt-1">Rejected</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-gray-600"><?= $stats['spam'] ?></p>
          <p class="text-xs text-gray-500 mt-1">Spam</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center shadow-sm">
          <p class="text-2xl font-bold text-orange-600"><?= $stats['avg_rating'] ?? '—' ?>★</p>
          <p class="text-xs text-orange-500 mt-1">Avg Rating</p>
        </div>
      </div>

      <!-- Table Card -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">⭐ Customer Reviews</h2>
              <p class="text-sm text-gray-500">Moderate and manage customer feedback</p>
            </div>
          </div>

          <form method="GET" class="mt-4 flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
              placeholder="Search name, feedback, product..."
              class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-full sm:w-64">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-48">
              <option value="">All Status</option>
              <option value="pending"  <?= $filterStatus === 'pending'  ? 'selected' : '' ?>>Pending</option>
              <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
              <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
              <option value="spam"     <?= $filterStatus === 'spam'     ? 'selected' : '' ?>>Spam</option>
            </select>
            <select name="rating" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-48">
              <option value="">All Ratings</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= $filterRating === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
              <?php endfor; ?>
            </select>
            <select name="product_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-48">
              <option value="">All Products</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= $p['product_id'] ?>" <?= $filterProduct == $p['product_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['product_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Filter</button>
            <?php if (!empty($search) || !empty($filterStatus) || $filterRating || $filterProduct): ?>
              <a href="reviews.php" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">Clear</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="ps-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Reviewer</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Products</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Avg Rating</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Reviews</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Status</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Date</th>
                <th class="px-6 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                  $sc = match($row['rolled_status']) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    'spam'     => 'bg-gray-100 text-gray-600',
                    default    => 'bg-yellow-100 text-yellow-700'
                  };
                  $si = match($row['rolled_status']) {
                    'approved' => '✓', 'rejected' => '✗', 'spam' => '⚑', default => '●'
                  };
                ?>
                <tr class="review-row bg-white">

                  <!-- Reviewer -->
                  <td class="ps-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="size-9 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm uppercase select-none">
                        <?php
                          $n = $row['full_name'];
                          echo htmlspecialchars(substr($n,0,1) . (strpos($n,' ')!==false ? substr(strrchr($n,' '),1,1) : ''));
                        ?>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['full_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($row['email'] ?? '') ?></p>
                        <?php if ($row['is_verified_purchase']): ?>
                          <span class="text-xs text-green-600 font-medium">✓ Verified</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>

                  <!-- Products -->
                  <td class="px-6 py-4 max-w-[200px]">
                    <p class="text-xs text-gray-700 leading-relaxed line-clamp-2">
                      <?= htmlspecialchars($row['product_names'] ?? 'N/A') ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1"><?= $row['product_count'] ?> product<?= $row['product_count'] != 1 ? 's' : '' ?></p>
                    <?php if ($row['attachment_count'] > 0): ?>
                      <span class="text-xs text-gray-400">📎 <?= $row['attachment_count'] ?> photo<?= $row['attachment_count'] > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                  </td>

                  <!-- Avg Rating -->
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-0.5">
                      <?php
                        $avg = round($row['avg_rating']);
                        for ($i = 1; $i <= 5; $i++):
                      ?>
                        <span class="text-sm <?= $i <= $avg ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
                      <?php endfor; ?>
                    </div>
                    <span class="text-xs text-gray-500"><?= $row['avg_rating'] ?>/5</span>
                  </td>

                  <!-- Review count -->
                  <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-gray-700"><?= $row['review_count'] ?></span>
                    <span class="text-xs text-gray-400 block">review<?= $row['review_count'] != 1 ? 's' : '' ?></span>
                  </td>

                  <!-- Status (rolled up) -->
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full font-medium <?= $sc ?>">
                      <?= $si ?> <?= ucfirst($row['rolled_status']) ?>
                    </span>
                  </td>

                  <!-- Date -->
                  <td class="px-6 py-4">
                    <span class="text-sm text-gray-500"><?= date("M j, Y", strtotime($row['created_at'])) ?></span>
                  </td>

                  <!-- Actions — pass order_id to modal -->
                  <td class="px-6 py-4">
                    <div class="flex justify-end">
                      <button onclick="openViewReviewModal(<?= intval($row['order_id']) ?>)"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-orange-50 hover:text-orange-700 text-xs font-medium transition">
                        👁 View All
                      </button>
                    </div>
                  </td>

                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="px-6 py-16 text-center">
                    <span class="text-5xl block mb-3">⭐</span>
                    <p class="text-gray-500 font-medium">No reviews found</p>
                    <p class="text-gray-400 text-sm mt-1">Customer reviews will appear here once submitted</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 flex justify-between items-center border-t border-gray-200">
          <p class="text-sm text-gray-600"><span class="font-semibold text-gray-800"><?= $totalItems ?></span> orders with reviews</p>
          <div class="inline-flex gap-x-2">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&rating=<?= $filterRating ?>&product_id=<?= $filterProduct ?>"
                class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">← Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
              <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&rating=<?= $filterRating ?>&product_id=<?= $filterProduct ?>"
                class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 <?= $i == $page ? 'bg-orange-500 text-white' : 'bg-white text-gray-800 hover:bg-gray-50' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&rating=<?= $filterRating ?>&product_id=<?= $filterProduct ?>"
                class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">Next →</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ==================== ADD PRODUCT MODAL ==================== -->
  <div id="viewReviewModal" class="modal-overlay hidden">      
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Product Reviews</h3>
          <p>Manage your reviews</p>
        </div>
        <button onclick="document.getElementById('viewReviewModal').classList.add('hidden')" class="modal-close">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>  
        </button>
      </div>

      <div class="modal-body">
        <div id="viewReviewContent">
          <!-- Loaded via fetch -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Close modal when clicking the backdrop
    document.getElementById('viewReviewModal').addEventListener('click', function(e) {
      if (e.target === this) this.classList.add('hidden');
    });

    function openViewReviewModal(orderId) {
      const content = document.getElementById('viewReviewContent');
      content.innerHTML = '<div class="p-10 text-center text-gray-400 text-sm">Loading...</div>';
      document.getElementById('viewReviewModal').classList.remove('hidden');

      fetch(`./functions/fetch_reviews.php?order_id=${orderId}`)
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p class="p-6 text-red-500 text-sm">Failed to load reviews.</p>'; });
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <?php $conn->close(); ?>
</body>
</html>