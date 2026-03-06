<!-- products.php -->
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

$countQuery = "SELECT COUNT(DISTINCT p.product_id) as total 
               FROM products p
               LEFT JOIN product_variants v ON p.product_id = v.product_id
               WHERE p.is_deleted = 0";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

$query = "SELECT
    p.*,
    GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS category_names,
    GROUP_CONCAT(DISTINCT pc.category_id SEPARATOR ',') AS category_ids,
    IFNULL(MAX(v.stock_status), 'Out of Stock') AS stock_status,
    GROUP_CONCAT(DISTINCT v.variant_name ORDER BY v.created_at DESC SEPARATOR ', ') AS variants,
    GROUP_CONCAT(DISTINCT v.variant_price ORDER BY v.created_at DESC SEPARATOR ', ') AS prices,
    GROUP_CONCAT(DISTINCT v.discount_price ORDER BY v.created_at DESC SEPARATOR ', ') AS discount_prices,
    GROUP_CONCAT(DISTINCT v.stock_quantity ORDER BY v.created_at DESC SEPARATOR ', ') AS stock_quantities,
    MAX(v.created_at) AS last_updated
FROM products p
LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0
WHERE p.is_deleted = 0
GROUP BY p.product_id, p.product_name, p.product_description
ORDER BY last_updated DESC
LIMIT $perPage OFFSET $offset";
$result = $conn->query($query);

