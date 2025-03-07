
<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-800 dark:border-neutral-700">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
          <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
              Products
            </h2>
            <p class="text-sm text-gray-600 dark:text-neutral-400">
              Manage your products
            </p>
          </div>
          <div class="inline-flex gap-x-2">
            <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700" 
              href="#" data-modal-target="addCategoryModal">
              <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14" />
                <path d="M12 5v14" />
              </svg>
              Add Category
            </a>
          </div>
        </div>
        <!-- End Header -->

        <!-- Table -->
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
          <thead class="bg-gray-50 dark:bg-neutral-800">
            <tr>
              <th scope="col" class="px-6 py-3 text-start">Category ID</th>
              <th scope="col" class="ps-6 py-3 text-start">Category Name</th>
              <th scope="col" class="px-6 py-3 text-start">Category Description</th>
              <th scope="col" class="px-6 py-3 text-start">Date Added</th>
              <th scope="col" class="px-6 py-3 text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="ps-6 py-3">
                <div class="flex items-center gap-x-3">
                  <div class="grow">
                    <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200"><?= htmlspecialchars($row['category_id']) ?></span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200"><?= $row['category_name'] ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200"><?= $row['category_description'] ?></span>
              </td>
              
              <td class="px-6 py-3">
                <span class="text-sm text-gray-500 dark:text-neutral-500"><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></span>
              </td>
              <td class="px-6 py-3">
                <a class="text-sm text-blue-600 hover:underline font-medium dark:text-blue-500" href="#" data-modal-target="editCategoryModal?id=<?= $row['category_id'] ?>">Edit</a>
                <a class="text-sm text-red-600 hover:underline font-medium dark:text-red-500" href="#" data-modal-target="deleteCategoryModal.php?id=<?= $row['category_id'] ?>">Delete</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <!-- End Table -->

        <!-- Footer -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
          <div>
            <p class="text-sm text-gray-600 dark:text-neutral-400">
              <span class="font-semibold text-gray-800 dark:text-neutral-200">1</span> results
            </p>
          </div>

          <div>
            <div class="inline-flex gap-x-2">
              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m15 18-6-6 6-6" />
                </svg>
                Prev
              </button>

              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
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
