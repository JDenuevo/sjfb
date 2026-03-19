<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

$countQuery = "SELECT COUNT(*) as total FROM markets WHERE is_active = 1";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

$query = "SELECT * FROM markets WHERE is_active = 1 ORDER BY display_order LIMIT $perPage OFFSET $offset";
$result = $conn->query($query);

// Get all products for dropdown
$products_query = "SELECT p.product_id, p.product_name, pv.variant_price 
                   FROM products p 
                   LEFT JOIN product_variants pv ON p.product_id = pv.product_id 
                   WHERE p.is_deleted = 0 
                   GROUP BY p.product_id 
                   ORDER BY p.product_name";
$products_result = $conn->query($products_query);
$products_options = '';
while ($product = $products_result->fetch_assoc()) {
    $products_options .= '<option value="' . $product['product_id'] . '">' . htmlspecialchars($product['product_name']) . ' - ₱' . number_format($product['variant_price'] ?? 0, 2) . '</option>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Markets | St. Joseph Fish Brokerage Inc.</title>

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
    select[multiple] { appearance: none; -webkit-appearance: none; background-image: none; }

    .market-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .market-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

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

    .member-row, .product-row {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 0.75rem;
      padding: 0.875rem;
      margin-bottom: 0.625rem;
      position: relative;
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

    .btn-danger {
      padding: 0.5rem 1rem;
      background: #fee2e2; color: #dc2626;
      border-radius: 0.5rem; border: none;
      font-size: 0.8125rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-danger:hover { background: #fecaca; }

    .btn-success {
      padding: 0.5rem 1rem;
      background: #dcfce7; color: #16a34a;
      border-radius: 0.5rem; border: none;
      font-size: 0.8125rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-success:hover { background: #bbf7d0; }

    .section-title {
      font-size: 0.9375rem; font-weight: 700; color: #111827;
      border-left: 3px solid #ea580c;
      padding-left: 0.625rem;
      margin: 1.25rem 0 0.75rem;
    }

    .image-thumb { position: relative; }
    .image-thumb img { width: 100%; height: 5rem; object-fit: cover; border-radius: 0.5rem; }
    .image-thumb .del-btn {
      position: absolute; top: 0.25rem; right: 0.25rem;
      width: 1.5rem; height: 1.5rem;
      background: #dc2626; color: white;
      border-radius: 50%; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.875rem; opacity: 0; transition: opacity 0.15s;
    }
    .image-thumb:hover .del-btn { opacity: 1; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-gray { background: #f3f4f6; color: #374151; }
    .badge-blue { background: #dbeafe; color: #1e40af; }

    .color-preview {
      width: 24px; height: 24px; border-radius: 4px; display: inline-block; margin-right: 8px; vertical-align: middle;
    }
  </style>
</head>

<body class="bg-gray-50">
  
  <?php include('./components/header.php'); ?>

  <!-- Breadcrumb mobile -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" data-hs-overlay="#hs-application-sidebar">
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/></svg>
      </button>
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">Navigation
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate">Markets</li>
      </ol>
    </div>
  </div>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <?php
        if (!empty($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
          echo '<div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
              <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
          </div>';
          unset($_SESSION['message']);
        }
      ?>

      <!-- Markets List Component -->
      <div class="space-y-4">
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
          <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
            <div>
              <h2 class="text-xl font-bold text-gray-900">Markets</h2>
              <p class="text-sm text-gray-500 mt-0.5">
                <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> total markets
              </p>
            </div>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm"
                    data-modal-target="addMarketModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14"/><path d="M12 5v14"/>
              </svg>
              Add Market
            </button>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Market</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Location</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Stalls</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Products</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Team</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Display Order</th>
                  <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($market = $result->fetch_assoc()): 
                    // Get product count
                    $prod_count_query = "SELECT COUNT(*) as total FROM market_products WHERE market_id = " . $market['market_id'];
                    $prod_count_result = $conn->query($prod_count_query);
                    $product_count = $prod_count_result->fetch_assoc()['total'];
                    
                    // Get team count
                    $team_count_query = "SELECT COUNT(*) as total FROM market_members WHERE market_id = " . $market['market_id'] . " AND is_active = 1";
                    $team_count_result = $conn->query($team_count_query);
                    $team_count = $team_count_result->fetch_assoc()['total'];
                  ?>
                  <tr class="market-row hover:bg-orange-50/40 transition-colors">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="color-preview" style="background: <?= $market['accent_color'] ?>"></div>
                        <div>
                          <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($market['market_name']) ?></p>
                          <p class="text-xs text-gray-400"><?= htmlspecialchars($market['market_key']) ?></p>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-gray-700"><?= htmlspecialchars($market['location_short']) ?></p>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-blue"><?= $market['stall_count'] ?> stalls</span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-gray"><?= $product_count ?> products</span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-gray"><?= $team_count ?> members</span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-gray"><?= $market['display_order'] ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end gap-1.5">
                        <button onclick="openEditMarketModal(<?= $market['market_id'] ?>)"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                          </svg>
                        </button>
                        <button onclick="openDeleteMarketModal(<?= $market['market_id'] ?>, '<?= htmlspecialchars(addslashes($market['market_name'])) ?>')"
                                class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                      <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                            <path d="M3 3h18v18H3z"/><path d="M8 12h8"/>
                          </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-700">No markets yet</p>
                        <p class="text-xs text-gray-400">Click "Add Market" to get started</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
            <p class="text-sm text-gray-500">
              Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span> of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> markets
            </p>
            <div class="flex items-center gap-1.5">
              <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
                </a>
              <?php else: ?>
                <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
                </span>
              <?php endif; ?>

              <?php
              $start = max(1, $page - 2);
              $end   = min($totalPages, $page + 2);
              if ($start > 1) echo '<a href="?page=1" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
              if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
              for ($i = $start; $i <= $end; $i++):
              ?>
                <a href="?page=<?= $i ?>" class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
                  <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
                  <?= $i ?>
                </a>
              <?php
              endfor;
              if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
              if ($end < $totalPages) echo '<a href="?page=' . $totalPages . '" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">' . $totalPages . '</a>';
              ?>

              <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
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
  </div>

  <!-- ==================== ADD MARKET MODAL ==================== -->
  <div id="addMarketModal" class="modal-overlay hidden">      
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Add New Market</h3>
          <p>Fill in all required fields to create a market</p>
        </div>
        <button class="modal-close" onclick="closeModal('addMarketModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="modal-body">
        <form id="addMarketForm" action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-1">
          
          <p class="section-title">Basic Information</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Market Key <span class="text-red-500">*</span></label>
              <input type="text" name="market_key" placeholder="e.g. navotas" required class="form-input">
              <p class="text-xs text-gray-400 mt-1">Unique identifier (lowercase, no spaces)</p>
            </div>
            <div>
              <label class="form-label">Market Name <span class="text-red-500">*</span></label>
              <input type="text" name="market_name" placeholder="e.g. Navotas Fish Port Complex" required class="form-input">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 mt-3">
            <div>
              <label class="form-label">Location Short <span class="text-red-500">*</span></label>
              <input type="text" name="location_short" placeholder="e.g. Navotas, Metro Manila" required class="form-input">
            </div>
            <div>
              <label class="form-label">Stall Count <span class="text-red-500">*</span></label>
              <input type="number" name="stall_count" placeholder="e.g. 12" required class="form-input">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Location Full <span class="text-red-500">*</span></label>
            <input type="text" name="location_full" placeholder="Full address" required class="form-input">
          </div>

          <div class="mt-3">
            <label class="form-label">Description <span class="text-red-500">*</span></label>
            <textarea name="description" rows="3" placeholder="Market description..." required class="form-input"></textarea>
          </div>

          <div class="mt-3">
            <label class="form-label">Highlights <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-2">Enter one highlight per line</p>
            <textarea name="highlights" rows="4" placeholder="Largest fish port in the Philippines&#10;Open 24 hours&#10;Direct access to Metro Manila markets" required class="form-input"></textarea>
          </div>

          <p class="section-title">Media</p>
          <div class="mt-3">
            <label class="form-label">Main Image</label>
            <input type="file" id="addMainImage" name="main_image" accept="image/*" class="hidden">
            <button type="button" onclick="document.getElementById('addMainImage').click()"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
              📸 Select Main Image
            </button>
            <div id="addMainImagePreview" class="mt-3 hidden">
              <img src="" alt="Preview" class="w-full h-40 object-cover rounded-lg">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Gallery Images</label>
            <input type="file" id="addGalleryImages" name="gallery_images[]" multiple accept="image/*" class="hidden">
            <button type="button" onclick="document.getElementById('addGalleryImages').click()"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
              📸 Select Gallery Images (multiple)
            </button>
            <div id="addGalleryPreview" class="grid grid-cols-4 gap-2 mt-3"></div>
          </div>

          <div class="mt-3">
            <label class="form-label">Map Embed URL</label>
            <input type="text" name="map_embed" placeholder="Google Maps embed iframe src" class="form-input">
          </div>

          <p class="section-title">Styling</p>
          <div class="mt-3">
            <label class="form-label">Accent Color</label>
            <input type="color" name="accent_color" value="#f97316" class="form-input h-10">
          </div>

          <p class="section-title mt-4">Team Members <span class="text-xs font-normal text-gray-400">(optional — can be added later)</span></p>
          <p class="text-xs text-gray-400 mb-2">Photos are saved to <code>uploads/members/</code></p>
          <div id="addMembersContainer"></div>
          <button type="button" id="add-member-btn-add" class="btn-success mt-2">+ Add Team Member</button>


        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeModal('addMarketModal')" class="btn-secondary">Cancel</button>
        <button type="submit" form="addMarketForm" name="add_market" class="btn-primary">
          Add Market
        </button>
      </div>
    </div>
  </div>

  <!-- ==================== EDIT MARKET MODAL ==================== -->
  <div id="editMarketModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Edit Market</h3>
          <p>Update market details, team members, and products</p>
        </div>
        <button class="modal-close" onclick="closeModal('editMarketModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <!-- Content will be loaded via fetch -->
      <div id="editMarketModalContent" class="modal-body"></div>
    </div>
  </div>

  <!-- ==================== DELETE MARKET MODAL ==================== -->
  <div id="deleteMarketModal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:28rem">
      <div class="modal-header">
        <div>
          <h3>Delete Market</h3>
          <p>This action cannot be undone</p>
        </div>
        <button class="modal-close" onclick="closeModal('deleteMarketModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </div>
        <form action="./functions/delete.php" method="POST" id="deleteMarketForm">
          <input type="hidden" name="market_id" id="deleteMarketId">
          <p id="deleteMarketName" class="text-sm font-semibold text-gray-800 mb-1"></p>
          <p class="text-xs text-red-500 mb-5">This will permanently delete the market and all its team members and product links.</p>
          <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeModal('deleteMarketModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="delete_market" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  // Modal helpers
  function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
  
  window.closeModal = function(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';
  };

  // Close on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) closeModal(this.id);
    });
  });

  // Open add modal
  document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', function() {
      openModal(this.getAttribute('data-modal-target'));
    });
  });

  // Main image preview for add modal
  document.getElementById('addMainImage')?.addEventListener('change', function(e) {
    const preview = document.getElementById('addMainImagePreview');
    const img = preview.querySelector('img');
    if (e.target.files && e.target.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        img.src = e.target.result;
        preview.classList.remove('hidden');
      }
      reader.readAsDataURL(e.target.files[0]);
    }
  });

  // Gallery images preview for add modal
  const galleryInput = document.getElementById('addGalleryImages');
  const galleryPreview = document.getElementById('addGalleryPreview');
  let galleryFiles = [];

  galleryInput?.addEventListener('change', function(e) {
    const newFiles = Array.from(e.target.files);
    galleryFiles.push(...newFiles);
    renderGalleryPreview();
  });

  function renderGalleryPreview() {
    galleryPreview.innerHTML = '';
    galleryFiles.forEach((file, i) => {
      const reader = new FileReader();
      reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'image-thumb';
        div.innerHTML = `
          <img src="${e.target.result}" alt="">
          <button type="button" class="del-btn" data-index="${i}">×</button>
        `;
        galleryPreview.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
    const dt = new DataTransfer();
    galleryFiles.forEach(f => dt.items.add(f));
    galleryInput.files = dt.files;
  }

  galleryPreview?.addEventListener('click', function(e) {
    if (e.target.classList.contains('del-btn')) {
      galleryFiles.splice(parseInt(e.target.dataset.index), 1);
      renderGalleryPreview();
    }
  });

  function addMemberRow(container, fileInputName) {
    container.insertAdjacentHTML('beforeend', `
      <div class="member-row">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="form-label">Name <span class="text-red-500">*</span></label>
            <input type="text" name="new_member_name[]" class="form-input" required>
          </div>
          <div>
            <label class="form-label">Position <span class="text-red-500">*</span></label>
            <input type="text" name="new_member_position[]" class="form-input" required>
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="new_member_order[]" value="0" class="form-input">
          </div>
          <div>
            <label class="form-label">Photo</label>
            <input type="file" name="${fileInputName}" accept="image/*"
                    class="form-input text-xs py-1.5">
            <p class="text-xs text-gray-400 mt-1">Saved to uploads/members/</p>
          </div>
          <div class="flex items-end col-span-2">
            <button type="button" class="btn-danger remove-member">Remove</button>
          </div>
        </div>
      </div>
    `);
  }

  /* ── Add Market modal ───────────────────────────────────────────────────────── */
  document.getElementById('add-member-btn-add')?.addEventListener('click', function () {
      addMemberRow(
          document.getElementById('addMembersContainer'),
          'new_member_image_file[]'   // matches what add.php expects
      );
  });

  document.getElementById('addMembersContainer')?.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-member')) {
          e.target.closest('.member-row').remove();
      }
  });

  // Edit market modal
  window.openEditMarketModal = function(marketId) {
    const modal = document.getElementById('editMarketModal');
    const content = document.getElementById('editMarketModalContent');
    
    content.innerHTML = `
      <div class="flex items-center justify-center py-12 text-gray-400">
        <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Loading market data...
      </div>`;
    openModal('editMarketModal');
    
    fetch(`./functions/fetch_markets.php?market_id=${marketId}`)
      .then(r => r.text())
      .then(html => {
        content.innerHTML = html;
        initEditMarketModal(marketId);
      })
      .catch(() => {
        content.innerHTML = '<p class="text-red-500 p-4">Failed to load market. Please try again.</p>';
      });
  };

  function initEditMarketModal(marketId) {
    // Member management
    const memberContainer = document.getElementById('members-container');
    const addMemberBtn = document.getElementById('add-member-btn');
    
    if (addMemberBtn && memberContainer) {
      addMemberBtn.addEventListener('click', function() {
        const memberHtml = `
          <div class="member-row">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="form-label">Name <span class="text-red-500">*</span></label>
                <input type="text" name="new_member_name[]" class="form-input" required>
              </div>
              <div>
                <label class="form-label">Position <span class="text-red-500">*</span></label>
                <input type="text" name="new_member_position[]" class="form-input" required>
              </div>
              <div>
                <label class="form-label">Image URL</label>
                <input type="text" name="new_member_image[]" class="form-input" placeholder="Optional">
              </div>
              <div>
                <label class="form-label">Display Order</label>
                <input type="number" name="new_member_order[]" value="0" class="form-input">
              </div>
              <div class="flex items-end">
                <button type="button" class="btn-danger remove-member">Remove</button>
              </div>
            </div>
          </div>
        `;
        memberContainer.insertAdjacentHTML('beforeend', memberHtml);
      });
      
      memberContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-member')) {
          e.target.closest('.member-row').remove();
        }
      });
    }
    
    // Product management
    const productContainer = document.getElementById('products-container');
    const addProductBtn = document.getElementById('add-product-btn');
    const productOptions = `<?php echo $products_options; ?>`;
    
    if (addProductBtn && productContainer) {
      addProductBtn.addEventListener('click', function() {
        const productHtml = `
          <div class="product-row">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="form-label">Product</label>
                <select name="new_product_id[]" class="form-input" required>
                  <option value="">Select product</option>
                  ${productOptions}
                </select>
              </div>
              <div>
                <label class="form-label">Display Order</label>
                <input type="number" name="new_product_order[]" value="0" class="form-input">
              </div>
              <div class="flex items-end">
                <button type="button" class="btn-danger remove-product">Remove</button>
              </div>
            </div>
          </div>
        `;
        productContainer.insertAdjacentHTML('beforeend', productHtml);
      });
      
      productContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
          if (confirm('Remove this product from market?')) {
            e.target.closest('.product-row').remove();
          }
        }
      });
    }
    
  }

  // Delete market modal
  window.openDeleteMarketModal = function(marketId, marketName) {
    document.getElementById('deleteMarketId').value = marketId;
    document.getElementById('deleteMarketName').textContent = `Are you sure you want to delete "${marketName}"?`;
    openModal('deleteMarketModal');
  };

  // ── Delegated handlers for dynamically loaded edit modal content ──────────────

  // Delete market image (main or gallery)
  document.addEventListener('click', function(e) {
      const btn = e.target.closest('[data-action="delete_market_image"]');
      if (!btn) return;

      const type     = btn.dataset.imageType;
      const marketId = btn.dataset.marketId;
      const label    = type === 'main' ? 'main image' : 'gallery image';
      if (!confirm('Delete this ' + label + '? This cannot be undone.')) return;

      const original    = btn.textContent;
      btn.textContent   = '…';
      btn.disabled      = true;

      let body = 'action=delete_market_image&market_id=' + marketId + '&image_type=' + type;
      if (type === 'gallery') body += '&image=' + encodeURIComponent(btn.dataset.image);

      fetch('./functions/delete.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body
      })
      .then(r => r.json())
      .then(d => {
          if (d.success) {
              if (type === 'main') {
                  const wrapper = document.getElementById(btn.dataset.target);
                  if (wrapper) { wrapper.style.opacity = '0'; setTimeout(() => wrapper.remove(), 200); }
              } else {
                  const thumb = btn.closest('.image-thumb');
                  if (thumb) {
                      thumb.style.opacity = '0';
                      setTimeout(() => {
                          thumb.remove();
                          const grid = document.getElementById('currentGalleryGrid');
                          if (grid && grid.children.length === 0) grid.closest('.mt-3')?.remove();
                      }, 200);
                  }
              }
          } else {
              btn.textContent = original;
              btn.disabled    = false;
              alert('Failed: ' + (d.message || 'Unknown error'));
          }
      })
      .catch(() => { btn.textContent = original; btn.disabled = false; alert('Request failed.'); });
  });

  // Delete existing team member
  document.addEventListener('click', function(e) {
      const btn = e.target.closest('.delete-member-btn');
      if (!btn) return;
      if (!confirm('Delete this team member permanently?')) return;

      const memberId = btn.dataset.memberId;
      const row      = btn.closest('.member-row');

      fetch('./functions/delete.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body   : 'action=delete_market_member&member_id=' + memberId
      })
      .then(r => r.json())
      .then(d => {
          if (d.success) {
              row.style.opacity = '0';
              setTimeout(() => row.remove(), 200);
          } else {
              alert('Failed: ' + (d.message || 'Unknown error'));
          }
      })
      .catch(() => alert('Request failed.'));
  });

  // Delete existing product link
  document.addEventListener('click', function(e) {
      const btn = e.target.closest('.delete-product-link-btn');
      if (!btn) return;
      if (!confirm('Remove this product from market?')) return;

      const linkId = btn.dataset.linkId;
      const row    = btn.closest('.product-row');

      fetch('./functions/delete.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body   : 'action=delete_market_product&link_id=' + linkId
      })
      .then(r => r.json())
      .then(d => {
          if (d.success) {
              row.style.opacity = '0';
              setTimeout(() => row.remove(), 200);
          } else {
              alert('Failed: ' + (d.message || 'Unknown error'));
          }
      })
      .catch(() => alert('Request failed.'));
  });
  </script>

  <?php $conn->close(); ?>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>