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
    <div id="viewOrderModal<?php echo $row['order_id']; ?>" class="hidden fixed inset-0 z-100 overflow-y-auto bg-black bg-opacity-50" style="margin: 0">
      <div class="flex items-center justify-center min-h-screen px-4" style="margin: 20px;">
        <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 lg:w-3/4 mx-auto relative">
          <!-- Card -->
          <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl" id="orderReceipt">
            <!-- Grid -->
            <div class="flex justify-between">
              <!-- Col -->
              <div class="">
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Order #</h2>
                <span class="mt-1 block text-gray-500 text-lg"><?php echo htmlspecialchars ($row['order_id']); ?></span>
              </div>
              <!-- Col -->

              <div>
                <button class="text-gray-500 hover:text-gray-700" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18" />
                    <path d="M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
            <!-- End Grid -->

            <!-- Grid -->
            <div class="my-8 grid sm:grid-cols-2 gap-3">
              <div>
                <h3 class="text-lg font-semibold text-gray-800 ">Shipping Information:</h3>
                <h3 class="text-lg font-semibold text-gray-500 "><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h3>
                <h3 class="mt-2 text-lg font-semibold text-gray-800 ">Address:</h3>
                <address class="not-italic text-gray-500 ">
                  <?php echo htmlspecialchars ($row['address']); ?><br>
                  <?php echo htmlspecialchars ($row['city']); ?>, <?php echo htmlspecialchars ($row['postal_code']); ?>
                </address>
              </div>
              <!-- Col -->
              
              <div class="sm:text-end space-y-2">
                <!-- Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800 ">Payment Method:</dt>
                    <dd class="col-span-2 text-gray-500 ">
                       <!-- Payment Method -->
                      <div>
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
                    </dd>
                  </dl>
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                  <dt class="col-span-3 font-semibold text-gray-800 ">Order date:</dt>
                  <dd class="col-span-2 text-gray-500"><?= date("F j, Y, g:i a", strtotime($row['order_date'])); ?></dd>
                  </dl>
                </div>
                <!-- End Grid -->
              </div>
              <!-- Col -->
            </div>
            <!-- End Grid -->

            <!-- Table -->
            <div class="mt-6">
              <div class="border border-gray-200 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 items-center">
                  <div class="col-span-full sm:col-span-2">
                    <h5 class="text-start text-xs font-medium text-black uppercase">Item Name</h5>
                  </div>
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase ">Variant</h5>
                  </div>
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase ">Price</h5>
                  </div>
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase ">Qty</h5>
                  </div>
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase ">Amount</h5>
                  </div>
                </div>

                <!-- Now properly use a real table -->
                <div class="overflow-x-auto">
                  <table class="min-w-full text-sm text-left text-gray-500">
                    <tbody class="divide-y divide-gray-200" id="orderItems<?php echo $row['order_id']; ?>">
                      <!-- Order items will be dynamically loaded here -->
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!-- End Table -->

            <!-- Subtotal Section -->
            <div class="mt-8 p-4">
              <div class="grid grid-cols-4 gap-2">
                <!-- Empty columns to push subtotal to the right -->
              
                <dt class="text-lg font-semibold text-gray-800">Subtotal:</dt>
          
                <div></div>
                <div></div>
                
                <dd class="text-lg font-semibold text-gray-800">₱<?php echo number_format($row['total_price'], 2); ?></dd>
              
              </div>
            </div>

            <div class="mt-4 sm:mt-8 p-4">
              <!-- Buttons -->
              <div class="mt-6 flex justify-end gap-x-3">
                <a id="downloadBtn" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" href="javascript:void(0);">
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                  Generate Waybill
                </a>
               
              </div>
              <!-- End Buttons -->
            </div>
          </div>
          <!-- End Card -->
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

          // Add the item row
          tbody.innerHTML += `
            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
              <div class="col-span-full sm:col-span-2">
                <p class="font-medium text-gray-800 ">${item.product_name}</p>
              </div>
              <div>
                <p class="text-gray-800 ">${item.variant_name}</p>
              </div>
              <div>
                <p class="text-gray-800 ">₱${price.toFixed(2)}</p>
              </div>
              <div>
                <p class="text-gray-800 ">${item.quantity}</p>
              </div>
              <div>
                <p class="text-gray-800 ">₱${total.toFixed(2)}</p>
              </div>
            </div>
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