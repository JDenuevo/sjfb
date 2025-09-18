<div class="flex flex-col">
  <div class="-m-1.5 overflow-x-auto">
    <div class="p-1.5 min-w-full inline-block align-middle">
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

        <!-- Filters and Search -->
        <div class="px-6 py-4 gap-3 md:flex border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">All Payments</h2>
            <p class="text-sm text-gray-600">
              <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> total payments
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
            
            <select name="payment_status" class="py-3 px-4 pe-9 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" onchange="this.form.submit()">   
              <option value="">All Statuses</option>
              <option value="Pending" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="Paid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'Paid') ? 'selected' : ''; ?>>Paid</option>
              <option value="Failed" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'Failed') ? 'selected' : ''; ?>>Failed</option>
              <option value="Refunded" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
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

        <!-- Payments Table -->
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
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Customer</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Provider ID</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Amount</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Payment Status</span>
                      </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                      <div class="flex items-center gap-x-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Date</span>
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
                      <tr class="payment-row bg-white">
                        <td class="size-px whitespace-nowrap">
                          <div class="ps-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo $row['order_code']; ?></span>
                          </div>
                        </td>
                        
                        <td class="size-px whitespace-nowrap">
                          <div class="px-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                            <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($row['billing_email']); ?></span>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800"><?php echo $row['provider_id'] ? htmlspecialchars($row['provider_id']) : 'N/A'; ?></span>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <span class="block text-sm font-semibold text-gray-800">₱<?php echo number_format($row['gross_amount'], 2); ?></span>
                            <?php if ($row['refunded_amount'] > 0): ?>
                              <span class="block text-xs text-red-500">Refunded: ₱<?php echo number_format($row['refunded_amount'], 2); ?></span>
                            <?php endif; ?>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <?php 
                              $status = $row['payment_status'];
                              $statusClass = '';

                              // Assign badge colors depending on the status
                              switch ($status) {
                                case 'Pending':
                                  $statusClass = 'bg-yellow-100 text-yellow-800';
                                  break;
                                case 'Paid':
                                  $statusClass = 'bg-green-100 text-green-800';
                                  break;
                                case 'Failed':
                                  $statusClass = 'bg-red-100 text-red-800';
                                  break;
                                case 'Refunded':
                                  $statusClass = 'bg-purple-100 text-purple-800';
                                  break;
                                default:
                                  $statusClass = 'bg-gray-100 text-gray-800';
                                  break;
                              }
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                              <?php echo ucfirst($status); ?>
                            </span>
                            <?php if ($row['failed_code']): ?>
                              <span class="block text-xs text-red-500 mt-1">Error: <?php echo htmlspecialchars($row['failed_code']); ?></span>
                            <?php endif; ?>
                          </div>
                        </td>

                        <td class="h-px w-72 whitespace-nowrap">
                          <div class="px-6 py-3">
                            <?php if ($row['paid_at']): ?>
                              <span class="block text-sm font-semibold text-gray-800"><?php echo date('M j, Y', strtotime($row['paid_at'])); ?></span>
                              <span class="block text-xs text-gray-500">Paid: <?php echo date('h:i A', strtotime($row['paid_at'])); ?></span>
                            <?php else: ?>
                              <span class="block text-sm font-semibold text-gray-800"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                              <span class="block text-xs text-gray-500">Created: <?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            <?php endif; ?>
                          </div>
                        </td>

                        <td class="size-px whitespace-nowrap">
                          <div class="px-6 py-1.5 flex justify-end">
                            <button class="inline-flex items-center gap-x-1 text-sm text-blue-600 decoration-2 hover:underline font-medium view-payment-btn" data-modal-target="viewPaymentModal<?php echo $row['payment_id']; ?>">
                              View
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-gray-500">No payments found.</td>
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
                        $preservedParams = ['payment_status', 'date_filter', 'search'];
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

