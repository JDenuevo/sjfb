<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
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
                          <?php 
                              $userType = $row['is_guest_order'] == 0 ? 'Customer' : 'Guest';
                              $badgeColor = $row['is_guest_order'] == 0 ? 'bg-orange-500 text-white' : 'bg-green-500 text-white';
                          ?>
                          <span class="block text-sm font-semibold text-gray-800">
                              <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeColor; ?>">
                                  <?php echo $userType; ?>
                              </span>
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

                  <?php
                    // Set status class based on order_status
                    $status = $row['order_status'];
                    switch ($status) {
                      case 'Pending':
                        $statusClass = 'bg-yellow-100 text-yellow-800';
                        break;
                      case 'Approved':
                        $statusClass = 'bg-blue-100 text-blue-800';
                        break;
                      case 'Shipped':
                        $statusClass = 'bg-indigo-100 text-indigo-800';
                        break;
                      case 'Delivered':
                        $statusClass = 'bg-green-100 text-green-800';
                        break;
                      case 'Cancelled':
                        $statusClass = 'bg-red-100 text-red-800';
                        break;
                      default:
                        $statusClass = 'bg-gray-100 text-gray-800';
                    }
                  ?>
                  <!-- Order Status (Styled Select Box) -->
                  <td class="size-px w-72 whitespace-nowrap">
                    <div class="px-6">
                      <form action="" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                        <select
                          name="order_status"
                          class="py-2.5 px-4 pe-9 block w-full border border-gray-200 rounded-full text-sm font-medium focus:border-blue-500 focus:ring-blue-500 <?php echo $statusClass; ?>"
                          onchange="this.form.submit()">
                          <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                          <option value="Approved" <?php echo $status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                          <option value="Shipped" <?php echo $status == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                          <option value="Delivered" <?php echo $status == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                          <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                      </form>
                    </div>
                  </td>

                  <!-- View Button -->
                  <td class="p-2 whitespace-nowrap">
                    <button style="background-color: #3b82f6;" class="px-3 py-2 text-white rounded-xl" data-modal-target="viewOrderModal<?php echo $row['order_id']; ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye">
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
    <div id="viewOrderModal<?php echo $row['order_id']; ?>" class="fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto p-10" style="margin: 0;">
      <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl">
        <div class="p-6 border-b font-bold text-lg flex justify-between">
          <h3 class="text-lg font-semibold">Order Details</h3>
          <button class="text-gray-500 hover:text-gray-700" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6L6 18" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 h-auto lg:max-h-[40vh] overflow-y-auto">
         
          <div class="text-center">
            <!-- Customer Details -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 inline-block text-center">
              <p class="text-blue-800 font-medium">Order #<?php echo htmlspecialchars ($row['order_id']); ?></p>
            </div>
          </div>

          <!-- Order Details -->
          <div class="space-y-4">
            
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
            <div class="overflow-x-auto">
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
              <?php
                $method = strtolower($row['payment_method']);
                switch ($method) {
                  case 'ewallet':
                    $methodLabel = 'E-Wallet';
                    $methodClass = 'bg-purple-100 text-purple-800';
                    break;
                  case 'cod':
                    $methodLabel = 'Cash on Delivery';
                    $methodClass = 'bg-orange-100 text-orange-800';
                    break;
                  case 'bank':
                    $methodLabel = 'Bank Transfer';
                    $methodClass = 'bg-blue-100 text-blue-800';
                    break;
                  default:
                    $methodLabel = ucfirst($method);
                    $methodClass = 'bg-gray-100 text-gray-800';
                }
              ?>
              <p class="text-sm mt-2 inline-block px-2 py-1 rounded-full font-medium <?php echo $methodClass; ?>">
                <?php echo $methodLabel; ?>
              </p>
            </div>

            <!-- Total Price -->
            <div>
              <h4 class="font-semibold text-gray-800">Total Price</h4>
              <p class="text-sm text-gray-600 mt-2">₱<?php echo number_format($row['total_price'], 2); ?></p>
            </div>
          </div>

          <div class="flex justify-between space-x-3 mt-4">
    
            <button type="submit" name="generate_waybill" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg--700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Generate Waybill</button>
            <div>
              <!-- <button type="submit" name="approve_order" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Approve</button> -->
              <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">Cancel</button>
                  
            </div>
               
          </div>
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