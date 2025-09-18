<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

        <!-- Filters and Search -->
        <div class="px-6 py-4 gap-3 md:flex border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">All Orders</h2>
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> total orders
            </p>
          </div>

          <form method="GET" action="" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <!-- Preserve existing parameters -->
            <?php 
            $preservedParams = ['search', 'page'];
            foreach ($preservedParams as $param) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    echo '<input type="hidden" name="' . $param . '" value="' . htmlspecialchars($_GET[$param]) . '">';
                }
            }
            ?>

            <select name="status" class="py-3 px-4 pe-9 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" onchange="this.form.submit()">   
              <option value="">All Statuses</option>
              <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="Processing" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
              <option value="Shipped" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
              <option value="OutForDelivery" <?php echo (isset($_GET['status']) && $_GET['status'] === 'OutForDelivery') ? 'selected' : ''; ?>>OutForDelivery</option>
              <option value="Delivered" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
              <option value="Cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
              <input 
                type="text" 
                name="search" 
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                class="py-3 px-4 block w-full text-sm focus:border-blue-500 focus:ring-blue-500 border-none" 
                placeholder="Search..."
              >
              <button type="submit" class="px-3 flex items-center justify-center text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/>
                  <path d="M21 21l-6 -6"/>
                </svg>
              </button>
            </div>
          </form>
        </div>

        <!-- Orders Table -->
        <div class="flex flex-col">
          <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="ps-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Order Code</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Ordered by</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Type</span>
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
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Payment Status</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-end">
                      <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</span>
                    </th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                  <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>    
                      <tr class="order-row bg-white">
                        <td class="size-px whitespace-nowrap">
                          <div class="ps-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo $row['order_code']; ?></span>
                          </div>
                        </td>
                        
                        <td class="size-px whitespace-nowrap">
                          <div class="px-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
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

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo date('F j, Y', strtotime($row['order_date'])); ?></span>
                            <span class="block text-xs text-gray-500 mt-1"><?php echo date('h:i A', strtotime($row['order_date'])); ?></span>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <?php 
                              $status = $row['order_status'];

                              // Assign badge colors depending on the status
                              switch ($status) {
                                case 'Pending':
                                  $badgeColor = 'bg-yellow-500 text-white';
                                  break;
                                case 'Processing':
                                  $badgeColor = 'bg-blue-500 text-white';
                                  break;
                                case 'Shipped':
                                  $badgeColor = 'bg-indigo-500 text-white';
                                  break;
                                case 'OutForDelivery':
                                  $badgeColor = 'bg-purple-500 text-white';
                                  break;
                                case 'Delivered':
                                  $badgeColor = 'bg-green-500 text-white';
                                  break;
                                case 'Cancelled':
                                  $badgeColor = 'bg-red-500 text-white';
                                  break;
                                default:
                                  $badgeColor = 'bg-gray-400 text-white'; // fallback for unexpected statuses
                                  break;
                              }
                            ?>
                            <span class="block text-sm font-semibold text-gray-800">
                              <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeColor; ?>">
                                <?php echo $status; ?>
                              </span>
                            </span>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <?php 
                              $status = $row['payment_status'];

                              // Assign badge colors depending on the status
                              switch ($status) {
                                case 'Pending':
                                  $badgeColor = 'bg-yellow-500 text-white';
                                  break;
                                case 'Paid':
                                  $badgeColor = 'bg-green-500 text-white';
                                  break;
                                case 'Failed':
                                  $badgeColor = 'bg-red-500 text-white';
                                  break;
                                case 'Refunded':
                                  $badgeColor = 'bg-blue-500 text-white';
                                  break;
                                default:
                                  $badgeColor = 'bg-gray-400 text-white'; // fallback for unexpected statuses
                                  break;
                              }
                            ?>
                            <span class="block text-sm font-semibold text-gray-800">
                              <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeColor; ?>">
                                <?php echo $status; ?>
                              </span>
                            </span>
                          </div>
                        </td>

                        <td class="p-2 size-px whitespace-nowrap">
                            <div class="px-6 py-1.5 flex justify-end">
                                <button class="inline-flex items-center gap-x-1 text-sm text-blue-600 decoration-2 hover:underline font-medium" data-modal-target="viewOrderModal<?php echo $row['order_id']; ?>">
                                    View
                                </button>
                            </div>
                        </td>

                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-gray-500">No orders found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
            </table>
            <!-- End Table -->
                        
           <!-- Footer -->
            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                <div>
                    <p class="text-sm text-gray-600">
                        <span class="font-semibold text-gray-800">
                            <?php echo mysqli_num_rows($result); ?>
                        </span> results
                    </p>
                </div>

                <div>
                    <div class="inline-flex gap-x-2">
                        <?php
                        // Build query string with current filters
                        $queryParams = [];
                        $preservedParams = ['status', 'search'];
                        foreach ($preservedParams as $param) {
                            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                                $queryParams[$param] = $_GET[$param];
                            }
                        }
                        
                        // Previous button
                        if ($page > 1): 
                            $prevParams = $queryParams;
                            $prevParams['page'] = $page - 1;
                            $prevQueryString = http_build_query($prevParams);
                        ?>
                            <a href="?<?php echo $prevQueryString; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
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
                        
                        for ($i = $start; $i <= $end; $i++): 
                            $pageParams = $queryParams;
                            $pageParams['page'] = $i;
                            $pageQueryString = http_build_query($pageParams);
                        ?>
                            <a href="?<?php echo $pageQueryString; ?>" class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-800'; ?> py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next button -->
                        <?php if ($page < $totalPages): 
                            $nextParams = $queryParams;
                            $nextParams['page'] = $page + 1;
                            $nextQueryString = http_build_query($nextParams);
                        ?>
                            <a href="?<?php echo $nextQueryString; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
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
          </div>
        </div>

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
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Order Code</h2>
                <span class="mt-1 block text-gray-500"><?php echo htmlspecialchars ($row['order_code']); ?></span>
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
                            case 'gcash':
                              $methodLabel = 'Gcash';
                              $methodClass = 'bg-blue-100 text-blue-800';
                              break;
                            case 'paymaya':
                              $methodLabel = 'Maya';
                              $methodClass = 'bg-green-100 text-green-800';
                              break;
                            case 'grab_pay':
                              $methodLabel = 'Grab Pay';
                              $methodClass = 'bg-green-100 text-green-800';
                              break;
                            case 'qrph':
                              $methodLabel = 'QR Ph';
                              $methodClass = 'bg-red-100 text-red-800';
                              break;
                            case 'cod':
                              $methodLabel = 'Cash on Delivery';
                              $methodClass = 'bg-orange-100 text-orange-800';
                              break;
                            case 'card':
                              $methodLabel = 'Visa/Mastercard';
                              $methodClass = 'bg-purple-100 text-purple-800';
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

            <div class="mt-6 flex justify-end gap-x-3">
              <form action="./functions/export_waybill.php" method="POST">
                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                <button type="submit" name="" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" x2="12" y1="15" y2="3"/>
                  </svg>
                  Generate Waybill
                </button>
              </form>
            </div>
          </div>
          <!-- End Card -->
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>

<style>
  .order-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .order-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>

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