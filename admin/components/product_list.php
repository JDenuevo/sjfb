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
            <tr>
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
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['variants'] ?></span>
              </td>

              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['prices'] ?></span>
              </td>

              <td class="px-6 py-3">
                <span class="text-sm text-gray-500 dark:text-neutral-500"><?= date("F j, Y, g:i a", strtotime($row['last_updated'])) ?></span>
              </td>
              <td class="px-6 py-3 inline-flex gap-1 items-center">
                <button class="px-3 py-2 bg-blue-500 text-dark rounded-xl" onclick="document.getElementById('editProductModal<?php echo $row['product_id']; ?>').classList.remove('hidden')">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                </button>
                <button class="px-3 py-2 bg-red-500 text-dark rounded-xl" data-modal-target="deleteProductModal<?php echo $row['product_id']; ?>">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>                </a>
                </button>
              </td>
            </tr>

            <!-- Update Product Modal -->
            <div id="editProductModal<?php echo $row['product_id']; ?>" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto">
                <div class="bg-white p-6 rounded-2xl shadow-2xl w-11/12 sm:w-4/5 md:w-3/4 lg:max-w-3xl xl:max-w-3xl max-h-[50vh] flex flex-col modal-content">
                    <div class="overflow-y-auto max-h-[40vh]">
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
                                            <img src="http://localhost/sjfbi-js/admin/uploads/products/' . htmlspecialchars($image_path) . '" class="w-auto h-auto object-cover rounded-lg shadow">
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
                                <input type="file" id="newImageInput" name="product_images[]" multiple class="hidden" accept="image/*" onchange="previewImages(event)">
                                <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('newImageInput').click()">📸 Select Images</button>
                                <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>
                            </div>

                            <!-- Preview Container -->
                            <div id="newImagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 mt-4">
                                <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                                        onclick="closeModal('editProductModal<?php echo $row['product_id']; ?>')">Cancel</button>
                                <button type="submit" name="update_product" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Update Product</button>
                            </div>
                        </form>
                    </div>
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
                    <button type="submit" name="delete_product" class="px-4 py-2 bg-red-600 text-dark rounded-lg">Delete</button>
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


