<?php
/**
 * supadmin/riders.php
 * Admin rider management.
 */
session_start();
include '../conn.php';

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../index.php');
    exit;
}

// ── Accounts that can become riders ─────────────────────────────────────
$availableAccounts = $conn->query("
    SELECT a.account_id, a.account_first_name, a.account_last_name, a.account_email
    FROM accounts a
    LEFT JOIN riders r ON r.account_id = a.account_id AND r.is_deleted = 0
    WHERE r.account_id IS NULL
      AND a.role NOT IN ('admin','super_admin','customer')
      AND a.is_deleted = 0
    ORDER BY a.account_first_name, a.account_last_name
")->fetch_all(MYSQLI_ASSOC);

// ── Pagination ─────────────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Search filter
$search      = trim($_GET['search'] ?? '');
$whereSearch = '';
$searchParam = '';
if ($search !== '') {
    $whereSearch = "AND (a.account_first_name LIKE ? OR a.account_last_name LIKE ? OR a.account_email LIKE ? OR r.vehicle_plate_number LIKE ? OR r.organization LIKE ?)";
    $s = '%' . $search . '%';
}

$countSQL = "SELECT COUNT(*) AS total FROM riders r
             JOIN accounts a ON a.account_id = r.account_id
             WHERE r.is_deleted = 0 {$whereSearch}";
if ($search !== '') {
    $cs = $conn->prepare($countSQL);
    $cs->bind_param('sssss', $s,$s,$s,$s,$s);
    $cs->execute();
    $totalItems = (int)$cs->get_result()->fetch_assoc()['total'];
} else {
    $totalItems = (int)$conn->query($countSQL)->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($totalItems / $perPage));

$mainSQL = "SELECT r.rider_id, r.image, r.rider_name, r.vehicle_type, r.vehicle_plate_number,
               r.variant_color, r.organization, r.rider_phone, r.is_available,
               r.current_lat, r.current_lng, r.created_at,
               a.account_id, a.account_first_name, a.account_last_name, a.account_email, a.account_phone,
               COALESCE(r.rider_name, CONCAT(a.account_first_name,' ',a.account_last_name)) AS display_name,
               (SELECT COUNT(*) FROM orders o WHERE o.assigned_rider_id=r.rider_id AND o.order_status='OutForDelivery') AS active_deliveries,
               (SELECT COUNT(*) FROM orders o WHERE o.assigned_rider_id=r.rider_id AND o.order_status='Delivered') AS total_delivered
            FROM riders r
            JOIN accounts a ON a.account_id = r.account_id
            WHERE r.is_deleted = 0 {$whereSearch}
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?";

if ($search !== '') {
    $st = $conn->prepare($mainSQL);
    $st->bind_param('sssssii', $s,$s,$s,$s,$s, $perPage, $offset);
    $st->execute();
    $riders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $st = $conn->prepare($mainSQL);
    $st->bind_param('ii', $perPage, $offset);
    $st->execute();
    $riders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Stats ──────────────────────────────────────────────────────────────
$stats = [
    'total'      => (int)$conn->query("SELECT COUNT(*) AS v FROM riders WHERE is_deleted=0")->fetch_assoc()['v'],
    'available'  => (int)$conn->query("SELECT COUNT(*) AS v FROM riders WHERE is_deleted=0 AND is_available=1")->fetch_assoc()['v'],
    'delivering' => (int)$conn->query("SELECT COUNT(DISTINCT assigned_rider_id) AS v FROM orders WHERE order_status='OutForDelivery' AND assigned_rider_id IS NOT NULL")->fetch_assoc()['v'],
    'delivered'  => (int)$conn->query("SELECT COUNT(*) AS v FROM orders WHERE order_status='Delivered' AND assigned_rider_id IS NOT NULL")->fetch_assoc()['v'],
];

// Vehicle type options
$vehicleTypes = [
    'Motorcycle'          => 'Motorcycle',
    'MPV'                 => 'MPV (up to 200 kg)',
    'Sedan'               => 'Sedan (up to 200 kg)',
    'Pickup Truck'        => 'Pickup Truck (up to 600 kg)',
    'Van'                 => 'Van (up to 1,000 kg)',
    'Small Truck'         => 'Small Truck (up to 2,000 kg)',
    'Medium Truck'        => 'Medium Truck (up to 5,000 kg)',
    'Large Truck'         => 'Large Truck (up to 12,000 kg)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riders | St. Joseph Fish Brokerage Inc.</title>
  
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <style>
    /* Import products.php design language */
    .rider-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .rider-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

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
      width: 100%; max-width: 48rem;
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

    .btn-danger {
      padding: 0.5rem 1rem;
      background: #fee2e2; color: #dc2626;
      border-radius: 0.5rem; border: none;
      font-size: 0.8125rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-danger:hover { background: #fecaca; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-purple { background: #f3e8ff; color: #6b21a8; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
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

    .image-thumb {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 9999px;
      object-fit: cover;
      border: 2px solid white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<!-- Toast notifications -->
<div id="toast-wrap" class="fixed bottom-5 right-5 flex flex-col gap-2 z-[60]"></div>

<!-- Content -->
<div class="w-full lg:ps-64">
  <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

    <?php if (!empty($_SESSION['message'])): 
      $msg = $_SESSION['message']; 
      unset($_SESSION['message']);
      $cls = $msg['type'] === 'success' ? 'bg-teal-500' : 'bg-red-500';
    ?>
    <div class="<?= $cls ?> text-white text-sm rounded-xl p-4 flex items-center gap-2">
      <span class="font-bold"><?= ucfirst($msg['type']) ?>!</span> <?= htmlspecialchars($msg['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards - Redesigned to match products.php style -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total Riders</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?></p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 text-xl">🏍️</div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Available</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['available'] ?></p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl">✅</div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Out Delivering</p>
            <p class="text-2xl font-bold text-orange-600 mt-1"><?= $stats['delivering'] ?></p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 text-xl">🚚</div>
        </div>
      </div>
      
      <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total Delivered</p>
            <p class="text-2xl font-bold text-blue-600 mt-1"><?= $stats['delivered'] ?></p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl">📦</div>
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

      <!-- Header with search and add button -->
      <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Riders</h2>
          <p class="text-sm text-gray-500 mt-0.5">
            <span class="font-semibold text-gray-800"><?= $totalItems ?></span> registered riders
          </p>
        </div>
        
        <div class="flex gap-2">
          <form method="GET" class="relative">
            <div class="relative">
              <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search name, plate, org..." 
                    class="ps-9 pe-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-64">
              <svg class="absolute ms-3 left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
            </div>
          </form>
  
          <?php if ($search): ?>
          <a href="riders.php" class="px-3 py-2 text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear
          </a>
          <?php endif; ?>
          
          <button onclick="openAddModal()" 
                  class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M5 12h14"/><path d="M12 5v14"/>
            </svg>
            Add Rider
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Rider</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Vehicle</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Organization</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Active</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Done</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <?php if (empty($riders)): ?>
            <tr>
              <td colspan="8" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="1.5">
                      <circle cx="12" cy="10" r="3"/><path d="M5 18v2c0 1.1.9 2 2 2h10a2 2 0 0 0 2-2v-2"/><circle cx="12" cy="10" r="7"/><path d="M12 13v4"/><path d="M8 16h8"/>
                    </svg>
                  </div>
                  <p class="text-sm font-semibold text-gray-700">No riders found</p>
                  <p class="text-xs text-gray-400">Click "Add Rider" to register a new rider</p>
                </div>
              </td>
            </tr>
            <?php else: foreach ($riders as $rider): 
              $active = (int)$rider['active_deliveries'];
              $done   = (int)$rider['total_delivered'];
            ?>
            <tr class="rider-row hover:bg-purple-50/40 transition-colors">
              <!-- Rider -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <?php if (!empty($rider['image'])): ?>
                  <img src="../<?= htmlspecialchars($rider['image']) ?>" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                  <?php else: ?>
                  <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-sm font-bold text-purple-600">
                    <?= strtoupper(substr($rider['account_first_name'],0,1).substr($rider['account_last_name'],0,1)) ?>
                  </div>
                  <?php endif; ?>
                  <div>
                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($rider['display_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($rider['account_email']) ?></p>
                  </div>
                </div>
              </td>
              
              <!-- Vehicle -->
              <td class="px-4 py-4">
                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($rider['vehicle_type']) ?></p>
                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($rider['vehicle_plate_number'] ?? '—') ?></p>
                <?php if (!empty($rider['variant_color'])): ?>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($rider['variant_color']) ?></p>
                <?php endif; ?>
              </td>
              
              <!-- Contact -->
              <td class="px-4 py-4">
                <p class="text-xs text-gray-700"><?= htmlspecialchars($rider['rider_phone'] ?: ($rider['phone_number'] ?? '—')) ?></p>
              </td>
              
              <!-- Organization -->
              <td class="px-4 py-4">
                <span class="px-2 py-1 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-full">
                  <?= htmlspecialchars($rider['organization'] ?? '—') ?>
                </span>
              </td>
              
              <!-- Active -->
              <td class="px-4 py-4 text-center">
                <?php if ($active > 0): ?>
                <span class="px-2 py-1 text-xs font-semibold bg-orange-100 text-orange-700 rounded-full"><?= $active ?></span>
                <?php else: ?>
                <span class="text-xs text-gray-400">0</span>
                <?php endif; ?>
              </td>
              
              <!-- Done -->
              <td class="px-4 py-4 text-center">
                <span class="text-sm font-bold text-gray-900"><?= $done ?></span>
              </td>
              
              <!-- Status -->
              <td class="px-4 py-4 text-center">
                <?php if ($active > 0): ?>
                <span class="flex items-center justify-center gap-1 text-xs text-orange-600">
                  <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                  Delivering
                </span>
                <?php elseif ($rider['is_available']): ?>
                <span class="flex items-center justify-center gap-1 text-xs text-green-600">
                  <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                  Available
                </span>
                <?php else: ?>
                <span class="flex items-center justify-center gap-1 text-xs text-gray-400">
                  <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                  Offline
                </span>
                <?php endif; ?>
              </td>
              
              <!-- Actions -->
              <td class="px-4 py-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button onclick="openEditModal(<?= $rider['rider_id'] ?>)"
                          class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                    </svg>
                  </button>
                  <button onclick="openDeleteModal(<?= $rider['rider_id'] ?>, '<?= htmlspecialchars(addslashes($rider['display_name'])) ?>', <?= $active ?>)"
                          class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination (matching products.php style) -->
      <?php if ($totalPages > 1): ?>
      <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
        <p class="text-sm text-gray-500">
          Showing <span class="font-semibold text-gray-800"><?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?></span> 
          of <span class="font-semibold text-gray-800"><?= $totalItems ?></span> riders
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

<!-- ADD RIDER MODAL (Redesigned) -->
<div id="addRiderModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div>
        <h3>Add New Rider</h3>
        <p>Register a new delivery rider</p>
      </div>
      <button class="modal-close" onclick="closeModal('addRiderModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="modal-body">
      <form action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="add_rider">
        
        <p class="section-title">Rider Information</p>
        
        <div>
          <label class="form-label">Rider Photo <span class="text-red-500">*</span></label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required
                 class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
        </div>
        
        <div>
          <label class="form-label">Link to Account <span class="text-red-500">*</span></label>
          <select name="account_id" required class="form-input">
            <option value="">— Select account —</option>
            <?php foreach ($availableAccounts as $acc): ?>
            <option value="<?= $acc['account_id'] ?>">
              <?= htmlspecialchars($acc['account_first_name'].' '.$acc['account_last_name'].' ('.$acc['account_email'].')') ?>
            </option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-400 mt-1">Selected account's role will be set to <strong>rider</strong></p>
        </div>
        
        <div>
          <label class="form-label">Display Name <span class="text-gray-400">(optional)</span></label>
          <input type="text" name="full_name" placeholder="e.g. Jun Rider" class="form-input">
        </div>
        
        <p class="section-title">Vehicle Details</p>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Vehicle Type <span class="text-red-500">*</span></label>
            <select name="vehicle_type" required class="form-input">
              <option value="">— Select —</option>
              <?php foreach ($vehicleTypes as $val => $label): ?>
              <option value="<?= $val ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="form-label">Vehicle Color <span class="text-red-500">*</span></label>
            <input type="text" name="variant_color" placeholder="e.g. Black, Red, White" required class="form-input">
          </div>
          
          <div>
            <label class="form-label">Plate Number <span class="text-red-500">*</span></label>
            <input type="text" name="vehicle_plate_number" placeholder="ABC-1234" required class="form-input">
          </div>
          
          <div>
            <label class="form-label">Rider Contact Number <span class="text-red-500">*</span></label>
            <input type="text" name="rider_phone" placeholder="09XXXXXXXXX" required class="form-input">
          </div>
        </div>
        
        <div>
          <label class="form-label">Organization <span class="text-red-500">*</span></label>
          <input type="text" name="organization" placeholder="e.g. Lalamove, Foodpanda, Independent" required class="form-input">
        </div>
      </form>
    </div>

    <div class="modal-footer">
      <button type="button" onclick="closeModal('addRiderModal')" class="btn-secondary">Cancel</button>
      <button type="submit" name="add_rider" form="addRiderForm" class="btn-primary">
        Add Rider
      </button>
    </div>
  </div>
</div>

<!-- EDIT RIDER MODAL (Redesigned) -->
<div id="editRiderModal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="modal-header">
      <div>
        <h3>Edit Rider</h3>
        <p>Update rider information</p>
      </div>
      <button class="modal-close" onclick="closeModal('editRiderModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div id="editRiderContent" class="modal-body">
      <div class="flex items-center justify-center py-12 text-gray-400">
        <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Loading rider data...
      </div>
    </div>
  </div>
</div>

<!-- DELETE RIDER MODAL (Redesigned) -->
<div id="deleteRiderModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:28rem">
    <div class="modal-header">
      <div>
        <h3>Remove Rider</h3>
        <p>This action cannot be undone</p>
      </div>
      <button class="modal-close" onclick="closeModal('deleteRiderModal')">
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
      
      <form action="./functions/delete.php" method="POST" id="deleteRiderForm">
        <input type="hidden" name="action" value="delete_rider">
        <input type="hidden" name="rider_id" id="deleteRiderId">
        <p id="deleteRiderName" class="text-sm font-semibold text-gray-800 mb-1"></p>
        <p id="deleteRiderWarning" class="text-xs text-red-500 mb-5"></p>
        <div class="flex gap-3 justify-center">
          <button type="button" onclick="closeModal('deleteRiderModal')" class="btn-secondary">Cancel</button>
          <button type="submit" name="delete_rider" class="btn-primary" style="background:#dc2626">Remove Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Modal functions
function openAddModal() {
  document.getElementById('addRiderModal').classList.remove('hidden');
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

// Open edit modal and fetch rider data
function openEditModal(riderId) {
  const modal = document.getElementById('editRiderModal');
  const content = document.getElementById('editRiderContent');
  
  content.innerHTML = `
    <div class="flex items-center justify-center py-12 text-gray-400">
      <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
      </svg>
      Loading rider data...
    </div>`;
  modal.classList.remove('hidden');
  
  fetch(`./functions/fetch_riders.php?rider_id=${riderId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load rider data.</p>';
        return;
      }
      const r = data.rider;
      
      // Build edit form HTML
      content.innerHTML = `
        <form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="action" value="update_rider">
          <input type="hidden" name="rider_id" value="${r.rider_id}">
          
          <p class="section-title">Rider Information</p>
          
          ${r.image ? `
          <div>
            <label class="form-label">Current Photo</label>
            <div class="relative inline-block">
              <img src="../${r.image}" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
              <button type="button" onclick="deleteRiderImage(${r.rider_id})" 
                      class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                ×
              </button>
            </div>
          </div>
          ` : ''}
          
          <div>
            <label class="form-label">Update Photo <span class="text-gray-400">(leave blank to keep current)</span></label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
          </div>
          
          <div>
            <label class="form-label">Display Name</label>
            <input type="text" name="full_name" value="${r.full_name || ''}" class="form-input">
          </div>
          
          <p class="section-title">Vehicle Details</p>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Vehicle Type <span class="text-red-500">*</span></label>
              <select name="vehicle_type" required class="form-input">
                <option value="">— Select —</option>
                <?php foreach ($vehicleTypes as $val => $label): ?>
                <option value="<?= $val ?>" ${r.vehicle_type == '<?= $val ?>' ? 'selected' : ''}> <?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label class="form-label">Vehicle Color <span class="text-red-500">*</span></label>
              <input type="text" name="variant_color" value="${r.variant_color || ''}" required class="form-input">
            </div>
            
            <div>
              <label class="form-label">Plate Number <span class="text-red-500">*</span></label>
              <input type="text" name="vehicle_plate_number" value="${r.vehicle_plate_number || ''}" required class="form-input">
            </div>
            
            <div>
              <label class="form-label">Contact Number <span class="text-red-500">*</span></label>
              <input type="text" name="contact_number" value="${r.contact_number || ''}" required class="form-input">
            </div>
          </div>
          
          <div>
            <label class="form-label">Organization <span class="text-red-500">*</span></label>
            <input type="text" name="organization" value="${r.organization || ''}" required class="form-input">
          </div>
          
          <div>
            <label class="form-label">Availability</label>
            <select name="is_available" class="form-input">
              <option value="1" ${r.is_available == 1 ? 'selected' : ''}>Available</option>
              <option value="0" ${r.is_available == 0 ? 'selected' : ''}>Offline</option>
            </select>
          </div>
          
          <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
            <button type="button" onclick="closeModal('editRiderModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="update_rider" class="btn-primary">Save Changes</button>
          </div>
        </form>
      `;
    })
    .catch(() => {
      content.innerHTML = '<p class="text-red-500 p-4 text-center">Network error. Please try again.</p>';
    });
}

// Open delete modal
function openDeleteModal(riderId, riderName, activeDeliveries) {
  document.getElementById('deleteRiderId').value = riderId;
  document.getElementById('deleteRiderName').innerHTML = `Are you sure you want to remove <strong>"${riderName}"</strong>?`;
  
  if (activeDeliveries > 0) {
    document.getElementById('deleteRiderWarning').innerHTML = '⚠️ This rider has active deliveries and cannot be removed.';
    document.querySelector('#deleteRiderForm button[type="submit"]').disabled = true;
  } else {
    document.getElementById('deleteRiderWarning').innerHTML = 'Their account role will be reverted to <strong>guest</strong>.';
    document.querySelector('#deleteRiderForm button[type="submit"]').disabled = false;
  }
  
  document.getElementById('deleteRiderModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

// Delete rider image (for edit modal)
function deleteRiderImage(riderId) {
  if (confirm('Delete this photo?')) {
    fetch('./functions/delete.php', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'action=delete_rider_image&rider_id=' + riderId
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Failed to delete image: ' + data.message);
      }
    });
  }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>