<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$month = date('n');
$year  = date('Y');
$base  = 'exports/';

// ── Pagination + filters ───────────────────────────────────────────────────
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$search     = trim($_GET['search']  ?? '');
$roleFilter = trim($_GET['role']    ?? '');

$where  = ["is_deleted = 0", "role IN ('customer','admin','rider')"];
$params = [];
$types  = '';

if (!empty($search)) {
    $where[] = "(account_first_name LIKE ? OR account_last_name LIKE ? OR account_email LIKE ? OR username LIKE ?)";
    $st = "%{$search}%";
    $params = array_merge($params, [$st, $st, $st, $st]);
    $types  .= 'ssss';
}
if (!empty($roleFilter)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types   .= 's';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Count
$cStmt = $conn->prepare("SELECT COUNT(*) AS total FROM accounts {$whereClause}");
if (!empty($params)) $cStmt->bind_param($types, ...$params);
$cStmt->execute();
$totalItems = (int)$cStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$offset     = ($page - 1) * $perPage;

// Main query
$mStmt = $conn->prepare("SELECT * FROM accounts {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?");
$mStmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
$mStmt->execute();
$result = $mStmt->get_result();

// Role counts
$roleCounts = [];
$rcRes = $conn->query("SELECT role, COUNT(*) AS cnt FROM accounts WHERE is_deleted=0 AND role IN ('customer','admin','rider','super_admin') GROUP BY role");
while ($rc = $rcRes->fetch_assoc()) $roleCounts[$rc['role']] = (int)$rc['cnt'];

// Customer groups for dropdowns
$groupsResult = $conn->query("SELECT * FROM customer_groups WHERE is_active=1 ORDER BY priority DESC, group_name ASC");
$groups = $groupsResult ? $groupsResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accounts | SJFBI Admin</title>
  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  <style>
    body { font-family: 'Lexend', sans-serif; }
    .account-row { transition: all .2s; border-left: 3px solid transparent; }
    .account-row:hover { background: #fafafa; border-left-color: #ea580c; }
    .modal-overlay { position:fixed;inset:0;z-index:999;display:flex;align-items:flex-start;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);overflow-y:auto;padding:2rem 1rem; }
    .modal-overlay.hidden { display:none; }
    .modal-box { background:white;width:100%;max-width:56rem;border-radius:1.25rem;box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden; }
    .modal-box-sm { max-width:32rem; }
    .modal-header { display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#fafafa; }
    .modal-header h3 { font-size:1.125rem;font-weight:700;color:#111827; }
    .modal-header p  { font-size:.75rem;color:#6b7280;margin-top:1px; }
    .modal-close { width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f3f4f6;color:#6b7280;border:none;cursor:pointer;transition:.15s; }
    .modal-close:hover { background:#fee2e2;color:#dc2626; }
    .modal-body { padding:1.5rem;max-height:75vh;overflow-y:auto; }
    .modal-footer { padding:1rem 1.5rem;border-top:1px solid #f3f4f6;background:#fafafa;display:flex;justify-content:flex-end;gap:.625rem; }
    .form-label { display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.375rem; }
    .form-input { width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.5rem;font-size:.875rem;color:#111827;transition:border-color .15s,box-shadow .15s;outline:none;font-family:inherit; }
    .form-input:focus { border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,.1); }
    .section-title { font-size:.9375rem;font-weight:700;color:#111827;border-left:3px solid #ea580c;padding-left:.625rem;margin:1.25rem 0 .75rem; }
    .btn-primary { padding:.5rem 1.25rem;background:#ea580c;color:white;border-radius:.625rem;border:none;font-size:.875rem;font-weight:600;cursor:pointer;transition:.15s;font-family:inherit; }
    .btn-primary:hover { background:#c2410c; }
    .btn-secondary { padding:.5rem 1.25rem;background:white;color:#374151;border-radius:.625rem;border:1px solid #e5e7eb;font-size:.875rem;font-weight:500;cursor:pointer;transition:.15s;font-family:inherit; }
    .btn-secondary:hover { background:#f9fafb; }
    .avatar { width:2.25rem;height:2.25rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:.875rem; }
    .badge { display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600; }
    .group-tag { display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .5rem;border-radius:9999px;font-size:.65rem;font-weight:600;background:#fef3c7;color:#92400e; }
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<div class="w-full lg:ps-64">
<div class="p-4 sm:p-6 space-y-5">

  <?php if (!empty($_SESSION['message'])):
    $msg = $_SESSION['message']; unset($_SESSION['message']);
    $cls = $msg['type'] === 'success' ? 'bg-teal-500' : 'bg-red-500';
  ?>
  <div class="<?= $cls ?> text-white text-sm rounded-xl p-4 flex items-center gap-2">
    <span class="font-bold"><?= ucfirst($msg['type']) ?>!</span> <?= htmlspecialchars($msg['text']) ?>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <?php
    $roleConf = [
      'customer'    => ['Customers',   'bg-blue-100',   'text-blue-600',   '👤'],
      'admin'       => ['Admins',       'bg-orange-100', 'text-orange-600', '⚙️'],
      'rider'       => ['Riders',       'bg-purple-100', 'text-purple-600', '🏍️'],
      'super_admin' => ['Super Admins', 'bg-gray-100',   'text-gray-600',   '👑'],
    ];
    foreach ($roleConf as $role => [$label, $bg, $text, $icon]): ?>
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 font-medium"><?= $label ?></p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?= $roleCounts[$role] ?? 0 ?></p>
        </div>
        <div class="w-12 h-12 <?= $bg ?> rounded-xl flex items-center justify-center <?= $text ?> text-xl"><?= $icon ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Main card -->
  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

    <!-- Header + filters -->
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Accounts</h2>
        <p class="text-sm text-gray-500 mt-0.5"><span class="font-semibold text-gray-800"><?= $totalItems ?></span> total accounts</p>
      </div>
      <form method="GET" class="flex flex-wrap gap-2">
        <select name="role" onchange="this.form.submit()"
                class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400">
          <option value="">All Roles</option>
          <?php foreach (['customer','admin','rider','super_admin'] as $r): ?>
          <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="relative">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Name, email, username..." 
                class="ps-9 pe-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
          <button type="submit" class="absolute left-0 top-0 h-full flex items-center ps-3">
            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/>
              <path d="m21 21-4.35-4.35"/>
            </svg>
          </button>
        </div>
        <?php if (!empty($search) || !empty($roleFilter)): ?>
        <a href="accounts.php" class="px-3 py-2 text-sm text-gray-400 hover:text-orange-500 flex items-center gap-1">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg> Clear
        </a>
        <?php endif; ?>
        <button type="button" onclick="openModal('addAccountModal')"
                class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 transition-all">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          Add Account
        </button>
      </form>
    </div>

    <!-- ═══════════════════════════════════════════
     accounts.php
    ════════════════════════════════════════════ -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin:12px 0;">
        <a href="<?= $base ?>export_customers.php" target="_blank"
          class="btn btn-outline-success btn-sm">
            <i class="ti ti-file-spreadsheet"></i> Export All Customers
        </a>
        <a href="<?= $base ?>export_customers.php?group=vip" target="_blank"
          class="btn btn-outline-warning btn-sm">
            <i class="ti ti-star"></i> VIP Only
        </a>
        <a href="<?= $base ?>export_customers.php?inactive_days=90" target="_blank"
          class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-user-off"></i> Inactive 90d+
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Contact</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Groups</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Location</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Joined</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="7" class="px-6 py-16 text-center text-gray-400 text-sm">No accounts found.</td></tr>
          <?php else: while ($row = $result->fetch_assoc()):
            $initials = strtoupper(substr($row['account_first_name'],0,1).substr($row['account_last_name'],0,1));
            $roleColors = [
              'customer'    => ['bg-blue-100 text-blue-700',   'bg-blue-100',   'text-blue-600'],
              'admin'       => ['bg-orange-100 text-orange-700','bg-orange-100','text-orange-600'],
              'rider'       => ['bg-purple-100 text-purple-700','bg-purple-100','text-purple-600'],
              'super_admin' => ['bg-gray-100 text-gray-700',   'bg-gray-100',   'text-gray-600'],
            ];
            [$badgeCls, $avatarBg, $avatarText] = $roleColors[$row['role']] ?? ['bg-gray-100 text-gray-700','bg-gray-100','text-gray-600'];

            // Fetch groups for this account
            $gStmt = $conn->prepare("
                SELECT cg.group_name, cg.group_code
                FROM account_groups ag
                JOIN customer_groups cg ON cg.group_id = ag.group_id
                WHERE ag.account_id = ? AND (ag.expires_at IS NULL OR ag.expires_at > NOW()) AND cg.is_active = 1
            ");
            $gStmt->bind_param('i', $row['account_id']);
            $gStmt->execute();
            $accountGroups = $gStmt->get_result()->fetch_all(MYSQLI_ASSOC);
          ?>
          <tr class="account-row hover:bg-orange-50/30 transition-colors">
            <!-- User -->
            <td class="px-6 py-3">
              <div class="flex items-center gap-3">
                <div class="avatar <?= $avatarBg ?> <?= $avatarText ?>"><?= $initials ?></div>
                <div>
                  <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['account_first_name'].' '.$row['account_last_name']) ?></p>
                  <p class="text-xs text-gray-400">@<?= htmlspecialchars($row['username'] ?? '—') ?></p>
                </div>
              </div>
            </td>
            <!-- Role -->
            <td class="px-4 py-3">
              <span class="badge <?= $badgeCls ?>"><?= ucfirst(str_replace('_',' ',$row['role'])) ?></span>
            </td>
            <!-- Contact -->
            <td class="px-4 py-3">
              <p class="text-xs text-gray-700"><?= htmlspecialchars($row['account_email']) ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($row['account_phone'] ?? '—') ?></p>
            </td>
            <!-- Groups -->
            <td class="px-4 py-3">
              <?php if (!empty($accountGroups)): ?>
              <div class="flex flex-wrap gap-1">
                <?php foreach ($accountGroups as $g): ?>
                <span class="group-tag">🏷 <?= htmlspecialchars($g['group_name']) ?></span>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <span class="text-xs text-gray-400">—</span>
              <?php endif; ?>
            </td>
            <!-- Location -->
            <td class="px-4 py-3">
              <p class="text-xs text-gray-700"><?= htmlspecialchars($row['city'] ?: '—') ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($row['postal_code'] ?: '') ?></p>
            </td>
            <!-- Joined -->
            <td class="px-4 py-3">
              <p class="text-xs text-gray-600"><?= date('M j, Y', strtotime($row['created_at'])) ?></p>
              <p class="text-xs text-gray-400"><?= date('g:i A', strtotime($row['created_at'])) ?></p>
            </td>
            <!-- Actions -->
            <td class="px-4 py-3 text-right">
              <div class="inline-flex gap-1.5">
                <button onclick="openEditModal(<?= $row['account_id'] ?>)"
                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                </button>
                <button onclick="openGroupModal(<?= $row['account_id'] ?>, '<?= htmlspecialchars(addslashes($row['account_first_name'].' '.$row['account_last_name'])) ?>')"
                        class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Manage Groups">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </button>
                <button onclick="openDeleteModal(<?= $row['account_id'] ?>, '<?= htmlspecialchars(addslashes($row['account_first_name'].' '.$row['account_last_name'])) ?>')"
                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
      <p class="text-sm text-gray-500">
        Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span>
        of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> accounts
      </p>
      <div class="flex gap-1.5">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
           class="px-3 py-1.5 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">← Prev</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
           class="px-3 py-1.5 text-sm border rounded-xl <?= $i === $page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
           class="px-3 py-1.5 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">Next →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
</div>

<!-- ══ ADD ACCOUNT MODAL ══════════════════════════════════════════════════════ -->
<div id="addAccountModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div><h3>Add New Account</h3><p>Create a new user account</p></div>
      <button class="modal-close" onclick="closeModal('addAccountModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <!-- id matches the footer submit button's form attribute -->
      <form action="./functions/add.php" method="POST" id="addAccountForm" class="space-y-4">
        <input type="hidden" name="add_account" value="1">

        <p class="section-title">Login Information</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" required class="form-input" placeholder="johndoe">
          </div>
          <div>
            <label class="form-label">Role <span class="text-red-500">*</span></label>
            <select name="role" required class="form-input">
              <option value="" disabled selected>Select role</option>
              <option value="customer">Customer</option>
              <option value="admin">Admin</option>
              <option value="rider">Rider</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" required class="form-input" placeholder="••••••••">
          </div>
          <div>
            <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
            <input type="password" name="confirm_password" required class="form-input" placeholder="••••••••">
          </div>
        </div>

        <p class="section-title">Personal Information</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <!-- name matches add.php: $_POST['account_first_name'] -->
            <label class="form-label">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="account_first_name" required class="form-input" placeholder="Juan">
          </div>
          <div>
            <label class="form-label">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="account_last_name" required class="form-input" placeholder="dela Cruz">
          </div>
        </div>
        <div>
          <label class="form-label">Email <span class="text-red-500">*</span></label>
          <input type="email" name="account_email" required class="form-input" placeholder="juan@example.com">
        </div>
        <div>
          <label class="form-label">Phone Number</label>
          <input type="text" name="account_phone" class="form-input" placeholder="09XXXXXXXXX" maxlength="11">
        </div>

        <p class="section-title">Address</p>
        <div>
          <label class="form-label">Street Address</label>
          <textarea name="account_address" rows="2" class="form-input resize-none" placeholder="House no., Street, Barangay"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-input" placeholder="Navotas">
          </div>
          <div>
            <label class="form-label">Postal Code</label>
            <input type="text" name="postal_code" class="form-input" placeholder="1485">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" onclick="closeModal('addAccountModal')" class="btn-secondary">Cancel</button>
      <!-- form="addAccountForm" links this button to the form above -->
      <button type="submit" form="addAccountForm" class="btn-primary">Add Account</button>
    </div>
  </div>
</div>

<!-- ══ EDIT ACCOUNT MODAL ════════════════════════════════════════════════════ -->
<div id="editAccountModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div><h3>Edit Account</h3><p>Update account information</p></div>
      <button class="modal-close" onclick="closeModal('editAccountModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="editAccountContent" class="modal-body">
      <div class="flex items-center justify-center py-12 text-gray-400">
        <svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
        Loading…
      </div>
    </div>
  </div>
</div>

<!-- ══ GROUP MANAGEMENT MODAL ════════════════════════════════════════════════ -->
<div id="groupModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3>Manage Groups</h3><p id="groupModalSubtitle">Assign customer tiers</p></div>
      <button class="modal-close" onclick="closeModal('groupModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="groupModalContent" class="modal-body space-y-4">
      <div class="flex items-center justify-center py-8 text-gray-400">
        <svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
        Loading groups…
      </div>
    </div>
  </div>
</div>

<!-- ══ DELETE ACCOUNT MODAL ══════════════════════════════════════════════════ -->
<div id="deleteAccountModal" class="modal-overlay hidden">
  <div class="modal-box modal-box-sm">
    <div class="modal-header">
      <div><h3>Delete Account</h3><p>This cannot be undone</p></div>
      <button class="modal-close" onclick="closeModal('deleteAccountModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body text-center">
      <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="size-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      </div>
      <form action="./functions/delete.php" method="POST">
        <input type="hidden" name="delete_account" value="1">
        <input type="hidden" name="account_id" id="deleteAccountId">
        <p id="deleteAccountName" class="text-sm font-semibold text-gray-800 mb-1"></p>
        <p class="text-xs text-red-500 mb-5">This will permanently delete this account and all associated data.</p>
        <div class="flex gap-3 justify-center">
          <button type="button" onclick="closeModal('deleteAccountModal')" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Available groups passed from PHP
const AVAILABLE_GROUPS = <?= json_encode($groups) ?>;
const UPDATE_URL   = './functions/update.php';
const ADD_GROUP_URL= './functions/add.php';

// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id) {
  document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(m => m.classList.add('hidden'));
  document.getElementById(id).classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

// ── Edit modal ─────────────────────────────────────────────────────────────
function openEditModal(accountId) {
  const content = document.getElementById('editAccountContent');
  content.innerHTML = `<div class="flex items-center justify-center py-12 text-gray-400">
    <svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Loading…</div>`;
  openModal('editAccountModal');

  fetch(`./functions/fetch_accounts.php?account_id=${accountId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { content.innerHTML = `<p class="text-red-500 p-4 text-center">${data.message}</p>`; return; }
      const a = data.account;
      content.innerHTML = `
        <form action="${UPDATE_URL}" method="POST" id="editAccountForm" class="space-y-4">
          <input type="hidden" name="update_account" value="1">
          <input type="hidden" name="account_id" value="${a.account_id}">

          <p class="section-title">Login Information</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Username</label>
              <input type="text" name="username" value="${esc(a.username)}" class="form-input">
            </div>
            <div>
              <label class="form-label">Role</label>
              <select name="role" class="form-input">
                ${['customer','admin','rider'].map(r =>
                  `<option value="${r}" ${a.role===r?'selected':''}>${r.charAt(0).toUpperCase()+r.slice(1)}</option>`
                ).join('')}
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">New Password <span class="text-gray-400 font-normal">(leave blank to keep)</span></label>
              <input type="password" name="password" class="form-input" placeholder="••••••••">
            </div>
            <div>
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-input" placeholder="••••••••">
            </div>
          </div>

          <p class="section-title">Personal Information</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">First Name</label>
              <input type="text" name="account_first_name" value="${esc(a.account_first_name)}" class="form-input">
            </div>
            <div>
              <label class="form-label">Last Name</label>
              <input type="text" name="account_last_name" value="${esc(a.account_last_name)}" class="form-input">
            </div>
          </div>
          <div>
            <label class="form-label">Email</label>
            <input type="email" name="account_email" value="${esc(a.account_email)}" class="form-input">
          </div>
          <div>
            <label class="form-label">Phone Number</label>
            <!-- name="account_phone" matches update.php -->
            <input type="text" name="account_phone" value="${esc(a.account_phone)}" class="form-input" maxlength="11">
          </div>

          <p class="section-title">Address</p>
          <div>
            <label class="form-label">Street Address</label>
            <textarea name="account_address" rows="2" class="form-input resize-none">${esc(a.account_address)}</textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">City</label>
              <input type="text" name="city" value="${esc(a.city)}" class="form-input">
            </div>
            <div>
              <label class="form-label">Postal Code</label>
              <input type="text" name="postal_code" value="${esc(a.postal_code)}" class="form-input">
            </div>
          </div>

          <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
            <button type="button" onclick="closeModal('editAccountModal')" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">Save Changes</button>
          </div>
        </form>
      `;
    })
    .catch(() => { content.innerHTML = '<p class="text-red-500 p-4 text-center">Network error. Please try again.</p>'; });
}

// ── Group management modal ─────────────────────────────────────────────────
let _groupAccountId = null;

function openGroupModal(accountId, accountName) {
  _groupAccountId = accountId;
  document.getElementById('groupModalSubtitle').textContent = accountName;
  const content = document.getElementById('groupModalContent');
  content.innerHTML = `<div class="flex items-center justify-center py-8 text-gray-400">
    <svg class="animate-spin mr-3 size-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Loading…</div>`;
  openModal('groupModal');

  fetch(`./functions/fetch_accounts.php?account_id=${accountId}&include_groups=1`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { content.innerHTML = `<p class="text-red-500 text-sm">${data.message}</p>`; return; }

      const assignedIds = (data.account_groups || []).map(g => g.group_id);

      let html = `<p class="text-xs text-gray-500 mb-3">Check the groups to assign to this account. Uncheck to remove.</p>`;
      html += `<div class="space-y-2">`;
      AVAILABLE_GROUPS.forEach(g => {
        const checked = assignedIds.includes(parseInt(g.group_id));
        html += `
          <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-amber-50 transition-colors ${checked ? 'border-amber-300 bg-amber-50' : 'border-gray-100'}">
            <input type="checkbox" class="group-checkbox size-4 accent-orange-500" data-group-id="${g.group_id}" ${checked ? 'checked' : ''}>
            <div class="flex-1">
              <p class="text-sm font-semibold text-gray-800">${esc(g.group_name)}</p>
              <p class="text-xs text-gray-400">${esc(g.group_code)}${g.discount_percentage > 0 ? ' · ' + g.discount_percentage + '% off' : ''}${esc(g.description ? ' · ' + g.description : '')}</p>
            </div>
          </label>`;
      });
      html += `</div>`;

      html += `
        <div class="pt-3">
          <label class="form-label">Expiry Date <span class="text-gray-400 font-normal">(optional — leave blank for no expiry)</span></label>
          <input type="datetime-local" id="groupExpiry" class="form-input">
        </div>
        <div class="flex gap-2 pt-2">
          <button type="button" onclick="closeModal('groupModal')" class="btn-secondary flex-1">Cancel</button>
          <button type="button" onclick="saveGroups()" class="btn-primary flex-1">Save Groups</button>
        </div>`;

      content.innerHTML = html;
    });
}

function saveGroups() {
  const checked = [...document.querySelectorAll('.group-checkbox:checked')].map(el => el.dataset.groupId);
  const expiry  = document.getElementById('groupExpiry')?.value || null;

  const fd = new FormData();
  fd.append('manage_account_groups', '1');
  fd.append('account_id', _groupAccountId);
  checked.forEach(id => fd.append('group_ids[]', id));
  if (expiry) fd.append('expires_at', expiry);

  fetch(ADD_GROUP_URL, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        closeModal('groupModal');
        showToast('Groups updated successfully!', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast('Error: ' + data.message, 'error');
      }
    })
    .catch(() => showToast('Network error.', 'error'));
}

// ── Delete modal ───────────────────────────────────────────────────────────
function openDeleteModal(accountId, accountName) {
  document.getElementById('deleteAccountId').value = accountId;
  document.getElementById('deleteAccountName').innerHTML = `Delete <strong>"${accountName}"</strong>?`;
  openModal('deleteAccountModal');
}

// ── Escape helper ──────────────────────────────────────────────────────────
function esc(v) {
  if (v == null) return '';
  return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Toast ──────────────────────────────────────────────────────────────────
function showToast(msg, type = 'info') {
  const c = { success:'bg-teal-600', error:'bg-red-600', info:'bg-gray-800' };
  const el = document.createElement('div');
  el.className = `${c[type]||c.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex gap-2 min-w-56 max-w-sm fixed bottom-5 right-5 z-[9999]`;
  el.innerHTML = `<span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 text-lg leading-none">✕</button>`;
  document.body.appendChild(el);
  setTimeout(() => el?.remove(), 4000);
}
</script>

<?php $conn->close(); ?>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>