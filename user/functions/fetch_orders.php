<?php
session_start();
include '../../conn.php';

// Check if user is logged in
if (!isset($_SESSION['loggedinasuser'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$account_id = $_SESSION['account_id'];

// Verify the order belongs to this customer
$order_query = "SELECT 
                  o.*,
                  a.username
                FROM orders o
                LEFT JOIN accounts a ON o.account_id = a.account_id
                WHERE o.order_id = ?
                AND o.account_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $account_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT 
                  oi.*, 
                  p.product_name, 
                  pv.variant_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                JOIN product_variants pv ON oi.variant_id = pv.variant_id
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<!-- Modal Content -->
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-lg font-semibold">Order Details</h3>
    <button class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('orderDetailsModal').classList.add('hidden')">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6L6 18" />
        <path d="M6 6l12 12" />
      </svg>
    </button>
  </div>

  <div class="text-center mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 inline-block">
      <p class="text-blue-800 font-medium">Order #<?= $order['order_id'] ?></p>
      <p class="text-sm text-gray-600">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?></p>
    </div>
  </div>

  <div class="space-y-6">
    <!-- Customer Information -->
    <div>
      <h4 class="font-semibold text-gray-800 mb-2">Customer Information</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
        <div>
          <p class="text-sm text-gray-600"><span class="font-medium">Name:</span> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
          <p class="text-sm text-gray-600"><span class="font-medium">Username:</span> <?= htmlspecialchars($order['username']) ?></p>
          <p class="text-sm text-gray-600"><span class="font-medium">Email:</span> <?= htmlspecialchars($order['email']) ?></p>
          <p class="text-sm text-gray-600"><span class="font-medium">Phone:</span> <?= htmlspecialchars($order['phone_number']) ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-600"><span class="font-medium">Address:</span> <?= htmlspecialchars($order['address']) ?></p>
          <p class="text-sm text-gray-600"><span class="font-medium">City:</span> <?= htmlspecialchars($order['city']) ?></p>
          <p class="text-sm text-gray-600"><span class="font-medium">Postal Code:</span> <?= htmlspecialchars($order['postal_code']) ?></p>
        </div>
      </div>
    </div>

    <!-- Order Items -->
    <div>
      <h4 class="font-semibold text-gray-800 mb-2">Order Items</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?= htmlspecialchars($item['variant_name']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?= $item['quantity'] ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">₱<?= number_format($item['price'], 2) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment and Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Payment Method</h4>
        <p class="text-sm text-gray-600"><?= ucfirst($order['payment_method']) ?></p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-800 mb-2">Order Status</h4>
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
          <?= $order['order_status'] === 'Delivered' ? 'bg-green-100 text-green-800' : 
             ($order['order_status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
          <?= $order['order_status'] ?>
        </span>
      </div>
    </div>

    <!-- Order Total -->
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="flex justify-between items-center">
        <h4 class="font-semibold text-gray-800">Order Total</h4>
        <p class="text-lg font-bold">₱<?= number_format($order['total_price'], 2) ?></p>
      </div>
    </div>
  </div>

  <div class="mt-6 flex justify-end">
    <button type="button" 
            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-200"
            onclick="document.getElementById('orderDetailsModal').classList.add('hidden')">
      Close
    </button>
  </div>
</div>