<?php
/**
 * order_summary.php — Admin order detail component
 *
 * Variables expected from order_manage.php:
 *   $order, $order_items, $available_riders,
 *   $order_timeline   — rows from order_status_history WHERE order_id = $order_id
 *   $activity_log     — rows from activity_log WHERE entity_type='order' AND entity_id=$order_id
 */

$statusLabels = [
    'Pending'        => 'Pending',
    'Processing'     => 'Processing',
    'OutForDelivery' => 'Out for Delivery',
    'Delivered'      => 'Delivered',
    'Cancelled'      => 'Cancelled',
];
$statusConf = [
    'Pending'        => 'bg-yellow-100 text-yellow-800',
    'Processing'     => 'bg-blue-100   text-blue-800',
    'OutForDelivery' => 'bg-purple-100 text-purple-800',
    'Delivered'      => 'bg-green-100  text-green-800',
    'Cancelled'      => 'bg-red-100    text-red-800',
];
$paymentConf = [
    'Paid'     => 'bg-green-100  text-green-700',
    'Pending'  => 'bg-yellow-100 text-yellow-700',
    'Failed'   => 'bg-red-100    text-red-700',
    'Refunded' => 'bg-blue-100   text-blue-700',
];
$methodLabels = [
    'gcash'     => 'GCash',
    'paymaya'   => 'PayMaya',
    'grab_pay'  => 'GrabPay',
    'qrph'      => 'QR Ph',
    'cod'       => 'Cash on Delivery',
    'card'      => 'Visa/Mastercard',
];

