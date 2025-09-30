<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:items-center border-b border-gray-200 ">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xl font-semibold text-gray-800 ">
                Products
              </h2>
              <p class="text-sm text-gray-600">
                Manage your products
              </p>
            </div>
            <div class="inline-flex gap-x-2">
              <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:bg-orange-700" 
                href="#" data-modal-target="addProductModal">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14" />
                  <path d="M12 5v14" />
                </svg>
                Add Products
              </a>
            </div>
          </div>
        </div>
        <!-- End Header -->

        <!-- Table -->
        <table class="min-w-full divide-y divide-gray-200 ">
          <thead class="bg-gray-50 ">
            <tr>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Product Name
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Category
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Status
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Variants
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Prices
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Last Updated
                  </span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 ">
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="product-row bg-white">
              <td class="ps-6 py-3">
                <div class="flex items-center gap-x-3">
                  <div class="grow">
                    <span class="block text-sm font-semibold text-gray-800 "><?= htmlspecialchars($row['product_name']) ?></span>
                    <span class="block text-sm text-gray-500 "><?= $row['product_description'] ?></span>
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['category_name'] ?></span>
              </td>
             
              <td class="px-6 py-3">
                <?php if ($row['stock_status'] > 0): ?>
                  <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full ">
                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                    </svg>
                    In Stock
                  </span>
                <?php else: ?>
                  <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full ">
                    <svg class="size-2.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm0-3.5a.75.75 0 0 0 .75-.75V4.25a.75.75 0 0 0-1.5 0v7.5c0 .414.336.75.75.75z"/>
                    </svg>
                    Out of Stock
                  </span>
                <?php endif; ?>
              </td>

              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= !empty($row['variants']) ? htmlspecialchars($row['variants']) : 'No Variants' ?></span>
              </td>

              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 ">₱<?= !empty($row['prices']) ? htmlspecialchars($row['prices']) : 'No Prices Available' ?></span>
              </td>

              <td class="px-6 py-3">
                <span class="text-sm text-gray-500"><?= date("F j, Y, g:i a", strtotime($row['last_updated'])) ?></span>
              </td>
              <td class="px-6 py-3 inline-flex gap-1 items-center">
                <button type="button" style="background-color: #3b82f6;" class="px-3 py-2 text-white rounded-xl" onclick="openEditModal(<?php echo $row['product_id']; ?>)">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                </button>
              </td>
            </tr>
            <?php endwhile; ?>

          </tbody>
        </table>
        <!-- End Table -->

        <!-- Update Product Modal -->
        <div id="editProductModal" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto p-10" style="margin: 0;">
          <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-3xl flex flex-col modal-content">
            <div class="p-6 lg:max-h-[40vh] overflow-y-auto" id="modalContent">
                  
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
          <div>
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-gray-800">
                <?php echo $totalItems; ?>
              </span> results
            </p>
          </div>

          <div>
            <div class="inline-flex gap-x-2">
              <?php
              // Previous button
              if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                  </svg>
                  Prev
                </a>
              <?php else: ?>
                <span class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed">
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                  </svg>
                  Prev
                </span>
              <?php endif; ?>

              <!-- Page numbers -->
              <?php 
              $start = max(1, $page - 2);
              $end = min($totalPages, $page + 2);
              
              // Show first page if not in range
              if ($start > 1): ?>
                <a href="?page=1" class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
                  1
                </a>
                <?php if ($start > 2): ?>
                  <span class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-gray-800">...</span>
                <?php endif;
              endif;
              
              for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-800'; ?> py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
                  <?php echo $i; ?>
                </a>
              <?php endfor; 
              
              // Show last page if not in range
              if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                  <span class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-gray-800">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $totalPages; ?>" class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
                  <?php echo $totalPages; ?>
                </a>
              <?php endif; ?>

              <!-- Next button -->
              <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
                  Next
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                  </svg>
                </a>
              <?php else: ?>
                <span class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed">
                  Next
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                  </svg>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <!-- End Footer -->
         
      </div>
    </div>
  </div>
</div>

<style>
  .product-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .product-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>


<script>
function openEditModal(productId) {
  fetch(`./functions/fetch_products.php?product_id=${productId}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('modalContent').innerHTML = data;
      document.getElementById('editProductModal').classList.remove('hidden');

      // Reinitialize variant handling
      reinitializeVariantHandling();
    })
    .catch(error => console.error('Error:', error));
}

function reinitializeVariantHandling() {
  const updateVariantContainers = document.querySelectorAll(".updateVariantContainer");

  updateVariantContainers.forEach(container => {
    const addVariantBtn = container.closest(".modal-content").querySelector(".addVariant");
    if (addVariantBtn) {
      addVariantBtn.addEventListener("click", () => addVariant(container));
      container.addEventListener("click", removeVariant);
    }
  });
}

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
</script>

