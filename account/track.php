<!-- View Order Modals -->
<?php if (mysqli_num_rows($result) > 0): ?>
  <?php mysqli_data_seek($result, 0); // Reset the result pointer ?>
  <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <?php
      // Define tracking variables for this order
      $orderStatus = $row['order_status'] ?? '';
      
      $statusFlow = ['Pending', 'Processing', 'OutForDelivery', 'Delivered', 'Cancelled'];
      
      $statusDisplay = [
        'Pending' => 'Pending',
        'Processing' => 'Processing',
        'OutForDelivery' => 'Out for Delivery', 
        'Delivered' => 'Delivered',
        'Cancelled' => 'Cancelled'
      ];
      
      $currentStatusIndex = array_search($orderStatus, $statusFlow);
      if ($currentStatusIndex === false) $currentStatusIndex = 0;
      
      $progressPercentage = ($currentStatusIndex + 1) / count($statusFlow) * 100;
      
      $statusIcons = [
        'Pending' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
        'Processing' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 9 9"/><path d="M14.5 9h-5L7 12l2.5 3h5L17 12l-2.5-3z"/></svg>',
        'OutForDelivery' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M5 17h14v-3.5a1.5 1.5 0 0 0-1.5-1.5h-11A1.5 1.5 0 0 0 5 13.5V17z"/><path d="M5 9V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2"/></svg>',
        'Delivered' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
        'Cancelled' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>'
      ];
      
      $statusColors = [
        'Pending' => ['bg-gray-100', 'text-gray-800', 'border-gray-500'],
        'Processing' => ['bg-blue-100', 'text-blue-800', 'border-blue-500'],
        'OutForDelivery' => ['bg-orange-100', 'text-orange-800', 'border-orange-500'],
        'Delivered' => ['bg-green-100', 'text-green-800', 'border-green-500'],
        'Cancelled' => ['bg-red-100', 'text-red-800', 'border-red-500']
      ];
    ?>

    <div id="viewOrderModal<?php echo $row['order_id']; ?>" class="hidden fixed inset-0 z-100 overflow-y-auto bg-black bg-opacity-50" style="margin: 0">
      <div class="flex items-center justify-center min-h-screen px-4" style="margin: 20px;">
        <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 lg:w-3/4 mx-auto relative max-h-[90vh] overflow-y-auto">
          <!-- Card -->
          <div class="flex flex-col p-4 sm:p-10 bg-white rounded-xl">
            <!-- Close Button -->
            <div class="flex justify-between items-start mb-4">
              <div>
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800">Order Details</h2>
                <span class="mt-1 block text-lg text-blue-600 font-medium"><?php echo htmlspecialchars($row['order_code']); ?></span>
              </div>
              <button class="text-gray-500 hover:text-gray-700" onclick="closeModal('viewOrderModal<?php echo $row['order_id']; ?>')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 6L6 18" />
                  <path d="M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Tabs for Order Details and Tracking -->
            <div class="mb-6 border-b border-gray-200">
              <div class="flex space-x-8">
                <button onclick="switchTab('detailsTab<?php echo $row['order_id']; ?>', 'trackingTab<?php echo $row['order_id']; ?>', this)" 
                        class="py-4 px-1 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                  Order Details
                </button>
                <button onclick="switchTab('trackingTab<?php echo $row['order_id']; ?>', 'detailsTab<?php echo $row['order_id']; ?>', this)" 
                        class="py-4 px-1 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
                  Track Order
                </button>
              </div>
            </div>

            <!-- Order Details Tab -->
            <div id="detailsTab<?php echo $row['order_id']; ?>" class="tab-content">
              <!-- Grid -->
              <div class="my-8 grid sm:grid-cols-2 gap-3">
                <div>
                  <h3 class="text-lg font-semibold text-gray-800">Delivery Information:</h3>
                  <h3 class="text-lg font-semibold text-gray-500"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h3>
                  <h3 class="mt-2 text-lg font-semibold text-gray-800">Address:</h3>
                  <address class="not-italic text-gray-500">
                    <?php echo htmlspecialchars($row['address']); ?><br>
                    <?php echo htmlspecialchars($row['city']); ?>, <?php echo htmlspecialchars($row['postal_code']); ?>
                  </address>
                </div>
                
                <div class="sm:text-end space-y-2">
                  <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                    <dl class="grid sm:grid-cols-5 gap-x-3">
                      <dt class="col-span-3 font-semibold text-gray-800">Payment Method:</dt>
                      <dd class="col-span-2 text-gray-500">
                        <?php
                          $method = strtolower($row['payment_method']);
                          switch ($method) {
                            case 'gcash': $methodLabel = 'Gcash'; $methodClass = 'bg-blue-100 text-blue-800'; break;
                            case 'paymaya': $methodLabel = 'Maya'; $methodClass = 'bg-green-100 text-green-800'; break;
                            case 'grab_pay': $methodLabel = 'Grab Pay'; $methodClass = 'bg-green-100 text-green-800'; break;
                            case 'qrph': $methodLabel = 'QR Ph'; $methodClass = 'bg-red-100 text-red-800'; break;
                            case 'cod': $methodLabel = 'Cash on Delivery'; $methodClass = 'bg-orange-100 text-orange-800'; break;
                            case 'card': $methodLabel = 'Visa/Mastercard'; $methodClass = 'bg-purple-100 text-purple-800'; break;
                            default: $methodLabel = ucfirst($method); $methodClass = 'bg-gray-100 text-gray-800';
                          }
                        ?>
                        <p class="text-sm mt-2 inline-block px-2 py-1 rounded-full font-medium <?php echo $methodClass; ?>">
                          <?php echo $methodLabel; ?>
                        </p>
                      </dd>
                    </dl>
                    <dl class="grid sm:grid-cols-5 gap-x-3">
                      <dt class="col-span-3 font-semibold text-gray-800">Order date:</dt>
                      <dd class="col-span-2 text-gray-500"><?= date("F j, Y, g:i a", strtotime($row['order_date'])); ?></dd>
                    </dl>
                  </div>
                </div>
              </div>

              <!-- Table -->
              <div class="mt-6">
                <div class="border border-gray-200 p-4 rounded-lg space-y-4">
                  <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 items-center">
                    <div class="col-span-full sm:col-span-2">
                      <h5 class="text-start text-xs font-medium text-black uppercase">Item Name</h5>
                    </div>
                    <div><h5 class="text-start text-xs font-medium text-black uppercase">Variant</h5></div>
                    <div><h5 class="text-start text-xs font-medium text-black uppercase">Price</h5></div>
                    <div><h5 class="text-start text-xs font-medium text-black uppercase">Qty</h5></div>
                    <div><h5 class="text-start text-xs font-medium text-black uppercase">Amount</h5></div>
                  </div>

                  <div class="overflow-x-auto">
                    <div class="min-w-full text-sm text-left text-gray-500">
                      <div class="divide-y divide-gray-200" id="orderItems<?php echo $row['order_id']; ?>">
                        <!-- Order items will be dynamically loaded here -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Subtotal Section -->
              <div class="mt-8 p-4">
                <div class="grid grid-cols-4 gap-2">
                  <dt class="text-lg font-semibold text-gray-800">Total:</dt>
                  <div></div><div></div>
                  <dd class="text-lg font-semibold text-gray-800">₱<?php echo number_format($row['total_price'], 2); ?></dd>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="mt-6 flex justify-end gap-x-3">
                <?php if ($row['order_status'] === 'Pending' && $row['payment_method'] === 'cod'): ?>
                  <button onclick="cancelOrder(<?php echo $row['order_id']; ?>)" 
                          class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:outline-none">
                    Cancel Order
                  </button>
                <?php endif; ?>
                <button onclick="switchTab('trackingTab<?php echo $row['order_id']; ?>', 'detailsTab<?php echo $row['order_id']; ?>', this)" 
                        class="inline-flex items-center gap-x-2 text-sm font-medium rounded-lg py-3 px-4 justify-center border border-transparent bg-orange-600 text-white hover:bg-orange-700">
                  Track Order
                </button>
              </div>
            </div>

            <!-- Tracking Tab -->
            <div id="trackingTab<?php echo $row['order_id']; ?>" class="tab-content hidden">
              <!-- Order Status Progress -->
              <div class="max-w-4xl mx-auto mt-4 bg-white p-6 rounded-lg border">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 text-center">Order Tracking</h3>
                
                <!-- Progress Bar -->
                <div class="mb-8">
                  <div class="inline-block mb-2 ms-[calc(50%-20px)] py-0.5 px-1.5 bg-blue-50 border border-blue-200 text-xs font-medium text-blue-600 rounded-lg">
                    <?= round($progressPercentage) ?>%
                  </div>
                  <div class="flex w-full h-2 bg-gray-200 rounded-full overflow-hidden" role="progressbar" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="flex flex-col justify-center rounded-full overflow-hidden bg-blue-600 text-xs text-white text-center whitespace-nowrap transition duration-500" style="width: <?= $progressPercentage ?>%"></div>
                  </div>
                </div>
                
                <!-- Status Steps -->
                <div class="flex justify-between relative mb-8">
                  <?php foreach ($statusFlow as $index => $status): ?>
                    <?php
                      $isCompleted = $index < $currentStatusIndex;
                      $isCurrent = $index === $currentStatusIndex;
                      $isCancelled = $orderStatus === 'Cancelled';
                      
                      if ($isCancelled && $status === 'Cancelled') {
                        $circleClass = "bg-red-500 text-white border-red-500";
                        $textClass = "text-red-600 font-semibold";
                      } elseif ($isCompleted) {
                        $circleClass = "bg-green-500 text-white border-green-500";
                        $textClass = "text-green-600";
                      } elseif ($isCurrent) {
                        $circleClass = (isset($statusColors[$status][0]) ? $statusColors[$status][0] : 'bg-gray-100') . " " . 
                                      (isset($statusColors[$status][1]) ? $statusColors[$status][1] : 'text-gray-800') . " border-2 " . 
                                      (isset($statusColors[$status][2]) ? $statusColors[$status][2] : 'border-gray-500');
                        $textClass = "text-blue-600 font-semibold";
                      } else {
                        $circleClass = "bg-gray-100 text-gray-400 border-gray-300";
                        $textClass = "text-gray-500";
                      }
                    ?>
                    
                    <div class="flex flex-col items-center relative z-10" style="width: <?= 100/count($statusFlow) ?>%">
                      <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center mb-2 <?= $circleClass ?>">
                        <?= isset($statusIcons[$status]) ? $statusIcons[$status] : $statusIcons['Pending'] ?>
                      </div>
                      <span class="text-sm font-medium <?= $textClass ?> text-center"><?= isset($statusDisplay[$status]) ? $statusDisplay[$status] : $status ?></span>
                    </div>
                    
                    <?php if ($index < count($statusFlow) - 1): ?>
                      <div class="absolute top-5 left-<?= ($index + 1) * (100 / count($statusFlow)) - (100 / (count($statusFlow) * 2)) ?>% w-<?= 100 / count($statusFlow) - 10 ?>% h-0.5 bg-gray-300 z-0"></div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                
                <!-- Current Status Display -->
                <div class="mt-8 text-center p-4 bg-gray-50 rounded-lg">
                  <p class="text-lg font-medium text-gray-700">Current Status:</p>
                  <p class="text-xl font-bold <?= isset($statusColors[$orderStatus][1]) ? $statusColors[$orderStatus][1] : 'text-gray-800' ?>">
                    <?= isset($statusDisplay[$orderStatus]) ? $statusDisplay[$orderStatus] : $orderStatus ?>
                  </p>
                  <?php if ($orderStatus === 'Delivered'): ?>
                    <p class="text-sm text-gray-600 mt-2">Your order has been successfully delivered.</p>
                  <?php elseif ($orderStatus === 'Cancelled'): ?>
                    <p class="text-sm text-gray-600 mt-2">This order has been cancelled.</p>
                  <?php elseif ($orderStatus === 'Pending'): ?>
                    <p class="text-sm text-gray-600 mt-2">Your order is pending confirmation.</p>
                  <?php elseif ($orderStatus === 'Processing'): ?>
                    <p class="text-sm text-gray-600 mt-2">Your order is being processed.</p>
                  <?php elseif ($orderStatus === 'OutForDelivery'): ?>
                    <p class="text-sm text-gray-600 mt-2">Your order is out for delivery.</p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Back Button -->
              <div class="mt-6 flex justify-center">
                <button onclick="switchTab('detailsTab<?php echo $row['order_id']; ?>', 'trackingTab<?php echo $row['order_id']; ?>', this)" 
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 hover:bg-gray-50">
                  Back to Order Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>

<!-- Add this CSS -->
<style>
  .tab-content {
    display: block;
  }
  .tab-content.hidden {
    display: none;
  }
</style>

<!-- Add this JavaScript -->
<script>
  function switchTab(showId, hideId, button) {
    // Hide all tabs and show the selected one
    document.getElementById(showId).classList.remove('hidden');
    document.getElementById(hideId).classList.add('hidden');
    
    // Update tab styles
    const tabs = button.parentElement.parentElement.querySelectorAll('button');
    tabs.forEach(tab => {
      tab.classList.remove('text-blue-600', 'border-blue-600');
      tab.classList.add('text-gray-500', 'border-transparent');
    });
    
    button.classList.remove('text-gray-500', 'border-transparent');
    button.classList.add('text-blue-600', 'border-blue-600');
  }
</script>