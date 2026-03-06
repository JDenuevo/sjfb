<?php
// payment_list.php — improved

// Payment stats
$pStats = [];
$pStatRes = $conn->query("SELECT payment_status, COUNT(*) as cnt, COALESCE(SUM(gross_amount),0) as total FROM payments GROUP BY payment_status");
while ($ps = $pStatRes->fetch_assoc()) {
  $pStats[$ps['payment_status']] = ['count' => (int)$ps['cnt'], 'total' => (float)$ps['total']];
}
$totalRevenue = $pStats['Paid']['total'] ?? 0;
$totalRefunded = $pStats['Refunded']['total'] ?? 0;
$pendingAmt = $pStats['Pending']['total'] ?? 0;

$paymentConf = [
  'Paid'     => ['bg-green-100 text-green-700','bg-green-50 border-green-200'],
  'Pending'  => ['bg-yellow-100 text-yellow-700','bg-yellow-50 border-yellow-200'],
  'Failed'   => ['bg-red-100 text-red-700','bg-red-50 border-red-200'],
  'Refunded' => ['bg-blue-100 text-blue-700','bg-blue-50 border-blue-200'],
];
$methodIcons = ['gcash'=>'📱','paymaya'=>'💳','grab_pay'=>'🟢','qrph'=>'📷','cod'=>'💵','card'=>'💳'];
?>

