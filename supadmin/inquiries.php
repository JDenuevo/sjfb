<?php
session_start();
include '../conn.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

// ── AJAX POST handler ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
  header('Content-Type: application/json');
  $action = $_POST['ajax_action'];

  if ($action === 'update_app_status') {
    $id     = intval($_POST['id']);
    $status = in_array($_POST['status'], ['pending','reviewed','shortlisted','rejected','hired'])
              ? $_POST['status'] : 'pending';
    $stmt = $conn->prepare("UPDATE job_applications SET status=? WHERE id=?");
    $stmt->bind_param('si', $status, $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit;
  }

  if ($action === 'mark_inquiry') {
    $id   = intval($_POST['id']);
    $mark = in_array($_POST['mark'], ['good','bad','neutral']) ? $_POST['mark'] : 'neutral';
    if (!isset($_SESSION['inquiry_marks'])) $_SESSION['inquiry_marks'] = [];
    $_SESSION['inquiry_marks'][$id] = $mark;
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'delete_inquiry') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM contact_inquiries WHERE id=?");
    $stmt->bind_param('i', $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit;
  }

  if ($action === 'delete_application') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM job_applications WHERE id=?");
    $stmt->bind_param('i', $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit;
  }

  echo json_encode(['ok' => false]);
  exit;
}

// ── Filters & pagination ──────────────────────────────────────────────────────
$tab        = (isset($_GET['tab']) && $_GET['tab'] === 'applications') ? 'applications' : 'inquiries';
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterType = isset($_GET['type'])   ? $_GET['type']   : '';
$filterStat = isset($_GET['status']) ? $_GET['status'] : '';
$filterMkt  = isset($_GET['market']) ? $_GET['market'] : '';
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

// ── Summary counts ────────────────────────────────────────────────────────────
$totalInq   = $conn->query("SELECT COUNT(*) FROM contact_inquiries")->fetch_row()[0];
$totalApp   = $conn->query("SELECT COUNT(*) FROM job_applications")->fetch_row()[0];
$newToday   = $conn->query("SELECT COUNT(*) FROM contact_inquiries WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
$pendingApp = $conn->query("SELECT COUNT(*) FROM job_applications WHERE status='pending'")->fetch_row()[0];
$marks      = $_SESSION['inquiry_marks'] ?? [];
$goodInq    = count(array_filter($marks, fn($v) => $v === 'good'));

// ── Inquiry query ──────────────────────────────────────────────────────────────
$inqWhere = "WHERE 1=1"; $inqParams = []; $inqTypes = '';
if ($search !== '') {
  $inqWhere .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? OR contact LIKE ?)";
  $t = "%$search%"; $inqTypes .= 'ssssss';
  array_push($inqParams,$t,$t,$t,$t,$t,$t);
}
if ($filterType !== '') { $inqWhere .= " AND form_type=?"; $inqTypes .= 's'; $inqParams[] = $filterType; }
if ($filterMkt  !== '') { $inqWhere .= " AND market=?";    $inqTypes .= 's'; $inqParams[] = $filterMkt;  }

$cnt = $conn->prepare("SELECT COUNT(*) FROM contact_inquiries $inqWhere");
if ($inqParams) $cnt->bind_param($inqTypes, ...$inqParams);
$cnt->execute();
$inqTotal = $cnt->get_result()->fetch_row()[0];
$inqPages = max(1, ceil($inqTotal / $perPage));

$s = $conn->prepare("SELECT * FROM contact_inquiries $inqWhere ORDER BY created_at DESC LIMIT ? OFFSET ?");
$s->bind_param($inqTypes.'ii', ...array_merge($inqParams,[$perPage,$offset]));
$s->execute();
$inquiries = $s->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Application query ─────────────────────────────────────────────────────────
$appWhere = "WHERE 1=1"; $appParams = []; $appTypes = '';
if ($search !== '') {
  $appWhere .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR position LIKE ? OR apply_location LIKE ? OR application_ref LIKE ?)";
  $t = "%$search%"; $appTypes .= 'ssssss';
  array_push($appParams,$t,$t,$t,$t,$t,$t);
}
if ($filterStat !== '') { $appWhere .= " AND status=?"; $appTypes .= 's'; $appParams[] = $filterStat; }

$cnt2 = $conn->prepare("SELECT COUNT(*) FROM job_applications $appWhere");
if ($appParams) $cnt2->bind_param($appTypes, ...$appParams);
$cnt2->execute();
$appTotal = $cnt2->get_result()->fetch_row()[0];
$appPages = max(1, ceil($appTotal / $perPage));

$s2 = $conn->prepare("SELECT * FROM job_applications $appWhere ORDER BY created_at DESC LIMIT ? OFFSET ?");
$s2->bind_param($appTypes.'ii', ...array_merge($appParams,[$perPage,$offset]));
$s2->execute();
$applications = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Dropdown data ─────────────────────────────────────────────────────────────
$formTypesRes = $conn->query("SELECT DISTINCT form_type FROM contact_inquiries ORDER BY form_type");
$formTypes = [];
while ($r = $formTypesRes->fetch_assoc()) $formTypes[] = $r['form_type'];

