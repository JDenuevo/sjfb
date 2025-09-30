            
<!-- Back Button -->
<div class="flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="orders.php" class="inline-flex items-center gap-x-2 text-sm text-blue-600 hover:text-blue-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Orders
        </a>
    </div>
    
</div>

<!-- Alert Messages -->
<?php if (!empty($_SESSION['message'])): ?>
    <div class="<?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded border" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['message']['text']; ?></span>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<!-- Order Overview -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Order Management</h1>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                <?php
                    switch($order['order_status']) {
                        case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                        case 'Processing': echo 'bg-blue-100 text-blue-800'; break;
                        case 'OutForDelivery': echo 'bg-purple-100 text-purple-800'; break;
                        case 'Delivered': echo 'bg-green-100 text-green-800'; break;
                        case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                        default: echo 'bg-gray-100 text-gray-800'; break;
                    }
                ?>">
                <?php 
                    // Define display labels
                    $statusLabels = [
                        'Pending' => 'Pending',
                        'Processing' => 'Processing',
                        'OutForDelivery' => 'Out For Delivery',
                        'Delivered' => 'Delivered',
                        'Cancelled' => 'Cancelled'
                    ];
                    
                    echo $statusLabels[$order['order_status']] ?? $order['order_status']; 
                ?>
            </span>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order Information -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Order Code:</span>
                    <span class="font-medium text-blue-600"><?php echo htmlspecialchars($order['order_code']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Customer:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Phone:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($order['phone_number']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Order Date:</span>
                    <span class="font-medium"><?php echo date('F j, Y @ g:i A', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Amount:</span>
                    <span class="font-bold text-lg text-green-600">₱<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment & Delivery Information -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment & Delivery</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="font-medium"><?php echo ucfirst($order['payment_method']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Status:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        <?php
                            switch($order['payment_status']) {
                                case 'Paid': echo 'bg-green-100 text-green-800'; break;
                                case 'Failed': echo 'bg-red-100 text-red-800'; break;
                                case 'Refunded': echo 'bg-blue-100 text-blue-800'; break;
                                default: echo 'bg-yellow-100 text-yellow-800'; break;
                            }
                        ?>">
                        <?php echo $order['payment_status'] ?: 'Pending'; ?>
                    </span>
                </div>
                <?php if ($order['assigned_rider_id']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Assigned Rider:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($order['rider_first_name'] . ' ' . $order['rider_last_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vehicle:</span>
                        <span class="font-medium"><?php echo ucfirst($order['vehicle_type']) . ' - ' . $order['vehicle_plate_number']; ?></span>
                    </div>
                <?php endif; ?>
                <div class="pt-2 border-t">
                    <p class="text-sm text-gray-600 mb-1">Delivery Address:</p>
                    <address class="not-italic text-gray-900">
                        <?php echo htmlspecialchars($order['address']); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['postal_code']); ?>
                    </address>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Actions -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Actions</h3>
    <div class="flex flex-wrap gap-3">
        
        <?php if ($order['order_status'] === 'Pending'): ?>
            <!-- Approve Order -->
            <form method="POST" action="./functions/order_process.php" class="inline-block">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                <input type="hidden" name="notes" value="Order approved by admin">
                <button type="submit" name="approve_order" 
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        onclick="return confirm('Are you sure you want to approve this order?')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Approve Order
                </button>
            </form>
        <?php endif; ?>

        <?php if ($order['order_status'] === 'Processing'): ?>
            <!-- Assign Rider -->
            <button type="button" onclick="openRiderModal()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Assign Rider
            </button>
        <?php endif; ?>

    </div>
</div>

<!-- Order Items -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                      
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($item['variant_name'] ?: 'Standard'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $item['quantity']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ₱<?php echo number_format($item['price'], 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                        Total Amount:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                        ₱<?php echo number_format($order['total_price'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Order Timeline & Activity Log -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Order Timeline -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Timeline</h3>
        <div class="space-y-4">
            <?php if (empty($order_timeline)): ?>
                <p class="text-gray-500 text-sm">No status changes recorded yet.</p>
            <?php else: ?>
                <?php foreach ($order_timeline as $timeline): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">
                                    Status changed from 
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs"><?php echo htmlspecialchars($timeline['old_status']); ?></span>
                                    to
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs"><?php echo htmlspecialchars($timeline['new_status']); ?></span>
                                </span>
                            </div>
                            <?php if ($timeline['notes']): ?>
                                <div class="text-sm text-gray-600 mt-1">
                                    <?php echo htmlspecialchars($timeline['notes']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo date('M j, Y @ g:i A', strtotime($timeline['created_at'])); ?>
                                <?php if ($timeline['first_name'] && $timeline['last_name']): ?>
                                    by <?php echo htmlspecialchars($timeline['first_name'] . ' ' . $timeline['last_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Log</h3>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php if (empty($activity_log)): ?>
                <p class="text-gray-500 text-sm">No activities recorded yet.</p>
            <?php else: ?>
                <?php foreach ($activity_log as $log): ?>
                    <div class="border-l-2 border-gray-200 pl-4 py-2">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </div>
                        <?php if ($log['details']): ?>
                            <div class="text-sm text-gray-600 mt-1">
                                <?php echo htmlspecialchars($log['details']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="text-xs text-gray-500 mt-1">
                            <?php echo date('M j, Y @ g:i A', strtotime($log['created_at'])); ?>
                            <?php if ($log['first_name'] && $log['last_name']): ?>
                                by <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Assign Rider Modal -->
<div id="riderModal" class="hidden fixed inset-0 z-100 overflow-y-auto bg-black bg-opacity-50" style="margin: 0">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Rider</h3>
            <form method="POST" action="./functions/order_process.php">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Rider</label>
                    <select name="rider_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">Choose a rider...</option>
                        <?php foreach ($available_riders as $rider): ?>
                            <option value="<?php echo $rider['rider_id']; ?>" 
                                    data-orders="<?php echo $rider['active_orders']; ?>">
                                <?php echo htmlspecialchars($rider['first_name'] . ' ' . $rider['last_name']); ?> 
                                (<?php echo ucfirst($rider['vehicle_type']); ?> - 
                                <?php echo $rider['vehicle_plate_number']; ?>)
                                - <?php echo $rider['active_orders']; ?> active orders
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Special delivery instructions..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRiderModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" name="assign_rider" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Assign Rider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openRiderModal() {
        document.getElementById('riderModal').classList.remove('hidden');
    }

    function closeRiderModal() {
        document.getElementById('riderModal').classList.add('hidden');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['riderModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>