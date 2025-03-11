<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden  ">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 ">
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
                    Size
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
                    Stock
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Price
                  </span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Discounted Price
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
                    Last Updated
                  </span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 ">
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="ps-6 py-3">
                <div class="flex items-center gap-x-3">
                  <div class="grow">
                    <span class="block text-sm font-semibold text-gray-800 "><?= htmlspecialchars($row['product_name']) ?></span>
                    <span class="block text-sm text-gray-500 dark:text-neutral-500"><?= htmlspecialchars($row['product_description']) ?></span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['product_size'] ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['category_name'] ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['product_stock'] ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= number_format($row['product_price'], 2) ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= number_format($row['discount_price'], 2) ?></span>
              </td>
              <td class="px-6 py-3">
                <?php if ($row['product_stock'] > 0): ?>
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
                <span class="text-sm text-gray-500 dark:text-neutral-500"><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></span>
              </td>
              <td class="px-6 py-3 inline-flex gap-1 items-center">
                <button class="px-3 py-2 bg-blue-500 text-white rounded-xl" onclick="document.getElementById('editProductModal<?php echo $row['product_id']; ?>').classList.remove('hidden')">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                </button>
                <button class="px-3 py-2 bg-red-500 text-white rounded-xl" data-modal-target="deleteProductModal<?php echo $row['product_id']; ?>">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>                </a>
                </button>
              </td>
            </tr>
            
            <!-- Edit Product Modal -->
            <div id="editProductModal<?php echo $row['product_id']; ?>" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
              <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all scale-95 hover:scale-100">
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
                      <input type="text" name="product_description" placeholder="Description" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" value="<?php echo htmlspecialchars($row['product_description']); ?>">
                    </div>

                    <!-- Product Size -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Product Size</label>
                      <input type="text" name="product_size" placeholder="Size" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" value="<?php echo htmlspecialchars($row['product_size']); ?>">
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <!-- Category -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Category</label>
                      <select name="product_category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                          <option value="" disabled>Select a category</option>
                          
                          <?php
                          // Fetch categories
                          $cat_query = "SELECT * FROM product_categories";
                          $cat_result = $conn->query($cat_query);

                          while ($cat_row = $cat_result->fetch_assoc()) {
                              $selected = ($cat_row['category_id'] == $row['product_category_id']) ? 'selected' : '';
                              echo '<option value="' . htmlspecialchars($cat_row['category_id']) . '" ' . $selected . '>' . htmlspecialchars($cat_row['category_name']) . '</option>';
                          }
                          ?>

                      </select>
                    </div>

                    <!-- Stock (If Editable) -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Stock</label>
                      <input type="number" name="product_stock" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['product_stock']); ?>" required>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <!-- Price -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Price</label>
                      <input type="text" name="product_price" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['product_price']); ?>" required>
                    </div>

                    <!-- Discounted Price -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Discounted Price</label>
                      <input type="text" name="discount_price" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['discount_price']); ?>">
                    </div>
                  </div>

                  <!-- Product Images -->
                  <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Current Product Images</label>
                    <div class="grid grid-cols-5 gap-2 mt-2">
                        <?php
                        $product_id = $row['product_id'];
                        $img_query = "SELECT * FROM product_images WHERE product_id = ?";
                        $img_stmt = $conn->prepare($img_query);
                        $img_stmt->bind_param("i", $product_id);
                        $img_stmt->execute();
                        $img_result = $img_stmt->get_result();

                        while ($img_row = $img_result->fetch_assoc()) {
                          $image_path = $img_row['image_path'];
                          $image_id = $img_row['image_id'];
                          $product_id = $row['product_id']; // Ensure product ID is set
                      
                          echo '<div class="relative group">';
                          echo '<img src="http://localhost/sjfbi-js/admin/uploads/products/' . htmlspecialchars($image_path) . '" class="w-20 h-20 object-cover rounded-lg shadow">';
                          echo '<button type="button" onclick="deleteImage(' . $image_id . ', ' . $product_id . ')" class="absolute top-0 right-0 bg-white p-1 rounded-full shadow-md">';
                          echo '<span class="text-red-500 cursor-pointer">🗑</span>';
                          echo '</button>';
                          echo '</div>';
                        }
                        $img_stmt->close();
                        ?>
                    </div>
                  </div>

                  <!-- Upload New Images -->
                  <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Update New Images</label>
                    <input type="file" id="newImageInput" name="product_images[]" multiple class="hidden" accept="image/*" onchange="previewImages(event)">
                    <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('newImageInput').click()">📸 Select Images</button>
                    <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>
                  </div>

                  <!-- Preview Container -->
                  <div id="newImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>

                  <!-- Action Buttons -->
                  <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="closeModal('editProductModal<?php echo $row['product_id']; ?>')">Cancel</button>
                    <button type="submit" name="update_product" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Update Product</button>
                  </div>

                </form>
              </div>
            </div>

            <!-- Delete Product Modal -->
            <div id="deleteProductModal<?php echo $row['product_id']; ?>" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
              <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <h3 class="text-lg font-semibold mb-4">Delete Product</h3>
                <form action="./functions/delete.php" method="POST">
                  <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                  
                  <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>?</p>

                  <div class="flex justify-end mt-4">
                    <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="closeModal('deleteProductModal<?php echo $row['product_id']; ?>')">Cancel</button>
                    <button type="submit" name="delete_product" class="px-4 py-2 bg-red-600 text-white rounded-lg">Delete</button>
                  </div>
                </form>
              </div>
            </div>

            <?php endwhile; ?>
          </tbody>
        </table>
        <!-- End Table -->

        <!-- Footer -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 ">
          <div>
            <p class="text-sm text-gray-600 dark:text-neutral-400">
              <span class="font-semibold text-gray-800 ">1</span> results
            </p>
          </div>

          <div>
            <div class="inline-flex gap-x-2">
              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 dark:bg-transparent  dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m15 18-6-6 6-6" />
                </svg>
                Prev
              </button>

              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 dark:bg-transparent  dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                Next
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        <!-- End Footer -->
      </div>
    </div>
  </div>
</div>

<script>
function deleteImage(imageId, productId) {
    if (confirm("Are you sure you want to delete this image?")) {
        fetch('./functions/delete_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                image_id: imageId,
                product_id: productId
            })
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Debugging response
            if (data.trim() === "success") {
                location.reload();
            } else {
                alert("Failed to delete image: " + data);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>


