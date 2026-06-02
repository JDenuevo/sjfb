<?php
/**
 * supadmin/discounts.php
 * Tabs: Vouchers | Promotions | Free Shipping Rules
 */
session_start();
include '../conn.php';
require_once '../supadmin/functions/order_helper.php';

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php'); exit;
}

$vouchers     = $conn->query("SELECT * FROM vouchers ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$promotions   = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$freeShipping = $conn->query("SELECT * FROM free_shipping_rules ORDER BY priority DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$groups       = $conn->query("SELECT * FROM customer_groups WHERE is_active = 1 ORDER BY priority DESC")->fetch_all(MYSQLI_ASSOC);

$promoGroups = [];
$pgRes = $conn->query("SELECT promotion_id, group_id FROM promotion_groups");
if ($pgRes) while ($r = $pgRes->fetch_assoc()) {
    $promoGroups[(int)$r['promotion_id']][] = (int)$r['group_id'];
}

function fmtDate(?string $ts, string $fmt = 'M j, Y'): string {
    if (!$ts) return '—';
    return date($fmt, strtotime($ts));
}
function dateStatus(?string $start, ?string $end): string {
    if (!$start || !$end) return 'unknown';
    $now = time();
    if (strtotime($start) > $now) return 'upcoming';
    if (strtotime($end)   < $now) return 'expired';
    return 'active';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discounts | SJFBI Admin</title>
  <link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body{font-family:'Lexend',sans-serif}
    .tab-nav{display:flex;gap:.375rem;border-bottom:2px solid #f3f4f6;margin-bottom:1.25rem}
    .tab-btn{padding:.625rem 1.125rem;font-size:.875rem;font-weight:600;color:#6b7280;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.15s;font-family:inherit}
    .tab-btn.active{color:#ea580c;border-bottom-color:#ea580c}
    .tab-btn:hover:not(.active){color:#374151}
    .tab-panel{display:none}.tab-panel.active{display:block}
    .modal-overlay{position:fixed;inset:0;z-index:999;display:flex;align-items:flex-start;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);overflow-y:auto;padding:2rem 1rem}
    .modal-overlay.hidden{display:none}
    .modal-box{background:white;width:100%;max-width:42rem;border-radius:1.25rem;box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden}
    .modal-box-sm{max-width:30rem}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#fafafa}
    .modal-header h3{font-size:1rem;font-weight:700;color:#111827}
    .modal-header p{font-size:.75rem;color:#6b7280;margin-top:1px}
    .modal-close{width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f3f4f6;color:#6b7280;border:none;cursor:pointer;transition:.15s}
    .modal-close:hover{background:#fee2e2;color:#dc2626}
    .modal-body{padding:1.5rem;max-height:80vh;overflow-y:auto}
    .modal-footer{padding:1rem 1.5rem;border-top:1px solid #f3f4f6;background:#fafafa;display:flex;justify-content:flex-end;gap:.625rem}
    .form-label{display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.375rem}
    .form-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.5rem;font-size:.875rem;color:#111827;outline:none;transition:.15s;font-family:inherit}
    .form-input:focus{border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,.1)}
    .btn{padding:.5rem 1.125rem;border-radius:.625rem;border:none;font-size:.875rem;font-weight:600;cursor:pointer;transition:.15s;font-family:inherit;display:inline-flex;align-items:center;gap:.375rem}
    .btn-orange{background:#ea580c;color:white}.btn-orange:hover{background:#c2410c}
    .btn-red{background:#dc2626;color:white}.btn-red:hover{background:#b91c1c}
    .btn-outline{background:white;color:#374151;border:1px solid #e5e7eb}.btn-outline:hover{background:#f9fafb}
    .btn-sm{padding:.3rem .75rem;font-size:.75rem;border-radius:.5rem}
    .btn-xs{padding:.2rem .5rem;font-size:.7rem;border-radius:.4rem}
    .toggle{position:relative;display:inline-block;width:36px;height:20px;flex-shrink:0}
    .toggle input{opacity:0;width:0;height:0}
    .toggle-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:9999px;transition:.2s}
    .toggle-slider::before{content:'';position:absolute;width:14px;height:14px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s}
    .toggle input:checked+.toggle-slider{background:#16a34a}
    .toggle input:checked+.toggle-slider::before{transform:translateX(16px)}
    .g2{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
    @media(max-width:640px){.g2{grid-template-columns:1fr}}
    .badge{display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:9999px;font-size:.7rem;font-weight:700}
    .badge-green{background:#dcfce7;color:#15803d}
    .badge-yellow{background:#fef9c3;color:#a16207}
    .badge-gray{background:#f3f4f6;color:#6b7280}
    .badge-blue{background:#dbeafe;color:#1d4ed8}
    .badge-orange{background:#fff7ed;color:#ea580c}
    .disc-card{background:white;border:1px solid #f3f4f6;border-radius:1rem;padding:1.25rem;transition:.2s}
    .disc-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
    .empty-state{text-align:center;padding:3rem 1rem;color:#9ca3af}
    .empty-state .icon{font-size:2.5rem;margin-bottom:.75rem}
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<div id="toast-wrap" class="fixed bottom-5 right-5 flex flex-col gap-2 z-[9999]"></div>

<!-- VOUCHER MODAL -->
<div id="voucherModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div><h3 id="voucherModalTitle">Add Voucher</h3><p>Create a customer discount code</p></div>
      <button class="modal-close" onclick="closeModal('voucherModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form action="./functions/discount_process.php" method="POST" id="voucherForm" class="space-y-4">
        <input type="hidden" name="action" id="voucherAction" value="add_voucher">
        <input type="hidden" name="voucher_id" id="voucherIdField">
        <div class="g2">
          <div><label class="form-label">Code *</label><input type="text" name="code" id="voucherCode" required class="form-input" placeholder="e.g. SAVE20" style="text-transform:uppercase"></div>
          <div><label class="form-label">Discount Type *</label>
            <select name="discount_type" id="voucherType" class="form-input" onchange="toggleMaxDiscount('voucher')">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed Amount (&#8369;)</option>
            </select>
          </div>
        </div>
        <div class="g2">
          <div><label class="form-label">Discount Value *</label><input type="number" name="discount_value" id="voucherValue" required min="0.01" step="0.01" class="form-input" placeholder="e.g. 10"></div>
          <div id="voucherMaxWrap"><label class="form-label">Max Discount (&#8369;) <span style="font-weight:400;color:#9ca3af">optional</span></label><input type="number" name="max_discount" id="voucherMaxDiscount" min="0" step="0.01" class="form-input" placeholder="Cap amount"></div>
        </div>
        <div class="g2">
          <div><label class="form-label">Minimum Order (&#8369;)</label><input type="number" name="minimum_order" id="voucherMinOrder" min="0" step="0.01" value="0" class="form-input"></div>
          <div><label class="form-label">Applicable Groups</label>
            <select name="applicable_groups" id="voucherGroups" class="form-input">
              <option value="all">All Customers</option>
              <option value="vip_only">VIP Only</option>
              <option value="employee_only">Employee Only</option>
              <option value="subscriber_only">Subscriber Only</option>
            </select>
          </div>
        </div>
        <div class="g2">
          <div><label class="form-label">Start Date *</label><input type="datetime-local" name="start_date" id="voucherStart" required class="form-input"></div>
          <div><label class="form-label">Expiry Date *</label><input type="datetime-local" name="expiry_date" id="voucherExpiry" required class="form-input"></div>
        </div>
        <div class="g2">
          <div><label class="form-label">Usage Limit <span style="font-weight:400;color:#9ca3af">blank=unlimited</span></label><input type="number" name="usage_limit" id="voucherUsageLimit" min="1" class="form-input" placeholder="e.g. 100"></div>
          <div><label class="form-label">Per User Limit</label><input type="number" name="per_user_limit" id="voucherPerUser" min="1" value="1" class="form-input"></div>
        </div>
        <div><label class="form-label">Description <span style="font-weight:400;color:#9ca3af">optional</span></label><input type="text" name="description" id="voucherDesc" class="form-input" placeholder="Brief note for customers"></div>
        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
          <p class="text-xs font-semibold text-gray-500 uppercase">Settings</p>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Active</p><p class="text-xs text-gray-400">Voucher can be redeemed</p></div><label class="toggle"><input type="checkbox" name="is_active" id="voucherIsActive" value="1" checked><span class="toggle-slider"></span></label></div>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Public</p><p class="text-xs text-gray-400">Show in voucher lists</p></div><label class="toggle"><input type="checkbox" name="toggle_public" id="voucherPublic" value="1" checked><span class="toggle-slider"></span></label></div>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Stackable</p><p class="text-xs text-gray-400">Combine with other discounts</p></div><label class="toggle"><input type="checkbox" name="toggle_stackable" id="voucherStackable" value="1"><span class="toggle-slider"></span></label></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('voucherModal')" class="btn btn-outline">Cancel</button>
      <button type="submit" form="voucherForm" class="btn btn-orange">Save Voucher</button>
    </div>
  </div>
</div>

<!-- USAGE STATS MODAL -->
<div id="usageModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width: 800px;">
    <div class="modal-header">
      <div><h3 id="usageModalTitle">Usage Statistics</h3><p id="usageSubtitle">View discount usage history</p></div>
      <button class="modal-close" onclick="closeModal('usageModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <div id="usageStatsContent" class="space-y-4">
        <div class="flex justify-center py-8">Loading...</div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('usageModal')" class="btn btn-outline">Close</button>
    </div>
  </div>
</div>

<!-- PROMOTION MODAL -->
<div id="promoModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div><h3 id="promoModalTitle">Add Promotion</h3><p>Auto-apply discount promotion</p></div>
      <button class="modal-close" onclick="closeModal('promoModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form action="./functions/discount_process.php" method="POST" id="promoForm" class="space-y-4">
        <input type="hidden" name="action" id="promoAction" value="add_promotion">
        <input type="hidden" name="promotion_id" id="promoIdField">
        <div><label class="form-label">Promotion Name *</label><input type="text" name="promotion_name" id="promoName" required class="form-input" placeholder="e.g. Summer Sale 20%"></div>
        <div class="g2">
          <div><label class="form-label">Discount Type *</label>
            <select name="discount_type" id="promoType" class="form-input" onchange="toggleMaxDiscount('promo')">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed Amount (&#8369;)</option>
            </select>
          </div>
          <div><label class="form-label">Discount Value *</label><input type="number" name="discount_value" id="promoValue" required min="0.01" step="0.01" class="form-input"></div>
        </div>
        <div class="g2">
          <div><label class="form-label">Minimum Order (&#8369;)</label><input type="number" name="minimum_order" id="promoMinOrder" min="0" step="0.01" value="0" class="form-input"></div>
          <div id="promoMaxWrap"><label class="form-label">Max Discount (&#8369;) <span style="font-weight:400;color:#9ca3af">optional</span></label><input type="number" name="max_discount" id="promoMaxDiscount" min="0" step="0.01" class="form-input"></div>
        </div>
        <div class="g2">
          <div><label class="form-label">Applicable To</label>
            <select name="applicable_to" id="promoApplicableTo" class="form-input" onchange="toggleGroupSelect()">
              <option value="all">All Customers</option>
              <option value="specific_groups">Specific Groups</option>
              <option value="specific_products">Specific Products</option>
              <option value="specific_categories">Specific Categories</option>
            </select>
          </div>
          <div><label class="form-label">Per Customer Limit</label><input type="number" name="per_customer_limit" id="promoPerCustomer" min="1" value="1" class="form-input"></div>
        </div>
        <div id="promoGroupSelect" class="hidden">
          <label class="form-label">Select Groups</label>
          <div class="bg-gray-50 rounded-xl p-3 space-y-2">
            <?php foreach ($groups as $g): ?>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="group_ids[]" value="<?= $g['group_id'] ?>" class="rounded promo-group-cb">
              <span class="text-sm text-gray-700"><?= htmlspecialchars($g['group_name']) ?></span>
              <?php if ($g['discount_percentage'] > 0): ?><span class="text-xs text-orange-600 font-semibold"><?= $g['discount_percentage'] ?>% base</span><?php endif; ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="g2">
          <div><label class="form-label">Start Date *</label><input type="datetime-local" name="start_date" id="promoStart" required class="form-input"></div>
          <div><label class="form-label">End Date *</label><input type="datetime-local" name="end_date" id="promoEnd" required class="form-input"></div>
        </div>
        <div><label class="form-label">Usage Limit <span style="font-weight:400;color:#9ca3af">blank=unlimited</span></label><input type="number" name="usage_limit" id="promoUsageLimit" min="1" class="form-input" placeholder="Total uses" style="max-width:200px"></div>
        <div><label class="form-label">Description <span style="font-weight:400;color:#9ca3af">optional</span></label><input type="text" name="description" id="promoDesc" class="form-input"></div>
        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
          <p class="text-xs font-semibold text-gray-500 uppercase">Settings</p>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Active</p></div><label class="toggle"><input type="checkbox" name="is_active" id="promoIsActive" value="1" checked><span class="toggle-slider"></span></label></div>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Auto-Apply</p><p class="text-xs text-gray-400">Apply automatically to eligible carts</p></div><label class="toggle"><input type="checkbox" name="toggle_auto_apply" id="promoAutoApply" value="1" checked><span class="toggle-slider"></span></label></div>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Public</p><p class="text-xs text-gray-400">Show on website</p></div><label class="toggle"><input type="checkbox" name="toggle_public" id="promoPublic" value="1" checked><span class="toggle-slider"></span></label></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('promoModal')" class="btn btn-outline">Cancel</button>
      <button type="submit" form="promoForm" class="btn btn-orange">Save Promotion</button>
    </div>
  </div>
</div>

<!-- FREE SHIPPING MODAL -->
<div id="fsModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div><h3 id="fsModalTitle">Add Free Shipping Rule</h3><p>Set conditions for free delivery</p></div>
      <button class="modal-close" onclick="closeModal('fsModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form action="./functions/discount_process.php" method="POST" id="fsForm" class="space-y-4">
        <input type="hidden" name="action" id="fsAction" value="add_free_shipping">
        <input type="hidden" name="rule_id" id="fsIdField">
        <div><label class="form-label">Rule Name *</label><input type="text" name="rule_name" id="fsName" required class="form-input" placeholder="e.g. Free shipping over &#8369;1000"></div>
        <div class="g2">
          <div><label class="form-label">Minimum Order (&#8369;) *</label><input type="number" name="minimum_order" id="fsMinOrder" min="0" step="0.01" value="0" class="form-input"></div>
          <div><label class="form-label">Applicable Groups</label>
            <select name="applicable_groups" id="fsGroups" class="form-input">
              <option value="all">All Customers</option>
              <option value="vip_only">VIP Only</option>
              <option value="employee_only">Employee Only</option>
              <option value="subscriber_only">Subscriber Only</option>
            </select>
          </div>
        </div>
        <div><label class="form-label">Applicable Cities <span style="font-weight:400;color:#9ca3af">blank = all cities</span></label><input type="text" name="applicable_cities" id="fsCities" class="form-input" placeholder='["Navotas","Malabon"] or leave blank'></div>
        <div class="g2">
          <div><label class="form-label">Start Date *</label><input type="datetime-local" name="start_date" id="fsStart" required class="form-input"></div>
          <div><label class="form-label">End Date *</label><input type="datetime-local" name="end_date" id="fsEnd" required class="form-input"></div>
        </div>
        <div><label class="form-label">Priority <span style="font-weight:400;color:#9ca3af">higher = applied first</span></label><input type="number" name="priority" id="fsPriority" min="0" value="0" class="form-input" style="max-width:120px"></div>
        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
          <p class="text-xs font-semibold text-gray-500 uppercase">Settings</p>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Active</p></div><label class="toggle"><input type="checkbox" name="is_active" id="fsIsActive" value="1" checked><span class="toggle-slider"></span></label></div>
          <div class="flex items-center justify-between"><div><p class="text-sm font-medium text-gray-700">Auto-Apply</p><p class="text-xs text-gray-400">Apply automatically to eligible carts</p></div><label class="toggle"><input type="checkbox" name="toggle_auto_apply" id="fsAutoApply" value="1" checked><span class="toggle-slider"></span></label></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('fsModal')" class="btn btn-outline">Cancel</button>
      <button type="submit" form="fsForm" class="btn btn-orange">Save Rule</button>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3>Confirm Delete</h3><p>This cannot be undone</p></div>
      <button class="modal-close" onclick="closeModal('deleteModal')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body text-center">
      <div class="size-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-3"><svg class="size-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></div>
      <p id="deleteMsg" class="text-sm text-gray-700 mb-4"></p>
      <form action="./functions/discount_process.php" method="POST">
        <input type="hidden" name="action" id="deleteAction">
        <input type="hidden" name="item_id"  id="deleteItemId">
        <div class="flex gap-3 justify-center">
          <button type="button" onclick="closeModal('deleteModal')" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-red">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="w-full lg:ps-64">
<div class="p-4 sm:p-6 space-y-6">

  <?php if (!empty($_SESSION['message'])): $msg=$_SESSION['message']; unset($_SESSION['message']); $cls=$msg['type']==='success'?'bg-teal-500':'bg-red-500'; ?>
  <div class="<?=$cls?> text-white text-sm rounded-xl p-4 flex items-center gap-2 shadow-sm">
    <span class="font-bold"><?=ucfirst($msg['type'])?>!</span> <?=htmlspecialchars($msg['text'])?>
  </div>
  <?php endif; ?>

  <div>
    <h1 class="text-2xl font-bold text-gray-900">Discounts</h1>
    <p class="text-sm text-gray-500 mt-0.5">Manage vouchers, promotions, and free shipping rules.</p>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-3 gap-3">
    <?php
    $av = count(array_filter($vouchers,    fn($v)=>$v['is_active']));
    $ap = count(array_filter($promotions,  fn($p)=>$p['is_active']));
    $af = count(array_filter($freeShipping,fn($f)=>$f['is_active']));
    foreach([
      ['🎟','Vouchers',   count($vouchers),   $av],
      ['🏷','Promotions', count($promotions),  $ap],
      ['🚚','Free Ship.', count($freeShipping),$af],
    ] as [$icon,$label,$total,$active]):
    ?>
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm text-center">
      <div class="text-xl mb-1"><?=$icon?></div>
      <div class="text-xl font-bold text-gray-900"><?=$total?></div>
      <div class="text-xs text-gray-500 mt-0.5"><?=$label?></div>
      <div class="text-xs text-green-600 font-semibold mt-0.5"><?=$active?> active</div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 pt-5">
      <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('vouchers',this)">🎟 Vouchers (<?=count($vouchers)?>)</button>
        <button class="tab-btn" onclick="switchTab('promotions',this)">🏷 Promotions (<?=count($promotions)?>)</button>
        <button class="tab-btn" onclick="switchTab('freeship',this)">🚚 Free Shipping (<?=count($freeShipping)?>)</button>
      </div>
    </div>
    <div class="px-6 pb-6">

      <!-- VOUCHERS -->
      <div id="tab-vouchers" class="tab-panel active">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500"><?=count($vouchers)?> voucher<?=count($vouchers)!==1?'s':''?></p>
          <button onclick="openAddVoucher()" class="btn btn-orange btn-sm"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Add Voucher</button>
        </div>
        <?php if(empty($vouchers)): ?><div class="empty-state"><div class="icon">🎟</div><p class="text-sm font-semibold text-gray-600">No vouchers yet</p></div>
        <?php else: ?><div class="space-y-3">
        <?php foreach($vouchers as $v):
          $st=dateStatus($v['start_date'],$v['expiry_date']);
          $sb=match($st){'active'=>'badge-green','upcoming'=>'badge-yellow',default=>'badge-gray'};
          $sl=match($st){'active'=>'Active','upcoming'=>'Upcoming',default=>'Expired'};
        ?>
        <div class="disc-card">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-orange-600 text-sm"><?=htmlspecialchars($v['code'])?></span>
                <span class="badge <?=$sb?>"><?=$sl?></span>
                <?php if(!$v['is_active']):?><span class="badge badge-gray">Inactive</span><?php endif;?>
                <?php if($v['toggle_stackable']):?><span class="badge badge-blue">Stackable</span><?php endif;?>
                <?php if(!$v['toggle_public']):?><span class="badge badge-gray">Hidden</span><?php endif;?>
              </div>
              <p class="text-sm font-semibold text-gray-800 mt-1">
                <?=$v['discount_type']==='percentage'?number_format($v['discount_value'],0).'% off':'&#8369;'.number_format($v['discount_value'],2).' off'?>
                <?php if($v['minimum_order']>0):?><span class="text-xs text-gray-400 font-normal">· min &#8369;<?=number_format($v['minimum_order'],0)?></span><?php endif;?>
                <?php if($v['max_discount']):?><span class="text-xs text-gray-400 font-normal">· cap &#8369;<?=number_format($v['max_discount'],0)?></span><?php endif;?>
              </p>
              <?php if($v['description']):?><p class="text-xs text-gray-400 mt-0.5"><?=htmlspecialchars($v['description'])?></p><?php endif;?>
              <div class="flex gap-3 mt-2 flex-wrap text-xs text-gray-500">
                <span>📅 <?=fmtDate($v['start_date'])?> — <?=fmtDate($v['expiry_date'])?></span>
                <?php if($v['usage_limit']):?><span>Limit: <?=$v['usage_limit']?></span><?php endif;?>
                <span class="capitalize"><?=str_replace('_',' ',$v['applicable_groups'])?></span>
              </div>
            </div>
            
             <!-- In the voucher card actions section -->
            <div class="flex gap-2 items-center shrink-0">
              <label class="toggle"><input type="checkbox" <?=$v['is_active']?'checked':''?> onchange="toggleField('voucher',<?=$v['voucher_id']?>,'is_active',this.checked?1:0)"><span class="toggle-slider"></span></label>
              <button onclick="viewUsage('voucher', <?= $v['voucher_id'] ?>, '<?= addslashes($v['code']) ?>')" class="btn btn-outline btn-xs">Usage</button>
              <button onclick='editVoucher(<?=json_encode($v)?>)' class="btn btn-outline btn-xs">Edit</button>
              <button onclick="confirmDelete('delete_voucher',<?=$v['voucher_id']?>,'<?=addslashes($v['code'])?>')" class="btn btn-xs text-red-500 border border-red-200 hover:bg-red-50">Del</button>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 flex gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">👁 Public <label class="toggle" style="width:28px;height:16px"><input type="checkbox" <?=$v['toggle_public']?'checked':''?> onchange="toggleField('voucher',<?=$v['voucher_id']?>,'toggle_public',this.checked?1:0)"><span class="toggle-slider"></span></label></span>
            <span class="flex items-center gap-1.5">➕ Stackable <label class="toggle" style="width:28px;height:16px"><input type="checkbox" <?=$v['toggle_stackable']?'checked':''?> onchange="toggleField('voucher',<?=$v['voucher_id']?>,'toggle_stackable',this.checked?1:0)"><span class="toggle-slider"></span></label></span>
          </div>
          
        </div>
        <?php endforeach;?></div><?php endif;?>
      </div>

      <!-- PROMOTIONS -->
      <div id="tab-promotions" class="tab-panel">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500"><?=count($promotions)?> promotion<?=count($promotions)!==1?'s':''?></p>
          <button onclick="openAddPromo()" class="btn btn-orange btn-sm"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Add Promotion</button>
        </div>
        <?php if(empty($promotions)):?><div class="empty-state"><div class="icon">🏷</div><p class="text-sm font-semibold text-gray-600">No promotions yet</p></div>
        <?php else:?><div class="space-y-3">
        <?php foreach($promotions as $p):
          $st=dateStatus($p['start_date'],$p['end_date']);
          $sb=match($st){'active'=>'badge-green','upcoming'=>'badge-yellow',default=>'badge-gray'};
          $sl=match($st){'active'=>'Active','upcoming'=>'Upcoming',default=>'Expired'};
          $linkedGroups=$promoGroups[$p['promotion_id']]??[];
        ?>
        <div class="disc-card">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($p['promotion_name'])?></span>
                <span class="badge <?=$sb?>"><?=$sl?></span>
                <?php if(!$p['is_active']):?><span class="badge badge-gray">Inactive</span><?php endif;?>
                <?php if($p['toggle_auto_apply']):?><span class="badge badge-orange">Auto</span><?php endif;?>
                <?php if(!$p['toggle_public']):?><span class="badge badge-gray">Hidden</span><?php endif;?>
              </div>
              <p class="text-sm font-semibold text-gray-800 mt-1">
                <?=$p['discount_type']==='percentage'?number_format($p['discount_value'],0).'% off':'&#8369;'.number_format($p['discount_value'],2).' off'?>
                <?php if($p['minimum_order']>0):?><span class="text-xs text-gray-400 font-normal">· min &#8369;<?=number_format($p['minimum_order'],0)?></span><?php endif;?>
                <?php if($p['max_discount']):?><span class="text-xs text-gray-400 font-normal">· cap &#8369;<?=number_format($p['max_discount'],0)?></span><?php endif;?>
              </p>
              <?php if($p['description']):?><p class="text-xs text-gray-400 mt-0.5"><?=htmlspecialchars($p['description'])?></p><?php endif;?>
              <div class="flex gap-3 mt-2 flex-wrap text-xs text-gray-500">
                <span>📅 <?=fmtDate($p['start_date'])?> — <?=fmtDate($p['end_date'])?></span>
                <span class="capitalize"><?=str_replace('_',' ',$p['applicable_to'])?></span>
                <?php if(!empty($linkedGroups)):
                  $gmap=array_column($groups,'group_name','group_id');
                  $gNames=array_map(fn($gid)=>$gmap[$gid]??"#$gid",$linkedGroups);
                ?><span class="text-blue-600"><?=implode(', ',$gNames)?></span><?php endif;?>
              </div>
            </div>
            <div class="flex gap-2 items-center shrink-0">
              <label class="toggle"><input type="checkbox" <?=$p['is_active']?'checked':''?> onchange="toggleField('promotion',<?=$p['promotion_id']?>,'is_active',this.checked?1:0)"><span class="toggle-slider"></span></label>
              <button onclick='editPromo(<?=json_encode($p)?>,<?=json_encode($linkedGroups)?>)' class="btn btn-outline btn-xs">Edit</button>
              <button onclick="confirmDelete('delete_promotion',<?=$p['promotion_id']?>,'<?=addslashes($p['promotion_name'])?>')" class="btn btn-xs text-red-500 border border-red-200 hover:bg-red-50">Del</button>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 flex gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">⚡ Auto-apply <label class="toggle" style="width:28px;height:16px"><input type="checkbox" <?=$p['toggle_auto_apply']?'checked':''?> onchange="toggleField('promotion',<?=$p['promotion_id']?>,'toggle_auto_apply',this.checked?1:0)"><span class="toggle-slider"></span></label></span>
            <span class="flex items-center gap-1.5">👁 Public <label class="toggle" style="width:28px;height:16px"><input type="checkbox" <?=$p['toggle_public']?'checked':''?> onchange="toggleField('promotion',<?=$p['promotion_id']?>,'toggle_public',this.checked?1:0)"><span class="toggle-slider"></span></label></span>
          </div>
        </div>
        <?php endforeach;?></div><?php endif;?>
      </div>

      <!-- FREE SHIPPING -->
      <div id="tab-freeship" class="tab-panel">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500"><?=count($freeShipping)?> rule<?=count($freeShipping)!==1?'s':''?></p>
          <button onclick="openAddFS()" class="btn btn-orange btn-sm"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>Add Rule</button>
        </div>
        <?php if(empty($freeShipping)):?><div class="empty-state"><div class="icon">🚚</div><p class="text-sm font-semibold text-gray-600">No free shipping rules yet</p></div>
        <?php else:?><div class="space-y-3">
        <?php foreach($freeShipping as $fs):
          $st=dateStatus($fs['start_date'],$fs['end_date']);
          $sb=match($st){'active'=>'badge-green','upcoming'=>'badge-yellow',default=>'badge-gray'};
          $sl=match($st){'active'=>'Active','upcoming'=>'Upcoming',default=>'Expired'};
        ?>
        <div class="disc-card">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-800 text-sm"><?=htmlspecialchars($fs['rule_name'])?></span>
                <span class="badge <?=$sb?>"><?=$sl?></span>
                <?php if(!$fs['is_active']):?><span class="badge badge-gray">Inactive</span><?php endif;?>
                <?php if($fs['toggle_auto_apply']):?><span class="badge badge-orange">Auto</span><?php endif;?>
                <?php if($fs['priority']>0):?><span class="badge badge-blue">Priority <?=$fs['priority']?></span><?php endif;?>
              </div>
              <p class="text-sm text-gray-700 mt-1">Free shipping on orders &ge; <strong>&#8369;<?=number_format($fs['minimum_order'],0)?></strong></p>
              <div class="flex gap-3 mt-2 flex-wrap text-xs text-gray-500">
                <span>📅 <?=fmtDate($fs['start_date'])?> — <?=fmtDate($fs['end_date'])?></span>
                <span class="capitalize"><?=str_replace('_',' ',$fs['applicable_groups'])?></span>
                <?php if($fs['applicable_cities']):?><span class="text-blue-600" title="<?=htmlspecialchars($fs['applicable_cities'])?>">Cities filter ↗</span><?php endif;?>
              </div>
            </div>
            <div class="flex gap-2 items-center shrink-0">
              <label class="toggle"><input type="checkbox" <?=$fs['is_active']?'checked':''?> onchange="toggleField('free_shipping',<?=$fs['rule_id']?>,'is_active',this.checked?1:0)"><span class="toggle-slider"></span></label>
              <button onclick='editFS(<?=json_encode($fs)?>)' class="btn btn-outline btn-xs">Edit</button>
              <button onclick="confirmDelete('delete_free_shipping',<?=$fs['rule_id']?>,'<?=addslashes($fs['rule_name'])?>')" class="btn btn-xs text-red-500 border border-red-200 hover:bg-red-50">Del</button>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 flex gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">⚡ Auto-apply <label class="toggle" style="width:28px;height:16px"><input type="checkbox" <?=$fs['toggle_auto_apply']?'checked':''?> onchange="toggleField('free_shipping',<?=$fs['rule_id']?>,'toggle_auto_apply',this.checked?1:0)"><span class="toggle-slider"></span></label></span>
          </div>
        </div>
        <?php endforeach;?></div><?php endif;?>
      </div>

    </div>
  </div>
</div>
</div>

<script>
const PROCESS = './functions/discount_process.php';
function switchTab(id,btn){document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.getElementById('tab-'+id).classList.add('active');btn.classList.add('active');}
function openModal(id){document.getElementById(id).classList.remove('hidden');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.add('hidden');document.body.style.overflow='';}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModal(m.id);}));

function toggleField(type,id,field,value){
  const fd=new FormData();
  fd.append('action','toggle_field');fd.append('type',type);fd.append('id',id);fd.append('field',field);fd.append('value',value);
  fetch(PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{d.success?toast(d.message,'success'):toast('Error: '+d.message,'error');}).catch(()=>toast('Network error','error'));
}

function toLocal(dt){if(!dt)return'';return dt.replace(' ','T').substring(0,16);}
function toggleMaxDiscount(pfx){
  const type=document.getElementById(pfx==='voucher'?'voucherType':'promoType').value;
  const wrap=document.getElementById(pfx==='voucher'?'voucherMaxWrap':'promoMaxWrap');
  if(wrap)wrap.style.display=type==='percentage'?'':'none';
}
function toggleGroupSelect(){
  const v=document.getElementById('promoApplicableTo').value;
  document.getElementById('promoGroupSelect').classList.toggle('hidden',v!=='specific_groups');
}

function openAddVoucher(){
  document.getElementById('voucherModalTitle').textContent='Add Voucher';
  document.getElementById('voucherAction').value='add_voucher';
  document.getElementById('voucherIdField').value='';
  document.getElementById('voucherForm').reset();
  document.getElementById('voucherIsActive').checked=true;
  document.getElementById('voucherPublic').checked=true;
  toggleMaxDiscount('voucher');
  openModal('voucherModal');
}
function editVoucher(v){
  document.getElementById('voucherModalTitle').textContent='Edit Voucher';
  document.getElementById('voucherAction').value='edit_voucher';
  document.getElementById('voucherIdField').value=v.voucher_id;
  document.getElementById('voucherCode').value=v.code;
  document.getElementById('voucherType').value=v.discount_type;
  document.getElementById('voucherValue').value=v.discount_value;
  document.getElementById('voucherMaxDiscount').value=v.max_discount||'';
  document.getElementById('voucherMinOrder').value=v.minimum_order;
  document.getElementById('voucherGroups').value=v.applicable_groups;
  document.getElementById('voucherStart').value=toLocal(v.start_date);
  document.getElementById('voucherExpiry').value=toLocal(v.expiry_date);
  document.getElementById('voucherUsageLimit').value=v.usage_limit||'';
  document.getElementById('voucherPerUser').value=v.per_user_limit;
  document.getElementById('voucherDesc').value=v.description||'';
  document.getElementById('voucherIsActive').checked=!!parseInt(v.is_active);
  document.getElementById('voucherPublic').checked=!!parseInt(v.toggle_public);
  document.getElementById('voucherStackable').checked=!!parseInt(v.toggle_stackable);
  toggleMaxDiscount('voucher');
  openModal('voucherModal');
}

function openAddPromo(){
  document.getElementById('promoModalTitle').textContent='Add Promotion';
  document.getElementById('promoAction').value='add_promotion';
  document.getElementById('promoIdField').value='';
  document.getElementById('promoForm').reset();
  document.getElementById('promoIsActive').checked=true;
  document.getElementById('promoAutoApply').checked=true;
  document.getElementById('promoPublic').checked=true;
  document.querySelectorAll('.promo-group-cb').forEach(cb=>cb.checked=false);
  toggleMaxDiscount('promo');
  toggleGroupSelect();
  openModal('promoModal');
}
function editPromo(p,groupIds){
  document.getElementById('promoModalTitle').textContent='Edit Promotion';
  document.getElementById('promoAction').value='edit_promotion';
  document.getElementById('promoIdField').value=p.promotion_id;
  document.getElementById('promoName').value=p.promotion_name;
  document.getElementById('promoType').value=p.discount_type;
  document.getElementById('promoValue').value=p.discount_value;
  document.getElementById('promoMaxDiscount').value=p.max_discount||'';
  document.getElementById('promoMinOrder').value=p.minimum_order;
  document.getElementById('promoApplicableTo').value=p.applicable_to;
  document.getElementById('promoPerCustomer').value=p.per_customer_limit;
  document.getElementById('promoStart').value=toLocal(p.start_date);
  document.getElementById('promoEnd').value=toLocal(p.end_date);
  document.getElementById('promoUsageLimit').value=p.usage_limit||'';
  document.getElementById('promoDesc').value=p.description||'';
  document.getElementById('promoIsActive').checked=!!parseInt(p.is_active);
  document.getElementById('promoAutoApply').checked=!!parseInt(p.toggle_auto_apply);
  document.getElementById('promoPublic').checked=!!parseInt(p.toggle_public);
  document.querySelectorAll('.promo-group-cb').forEach(cb=>{cb.checked=groupIds.includes(parseInt(cb.value));});
  toggleMaxDiscount('promo');
  toggleGroupSelect();
  openModal('promoModal');
}

function openAddFS(){
  document.getElementById('fsModalTitle').textContent='Add Free Shipping Rule';
  document.getElementById('fsAction').value='add_free_shipping';
  document.getElementById('fsIdField').value='';
  document.getElementById('fsForm').reset();
  document.getElementById('fsIsActive').checked=true;
  document.getElementById('fsAutoApply').checked=true;
  openModal('fsModal');
}
function editFS(fs){
  document.getElementById('fsModalTitle').textContent='Edit Free Shipping Rule';
  document.getElementById('fsAction').value='edit_free_shipping';
  document.getElementById('fsIdField').value=fs.rule_id;
  document.getElementById('fsName').value=fs.rule_name;
  document.getElementById('fsMinOrder').value=fs.minimum_order;
  document.getElementById('fsGroups').value=fs.applicable_groups;
  document.getElementById('fsCities').value=fs.applicable_cities||'';
  document.getElementById('fsStart').value=toLocal(fs.start_date);
  document.getElementById('fsEnd').value=toLocal(fs.end_date);
  document.getElementById('fsPriority').value=fs.priority;
  document.getElementById('fsIsActive').checked=!!parseInt(fs.is_active);
  document.getElementById('fsAutoApply').checked=!!parseInt(fs.toggle_auto_apply);
  openModal('fsModal');
}

function confirmDelete(action,id,name){
  const labels={delete_voucher:'voucher',delete_promotion:'promotion',delete_free_shipping:'free shipping rule'};
  document.getElementById('deleteMsg').innerHTML=`Delete <strong>${esc(name)}</strong>? This ${labels[action]||'item'} will be permanently removed.`;
  document.getElementById('deleteAction').value=action;
  document.getElementById('deleteItemId').value=id;
  openModal('deleteModal');
}

function viewUsage(type, id, name) {
  document.getElementById('usageModalTitle').innerHTML = `Usage: ${name}`;
  document.getElementById('usageSubtitle').innerHTML = `${type === 'voucher' ? 'Voucher' : 'Promotion'} usage history`;
  
  fetch(`./functions/fetch_usage.php?type=${type}&id=${id}`)
    .then(r => r.json())
    .then(data => {
      const content = document.getElementById('usageStatsContent');
      if (!data.success) {
        content.innerHTML = `<div class="text-center text-red-500 py-4">${data.message}</div>`;
        return;
      }
      
      // Safely convert values to numbers
      const totalUses = parseInt(data.total_uses) || 0;
      const totalDiscount = parseFloat(data.total_discount) || 0;
      const remainingUses = data.remaining_uses !== undefined ? parseInt(data.remaining_uses) : undefined;
      
      content.innerHTML = `
        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
          <div class="text-center">
            <div class="text-2xl font-bold text-orange-600">${totalUses}</div>
            <div class="text-xs text-gray-500">Total Uses</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">₱${totalDiscount.toFixed(2)}</div>
            <div class="text-xs text-gray-500">Total Discount Given</div>
          </div>
        </div>
        
        ${remainingUses !== undefined ? `
        <div class="bg-blue-50 p-3 rounded-xl">
          <div class="flex justify-between text-sm">
            <span class="text-blue-700">Remaining Uses:</span>
            <span class="font-bold text-blue-800">${remainingUses}</span>
          </div>
        </div>
        ` : ''}
        
        <div class="border-t pt-3">
          <p class="text-sm font-semibold text-gray-700 mb-3">Recent Usage</p>
          ${!data.recent_uses || data.recent_uses.length === 0 ? '<p class="text-xs text-gray-400">No usage recorded yet.</p>' : `
          <div class="space-y-2 max-h-96 overflow-y-auto">
            ${data.recent_uses.map(use => `
              <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg text-sm">
                <div>
                  <span class="font-medium text-gray-800">Order: ${use.order_code || 'N/A'}</span>
                  <span class="text-xs text-gray-400 ml-2">${use.email || 'Guest'}</span>
                </div>
                <div class="text-right">
                  <span class="text-green-600 font-semibold">-₱${(parseFloat(use.discount_amount) || 0).toFixed(2)}</span>
                  <span class="text-xs text-gray-400 ml-2">${new Date(use.used_at).toLocaleString()}</span>
                </div>
              </div>
            `).join('')}
          </div>
          `}
        </div>
      `;
      
      openModal('usageModal');
    })
    .catch(err => {
      console.error('Error fetching usage stats:', err);
      const content = document.getElementById('usageStatsContent');
      content.innerHTML = `<div class="text-center text-red-500 py-4">Error loading usage statistics. Please try again.</div>`;
      openModal('usageModal');
    });
}

function toast(msg,type='info'){
  const c={success:'bg-teal-600',error:'bg-red-600',info:'bg-gray-800'};
  const el=document.createElement('div');
  el.className=`${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-start gap-2 min-w-56 max-w-sm`;
  el.innerHTML=`<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none">&#x2715;</button>`;
  document.getElementById('toast-wrap').prepend(el);
  setTimeout(()=>el?.remove(),4500);
}
function esc(v){if(v==null)return'';return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

</script>
<?php $conn->close(); ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>