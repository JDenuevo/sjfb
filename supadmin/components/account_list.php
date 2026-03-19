<?php
// components/account_list.php — improved
// Helper: avatar color based on name
function avatarColor($name) {
  $colors = [['bg-orange-100','text-orange-600'],['bg-blue-100','text-blue-600'],['bg-purple-100','text-purple-600'],
             ['bg-green-100','text-green-600'],['bg-rose-100','text-rose-600'],['bg-cyan-100','text-cyan-600']];
  return $colors[ord($name[0] ?? 'A') % count($colors)];
}

// Re-fetch with search if needed (override parent query if search applied)
$searchTerm = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$where = ["role IN ('customer','admin','rider','super_admin')"];
$bindParams = [];
$bindTypes  = '';

if ($searchTerm) {
  $where[] = "(account_first_name LIKE ? OR account_last_name LIKE ? OR account_email LIKE ? OR username LIKE ?)";
  $st = "%$searchTerm%";
  $bindParams = array_merge($bindParams, [$st,$st,$st,$st]);
  $bindTypes .= 'ssss';
}
if ($roleFilter) {
  $where[] = "role = ?";
  $bindParams[] = $roleFilter;
  $bindTypes .= 's';
}

$whereClause = 'WHERE '.implode(' AND ', $where);

// Count
$cStmt = $conn->prepare("SELECT COUNT(*) as total FROM accounts $whereClause");
if ($bindTypes) $cStmt->bind_param($bindTypes, ...$bindParams);
$cStmt->execute();
$totalItems = $cStmt->get_result()->fetch_assoc()['total'];
$cStmt->close();

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page-1)*$perPage;
$totalPages = ceil($totalItems / $perPage);

// Role counts for stats
$roleCounts = [];
$rcResult = $conn->query("SELECT role, COUNT(*) as cnt FROM accounts WHERE role IN ('customer','admin','rider','super_admin') GROUP BY role");
while ($rc = $rcResult->fetch_assoc()) $roleCounts[$rc['role']] = $rc['cnt'];

