<?php
// superadmin/functions/fetch_reviews.php
// Fetches all reviews for a given order_id, grouped by product then variant.
// Called via AJAX from reviews.php: fetch(`./functions/fetch_reviews.php?order_id=${id}`)

session_start();
require '../../conn.php';

if (!isset($_GET['order_id'])) {
    echo '<p class="text-red-600 p-4">Invalid request.</p>';
    exit;
}

$orderId = intval($_GET['order_id']);

// Fetch all reviews for this order, joined with product + variant info
$stmt = $conn->prepare("
    SELECT  r.*,
            p.product_name,
            pv.variant_name,
            pv.unit_type,
            oi.quantity
    FROM    reviews r
    LEFT JOIN products        p  ON r.product_id   = p.product_id
    LEFT JOIN order_items     oi ON r.order_item_id = oi.order_item_id
    LEFT JOIN product_variants pv ON oi.variant_id  = pv.variant_id
    WHERE   r.order_id = ?
    ORDER BY p.product_name ASC, pv.variant_name ASC
");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($rows)) {
    echo '<p class="text-red-600 p-4">No reviews found for this order.</p>';
    exit;
}

// Fetch attachments for all review_ids in one query
$reviewIds   = array_column($rows, 'review_id');
$placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
$attStmt = $conn->prepare("
    SELECT * FROM review_attachments
    WHERE review_id IN ({$placeholders})
    ORDER BY review_id, upload_order
");
$attStmt->bind_param(str_repeat('i', count($reviewIds)), ...$reviewIds);
$attStmt->execute();
$allAttachments = $attStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attStmt->close();

// Index attachments by review_id
$attachmentsByReview = [];
foreach ($allAttachments as $att) {
    $attachmentsByReview[$att['review_id']][] = $att;
}

// Group rows by product_id
$byProduct = [];
foreach ($rows as $row) {
    $byProduct[$row['product_id']][] = $row;
}

// Use first row for reviewer info (same across all reviews in one order)
$first = $rows[0];

$statusClass = fn($s) => match($s) {
    'approved' => 'bg-green-100 text-green-700 border-green-200',
    'rejected' => 'bg-red-100 text-red-700 border-red-200',
    'spam'     => 'bg-gray-100 text-gray-600 border-gray-200',
    default    => 'bg-yellow-100 text-yellow-700 border-yellow-200'
};
$statusIcon = fn($s) => match($s) {
    'approved' => '✓', 'rejected' => '✗', 'spam' => '⚑', default => '●'
};

// Base URL for uploads — absolute from project root so it works from any subfolder
// file_path in DB is already:  uploads/reviews/filename.jpg
// Web root is /sjfbi-js/ so the full URL is /sjfbi-js/uploads/reviews/filename.jpg
$uploadsBase = '/sjfbi-js/';  // ← adjust if your project root differs
?>

<div class="flex flex-col h-full">

  <!-- ── Header ── -->
  <div class="flex justify-between items-start px-6 pt-6 pb-4 border-b border-gray-100">
    <div class="flex items-center gap-3">
      <div class="size-12 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-lg uppercase select-none">
        <?php
          $n = $first['reviewer_name'];
          echo htmlspecialchars(substr($n, 0, 1) . (strpos($n, ' ') !== false ? substr(strrchr($n, ' '), 1, 1) : ''));
        ?>
      </div>
      <div>
        <h3 class="text-lg font-bold text-gray-800 leading-tight"><?= htmlspecialchars($first['reviewer_name']) ?></h3>
        <p class="text-sm text-gray-500">
          <?= !empty($first['position']) ? htmlspecialchars($first['position']) : 'Customer' ?>
          <?= !empty($first['company'])  ? ' · ' . htmlspecialchars($first['company'])  : '' ?>
        </p>
        <?php if (!empty($first['email'])): ?>
          <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($first['email']) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <button onclick="document.getElementById('viewReviewModal').classList.add('hidden')"
      class="text-gray-300 hover:text-gray-600 text-3xl leading-none mt-1 transition-colors">&times;</button>
  </div>

  <!-- ── Reviewer meta strip ── -->
  <div class="flex flex-wrap gap-3 px-6 py-3 bg-gray-50 border-b border-gray-100 text-xs">
    <span class="flex items-center gap-1 text-gray-500">
      <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      <?= date("M j, Y · g:i A", strtotime($first['created_at'])) ?>
    </span>
    <?php if ($first['is_verified_purchase']): ?>
      <span class="flex items-center gap-1 text-green-600 font-medium">
        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        Verified Purchase
      </span>
    <?php endif; ?>
    <span class="text-gray-400">Order #<?= $orderId ?></span>
    <span class="text-gray-400"><?= count($rows) ?> review<?= count($rows) !== 1 ? 's' : '' ?> · <?= count($byProduct) ?> product<?= count($byProduct) !== 1 ? 's' : '' ?></span>
  </div>

  <!-- ── Products ── -->
  <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">

    <?php foreach ($byProduct as $productId => $variants):
      $productName = $variants[0]['product_name'] ?? 'Unknown Product';
      $productReviewCount = count($variants);
    ?>

    <!-- Product block -->
    <div class="rounded-xl border border-gray-200 overflow-hidden">

      <!-- Product header -->
      <div class="flex items-center gap-2 px-4 py-2.5 bg-orange-50 border-b border-orange-100">
        <span class="text-orange-500 text-base">🐟</span>
        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($productName) ?></span>
        <span class="ml-auto text-xs text-orange-400 font-medium"><?= $productReviewCount ?> variant<?= $productReviewCount !== 1 ? 's' : '' ?></span>
      </div>

      <!-- Variants -->
      <div class="divide-y divide-gray-100">
        <?php foreach ($variants as $idx => $v):
          $atts = $attachmentsByReview[$v['review_id']] ?? [];
          $sc   = $statusClass($v['status']);
          $si   = $statusIcon($v['status']);
          $variantLabel = trim(($v['variant_name'] ?? '') . ($v['unit_type'] ? ' (' . $v['unit_type'] . ')' : ''));
        ?>
        <div class="px-4 py-4 <?= $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50' ?>">

          <!-- Variant label + rating + status -->
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <?php if (!empty($variantLabel)): ?>
              <span class="text-xs font-semibold bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full border border-gray-200">
                <?= htmlspecialchars($variantLabel) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($v['quantity'])): ?>
              <span class="text-xs text-gray-400">Qty: <?= intval($v['quantity']) ?></span>
            <?php endif; ?>

            <!-- Stars -->
            <span class="flex items-center gap-0.5 ml-auto">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="text-sm <?= $i <= $v['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
              <?php endfor; ?>
              <span class="text-xs text-gray-500 ml-1"><?= $v['rating'] ?>/5</span>
            </span>

            <!-- Status pill -->
            <span class="text-xs px-2 py-0.5 rounded-full font-medium border <?= $sc ?>">
              <?= $si ?> <?= ucfirst($v['status']) ?>
            </span>
          </div>

          <!-- Feedback -->
          <div class="bg-orange-50/60 rounded-lg px-3 py-2.5 mb-3 border border-orange-100/50">
            <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($v['feedback'])) ?></p>
          </div>

          <!-- Attachments -->
          <?php if (!empty($atts)): ?>
          <div>
            <p class="text-xs text-gray-400 font-medium mb-2">📎 <?= count($atts) ?> photo<?= count($atts) !== 1 ? 's' : '' ?></p>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($atts as $att):
                // file_path stored as "uploads/reviews/filename.jpg" — prepend project base
                $src = rtrim($uploadsBase, '/') . '/' . ltrim($att['file_path'], '/');
              ?>
                <?php if (str_starts_with($att['mime_type'] ?? '', 'image/')): ?>
                  <a href="<?= htmlspecialchars($src) ?>" target="_blank" class="block">
                    <img src="<?= htmlspecialchars($src) ?>"
                         alt="<?= htmlspecialchars($att['file_name']) ?>"
                         class="h-20 w-20 object-cover rounded-lg border border-gray-200 hover:opacity-90 transition shadow-sm">
                  </a>
                <?php else: ?>
                  <a href="<?= htmlspecialchars($src) ?>" target="_blank"
                    class="flex items-center justify-center h-20 w-20 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 transition text-xs text-gray-600 text-center p-2 leading-tight">
                    📄 <?= htmlspecialchars(substr($att['file_name'], 0, 20)) ?>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Per-variant quick actions -->
          <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-gray-100">
            <?php if ($v['status'] !== 'approved'): ?>
              <form action="./functions/update_review_status.php" method="POST" class="inline">
                <input type="hidden" name="review_id" value="<?= $v['review_id'] ?>">
                <input type="hidden" name="status" value="approved">
                <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-600 text-xs font-medium transition" >✓ Approve</button>
              </form>
            <?php endif; ?>
            <?php if ($v['status'] !== 'rejected'): ?>
              <form action="./functions/update_review_status.php" method="POST" class="inline">
                <input type="hidden" name="review_id" value="<?= $v['review_id'] ?>">
                <input type="hidden" name="status" value="rejected">
                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium transition">✗ Reject</button>
              </form>
            <?php endif; ?>
            <?php if ($v['status'] !== 'spam'): ?>
              <form action="./functions/update_review_status.php" method="POST" class="inline">
                <input type="hidden" name="review_id" value="<?= $v['review_id'] ?>">
                <input type="hidden" name="status" value="spam">
                <button type="submit" class="px-3 py-1 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-xs font-medium transition">⚑ Spam</button>
              </form>
            <?php endif; ?>
          </div>

        </div>
        <?php endforeach; ?>
      </div>

    </div>
    <!-- /product block -->

    <?php endforeach; ?>
  </div>

  <!-- ── Footer ── -->
  <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
    <button onclick="document.getElementById('viewReviewModal').classList.add('hidden')"
      class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium transition">
      Close
    </button>
  </div>

</div>