// Fetch categories once for reuse
$cat_sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_name";
$cat_result_global = mysqli_query($conn, $cat_sql);
$all_categories_arr = [];
while ($cr = mysqli_fetch_assoc($cat_result_global)) {
  $all_categories_arr[] = $cr;
}
$category_options_html = '';
foreach ($all_categories_arr as $cr) {
  $category_options_html .= '<option value="' . $cr['category_id'] . '">' . htmlspecialchars($cr['category_name']) . '</option>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products | St. Joseph Fish Brokerage Inc.</title>

  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">

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

    .variant-row {
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
      width: 100%;
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
        <li class="text-sm font-semibold text-gray-800 truncate">Products</li>
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
        if (!empty($_SESSION['upload_errors'])) {
          echo '<div class="mt-2 bg-red-500 text-white text-sm rounded-xl p-4">';
          foreach ($_SESSION['upload_errors'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
          }
          echo '</div>';
          unset($_SESSION['upload_errors']);
        }
      ?>

      <?php include('./components/product_list.php'); ?>

    </div>
  </div>

  <!-- ==================== ADD PRODUCT MODAL ==================== -->
  <div id="addProductModal" class="modal-overlay hidden">      
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Add New Product</h3>
          <p>Fill in all required fields to create a product</p>
        </div>
        <button class="modal-close" onclick="closeModal('addProductModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="modal-body">
        <form id="addProductForm" action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-1">
          
          <p class="section-title">Basic Information</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Product Name <span class="text-red-500">*</span></label>
              <input type="text" name="product_name" placeholder="e.g. Bangus" required class="form-input">
            </div>
            <div>
              <label class="form-label">Product Unit <span class="text-red-500">*</span></label>
              <input type="text" name="product_unit" placeholder="e.g. per kg" required class="form-input">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="product_description" rows="2" placeholder="Short product description..." class="form-input" style="resize:none"></textarea>
          </div>

          <div class="mt-3">
            <label class="form-label">Nickname / Tags <span class="text-red-500">*</span></label>
            <input type="text" name="product_nickname" placeholder="e.g. bangus, milkfish" required class="form-input">
          </div>

          <p class="section-title">Variants</p>
          <!-- ADD MODAL variant container — ID scoped to add modal -->
          <div id="addVariantContainer" class="space-y-2">
            <div class="variant-row">
              <div class="grid grid-cols-4 gap-2">
                <div>
                  <label class="form-label">Size / Name <span class="text-red-500">*</span></label>
                  <input type="text" name="variant_name[]" class="form-input" placeholder="e.g. Small" required>
                </div>
                <div>
                  <label class="form-label">Unit <span class="text-red-500">*</span></label>
                  <select name="unit_type[]" class="form-input" required>
                    <option value="kg">Kilogram</option>
                    <option value="piece">Piece</option>
                    <option value="gram">Gram</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Price <span class="text-red-500">*</span></label>
                  <input type="number" min="0" step="0.01" name="variant_price[]" class="form-input" placeholder="0.00" required>
                </div>
                <div>
                  <label class="form-label">Discount Price</label>
                  <input type="number" min="0" step="0.01" name="discount_price[]" class="form-input" placeholder="0.00">
                </div>
                <div>
                  <label class="form-label">Min Order <span class="text-red-500">*</span></label>
                  <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="form-input" required>
                </div>
                <div>
                  <label class="form-label">Increment <span class="text-red-500">*</span></label>
                  <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="form-input" required>
                </div>
                <div>
                  <label class="form-label">Stock <span class="text-red-500">*</span></label>
                  <input type="number" min="0" name="stock_quantity[]" class="form-input" placeholder="0" required>
                </div>
                <div>
                  <label class="form-label">Action</label>
                  <button type="button" class="btn-danger removeAddVariant">🗑 Remove</button>
                </div>
              </div>
              <div class="mt-2">
                <label class="form-label">Variant Categories</label>
                <select name="variant_categories[][]" multiple class="form-input text-sm" size="2">
                  <option value="">Inherit from product</option>
                  <?php echo $category_options_html; ?>
                </select>
                <p class="text-xs text-gray-400 mt-1">Leave empty to use product categories</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end mt-2">
            <!-- This button only adds variants to addVariantContainer -->
            <button type="button" id="addVariantBtn" class="btn-success">
              + Add Variant
            </button>
          </div>

          <p class="section-title">Categories</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Primary Category</label>
              <select name="primary_category" class="form-input">
                <option value="0">— Select Primary —</option>
                <?php
                foreach ($all_categories_arr as $cr) {
                  echo '<option value="' . $cr['category_id'] . '">' . htmlspecialchars($cr['category_name']) . '</option>';
                }
                ?>
              </select>
            </div>
          </div>

          <p class="section-title">Product Images</p>
          <div>
            <input type="file" id="addProductImages" name="product_images[]" multiple class="hidden" accept="image/*">
            <button type="button" onclick="document.getElementById('addProductImages').click()"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
              📸 Click to select images (up to 5)
            </button>
            <div id="addImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeModal('addProductModal')" class="btn-secondary">Cancel</button>
        <button type="submit" form="addProductForm" name="add_product" class="btn-primary">
          Add Product
        </button>
      </div>
    </div>
  </div>

  <!-- ==================== EDIT PRODUCT MODAL ==================== -->
  <div id="editProductModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Edit Product</h3>
          <p>Update product details below</p>
        </div>
        <button class="modal-close" onclick="closeModal('editProductModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <!-- Content injected via fetch -->
      <div id="modalContent" class="modal-body"></div>
    </div>
  </div>

  <!-- ==================== DELETE PRODUCT MODAL ==================== -->
  <div id="deleteProductModal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:28rem">
      <div class="modal-header">
        <div>
          <h3>Delete Product</h3>
          <p>This action cannot be undone</p>
        </div>
        <button class="modal-close" onclick="closeModal('deleteProductModal')">
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
        <form action="./functions/delete.php" method="POST" id="deleteForm">
          <input type="hidden" name="product_id" id="deleteProductId">
          <p id="deleteProductName" class="text-sm font-semibold text-gray-800 mb-1"></p>
          <p class="text-xs text-red-500 mb-5">This will permanently delete the product and all its variants.</p>
          <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeModal('deleteProductModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="delete_product" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  // ─── Shared category options string (from PHP) ────────────────
  const CATEGORY_OPTIONS = `<?php echo addslashes($category_options_html); ?>`;

  // ─── Variant row HTML builder ─────────────────────────────────
  function buildVariantRow(removeBtnClass) {
    return `
      <div class="variant-row">
        <div class="grid grid-cols-4 gap-2">
          <div>
            <label class="form-label">Size / Name <span class="text-red-500">*</span></label>
            <input type="text" name="variant_name[]" class="form-input" placeholder="e.g. Small" required>
          </div>
          <div>
            <label class="form-label">Unit <span class="text-red-500">*</span></label>
            <select name="unit_type[]" class="form-input" required>
              <option value="kg">Kilogram</option>
              <option value="piece">Piece</option>
              <option value="gram">Gram</option>
            </select>
          </div>
          <div>
            <label class="form-label">Price <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="0.01" name="variant_price[]" class="form-input" placeholder="0.00" required>
          </div>
          <div>
            <label class="form-label">Discount Price</label>
            <input type="number" min="0" step="0.01" name="discount_price[]" class="form-input" placeholder="0.00">
          </div>
          <div>
            <label class="form-label">Min Order <span class="text-red-500">*</span></label>
            <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="form-input" required>
          </div>
          <div>
            <label class="form-label">Increment <span class="text-red-500">*</span></label>
            <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="form-input" required>
          </div>
          <div>
            <label class="form-label">Stock <span class="text-red-500">*</span></label>
            <input type="number" min="0" name="stock_quantity[]" class="form-input" placeholder="0" required>
          </div>
          <div>
            <label class="form-label">Action</label>
            <button type="button" class="btn-danger ${removeBtnClass}">🗑 Remove</button>
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label">Variant Categories</label>
          <select name="variant_categories[][]" multiple class="form-input text-sm" size="2">
            <option value="">Inherit from product</option>
            ${CATEGORY_OPTIONS}
          </select>
          <p class="text-xs text-gray-400 mt-1">Leave empty to use product categories</p>
        </div>
      </div>
    `;
  }

  // ─── Modal helpers ────────────────────────────────────────────
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

  // ─── Open add modal via data-modal-target ─────────────────────
  document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', function() {
      openModal(this.getAttribute('data-modal-target'));
    });
  });

  // ════════════════════════════════════════════════════════════════
  // ADD MODAL — variant logic scoped to #addVariantContainer ONLY
  // ════════════════════════════════════════════════════════════════
  (function() {
    const container = document.getElementById('addVariantContainer');
    const addBtn    = document.getElementById('addVariantBtn');

    // Add new variant row — inserts ONLY into addVariantContainer
    addBtn.addEventListener('click', function() {
      container.insertAdjacentHTML('beforeend', buildVariantRow('removeAddVariant'));
    });

    // Delete variant row — event delegation on addVariantContainer
    container.addEventListener('click', function(e) {
      if (e.target.classList.contains('removeAddVariant')) {
        e.target.closest('.variant-row').remove();
      }
    });

    // ── Image preview for add modal ──
    const input   = document.getElementById('addProductImages');
    const preview = document.getElementById('addImagePreview');
    let files = [];

    input.addEventListener('change', function(e) {
      const newFiles = Array.from(e.target.files);
      if (files.length + newFiles.length > 5) {
        alert('Maximum 5 images allowed.');
        return;
      }
      files.push(...newFiles);
      renderAddPreview();
    });

    function renderAddPreview() {
      preview.innerHTML = '';
      files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = function(e) {
          const div = document.createElement('div');
          div.className = 'image-thumb';
          div.innerHTML = `
            <img src="${e.target.result}" alt="">
            <button type="button" class="del-btn" data-index="${i}">×</button>
          `;
          preview.appendChild(div);
        };
        reader.readAsDataURL(file);
      });
      const dt = new DataTransfer();
      files.forEach(f => dt.items.add(f));
      input.files = dt.files;
    }

    preview.addEventListener('click', function(e) {
      if (e.target.classList.contains('del-btn')) {
        files.splice(parseInt(e.target.dataset.index), 1);
        renderAddPreview();
      }
    });
  })();

  // ════════════════════════════════════════════════════════════════
  // EDIT MODAL — fetch product data, then init scoped to #editProductModal
  // ════════════════════════════════════════════════════════════════
  window.openEditModal = function(productId) {
    const modal   = document.getElementById('editProductModal');
    const content = document.getElementById('modalContent');

    content.innerHTML = `
      <div class="flex items-center justify-center py-12 text-gray-400">
        <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Loading product data...
      </div>`;
    openModal('editProductModal');

    fetch(`./functions/fetch_products.php?product_id=${productId}`)
      .then(r => r.text())
      .then(html => {
        content.innerHTML = html;
        initEditModal(modal); // pass the modal element as scope
      })
      .catch(() => {
        content.innerHTML = '<p class="text-red-500 p-4">Failed to load product. Please try again.</p>';
      });
  };

  // ─── AJAX: delete a variant immediately from the database using consolidated delete.php ───
  function ajaxDeleteVariant(variantId, rowEl) {
    // Disable button & show loading state
    const btn = rowEl.querySelector('.removeEditVariant');
    if (btn) { 
      btn.disabled = true; 
      btn.textContent = '⏳ Deleting...'; 
    }

    // Use URLSearchParams instead of FormData for simpler AJAX
    const params = new URLSearchParams();
    params.append('action', 'delete_variant');
    params.append('variant_id', variantId);

    fetch('./functions/delete.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Animate removal
        rowEl.style.transition = 'opacity 0.25s, transform 0.25s';
        rowEl.style.opacity = '0';
        rowEl.style.transform = 'translateX(8px)';
        setTimeout(() => {
          rowEl.remove();
          // Show "no variants" message if container is now empty
          const container = document.querySelector('.updateVariantContainer');
          if (container && container.querySelectorAll('.variant-row').length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-sm" id="noVariantsMsg">No variants yet. Add one below.</p>';
          }
        }, 250);
      } else {
        if (btn) { 
          btn.disabled = false; 
          btn.textContent = '🗑 Remove'; 
        }
        alert('Failed to delete variant: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      if (btn) { 
        btn.disabled = false; 
        btn.textContent = '🗑 Remove'; 
      }
      alert('Network error. Please try again.');
    });
  }

  function initEditModal(modal) {
    const variantContainer = modal.querySelector('.updateVariantContainer');

    // ── AJAX delete for existing variant rows using consolidated delete.php ──
    variantContainer?.querySelectorAll('.variant-row').forEach(row => {
      row.querySelector('.removeEditVariant')?.addEventListener('click', function() {
        const variantId = this.dataset.variantId || row.dataset.variantId;
        if (!variantId) {
          // New row (no DB record yet) — just remove from DOM
          row.remove();
          return;
        }
        if (!confirm('Delete this variant permanently? This cannot be undone.')) return;
        ajaxDeleteVariant(variantId, row);
      });
    });

    // ── Add new variant row (new rows have no variant_id = not in DB yet) ──
    const addBtn = modal.querySelector('.addEditVariantBtn');
    if (addBtn && variantContainer) {
      addBtn.addEventListener('click', function() {
        // Remove "no variants" placeholder if present
        const placeholder = variantContainer.querySelector('#noVariantsMsg');
        if (placeholder) placeholder.remove();

        const html = `
          <div class="variant-row" data-variant-id="">
            <div class="grid grid-cols-4 gap-2">
              <div>
                <label class="form-label">Size / Name <span class="text-red-500">*</span></label>
                <input type="text" name="variant_name[]" class="form-input" placeholder="e.g. Small" required>
              </div>
              <div>
                <label class="form-label">Unit <span class="text-red-500">*</span></label>
                <select name="unit_type[]" class="form-input" required>
                  <option value="kg">Kilogram</option>
                  <option value="piece">Piece</option>
                  <option value="gram">Gram</option>
                </select>
              </div>
              <div>
                <label class="form-label">Price <span class="text-red-500">*</span></label>
                <input type="number" min="0" step="0.01" name="variant_price[]" class="form-input" placeholder="0.00" required>
              </div>
              <div>
                <label class="form-label">Discount</label>
                <input type="number" min="0" step="0.01" name="discount_price[]" class="form-input" placeholder="0.00">
              </div>
              <div>
                <label class="form-label">Min Order <span class="text-red-500">*</span></label>
                <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="form-input" required>
              </div>
              <div>
                <label class="form-label">Increment <span class="text-red-500">*</span></label>
                <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="form-input" required>
              </div>
              <div>
                <label class="form-label">Stock <span class="text-red-500">*</span></label>
                <input type="number" min="0" name="stock_quantity[]" class="form-input" placeholder="0" required>
              </div>
              <div>
                <label class="form-label">Action</label>
                <button type="button" class="btn-danger removeEditVariant" data-variant-id="">🗑 Remove</button>
              </div>
            </div>
            <div class="mt-2">
              <label class="form-label">Variant Categories</label>
              <select name="variant_categories[new_${Date.now()}][]" multiple class="form-input text-sm" size="2">
                <option value="">Inherit from product</option>
                ${CATEGORY_OPTIONS}
              </select>
              <p class="text-xs text-gray-400 mt-1">Leave empty to inherit product categories</p>
            </div>
          </div>
        `;
        variantContainer.insertAdjacentHTML('beforeend', html);

        // Wire up the remove button for this new (unsaved) row — just DOM removal
        const newRow = variantContainer.lastElementChild;
        newRow.querySelector('.removeEditVariant')?.addEventListener('click', function() {
          newRow.remove();
        });
      });
    }

    // ── Existing image deletion (unchanged) ──
    const deletedImages = new Set();
    modal.querySelectorAll('.delete-image-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const imageDiv = this.closest('.current-image');
        const imageId  = imageDiv.dataset.imageId;
        if (confirm('Delete this image?')) {
          deletedImages.add(imageId);
          const inp = modal.querySelector('#deletedImages');
          if (inp) inp.value = Array.from(deletedImages).join(',');
          imageDiv.remove();
          const container = modal.querySelector('#currentImagesContainer');
          if (container && container.querySelectorAll('.current-image').length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-sm col-span-5">No images remaining</p>';
          }
        }
      });
    });

    // ── New image upload in edit modal (unchanged) ──
    let selectedNewFiles = [];
    const newImageInput  = modal.querySelector('#newImageInput');
    const newImagePreview = modal.querySelector('#newImagePreview');

    if (newImageInput && newImagePreview) {
      newImageInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        const currentCount = modal.querySelectorAll('.current-image').length;
        if (currentCount + selectedNewFiles.length + newFiles.length > 5) {
          alert('Maximum 5 images allowed total.');
          return;
        }
        selectedNewFiles.push(...newFiles);
        renderEditImagePreview();
      });

      function renderEditImagePreview() {
        newImagePreview.innerHTML = '';
        selectedNewFiles.forEach((file, i) => {
          const reader = new FileReader();
          reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-thumb';
            div.innerHTML = `
              <img src="${e.target.result}" alt="">
              <button type="button" class="del-btn remove-new-image" data-index="${i}">×</button>
            `;
            newImagePreview.appendChild(div);
          };
          reader.readAsDataURL(file);
        });
        const dt = new DataTransfer();
        selectedNewFiles.forEach(f => dt.items.add(f));
        newImageInput.files = dt.files;
      }

      newImagePreview.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-new-image')) {
          selectedNewFiles.splice(parseInt(e.target.dataset.index), 1);
          renderEditImagePreview();
        }
      });
    }
  }

  // ════════════════════════════════════════════════════════════════
  // DELETE MODAL
  // ════════════════════════════════════════════════════════════════
  window.openDeleteModal = function(productId, productName) {
    document.getElementById('deleteProductId').value = productId;
    document.getElementById('deleteProductName').textContent = `Are you sure you want to delete "${productName}"?`;
    openModal('deleteProductModal');
  };
  </script>

  <?php $conn->close(); ?>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>