<?php
// payment_list.php - redesigned with products.php design language

// Payment stats
$pStats = [];
$pStatRes = $conn->query("SELECT payment_status, COUNT(*) as cnt, COALESCE(SUM(gross_amount),0) as total FROM payments GROUP BY payment_status");
while ($ps = $pStatRes->fetch_assoc()) {
  $pStats[$ps['payment_status']] = ['count' => (int)$ps['cnt'], 'total' => (float)$ps['total']];
}
$totalRevenue = $pStats['Paid']['total'] ?? 0;
$totalRefunded = $pStats['Refunded']['total'] ?? 0;
$pendingAmt = $pStats['Pending']['total'] ?? 0;

// Status badge configurations
$statusConfig = [
  'Paid'     => ['badge-green', 'bg-green-50 border-green-100', '💰'],
  'Pending'  => ['badge-yellow', 'bg-yellow-50 border-yellow-100', '⏳'],
  'Failed'   => ['badge-red', 'bg-red-50 border-red-100', '❌'],
  'Refunded' => ['badge-blue', 'bg-blue-50 border-blue-100', '↩️'],
];

$methodIcons = [
  'gcash' => '📱', 'paymaya' => '💳', 'grab_pay' => '🟢', 
  'qrph' => '📷', 'cod' => '💵', 'card' => '💳'
];
?>

<!-- Stats Cards - redesigned to match products.php style -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <!-- Total Collected -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Total Collected</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">₱<?= number_format($totalRevenue, 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $pStats['Paid']['count'] ?? 0 ?> transactions</p>
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl">💰</div>
    </div>
  </div>
  
  <!-- Pending Amount -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Pending Amount</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">₱<?= number_format($pendingAmt, 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $pStats['Pending']['count'] ?? 0 ?> pending</p>
      </div>
      <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 text-xl">⏳</div>
    </div>
  </div>
  
  <!-- Failed -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Failed</p>
        <p class="text-2xl font-bold text-red-600 mt-1"><?= $pStats['Failed']['count'] ?? 0 ?></p>
        <p class="text-xs text-gray-400 mt-1">transactions failed</p>
      </div>
      <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 text-xl">❌</div>
    </div>
  </div>
  
  <!-- Total Refunded -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 font-medium">Total Refunded</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">₱<?= number_format($totalRefunded, 0) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $pStats['Refunded']['count'] ?? 0 ?> refunded</p>
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl">↩️</div>
    </div>
  </div>
</div>