// Main query
$mStmt = $conn->prepare("SELECT * FROM accounts $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$allParams = array_merge($bindParams, [$perPage, $offset]);
$allTypes  = $bindTypes . 'ii';
$mStmt->bind_param($allTypes, ...$allParams);
$mStmt->execute();
$result = $mStmt->get_result();
?>

<!-- Stats strip -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
  <?php
  $rConf = [
    'customer'    => ['bg-blue-50','text-blue-700','border-blue-100', 'Customers'],
    'admin'       => ['bg-orange-50','text-orange-700','border-orange-100', 'Admins'],
    'rider'       => ['bg-purple-50','text-purple-700','border-purple-100', 'Riders'],
    'super_admin' => ['bg-gray-50','text-gray-700','border-gray-100', 'Super Admins'],
  ];
  foreach ($rConf as $role => [$bg,$text,$border,$label]): ?>
  <div class="<?= $bg ?> border <?= $border ?> rounded-xl p-3 text-center">
    <div class="text-xl font-bold <?= $text ?>"><?= $roleCounts[$role] ?? 0 ?></div>
    <div class="text-xs <?= $text ?>"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

  <!-- Header -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
    <div class="flex-1">
      <h2 class="text-lg font-semibold text-gray-800">Accounts</h2>
      <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> total accounts</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2">
      <!-- Role filter -->
      <select name="role" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-400">
        <option value="">All Roles</option>
        <?php foreach (['customer','admin','rider','super_admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
        <?php endforeach; ?>
      </select>
      <!-- Search -->
      <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
        <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search accounts..." class="text-sm px-3 py-2 focus:outline-none w-48">
        <button type="submit" class="px-3 py-2 text-orange-500 hover:bg-orange-50">
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>
      </div>
      <!-- Add Account button -->
      <button type="button" data-modal-target="addAccountModal"
        class="flex items-center gap-x-2 px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white text-sm font-medium rounded-lg transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Add Account
      </button>
    </form>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Contact</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Location</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Joined</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php while ($row = $result->fetch_assoc()):
          [$avatarBg, $avatarText] = avatarColor($row['account_first_name'] ?? 'A');
          $initials = strtoupper(substr($row['account_first_name'],0,1).substr($row['last_name'],0,1));
          $roleConf = ['customer'=>['bg-blue-100','text-blue-700'],'admin'=>['bg-orange-100','text-orange-700'],
                       'rider'=>['bg-purple-100','text-purple-700'],'super_admin'=>['bg-gray-100','text-gray-700'],'guest'=>['bg-gray-100','text-gray-600']];
          [$roleBg, $roleText] = $roleConf[$row['role']] ?? ['bg-gray-100','text-gray-600'];
        ?>
        <tr class="account-row hover:bg-orange-50/30 transition-colors">
          <!-- User -->
          <td class="px-6 py-3">
            <div class="flex items-center gap-3">
              <div class="size-9 rounded-full <?= $avatarBg ?> flex items-center justify-center text-xs font-bold <?= $avatarText ?> shrink-0">
                <?= $initials ?>
              </div>
              <div>
                <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['account_first_name'].' '.$row['account_last_name']) ?></div>
                <div class="text-xs text-gray-400">@<?= htmlspecialchars($row['username'] ?? '—') ?></div>
              </div>
            </div>
          </td>
          <!-- Role -->
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $roleBg ?> <?= $roleText ?>">
              <?= ucfirst(str_replace('_',' ', $row['role'])) ?>
            </span>
          </td>
          <!-- Contact -->
          <td class="px-4 py-3">
            <div class="text-xs text-gray-700"><?= htmlspecialchars($row['account_email']) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($row['account_phone'] ?? '—') ?></div>
          </td>
          <!-- Location -->
          <td class="px-4 py-3">
            <div class="text-xs text-gray-700"><?= htmlspecialchars($row['city'] ?: '—') ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($row['postal_code'] ?: '') ?></div>
          </td>
          <!-- Date -->
          <td class="px-4 py-3">
            <div class="text-xs text-gray-600"><?= date('M j, Y', strtotime($row['created_at'])) ?></div>
            <div class="text-xs text-gray-400"><?= date('g:i A', strtotime($row['created_at'])) ?></div>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right">
            <div class="inline-flex gap-1">
              <button onclick="document.getElementById('updateAccountModal<?= $row['account_id'] ?>').classList.remove('hidden')"
                class="size-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button onclick="document.getElementById('deleteAccountModal<?= $row['account_id'] ?>').classList.remove('hidden')"
                class="size-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Delete">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              </button>
            </div>
          </td>
        </tr>

        <!-- Update Modal -->
        <div id="updateAccountModal<?= $row['account_id'] ?>" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
          <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-800">Edit Account — <?= htmlspecialchars($row['account_first_name']) ?></h3>
              <button onclick="document.getElementById('updateAccountModal<?= $row['account_id'] ?>').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </button>
            </div>
            <form action="./functions/update.php" method="POST" class="p-6 space-y-4">
              <input type="hidden" name="account_id" value="<?= $row['account_id'] ?>">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                  <input type="text" name="username" value="<?= htmlspecialchars($row['username'] ?? '') ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
                  <select name="role" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                    <?php foreach (['customer','admin','rider','guest'] as $r): ?>
                    <option value="<?= $r ?>" <?= $row['role']==$r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">New Password <span class="text-gray-400">(leave blank to keep)</span></label>
                  <input type="password" name="password" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400" placeholder="New password">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password</label>
                  <input type="password" name="confirm_password" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400" placeholder="Confirm">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">First Name</label>
                  <input type="text" name="account_first_name" value="<?= htmlspecialchars($row['account_first_name']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Last Name</label>
                  <input type="text" name="account_last_name" value="<?= htmlspecialchars($row['account_last_name']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                <input type="email" name="account_email" value="<?= htmlspecialchars($row['account_email']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number</label>
                <input type="text" name="account_phone" value="<?= htmlspecialchars($row['account_phone'] ?? '') ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                <textarea name="account_address" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400"><?= htmlspecialchars($row['account_address']) ?></textarea>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">City</label>
                  <input type="text" name="city" value="<?= htmlspecialchars($row['city']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Postal Code</label>
                  <input type="text" name="postal_code" value="<?= htmlspecialchars($row['postal_code']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
              </div>
              <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('updateAccountModal<?= $row['account_id'] ?>').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" name="update_account" class="px-4 py-2 text-sm bg-orange-600 hover:bg-orange-500 text-white rounded-lg transition-colors">Save Changes</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Delete Modal -->
        <div id="deleteAccountModal<?= $row['account_id'] ?>" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
          <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div class="flex items-center gap-3 mb-4">
              <div class="size-10 bg-red-100 rounded-xl flex items-center justify-center">
                <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              </div>
              <div>
                <h3 class="text-base font-semibold text-gray-800">Delete Account</h3>
                <p class="text-xs text-gray-500">This action cannot be undone.</p>
              </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete <strong><?= htmlspecialchars($row['account_first_name'].' '.$row['account_last_name']) ?></strong>?</p>
            <form action="./functions/delete.php" method="POST">
              <input type="hidden" name="account_id" value="<?= $row['account_id'] ?>">
              <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('deleteAccountModal<?= $row['account_id'] ?>').classList.add('hidden')" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" name="delete_account" class="flex-1 px-4 py-2 text-sm bg-orange-600 hover:bg-orange-500 text-white rounded-lg transition-colors">Delete</button>
              </div>
            </form>
          </div>
        </div>

        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Footer / Pagination -->
  <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> total accounts</p>
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
