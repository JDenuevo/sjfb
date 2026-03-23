<style>
  /* ════ TOAST (scoped here so the component is self-contained) ════ */
  #toast-wrap {
    position:fixed; bottom:5.5rem; right:1.25rem;
    display:flex; flex-direction:column; align-items:flex-end; gap:.5rem;
    z-index:9999; pointer-events:none;
  }
  @media(min-width:640px){ #toast-wrap { right:1.5rem; } }

  .toast {
    pointer-events:auto;
    display:flex; align-items:flex-start; gap:.75rem;
    min-width:230px; max-width:340px;
    padding:.8rem 1rem;
    border-radius:.875rem; border-left:4px solid currentColor;
    background:#fff;
    box-shadow:0 8px 28px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
    position:relative; overflow:hidden;
    animation:tIn .28s cubic-bezier(.34,1.4,.64,1) both;
  }
  .toast::after {
    content:''; position:absolute; bottom:0; left:0;
    height:2px; width:100%; background:currentColor; opacity:.2;
    transform-origin:left; animation:tBar 4.5s linear forwards;
  }
  @keyframes tIn  { from{opacity:0;transform:translateX(24px) scale(.96)} to{opacity:1;transform:translateX(0) scale(1)} }
  @keyframes tOut { to{opacity:0;transform:translateX(24px) scale(.94);max-height:0;padding:0;margin:0} }
  @keyframes tBar { from{transform:scaleX(1)} to{transform:scaleX(0)} }

  .toast.t-success { color:#16a34a; }
  .toast.t-error   { color:#dc2626; }
  .toast.t-info    { color:#ea580c; }
  .toast.t-warning { color:#d97706; }

  .toast-icon  { font-size:1rem; flex-shrink:0; margin-top:.05rem; line-height:1; }
  .toast-body  { flex:1; min-width:0; }
  .toast-title { font-size:.8125rem; font-weight:700; color:#111827; line-height:1.3; }
  .toast-msg   { font-size:.75rem; color:#6b7280; margin-top:.15rem; line-height:1.4; }
  .toast-close {
    background:none; border:none; padding:0; color:#9ca3af;
    cursor:pointer; font-size:.875rem; flex-shrink:0; line-height:1;
    transition:color .1s;
  }
  .toast-close:hover { color:#111827; }
  .toast.leaving { animation:tOut .22s ease forwards; }

  .order-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }
  .order-row:hover {
    border-left-color: #f97316;
    background: rgba(255,247,237,.2);
  }
</style>

<!-- ════ TOAST CONTAINER ════ -->
<div id="toast-wrap"></div>

<!-- Stats strip -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
  <?php foreach ($statusConf as $status => $cfg): ?>
  <a href="?status=<?= $status ?>"
     class="<?= $cfg['card'] ?> border rounded-xl p-3 text-center hover:shadow-sm transition-shadow <?= ($_GET['status'] ?? '') === $status ? 'ring-2 ring-orange-400 ring-offset-1' : '' ?>">
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
      <select name="status" onchange="this.form.submit()"
              class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-400">
        <option value="">All Statuses</option>
        <?php foreach ($statusLabels as $v => $l): ?>
        <option value="<?= $v ?>" <?= ($_GET['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
               placeholder="Order code, name…"
               class="ps-3 pe-3 py-2 text-sm focus:outline-none w-44">
        <button type="submit" class="px-3 py-2 text-orange-500 hover:bg-orange-50">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>
      </div>
      <?php if (!empty($_GET['status']) || !empty($_GET['search'])): ?>
      <a href="?" class="text-sm text-gray-400 hover:text-orange-500 py-2 px-1">✕ Clear</a>
      <?php endif; ?>
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
          $sBadge  = $statusConf[$row['order_status']]['badge'] ?? 'bg-gray-100 text-gray-700';
          $psClass = $paymentConf[$row['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
          $mLabel  = $methodLabels[$row['payment_method'] ?? ''] ?? ucfirst($row['payment_method'] ?? '—');
        ?>
        <tr class="order-row group cursor-pointer" onclick="toggleOrderExpand(<?= $row['order_id'] ?>)">
          <!-- Order -->
          <td class="px-6 py-3">
            <a href="order_manage.php?order_id=<?= $row['order_id'] ?>" onclick="event.stopPropagation()"
               class="text-sm font-bold text-orange-600 hover:text-orange-700"><?= htmlspecialchars($row['order_code']) ?></a>
            <div class="text-xs text-gray-400"><?= date('M j, Y · g:i A', strtotime($row['order_date'])) ?></div>
          </td>
          <!-- Customer -->
          <td class="px-4 py-3">
            <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($row['recipient_first_name'].' '.$row['recipient_last_name']) ?></div>
            <div class="text-xs text-gray-400"><?= $row['is_guest_order'] ? 'Guest' : 'Member' ?></div>
          </td>
          <!-- Payment -->
          <td class="px-4 py-3">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $psClass ?>"><?= $row['payment_status'] ?? 'Pending' ?></span>
            <div class="text-xs text-gray-400 mt-0.5"><?= $mLabel ?></div>
          </td>
          <!-- Status -->
          <td class="px-4 py-3">
            <span class="order-status-badge px-2 py-1 rounded-full text-xs font-semibold <?= $sBadge ?>">
              <?= $statusLabels[$row['order_status']] ?? $row['order_status'] ?>
            </span>
          </td>
          <!-- Total -->
          <td class="px-4 py-3 text-right">
            <span class="text-sm font-bold text-gray-800">₱<?= number_format($row['total_price'], 2) ?></span>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
            <div class="inline-flex gap-1">
              <button onclick="toggleOrderExpand(<?= $row['order_id'] ?>)"
                      class="size-8 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-colors"
                      title="Quick view">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <a href="order_manage.php?order_id=<?= $row['order_id'] ?>"
                 class="size-8 flex items-center justify-center rounded-lg bg-orange-50 text-orange-500 hover:bg-orange-100 transition-colors"
                 title="Manage">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              </a>
              <?php if ($row['order_status'] === 'Pending'): ?>
              <button id="approve-ol-<?= $row['order_id'] ?>"
                      onclick="quickApproveOL(<?= $row['order_id'] ?>, this)"
                      class="size-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                      title="Approve">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>

        <!-- Expanded order items row -->
        <tr id="orderExpand<?= $row['order_id'] ?>" class="hidden bg-orange-50/20">
          <td colspan="6" class="px-6 py-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
              <div class="space-y-1">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Customer Details</p>
                <p class="text-xs text-gray-600"><span class="font-medium">Email:</span> <?= htmlspecialchars($row['recipient_email']) ?></p>
                <p class="text-xs text-gray-600"><span class="font-medium">Address:</span> <?= htmlspecialchars($row['recipient_address'].', '.$row['city']) ?></p>
              </div>
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
                  <a href="order_manage.php?order_id=<?= $row['order_id'] ?>"
                     class="px-3 py-1.5 text-xs bg-orange-500 text-white hover:bg-orange-600 rounded-lg transition-colors">
                    Manage Order →
                  </a>
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
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>"
         class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
      <?php endif; ?>
      <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"
         class="px-3 py-1.5 text-xs border rounded-lg <?= $i==$page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>">
        <?= $i ?>
      </a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>"
         class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
/* ── Toast ─────────────────────────────────────────────────────────────── */
var _TOAST_META = {
  success: { icon:'\u2713', title:'Success', cls:'t-success' },
  error:   { icon:'\u2715', title:'Error',   cls:'t-error'   },
  info:    { icon:'\u2139', title:'Notice',  cls:'t-info'    },
  warning: { icon:'\u26a0', title:'Warning', cls:'t-warning' },
};
function showToast(msg, type, title) {
  type  = type  || 'info';
  var m  = _TOAST_META[type] || _TOAST_META.info;
  title  = title || m.title;
  var wrap = document.getElementById('toast-wrap');
  if (!wrap) return;
  var t = document.createElement('div');
  t.className = 'toast ' + m.cls;
  t.innerHTML =
    '<span class="toast-icon">' + m.icon + '</span>' +
    '<div class="toast-body">' +
      '<p class="toast-title">' + _escT(title) + '</p>' +
      '<p class="toast-msg">'   + msg           + '</p>' +
    '</div>' +
    '<button class="toast-close" aria-label="Dismiss">\u2715</button>';
  t.querySelector('.toast-close').addEventListener('click', function(){ _dismissToast(t); });
  wrap.appendChild(t);
  t._timer = setTimeout(function(){ _dismissToast(t); }, 4500);
}
function _dismissToast(el) {
  if (!el || el._gone) return; el._gone = true;
  clearTimeout(el._timer);
  el.classList.add('leaving');
  el.addEventListener('animationend', function(){ el.remove(); }, { once:true });
}
function _escT(v) {
  return v == null ? '' : String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
/* legacy alias */
function toast(msg, type) { showToast(msg, type); }

/* ── Expand ─────────────────────────────────────────────────────────────── */
function toggleOrderExpand(orderId) {
  var row    = document.getElementById('orderExpand' + orderId);
  var isHide = row.classList.contains('hidden');
  document.querySelectorAll('[id^="orderExpand"]').forEach(function(r){ r.classList.add('hidden'); });
  if (!isHide) return;

  row.classList.remove('hidden');
  var container = document.getElementById('orderItems' + orderId);
  if (!container || !container.innerHTML.includes('Loading')) return;

  fetch('./functions/fetch_orders.php?order_id=' + orderId)
    .then(function(r){ return r.json(); })
    .then(function(items) {
      if (!items.length) { container.innerHTML = '<div class="text-xs text-gray-400">No items.</div>'; return; }
      container.innerHTML = items.map(function(item) {
        var total = (item.quantity * parseFloat(item.price)).toFixed(2);
        return '<div class="flex justify-between text-xs py-1 border-b border-gray-100 last:border-0">' +
          '<span class="text-gray-700 font-medium">' + item.product_name +
          ' <span class="text-gray-400">(' + item.variant_name + ')</span></span>' +
          '<span class="text-gray-600">\u00d7' + item.quantity + ' &nbsp; \u20b1' + total + '</span>' +
          '</div>';
      }).join('');
    })
    .catch(function(){ container.innerHTML = '<div class="text-xs text-red-400">Failed to load.</div>'; });
}

/* ── Quick approve (order_list variant) ─────────────────────────────────── */
function quickApproveOL(orderId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>';

  var fd = new FormData();
  fd.append('action', 'approve_order');
  fd.append('order_id', orderId);

  fetch('./functions/order_process.php', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (data.ok) {
        showToast('Order approved and moved to Processing.', 'success');
        var badge = document.querySelector('#orderExpand' + orderId + ' .order-status-badge') ||
                    document.querySelector('[onclick*="' + orderId + '"] .order-status-badge');
        // Update the badge on the same row
        document.querySelectorAll('.order-status-badge').forEach(function(el) {
          var tr = el.closest('tr');
          if (tr && tr.id && tr.id.includes(orderId)) {
            el.className = 'order-status-badge px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800';
            el.textContent = 'Processing';
          }
        });
        btn.remove();
      } else {
        if (data.shortfalls && data.shortfalls.length) {
          var lines = data.shortfalls.map(function(s){
            return '<li>' + s.product_name + ' (' + s.variant_name + '): need ' + s.requested + ', only ' + s.available + ' available</li>';
          }).join('');
          showToast('Cannot approve \u2014 stock insufficient:<ul class="mt-1 list-disc list-inside text-xs">' + lines + '</ul>', 'error');
        } else {
          showToast(data.msg, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
      }
    })
    .catch(function(){
      showToast('Network error. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
    });
}

document.querySelectorAll('[data-modal-target]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var el = document.getElementById(this.getAttribute('data-modal-target'));
    if (el) el.classList.remove('hidden');
  });
});
function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add('hidden');
}
</script>