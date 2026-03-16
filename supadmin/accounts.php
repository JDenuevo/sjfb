<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// Build WHERE clause for search/filter
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$where = ["role IN ('customer','admin','rider','super_admin')"];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= 'ssss';
}
if (!empty($roleFilter)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM accounts $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);
$countStmt->close();

$offset = ($page - 1) * $perPage;

// Main query with pagination
$query = "SELECT * FROM accounts $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes = $types . 'ii';
if (!empty($allParams)) $stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$result = $stmt->get_result();

// Role counts for stats
$roleCounts = [];
$rcResult = $conn->query("SELECT role, COUNT(*) as cnt FROM accounts WHERE role IN ('customer','admin','rider','super_admin') GROUP BY role");
while ($rc = $rcResult->fetch_assoc()) $roleCounts[$rc['role']] = $rc['cnt'];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accounts | St. Joseph Fish Brokerage Inc.</title>

  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <style>
    /* Import products.php design language */
    .account-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .account-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

    .modal-overlay {
      position: fixed; inset: 0; z-index: 999;
      display: flex; align-items: flex-start; justify-content: center;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(4px);
      overflow-y: auto;
      padding: 2rem 1rem;
    }
    .modal-overlay.hidden { display: none; }

    .modal-box {
      background: white;
      width: 100%; max-width: 56rem;
      border-radius: 1.25rem;
      box-shadow: 0 25px 60px rgba(0,0,0,0.2);
      overflow: hidden;
    }

    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f3f4f6;
      background: #fafafa;
    }
    .modal-header h3 { font-size: 1.125rem; font-weight: 700; color: #111827; }
    .modal-header p { font-size: 0.75rem; color: #6b7280; margin-top: 1px; }

    .modal-close {
      width: 2rem; height: 2rem;
      display: flex; align-items: center; justify-content: center;
      border-radius: 50%; background: #f3f4f6;
      color: #6b7280; border: none; cursor: pointer;
      transition: background 0.15s, color 0.15s;
    }
    .modal-close:hover { background: #fee2e2; color: #dc2626; }

    .modal-body { padding: 1.5rem; max-height: 75vh; overflow-y: auto; }
    .modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid #f3f4f6;
      background: #fafafa;
      display: flex; justify-content: flex-end; gap: 0.625rem;
    }

    .form-label {
      display: block; font-size: 0.8125rem; font-weight: 600; color: #374151;
      margin-bottom: 0.375rem;
    }
    .form-input {
      width: 100%; padding: 0.5rem 0.75rem;
      border: 1px solid #e5e7eb; border-radius: 0.5rem;
      font-size: 0.875rem; color: #111827;
      transition: border-color 0.15s, box-shadow 0.15s;
      outline: none;
    }
    .form-input:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,0.1); }

    .section-title {
      font-size: 0.9375rem; font-weight: 700; color: #111827;
      border-left: 3px solid #ea580c;
      padding-left: 0.625rem;
      margin: 1.25rem 0 0.75rem;
    }

    .btn-primary {
      padding: 0.5rem 1.25rem;
      background: #ea580c; color: white;
      border-radius: 0.625rem; border: none;
      font-size: 0.875rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s, transform 0.1s;
    }
    .btn-primary:hover { background: #c2410c; }
    .btn-primary:active { transform: scale(0.97); }

    .btn-secondary {
      padding: 0.5rem 1.25rem;
      background: white; color: #374151;
      border-radius: 0.625rem; border: 1px solid #e5e7eb;
      font-size: 0.875rem; font-weight: 500;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-secondary:hover { background: #f9fafb; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-orange { background: #ffedd5; color: #9a3412; }
    .badge-purple { background: #f3e8ff; color: #6b21a8; }
    .badge-gray { background: #f3f4f6; color: #374151; }

    .stats-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 1rem;
      padding: 1.25rem;
      transition: all 0.2s ease;
    }
    .stats-card:hover {
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }

    .avatar {
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 9999px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.875rem;
    }
  </style>
</head>

<body class="bg-gray-50">
  
  <?php include('./components/header.php'); ?>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      
      <?php if (!empty($_SESSION['message'])): 
        $message = $_SESSION['message'];
        $alertClass = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
        echo '<div class="mt-2 ' . $alertClass . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
          <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
        </div>';
        unset($_SESSION['message']);
      endif; ?>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $roleConfig = [
          'customer' => ['label' => 'Customers', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'icon' => '👤'],
          'admin' => ['label' => 'Admins', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => '⚙️'],
          'rider' => ['label' => 'Riders', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'icon' => '🏍️'],
          'super_admin' => ['label' => 'Super Admins', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => '👑'],
        ];
        
        foreach ($roleConfig as $role => $config): ?>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium"><?= $config['label'] ?></p>
              <p class="text-2xl font-bold text-gray-900 mt-1"><?= $roleCounts[$role] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 <?= $config['bg'] ?> rounded-xl flex items-center justify-center <?= $config['text'] ?> text-xl">
              <?= $config['icon'] ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Main Card -->
      <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        <!-- Header with filters -->
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Accounts</h2>
            <p class="text-sm text-gray-500 mt-0.5">
              <span class="font-semibold text-gray-800"><?= $totalItems ?></span> total accounts
            </p>
          </div>
          
          <form method="GET" class="flex flex-wrap gap-2">
            <select name="role" onchange="this.form.submit()" 
                    class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
              <option value="">All Roles</option>
              <?php foreach (['customer','admin','rider','super_admin'] as $r): ?>
              <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>>
                <?= ucfirst(str_replace('_',' ', $r)) ?>
              </option>
              <?php endforeach; ?>
            </select>
            
            <div class="relative">
              <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                     placeholder="Search name, email..." 
                     class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
              <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
            </div>
            
            <?php if (!empty($search) || !empty($roleFilter)): ?>
            <a href="accounts.php" class="px-3 py-2 text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Clear
            </a>
            <?php endif; ?>
            
            <button type="button" onclick="openAddModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14"/><path d="M12 5v14"/>
              </svg>
              Add Account
            </button>
          </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Location</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Joined</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                  $initials = strtoupper(substr($row['first_name'],0,1).substr($row['last_name'],0,1));
                  $roleColors = [
                    'customer' => ['badge-blue', 'bg-blue-100', 'text-blue-600'],
                    'admin' => ['badge-orange', 'bg-orange-100', 'text-orange-600'],
                    'rider' => ['badge-purple', 'bg-purple-100', 'text-purple-600'],
                    'super_admin' => ['badge-gray', 'bg-gray-100', 'text-gray-600'],
                  ];
                  [$roleBadge, $roleBg, $roleText] = $roleColors[$row['role']] ?? ['badge-gray', 'bg-gray-100', 'text-gray-600'];
                ?>
                <tr class="account-row hover:bg-orange-50/40 transition-colors">
                  <!-- User -->
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="avatar <?= $roleBg ?> <?= $roleText ?>">
                        <?= $initials ?>
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></p>
                        <p class="text-xs text-gray-400">@<?= htmlspecialchars($row['username'] ?? '—') ?></p>
                      </div>
                    </div>
                  </td>
                  
                  <!-- Role -->
                  <td class="px-4 py-4">
                    <span class="badge <?= $roleBadge ?>"><?= ucfirst(str_replace('_',' ', $row['role'])) ?></span>
                  </td>
                  
                  <!-- Contact -->
                  <td class="px-4 py-4">
                    <p class="text-xs text-gray-700"><?= htmlspecialchars($row['email']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($row['phone_number'] ?? '—') ?></p>
                  </td>
                  
                  <!-- Location -->
                  <td class="px-4 py-4">
                    <p class="text-xs text-gray-700"><?= htmlspecialchars($row['city'] ?: '—') ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($row['postal_code'] ?: '') ?></p>
                  </td>
                  
                  <!-- Date -->
                  <td class="px-4 py-4">
                    <p class="text-xs text-gray-600"><?= date('M j, Y', strtotime($row['created_at'])) ?></p>
                    <p class="text-xs text-gray-400"><?= date('g:i A', strtotime($row['created_at'])) ?></p>
                  </td>
                  
                  <!-- Actions -->
                  <td class="px-4 py-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button onclick="openEditModal(<?= $row['account_id'] ?>)"
                              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                        </svg>
                      </button>
                      <button onclick="openDeleteModal(<?= $row['account_id'] ?>, '<?= htmlspecialchars(addslashes($row['first_name'].' '.$row['last_name'])) ?>')"
                              class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"/>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center gap-3">
                      <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                          <circle cx="12" cy="8" r="4"/><path d="M5.5 20.8c.9-3 4.1-4 6.5-4s5.6 1 6.5 4"/>
                        </svg>
                      </div>
                      <p class="text-sm font-semibold text-gray-700">No accounts found</p>
                      <p class="text-xs text-gray-400">Try adjusting your filters</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
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
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            $queryPrefix = $queryString ? '&' . $queryString : '';
            
            if ($start > 1) {
              echo '<a href="?page=1' . $queryPrefix . '" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
            }
            if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
            
            for ($i = $start; $i <= $end; $i++):
            ?>
              <a href="?page=<?= $i ?><?= $queryPrefix ?>" 
                 class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
                 <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
                <?= $i ?>
              </a>
            <?php
            endfor;
            
            if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
            if ($end < $totalPages) {
              echo '<a href="?page=' . $totalPages . $queryPrefix . '" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page + 1 ?><?= $queryPrefix ?>" 
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
    </div>
  </div>

  <!-- ADD ACCOUNT MODAL (Redesigned) -->
  <div id="addAccountModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Add New Account</h3>
          <p>Create a new user account</p>
        </div>
        <button class="modal-close" onclick="closeModal('addAccountModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form action="./functions/add.php" method="POST" class="space-y-4">
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
                <option value="admin">Admin</option>
                <option value="rider">Rider</option>
                <option value="customer">Customer</option>
                <option value="guest">Guest</option>
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
              <label class="form-label">First Name <span class="text-red-500">*</span></label>
              <input type="text" name="first_name" required class="form-input" placeholder="John">
            </div>
            <div>
              <label class="form-label">Last Name <span class="text-red-500">*</span></label>
              <input type="text" name="last_name" required class="form-input" placeholder="Doe">
            </div>
          </div>
          
          <div>
            <label class="form-label">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" required class="form-input" placeholder="john@example.com">
          </div>
          
          <div>
            <label class="form-label">Phone Number <span class="text-red-500">*</span></label>
            <input type="text" name="phone_number" required class="form-input" placeholder="09123456789" maxlength="11">
          </div>
          
          <p class="section-title">Address Information</p>
          
          <div>
            <label class="form-label">Address <span class="text-red-500">*</span></label>
            <textarea name="address" required rows="2" class="form-input resize-none" placeholder="Street, Barangay"></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">City <span class="text-red-500">*</span></label>
              <input type="text" name="city" required class="form-input" placeholder="Manila">
            </div>
            <div>
              <label class="form-label">Postal Code <span class="text-red-500">*</span></label>
              <input type="text" name="postal_code" required class="form-input" placeholder="1000">
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeModal('addAccountModal')" class="btn-secondary">Cancel</button>
        <button type="submit" name="add_account" form="addAccountForm" class="btn-primary">
          Add Account
        </button>
      </div>
    </div>
  </div>

  <!-- EDIT ACCOUNT MODAL (Redesigned) -->
  <div id="editAccountModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Edit Account</h3>
          <p>Update account information</p>
        </div>
        <button class="modal-close" onclick="closeModal('editAccountModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div id="editAccountContent" class="modal-body">
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading account data...
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE ACCOUNT MODAL (Redesigned) -->
  <div id="deleteAccountModal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:28rem">
      <div class="modal-header">
        <div>
          <h3>Delete Account</h3>
          <p>This action cannot be undone</p>
        </div>
        <button class="modal-close" onclick="closeModal('deleteAccountModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <div class="modal-body text-center">
        <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </div>
        
        <form action="./functions/delete.php" method="POST" id="deleteAccountForm">
          <input type="hidden" name="account_id" id="deleteAccountId">
          <p id="deleteAccountName" class="text-sm font-semibold text-gray-800 mb-1"></p>
          <p class="text-xs text-red-500 mb-5">This will permanently delete this account.</p>
          <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeModal('deleteAccountModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="delete_account" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Modal functions
    function openAddModal() {
      document.getElementById('addAccountModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    window.closeModal = function(modalId) {
      document.getElementById(modalId).classList.add('hidden');
      document.body.style.overflow = '';
    };

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
      });
    });

    // Open edit modal and fetch account data
    function openEditModal(accountId) {
      const modal = document.getElementById('editAccountModal');
      const content = document.getElementById('editAccountContent');
      
      content.innerHTML = `
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading account data...
        </div>`;
      modal.classList.remove('hidden');
      
      fetch(`./functions/fetch_accounts.php?account_id=${accountId}`)
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load account data.</p>';
            return;
          }
          const a = data.account;
          
          content.innerHTML = `
            <form action="./functions/update.php" method="POST" class="space-y-4">
              <input type="hidden" name="account_id" value="${a.account_id}">
              
              <p class="section-title">Login Information</p>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Username</label>
                  <input type="text" name="username" value="${a.username || ''}" class="form-input">
                </div>
                <div>
                  <label class="form-label">Role</label>
                  <select name="role" class="form-input">
                    <option value="customer" ${a.role == 'customer' ? 'selected' : ''}>Customer</option>
                    <option value="admin" ${a.role == 'admin' ? 'selected' : ''}>Admin</option>
                    <option value="rider" ${a.role == 'rider' ? 'selected' : ''}>Rider</option>
                    <option value="guest" ${a.role == 'guest' ? 'selected' : ''}>Guest</option>
                  </select>
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">New Password <span class="text-gray-400">(leave blank to keep)</span></label>
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
                  <input type="text" name="first_name" value="${a.first_name || ''}" class="form-input">
                </div>
                <div>
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" value="${a.last_name || ''}" class="form-input">
                </div>
              </div>
              
              <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" value="${a.email || ''}" class="form-input">
              </div>
              
              <div>
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" value="${a.phone_number || ''}" class="form-input">
              </div>
              
              <p class="section-title">Address Information</p>
              
              <div>
                <label class="form-label">Address</label>
                <textarea name="address" rows="2" class="form-input resize-none">${a.address || ''}</textarea>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">City</label>
                  <input type="text" name="city" value="${a.city || ''}" class="form-input">
                </div>
                <div>
                  <label class="form-label">Postal Code</label>
                  <input type="text" name="postal_code" value="${a.postal_code || ''}" class="form-input">
                </div>
              </div>
              
              <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
                <button type="button" onclick="closeModal('editAccountModal')" class="btn-secondary">Cancel</button>
                <button type="submit" name="update_account" class="btn-primary">Save Changes</button>
              </div>
            </form>
          `;
        })
        .catch(() => {
          content.innerHTML = '<p class="text-red-500 p-4 text-center">Network error. Please try again.</p>';
        });
    }

    // Open delete modal
    function openDeleteModal(accountId, accountName) {
      document.getElementById('deleteAccountId').value = accountId;
      document.getElementById('deleteAccountName').innerHTML = `Are you sure you want to delete <strong>"${accountName}"</strong>?`;
      document.getElementById('deleteAccountModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  </script>

  <?php $conn->close(); ?>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>