$osBadge       = $statusConf[$order['order_status']]         ?? 'bg-gray-100 text-gray-700';
$psClass       = $paymentConf[$order['payment_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700';
$methodDisplay = $methodLabels[$order['payment_method'] ?? ''] ?? ucfirst($order['payment_method'] ?? '—');

// Progress timeline steps
$steps = [
    ['key' => 'Pending',        'label' => 'Order Placed',     'icon' => '🛒'],
    ['key' => 'Processing',     'label' => 'Processing',       'icon' => '⚙️'],
    ['key' => 'OutForDelivery', 'label' => 'Out for Delivery', 'icon' => '🛵'],
    ['key' => 'Delivered',      'label' => 'Delivered',        'icon' => '✅'],
];
$stepOrder   = ['Pending'=>0,'Processing'=>1,'OutForDelivery'=>2,'Delivered'=>3,'Cancelled'=>-1];
$currentStep = $stepOrder[$order['order_status']] ?? 0;

// ── Merge & sort timeline + activity log chronologically ──────────────────────
// Build a unified feed: each entry gets a normalised 'type', 'label', 'sub',
// 'actor', and 'ts' so the template stays clean.
$feed = [];

foreach ($order_timeline ?? [] as $t) {
    $oldLabel = $statusLabels[$t['old_status']] ?? $t['old_status'];
    $newLabel = $statusLabels[$t['new_status']] ?? $t['new_status'];
    $actor    = !empty($t['first_name']) ? trim($t['first_name'].' '.$t['last_name']) : ucfirst($t['changed_by_user_type'] ?? 'system');
    $feed[]   = [
        'type'  => 'status',
        'label' => "Status changed: {$oldLabel} → {$newLabel}",
        'sub'   => $t['notes'] ?? '',
        'actor' => $actor,
        'ts'    => $t['created_at'],
    ];
}

foreach ($activity_log ?? [] as $l) {
    $actor  = !empty($l['actor_name']) ? trim($l['actor_name']) : ucfirst($l['user_type'] ?? 'system');
    $feed[] = [
        'type'  => 'activity',
        'label' => $l['action'],
        'sub'   => $l['details'] ?? '',
        'actor' => $actor,
        'ts'    => $l['created_at'],
    ];
}

// Sort oldest-first so the timeline reads top-to-bottom chronologically
usort($feed, fn($a, $b) => strtotime($a['ts']) <=> strtotime($b['ts']));
?>

<!-- ── Back + date ─────────────────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-2">
    <a href="orders.php" class="flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Back to Orders
    </a>
    <span class="text-xs text-gray-400"><?= date('F j, Y · g:i A', strtotime($order['order_date'])) ?></span>
</div>

<!-- ── Order header banner ────────────────────────────────────────────────────── -->
<div class="relative overflow-hidden bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-5 text-white shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 relative z-10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-orange-600"><?= htmlspecialchars($order['order_code']) ?></h1>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $osBadge ?>">
                    <?= $statusLabels[$order['order_status']] ?? $order['order_status'] ?>
                </span>
            </div>
            <p class="text-gray-400 text-sm mt-1">
                <?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?> · <?= htmlspecialchars($order['email']) ?>
            </p>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-green-600">₱<?= number_format($order['total_price'], 2) ?></div>
            <div class="text-sm text-gray-400"><?= $methodDisplay ?></div>
        </div>
    </div>
    <div class="absolute -top-4 -right-4 size-24 bg-white/5 rounded-full pointer-events-none"></div>
</div>

<!-- ── Progress stepper ── -->
<style>
/* ── stepper ────────────────────────────────────────────── */
.os-stepper-wrap {
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 1rem;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

/* thin top bar */
.os-progress-track {
  width: 100%;
  height: 6px;
  background: #f3f4f6;
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.os-progress-fill {
  height: 100%;
  border-radius: 9999px;
  background: linear-gradient(90deg, #f97316, #fbbf24);
  transition: width .7s cubic-bezier(.4,0,.2,1);
}

/* row that holds bubbles + connectors */
.os-steps {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

/* connector lines — sit at vertical centre of the bubbles */
.os-connector-bg,
.os-connector-fill {
  position: absolute;
  top: 20px;          /* half of 40px bubble */
  left: 20px;
  right: 20px;
  height: 2px;
  pointer-events: none;
}
.os-connector-bg   { background: #e5e7eb; z-index: 0; }
.os-connector-fill {
  background: linear-gradient(90deg, #f97316, #fbbf24);
  z-index: 1;
  transition: width .7s cubic-bezier(.4,0,.2,1);
  /* width set inline by PHP */
}

/* individual step column */
.os-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  z-index: 10;
  flex: 1;
}

/* bubble */
.os-bubble {
  width: 40px;
  height: 40px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 1;
  transition: background .3s, box-shadow .3s, border-color .3s;
  flex-shrink: 0;
}
.os-bubble.done {
  background: #f97316;
  box-shadow: 0 4px 12px rgba(249,115,22,.35);
  border: 2px solid #f97316;
  color: #fff;
}
.os-bubble.active {
  background: #fff;
  border: 2px solid #f97316;
}
.os-bubble.idle {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
}

/* dot inside inactive bubbles */
.os-dot {
  width: 10px;
  height: 10px;
  border-radius: 9999px;
  display: inline-block;
}
.os-dot.active { background: #fdba74; }
.os-dot.idle   { background: #d1d5db; }

/* label under bubble */
.os-step-label {
  font-size: 11px;
  text-align: center;
  line-height: 1.3;
  max-width: 64px;
  transition: color .3s;
}
.os-step-label.done   { color: #ea580c; font-weight: 600; }
.os-step-label.active { color: #f97316; font-weight: 500; }
.os-step-label.idle   { color: #9ca3af; }

/* label + % row */
.os-bar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.os-bar-header span:first-child { font-size: 12px; font-weight: 600; color: #6b7280; }
.os-bar-header span:last-child  { font-size: 12px; font-weight: 700; color: #f97316; }

/* status message strip */
.os-status-strip {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid #f3f4f6;
}
.os-status-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #fff7ed;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}
.os-status-strip p:first-child { font-size: 14px; font-weight: 600; color: #1f2937; margin: 0; }
.os-status-strip p:last-child  { font-size: 12px; color: #6b7280; margin: 2px 0 0; }

/* cancelled banner */
.os-cancelled {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 1rem;
  padding: 1.25rem;
  text-align: center;
}
.os-cancelled-icon {
  width: 40px; height: 40px;
  background: #fee2e2;
  border-radius: 9999px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 8px;
}
.os-cancelled p { font-size: 14px; font-weight: 600; color: #b91c1c; margin: 0; }
</style>

<?php
$statusMessages = [
    'Pending'        => 'Order is awaiting confirmation.',
    'Processing'     => 'Order is being prepared and packed.',
    'OutForDelivery' => 'Rider is on the way to the delivery address.',
    'Delivered'      => 'Order has been successfully delivered.',
];
$totalSteps = count($steps);
$fillPct    = ($totalSteps > 1 && $currentStep > 0)
              ? round(($currentStep / ($totalSteps - 1)) * 100)
              : 0;
// connector width relative to the space between first and last bubble centres
// = fillPct of the connector span (left:20px right:20px)
$connectorStyle = "width: calc({$fillPct}% * (100% - 40px) / 100% + 0px)";
// simpler: just use fillPct directly — the connector left/right offsets centre it
?>

<?php if ($order['order_status'] !== 'Cancelled'): ?>
<div class="os-stepper-wrap">

    <!-- Progress bar -->
    <div class="os-bar-header">
        <span>Delivery Progress</span>
        <span><?= $fillPct ?>% complete</span>
    </div>
    <div class="os-progress-track">
        <div class="os-progress-fill" style="width: <?= $fillPct ?>%"></div>
    </div>

    <!-- Step bubbles -->
    <div class="os-steps">
        <div class="os-connector-bg"></div>
        <div class="os-connector-fill" style="width: <?= $fillPct ?>%"></div>

        <?php foreach ($steps as $i => $step):
            $done   = ($currentStep >= $i);
            $active = ($currentStep === $i);
            $stateClass = $done ? 'done' : ($active ? 'active' : 'idle');
        ?>
        <div class="os-step">
            <div class="os-bubble <?= $stateClass ?>">
                <?php if ($done): ?>
                    <?= $step['icon'] ?>
                <?php else: ?>
                    <span class="os-dot <?= $active ? 'active' : 'idle' ?>"></span>
                <?php endif; ?>
            </div>
            <span class="os-step-label <?= $stateClass ?>"><?= $step['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Status message -->
    <div class="os-status-strip">
        <div class="os-status-icon"><?= $steps[$currentStep]['icon'] ?></div>
        <div>
            <p><?= $statusLabels[$order['order_status']] ?></p>
            <p><?= $statusMessages[$order['order_status']] ?? '' ?></p>
        </div>
    </div>

</div>

<?php else: ?>
<div class="os-cancelled">
    <div class="os-cancelled-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2">
            <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
    </div>
    <p>This order has been cancelled.</p>
</div>
<?php endif; ?>

<!-- ── Main grid ──────────────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left col -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Order Items -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Order Items</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Variant</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($order_items as $item):
                        $lineTotal = $item['quantity'] * $item['price'];
                    ?>
                    <tr class="hover:bg-orange-50/20 transition-colors">
                        <td class="px-5 py-3">
                            <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['product_name'] ?? '—') ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                <?= htmlspecialchars($item['variant_name'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-gray-800">×<?= $item['quantity'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm text-gray-600">₱<?= number_format($item['price'], 2) ?></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-gray-800">₱<?= number_format($lineTotal, 2) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Order Total</td>
                        <td class="px-4 py-3 text-right text-base font-bold text-orange-600">₱<?= number_format($order['total_price'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Delivery Address -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Delivery Address</h3>
            <div class="flex items-start gap-3">
                <div class="size-9 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                    <svg class="size-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="text-sm text-gray-600">
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></p>
                    <p><?= htmlspecialchars($order['address']) ?></p>
                    <p><?= htmlspecialchars($order['city'].', '.$order['postal_code']) ?></p>
                    <?php if (!empty($order['delivery_notes'])): ?>
                        <p class="mt-1 text-orange-600 text-xs italic">"<?= htmlspecialchars($order['delivery_notes']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Update Actions -->
        <?php if (!in_array($order['order_status'], ['Cancelled','Delivered'])): ?>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-1">Update Order Status</h3>
            <p class="text-xs text-gray-400 mb-4">
                Current: <span class="font-semibold text-gray-600"><?= $statusLabels[$order['order_status']] ?></span>
                <?php
                $nextLabel = ['Pending'=>'Processing','Processing'=>'Out for Delivery','OutForDelivery'=>'Delivered'];
                if (isset($nextLabel[$order['order_status']])): ?>
                → <span class="font-semibold text-orange-500"><?= $nextLabel[$order['order_status']] ?></span>
                <?php endif; ?>
            </p>

            <div class="flex flex-wrap gap-2">
                <?php if ($order['order_status'] === 'Pending'): ?>
                <form action="./functions/order_process.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="hidden" name="notes"    value="Order approved by admin">
                    <button type="submit" name="approve_order"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-500 rounded-xl transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
                        Approve &amp; Process
                    </button>
                </form>

                <?php elseif ($order['order_status'] === 'Processing'): ?>
                <form action="./functions/order_process.php" method="POST"
                      onsubmit="
                        var rid = document.getElementById('send_rider_id_<?= $order['order_id'] ?>').value;
                        if (!rid || rid == '0') { alert('Please assign a rider first.'); return false; }
                        return true;
                      ">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="hidden" name="notes"    value="Order dispatched for delivery">
                    <input type="hidden" id="send_rider_id_<?= $order['order_id'] ?>"
                           name="rider_id" value="<?= (int)($order['assigned_rider_id'] ?? 0) ?>">
                    <button type="submit" name="assign_rider"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-500 rounded-xl transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h5l3 3v5h-2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Send Out for Delivery
                    </button>
                </form>

                <?php elseif ($order['order_status'] === 'OutForDelivery'): ?>
                <form action="./functions/order_process.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="hidden" name="notes"    value="Order delivered — marked by admin">
                    <button type="submit" name="mark_delivered"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-500 rounded-xl transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        Mark as Delivered
                    </button>
                </form>
                <?php endif; ?>

                <form action="./functions/order_process.php" method="POST"
                      onsubmit="return confirm('Cancel this order? This cannot be undone.')">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <input type="hidden" name="reason"   value="Order cancelled by admin">
                    <button type="submit" name="cancel_order"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 rounded-xl transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        Cancel Order
                    </button>
                </form>
            </div>

            <?php if ($order['order_status'] === 'Processing' && empty($order['assigned_rider_id'])): ?>
            <p class="mt-3 text-xs text-amber-600 flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                No rider assigned yet. Assign one in the sidebar before dispatching.
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════
             ORDER HISTORY — unified, scoped feed
             Shows ONLY events from order_status_history and activity_log that
             are directly related to this order (no global activity leaking in).
             Sorted oldest-first so the timeline reads naturally top → bottom.
        ═══════════════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Order History</h3>
                    <p class="text-xs text-gray-400 mt-0.5">All status changes &amp; events for <?= htmlspecialchars($order['order_code']) ?></p>
                </div>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full"><?= count($feed) ?> events</span>
            </div>

            <?php if (empty($feed)): ?>
            <div class="px-5 py-8 text-center text-sm text-gray-400">No history recorded yet.</div>
            <?php else: ?>
            <div class="relative px-5 py-4 max-h-[520px] overflow-y-auto">
                <!-- Vertical connector line -->
                <div class="absolute left-[32px] top-4 bottom-4 w-px bg-gray-200 pointer-events-none"></div>

                <div class="space-y-4">
                <?php foreach ($feed as $idx => $entry):
                    $isStatus   = $entry['type'] === 'status';
                    $dotBg      = $isStatus ? 'bg-orange-500' : 'bg-blue-400';
                    $dotIcon    = $isStatus
                        ? '<svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 12h14M12 5l7 7-7 7"/></svg>'
                        : '<svg class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>';
                    $isLast     = $idx === count($feed) - 1;
                ?>
                <div class="flex items-start gap-3 relative">
                    <!-- Dot -->
                    <div class="shrink-0 size-6 rounded-full <?= $dotBg ?> flex items-center justify-center z-10 mt-0.5
                        <?= $isLast ? 'ring-4 ring-orange-100' : '' ?>">
                        <?= $dotIcon ?>
                    </div>

                    <!-- Content card -->
                    <div class="flex-1 min-w-0 <?= $isStatus ? 'bg-orange-50 border border-orange-100' : 'bg-gray-50 border border-gray-100' ?> rounded-xl px-4 py-3">
                        <p class="text-xs font-semibold <?= $isStatus ? 'text-orange-700' : 'text-gray-700' ?>">
                            <?= htmlspecialchars($entry['label']) ?>
                        </p>
                        <?php if (!empty($entry['sub'])): ?>
                        <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($entry['sub']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-[11px] text-gray-400 font-medium"><?= htmlspecialchars($entry['actor']) ?></span>
                            <span class="text-gray-300">·</span>
                            <span class="text-[11px] text-gray-400"><?= date('M j, Y · g:i A', strtotime($entry['ts'])) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /left col -->

    <!-- Right: sidebar -->
    <div class="space-y-5">

        <!-- Payment Info -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Payment</h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $psClass ?>">
                        <?= $order['payment_status'] ?? 'Pending' ?>
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Method</span>
                    <span class="font-medium text-gray-800"><?= $methodDisplay ?></span>
                </div>
                <?php if (!empty($order['paid_at'])): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Paid At</span>
                    <span class="font-medium text-gray-800 text-xs"><?= date('M j, Y g:i A', strtotime($order['paid_at'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['refunded_amount']) && $order['refunded_amount'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Refunded</span>
                    <span class="font-medium text-red-600">₱<?= number_format($order['refunded_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="pt-2 border-t border-gray-100 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700">Total</span>
                    <span class="text-base font-bold text-orange-600">₱<?= number_format($order['total_price'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Rider Assignment -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Rider Assignment</h3>

            <?php if (!empty($order['assigned_rider_id']) && !empty($order['rider_first_name'])): ?>
            <div class="flex items-center gap-3 mb-4 p-3 bg-purple-50 rounded-xl">
                <div class="size-10 rounded-full bg-purple-200 flex items-center justify-center text-sm font-bold text-purple-700">
                    <?= strtoupper(substr($order['rider_first_name'],0,1).substr($order['rider_last_name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">
                        <?= htmlspecialchars($order['rider_first_name'].' '.$order['rider_last_name']) ?>
                    </div>
                    <div class="text-xs text-gray-500"><?= ucfirst($order['vehicle_type'] ?? '') ?> · <?= $order['vehicle_plate_number'] ?? '' ?></div>
                    <div class="text-xs text-gray-400"><?= htmlspecialchars($order['rider_phone'] ?? '') ?></div>
                </div>
            </div>
            <?php else: ?>
            <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3 flex items-center gap-1.5">
                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                No rider assigned yet.
            </p>
            <?php endif; ?>

            <?php if (!in_array($order['order_status'], ['Delivered','Cancelled'])): ?>
            <form action="./functions/order_process.php" method="POST"
                  onsubmit="
                    if (!this.rider_id.value) { alert('Please select a rider.'); return false; }
                    var sb = document.getElementById('send_rider_id_<?= $order['order_id'] ?>');
                    if (sb) sb.value = this.rider_id.value;
                    return true;
                  ">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <input type="hidden" name="notes"    value="Rider assigned via admin panel">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                    <?= !empty($order['assigned_rider_id']) ? 'Re-assign Rider' : 'Assign Rider' ?>
                </label>
                <select name="rider_id"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 mb-3"
                    onchange="var sb=document.getElementById('send_rider_id_<?= $order['order_id'] ?>'); if(sb) sb.value=this.value;">
                    <option value="">— Select rider —</option>
                    <?php foreach ($available_riders as $r): ?>
                    <option value="<?= $r['rider_id'] ?>"
                        <?= (isset($order['assigned_rider_id']) && $order['assigned_rider_id'] == $r['rider_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?>
                        (<?= $r['active_orders'] ?? 0 ?> active · <?= ucfirst($r['vehicle_type'] ?? '') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_rider"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <?= !empty($order['assigned_rider_id']) ? 'Re-assign Rider' : 'Assign &amp; Dispatch' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Customer</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Name</span>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email</span>
                    <span class="text-xs text-gray-700"><?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Phone</span>
                    <span class="text-gray-700"><?= htmlspecialchars($order['phone_number']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Type</span>
                    <span class="text-xs font-medium <?= !empty($order['is_guest_order']) ? 'text-gray-500' : 'text-blue-600' ?>">
                        <?= !empty($order['is_guest_order']) ? 'Guest Order' : 'Member Order' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Quick Actions</h3>
            <div class="space-y-2">
                <form action="./functions/export_waybill.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
                        <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Generate Waybill
                    </button>
                </form>
                <a href="orders.php" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="size-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    Back to All Orders
                </a>
            </div>
        </div>

    </div><!-- /sidebar -->
</div><!-- /main grid -->