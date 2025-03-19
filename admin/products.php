<?php
session_start();
include '../conn.php';

// Check if the admin is logged in as admin and account_id exists
if (!isset($_SESSION["loggedinasadmin"]) || $_SESSION["loggedinasadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

// Retrieve the logged-in admin's account_id
$account_id = $_SESSION['account_id'];

$query = "SELECT
    p.product_id,
    p.product_name,
    p.product_description,
    c.category_name,
    IFNULL(MAX(v.stock_status), 'Out of Stock') AS stock_status,
    GROUP_CONCAT(DISTINCT v.variant_name ORDER BY v.created_at DESC SEPARATOR ', ') AS variants,
    GROUP_CONCAT(DISTINCT v.variant_price ORDER BY v.created_at DESC SEPARATOR ', ') AS prices,
    GROUP_CONCAT(DISTINCT v.discount_price ORDER BY v.created_at DESC SEPARATOR ', ') AS discount_prices,
    GROUP_CONCAT(DISTINCT v.stock_quantity ORDER BY v.created_at DESC SEPARATOR ', ') AS stock_quantities,
    MAX(v.created_at) AS last_updated
FROM products p
LEFT JOIN product_categories c ON p.product_category = c.category_id
LEFT JOIN product_variants v ON p.product_id = v.product_id
GROUP BY p.product_id, p.product_name, p.product_description, c.category_name
ORDER BY last_updated DESC;";

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

  <div id="addProductModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto">
    <div class="bg-white p-6 rounded-2xl shadow-2xl w-11/12 sm:w-4/5 md:w-3/4 lg:max-w-3xl xl:max-w-3xl max-h-[50vh] flex flex-col">
        <div class="overflow-y-auto max-h-[40vh]">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Product</h3>
            
            <form action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <!-- Product Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="product_name" placeholder="Product Name" required class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <!-- Product Description -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Product Description</label>
                      <input type="text" name="product_description" placeholder="Description" required class="w-full px-3 py-2 border rounded-lg">
                  </div>

                  <!-- Product Category -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Category</label>
                      <select name="product_category" required class="w-full px-3 py-2 border rounded-lg">
                          <option value="" disabled selected>Select a category</option>
                          <?php
                          $sql = "SELECT * FROM product_categories";
                          $result = mysqli_query($conn, $sql);
                          while ($row = mysqli_fetch_assoc($result)) {
                              $category_id = $row['category_id'];
                              $category_name = $row['category_name'];
                              echo "<option value=\"$category_id\">$category_name</option>";
                          }
                          ?>
                      </select>
                  </div>
                </div>

                <!-- 🛑 START VARIANT LIST -->
                <h4 class="font-semibold text-lg text-gray-800">Variants</h4>

                <!-- Dynamic Variant Container -->
                <div id="variantContainer">
                    <!-- Default Variant Row -->
                    <div class="grid grid-cols-5 gap-4 py-2 pb-4 variantRow">
                        <!-- Variant Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Variant Name</label>
                            <input type="text" name="variant_name[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <!-- Stock Quantity -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stock</label>
                            <input type="number" min="1" name="stock_quantity[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price</label>
                            <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <!-- Discount Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Discount Price</label>
                            <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <!-- Delete Variant Button --> 

                        <div>                            
                            <label class="block text-sm font-medium text-gray-700">&nbsp;</label>
                            <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white rounded-lg">🗑 Delete</button>
                        </div>
                    </div>
                </div>

                <!-- Add Variant Button -->
                <div class="flex justify-end mt-3">
                    <button type="button" id="addVariant" style="background-color: #22c55e;" class="px-4 py-2 text-white rounded-lg">+ Add Variant</button>
                </div>
                <!-- 🛑 END VARIANT LIST -->

                <!-- Product Images Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload Product Images</label>
                    <input type="file" id="productImages" name="product_images[]" multiple required class="hidden" accept="image/*">
                    <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('productImages').click()">📸 Select Images</button>
                    <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>

                    <!-- Image Preview Section -->
                    <div id="imagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                            onclick="document.getElementById('addProductModal').classList.add('hidden')">Cancel</button>
                    <button type="submit" name="add_product" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // 🛠️ Utility Functions
      function addVariant(container) {
          const variantHTML = `
              <div class="grid grid-cols-5 gap-4 py-2 pb-4 variantRow">
                  <!-- Hidden Variant ID (for existing variants) -->
                  <input type="hidden" name="variant_id[]" value="">
                  <!-- Variant Name -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Variant Name</label>
                      <input type="text" name="variant_name[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                  </div>
                  <!-- Stock Quantity -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Stock</label>
                      <input type="number" min="1" name="stock_quantity[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                  </div>
                  <!-- Price -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Price</label>
                      <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                  </div>
                  <!-- Discount Price -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700">Discount Price</label>
                      <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                  </div>
                  <!-- Delete Variant Button -->
                  <div class="flex items-end">
                      <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white rounded-lg">
                            🗑 Delete
                      </button>
                  </div>
              </div>
          `;
          container.insertAdjacentHTML("beforeend", variantHTML);
      }

      function removeVariant(event) {
          if (event.target.classList.contains("removeVariant")) {
              event.target.closest(".variantRow").remove();
          }
      }

      // 🎯 Add Modal Variant Handling
      const addVariantContainer = document.getElementById("variantContainer");
      const addVariantBtn = document.getElementById("addVariant");

      if (addVariantBtn && addVariantContainer) {
          addVariantBtn.addEventListener("click", () => addVariant(addVariantContainer));
          addVariantContainer.addEventListener("click", removeVariant);
      }

      // 🎯 Update Modal Variant Handling
      const updateVariantContainers = document.querySelectorAll(".updateVariantContainer");

      updateVariantContainers.forEach(container => {
          const addVariantBtn = container.closest(".modal-content").querySelector(".addVariant");
          if (addVariantBtn) {
              addVariantBtn.addEventListener("click", () => addVariant(container));
              container.addEventListener("click", removeVariant);
          }
      });

      function handleImageUpload(inputId, previewId) {
        const imageInput = document.getElementById(inputId);
        const previewContainer = document.getElementById(previewId);
        let selectedFiles = [];

        if (imageInput && previewContainer) {
          imageInput.addEventListener("change", function (event) {
            const newFiles = Array.from(event.target.files);
            if (selectedFiles.length + newFiles.length > 5) {
              alert("You can only upload up to 5 images.");
              return;
            }
            selectedFiles.push(...newFiles);
            updateImagePreview();
          });

          function updateImagePreview() {
            previewContainer.innerHTML = "";
            selectedFiles.forEach((file, index) => {
              const reader = new FileReader();
              reader.onload = function (e) {
                const div = document.createElement("div");
                div.classList.add("relative");

                const img = document.createElement("img");
                img.src = e.target.result;
                img.classList.add("w-auto", "h-auto", "object-cover", "rounded-lg", "border");

                const removeBtn = document.createElement("button");
                removeBtn.innerHTML = "X";
                removeBtn.classList.add(
                  "absolute", "top-0", "right-0", "bg-red-600", "text-white",
                  "rounded-full", "text-xs", "w-8", "h-8", "flex", "items-center", "justify-center"
                );

                removeBtn.addEventListener("click", () => {
                  selectedFiles.splice(index, 1);
                  updateImagePreview();
                });

                div.appendChild(img);
                div.appendChild(removeBtn);
                previewContainer.appendChild(div);
              };
              reader.readAsDataURL(file);
            });

            const dataTransfer = new DataTransfer();
            selectedFiles.forEach((file) => dataTransfer.items.add(file));
            imageInput.files = dataTransfer.files;
          }
        }
      }

      // Handle both Add and Update Modals
      handleImageUpload("productImages", "imagePreview"); // For Add Modal
      handleImageUpload("newImageInput", "newImagePreview"); // For Update Modal

      // 🎯 Modal Handling
      document.querySelectorAll("[data-modal-target]").forEach(button => {
          button.addEventListener("click", function () {
              const modalId = this.getAttribute("data-modal-target");
              document.getElementById(modalId).classList.remove("hidden");
          });
      });

      window.closeModal = function (modalId) {
          document.getElementById(modalId).classList.add("hidden");
      };
  });
  </script>

  <script>
  document.addEventListener("DOMContentLoaded", function () {
      // Update Modal Variant Handling
      const updateVariantContainers = document.querySelectorAll(".updateVariantContainer");

      updateVariantContainers.forEach(container => {
          const addVariantBtn = container.closest(".modal-content").querySelector(".addVariant");

          // Function to add a new variant input set in Update Modal
          addVariantBtn.addEventListener("click", function () {
              const variantHTML = `
                  <div class="grid grid-cols-5 gap-4 py-2 pb-4 variantRow">
                      <!-- Hidden Variant ID (for existing variants) -->
                      <input type="hidden" name="variant_id[]" value="">

                      <!-- Variant Name -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Variant Name</label>
                          <input type="text" name="variant_name[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                      </div>

                      <!-- Stock Quantity -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Stock</label>
                          <input type="number" min="1" name="stock_quantity[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                      </div>

                      <!-- Price -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Price</label>
                          <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                      </div>

                      <!-- Discount Price -->
                      <div>
                          <label class="block text-sm font-medium text-gray-700">Discount Price</label>
                          <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                      </div>

                      <!-- Delete Variant Button -->
                      <div class="flex items-end">
                          <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white rounded-lg">
                            🗑 Delete
                          </button>
                      </div>
                  </div>
              `;

              // Append new variant input fields
              container.insertAdjacentHTML("beforeend", variantHTML);
          });

          // Event delegation to handle dynamically added "Delete" buttons in Update Modal
          container.addEventListener("click", function (event) {
              if (event.target.classList.contains("removeVariant")) {
                  event.target.closest(".variantRow").remove();
              }
          });
      });
  });
  </script>

  <?php $conn->close(); ?>

  <!-- JS Implementing Plugins -->

  <!-- JS PLUGINS -->
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="../node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>

