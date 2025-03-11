
<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">
              Account
            </h2>
            <p class="text-sm text-gray-600">
              Manage your account
            </p>
          </div>
          
        </div>
        <!-- End Header -->

        <!-- Table -->
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>

              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    User Type
                  </span>
                </div>
              </th>
              
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Username
                  </span>
                </div>
              </th>

              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Email
                  </span>
                </div>
              </th>

              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Phone
                  </span>
                </div>
              </th>

              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                    Date Created
                  </span>
                </div>
              </th>

              <th scope="col" class="px-6  text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 ">
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['role'] ?></span>
              </td>
              <td class="ps-6 py-3">
                <div class="flex items-center gap-x-3">
                  <div class="grow">
                    <span class="block text-sm font-semibold text-gray-800 "><?= htmlspecialchars($row['username']) ?></span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['email'] ?></span>
              </td>
              <td class="px-6 py-3">
                <span class="block text-sm font-semibold text-gray-800 "><?= $row['phone_number'] ?></span>
              </td>
              
              <td class="px-6 py-3">
                <span class="text-sm text-gray-500 "><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></span>
              </td>
              <td class="px-6 py-3 inline-flex gap-1 items-center">
                <button class="px-3 py-2text-dark rounded-xl" onclick="document.getElementById('viewAccountModal<?php echo $row['account_id']; ?>').classList.remove('hidden')">
  
                  <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                </button>
              </td>
            
            </tr>

            <!-- Users Account Modal -->
            <div id="viewAccountModal<?php echo $row['account_id']; ?>" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
              <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold mb-4"><?php echo htmlspecialchars($row['username']); ?> Account Details</h3>
                  <button type="button" class="" onclick="closeModal('viewAccountModal<?php echo $row['account_id']; ?>')">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                  </button>
                </div>
                <div class="grid grid-cols-2 gap-y-2">
                  <div>
                    <strong class="block text-sm font-medium text-gray-700">Firstname:</strong>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['first_name']); ?></span>
                  </div>
                  <div>
                    <strong class="block text-sm font-medium text-gray-700">Lastname:</strong>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['last_name']); ?></span>
                  </div>
                </div>
                <div>
                  <strong class="block text-sm font-medium text-gray-700">Email:</strong>
                  <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['email']); ?></span>
                </div>
                <div>
                  <strong class="block text-sm font-medium text-gray-700">Phone Number:</strong>
                  <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['phone_number']); ?></span>
                </div>
                <div>
                  <strong class="block text-sm font-medium text-gray-700">Address:</strong>
                  <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['address']); ?></span>
                </div>
                <div class="grid grid-cols-2 gap-y-2">
                  <div>
                    <strong class="block text-sm font-medium text-gray-700">City:</strong>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['city']); ?></span>
                  </div>
                  <div>
                    <strong class="block text-sm font-medium text-gray-700">Postal Code:</strong>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($row['postal_code']); ?></span>
                  </div>
                </div>
              </div>
            </div>

            <?php endwhile; ?>
          </tbody>
        </table>
        <!-- End Table -->

        <!-- Footer -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
          <div>
            <p class="text-sm text-gray-600 ">
              <span class="font-semibold text-gray-800 ">1</span> results
            </p>
          </div>

          <div>
            <div class="inline-flex gap-x-2">
              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 ">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m15 18-6-6 6-6" />
                </svg>
                Prev
              </button>

              <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 ">
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
