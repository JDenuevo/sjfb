<!-- Stats strip -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
  <?php foreach ($statusConf as $status => [$badge, $card]): ?>
  <a href="?status=<?= $status ?>" class="<?= $card ?> border rounded-xl p-3 text-center hover:shadow-sm transition-shadow">
    <div class="text-xl font-bold text-gray-800"><?= $oStats[$status] ?? 0 ?></div>
    <div class="text-xs text-gray-500 mt-0.5"><?= $statusLabels[$status] ?></div>
  </a>
  <?php endforeach; ?>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

  <!-- Header: title + filters -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
    <div class="flex-1">
      <h2 class="text-lg font-semibold text-gray-800">All Orders</h2>
      <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> total orders</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
      <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-400">
        <option value="">All Statuses</option>
        <?php foreach ($statusLabels as $v => $l): ?>
        <option value="<?= $v ?>" <?= ($_GET['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Order code, name…" class="ps-9 pe-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
        <button type="submit" class="px-3 py-2 text-orange-500 hover:bg-orange-50">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="6" class="py-16 text-center text-gray-400 text-sm">No orders found.</td></tr>
        <?php else: while ($row = $result->fetch_assoc()):
          [$badge, $card] = $statusConf[$row['order_status']] ?? ['bg-gray-100 text-gray-700','bg-gray-50 border-gray-100'];
          $psClass = $paymentConf[$row['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
          $mLabel  = $methodLabels[$row['payment_method'] ?? ''] ?? ucfirst($row['payment_method'] ?? '—');
        ?>
        <tr class="order-row group hover:bg-orange-50/30 transition-colors cursor-pointer" onclick="toggleOrderExpand(<?= $row['order_id'] ?>)">
          <!-- Order -->
          <td class="px-6 py-3">
            <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" onclick="event.stopPropagation()" class="text-sm font-bold text-orange-600 hover:text-orange-700"><?= htmlspecialchars($row['order_code']) ?></a>
            <div class="text-xs text-gray-400"><?= date('M j, Y · g:i A', strtotime($row['order_date'])) ?></div>
          </td>
          <!-- Customer -->
          <td class="px-4 py-3">
            <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></div>
            <div class="text-xs text-gray-400"><?= $row['is_guest_order'] ? 'Guest' : 'Member' ?></div>
          </td>
          <!-- Payment -->
          <td class="px-4 py-3">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $psClass ?>"><?= $row['payment_status'] ?? 'Pending' ?></span>
            <div class="text-xs text-gray-400 mt-0.5"><?= $mLabel ?></div>
          </td>
          <!-- Status -->
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badge ?>"><?= $statusLabels[$row['order_status']] ?? $row['order_status'] ?></span>
          </td>
          <!-- Total -->
          <td class="px-4 py-3 text-right">
            <span class="text-sm font-bold text-gray-800">₱<?= number_format($row['total_price'], 2) ?></span>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
            <div class="inline-flex gap-1">
              <!-- Quick expand toggle -->
              <button onclick="toggleOrderExpand(<?= $row['order_id'] ?>)"
                class="size-8 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-colors" title="Quick view">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <a href="order_manage.php?order_id=<?= $row['order_id'] ?>"
                class="size-8 flex items-center justify-center rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-100 transition-colors" title="Manage">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              </a>
              <?php if ($row['order_status'] === 'Pending'): ?>
              <form action="./functions/order_process.php" method="POST" onsubmit="event.stopPropagation()">
                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                <button type="submit" name="approve_order" title="Approve"
                  class="size-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <!-- Expanded order items row -->
        <tr id="orderExpand<?= $row['order_id'] ?>" class="hidden bg-orange-50/20">
          <td colspan="6" class="px-6 py-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
              <!-- Customer Info -->
              <div class="space-y-1">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Customer Details</p>
                <p class="text-xs text-gray-600"><span class="font-medium">Email:</span> <?= htmlspecialchars($row['email']) ?></p>
                <p class="text-xs text-gray-600"><span class="font-medium">Delivery address:</span> <?= htmlspecialchars($row['address'].', '.$row['city']) ?></p>
              </div>
              <!-- Order Items (loaded via JS) -->
              <div class="sm:col-span-2">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Order Items</p>
                <div id="orderItems<?= $row['order_id'] ?>" class="space-y-1">
                  <div class="text-xs text-gray-400 italic">Loading…</div>
                </div>
                <div class="flex gap-2 mt-3">
                  <form action="./functions/export_waybill.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                    <button type="submit" class="px-3 py-1.5 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors">📋 Waybill</button>
                  </form>
                  <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" class="px-3 py-1.5 text-xs bg-orange-500 text-white hover:bg-orange-600 rounded-lg transition-colors">Manage Order →</a>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> orders</p>
    <div class="flex gap-1">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
      <?php endif; ?>
      <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
           class="px-3 py-1.5 text-xs border rounded-lg <?= $i==$page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function toggleOrderExpand(orderId) {
  const row = document.getElementById('orderExpand' + orderId);
  const isHidden = row.classList.contains('hidden');
  // Close all
  document.querySelectorAll('[id^="orderExpand"]').forEach(r => r.classList.add('hidden'));
  if (isHidden) {
    row.classList.remove('hidden');
    // Load items if empty
    const container = document.getElementById('orderItems' + orderId);
    if (container && container.innerHTML.includes('Loading')) {
      fetch('./functions/fetch_orders.php?order_id=' + orderId)
        .then(r => r.json())
        .then(items => {
          if (!items.length) { container.innerHTML = '<div class="text-xs text-gray-400">No items.</div>'; return; }
          container.innerHTML = items.map(item => {
            const price = parseFloat(item.price);
            const total = item.quantity * price;
            return `<div class="flex justify-between text-xs py-1 border-b border-gray-100 last:border-0">
              <span class="text-gray-700 font-medium">${item.product_name} <span class="text-gray-400">(${item.variant_name})</span></span>
              <span class="text-gray-600">×${item.quantity} &nbsp; ₱${total.toFixed(2)}</span>
            </div>`;
          }).join('');
        }).catch(() => { container.innerHTML = '<div class="text-xs text-red-400">Failed to load.</div>'; });
    }
  }
}

document.querySelectorAll('[data-modal-target]').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById(this.getAttribute('data-modal-target'))?.classList.remove('hidden');
  });
});
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }
</script>

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