<!-- Main Card -->
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
  
  <!-- Header with filters -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
    <div>
      <h2 class="text-xl font-bold text-gray-900">Payments</h2>
      <p class="text-sm text-gray-500 mt-0.5">
        <span class="font-semibold text-gray-800"><?= $totalItems ?></span> total records
      </p>
    </div>
    
    <form method="GET" class="flex flex-wrap gap-2">
      <select name="payment_status" onchange="this.form.submit()" 
              class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-48">
        <option value="">All Statuses</option>
        <?php foreach (['Paid','Pending','Failed','Refunded'] as $s): ?>
        <option value="<?= $s ?>" <?= ($_GET['payment_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      
      <div class="relative">
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
               placeholder="Search order, customer..." 
               class="ps-9 pe-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
        <svg class="absolute ms-3 left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
      </div>
      
      <?php if (!empty($_GET['payment_status']) || !empty($_GET['search'])): ?>
      <a href="?" class="px-3 py-2 text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear
      </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Order</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Method</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Gross</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Net</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        <?php if ($result->num_rows === 0): ?>
        <tr>
          <td colspan="7" class="px-6 py-16 text-center">
            <div class="flex flex-col items-center gap-3">
              <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path d="M3 10H7M5 14L5 18M19 6V18C19 19.1046 18.1046 20 17 20H7C5.89543 20 5 19.1046 5 18V6C5 4.89543 5.89543 4 7 4H17C18.1046 4 19 4.89543 19 6Z"/>
                </svg>
              </div>
              <p class="text-sm font-semibold text-gray-700">No payments found</p>
              <p class="text-xs text-gray-400">Try adjusting your filters</p>
            </div>
          </td>
        </tr>
        <?php else: while ($row = $result->fetch_assoc()):
          $status = $row['payment_status'] ?? 'Pending';
          [$badgeClass, $cardBg, $statusIcon] = $statusConfig[$status] ?? ['badge-gray', 'bg-gray-50 border-gray-200', '❓'];
          $methodDisplay = strtoupper($row['payment_method'] ?? '—');
          $methodIcon = $methodIcons[$row['payment_method'] ?? ''] ?? '💰';
        ?>
        <tr class="payment-row hover:bg-orange-50/40 transition-colors">
          <!-- Order -->
          <td class="px-6 py-4">
            <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" 
               class="text-sm font-semibold text-orange-600 hover:text-orange-700 hover:underline">
              <?= htmlspecialchars($row['order_code']) ?>
            </a>
            <div class="text-xs text-gray-400 font-mono mt-0.5">
              <?= htmlspecialchars($row['provider_id'] ? substr($row['provider_id'],0,20).'…' : '—') ?>
            </div>
          </td>
          
          <!-- Customer -->
          <td class="px-4 py-4">
            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['recipient_first_name'].' '.$row['recipient_last_name']) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($row['billing_email'] ?? '—') ?></div>
          </td>
          
          <!-- Method -->
          <td class="px-4 py-4">
            <div class="flex items-center gap-1.5">
              <span class="text-base"><?= $methodIcon ?></span>
              <span class="text-xs font-medium text-gray-700"><?= $methodDisplay ?></span>
            </div>
            <div class="text-xs text-gray-400 mt-0.5"><?= $row['mode'] === 'live' ? '🟢 Live' : '🔵 Test' ?></div>
          </td>
          
          <!-- Status -->
          <td class="px-4 py-4">
            <span class="badge <?= $badgeClass ?> flex items-center gap-1 w-fit">
              <?= $statusIcon ?> <?= $status ?>
            </span>
            <?php if ($row['paid_at']): ?>
            <div class="text-xs text-gray-400 mt-1"><?= date('M j, g:i A', strtotime($row['paid_at'])) ?></div>
            <?php endif; ?>
          </td>
          
          <!-- Gross -->
          <td class="px-4 py-4 text-right">
            <span class="text-sm font-bold text-gray-900">₱<?= number_format($row['gross_amount'], 2) ?></span>
          </td>
          
          <!-- Net -->
          <td class="px-4 py-4 text-right">
            <span class="text-sm text-gray-600">₱<?= number_format($row['net_amount'] ?? $row['gross_amount'], 2) ?></span>
            <?php if ($row['refunded_amount'] > 0): ?>
            <div class="text-xs text-red-500 mt-0.5">-₱<?= number_format($row['refunded_amount'], 2) ?></div>
            <?php endif; ?>
          </td>
          
          <!-- Actions -->
          <td class="px-4 py-4 text-right">
            <button onclick="openPaymentModal(<?= $row['payment_id'] ?>)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View details">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
              </svg>
            </button>
          </td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination (matching products.php style) -->
  <?php if ($totalPages > 1): ?>
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
    <p class="text-sm text-gray-500">
      Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $itemsPerPage, $totalItems) ?></span> 
      of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> payments
    </p>
    
    <div class="flex items-center gap-1.5">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" 
           class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
        </a>
      <?php else: ?>
        <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
        </span>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end = min($totalPages, $page + 2);
      
      if ($start > 1) {
        echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
      }
      if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
      
      for ($i = $start; $i <= $end; $i++):
      ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
           class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
           <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
          <?= $i ?>
        </a>
      <?php
      endfor;
      
      if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
      if ($end < $totalPages) {
        echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">'.$totalPages.'</a>';
      }
      ?>

      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" 
           class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      <?php else: ?>
        <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>