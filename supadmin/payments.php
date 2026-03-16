<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// Pagination setup
$itemsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Base query
$whereConditions = [];
$params = [];
$types = "";

// Payment Status Filter
if (!empty($_GET['payment_status'])) {
    $whereConditions[] = "p.payment_status = ?";
    $params[] = $_GET['payment_status'];
    $types .= "s";
}

// Search Filter
if (!empty($_GET['search'])) {
    $whereConditions[] = "(o.order_code LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ? OR p.provider_id LIKE ? OR p.billing_email LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "sssss";
}

// Build WHERE clause
$whereSQL = "";
if (count($whereConditions) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereConditions);
}

// Count total records
$countSql = "
    SELECT COUNT(*) as total 
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    $whereSQL
";

$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$countResult = $stmt->get_result()->fetch_assoc();
$totalItems = $countResult['total'];
$totalPages = ceil($totalItems / $itemsPerPage);
$stmt->close();

// Main query with pagination
$sql = "
    SELECT p.*, o.order_code, o.first_name, o.last_name, o.payment_method
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    $whereSQL
    ORDER BY p.created_at DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);

// Add pagination params
$paramsWithPagination = $params; 
$typesWithPagination = $types . "ii";
$paramsWithPagination[] = $offset;
$paramsWithPagination[] = $itemsPerPage;

if ($types) {
    $stmt->bind_param($typesWithPagination, ...$paramsWithPagination);
} else {
    $stmt->bind_param("ii", $offset, $itemsPerPage);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <style>
    /* Import products.php design language */
    .payment-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .payment-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

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
      width: 100%; max-width: 42rem;
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

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-gray { background: #f3f4f6; color: #374151; }

    .stats-card {
      border-radius: 1rem;
      padding: 1.25rem;
      transition: transform 0.2s ease;
    }
    .stats-card:hover { transform: translateY(-2px); }

    .section-title {
      font-size: 0.9375rem; font-weight: 700; color: #111827;
      border-left: 3px solid #ea580c;
      padding-left: 0.625rem;
      margin: 1.25rem 0 0.75rem;
    }
  </style>
</head>

<body class="bg-gray-50">
  
  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- Sidebar -->
  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      
      <?php
        if (!empty($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
          echo '
          <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
              <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
          </div>';
          unset($_SESSION['message']);
        }
      ?>
      
      <!-- Payment List -->
      <?php include('./components/payment_list.php'); ?>

    </div>
  </div>

  <!-- Payment Detail Modal -->
  <div id="paymentDetailModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Payment Details</h3>
          <p>Transaction Information</p>
        </div>
        <button onclick="closePaymentModal()" class="modal-close">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div id="paymentDetailContent" class="modal-body">
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading payment details...
        </div>
      </div>
    </div>
  </div>

  <script>
    function openPaymentModal(paymentId) {
      const modal = document.getElementById('paymentDetailModal');
      const content = document.getElementById('paymentDetailContent');
      modal.classList.remove('hidden');
      
      fetch('./functions/fetch_payments.php?payment_id=' + paymentId)
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load payment.</p>';
            return;
          }
          const p = data.payment;
          
          // Status badge colors
          const statusColors = {
            'Paid': 'badge-green',
            'Pending': 'badge-yellow',
            'Failed': 'badge-red',
            'Refunded': 'badge-blue'
          };
          const statusClass = statusColors[p.payment_status] || 'badge-gray';
          
          content.innerHTML = `
            <div class="space-y-5">
              <!-- Order Info -->
              <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                  <span class="text-sm text-gray-500">Order</span>
                  <div class="text-xl font-bold text-orange-600">${p.order_code}</div>
                  <div class="text-sm text-gray-700 mt-1">${p.billing_name || (p.first_name + ' ' + p.last_name)}</div>
                </div>
                <span class="badge ${statusClass} text-sm px-3 py-1.5">${p.payment_status}</span>
              </div>
              
              <!-- Amount Cards -->
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                  <div class="text-xs text-green-600 font-medium mb-1">Gross Amount</div>
                  <div class="text-xl font-bold text-green-700">₱${parseFloat(p.gross_amount).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                </div>
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                  <div class="text-xs text-blue-600 font-medium mb-1">Net Amount</div>
                  <div class="text-xl font-bold text-blue-700">₱${parseFloat(p.net_amount || p.gross_amount).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                </div>
              </div>
              
              ${p.refunded_amount > 0 ? `
              <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                <div class="text-xs text-red-600 font-medium mb-1">Refunded Amount</div>
                <div class="text-xl font-bold text-red-700">₱${parseFloat(p.refunded_amount).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
              </div>
              ` : ''}
              
              <!-- Payment Details -->
              <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                <div class="flex justify-between py-1.5 border-b border-gray-200 last:border-0">
                  <span class="text-xs text-gray-500">Provider ID</span>
                  <span class="text-xs font-mono text-gray-700 max-w-[200px] truncate">${p.provider_id || '—'}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-200 last:border-0">
                  <span class="text-xs text-gray-500">Payment Method</span>
                  <span class="text-xs font-medium text-gray-700">${p.payment_method?.toUpperCase() || '—'}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-200 last:border-0">
                  <span class="text-xs text-gray-500">Mode</span>
                  <span class="text-xs font-medium text-gray-700">${p.mode === 'live' ? '🟢 Live' : '🔵 Test'}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-200 last:border-0">
                  <span class="text-xs text-gray-500">Currency</span>
                  <span class="text-xs font-medium text-gray-700">${p.currency || 'PHP'}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-gray-200 last:border-0">
                  <span class="text-xs text-gray-500">Billing Email</span>
                  <span class="text-xs font-medium text-gray-700 truncate max-w-[200px]">${p.billing_email || p.order_email || '—'}</span>
                </div>
                ${p.paid_at ? `
                <div class="flex justify-between py-1.5">
                  <span class="text-xs text-gray-500">Paid At</span>
                  <span class="text-xs font-medium text-gray-700">${new Date(p.paid_at).toLocaleString('en-PH')}</span>
                </div>
                ` : ''}
              </div>
              
              <!-- Action Buttons -->
              <div class="flex gap-3 pt-3">
                <a href="./order_manage.php?order_id=${p.order_id}" 
                   class="flex-1 py-2.5 px-4 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-xl text-center transition">
                  View Order
                </a>
                <button onclick="closePaymentModal()" 
                        class="flex-1 py-2.5 px-4 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                  Close
                </button>
              </div>
            </div>
          `;
        })
        .catch(() => {
          content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load payment details.</p>';
        });
    }

    function closePaymentModal() {
      document.getElementById('paymentDetailModal').classList.add('hidden');
    }

    // Close on backdrop click
    document.getElementById('paymentDetailModal')?.addEventListener('click', function(e) {
      if (e.target === this) closePaymentModal();
    });
  </script>

  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>