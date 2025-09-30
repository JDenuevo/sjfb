<div class="flex flex-col">
    <div class="-m-1.5 overflow-x-auto">
        <div class="p-1.5 min-w-full inline-block align-middle">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 gap-3 flex justify-between items-center border-b border-gray-200">
  
                  <!-- Left section -->
                  <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                      Accounts
                    </h2>
                    <p class="text-sm text-gray-600">
                      Manage your accounts
                    </p>
                  </div>

                  <!-- Right section -->
                  <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:bg-orange-700" 
                    href="#" data-modal-target="addAccountModal">
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                      <path d="M5 12h14" />
                      <path d="M12 5v14" />
                    </svg>
                    Add Account
                  </a>

                </div>

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
                            <th scope="col" class="px-6 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 ">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="accounts-row bg-white">
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
                                <button style="background-color: #3b82f6;" class="px-3 py-2 text-white rounded-xl" onclick="document.getElementById('updateAccountModal<?php echo $row['account_id']; ?>').classList.remove('hidden')">
                                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                </button>
                                <button style="background-color: #ef4444;" class="px-3 py-2 text-white rounded-xl" onclick="document.getElementById('deleteAccountModal<?php echo $row['account_id']; ?>').classList.remove('hidden')">
                                  <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                </button>
                              </td>
                            </tr>

                            <div id="updateAccountModal<?php echo $row['account_id']; ?>" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden">
                              <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all scale-95 hover:scale-100">
                                <h3 class="text-xl font-semibold mb-4 text-gray-800">Update Account</h3>
                                
                                <form action="./functions/update.php" method="POST">
                                  <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($row['account_id']); ?>">

                                  <div class="grid grid-cols-2 gap-x-2">
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">Username</label>
                                      <input type="text" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                    </div>
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">Role</label>
                                      <select name="role" required class="w-full px-3 py-2 border rounded-lg">
                                        <option value="admin" <?php echo ($row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="customer" <?php echo ($row['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                        <option value="rider" <?php echo ($row['role'] == 'rider') ? 'selected' : ''; ?>>Rider</option>
                                        <option value="guest" <?php echo ($row['role'] == 'guest') ? 'selected' : ''; ?>>Guest</option>
                                      </select>
                                    </div>
                                  </div>

                                  <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                      <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                      <input 
                                        type="password" 
                                        name="password" 
                                        id="password" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                        placeholder="Leave blank to keep current password"
                                      >
                                    </div>

                                    <div>
                                      <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                      <input 
                                        type="password" 
                                        name="confirm_password" 
                                        id="confirm_password" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      >
                                    </div>
                                  </div>

                                  <div class="grid grid-cols-2 gap-x-2">
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">First Name</label>
                                      <input type="text" name="first_name" value="<?php echo htmlspecialchars($row['first_name']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                    </div>
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                      <input type="text" name="last_name" value="<?php echo htmlspecialchars($row['last_name']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                    </div>
                                  </div>

                                  <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                  </div>

                                  <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="number" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                  </div>

                                  <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                    <textarea name="address" class="w-full px-3 py-2 border rounded-lg" required><?php echo htmlspecialchars($row['address']); ?></textarea>
                                  </div>

                                  <div class="grid grid-cols-2 gap-x-2">
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">City</label>
                                      <input type="text" name="city" value="<?php echo htmlspecialchars($row['city']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                    </div>
                                    <div class="mb-3">
                                      <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                      <input type="number" name="postal_code" value="<?php echo htmlspecialchars($row['postal_code']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                                    </div>
                                  </div>

                                  <!-- Action Buttons -->
                                  <div class="flex justify-end space-x-3 mt-4">
                                    <button type="submit" name="update_account" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Update Account</button>
                                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('updateAccountModal<?php echo $row['account_id']; ?>')">Cancel</button>
                                  </div>
                                </form>
                              </div>
                            </div>

                            <div id="deleteAccountModal<?php echo $row['account_id']; ?>" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden">
                                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                                    <form action="./functions/delete.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                                        <input type="hidden" name="account_id" value="<?php echo $row['account_id']; ?>">
                                        
                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($row['username']); ?></strong>?</p>

                                        <div class="flex justify-end mt-4">
                                            <button type="submit" name="delete_account" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Delete Account</button>
                                            <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('deleteAccountModal<?php echo $row['account_id']; ?>')">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>
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
  .accounts-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .accounts-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>
