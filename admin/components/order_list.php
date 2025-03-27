<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">Orders</h2>
            <p class="text-sm text-gray-600">Manage your orders</p>
          </div>
        </div>
        <!-- End Header -->

        <!-- Table -->
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="ps-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Order ID</span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Ordered by</span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">User Type</span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Date Ordered</span>
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-start">
                <div class="flex items-center gap-x-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Order Status</span>
                </div>
              </th>
              <th scope="col" class="px-6 text-end"></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($result) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <!-- Order ID -->
                  <td class="size-px whitespace-nowrap">
                    <div class="ps-6">
                      <div class="flex items-center gap-x-3">
                        <div class="grow">
                          <span class="block text-sm font-semibold text-gray-800"><?php echo $row['order_id']; ?></span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Ordered By -->
                  <td class="size-px whitespace-nowrap">
                    <div class="ps-6 lg:ps-3 xl:ps-0 pe-6">
                      <span class="block text-sm font-semibold text-gray-800">
                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                      </span>
                    </div>
                  </td>

                  <!-- User Type -->
                  <td class="h-px w-72 whitespace-nowrap">
                    <div class="px-6">
                      <span class="block text-sm font-semibold text-gray-800">
                        <?php echo ucfirst($row['user_type']); ?>
                      </span>
                    </div>
                  </td>

                  <!-- Date Ordered -->
                  <td class="h-px w-72 whitespace-nowrap">
                    <div class="px-6">
                      <span class="block text-sm font-semibold text-gray-800">
                        <?php echo date('F j, Y @ h:i A', strtotime($row['order_date'])); ?>
                      </span>
                    </div>
                  </td>

                  <!-- Order Status -->
                  <td class="size-px whitespace-nowrap">
                    <div class="px-6">
                      <span class="py-1 px-1.5 inline-flex items-center gap-x-1 text-xs font-medium 
                        <?php echo ($row['order_status'] === 'Pending') ? 'bg-teal-100 text-teal-800' : 'bg-gray-100 text-gray-800'; ?> rounded-full">
                        <?php echo htmlspecialchars($row['order_status']); ?>
                      </span>
                    </div>
                  </td>

                  <!-- View Button -->
                  <td class="size-px whitespace-nowrap">
                    <button class="px-3 py-2 text-dark rounded-xl" data-modal-target="viewOrderModal<?php echo $row['order_id']; ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                      </svg>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-gray-500">No orders found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <!-- End Table -->
      </div>
    </div>
  </div>
</div>

<!-- View Order Modals -->
<?php if (mysqli_num_rows($result) > 0): ?>
  <?php mysqli_data_seek($result, 0); // Reset the result pointer ?>
  <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div id="viewOrderModal<?php echo $row['order_id']; ?>" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
      <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-4xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Order Details</h3>
          <button class="text-gray-500 hover:text-gray-700" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6L6 18" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Order Details -->
        <div class="space-y-4">
          <!-- Customer Details -->
          <div>
            <h4 class="font-semibold text-gray-800">Customer Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
              <div>
                <p class="text-sm text-gray-600">Name: <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                <p class="text-sm text-gray-600">Email: <?php echo htmlspecialchars($row['email']); ?></p>
                <p class="text-sm text-gray-600">Phone: <?php echo htmlspecialchars($row['phone_number']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Address: <?php echo htmlspecialchars($row['address']); ?></p>
                <p class="text-sm text-gray-600">City: <?php echo htmlspecialchars($row['city']); ?></p>
                <p class="text-sm text-gray-600">Postal Code: <?php echo htmlspecialchars($row['postal_code']); ?></p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div>
            <h4 class="font-semibold text-gray-800">Order Items</h4>
            <div class="mt-2">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Variant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="orderItems<?php echo $row['order_id']; ?>">
                  <!-- Order items will be dynamically loaded here -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Payment Method -->
          <div>
            <h4 class="font-semibold text-gray-800">Payment Method</h4>
            <p class="text-sm text-gray-600 mt-2"><?php echo ucfirst($row['payment_method']); ?></p>
          </div>

          <!-- Total Price -->
          <div>
            <h4 class="font-semibold text-gray-800">Total Price</h4>
            <p class="text-sm text-gray-600 mt-2">₱<?php echo number_format($row['total_price'], 2); ?></p>
          </div>
        </div>

        <!-- Close Button -->
        <div class="mt-6 flex justify-end">
          <button type="button" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">Close</button>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>

<script>

  // Function to fetch and display order items
function fetchOrderItems(orderId) {
  fetch(`./functions/fetch_orders.php?order_id=${orderId}`)
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById(`orderItems${orderId}`);
      tbody.innerHTML = ''; // Clear existing rows

      data.forEach(item => {
        const price = parseFloat(item.price); // Convert to number
        const total = item.quantity * price; // Calculate total
        tbody.innerHTML += `
          <tr>
            <td class="px-6 py-4 text-sm text-gray-800">${item.product_name}</td>
            <td class="px-6 py-4 text-sm text-gray-800">${item.variant_name}</td>
            <td class="px-6 py-4 text-sm text-gray-800">${item.quantity}</td>
            <td class="px-6 py-4 text-sm text-gray-800">₱${price.toFixed(2)}</td>
            <td class="px-6 py-4 text-sm text-gray-800">₱${total.toFixed(2)}</td>
          </tr>
        `;
      });
    })
    .catch(error => {
      console.error('Error fetching order items:', error);
    });
}

// Add event listener to the "View" button
document.querySelectorAll('[data-modal-target]').forEach(button => {
  button.addEventListener('click', function() {
    const modalId = this.getAttribute('data-modal-target');
    const orderId = modalId.replace('viewOrderModal', '');
    document.getElementById(modalId).classList.remove('hidden');
    fetchOrderItems(orderId); // Fetch order items when the modal is opened
  });
});

// Function to close the modal
function closeModal(modalId) {
  document.getElementById(modalId).classList.add('hidden');
}

</script>