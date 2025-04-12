<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="text-3xl font-bold text-center mb-6">My Orders</h1>
    <p class="mt-1 text-gray-800">
    View your order history
    </p>
  </div>

  <!-- Orders Table -->
  <div class="bg-white shadow rounded-lg overflow-hidden">
  <table class="min-w-full table-auto border-collapse divide-y divide-gray-200">
  <thead class="bg-gray-50">
    <tr>
      <th scope="col" class="px-4 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Order ID</th>
      <th scope="col" class="px-4 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Date</th>
      <th scope="col" class="px-4 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Total</th>
      <th scope="col" class="px-4 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-800">Status</th>
      <th scope="col" class="px-4 py-2 text-end"></th>
    </tr>
  </thead>
  <tbody class="divide-y divide-gray-200">
    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-2 text-sm text-gray-800 font-medium"><?= $row['order_id'] ?></td>
          <td class="px-4 py-2 text-sm text-gray-800"><?= date('F j, Y @ g:i A', strtotime($row['order_date'])) ?></td>
          <td class="px-4 py-2 text-sm text-gray-800">₱<?= $row['total_price'] ?></td>
          <td class="px-4 py-2">
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full 
              <?= $row['order_status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                  ($row['order_status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
              <?= $row['order_status'] ?>
            </span>
          </td>
          <td class="px-4 py-2 text-end">
            <button class="p-1 text-gray-600 hover:text-black" data-modal-target="viewOrderModal<?= $row['order_id'] ?>" onclick="fetchOrderDetails(<?= $row['order_id'] ?>)">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="5" class="px-4 py-4 text-center text-gray-500">
          You haven't placed any orders yet.
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

  </div>
  <div class="text-center p-4">
    <a href="./products.php" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Shop Now</a>
  </div>
</div>

<!-- Order Details Modal (Empty template) -->
<div id="orderDetailsModal" class="p-6 fixed inset-0 z-100 flex items-center justify-center bg-black bg-opacity-50 hidden overflow-y-auto h-auto" style="margin: 0;">
  <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-4xl max-">
    <!-- Content will be loaded dynamically -->
  </div>
</div>

<script>
  // Function to fetch and display order details
  function fetchOrderDetails(orderId) {
    fetch(`./functions/fetch_orders.php?order_id=${orderId}`)
      .then(response => response.text())
      .then(html => {
        const modal = document.getElementById('orderDetailsModal');
        modal.querySelector('.bg-white').innerHTML = html;
        modal.classList.remove('hidden');
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load order details');
      });
  }

  // Close modal when clicking outside content
  document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.add('hidden');
    }
  });

  // Close modal with escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.getElementById('orderDetailsModal').classList.add('hidden');
    }
  });
</script>
