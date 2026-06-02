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

  <style>
    /* Import products.php design language */
    .suggestion-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .suggestion-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

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
      width: 100%; max-width: 48rem;
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

    .form-label {
      display: block; font-size: 0.8125rem; font-weight: 600; color: #374151;
      margin-bottom: 0.375rem;
    }
    .form-input {
      width: 100%; padding: 0.5rem 0.75rem;
      border: 1px solid #e5e7eb; border-radius: 0.5rem;
      font-size: 0.875rem; color: #111827;
      transition: border-color 0.15s, box-shadow 0.15s;
      outline: none;
    }
    .form-input:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,0.1); }

    .section-title {
      font-size: 0.9375rem; font-weight: 700; color: #111827;
      border-left: 3px solid #ea580c;
      padding-left: 0.625rem;
      margin: 1.25rem 0 0.75rem;
    }

    .btn-primary {
      padding: 0.5rem 1.25rem;
      background: #ea580c; color: white;
      border-radius: 0.625rem; border: none;
      font-size: 0.875rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s, transform 0.1s;
    }
    .btn-primary:hover { background: #c2410c; }
    .btn-primary:active { transform: scale(0.97); }

    .btn-secondary {
      padding: 0.5rem 1.25rem;
      background: white; color: #374151;
      border-radius: 0.625rem; border: 1px solid #e5e7eb;
      font-size: 0.875rem; font-weight: 500;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-secondary:hover { background: #f9fafb; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-purple { background: #f3e8ff; color: #6b21a8; }
    .badge-gray { background: #f3f4f6; color: #374151; }

    .stats-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 1rem;
      padding: 1.25rem;
      transition: all 0.2s ease;
    }
    .stats-card:hover {
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }
  </style>
</head>

<body class="bg-gray-50">
  <?php include('./components/header.php'); ?>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <?php if (!empty($_SESSION['message'])): 
        $message = $_SESSION['message'];
        $alertClass = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
        echo '<div class="mt-2 ' . $alertClass . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
          <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
        </div>';
        unset($_SESSION['message']);
      endif; ?>

      <!-- Stats Row -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Total Suggestions</p>
              <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $totalItems; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 text-xl">🍳</div>
          </div>
        </div>
        
        <?php
        $easyCount = 0; $mediumCount = 0; $hardCount = 0;
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            if ($row['difficulty_level'] === 'Easy') $easyCount++;
            elseif ($row['difficulty_level'] === 'Medium') $mediumCount++;
            elseif ($row['difficulty_level'] === 'Hard') $hardCount++;
        }
        $result->data_seek(0);
        ?>
        
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Easy</p>
              <p class="text-2xl font-bold text-green-600 mt-1"><?php echo $easyCount; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl">🌱</div>
          </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Medium</p>
              <p class="text-2xl font-bold text-yellow-600 mt-1"><?php echo $mediumCount; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 text-xl">🔥</div>
          </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Hard</p>
              <p class="text-2xl font-bold text-red-600 mt-1"><?php echo $hardCount; ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 text-xl">👨‍🍳</div>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        <!-- Header with filters -->
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
          <div>
            <h2 class="text-xl font-bold text-gray-900">🍽 Cooking Suggestions</h2>
            <p class="text-sm text-gray-500 mt-0.5">
              <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> total recipes
            </p>
          </div>
          
          <div class="flex gap-2">
            <button onclick="openAddModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14"/><path d="M12 5v14"/>
              </svg>
              Add Suggestion
            </button>
          </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="px-6 py-3 bg-gray-50/50 border-b border-gray-100 flex flex-wrap gap-3">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                 placeholder="Search dish or product..." 
                 class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
          
          <select name="product_id" class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-48">
            <option value="">All Products</option>
            <?php foreach ($allProducts as $p): ?>
              <option value="<?= $p['product_id'] ?>" <?= $filterProduct == $p['product_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          
          <select name="difficulty" class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-48">
            <option value="">All Levels</option>
            <option value="Easy" <?= $filterDifficulty === 'Easy' ? 'selected' : '' ?>>Easy</option>
            <option value="Medium" <?= $filterDifficulty === 'Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="Hard" <?= $filterDifficulty === 'Hard' ? 'selected' : '' ?>>Hard</option>
          </select>
          
          <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm hover:bg-gray-200 transition-colors">
            Filter
          </button>
          
          <?php if (!empty($search) || $filterProduct || !empty($filterDifficulty)): ?>
            <a href="cooking_suggestions.php" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-sm hover:bg-red-100 transition-colors flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Clear
            </a>
          <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dish Name</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Difficulty</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Time</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Created</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="suggestion-row hover:bg-orange-50/40 transition-colors">
                  <td class="px-6 py-4">
                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($row['dish_name']) ?></p>
                    <p class="text-xs text-gray-400 mt-0.5 max-w-xs truncate"><?= htmlspecialchars(substr($row['ingredients'], 0, 60)) ?>...</p>
                  </td>
                  <td class="px-4 py-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">
                      <?= htmlspecialchars($row['product_name'] ?? 'N/A') ?>
                    </span>
                  </td>
                  <td class="px-4 py-4">
                    <?php
                    $difficultyColors = [
                      'Easy' => 'badge-green',
                      'Medium' => 'badge-yellow',
                      'Hard' => 'badge-red'
                    ];
                    $diffClass = $difficultyColors[$row['difficulty_level']] ?? 'badge-gray';
                    ?>
                    <span class="badge <?= $diffClass ?>"><?= htmlspecialchars($row['difficulty_level']) ?></span>
                  </td>
                  <td class="px-4 py-4">
                    <?php
                      $prep = $row['prep_time_minutes'] ?? 0;
                      $cook = $row['cook_time_minutes'] ?? 0;
                      $total = $prep + $cook;
                      echo $total > 0 ? '<span class="text-sm text-gray-600">' . $total . ' min</span>' : '<span class="text-sm text-gray-400">—</span>';
                    ?>
                  </td>
                  <td class="px-4 py-4">
                    <span class="text-sm text-gray-500"><?= date("M j, Y", strtotime($row['created_at'])) ?></span>
                  </td>
                  <td class="px-4 py-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button onclick="openViewModal(<?= $row['suggestion_id'] ?>)"
                              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="3"/><path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                        </svg>
                      </button>
                      <button onclick="openEditModal(<?= $row['suggestion_id'] ?>)"
                              class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                        </svg>
                      </button>
                      <button onclick="openDeleteModal(<?= $row['suggestion_id'] ?>, '<?= htmlspecialchars(addslashes($row['dish_name'])) ?>')"
                              class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                      <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                          <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Z"/>
                        </svg>
                      </div>
                      <p class="text-sm font-semibold text-gray-700">No cooking suggestions found</p>
                      <p class="text-xs text-gray-400">Click "Add Suggestion" to create your first recipe</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination (matching products.php style) -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
          <p class="text-sm text-gray-500">
            Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span> 
            of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> suggestions
          </p>
          
          <div class="flex items-center gap-1.5">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&product_id=<?= $filterProduct ?>&difficulty=<?= $filterDifficulty ?>" 
                 class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
              </a>
            <?php else: ?>
              <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
              </span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            $queryParams = "&search=".urlencode($search)."&product_id=".$filterProduct."&difficulty=".urlencode($filterDifficulty);
            
            if ($start > 1) {
              echo '<a href="?page=1'.$queryParams.'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
            }
            if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
            
            for ($i = $start; $i <= $end; $i++):
            ?>
              <a href="?page=<?= $i ?><?= $queryParams ?>" 
                 class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
                 <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
                <?= $i ?>
              </a>
            <?php
            endfor;
            
            if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
            if ($end < $totalPages) {
              echo '<a href="?page='.$totalPages.$queryParams.'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">'.$totalPages.'</a>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page + 1 ?><?= $queryParams ?>" 
                 class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
                Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
              </a>
            <?php else: ?>
              <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
                Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
              </span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ADD MODAL (Redesigned) -->
  <div id="addSuggestionModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Add Cooking Suggestion</h3>
          <p>Create a new recipe for your products</p>
        </div>
        <button class="modal-close" onclick="closeModal('addSuggestionModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form id="addSuggestionForm" action="./functions/add.php" method="POST" class="space-y-4">
          <p class="section-title">Recipe Details</p>
          
          <div>
            <label class="form-label">Product <span class="text-red-500">*</span></label>
            <select name="product_id" required class="form-input">
              <option value="">— Select Product —</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="form-label">Dish Name <span class="text-red-500">*</span></label>
            <input type="text" name="dish_name" required placeholder="e.g. Sinigang na Bangus" class="form-input">
          </div>
          
          <div>
            <label class="form-label">Ingredients <span class="text-red-500">*</span></label>
            <textarea name="ingredients" rows="3" required placeholder="List all ingredients..." class="form-input resize-none"></textarea>
          </div>
          
          <div>
            <label class="form-label">Steps <span class="text-red-500">*</span></label>
            <textarea name="steps" rows="5" required placeholder="Step 1: ...\nStep 2: ..." class="form-input resize-none"></textarea>
          </div>
          
          <p class="section-title">Cooking Information</p>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Prep Time (minutes)</label>
              <input type="number" name="prep_time_minutes" min="0" placeholder="e.g. 15" class="form-input">
            </div>
            <div>
              <label class="form-label">Cook Time (minutes)</label>
              <input type="number" name="cook_time_minutes" min="0" placeholder="e.g. 25" class="form-input">
            </div>
          </div>
          
          <div>
            <label class="form-label">Difficulty Level</label>
            <select name="difficulty_level" class="form-input">
              <option value="Easy">Easy</option>
              <option value="Medium">Medium</option>
              <option value="Hard">Hard</option>
            </select>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeModal('addSuggestionModal')" class="btn-secondary">Cancel</button>
        <button type="submit" name="add_suggestion" form="addSuggestionForm" class="btn-primary">
          Save Suggestion
        </button>
      </div>
    </div>
  </div>

  <!-- VIEW MODAL (Redesigned) -->
  <div id="viewSuggestionModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Recipe Details</h3>
          <p id="viewDishName" class="text-sm text-gray-500"></p>
        </div>
        <button class="modal-close" onclick="closeModal('viewSuggestionModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div id="viewModalContent" class="modal-body">
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading recipe...
        </div>
      </div>
    </div>
  </div>

  <!-- EDIT MODAL (Redesigned) -->
  <div id="editSuggestionModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Edit Cooking Suggestion</h3>
          <p>Update recipe details</p>
        </div>
        <button class="modal-close" onclick="closeModal('editSuggestionModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div id="editModalContent" class="modal-body">
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading recipe...
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE MODAL (Redesigned) -->
  <div id="deleteSuggestionModal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:28rem">
      <div class="modal-header">
        <div>
          <h3>Delete Suggestion</h3>
          <p>This action cannot be undone</p>
        </div>
        <button class="modal-close" onclick="closeModal('deleteSuggestionModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <div class="modal-body text-center">
        <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </div>
        
        <form action="./functions/delete.php" method="POST" id="deleteSuggestionForm">
          <input type="hidden" name="suggestion_id" id="deleteSuggestionId">
          <p id="deleteSuggestionName" class="text-sm font-semibold text-gray-800 mb-1"></p>
          <p class="text-xs text-red-500 mb-5">This will permanently delete this cooking suggestion.</p>
          <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeModal('deleteSuggestionModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="delete_suggestion" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Modal functions
    function openAddModal() {
      document.getElementById('addSuggestionModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    window.closeModal = function(modalId) {
      document.getElementById(modalId).classList.add('hidden');
      document.body.style.overflow = '';
    };

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
      });
    });

    function openViewModal(id) {
      const modal = document.getElementById('viewSuggestionModal');
      const content = document.getElementById('viewModalContent');
      
      content.innerHTML = `
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading recipe...
        </div>`;
      modal.classList.remove('hidden');
      
      fetch(`./functions/fetch_suggestions.php?suggestion_id=${id}&mode=view`)
        .then(r => r.text())
        .then(html => {
          content.innerHTML = html;
        })
        .catch(() => {
          content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load recipe.</p>';
        });
    }

    function openEditModal(id) {
      const modal = document.getElementById('editSuggestionModal');
      const content = document.getElementById('editModalContent');
      
      content.innerHTML = `
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading recipe...
        </div>`;
      modal.classList.remove('hidden');
      
      fetch(`./functions/fetch_suggestions.php?suggestion_id=${id}&mode=edit`)
        .then(r => r.text())
        .then(html => {
          content.innerHTML = html;
        })
        .catch(() => {
          content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load recipe.</p>';
        });
    }

    function openDeleteModal(id, name) {
      document.getElementById('deleteSuggestionId').value = id;
      document.getElementById('deleteSuggestionName').innerHTML = `Are you sure you want to delete <strong>"${name}"</strong>?`;
      document.getElementById('deleteSuggestionModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <?php $conn->close(); ?>
</body>
</html>