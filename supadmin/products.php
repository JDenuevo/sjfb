<?php
session_start();
include '../conn.php';

// Check if the supadmin is logged in as supadmin and account_id exists
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

// Retrieve the logged-in admin's account_id
$account_id = $_SESSION['account_id'];

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Items per page

// First get the total count of products
$countQuery = "SELECT COUNT(DISTINCT p.product_id) as total 
               FROM products p
               LEFT JOIN product_variants v ON p.product_id = v.product_id
               WHERE p.is_deleted = 0";

$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);

// Main query with pagination
$offset = ($page - 1) * $perPage;

// Updated query with proper category linking
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
LEFT JOIN product_variants v ON p.product_id = v.product_id
WHERE p.is_deleted = 0
GROUP BY p.product_id, p.product_name, p.product_description
ORDER BY last_updated DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">

  <!-- CSS Preline -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<style>
  select[multiple] {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none;
}

</style>
<body class="bg-gray-50">
  
  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- ========== MAIN CONTENT ========== -->
  <!-- Breadcrumb -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <!-- Navigation Toggle -->
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
        <span class="sr-only">Toggle Navigation</span>
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2" />
          <path d="M15 3v18" />
          <path d="m8 9 3 3-3 3" />
        </svg>
      </button>
      <!-- End Navigation Toggle -->

      <!-- Breadcrumb -->
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">
          Navigation
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate" aria-current="page">
          Products
        </li>
      </ol>
      <!-- End Breadcrumb -->
    </div>
  </div>
  <!-- End Breadcrumb -->

  <!-- Sidebar -->
  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      <?php
        if (!empty($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';

            echo '
            <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-lg p-4" role="alert">
                <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
            </div>';

            unset($_SESSION['message']); // Clear message after displaying it
        }

        if (!empty($_SESSION['upload_errors'])) {
            echo '<div class="mt-2 bg-red-500 text-white text-sm rounded-lg p-4" role="alert">';
            foreach ($_SESSION['upload_errors'] as $error) {
                echo '<p>' . htmlspecialchars($error) . '</p>';
            }
            echo '</div>';

            unset($_SESSION['upload_errors']); // Clear errors after displaying
        }
      ?>
      
      <!-- Table Card -->
      <?php include('./components/product_list.php'); ?>
      <!-- Table End -->

    </div>
  </div>
  <!-- End Content -->

  <div id="addProductModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
      <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
          <div class="overflow-y-auto flex-1">
              <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Product</h3>
              
              <form action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                      <!-- Product Name -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Product Name</label>
                          <input type="text" name="product_name" placeholder="Product Name" required class="w-full px-3 py-2 border rounded-lg">
                      </div>

                      <!-- Product Unit -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Product Unit</label>
                          <input type="text" name="product_unit" placeholder="Unit" required class="w-full px-3 py-2 border rounded-lg">
                      </div>
                  </div>

                  <!-- Product Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Product Description</label>
                    <textarea name="product_description" rows="3" class="py-2 px-3 sm:py-3 sm:px-4 block w-full border rounded-lg" rows="3" placeholder="Product Description"></textarea>
                  </div>

                  <!-- Product Nickname -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Product Nickname/Tags</label>
                      <input type="text" name="product_nickname" placeholder="Nickname/Tags" required class="w-full px-3 py-2 border rounded-lg">
                  </div>

                  <!-- Variants Section -->
                  <h4 class="font-semibold text-lg text-gray-800">Variants</h4>

                  <!-- Dynamic Variant Container -->
                  <div id="variantContainer">
                      <!-- Default Variant Row -->
                      <div class="grid grid-cols-5 gap-2 py-2 pb-4 border-b variantRow">
                          <div>
                              <label class="block text-xs font-medium text-gray-700">Size</label>
                              <input type="text" name="variant_name[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Size" required>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Unit</label>
                              <select name="unit_type[]" class="w-full px-3 py-2 border rounded-lg" required>
                                  <option value="kg">Kilogram</option>
                                  <option value="piece">Piece</option>
                                  <option value="gram">Gram</option>
                              </select>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Price</label>
                              <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Price" required>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Discount</label>
                              <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Discount">
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Min Order</label>
                              <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="Min Order" required>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Increment</label>
                              <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="1" required>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Stock</label>
                              <input type="number" min="0" name="stock_quantity[]" class="w-full px-3 py-2 border rounded-lg" placeholder="0" required>
                          </div>

                          <!-- Variant Categories -->
                          <div class="col-span-2">
                              <label class="block text-xs font-medium text-gray-700">Categories</label>
                              <select name="variant_categories[][]" multiple class="w-full px-3 py-2 border rounded-lg text-sm" size="2">
                                  <option value="">Inherit from product</option>
                                  <?php
                                  $cat_sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_name";
                                  $cat_result = mysqli_query($conn, $cat_sql);
                                  while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                      echo "<option value=\"{$cat_row['category_id']}\">" . htmlspecialchars($cat_row['category_name']) . "</option>";
                                  }
                                  ?>
                              </select>
                              <p class="text-xs text-gray-500">Leave empty to use product categories</p>
                          </div>

                          <div>
                              <label class="block text-xs font-medium text-gray-700">Action</label>
                              <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white rounded-lg">
                                  🗑 Delete
                              </button>
                          </div>
                      </div>
                  </div>

                  <!-- Add Variant Button -->
                  <div class="flex justify-end mt-3">
                      <button type="button" id="addVariant" style="background-color: #22c55e;" class="py-2 px-3 items-center text-sm font-medium rounded-lg border border-transparent text-white">+ Add Variant</button>
                  </div>

                  <!-- Primary Category Selection -->
                  <div class="col-span-2">
                      <label class="block text-sm font-medium text-gray-700">Primary Category</label>
                      <select name="primary_category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                          <option value="0">— Select Primary Category —</option>
                          <?php
                          $sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_name";
                          $result = mysqli_query($conn, $sql);
                          while ($row = mysqli_fetch_assoc($result)) {
                              echo "<option value=\"{$row['category_id']}\">" . htmlspecialchars($row['category_name']) . "</option>";
                          }
                          ?>
                      </select>
                      <p class="text-xs text-gray-500 mt-1">This category will be shown as the main category</p>
                  </div>

                  <!-- Product Categories - Multi-select -->
                  <div class="col-span-2">
                      <label class="block text-sm font-medium text-gray-700">Categories *</label>
                      <div class="space-y-2 border rounded-lg p-3">
                          <div class="flex items-center justify-between mb-2">
                              <span class="text-sm font-medium">Select categories for this product</span>
                              <span class="text-xs text-gray-500">Hold Ctrl to select multiple</span>
                          </div>
                          <select name="product_categories[]" multiple required size="5" 
                                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                              <?php
                              $sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_level, category_name";
                              $result = mysqli_query($conn, $sql);
                              while ($row = mysqli_fetch_assoc($result)) {
                                  $indent = str_repeat('&nbsp;&nbsp;', $row['category_level'] - 1);
                                  echo "<option value=\"{$row['category_id']}\">{$indent} " . htmlspecialchars($row['category_name']) . "</option>";
                              }
                              ?>
                          </select>
                          <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple categories</p>
                      </div>
                  </div>

                  <!-- Product Images Upload -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Upload Product Images</label>
                      <input type="file" id="productImages" name="product_images[]" multiple required class="hidden" accept="image/*">
                      <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('productImages').click()">📸 Select Images</button>
                      <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>

                      <!-- Image Preview Section -->
                      <div id="imagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
                  </div>

                  <div class="flex justify-end space-x-3 mt-4">
                    <button type="submit" name="add_product" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700">Add Product</button>
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200" onclick="document.getElementById('addProductModal').classList.add('hidden')">Cancel</button>
                  </div>
              </form>
          </div>
      </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
        // ==========================================
        // UTILITY FUNCTIONS
        // ==========================================
        
        window.closeModal = function(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        };

        function showAlert(message, type = 'success') {
            alert(message); // You can replace this with a better toast notification
        }

        // ==========================================
        // ADD PRODUCT MODAL
        // ==========================================
        
        // Open modal
        document.querySelectorAll("[data-modal-target]").forEach(button => {
            button.addEventListener("click", function() {
                const modalId = this.getAttribute("data-modal-target");
                document.getElementById(modalId).classList.remove("hidden");
            });
        });

        // Add variant functionality
        const addVariantContainer = document.getElementById("variantContainer");
        const addVariantBtn = document.getElementById("addVariant");
        const categoryOptions = `<?php
                    $cat_sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_name";
                    $cat_result = mysqli_query($conn, $cat_sql);
                    $options = '';
                    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                        $options .= '<option value="' . $cat_row['category_id'] . '">' . htmlspecialchars($cat_row['category_name']) . '</option>';
                    }
                    echo $options;
                ?>`;

        if (addVariantBtn && addVariantContainer) {
            addVariantBtn.addEventListener("click", function() {
                const variantHTML = `
                    <div class="grid grid-cols-5 gap-2 py-2 pb-4 border-b variantRow">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Size</label>
                            <input type="text" name="variant_name[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Size" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Unit</label>
                            <select name="unit_type[]" class="w-full px-3 py-2 border rounded-lg" required>
                                <option value="kg">Kilogram</option>
                                <option value="piece">Piece</option>
                                <option value="gram">Gram</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Price</label>
                            <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Price" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Discount</label>
                            <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Discount">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Min Order</label>
                            <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="Min Order" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Increment</label>
                            <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="Increment" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Stock</label>
                            <input type="number" min="0" name="stock_quantity[]" class="w-full px-3 py-2 border rounded-lg" placeholder="0" required>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700">Categories</label>
                            <select name="variant_categories[][]" multiple class="w-full px-3 py-2 border rounded-lg text-sm" size="2">
                                <option value="">Inherit from product</option>
                                ${categoryOptions}
                            </select>
                            <p class="text-xs text-gray-500">Leave empty to use product categories</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Action</label>
                            <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                               🗑 Delete
                            </button>
                        </div>
                    </div>
                `;
                addVariantContainer.insertAdjacentHTML("beforeend", variantHTML);
            });

            // Delete variant in add modal
            addVariantContainer.addEventListener("click", function(e) {
                if (e.target.classList.contains("removeVariant")) {
                    e.target.closest(".variantRow").remove();
                }
            });
        }

        // Image preview for add modal
        const productImages = document.getElementById("productImages");
        const imagePreview = document.getElementById("imagePreview");
        let selectedFiles = [];

        if (productImages && imagePreview) {
            productImages.addEventListener("change", function(e) {
                const newFiles = Array.from(e.target.files);
                if (selectedFiles.length + newFiles.length > 5) {
                    alert("You can only upload up to 5 images.");
                    return;
                }
                selectedFiles.push(...newFiles);
                updateImagePreview();
            });

            function updateImagePreview() {
                imagePreview.innerHTML = "";
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement("div");
                        div.className = "relative";
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border">
                            <button type="button" class="remove-image absolute top-0 right-0 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center" data-index="${index}">
                                ×
                            </button>
                        `;
                        imagePreview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });

                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => dataTransfer.items.add(file));
                productImages.files = dataTransfer.files;
            }

            imagePreview.addEventListener("click", function(e) {
                if (e.target.classList.contains("remove-image")) {
                    const index = parseInt(e.target.dataset.index);
                    selectedFiles.splice(index, 1);
                    updateImagePreview();
                }
            });
        }

        // ==========================================
        // EDIT PRODUCT MODAL
        // ==========================================
        
        window.openEditModal = function(productId) {
            fetch(`./functions/fetch_products.php?product_id=${productId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    document.getElementById('editProductModal').classList.remove('hidden');
                    initializeUpdateModal();
                })
                .catch(error => console.error('Error:', error));
        };

        function initializeUpdateModal() {
            const deletedVariants = new Set();
            const deletedImages = new Set();
            let selectedNewFiles = [];

            // Handle variant deletion
            document.querySelectorAll('.variantRow').forEach(row => {
                const deleteBtn = row.querySelector('.removeVariant');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        const variantId = row.dataset.variantId;
                        if (variantId) {
                            deletedVariants.add(variantId);
                            document.getElementById('deletedVariants').value = Array.from(deletedVariants).join(',');
                        }
                        row.remove();
                    });
                }
            });

            // Add new variant in update modal
            const addVariantBtn = document.querySelector('.addVariant');
            if (addVariantBtn) {
                addVariantBtn.addEventListener("click", function() {
                const categoryOptions = `<?php
                    $cat_sql = "SELECT * FROM product_categories WHERE is_active = 1 ORDER BY category_name";
                    $cat_result = mysqli_query($conn, $cat_sql);
                    $options = '';
                    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                        $options .= '<option value="' . $cat_row['category_id'] . '">' . htmlspecialchars($cat_row['category_name']) . '</option>';
                    }
                    echo $options;
                ?>`;
                
                const variantHTML = `
                    <div class="grid grid-cols-5 gap-2 py-2 pb-4 border-b variantRow">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Size</label>
                            <input type="text" name="variant_name[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Size" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Unit</label>
                            <select name="unit_type[]" class="w-full px-3 py-2 border rounded-lg" required>
                                <option value="kg">Kilogram</option>
                                <option value="piece">Piece</option>
                                <option value="gram">Gram</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Price</label>
                            <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Price" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Discount</label>
                            <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-3 py-2 border rounded-lg" placeholder="Discount">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Min Order</label>
                            <input type="number" name="minimum_order[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="Min Order" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Increment</label>
                            <input type="number" name="order_increment[]" value="1" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg" placeholder="Increment" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Stock</label>
                            <input type="number" min="0" name="stock_quantity[]" class="w-full px-3 py-2 border rounded-lg" placeholder="0" required>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-700">Categories</label>
                            <select name="variant_categories[][]" multiple class="w-full px-3 py-2 border rounded-lg text-sm" size="2">
                                <option value="">Inherit from product</option>
                                ${categoryOptions}
                            </select>
                            <p class="text-xs text-gray-500">Leave empty to use product categories</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Action</label>
                            <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                              🗑 Delete
                            </button>
                        </div>
                    </div>
                `;
                addVariantContainer.insertAdjacentHTML("beforeend", variantHTML);
              });
            }

            // Handle image deletion
            document.querySelectorAll('.delete-image-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const imageDiv = this.closest('.current-image');
                    const imageId = imageDiv.dataset.imageId;
                    
                    if (confirm('Delete this image?')) {
                        deletedImages.add(imageId);
                        document.getElementById('deletedImages').value = Array.from(deletedImages).join(',');
                        imageDiv.remove();
                        
                        const container = document.getElementById('currentImagesContainer');
                        if (container.querySelectorAll('.current-image').length === 0) {
                            container.innerHTML = '<p class="text-gray-500 text-sm col-span-5">No images</p>';
                        }
                    }
                });
            });

            // Handle new image selection in update modal
            const newImageInput = document.getElementById('newImageInput');
            const newImagePreview = document.getElementById('newImagePreview');
            
            if (newImageInput && newImagePreview) {
                newImageInput.addEventListener('change', function(e) {
                    const newFiles = Array.from(e.target.files);
                    const currentImageCount = document.querySelectorAll('.current-image').length;
                    const totalCount = currentImageCount + selectedNewFiles.length + newFiles.length;
                    
                    if (totalCount > 5) {
                        alert('Maximum 5 images allowed total');
                        return;
                    }
                    
                    selectedNewFiles.push(...newFiles);
                    updateNewImagePreview();
                });

                function updateNewImagePreview() {
                    newImagePreview.innerHTML = '';
                    selectedNewFiles.forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'relative group';
                            div.innerHTML = `
                                <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg shadow">
                                <button type="button" class="remove-new-image absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600" data-index="${index}">
                                    ×
                                </button>
                            `;
                            newImagePreview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    });
                    
                    const dataTransfer = new DataTransfer();
                    selectedNewFiles.forEach(file => dataTransfer.items.add(file));
                    newImageInput.files = dataTransfer.files;
                }

                newImagePreview.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-new-image')) {
                        const index = parseInt(e.target.dataset.index);
                        selectedNewFiles.splice(index, 1);
                        updateNewImagePreview();
                    }
                });
            }
        }

        // ==========================================
        // DELETE PRODUCT MODAL
        // ==========================================
        
        window.openDeleteModal = function(productId, productName) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteProductName').innerText = `Are you sure you want to delete ${productName}?`;
            document.getElementById('deleteProductModal').classList.remove('hidden');
        };
    });
    </script>

  <?php $conn->close(); ?>

  <!-- JS Implementing Plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>


