<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterRating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$filterProduct = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $where .= " AND (r.full_name LIKE ? OR r.feedback LIKE ? OR p.product_name LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= 'sss';
}
if (!empty($filterStatus)) {
    $where .= " AND r.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}
if ($filterRating > 0) {
    $where .= " AND r.rating = ?";
    $params[] = $filterRating;
    $types .= 'i';
}
if ($filterProduct > 0) {
    $where .= " AND r.product_id = ?";
    $params[] = $filterProduct;
    $types .= 'i';
}

$countQuery = "SELECT COUNT(*) as total FROM reviews r LEFT JOIN products p ON r.product_id = p.product_id $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$countStmt->close();

$offset = ($page - 1) * $perPage;
$query = "SELECT r.*, p.product_name,
          (SELECT COUNT(*) FROM review_attachments ra WHERE ra.review_id = r.review_id) as attachment_count
          FROM reviews r
          LEFT JOIN products p ON r.product_id = p.product_id
          $where
          ORDER BY r.created_at DESC
          LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Stats
$statsResult = $conn->query("SELECT 
  COUNT(*) as total,
  SUM(status='pending') as pending,
  SUM(status='approved') as approved,
  SUM(status='rejected') as rejected,
  SUM(status='spam') as spam,
  ROUND(AVG(rating),1) as avg_rating
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
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>
<body class="bg-gray-50">
  <?php include('./components/header.php'); ?>

  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <button type="button" class="size-8 flex justify-center items-center border border-gray-200 text-gray-800 rounded-lg" aria-haspopup="dialog" data-hs-overlay="#hs-application-sidebar">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/>
        </svg>
      </button>
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">Navigation
          <svg class="shrink-0 mx-3 size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
        echo "<div class='mt-2 $cls text-white text-sm rounded-lg p-4'><span class='font-bold'>" . ucfirst($msg['type']) . "!</span> " . htmlspecialchars($msg['text']) . "</div>";
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
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, feedback, product..."
              class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-full sm:w-64">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
              <option value="">All Status</option>
              <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
              <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
              <option value="spam" <?= $filterStatus === 'spam' ? 'selected' : '' ?>>Spam</option>
            </select>
            <select name="rating" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
              <option value="">All Ratings</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= $filterRating === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
              <?php endfor; ?>
            </select>
            <select name="product_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
              <option value="">All Products</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= $p['product_id'] ?>" <?= $filterProduct == $p['product_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Filter</button>
            <?php if (!empty($search) || !empty($filterStatus) || $filterRating || $filterProduct): ?>
              <a href="reviews.php" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">Clear</a>
            <?php endif; ?>
          </form>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="ps-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Reviewer</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Product</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Rating</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Feedback</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Status</th>
                <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Date</th>
                <th class="px-6 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="review-row bg-white">
                  <td class="ps-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="size-9 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm uppercase">
                        <?= substr($row['full_name'], 0, 1) . (strpos($row['full_name'], ' ') !== false ? substr(strrchr($row['full_name'], ' '), 1, 1) : '') ?>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['full_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= !empty($row['position']) ? htmlspecialchars($row['position']) : 'Customer' ?>
                          <?= !empty($row['company']) ? '· ' . htmlspecialchars($row['company']) : '' ?></p>
                        <?php if ($row['is_verified_purchase']): ?>
                          <span class="text-xs text-green-600 font-medium">✓ Verified</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs bg-orange-50 text-orange-700 rounded-full border border-orange-200">
                      <?= htmlspecialchars($row['product_name'] ?? 'N/A') ?>
                    </span>
                    <?php if ($row['attachment_count'] > 0): ?>
                      <span class="block text-xs text-gray-400 mt-1">📎 <?= $row['attachment_count'] ?> photo<?= $row['attachment_count'] > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="text-sm <?= $i <= $row['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
                      <?php endfor; ?>
                    </div>
                    <span class="text-xs text-gray-500"><?= $row['rating'] ?>/5</span>
                  </td>
                  <td class="px-6 py-4 max-w-xs">
                    <p class="text-sm text-gray-700 line-clamp-2"><?= htmlspecialchars(substr($row['feedback'], 0, 100)) . (strlen($row['feedback']) > 100 ? '...' : '') ?></p>
                  </td>
                  <td class="px-6 py-4">
                    <?php
                    $statusClass = match($row['status']) {
                      'approved' => 'bg-green-100 text-green-700',
                      'rejected' => 'bg-red-100 text-red-700',
                      'spam' => 'bg-gray-100 text-gray-600',
                      default => 'bg-yellow-100 text-yellow-700'
                    };
                    $statusIcon = match($row['status']) {
                      'approved' => '✓', 'rejected' => '✗', 'spam' => '⚑', default => '●'
                    };
                    ?>
                    <span class="px-2 py-1 text-xs rounded-full font-medium <?= $statusClass ?>">
                      <?= $statusIcon ?> <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-sm text-gray-500"><?= date("M j, Y", strtotime($row['created_at'])) ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end gap-1 flex-wrap">
                      <button onclick="openViewReviewModal(<?= $row['review_id'] ?>)"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-medium">👁 View</button>
                      <?php if ($row['status'] !== 'approved'): ?>
                        <form action="./functions/update_review_status.php" method="POST" class="inline">
                          <input type="hidden" name="review_id" value="<?= $row['review_id'] ?>">
                          <input type="hidden" name="status" value="approved">
                          <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium">✓ Approve</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($row['status'] !== 'rejected'): ?>
                        <form action="./functions/update_review_status.php" method="POST" class="inline">
                          <input type="hidden" name="review_id" value="<?= $row['review_id'] ?>">
                          <input type="hidden" name="status" value="rejected">
                          <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium">✗ Reject</button>
                        </form>
                      <?php endif; ?>
                      <?php if ($row['status'] !== 'spam'): ?>
                        <form action="./functions/update_review_status.php" method="POST" class="inline">
                          <input type="hidden" name="review_id" value="<?= $row['review_id'] ?>">
                          <input type="hidden" name="status" value="spam">
                          <button type="submit" class="px-3 py-1.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-xs font-medium">⚑ Spam</button>
                        </form>
                      <?php endif; ?>
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
          <p class="text-sm text-gray-600"><span class="font-semibold text-gray-800"><?= $totalItems ?></span> results</p>
          <div class="inline-flex gap-x-2">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&rating=<?= $filterRating ?>&product_id=<?= $filterProduct ?>"
                class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">← Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
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

  <!-- VIEW REVIEW MODAL -->
  <div id="viewReviewModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">
      <div id="viewReviewContent" class="p-6"></div>
    </div>
  </div>

  <script>
    function openViewReviewModal(id) {
      fetch(`./functions/fetch_reviews.php?review_id=${id}`)
        .then(r => r.text())
        .then(html => {
          document.getElementById('viewReviewContent').innerHTML = html;
          document.getElementById('viewReviewModal').classList.remove('hidden');
        });
    }
  </script>

  <style>
    .review-row { transition: all 0.2s ease; border-left: 4px solid transparent; }
    .review-row:hover { border-left-color: #f97316; background-color: #fffbf7; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <?php $conn->close(); ?>
</body>
</html>