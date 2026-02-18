
<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:items-center border-b border-gray-200">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">
                Rider
              </h2>
              <p class="text-sm text-gray-600">
                Manage delivery rider
              </p>
            </div>
            <div class="inline-flex gap-x-2">
                <button type="button" onclick="openAddRiderModal()" 
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700">
                    Add New Rider
                </button>
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
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Rider</span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Vehicle</span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Status</span>
                </div>
              </th>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</span>
                </div>
              </th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($riders)): ?>
                  <tr>
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                          No riders found. 
                      </td>
                  </tr>
              <?php else: ?>
                  <?php foreach ($riders as $rider): ?>
                      <tr class="rider-row bg-white">
                          <td class="px-6 py-4 whitespace-nowrap">
                              <div class="flex items-center">
                                  <div class="ml-4">
                                      <div class="text-sm font-medium text-gray-900">
                                          <?php echo htmlspecialchars($rider['first_name'] . ' ' . $rider['last_name']); ?>
                                      </div>
                                      <div class="text-sm text-gray-500">
                                          <?php echo htmlspecialchars($rider['email']); ?>
                                      </div>
                                  </div>
                              </div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                              <div class="font-medium"><?php echo htmlspecialchars(ucfirst($rider['vehicle_type'])); ?></div>
                              <div class="text-gray-500"><?php echo htmlspecialchars($rider['vehicle_plate_number']); ?></div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                              $isAvailable = $rider['is_available'];
                              $statusClass = $isAvailable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                              $statusText = $isAvailable ? 'Available' : 'Busy';
                              $statusIcon = $isAvailable 
                                ? '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M5 13l4 4L19 7"/>
                                  </svg>'
                                : '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M6 18L18 6M6 6l12 12"/>
                                  </svg>';
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                              <?php echo $statusIcon; ?>
                              <?php echo $statusText; ?>
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                              <div class="flex space-x-2">
                                  <button style="background-color: #3b82f6;" class="px-3 py-2 text-white rounded-xl" onclick="openEditRiderModal(<?php echo $rider['rider_id']; ?>)">
                                      <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                  </button>
                                  <button style="background-color: #ef4444;" class="px-3 py-2 text-white rounded-xl" onclick="confirmDeleteRider(<?php echo $rider['rider_id']; ?>)">
                                      <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                  </button>
                              </div>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              <?php endif; ?>
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
  .rider-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .rider-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>

<!-- Add Rider Modal - FIXED VERSION -->
<div id="addRiderModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10 z-100">
    <div class="bg-white w-full max-w-lg p-6 rounded-2xl shadow-2xl flex flex-col relative">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Rider</h3>
        <form action="./functions/rider_process.php" method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Account</label>
                <select name="account_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select an account...</option>
                    <?php foreach ($availableAccounts as $account): ?>
                        <option value="<?php echo $account['account_id']; ?>">
                            <?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?> 
                            (<?php echo htmlspecialchars($account['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                <select name="vehicle_type" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select vehicle type...</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="bicycle">Bicycle</option>
                    <option value="car">Car</option>
                    <option value="truck">Truck</option>
                </select>
            </div>
            
            <!-- ADD THIS MISSING FIELD -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Plate Number</label>
                <input type="text" name="vehicle_plate_number" class="w-full px-3 py-2 border rounded-lg" placeholder="ABC-123 (Optional)">
                <p class="text-xs text-gray-500 mt-1">Optional field</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                <input type="text" name="license_number" required class="w-full px-3 py-2 border rounded-lg" placeholder="License number">
            </div>
            
            <div class="flex justify-end space-x-3 mt-4">
                <button type="submit" name="add_rider" class="py-2 px-4 bg-orange-600 text-white rounded-lg">
                    Add Rider
                </button>
                <button type="button" class="py-2 px-4 bg-gray-200 text-gray-800 rounded-lg" onclick="closeModal('addRiderModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Rider Modal -->
<div id="editRiderModal" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto">
    <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Edit Rider</h3>
        <form action="./functions/rider_process.php" method="POST" id="editRiderForm">
            <input type="hidden" name="rider_id" id="edit_rider_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                <select name="vehicle_type" id="edit_vehicle_type" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select vehicle type...</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="bicycle">Bicycle</option>
                    <option value="car">Car</option>
                    <option value="truck">Truck</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Plate Number</label>
                <input type="text" name="vehicle_plate_number" id="edit_vehicle_plate" class="w-full px-3 py-2 border rounded-lg" placeholder="ABC-123">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                <input type="text" name="license_number" id="edit_license_number" required class="w-full px-3 py-2 border rounded-lg" placeholder="License number">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Availability Status</label>
                <select name="is_available" id="edit_is_available" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="1">Available</option>
                    <option value="0">Busy</option>
                </select>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-4">
                <button type="submit" name="edit_rider" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700">
                    Update Rider
                </button>
                <button type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200" onclick="closeModal('editRiderModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddRiderModal() {
        document.getElementById('addRiderModal').classList.remove('hidden');
    }
    
    function openEditRiderModal(riderId) {
        // Fetch rider data via AJAX
        fetch(`./functions/fetch_riders.php?rider_id=${riderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate edit form
                    document.getElementById('edit_rider_id').value = data.rider.rider_id;
                    document.getElementById('edit_vehicle_type').value = data.rider.vehicle_type;
                    document.getElementById('edit_vehicle_plate').value = data.rider.vehicle_plate_number || '';
                    document.getElementById('edit_license_number').value = data.rider.license_number;
                    document.getElementById('edit_is_available').value = data.rider.is_available ? '1' : '0';
                    document.getElementById('editRiderModal').classList.remove('hidden');
                } else {
                    alert('Error loading rider data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading rider data');
            });
    }
    
    function confirmDeleteRider(riderId) {
        if (confirm('Are you sure you want to remove this rider? They will no longer be able to accept deliveries.')) {
            window.location.href = `./functions/rider_process.php?delete_rider=${riderId}`;
        }
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['addRiderModal', 'editRiderModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>