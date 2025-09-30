
<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:items-center border-b border-gray-200">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">
                Category
              </h2>
              <p class="text-sm text-gray-600">
                Manage your category
              </p>
            </div>
            <div class="inline-flex gap-x-2">
              <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:bg-orange-700" 
                href="#" data-modal-target="addCategoryModal">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14" />
                  <path d="M12 5v14" />
                </svg>
                Add Category
              </a>
            </div>
          </div>
        </div>
        <!-- End Header -->

        <!-- Table -->
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Category ID</span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Category Name</span>
                </div>
              </th>
              
              <th scope="col" class="px-6 py-3 text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 ">
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="category-row bg-white">
              <td class="ps-6 py-3">
                <div class="flex items-center gap-x-3">
                  <div class="grow">
                    <span class="block text-sm font-semibold text-gray-800 "><?= htmlspecialchars($row['category_id']) ?></span>
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['category_name'] ?></span>
              </td>

              <td class="px-6 py-3 inline-flex gap-1 items-center">
                <button type="button" style="background-color: #3b82f6;" class="px-3 py-2 text-white rounded-xl" onclick="document.getElementById('editCategoryModal<?php echo $row['category_id']; ?>').classList.remove('hidden')">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                </button>
                <button type="button" style="background-color: #ef4444;" class="px-3 py-2 text-white rounded-xl" data-modal-target="deleteCategoryModal<?php echo $row['category_id']; ?>">
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                </button>
              </td>
              
              
            
            </tr>

            <!-- Edit Category Modal -->
            <div id="editCategoryModal<?php echo $row['category_id']; ?>" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden">
              <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <h3 class="text-lg font-semibold mb-4">Edit Category</h3>
                <form action="./functions/update.php" method="POST">
                  <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                  
                  <div class="mb-3">
                    <label class="block text-sm font-medium">Category Name</label>
                    <input type="text" name="category_name" required class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['category_name']); ?>">
                  </div>
                  
                  <div class="flex justify-end space-x-3 mt-4">
                  
                    <button type="submit" name="update_category" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Update Category</button>
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('editCategoryModal<?php echo $row['category_id']; ?>')">Cancel</button>
                                        
                  </div>
                </form>
              </div>
            </div>

            <!-- Delete Category Modal -->
            <div id="deleteCategoryModal<?php echo $row['category_id']; ?>" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden">
              <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <h3 class="text-lg font-semibold mb-4">Delete Category</h3>
                <form action="./functions/delete.php" method="POST">
                  <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                  
                  <div class="mb-3">
                    <label class="block text-sm font-medium">Category Name</label>
                    <input type="text" name="category_name" required class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($row['category_name']); ?>">
                  </div>
                  
                  <div class="flex justify-end space-x-3 mt-4">

                    <button type="submit" name="delete_category" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Delete Category</button>
                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('deleteCategoryModal<?php echo $row['category_id']; ?>')">Cancel</button>
                     </div>
                </form>
              </div>
            </div>

            <?php endwhile; ?>
          </tbody>
        </table>
        <!-- End Table -->

        
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
  .category-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .category-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>