<!-- View Payment Modals -->
<?php if (mysqli_num_rows($result) > 0): ?>
  <?php mysqli_data_seek($result, 0); // Reset the result pointer ?>
  <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div id="viewPaymentModal<?php echo $row['payment_id']; ?>" class="hidden fixed inset-0 z-100 overflow-y-auto bg-black bg-opacity-50" style="margin: 0">
      <div class="flex items-center justify-center min-h-screen px-4" style="margin: 20px;">
        <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 lg:w-3/4 mx-auto relative">
          <!-- Card -->
          <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl" id="paymentReceipt">
            <!-- Grid -->
            <div class="flex justify-between">
              <!-- Col -->
              <div class="">
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Payment Details</h2>
                <span class="mt-1 block text-gray-500">Payment ID: <?php echo htmlspecialchars($row['payment_id']); ?></span>
              </div>
              <!-- Col -->

              <div>
                <button class="text-gray-500 hover:text-gray-700" onclick="closeModal('viewPaymentModal<?php echo $row['payment_id']; ?>')">
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
                <h3 class="text-lg font-semibold text-gray-800 ">Order Information:</h3>
                <h3 class="text-lg font-semibold text-gray-500 ">Order Code: <?php echo htmlspecialchars($row['order_code']); ?></h3>
                <h3 class="mt-2 text-lg font-semibold text-gray-800 ">Customer:</h3>
                <address class="not-italic text-gray-500 ">
                  <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                  <?php echo htmlspecialchars($row['billing_email']); ?>
                </address>
              </div>
              <!-- Col -->
              
              <div class="sm:text-end space-y-2">
                <!-- Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800 ">Payment Status:</dt>
                    <dd class="col-span-2 text-gray-500 ">
                      <?php 
                        $status = $row['payment_status'];
                        $statusClass = '';
                        switch ($status) {
                          case 'Pending':
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            break;
                          case 'Paid':
                            $statusClass = 'bg-green-100 text-green-800';
                            break;
                          case 'Failed':
                            $statusClass = 'bg-red-100 text-red-800';
                            break;
                          case 'Refunded':
                            $statusClass = 'bg-purple-100 text-purple-800';
                            break;
                          default:
                            $statusClass = 'bg-gray-100 text-gray-800';
                        }
                      ?>
                      <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                        <?php echo ucfirst($status); ?>
                      </span>
                    </dd>
                  </dl>
                  <dl class="grid sm:grid-cols-5 gap-x-3">
                    <dt class="col-span-3 font-semibold text-gray-800 ">Payment Date:</dt>
                    <dd class="col-span-2 text-gray-500">
                      <?php 
                        if ($row['paid_at']) {
                          echo date("F j, Y, g:i a", strtotime($row['paid_at']));
                        } else {
                          echo date("F j, Y, g:i a", strtotime($row['created_at']));
                        }
                      ?>
                    </dd>
                  </dl>
                </div>
                <!-- End Grid -->
              </div>
              <!-- Col -->
            </div>
            <!-- End Grid -->

            <!-- Payment Details Table -->
            <div class="mt-6">
              <div class="border border-gray-200 p-4 rounded-lg space-y-4">
                <div class="grid grid-cols-2 gap-2 items-center">
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase">Payment Information</h5>
                  </div>
                  <div>
                    <h5 class="text-start text-xs font-medium text-black uppercase">Amount Details</h5>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <!-- Payment Information -->
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Provider ID:</span>
                      <span class="font-medium"><?php echo $row['provider_id'] ? htmlspecialchars($row['provider_id']) : 'N/A'; ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Payment Mode:</span>
                      <span class="font-medium"><?php echo $row['mode'] ? htmlspecialchars($row['mode']) : 'N/A'; ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Currency:</span>
                      <span class="font-medium"><?php echo $row['currency'] ? htmlspecialchars($row['currency']) : 'PHP'; ?></span>
                    </div>
                  </div>
                  
                  <!-- Amount Information -->
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Gross Amount:</span>
                      <span class="font-medium">₱<?php echo number_format($row['gross_amount'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Net Amount:</span>
                      <span class="font-medium">₱<?php echo number_format($row['net_amount'], 2); ?></span>
                    </div>
                    <?php if ($row['refunded_amount'] > 0): ?>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Refunded Amount:</span>
                      <span class="font-medium text-red-600">₱<?php echo number_format($row['refunded_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Payment Details Table -->

            <?php if ($row['failed_code']): ?>
            <!-- Error Information -->
            <div class="mt-6 p-4 bg-red-50 rounded-lg">
              <h3 class="text-lg font-semibold text-red-800 mb-2">Payment Error</h3>
              <p class="text-red-600">Error Code: <?php echo htmlspecialchars($row['failed_code']); ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-6 flex justify-end gap-x-3">
              <?php if ($row['payment_status'] === 'paid' && $row['refunded_amount'] == 0): ?>
              <button class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 12H4"/>
                  <path d="M10 18l-6-6 6-6"/>
                </svg>
                Process Refund
              </button>
              <?php endif; ?>
              
              <button class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" x2="12" y1="15" y2="3"/>
                </svg>
                Export Receipt
              </button>
            </div>
          </div>
          <!-- End Card -->
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>

<style>
  .payment-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .payment-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>

<script>
  // Function to fetch and display payment details via AJAX
  function fetchPaymentDetails(paymentId) {
    fetch(`./functions/fetch_payments.php?payment_id=${paymentId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const payment = data.payment;
          const modalContent = document.getElementById(`paymentDetails${paymentId}`);
          
          // Update modal content with payment data
          modalContent.innerHTML = `
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Provider ID:</span>
                  <span class="font-medium">${payment.provider_id || 'N/A'}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Payment Mode:</span>
                  <span class="font-medium">${payment.mode || 'N/A'}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Currency:</span>
                  <span class="font-medium">${payment.currency || 'PHP'}</span>
                </div>
              </div>
              
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Gross Amount:</span>
                  <span class="font-medium">₱${parseFloat(payment.gross_amount).toFixed(2)}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Net Amount:</span>
                  <span class="font-medium">₱${parseFloat(payment.net_amount).toFixed(2)}</span>
                </div>
                ${payment.refunded_amount > 0 ? `
                <div class="flex justify-between">
                  <span class="text-gray-600">Refunded Amount:</span>
                  <span class="font-medium text-red-600">₱${parseFloat(payment.refunded_amount).toFixed(2)}</span>
                </div>
                ` : ''}
              </div>
            </div>
          `;
        } else {
          console.error('Error fetching payment details:', data.message);
        }
      })
      .catch(error => {
        console.error('Error fetching payment details:', error);
      });
  }

  // Add event listener to the "View" button
  document.querySelectorAll('[data-modal-target]').forEach(button => {
    button.addEventListener('click', function() {
      const modalId = this.getAttribute('data-modal-target');
      const paymentId = modalId.replace('viewPaymentModal', '');
      document.getElementById(modalId).classList.remove('hidden');
      fetchPaymentDetails(paymentId); // Fetch payment details when the modal is opened
    });
  });

  // Function to close the modal
  function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
  }
</script>