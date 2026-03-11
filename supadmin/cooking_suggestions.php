<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterProduct = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$filterDifficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $where .= " AND (pcs.dish_name LIKE ? OR p.product_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}
if ($filterProduct > 0) {
    $where .= " AND pcs.product_id = ?";
    $params[] = $filterProduct;
    $types .= 'i';
}
if (!empty($filterDifficulty)) {
    $where .= " AND pcs.difficulty_level = ?";
    $params[] = $filterDifficulty;
    $types .= 's';
}

// Count
$countQuery = "SELECT COUNT(*) as total FROM product_cooking_suggestions pcs 
               LEFT JOIN products p ON pcs.product_id = p.product_id $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$countStmt->close();

$offset = ($page - 1) * $perPage;

// Main query
$query = "SELECT pcs.*, p.product_name 
          FROM product_cooking_suggestions pcs
          LEFT JOIN products p ON pcs.product_id = p.product_id
          $where
          ORDER BY pcs.created_at DESC
          LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all products for dropdown
$productsResult = $conn->query("SELECT product_id, product_name FROM products WHERE is_deleted = 0 ORDER BY product_name");
$allProducts = [];
while ($p = $productsResult->fetch_assoc()) {
    $allProducts[] = $p;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cooking Suggestions | St. Joseph Fish Brokerage Inc.</title>
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
  <?php include('./components/header.php'); ?>

  <!-- Breadcrumb mobile -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" data-hs-overlay="#hs-application-sidebar">
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2" /><path d="M15 3v18" /><path d="m8 9 3 3-3 3" />
        </svg>
      </button>
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">Navigation
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate" aria-current="page">Cooking Suggestions</li>
      </ol>
    </div>
  </div>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <?php if (!empty($_SESSION['message'])): 
        $message = $_SESSION['message'];
        $alertClass = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
        echo '<div class="mt-2 ' . $alertClass . ' text-sm rounded-lg p-4" role="alert">
          <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
        </div>';
        unset($_SESSION['message']);
      endif; ?>

      <!-- Table Card -->
      <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
          <div class="p-1.5 min-w-full inline-block align-middle">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

              <!-- Header -->
              <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div>
                    <h2 class="text-xl font-semibold text-gray-800">🍽 Cooking Suggestions</h2>
                    <p class="text-sm text-gray-600">Manage recipe suggestions for your products</p>
                  </div>
                  <button onclick="document.getElementById('addSuggestionModal').classList.remove('hidden')"
                    class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg bg-orange-600 text-white hover:bg-orange-700 whitespace-nowrap">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M5 12h14" /><path d="M12 5v14" />
                    </svg>
                    Add Suggestion
                  </button>
                </div>

                <!-- Filters -->
                <form method="GET" class="mt-4 flex flex-wrap gap-3">
                  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search dish or product..." 
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-full sm:w-64">
                  <select name="product_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Products</option>
                    <?php foreach ($allProducts as $p): ?>
                      <option value="<?= $p['product_id'] ?>" <?= $filterProduct == $p['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['product_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <select name="difficulty" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Levels</option>
                    <option value="Easy" <?= $filterDifficulty === 'Easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="Medium" <?= $filterDifficulty === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Hard" <?= $filterDifficulty === 'Hard' ? 'selected' : '' ?>>Hard</option>
                  </select>
                  <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Filter</button>
                  <?php if (!empty($search) || $filterProduct || !empty($filterDifficulty)): ?>
                    <a href="cooking_suggestions.php" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">Clear</a>
                  <?php endif; ?>
                </form>
              </div>

              <!-- Table -->
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="ps-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Dish Name</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Product</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Difficulty</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Time</th>
                    <th class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Created</th>
                    <th class="px-6 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="suggestion-row bg-white">
                      <td class="ps-6 py-4">
                        <span class="block text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['dish_name']) ?></span>
                        <span class="block text-xs text-gray-400 mt-0.5 max-w-xs truncate"><?= htmlspecialchars(substr($row['ingredients'], 0, 60)) . '...' ?></span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs bg-orange-50 text-orange-700 rounded-full border border-orange-200">
                          <?= htmlspecialchars($row['product_name'] ?? 'N/A') ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <?php
                        $diffClass = match($row['difficulty_level']) {
                          'Easy' => 'bg-green-100 text-green-700',
                          'Medium' => 'bg-yellow-100 text-yellow-700',
                          'Hard' => 'bg-red-100 text-red-700',
                          default => 'bg-gray-100 text-gray-700'
                        };
                        ?>
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= $diffClass ?>">
                          <?= htmlspecialchars($row['difficulty_level']) ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-gray-600">
                          <?php
                            $prep = $row['prep_time_minutes'] ?? 0;
                            $cook = $row['cook_time_minutes'] ?? 0;
                            $total = $prep + $cook;
                            echo $total > 0 ? $total . ' min' : '—';
                          ?>
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-gray-500"><?= date("M j, Y", strtotime($row['created_at'])) ?></span>
                      </td>
                      <td class="px-6 py-4 text-end">
                        <div class="inline-flex gap-1">
                          <button onclick="openViewModal(<?= $row['suggestion_id'] ?>)"
                            class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-medium">
                            👁 View
                          </button>
                          <button onclick="openEditModal(<?= $row['suggestion_id'] ?>)"
                            class="px-3 py-2 text-white rounded-lg text-xs font-medium" style="background-color: #3b82f6;">
                            ✏️ Edit
                          </button>
                          <button onclick="openDeleteModal(<?= $row['suggestion_id'] ?>, '<?= htmlspecialchars($row['dish_name']) ?>')"
                            class="px-3 py-2 text-white rounded-lg text-xs font-medium" style="background-color: #ef4444;">
                            🗑
                          </button>
                        </div>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                          <span class="text-5xl mb-3">🍽</span>
                          <p class="text-gray-500 font-medium">No cooking suggestions found</p>
                          <p class="text-gray-400 text-sm mt-1">Add your first recipe suggestion to get started</p>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>

              <!-- Footer / Pagination -->
              <div class="px-6 py-4 flex justify-between items-center border-t border-gray-200">
                <p class="text-sm text-gray-600"><span class="font-semibold text-gray-800"><?= $totalItems ?></span> results</p>
                <div class="inline-flex gap-x-2">
                  <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&product_id=<?= $filterProduct ?>&difficulty=<?= $filterDifficulty ?>" 
                      class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">← Prev</a>
                  <?php endif; ?>
                  <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&product_id=<?= $filterProduct ?>&difficulty=<?= $filterDifficulty ?>"
                      class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 <?= $i == $page ? 'bg-orange-500 text-white' : 'bg-white text-gray-800 hover:bg-gray-50' ?>">
                      <?= $i ?>
                    </a>
                  <?php endfor; ?>
                  <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&product_id=<?= $filterProduct ?>&difficulty=<?= $filterDifficulty ?>"
                      class="py-1.5 px-3 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">Next →</a>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ADD MODAL -->
  <div id="addSuggestionModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
    <div class="bg-white w-full max-w-3xl p-6 rounded-2xl shadow-2xl">
      <h3 class="text-xl font-semibold mb-4 text-gray-800">🍳 Add Cooking Suggestion</h3>
      <form action="./functions/add_suggestion.php" method="POST" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Product *</label>
            <select name="product_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
              <option value="">— Select Product —</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Dish Name *</label>
            <input type="text" name="dish_name" required placeholder="e.g. Sinigang na Bangus" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Ingredients *</label>
            <textarea name="ingredients" rows="3" required placeholder="List all ingredients..." class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Steps *</label>
            <textarea name="steps" rows="5" required placeholder="Step 1: ...\nStep 2: ..." class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Prep Time (minutes)</label>
            <input type="number" name="prep_time_minutes" min="0" placeholder="e.g. 15" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Cook Time (minutes)</label>
            <input type="number" name="cook_time_minutes" min="0" placeholder="e.g. 25" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Difficulty Level</label>
            <select name="difficulty_level" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
              <option value="Easy">Easy</option>
              <option value="Medium">Medium</option>
              <option value="Hard">Hard</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="submit" name="add_suggestion" class="py-2 px-5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium">Save Suggestion</button>
          <button type="button" onclick="document.getElementById('addSuggestionModal').classList.add('hidden')" class="py-2 px-5 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 font-medium">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW MODAL -->
  <div id="viewSuggestionModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden">
      <div id="viewModalContent" class="p-6"></div>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div id="editSuggestionModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden">
      <div id="editModalContent" class="p-6"></div>
    </div>
  </div>

  <!-- DELETE MODAL -->
  <div id="deleteSuggestionModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
    <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-2xl">
      <h3 class="text-lg font-semibold mb-2 text-gray-800">Delete Suggestion</h3>
      <p id="deleteSuggestionName" class="text-gray-600 mb-4"></p>
      <form action="./functions/delete.php" method="POST">
        <input type="hidden" name="suggestion_id" id="deleteSuggestionId">
        <div class="flex justify-end gap-3">
          <button type="submit" name="delete_suggestion" class="py-2 px-5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Delete</button>
          <button type="button" onclick="document.getElementById('deleteSuggestionModal').classList.add('hidden')" class="py-2 px-5 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 font-medium">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openViewModal(id) {
      fetch(`./functions/fetch_suggestions.php?suggestion_id=${id}&mode=view`)
        .then(r => r.text())
        .then(html => {
          document.getElementById('viewModalContent').innerHTML = html;
          document.getElementById('viewSuggestionModal').classList.remove('hidden');
        });
    }

    function openEditModal(id) {
      fetch(`./functions/fetch_suggestions.php?suggestion_id=${id}&mode=edit`)
        .then(r => r.text())
        .then(html => {
          document.getElementById('editModalContent').innerHTML = html;
          document.getElementById('editSuggestionModal').classList.remove('hidden');
        });
    }

    function openDeleteModal(id, name) {
      document.getElementById('deleteSuggestionId').value = id;
      document.getElementById('deleteSuggestionName').textContent = `Are you sure you want to delete "${name}"?`;
      document.getElementById('deleteSuggestionModal').classList.remove('hidden');
    }
  </script>

  <style>
    .suggestion-row {
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
    }
    .suggestion-row:hover {
      border-left-color: #f97316;
      background-color: #fffbf7;
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <?php $conn->close(); ?>
</body>
</html>