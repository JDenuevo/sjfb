  <div class="flex flex-col">
    <div class="-m-1.5 overflow-x-auto">
      <div class="p-1.5 min-w-full inline-block align-middle">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden  ">
          <!-- Header -->
          <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 ">
            <div>
              <h2 class="text-xl font-semibold text-gray-800 ">
                Orders
              </h2>
              <p class="text-sm text-gray-600 ">
                Manage your orders
              </p>
            </div>
            
          </div>
          <!-- End Header -->

          <!-- Table -->
          <table class="min-w-full divide-y divide-gray-200 ">
            <thead class="bg-gray-50 ">
              <tr>
                
                <th scope="col" class="ps-6 py-3 text-start">
                  <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                      Order ID
                    </span>
                  </div>
                </th>

                <th scope="col" class="px-6 py-3 text-start">
                  <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                      Ordered by
                    </span>
                  </div>
                </th>

                <th scope="col" class="px-6 py-3 text-start">
                  <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                      User Type
                    </span>
                  </div>
                </th>

                <th scope="col" class="px-6 py-3 text-start">
                  <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                      Date Ordered
                    </span>
                  </div>
                </th>

                <th scope="col" class="px-6 py-3 text-start">
                  <div class="flex items-center gap-x-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 ">
                      Order Status
                    </span>
                  </div>
                </th>

                <th scope="col" class="px-6  text-end"></th>
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
                      <div class="px-6 py-1.5">
                        <a href="javascript:void(0)" onclick="openOrderModal(<?php echo $row['order_id']; ?>)" class="text-sm text-orange-600 hover:underline font-medium">View Orders</a>
                      </div>
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

          <!-- Footer -->
          <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 ">
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


<!-- View Orders Modal -->
<div id="ViewOrdersModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto"> 
  <div class="bg-white p-6 rounded-2xl shadow-2xl w-11/12 sm:w-4/5 md:w-3/4 lg:max-w-3xl xl:max-w-3xl max-h-[50vh] flex flex-col modal-content">
    <div class="overflow-y-auto max-h-[40vh]" id="modalContent"></div>
  </div>
</div>

<script>
  function openOrderModal(orderId) {
    fetch(`./functions/fetch_orders.php?order_id=${orderId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const modal = document.getElementById('ViewOrdersModal');
          document.getElementById('modalContent').innerHTML = `<p><strong>Order ID:</strong> ${data.order.order_id}</p>
            <p><strong>Product Name:</strong> ${data.order.product_name}</p>
            <p><strong>Product Price:</strong> ${data.order.product_price}</p>
            <p><strong>Discount Price:</strong> ${data.order.discount_price || 'N/A'}</p>`;
          modal.classList.remove('hidden');
        } else {
          alert('Failed to fetch order details.');
        }
      })
      .catch(error => console.error('Error:', error));
  }

  document.getElementById('ViewOrdersModal').addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.add('hidden');
    }
  });
</script>
