<?php
session_start();
require '../../conn.php';

if (!isset($_GET['review_id'])) {
    echo '<p class="text-red-600">Invalid request.</p>';
    exit;
}

$id = intval($_GET['review_id']);

$stmt = $conn->prepare("SELECT r.*, p.product_name FROM reviews r 
    LEFT JOIN products p ON r.product_id = p.product_id 
    WHERE r.review_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$r) {
    echo '<p class="text-red-600">Review not found.</p>';
    exit;
}

// Fetch attachments
$attStmt = $conn->prepare("SELECT * FROM review_attachments WHERE review_id = ? ORDER BY upload_order");
$attStmt->bind_param("i", $id);
$attStmt->execute();
$attachments = $attStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attStmt->close();

$statusClass = match($r['status']) {
    'approved' => 'bg-green-100 text-green-700',
    'rejected' => 'bg-red-100 text-red-700',
    'spam' => 'bg-gray-100 text-gray-600',
    default => 'bg-yellow-100 text-yellow-700'
};
?>

<div>
  <div class="flex justify-between items-start mb-5">
    <div>
      <div class="flex items-center gap-3">
        <div class="size-12 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-lg uppercase">
          <?php
            $initials = substr($r['full_name'], 0, 1);
            if (strpos($r['full_name'], ' ') !== false) {
                $initials .= substr(strrchr($r['full_name'], ' '), 1, 1);
            }
            echo htmlspecialchars($initials);
          ?>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($r['full_name']) ?></h3>
          <p class="text-sm text-gray-500">
            <?= !empty($r['position']) ? htmlspecialchars($r['position']) : 'Customer' ?>
            <?= !empty($r['company']) ? ' · ' . htmlspecialchars($r['company']) : '' ?>
          </p>
        </div>
      </div>
    </div>
    <button onclick="document.getElementById('viewReviewModal').classList.add('hidden')"
      class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
  </div>

  <!-- Meta info -->
  <div class="grid grid-cols-2 gap-3 mb-5 text-sm">
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Product</p>
      <p class="font-semibold text-gray-800 mt-0.5"><?= htmlspecialchars($r['product_name'] ?? 'N/A') ?></p>
    </div>
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Rating</p>
      <div class="flex items-center gap-1 mt-0.5">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <span class="text-lg <?= $i <= $r['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
        <?php endfor; ?>
        <span class="text-sm text-gray-600 ml-1"><?= $r['rating'] ?>/5</span>
      </div>
    </div>
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Status</p>
      <span class="px-2 py-0.5 text-xs rounded-full font-medium mt-0.5 inline-block <?= $statusClass ?>">
        <?= ucfirst($r['status']) ?>
      </span>
    </div>
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Submitted</p>
      <p class="font-semibold text-gray-800 mt-0.5"><?= date("M j, Y g:i A", strtotime($r['created_at'])) ?></p>
    </div>
    <?php if (!empty($r['email'])): ?>
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Email</p>
      <p class="font-semibold text-gray-800 mt-0.5"><?= htmlspecialchars($r['email']) ?></p>
    </div>
    <?php endif; ?>
    <div class="bg-gray-50 rounded-lg p-3">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Verified Purchase</p>
      <p class="font-semibold mt-0.5 <?= $r['is_verified_purchase'] ? 'text-green-600' : 'text-gray-500' ?>">
        <?= $r['is_verified_purchase'] ? '✓ Yes' : '✗ No' ?>
      </p>
    </div>
  </div>

  <!-- Feedback -->
  <div class="bg-orange-50 rounded-xl p-4 mb-4">
    <h4 class="font-semibold text-gray-800 mb-2">💬 Feedback</h4>
    <p class="text-gray-700 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($r['feedback'])) ?></p>
  </div>

  <!-- Attachments -->
  <?php if (!empty($attachments)): ?>
  <div class="mb-4">
    <h4 class="font-semibold text-gray-800 mb-2">📎 Attachments (<?= count($attachments) ?>)</h4>
    <div class="grid grid-cols-3 gap-2">
      <?php foreach ($attachments as $att): ?>
        <?php if (str_starts_with($att['mime_type'] ?? '', 'image/')): ?>
          <a href="<?= htmlspecialchars($att['file_path']) ?>" target="_blank">
            <img src="<?= htmlspecialchars($att['file_path']) ?>" alt="<?= htmlspecialchars($att['file_name']) ?>"
              class="w-full h-24 object-cover rounded-lg border hover:opacity-90 transition">
          </a>
        <?php else: ?>
          <a href="<?= htmlspecialchars($att['file_path']) ?>" target="_blank"
            class="flex items-center justify-center h-24 bg-gray-100 rounded-lg border hover:bg-gray-200 transition text-xs text-gray-600 text-center p-2">
            📄 <?= htmlspecialchars($att['file_name']) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Quick Status Actions -->
  <div class="border-t pt-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-2">Quick Actions</p>
    <div class="flex flex-wrap gap-2">
      <?php if ($r['status'] !== 'approved'): ?>
        <form action="./functions/update_review_status.php" method="POST" class="inline">
          <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
          <input type="hidden" name="status" value="approved">
          <button type="submit" class="px-4 py-2 bg-orange-600 text-black rounded-lg hover:bg-orange-700 text-sm font-medium">✓ Approve</button>
        </form>
      <?php endif; ?>
      <?php if ($r['status'] !== 'rejected'): ?>
        <form action="./functions/update_review_status.php" method="POST" class="inline">
          <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
          <input type="hidden" name="status" value="rejected">
          <button type="submit" class="px-4 py-2 bg-red-600 text-black rounded-lg hover:bg-red-700 text-sm font-medium">✗ Reject</button>
        </form>
      <?php endif; ?>
      <?php if ($r['status'] !== 'spam'): ?>
        <form action="./functions/update_review_status.php" method="POST" class="inline">
          <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
          <input type="hidden" name="status" value="spam">
          <button type="submit" class="px-4 py-2 bg-gray-600 text-black rounded-lg hover:bg-gray-600 text-sm font-medium">⚑ Mark Spam</button>
        </form>
      <?php endif; ?>
      <button onclick="document.getElementById('viewReviewModal').classList.add('hidden')"
        class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 text-sm font-medium">Close</button>
    </div>
  </div>
</div>