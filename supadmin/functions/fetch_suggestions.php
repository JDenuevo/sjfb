<?php
session_start();
require '../../conn.php';

if (!isset($_GET['suggestion_id'])) {
    echo '<p class="text-red-600">Invalid request.</p>';
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
    echo '<p class="text-red-600">Suggestion not found.</p>';
    exit;
}

if ($mode === 'view'): ?>

<div>
  <div class="flex justify-between items-start mb-4">
    <div>
      <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($s['dish_name']) ?></h3>
      <span class="text-sm text-orange-600 font-medium">for <?= htmlspecialchars($s['product_name'] ?? 'Unknown Product') ?></span>
    </div>
    <button onclick="document.getElementById('viewSuggestionModal').classList.add('hidden')" 
      class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
  </div>

  <div class="flex flex-wrap gap-3 mb-5">
    <?php
    $diffClass = match($s['difficulty_level']) {
      'Easy' => 'bg-green-100 text-green-700',
      'Medium' => 'bg-yellow-100 text-yellow-700',
      'Hard' => 'bg-red-100 text-red-700',
      default => 'bg-gray-100 text-gray-700'
    };
    ?>
    <span class="px-3 py-1 text-sm rounded-full font-medium <?= $diffClass ?>"><?= $s['difficulty_level'] ?></span>
    <?php if ($s['prep_time_minutes']): ?>
      <span class="px-3 py-1 text-sm rounded-full bg-blue-50 text-blue-700">⏱ Prep: <?= $s['prep_time_minutes'] ?> min</span>
    <?php endif; ?>
    <?php if ($s['cook_time_minutes']): ?>
      <span class="px-3 py-1 text-sm rounded-full bg-purple-50 text-purple-700">🔥 Cook: <?= $s['cook_time_minutes'] ?> min</span>
    <?php endif; ?>
    <?php if ($s['prep_time_minutes'] || $s['cook_time_minutes']): ?>
      <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-600">⏰ Total: <?= ($s['prep_time_minutes'] + $s['cook_time_minutes']) ?> min</span>
    <?php endif; ?>
  </div>

  <div class="space-y-4">
    <div class="bg-orange-50 rounded-xl p-4">
      <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2"><span>🥬</span> Ingredients</h4>
      <p class="text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($s['ingredients']) ?></p>
    </div>

    <div class="bg-blue-50 rounded-xl p-4">
      <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2"><span>📋</span> Steps</h4>
      <p class="text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($s['steps']) ?></p>
    </div>
  </div>

  <div class="mt-5 flex justify-end gap-3">
    <button onclick="document.getElementById('viewSuggestionModal').classList.add('hidden'); openEditModal(<?= $id ?>)"
      class="py-2 px-5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">✏️ Edit</button>
    <button onclick="document.getElementById('viewSuggestionModal').classList.add('hidden')"
      class="py-2 px-5 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 font-medium text-sm">Close</button>
  </div>
</div>

<?php else: // EDIT MODE

$productsResult = $conn->query("SELECT product_id, product_name FROM products WHERE is_deleted = 0 ORDER BY product_name");
$allProducts = [];
while ($p = $productsResult->fetch_assoc()) $allProducts[] = $p;
?>

<div>
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-xl font-semibold text-gray-800">✏️ Edit Cooking Suggestion</h3>
    <button onclick="document.getElementById('editSuggestionModal').classList.add('hidden')" 
      class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
  </div>

  <form action="./functions/update.php" method="POST" class="space-y-4">
    <input type="hidden" name="suggestion_id" value="<?= $s['suggestion_id'] ?>">

    <div class="grid grid-cols-2 gap-4">
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700">Product *</label>
        <select name="product_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
          <?php foreach ($allProducts as $p): ?>
            <option value="<?= $p['product_id'] ?>" <?= $p['product_id'] == $s['product_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['product_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700">Dish Name *</label>
        <input type="text" name="dish_name" required value="<?= htmlspecialchars($s['dish_name']) ?>"
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700">Ingredients *</label>
        <textarea name="ingredients" rows="3" required
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"><?= htmlspecialchars($s['ingredients']) ?></textarea>
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700">Steps *</label>
        <textarea name="steps" rows="5" required
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"><?= htmlspecialchars($s['steps']) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Prep Time (minutes)</label>
        <input type="number" name="prep_time_minutes" min="0" value="<?= $s['prep_time_minutes'] ?>"
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Cook Time (minutes)</label>
        <input type="number" name="cook_time_minutes" min="0" value="<?= $s['cook_time_minutes'] ?>"
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Difficulty Level</label>
        <select name="difficulty_level" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
          <option value="Easy" <?= $s['difficulty_level'] === 'Easy' ? 'selected' : '' ?>>Easy</option>
          <option value="Medium" <?= $s['difficulty_level'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
          <option value="Hard" <?= $s['difficulty_level'] === 'Hard' ? 'selected' : '' ?>>Hard</option>
        </select>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <button type="submit" name="update_suggestion"
        class="py-2 px-5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium">Update Suggestion</button>
      <button type="button" onclick="document.getElementById('editSuggestionModal').classList.add('hidden')"
        class="py-2 px-5 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 font-medium">Cancel</button>
    </div>
  </form>
</div>

<?php endif; ?>