<!-- Revenue Stats Strip -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
  <div class="bg-green-50 border border-green-100 rounded-xl p-4">
    <div class="text-xs text-green-600 font-medium mb-1">Total Collected</div>
    <div class="text-xl font-bold text-green-700">₱<?= number_format($totalRevenue, 0) ?></div>
    <div class="text-xs text-green-500"><?= $pStats['Paid']['count'] ?? 0 ?> paid</div>
  </div>
  <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4">
    <div class="text-xs text-yellow-600 font-medium mb-1">Pending Amount</div>
    <div class="text-xl font-bold text-yellow-700">₱<?= number_format($pendingAmt, 0) ?></div>
    <div class="text-xs text-yellow-500"><?= $pStats['Pending']['count'] ?? 0 ?> pending</div>
  </div>
  <div class="bg-red-50 border border-red-100 rounded-xl p-4">
    <div class="text-xs text-red-600 font-medium mb-1">Failed</div>
    <div class="text-xl font-bold text-red-700"><?= $pStats['Failed']['count'] ?? 0 ?></div>
    <div class="text-xs text-red-500">transactions failed</div>
  </div>
  <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
    <div class="text-xs text-blue-600 font-medium mb-1">Total Refunded</div>
    <div class="text-xl font-bold text-blue-700">₱<?= number_format($totalRefunded, 0) ?></div>
    <div class="text-xs text-blue-500"><?= $pStats['Refunded']['count'] ?? 0 ?> refunded</div>
  </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
  <!-- Header -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
    <div class="flex-1">
      <h2 class="text-lg font-semibold text-gray-800">Payments</h2>
      <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> total records</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
      <select name="payment_status" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-400">
        <option value="">All Statuses</option>
        <?php foreach (['Paid','Pending','Failed','Refunded'] as $s): ?>
        <option value="<?= $s ?>" <?= ($_GET['payment_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Order, name, provider ID…" class="text-sm px-3 py-2 focus:outline-none w-48">
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
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Method</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Gross</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Net</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="7" class="py-16 text-center text-gray-400 text-sm">No payments found.</td></tr>
        <?php else: while ($row = $result->fetch_assoc()):
          $status = $row['payment_status'] ?? 'Pending';
          [$badge, $card] = $paymentConf[$status] ?? ['bg-gray-100 text-gray-700','bg-gray-50 border-gray-200'];
          $methodDisplay = strtoupper($row['payment_method'] ?? '—');
          $methodIcon = $methodIcons[$row['payment_method'] ?? ''] ?? '💰';
        ?>
        <tr class="payment-row hover:bg-orange-50/30 transition-colors">
          <!-- Order -->
          <td class="px-6 py-3">
            <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" class="text-sm font-bold text-orange-600 hover:text-orange-700"><?= htmlspecialchars($row['order_code']) ?></a>
            <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($row['provider_id'] ? substr($row['provider_id'],0,20).'…' : '—') ?></div>
          </td>
          <!-- Customer -->
          <td class="px-4 py-3">
            <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($row['billing_email'] ?? $row['order_email'] ?? '—') ?></div>
          </td>
          <!-- Method -->
          <td class="px-4 py-3">
            <span class="text-sm"><?= $methodIcon ?></span>
            <span class="text-xs text-gray-600 ml-1"><?= $methodDisplay ?></span>
            <div class="text-xs text-gray-400"><?= $row['mode'] === 'live' ? '🟢 Live' : '🔵 Test' ?></div>
          </td>
          <!-- Status -->
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badge ?>"><?= $status ?></span>
            <?php if ($row['paid_at']): ?>
            <div class="text-xs text-gray-400 mt-0.5"><?= date('M j, g:i A', strtotime($row['paid_at'])) ?></div>
            <?php endif; ?>
          </td>
          <!-- Gross -->
          <td class="px-4 py-3 text-right">
            <span class="text-sm font-bold text-gray-800">₱<?= number_format($row['gross_amount'], 2) ?></span>
          </td>
          <!-- Net -->
          <td class="px-4 py-3 text-right">
            <span class="text-sm text-gray-600">₱<?= number_format($row['net_amount'] ?? $row['gross_amount'], 2) ?></span>
            <?php if ($row['refunded_amount'] > 0): ?>
            <div class="text-xs text-red-500">-₱<?= number_format($row['refunded_amount'], 2) ?> refunded</div>
            <?php endif; ?>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right">
            <button onclick="openPaymentModal(<?= $row['payment_id'] ?>)"
              class="size-8 inline-flex items-center justify-center rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100 transition-colors" title="View details">
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </button>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> payments</p>
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

<!-- Payment Detail Modal (AJAX) -->
<div id="paymentDetailModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">
  <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto p-6 flex flex-col">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800">Payment Details</h3>
      <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <div id="paymentDetailContent" class="p-6">
      <div class="flex items-center justify-center py-8">
        <div class="size-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    </div>
  </div>
</div>

<script>
function openPaymentModal(paymentId) {
  const modal = document.getElementById('paymentDetailModal');
  const content = document.getElementById('paymentDetailContent');
  modal.classList.remove('hidden');
  content.innerHTML = '<div class="flex items-center justify-center py-8"><div class="size-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div></div>';
  
  fetch('./functions/fetch_payments.php?payment_id=' + paymentId)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { content.innerHTML = '<p class="text-red-500 text-sm">Failed to load payment.</p>'; return; }
      const p = data.payment;
      const statusColors = {Paid:'bg-green-100 text-green-700',Pending:'bg-yellow-100 text-yellow-700',Failed:'bg-red-100 text-red-700',Refunded:'bg-blue-100 text-blue-700'};
      const sClass = statusColors[p.payment_status] || 'bg-gray-100 text-gray-700';
      
      content.innerHTML = `
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-lg font-bold text-orange-600">${p.order_code}</div>
              <div class="text-sm text-gray-500">${p.billing_name || (p.first_name + ' ' + p.last_name)}</div>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-semibold ${sClass}">${p.payment_status}</span>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-xl p-3">
              <div class="text-xs text-gray-500 mb-1">Gross Amount</div>
              <div class="text-lg font-bold text-gray-800">₱${parseFloat(p.gross_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <div class="text-xs text-gray-500 mb-1">Net Amount</div>
              <div class="text-lg font-bold text-green-700">₱${parseFloat(p.net_amount||p.gross_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
            </div>
          </div>
          
          ${p.refunded_amount > 0 ? `<div class="bg-red-50 rounded-xl p-3">
            <div class="text-xs text-red-500 mb-1">Refunded Amount</div>
            <div class="text-lg font-bold text-red-700">₱${parseFloat(p.refunded_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
          </div>` : ''}
          
          <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Provider ID</span>
              <span class="font-mono text-xs text-gray-700 max-w-[200px] truncate">${p.provider_id || '—'}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Payment Method</span>
              <span class="font-medium text-gray-800">${p.payment_method || '—'}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Mode</span>
              <span class="font-medium text-gray-800">${p.mode === 'live' ? '🟢 Live' : '🔵 Test'}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Currency</span>
              <span class="font-medium text-gray-800">${p.currency || 'PHP'}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Billing Email</span>
              <span class="font-medium text-gray-800 text-xs">${p.billing_email || p.order_email || '—'}</span>
            </div>
            ${p.paid_at ? `<div class="flex justify-between py-1.5 border-b border-gray-100">
              <span class="text-gray-500">Paid At</span>
              <span class="font-medium text-gray-800">${new Date(p.paid_at).toLocaleString('en-PH')}</span>
            </div>` : ''}
            ${p.failed_code ? `<div class="flex justify-between py-1.5">
              <span class="text-gray-500">Failure Code</span>
              <span class="font-mono text-xs text-red-600">${p.failed_code}</span>
            </div>` : ''}
          </div>
          
          <div class="flex gap-2 pt-2">
            <a href="./order_manage.php?order_id=${p.order_id}" class="flex-1 px-4 py-2 text-sm text-center bg-orange-500 text-white rounded-lg hover:bg-orange-600">View Order</a>
            <button onclick="closePaymentModal()" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Close</button>
          </div>
        </div>
      `;
    }).catch(() => { content.innerHTML = '<p class="text-red-500 text-sm text-center">Failed to load.</p>'; });
}
function closePaymentModal() { document.getElementById('paymentDetailModal').classList.add('hidden'); }
document.getElementById('paymentDetailModal')?.addEventListener('click', function(e) {
  if (e.target === this) closePaymentModal();
});
</script>