$mktRes = $conn->query("SELECT DISTINCT market FROM contact_inquiries WHERE market IS NOT NULL AND market!='' ORDER BY market");
$marketList = [];
while ($r = $mktRes->fetch_assoc()) $marketList[] = $r['market'];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inquiries & Applications | St. Joseph Fish Brokerage Inc.</title>

  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <style>
    /* ── Exact same modal system as markets.php ── */
    .modal-overlay {
      position:fixed;inset:0;z-index:999;
      display:flex;align-items:flex-start;justify-content:center;
      background:rgba(0,0,0,.55);backdrop-filter:blur(4px);
      overflow-y:auto;padding:2rem 1rem;
    }
    .modal-overlay.hidden{display:none}
    .modal-box{background:white;width:100%;max-width:52rem;border-radius:1.25rem;
      box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden}
    .modal-box.sm{max-width:28rem}
    .modal-header{display:flex;align-items:center;justify-content:space-between;
      padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#fafafa}
    .modal-header h3{font-size:1.125rem;font-weight:700;color:#111827}
    .modal-header p{font-size:.75rem;color:#6b7280;margin-top:1px}
    .modal-close{width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;
      border-radius:50%;background:#f3f4f6;color:#6b7280;border:none;cursor:pointer;
      transition:background .15s,color .15s}
    .modal-close:hover{background:#fee2e2;color:#dc2626}
    .modal-body{padding:1.5rem;max-height:72vh;overflow-y:auto}
    .modal-footer{padding:1rem 1.5rem;border-top:1px solid #f3f4f6;background:#fafafa;
      display:flex;justify-content:flex-end;gap:.625rem}

    /* ── Same badge system as markets.php ── */
    .badge{display:inline-flex;align-items:center;padding:.2rem .6rem;
      border-radius:9999px;font-size:.7rem;font-weight:600}
    .badge-green {background:#dcfce7;color:#166534}
    .badge-red   {background:#fee2e2;color:#991b1b}
    .badge-gray  {background:#f3f4f6;color:#374151}
    .badge-blue  {background:#dbeafe;color:#1e40af}
    .badge-orange{background:#ffedd5;color:#9a3412}
    .badge-yellow{background:#fef9c3;color:#854d0e}
    .badge-purple{background:#ede9fe;color:#5b21b6}

    /* ── Quality mark pill ── */
    .mark-pill{display:inline-flex;align-items:center;gap:4px;padding:.2rem .6rem;
      border-radius:9999px;font-size:.7rem;font-weight:600}
    .mark-good   {background:#dcfce7;color:#166534}
    .mark-bad    {background:#fee2e2;color:#991b1b}
    .mark-neutral{background:#f3f4f6;color:#9ca3af}

    /* ── Row hover (mirrors markets.php market-row) ── */
    .data-row{transition:all .15s;border-left:3px solid transparent}
    .data-row:hover{background-color:#fafafa;border-left-color:#ea580c}

    /* ── Tab underline ── */
    .tab-link{display:inline-flex;align-items:center;gap:.5rem;
      padding:.75rem 0;font-size:.875rem;font-weight:600;color:#6b7280;
      border-bottom:2px solid transparent;cursor:pointer;
      transition:color .15s,border-color .15s;
      background:none;border-top:none;border-left:none;border-right:none}
    .tab-link.active{color:#ea580c;border-bottom-color:#ea580c}

    /* ── Expand row ── */
    .expand-row{display:none}
    .expand-row.open{display:table-row}

    /* ── Btn helpers (same as markets.php) ── */
    .btn-primary  {padding:.5rem 1.25rem;background:#ea580c;color:white;border-radius:.625rem;border:none;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s,transform .1s}
    .btn-primary:hover{background:#c2410c}
    .btn-primary:active{transform:scale(.97)}
    .btn-secondary{padding:.5rem 1.25rem;background:white;color:#374151;border-radius:.625rem;border:1px solid #e5e7eb;font-size:.875rem;font-weight:500;cursor:pointer;transition:background .15s}
    .btn-secondary:hover{background:#f9fafb}

    .form-label{display:block;font-size:.8125rem;font-weight:600;color:#374151;margin-bottom:.375rem}
    .form-input{width:100%;padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:.5rem;
      font-size:.875rem;color:#111827;transition:border-color .15s,box-shadow .15s;outline:none}
    .form-input:focus{border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,.1)}
    .section-title{font-size:.9375rem;font-weight:700;color:#111827;border-left:3px solid #ea580c;
      padding-left:.625rem;margin:1.25rem 0 .75rem}
  </style>
</head>

<body class="bg-gray-50">
  <?php include('./components/header.php'); ?>

  <!-- Mobile breadcrumb — exact copy from markets.php -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none"
              aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar"
              data-hs-overlay="#hs-application-sidebar">
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/></svg>
      </button>
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800">Navigation
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate">Inquiries & Applications</li>
      </ol>
    </div>
  </div>

  <?php include('./components/sidebar.php'); ?>

  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <?php if (!empty($_SESSION['message'])):
        $msg = $_SESSION['message'];
        $cls = $msg['type'] === 'success' ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
        echo "<div class='mt-2 {$cls} text-sm rounded-xl p-4 flex items-center gap-2' role='alert'>
                <span class='font-bold'>".ucfirst(htmlspecialchars($msg['type']))."!</span> ".htmlspecialchars($msg['text'])."
              </div>";
        unset($_SESSION['message']);
      endif; ?>

      <!-- ══ STAT CARDS ══════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <?php $statCards = [
          ['Total Inquiries',    $totalInq,   'badge-blue',   'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
          ['Received Today',     $newToday,   'badge-orange', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
          ['Good Business Leads',$goodInq,    'badge-green',  'M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3'],
          ['Job Applications',   $totalApp,   'badge-purple', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
          ['Pending Review',     $pendingApp, 'badge-yellow', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        ];
        foreach ($statCards as [$label,$value,$badgeCls,$iconPath]): ?>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide truncate"><?= $label ?></p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($value) ?></p>
          </div>
          <span class="badge <?= $badgeCls ?> p-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">
              <path d="<?= $iconPath ?>"/>
            </svg>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ══ MAIN PANEL (same card style as markets.php) ════════════════════ -->
      <div class="space-y-4">
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">

          <!-- Tab bar -->
          <div class="px-6 flex gap-6 border-b border-gray-100">
            <button onclick="switchTab('inquiries')" id="tab-inquiries"
                    class="tab-link <?= $tab==='inquiries' ? 'active' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              Contact Inquiries
              <span class="badge badge-blue"><?= $inqTotal ?></span>
            </button>
            <button onclick="switchTab('applications')" id="tab-applications"
                    class="tab-link <?= $tab==='applications' ? 'active' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Job Applications
              <span class="badge badge-purple"><?= $appTotal ?></span>
            </button>
          </div>

          <!-- Filter bar -->
          <form method="GET" class="px-6 py-4 flex flex-wrap gap-3 items-end bg-gray-50/50 border-b border-gray-100">
            <input type="hidden" name="tab" id="tabHidden" value="<?= htmlspecialchars($tab) ?>">
            <div class="flex-1 min-w-[180px]">
              <label class="form-label">Search</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Name, email, subject…" class="form-input ps-8 py-2">
              </div>
            </div>

            <div id="inqFiltersWrap" class="flex gap-3 flex-wrap <?= $tab==='applications' ? 'hidden' : '' ?>">
              <div>
                <label class="form-label">Form Type</label>
                <select name="type" class="form-input py-2 pe-8">
                  <option value="">All Types</option>
                  <?php foreach ($formTypes as $ft): ?>
                  <option value="<?= htmlspecialchars($ft) ?>" <?= $filterType===$ft?'selected':'' ?>>
                    <?= ucfirst(str_replace('_',' ',$ft)) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if (!empty($marketList)): ?>
              <div>
                <label class="form-label">Market</label>
                <select name="market" class="form-input py-2 pe-8">
                  <option value="">All Markets</option>
                  <?php foreach ($marketList as $m): ?>
                  <option value="<?= htmlspecialchars($m) ?>" <?= $filterMkt===$m?'selected':'' ?>><?= ucfirst(htmlspecialchars($m)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
            </div>

            <div id="appFiltersWrap" class="flex gap-3 flex-wrap <?= $tab==='inquiries' ? 'hidden' : '' ?>">
              <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input py-2 pe-8">
                  <option value="">All Statuses</option>
                  <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $sv): ?>
                  <option value="<?= $sv ?>" <?= $filterStat===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="flex items-end gap-2">
              <button type="submit" class="btn-primary py-2 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                Filter
              </button>
              <?php if ($search||$filterType||$filterStat||$filterMkt): ?>
              <a href="?tab=<?= $tab ?>" class="btn-secondary py-2 text-sm flex items-center gap-1">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                Clear
              </a>
              <?php endif; ?>
            </div>
          </form>

          <!-- ══════════════════════════════════════════════════════════════
               INQUIRIES TABLE
          ════════════════════════════════════════════════════════════════ -->
          <div id="panel-inquiries" class="<?= $tab==='applications' ? 'hidden' : '' ?>">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-100">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name / Sender</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Subject & Preview</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Market</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Quality Mark</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <?php if (empty($inquiries)): ?>
                  <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                      <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#ea580c" stroke-width="1.5"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-700">No inquiries found</p>
                        <p class="text-xs text-gray-400">Try adjusting your search or filters</p>
                      </div>
                    </td>
                  </tr>
                  <?php else: foreach ($inquiries as $inq):
                    $mark = $marks[$inq['id']] ?? 'neutral';
                    $markLabel = ['good'=>'👍 Good Lead','bad'=>'👎 Bad/Spam','neutral'=>'— Unmarked'][$mark];
                  ?>
                  <!-- Main row — clickable to expand -->
                  <tr class="data-row cursor-pointer hover:bg-orange-50/20 transition-colors" onclick="toggleRow('inq-<?= $inq['id'] ?>')">
                    <td class="px-6 py-4">
                      <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($inq['first_name'].' '.$inq['last_name']) ?></p>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <span class="badge <?= $inq['form_type']==='inquiry' ? 'badge-blue' : 'badge-purple' ?>">
                          <?= ucfirst(str_replace('_',' ',$inq['form_type'])) ?>
                        </span>
                        <?php if ($inq['sender_type']): ?>
                        <span class="badge badge-gray"><?= htmlspecialchars($inq['sender_type']) ?></span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-gray-700"><?= htmlspecialchars($inq['email']) ?></p>
                      <?php if ($inq['contact']): ?>
                      <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($inq['contact']) ?></p>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 max-w-[220px]">
                      <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($inq['subject']??'—') ?></p>
                      <?php if ($inq['message']): ?>
                      <p class="text-xs text-gray-400 truncate mt-0.5"><?= htmlspecialchars(mb_substr($inq['message'],0,55)) ?>…</p>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php if ($inq['market']): ?>
                      <span class="badge badge-orange"><?= htmlspecialchars($inq['market']) ?></span>
                      <?php else: ?>
                      <span class="text-xs text-gray-400">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="mark-pill mark-<?= $mark ?>" id="mark-pill-<?= $inq['id'] ?>"><?= $markLabel ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-gray-600"><?= date('M d, Y', strtotime($inq['created_at'])) ?></p>
                      <p class="text-xs text-gray-400"><?= date('g:i A', strtotime($inq['created_at'])) ?></p>
                    </td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                      <div class="flex items-center justify-end gap-1.5">
                        <button onclick="markInquiry(<?= $inq['id'] ?>,'good')" title="Mark Good Lead"
                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>
                        </button>
                        <button onclick="markInquiry(<?= $inq['id'] ?>,'bad')" title="Mark Bad/Spam"
                                class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/></svg>
                        </button>
                        <button onclick="openInqModal(<?= $inq['id'] ?>)" title="View Full Details"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                        </button>
                        <a href="mailto:<?= htmlspecialchars($inq['email']) ?>?subject=Re: <?= rawurlencode($inq['subject']??'Your Inquiry') ?>"
                           title="Reply by Email"
                           class="p-2 text-orange-500 hover:bg-orange-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
                        </a>
                        <button onclick="openDeleteModal('inquiry',<?= $inq['id'] ?>,'<?= htmlspecialchars(addslashes($inq['first_name'].' '.$inq['last_name'])) ?>')"
                                title="Delete" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <!-- Expandable detail row -->
                  <tr id="inq-<?= $inq['id'] ?>" class="expand-row">
                    <td colspan="7" class="px-6 py-5 bg-orange-50/30 border-b border-orange-100/50">
                      <div class="grid sm:grid-cols-3 gap-5">
                        <div class="sm:col-span-2">
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Full Message</p>
                          <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-sm text-gray-700 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($inq['message']??'—') ?></div>
                        </div>
                        <div>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Details</p>
                          <div class="space-y-2 text-xs mb-4">
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Sender type</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($inq['sender_type']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Market</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($inq['market']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">IP Address</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($inq['ip_address']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Form type</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($inq['form_type']) ?></span></div>
                          </div>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Mark Quality</p>
                          <div class="flex gap-1.5">
                            <button onclick="markInquiry(<?= $inq['id'] ?>,'good')"
                                    class="flex-1 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-xl transition">👍 Good</button>
                            <button onclick="markInquiry(<?= $inq['id'] ?>,'bad')"
                                    class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-xl transition">👎 Bad</button>
                            <button onclick="markInquiry(<?= $inq['id'] ?>,'neutral')"
                                    class="flex-1 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-medium rounded-xl transition">↩</button>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php if ($inqPages > 1): ?>
            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
              <p class="text-sm text-gray-500">
                Showing <span class="font-semibold text-gray-800"><?= min($offset+1,$inqTotal) ?>–<?= min($offset+$perPage,$inqTotal) ?></span>
                of <span class="font-semibold text-gray-800"><?= $inqTotal ?></span> inquiries
              </p>
              <div class="flex items-center gap-1.5">
                <?php for ($p=1;$p<=$inqPages;$p++): ?>
                <a href="?tab=inquiries&page=<?=$p?>&search=<?=urlencode($search)?>&type=<?=urlencode($filterType)?>&market=<?=urlencode($filterMkt)?>"
                   class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
                     <?= $p===$page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
                  <?= $p ?>
                </a>
                <?php endfor; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- ══════════════════════════════════════════════════════════════
               APPLICATIONS TABLE
          ════════════════════════════════════════════════════════════════ -->
          <div id="panel-applications" class="<?= $tab==='inquiries' ? 'hidden' : '' ?>">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-100">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Applicant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Experience</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <?php if (empty($applications)): ?>
                  <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                      <div class="flex flex-col items-center gap-3">
                        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#ea580c" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-700">No applications found</p>
                        <p class="text-xs text-gray-400">Try adjusting your search or filters</p>
                      </div>
                    </td>
                  </tr>
                  <?php else: foreach ($applications as $app):
                    $sBadge = ['pending'=>'badge-yellow','reviewed'=>'badge-blue','shortlisted'=>'badge-green','rejected'=>'badge-red','hired'=>'badge-purple'][$app['status']] ?? 'badge-gray';
                  ?>
                  <!-- Main row -->
                  <tr class="data-row cursor-pointer hover:bg-orange-50/20 transition-colors" onclick="toggleRow('app-<?= $app['id'] ?>')">
                    <td class="px-6 py-4">
                      <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($app['first_name'].' '.$app['last_name']) ?></p>
                      <p class="text-xs text-gray-400 mt-0.5">Age: <?= htmlspecialchars($app['age']??'—') ?></p>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-gray-700"><?= htmlspecialchars($app['email']) ?></p>
                      <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($app['contact']) ?></p>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($app['position']) ?></p>
                      <?php if ($app['position_other']): ?>
                      <p class="text-xs text-gray-400"><?= htmlspecialchars($app['position_other']) ?></p>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-orange"><?= htmlspecialchars($app['apply_location']) ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="badge badge-gray"><?= htmlspecialchars($app['experience_years']) ?></span>
                    </td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                      <select onchange="updateAppStatus(<?= $app['id'] ?>, this.value, this)"
                              class="text-xs py-1.5 px-2.5 rounded-lg border border-gray-200 focus:ring-1 focus:ring-orange-500 outline-none cursor-pointer font-semibold bg-white">
                        <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $sv): ?>
                        <option value="<?= $sv ?>" <?= $app['status']===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-gray-600"><?= date('M d, Y', strtotime($app['created_at'])) ?></p>
                      <p class="text-xs text-gray-400"><?= date('g:i A', strtotime($app['created_at'])) ?></p>
                    </td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                      <div class="flex items-center justify-end gap-1.5">
                        <button onclick="openAppModal(<?= $app['id'] ?>)" title="View Details"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                        </button>
                        <a href="mailto:<?= htmlspecialchars($app['email']) ?>?subject=Re: Your Application for <?= rawurlencode($app['position']) ?>"
                           title="Reply by Email"
                           class="p-2 text-orange-500 hover:bg-orange-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
                        </a>
                        <button onclick="openDeleteModal('application',<?= $app['id'] ?>,'<?= htmlspecialchars(addslashes($app['first_name'].' '.$app['last_name'])) ?>')"
                                title="Delete" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <!-- Expandable detail row -->
                  <tr id="app-<?= $app['id'] ?>" class="expand-row">
                    <td colspan="8" class="px-6 py-5 bg-orange-50/30 border-b border-orange-100/50">
                      <div class="grid sm:grid-cols-3 gap-5 text-sm">
                        <div class="sm:col-span-2">
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Work History</p>
                          <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-sm text-gray-700 whitespace-pre-wrap leading-relaxed mb-4"><?= htmlspecialchars($app['work_history']??'—') ?></div>
                          <?php if ($app['extra_notes']): ?>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Extra Notes</p>
                          <div class="bg-white rounded-xl p-4 border border-gray-100 text-sm text-gray-600"><?= htmlspecialchars($app['extra_notes']) ?></div>
                          <?php endif; ?>
                        </div>
                        <div>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Details</p>
                          <div class="space-y-2 text-xs mb-4">
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Address</span><span class="font-semibold text-gray-800 text-right"><?= htmlspecialchars($app['address']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Start Date</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($app['start_date']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Work Type</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($app['work_type']??'—') ?></span></div>
                            <div class="flex justify-between gap-2"><span class="text-gray-500">Salary</span><span class="font-semibold text-gray-800"><?= htmlspecialchars($app['expected_salary']??'—') ?></span></div>
                            <div><span class="text-gray-500">Ref: </span><code class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-700"><?= htmlspecialchars($app['application_ref']) ?></code></div>
                          </div>
                          <?php
                          $tags = [];
                          if ($app['industry_tags']) {
                            $decoded = json_decode($app['industry_tags'], true);
                            if (is_array($decoded)) $tags = $decoded;
                            else $tags = array_filter(array_map('trim', explode(',', $app['industry_tags'])));
                          }
                          if (!empty($tags)): ?>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Industry Tags</p>
                          <div class="flex flex-wrap gap-1.5 mb-4">
                            <?php foreach ($tags as $tag): ?>
                            <span class="badge badge-orange"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                          </div>
                          <?php endif; ?>
                          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Quick Actions</p>
                          <div class="flex flex-wrap gap-1.5">
                            <?php foreach (['shortlisted','rejected','hired'] as $qs): ?>
                            <button onclick="updateAppStatus(<?= $app['id'] ?>,'<?= $qs ?>',null)"
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-xl border transition
                                      <?= $app['status']===$qs ? 'bg-orange-600 text-white border-orange-600' : 'bg-white border-gray-200 text-gray-700 hover:border-orange-400 hover:text-orange-700' ?>">
                              <?= ucfirst($qs) ?>
                            </button>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php if ($appPages > 1): ?>
            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
              <p class="text-sm text-gray-500">
                Showing <span class="font-semibold text-gray-800"><?= min($offset+1,$appTotal) ?>–<?= min($offset+$perPage,$appTotal) ?></span>
                of <span class="font-semibold text-gray-800"><?= $appTotal ?></span> applications
              </p>
              <div class="flex items-center gap-1.5">
                <?php for ($p=1;$p<=$appPages;$p++): ?>
                <a href="?tab=applications&page=<?=$p?>&search=<?=urlencode($search)?>&status=<?=urlencode($filterStat)?>"
                   class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
                     <?= $p===$page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
                  <?= $p ?>
                </a>
                <?php endfor; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

        </div><!-- /card -->
      </div>

    </div><!-- /p-4 -->
  </div><!-- /lg:ps-64 -->

  <!-- ══ INQUIRY DETAIL MODAL ════════════════════════════════════════════════ -->
  <div id="inqModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3 id="inqModalTitle">Inquiry Details</h3>
          <p id="inqModalSub"></p>
        </div>
        <button class="modal-close" onclick="closeModal('inqModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div id="inqModalBody" class="modal-body"></div>
      <div id="inqModalFooter" class="modal-footer"></div>
    </div>
  </div>

  <!-- ══ APPLICATION DETAIL MODAL ═══════════════════════════════════════════ -->
  <div id="appModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3 id="appModalTitle">Application Details</h3>
          <p id="appModalSub"></p>
        </div>
        <button class="modal-close" onclick="closeModal('appModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div id="appModalBody" class="modal-body"></div>
      <div id="appModalFooter" class="modal-footer"></div>
    </div>
  </div>

  <!-- ══ DELETE CONFIRM MODAL ════════════════════════════════════════════════ -->
  <div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-box sm">
      <div class="modal-header">
        <div><h3>Confirm Delete</h3><p>This action cannot be undone</p></div>
        <button class="modal-close" onclick="closeModal('deleteModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body text-center">
        <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
        </div>
        <p id="deleteModalName" class="text-sm font-semibold text-gray-800 mb-1"></p>
        <p class="text-xs text-red-500 mb-5">This will permanently remove the record from the database.</p>
        <div class="flex gap-3 justify-center">
          <button onclick="closeModal('deleteModal')" class="btn-secondary">Cancel</button>
          <button id="deleteConfirmBtn" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ TOAST ═══════════════════════════════════════════════════════════════ -->
  <style>@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}</style>
  <div id="toastEl" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9999;
    align-items:center;gap:10px;padding:14px 20px;border-radius:12px;
    color:#fff;font-size:.875rem;font-weight:500;
    box-shadow:0 8px 24px rgba(0,0,0,.2);animation:slideUp .25s ease;min-width:200px"></div>

  <script>
  // ── JSON data islands ──────────────────────────────────────────────────────
  var INQ_DATA  = <?= json_encode(array_column($inquiries,    null, 'id'), JSON_HEX_TAG|JSON_HEX_APOS) ?>;
  var APP_DATA  = <?= json_encode(array_column($applications, null, 'id'), JSON_HEX_TAG|JSON_HEX_APOS) ?>;
  var INQ_MARKS = <?= json_encode($marks, JSON_HEX_TAG|JSON_HEX_APOS) ?>;

  // ── Modal helpers — exact pattern from markets.php ─────────────────────────
  function openModal(id)  { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
  function closeModal(id) { document.getElementById(id).classList.add('hidden');    document.body.style.overflow = ''; }
  document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
  });

  // ── Tab switch ─────────────────────────────────────────────────────────────
  function switchTab(t) {
    ['inquiries','applications'].forEach(function(n) {
      document.getElementById('panel-'+n).classList.toggle('hidden', n !== t);
      document.getElementById('tab-'+n).classList.toggle('active', n === t);
    });
    document.getElementById('inqFiltersWrap').classList.toggle('hidden', t === 'applications');
    document.getElementById('appFiltersWrap').classList.toggle('hidden', t === 'inquiries');
    document.getElementById('tabHidden').value = t;
  }

  // ── Row expand ─────────────────────────────────────────────────────────────
  function toggleRow(id) {
    var r = document.getElementById(id);
    if (r) r.classList.toggle('open');
  }

  // ── Mark inquiry quality ───────────────────────────────────────────────────
  function markInquiry(id, mark) {
    var fd = new FormData();
    fd.append('ajax_action', 'mark_inquiry');
    fd.append('id', id);
    fd.append('mark', mark);
    fetch('', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (!d.ok) return toast('Failed to save mark', '#dc2626');
        INQ_MARKS[id] = mark;
        var labels = { good: '👍 Good Lead', bad: '👎 Bad/Spam', neutral: '— Unmarked' };
        var pill = document.getElementById('mark-pill-' + id);
        if (pill) {
          pill.textContent = labels[mark];
          pill.className   = 'mark-pill mark-' + mark;
        }
        var msgs   = { good: '✅ Marked as Good Business Lead', bad: '🚫 Marked as Bad / Spam', neutral: '↩ Mark cleared' };
        var colors = { good: '#16a34a', bad: '#dc2626', neutral: '#374151' };
        toast(msgs[mark], colors[mark]);
      });
  }

  // ── Update application status ──────────────────────────────────────────────
  function updateAppStatus(id, status, selectEl) {
    var fd = new FormData();
    fd.append('ajax_action', 'update_app_status');
    fd.append('id', id);
    fd.append('status', status);
    fetch('', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.ok) toast('Status updated to "' + status + '"');
        else      toast('Failed to update status', '#dc2626');
      });
  }

  // ── Delete modal ───────────────────────────────────────────────────────────
  var _pendingDelete = null;
  function openDeleteModal(type, id, name) {
    document.getElementById('deleteModalName').textContent = 'Delete ' + type + ' from "' + name + '"?';
    _pendingDelete = { type: type, id: id };
    openModal('deleteModal');
  }
  document.getElementById('deleteConfirmBtn').addEventListener('click', function() {
    if (!_pendingDelete) return;
    var action = _pendingDelete.type === 'inquiry' ? 'delete_inquiry' : 'delete_application';
    var fd = new FormData();
    fd.append('ajax_action', action);
    fd.append('id', _pendingDelete.id);
    fetch('', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        closeModal('deleteModal');
        if (d.ok) { toast('Deleted successfully'); setTimeout(function() { location.reload(); }, 700); }
        else toast('Delete failed', '#dc2626');
        _pendingDelete = null;
      });
  });

  // ── Open inquiry detail modal ──────────────────────────────────────────────
  function openInqModal(id) {
    var d = INQ_DATA[id]; if (!d) return;
    var mark = INQ_MARKS[id] || 'neutral';
    var markLabels = { good: '👍 Good Lead', bad: '👎 Bad / Spam', neutral: '— Unmarked' };
    var markBadge  = { good: 'badge-green',  bad: 'badge-red',     neutral: 'badge-gray'  };
    document.getElementById('inqModalTitle').textContent = d.first_name + ' ' + d.last_name;
    document.getElementById('inqModalSub').textContent   = d.email + (d.contact ? ' · ' + d.contact : '');
    document.getElementById('inqModalBody').innerHTML = `
      <div class="flex flex-wrap gap-2 mb-4">
        <span class="badge ${d.form_type==='inquiry'?'badge-blue':'badge-purple'}">${esc(d.form_type?.replace('_',' '))}</span>
        ${d.sender_type ? '<span class="badge badge-gray">'+esc(d.sender_type)+'</span>' : ''}
        ${d.market ? '<span class="badge badge-orange">📍 '+esc(d.market)+'</span>' : ''}
        <span class="badge ${markBadge[mark]}" id="inqModalMarkBadge">${markLabels[mark]}</span>
      </div>
      <p class="section-title">Subject</p>
      <p class="font-semibold text-gray-800 mb-4">${esc(d.subject||'—')}</p>
      <p class="section-title">Message</p>
      <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-100 leading-relaxed mb-4">${esc(d.message||'—')}</div>
      <div class="grid sm:grid-cols-2 gap-3 text-sm text-gray-600 mb-4">
        <div><span class="font-semibold text-gray-700">Received:</span> ${new Date(d.created_at.replace(' ','T')).toLocaleString()}</div>
        <div><span class="font-semibold text-gray-700">IP Address:</span> ${esc(d.ip_address||'—')}</div>
      </div>
      <p class="section-title">Mark Quality</p>
      <div class="flex gap-2">
        <button onclick="markInquiry(${id},'good'); updateInqModalMark('good')"
                class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">👍 Good Business Lead</button>
        <button onclick="markInquiry(${id},'bad'); updateInqModalMark('bad')"
                class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition">👎 Bad / Spam</button>
        <button onclick="markInquiry(${id},'neutral'); updateInqModalMark('neutral')"
                class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-xl transition">↩ Clear</button>
      </div>`;
    document.getElementById('inqModalFooter').innerHTML = `
      <button onclick="closeModal('inqModal')" class="btn-secondary">Close</button>
      <a href="mailto:${esc(d.email)}?subject=Re: ${encodeURIComponent(d.subject||'Your Inquiry')}"
         class="btn-primary inline-flex items-center gap-2">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
        Reply by Email
      </a>`;
    openModal('inqModal');
  }
  function updateInqModalMark(mark) {
    var el = document.getElementById('inqModalMarkBadge');
    if (!el) return;
    var labels = { good: '👍 Good Lead', bad: '👎 Bad / Spam', neutral: '— Unmarked' };
    var badges = { good: 'badge badge-green', bad: 'badge badge-red', neutral: 'badge badge-gray' };
    el.textContent = labels[mark];
    el.className   = badges[mark];
  }

  // ── Open application detail modal ──────────────────────────────────────────
  function openAppModal(id) {
    var d = APP_DATA[id]; if (!d) return;
    var sBadges = { pending:'badge-yellow', reviewed:'badge-blue', shortlisted:'badge-green', rejected:'badge-red', hired:'badge-purple' };
    document.getElementById('appModalTitle').textContent = d.first_name + ' ' + d.last_name;
    document.getElementById('appModalSub').textContent   = d.email + ' · ' + d.contact;
    var tags = '';
    try {
      var t = JSON.parse(d.industry_tags || '[]');
      if (Array.isArray(t)) tags = t.map(function(x) { return '<span class="badge badge-orange">'+esc(x)+'</span>'; }).join('');
    } catch(e) {
      if (d.industry_tags) tags = d.industry_tags.split(',').map(function(x){ return '<span class="badge badge-orange">'+esc(x.trim())+'</span>'; }).join('');
    }
    document.getElementById('appModalBody').innerHTML = `
      <div class="flex flex-wrap gap-2 mb-5">
        <span class="badge badge-orange">📍 ${esc(d.apply_location)}</span>
        <span class="badge badge-blue">🎯 ${esc(d.position)}${d.position_other?' ('+esc(d.position_other)+')':''}</span>
        <span class="badge badge-gray">⏱ ${esc(d.experience_years)}</span>
        <span class="badge ${sBadges[d.status]||'badge-gray'}">${d.status}</span>
      </div>
      <div class="grid sm:grid-cols-2 gap-4 text-sm mb-5">
        <div class="space-y-2">
          <div><span class="font-semibold text-gray-600">Age:</span> ${esc(d.age||'—')}</div>
          <div><span class="font-semibold text-gray-600">Address:</span> ${esc(d.address||'—')}</div>
          <div><span class="font-semibold text-gray-600">Start Date:</span> ${esc(d.start_date||'—')}</div>
        </div>
        <div class="space-y-2">
          <div><span class="font-semibold text-gray-600">Work Type:</span> ${esc(d.work_type||'—')}</div>
          <div><span class="font-semibold text-gray-600">Expected Salary:</span> ${esc(d.expected_salary||'—')}</div>
          <div><span class="font-semibold text-gray-600">Ref:</span> <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">${esc(d.application_ref)}</code></div>
        </div>
      </div>
      ${tags ? '<div class="mb-4"><p class="section-title">Industry Tags</p><div class="flex flex-wrap gap-1.5">'+tags+'</div></div>' : ''}
      <p class="section-title">Work History</p>
      <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-100 leading-relaxed mb-4">${esc(d.work_history||'—')}</div>
      ${d.extra_notes ? '<p class="section-title">Extra Notes</p><div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 border border-gray-100 mb-4">'+esc(d.extra_notes)+'</div>' : ''}
      <p class="section-title">Update Status</p>
      <div class="flex flex-wrap gap-2">
        ${['pending','reviewed','shortlisted','rejected','hired'].map(function(sv) {
          return '<button onclick="updateAppStatus('+id+',\''+sv+'\',null)" class="px-3 py-2 text-xs font-semibold rounded-xl border transition '+(d.status===sv?'bg-orange-600 text-white border-orange-600':'bg-white border-gray-200 text-gray-700 hover:border-orange-400 hover:text-orange-700')+'">'+sv.charAt(0).toUpperCase()+sv.slice(1)+'</button>';
        }).join('')}
      </div>`;
    document.getElementById('appModalFooter').innerHTML = `
      <button onclick="closeModal('appModal')" class="btn-secondary">Close</button>
      <a href="mailto:${esc(d.email)}?subject=Re: Your Application for ${encodeURIComponent(d.position)}"
         class="btn-primary inline-flex items-center gap-2">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
        Reply to Applicant
      </a>`;
    openModal('appModal');
  }

  // ── Toast ──────────────────────────────────────────────────────────────────
  function toast(msg, bg) {
    var el = document.getElementById('toastEl');
    el.textContent   = msg;
    el.style.background = bg || '#1f2937';
    el.style.display = 'flex';
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.style.display = 'none'; }, 2800);
  }

  // ── HTML escape ────────────────────────────────────────────────────────────
  function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  </script>

  <?php $conn->close(); ?>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>