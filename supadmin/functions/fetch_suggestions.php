<?php
session_start();
require '../../conn.php';

if (!isset($_GET['suggestion_id'])) {
    echo '<p class="text-red-600 p-4 text-center">Invalid request.</p>';
    exit;
}

$id   = intval($_GET['suggestion_id']);
$mode = $_GET['mode'] ?? 'view';

$stmt = $conn->prepare("SELECT pcs.*, p.product_name FROM product_cooking_suggestions pcs 
    LEFT JOIN products p ON pcs.product_id = p.product_id 
    WHERE pcs.suggestion_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$s) {
    echo '<p class="text-red-600 p-4 text-center">Suggestion not found.</p>';
    exit;
}

if ($mode === 'view'): ?>

<div class="space-y-5">
  <!-- Header with status badges -->
  <div class="flex flex-wrap gap-3">
    <?php
    $difficultyColors = [
      'Easy' => 'badge-green',
      'Medium' => 'badge-yellow',
      'Hard' => 'badge-red'
    ];
    $diffClass = $difficultyColors[$s['difficulty_level']] ?? 'badge-gray';
    ?>
    <span class="badge <?= $diffClass ?> text-sm px-3 py-1.5"><?= $s['difficulty_level'] ?></span>
    
    <?php if ($s['prep_time_minutes']): ?>
      <span class="badge bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1.5">⏱ Prep: <?= $s['prep_time_minutes'] ?> min</span>
    <?php endif; ?>
    
    <?php if ($s['cook_time_minutes']): ?>
      <span class="badge bg-purple-50 text-purple-700 border border-purple-100 px-3 py-1.5">🔥 Cook: <?= $s['cook_time_minutes'] ?> min</span>
    <?php endif; ?>
    
    <?php if ($s['prep_time_minutes'] || $s['cook_time_minutes']): ?>
      <span class="badge bg-gray-100 text-gray-700 border border-gray-200 px-3 py-1.5">⏰ Total: <?= ($s['prep_time_minutes'] + $s['cook_time_minutes']) ?> min</span>
    <?php endif; ?>
  </div>

  <!-- Product info -->
  <div class="bg-orange-50 rounded-xl p-3 border border-orange-100">
    <span class="text-xs text-orange-600 font-medium">For Product</span>
    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($s['product_name'] ?? 'Unknown Product') ?></p>
  </div>

  <!-- Ingredients -->
  <div>
    <h4 class="section-title mb-3">Ingredients</h4>
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
      <p class="text-sm text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($s['ingredients'])) ?></p>
    </div>
  </div>

  <!-- Steps -->
  <div>
    <h4 class="section-title mb-3">Steps</h4>
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
      <p class="text-sm text-gray-700 whitespace-pre-line"><?= nl2br(htmlspecialchars($s['steps'])) ?></p>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
    <button onclick="closeModal('viewSuggestionModal'); openEditModal(<?= $id ?>)" 
            class="btn-secondary flex items-center gap-2">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
      </svg>
      Edit
    </button>
    <button onclick="closeModal('viewSuggestionModal')" class="btn-primary">Close</button>
  </div>
</div>

<?php else: // EDIT MODE

$productsResult = $conn->query("SELECT product_id, product_name FROM products WHERE is_deleted = 0 ORDER BY product_name");
$allProducts = [];
while ($p = $productsResult->fetch_assoc()) $allProducts[] = $p;
?>

<form action="./functions/update.php" method="POST" class="space-y-4">
  <input type="hidden" name="suggestion_id" value="<?= $s['suggestion_id'] ?>">
  
  <p class="section-title">Recipe Details</p>
  
  <div>
    <label class="form-label">Product <span class="text-red-500">*</span></label>
    <select name="product_id" required class="form-input">
      <?php foreach ($allProducts as $p): ?>
        <option value="<?= $p['product_id'] ?>" <?= $p['product_id'] == $s['product_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['product_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  
  <div>
    <label class="form-label">Dish Name <span class="text-red-500">*</span></label>
    <input type="text" name="dish_name" required value="<?= htmlspecialchars($s['dish_name']) ?>" class="form-input">
  </div>
  
  <div>
    <label class="form-label">Ingredients <span class="text-red-500">*</span></label>
    <textarea name="ingredients" rows="3" required class="form-input resize-none"><?= htmlspecialchars($s['ingredients']) ?></textarea>
  </div>
  
  <div>
    <label class="form-label">Steps <span class="text-red-500">*</span></label>
    <textarea name="steps" rows="5" required class="form-input resize-none"><?= htmlspecialchars($s['steps']) ?></textarea>
  </div>
  
  <p class="section-title">Cooking Information</p>
  
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="form-label">Prep Time (minutes)</label>
      <input type="number" name="prep_time_minutes" min="0" value="<?= $s['prep_time_minutes'] ?>" class="form-input">
    </div>
    <div>
      <label class="form-label">Cook Time (minutes)</label>
      <input type="number" name="cook_time_minutes" min="0" value="<?= $s['cook_time_minutes'] ?>" class="form-input">
    </div>
  </div>
  
  <div>
    <label class="form-label">Difficulty Level</label>
    <select name="difficulty_level" class="form-input">
      <option value="Easy" <?= $s['difficulty_level'] === 'Easy' ? 'selected' : '' ?>>Easy</option>
      <option value="Medium" <?= $s['difficulty_level'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
      <option value="Hard" <?= $s['difficulty_level'] === 'Hard' ? 'selected' : '' ?>>Hard</option>
    </select>
  </div>
  
  <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
    <button type="button" onclick="closeModal('editSuggestionModal')" class="btn-secondary">Cancel</button>
    <button type="submit" name="update_suggestion" class="btn-primary">Update Suggestion</button>
  </div>
</form>

<?php endif; ?>