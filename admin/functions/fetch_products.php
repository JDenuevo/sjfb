<?php
session_start();
include '../../conn.php';

if (!isset($_SESSION["loggedinasadmin"]) || $_SESSION["loggedinasadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$product_id = $_GET['product_id'];

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
WHERE p.product_id = ?
GROUP BY p.product_id, p.product_name, p.product_description, c.category_name
ORDER BY last_updated DESC;";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>

<h3 class="text-xl font-semibold mb-4 text-gray-800">Update Product</h3>
                  
<form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <!-- Hidden Product ID -->
    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">

    <!-- Product Name -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Product Name</label>
        <input type="text" name="product_name" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['product_name']); ?>" required>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <!-- Product Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Product Description</label>
            <input type="text" name="product_description" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['product_description']); ?>" required>
        </div>

        <!-- Product Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Category</label>
            <select name="product_category" required class="w-full px-3 py-2 border rounded-lg">
                <option value="" disabled>Select a category</option>
                <?php
                $sql = "SELECT * FROM product_categories";
                $result = mysqli_query($conn, $sql);
                while ($category = mysqli_fetch_assoc($result)) {
                    $selected = ($category['category_id'] == $row['product_category']) ? "selected" : "";
                    echo "<option value='{$category['category_id']}' $selected>{$category['category_name']}</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <!-- 🛑 START VARIANT LIST -->
    <h4 class="font-semibold text-lg text-gray-800">Variants</h4>

    <!-- Dynamic Variant Container -->
    <div class="updateVariantContainer">
        <?php
        $variant_query = "SELECT * FROM product_variants WHERE product_id = ?";
        $variant_stmt = $conn->prepare($variant_query);
        $variant_stmt->bind_param("i", $row['product_id']);
        $variant_stmt->execute();
        $variant_result = $variant_stmt->get_result();

        while ($variant = $variant_result->fetch_assoc()) {
            echo '
            <div class="grid grid-cols-5 gap-4 py-2 pb-4 variantRow">
                <!-- Hidden Variant ID -->
                <input type="hidden" name="variant_id[]" value="' . $variant['variant_id'] . '">

                <!-- Variant Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Variant Name</label>
                    <input type="text" name="variant_name[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="' . htmlspecialchars($variant['variant_name']) . '" required>
                </div>

                <!-- Stock Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock</label>
                    <input type="number" min="1" name="stock_quantity[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="' . htmlspecialchars($variant['stock_quantity']) . '" required>
                </div>

                <!-- Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" min="0" step="0.01" name="variant_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="' . htmlspecialchars($variant['variant_price']) . '" required>
                </div>

                <!-- Discount Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Discount Price</label>
                    <input type="number" min="0" step="0.01" name="discount_price[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="' . htmlspecialchars($variant['discount_price']) . '">
                </div>

                <!-- Delete Variant Button -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">&nbsp;</label>
                      <button type="button" style="background-color: #ef4444;" class="removeVariant w-full px-4 py-2 text-white rounded-lg">
                        🗑 Delete
                    </button>
                </div>
            </div>';
        }
        $variant_stmt->close();
        ?>
    </div>

    <!-- Add Variant Button -->
    <div class="flex justify-end mt-3">
        <button type="button" style="background-color: #22c55e;" class="addVariant px-4 py-2 text-white rounded-lg">+ Add Variant</button>
    </div>
    <!-- 🛑 END VARIANT LIST -->

    <!-- Product Images -->
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700">Current Product Images</label>
        <div class="grid grid-cols-5 gap-2 mt-2">
            <?php
            $image_query = "SELECT * FROM product_images WHERE product_id = ?";
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bind_param("i", $row['product_id']);
            $image_stmt->execute();
            $image_result = $image_stmt->get_result();

            while ($image = $image_result->fetch_assoc()) {
                $image_path = $image['image_path'];
                $image_id = $image['image_id'];
                echo '
                <div class="relative group">
                    <img src="http://localhost/sjfbi-js/supadmin/uploads/products/' . htmlspecialchars($image_path) . '" class="w-auto h-auto object-cover rounded-lg shadow">
                    <button type="button" onclick="deleteImage(' . $image_id . ', ' . $row['product_id'] . ')" class="absolute top-0 right-0 bg-white p-1 rounded-full shadow-md">
                        <span class="text-red-500 cursor-pointer">🗑</span>
                    </button>
                </div>';
            }
            $image_stmt->close();
            ?>
        </div>
    </div>

    <!-- Upload New Images -->
    <div class="mt-4">
      <label class="block text-sm font-medium text-gray-700">Update New Images</label>
      <input type="file" id="newImageInput-<?php echo $row['product_id']; ?>" name="product_images[]" multiple class="hidden" accept="image/*">
      <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('newImageInput-<?php echo $row['product_id']; ?>').click()">📸 Select Images</button>
      <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>
    </div>

    <!-- Preview Container -->
    <div id="newImagePreview-<?php echo $row['product_id']; ?>" class="grid grid-cols-5 gap-2 mt-3"></div>
    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3 mt-4">
      <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition" onclick="closeModal('editProductModal')">Cancel</button>
      <button type="submit" name="update_product" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Update Product</button>
    </div>
  </form>
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