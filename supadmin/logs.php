<?php
/**
 * supadmin/logs.php — Activity log viewer (compact table)
 */
session_start();
include '../conn.php';

if (!isset($_SESSION['loggedinassupadmin']) || $_SESSION['loggedinassupadmin'] !== true || !isset($_SESSION['account_id'])) {
    header('Location: ../sign_in.php'); exit;
}

// ── Filters ────────────────────────────────────────────────────────────────
$filterEntity = trim($_GET['entity_type'] ?? '');
$filterRole   = trim($_GET['user_type']   ?? '');
$filterAction = trim($_GET['action']      ?? '');
$filterFrom   = trim($_GET['from']        ?? '');
$filterTo     = trim($_GET['to']          ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;

// ── WHERE ──────────────────────────────────────────────────────────────────
$where = []; $params = []; $types = '';
if ($filterEntity) { $where[] = 'al.entity_type = ?';       $params[] = $filterEntity;         $types .= 's'; }
if ($filterRole)   { $where[] = 'al.user_type = ?';         $params[] = $filterRole;           $types .= 's'; }
if ($filterAction) { $where[] = 'al.action LIKE ?';         $params[] = '%'.$filterAction.'%'; $types .= 's'; }
if ($filterFrom)   { $where[] = 'DATE(al.created_at) >= ?'; $params[] = $filterFrom;           $types .= 's'; }
if ($filterTo)     { $where[] = 'DATE(al.created_at) <= ?'; $params[] = $filterTo;             $types .= 's'; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

// ── Count ──────────────────────────────────────────────────────────────────
$cSt = $conn->prepare("SELECT COUNT(*) AS total FROM activity_log al $whereSQL");
if ($params) $cSt->bind_param($types, ...$params);
$cSt->execute();
$totalItems = (int)$cSt->get_result()->fetch_assoc()['total'];
$totalPages = max(1,(int)ceil($totalItems/$perPage));
$page   = min($page,$totalPages);
$offset = ($page-1)*$perPage;

// ── Fetch ──────────────────────────────────────────────────────────────────
$lSt = $conn->prepare("
    SELECT al.log_id, al.entity_type, al.entity_id, al.user_type, al.action,
           al.old_value, al.new_value, al.details, al.created_at,
           TRIM(CONCAT(COALESCE(a.first_name,''),' ',COALESCE(a.last_name,''))) AS user_name,
           o.order_code
    FROM activity_log al
    LEFT JOIN accounts a ON a.account_id = al.user_id
    LEFT JOIN orders   o ON al.entity_type='order' AND al.entity_id=o.order_id
    $whereSQL
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET $offset
");
if ($params) $lSt->bind_param($types, ...$params);
$lSt->execute();
$logs = $lSt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Dropdowns ──────────────────────────────────────────────────────────────
$entityTypes = $conn->query("SELECT DISTINCT entity_type FROM activity_log ORDER BY entity_type")->fetch_all(MYSQLI_ASSOC);
$userTypes   = $conn->query("SELECT DISTINCT user_type   FROM activity_log ORDER BY user_type")->fetch_all(MYSQLI_ASSOC);

// ── Colours ────────────────────────────────────────────────────────────────
$dotColor = [
    'order'=>'#3b82f6','payment'=>'#22c55e','product'=>'#f97316',
    'product_variant'=>'#f59e0b','account'=>'#a855f7','rider'=>'#06b6d4',
    'category'=>'#ec4899','blog'=>'#6366f1','cooking_suggestion'=>'#84cc16',
    'review'=>'#f43f5e','delivery'=>'#14b8a6','refund'=>'#ef4444','system'=>'#9ca3af',
];
$roleColor = [
    'super_admin'=>'#f97316','admin'=>'#3b82f6',
    'rider'=>'#a855f7','customer'=>'#22c55e','system'=>'#9ca3af',
];

// ── Diff helper ────────────────────────────────────────────────────────────
function diffStr(?string $o, ?string $n): ?array {
    if (!$o && !$n) return null;
    $od = $o ? json_decode($o,true) : null;
    $nd = $n ? json_decode($n,true) : null;
    if (!is_array($od) && !is_array($nd)) {
        $os = mb_strimwidth(trim((string)($o??'')),0,32,'…');
        $ns = mb_strimwidth(trim((string)($n??'')),0,32,'…');
        return ($os===$ns) ? null : ['label'=>null,'old'=>$os,'new'=>$ns,'more'=>0];
    }
    if (is_array($nd)) {
        $changed=0; $label=$oldS=$newS=null;
        foreach ($nd as $k=>$v) {
            $oldV = is_array($od)?($od[$k]??''):'';
            if ((string)$oldV!==(string)$v) {
                if (!$changed) {
                    $label = ucfirst(str_replace('_',' ',$k));
                    $oldS  = mb_strimwidth((string)$oldV,0,28,'…');
                    $newS  = mb_strimwidth((string)$v,0,28,'…');
                }
                $changed++;
            }
        }
        return $changed ? ['label'=>$label,'old'=>$oldS,'new'=>$newS,'more'=>$changed-1] : null;
    }
    return null;
}

// ── Pagination URL ─────────────────────────────────────────────────────────
function pgUrl(array $ov=[]): string {
    return '?'.http_build_query(array_filter(array_merge([
        'entity_type'=>$_GET['entity_type']??'','user_type'=>$_GET['user_type']??'',
        'action'=>$_GET['action']??'','from'=>$_GET['from']??'','to'=>$_GET['to']??'',
    ],$ov)));
}

$isFiltered = $filterEntity||$filterRole||$filterAction||$filterFrom||$filterTo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Activity Logs | SJFBI Admin</title>
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
    body { font-family:'Lexend',sans-serif; }

    /* ── table ── */
    .ltbl { width:100%; border-collapse:collapse; }
    .ltbl th {
      padding:6px 10px; font-size:9.5px; font-weight:700; letter-spacing:.06em;
      text-transform:uppercase; color:#9ca3af; background:#f9fafb;
      text-align:left; border-bottom:1px solid #f3f4f6; white-space:nowrap;
    }
    .ltbl td {
      padding:5px 10px; font-size:11px; color:#374151;
      border-bottom:1px solid #f3f4f6; vertical-align:middle; line-height:1.45;
    }
    .ltbl tbody tr:last-child td { border-bottom:none; }
    .ltbl tbody tr { border-left:2px solid transparent; transition:background .1s,border-color .1s; }
    .ltbl tbody tr:hover { background:#fffbf5; border-left-color:#f97316; }

    /* entity dot */
    .edot { display:inline-block;width:6px;height:6px;border-radius:50%;
            margin-right:4px;vertical-align:middle;flex-shrink:0; }

    /* role chip */
    .rchip { display:inline-block;padding:1px 5px;border-radius:999px;
             font-size:9px;font-weight:700;color:#fff;vertical-align:middle;
             line-height:1.6;white-space:nowrap; }

    /* diff */
    .dold { color:#ef4444;text-decoration:line-through; }
    .dnew { color:#16a34a;font-weight:600; }
    .darr { color:#d1d5db;margin:0 2px; }

    /* filter inputs */
    .fi { font-size:11px;border:1px solid #e5e7eb;border-radius:7px;
          padding:4px 8px;outline:none;font-family:'Lexend',sans-serif;
          background:#fff;color:#111827;transition:border-color .1s; }
    .fi:focus { border-color:#f97316; }

    /* pagination */
    .pg { display:inline-flex;align-items:center;justify-content:center;
          min-width:24px;height:22px;padding:0 5px;font-size:11px;font-weight:600;
          border-radius:5px;border:1px solid #e5e7eb;color:#6b7280;background:#fff;
          text-decoration:none;transition:all .1s; }
    .pg:hover  { border-color:#f97316;color:#f97316; }
    .pg.on     { background:#f97316;border-color:#f97316;color:#fff; }
    .pg.off    { opacity:.3;pointer-events:none; }
  </style>
</head>
<body class="bg-gray-50">

<?php include './components/header.php'; ?>
<?php include './components/sidebar.php'; ?>

<div class="w-full lg:ps-64">
<div class="p-4 sm:p-6 space-y-3">

  <!-- Header row -->
  <div class="flex items-center justify-between gap-2 flex-wrap">
    <div>
      <h1 class="text-base font-bold text-gray-800">Activity Logs</h1>
      <p class="text-xs text-gray-400 mt-0.5">
        <?= number_format($totalItems) ?> event<?= $totalItems!==1?'s':'' ?>
        <?php if ($isFiltered): ?>&nbsp;<span class="text-orange-400 font-semibold">· filtered</span><?php endif; ?>
      </p>
    </div>
    <a href="dashboard.php" class="text-xs text-gray-400 hover:text-orange-500 transition-colors">← Dashboard</a>
  </div>

  <!-- Filter bar -->
  <form method="GET" class="bg-white border border-gray-100 rounded-xl px-4 py-3 flex flex-wrap items-end gap-x-3 gap-y-2 shadow-sm">
    <div>
      <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-1">Action</div>
      <input name="action" type="text" class="fi w-36" placeholder="Search action…" value="<?= htmlspecialchars($filterAction) ?>">
    </div>
    <div>
      <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-1">Entity</div>
      <select name="entity_type" class="fi">
        <option value="">All entities</option>
        <?php foreach ($entityTypes as $et): ?>
        <option value="<?= htmlspecialchars($et['entity_type']) ?>" <?= $filterEntity===$et['entity_type']?'selected':'' ?>>
          <?= htmlspecialchars(ucfirst(str_replace('_',' ',$et['entity_type']))) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-1">Role</div>
      <select name="user_type" class="fi">
        <option value="">All roles</option>
        <?php foreach ($userTypes as $ut): ?>
        <option value="<?= htmlspecialchars($ut['user_type']) ?>" <?= $filterRole===$ut['user_type']?'selected':'' ?>>
          <?= htmlspecialchars(ucfirst(str_replace('_',' ',$ut['user_type']))) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-1">From</div>
      <input name="from" type="date" class="fi" value="<?= htmlspecialchars($filterFrom) ?>">
    </div>
    <div>
      <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-1">To</div>
      <input name="to" type="date" class="fi" value="<?= htmlspecialchars($filterTo) ?>">
    </div>
    <div class="flex gap-1.5">
      <button type="submit"
              class="px-3 py-1.5 text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors">
        Filter
      </button>
      <?php if ($isFiltered): ?>
      <a href="logs.php"
         class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-red-500 border border-gray-200 rounded-lg transition-colors">
        Clear
      </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Log table -->
  <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">

    <?php if (empty($logs)): ?>
    <p class="py-10 text-center text-xs text-gray-400">No logs match your filters.</p>
    <?php else: ?>

    <div class="overflow-x-auto">
    <table class="ltbl">
      <thead>
        <tr>
          <th style="width:140px">Time</th>
          <th style="width:90px">Entity</th>
          <th>Action</th>
          <th style="width:115px">By</th>
          <th>Change</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($logs as $r):
        $etype  = $r['entity_type'] ?? 'system';
        $dot    = $dotColor[$etype]  ?? '#9ca3af';
        $actor  = trim($r['user_name'] ?? '') ?: ucfirst($r['user_type'] ?? 'system');
        $rclr   = $roleColor[strtolower($r['user_type'] ?? 'system')] ?? '#9ca3af';
        $diff   = diffStr($r['old_value'], $r['new_value']);
        $action = ucfirst(str_replace('_',' ',$r['action'] ?? '—'));
      ?>
      <tr>

        <!-- Time -->
        <td style="font-size:10px;color:#9ca3af;white-space:nowrap">
          <?= date('M j, Y'  ,strtotime($r['created_at'])) ?>
          <span style="color:#e5e7eb">·</span>
          <?= date('g:i A',strtotime($r['created_at'])) ?>
        </td>

        <!-- Entity -->
        <td style="white-space:nowrap">
          <span class="edot" style="background:<?= $dot ?>"></span>
          <span style="font-size:10px;color:#6b7280;text-transform:capitalize"><?= str_replace('_',' ',$etype) ?></span>
          <span style="font-size:10px;color:#d1d5db"> #<?= (int)$r['entity_id'] ?></span>
        </td>

        <!-- Action + optional detail line -->
        <td>
          <span class="font-semibold text-gray-700"><?= htmlspecialchars($action) ?></span>
          <?php if (!empty($r['order_code'])): ?>
            <a href="order_manage.php?order_id=<?= (int)$r['entity_id'] ?>"
               style="font-size:10px;margin-left:5px"
               class="text-orange-500 hover:underline font-semibold">
              #<?= htmlspecialchars($r['order_code']) ?>
            </a>
          <?php endif; ?>
          <?php if (!empty($r['details'])): ?>
            <div style="font-size:10px;color:#9ca3af;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($r['details']) ?>
            </div>
          <?php endif; ?>
        </td>

        <!-- By -->
        <td style="white-space:nowrap">
          <span class="text-gray-700 font-medium"><?= htmlspecialchars($actor) ?></span>
          <span class="rchip ml-1" style="background:<?= $rclr ?>">
            <?= htmlspecialchars(ucfirst(str_replace('_',' ',$r['user_type']??'system'))) ?>
          </span>
        </td>

        <!-- Change diff -->
        <td style="max-width:200px">
          <?php if ($diff): ?>
            <?php if ($diff['label']): ?>
              <span style="font-size:9px;text-transform:uppercase;font-weight:700;letter-spacing:.04em;color:#9ca3af">
                <?= htmlspecialchars($diff['label']) ?>:
              </span>
            <?php endif; ?>
            <?php if ($diff['old']): ?>
              <span class="dold"><?= htmlspecialchars($diff['old']) ?></span>
              <span class="darr">→</span>
            <?php endif; ?>
            <span class="dnew"><?= htmlspecialchars($diff['new']) ?></span>
            <?php if ($diff['more']>0): ?>
              <span style="font-size:10px;color:#9ca3af"> +<?= $diff['more'] ?> more</span>
            <?php endif; ?>
          <?php else: ?>
            <span style="color:#e5e7eb">—</span>
          <?php endif; ?>
        </td>

      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages>1): ?>
    <div class="px-4 py-2.5 border-t border-gray-100 flex items-center justify-between gap-2 flex-wrap">
      <span style="font-size:10px;color:#9ca3af">
        <?= number_format($offset+1) ?>–<?= number_format(min($offset+$perPage,$totalItems)) ?>
        of <?= number_format($totalItems) ?>
      </span>
      <div class="flex items-center gap-1">
        <a href="<?= pgUrl(['page'=>$page-1]) ?>" class="pg <?= $page<=1?'off':'' ?>">‹</a>
        <?php
        $s=max(1,$page-2); $e=min($totalPages,$page+2);
        if ($s>1) { echo '<a href="'.pgUrl(['page'=>1]).'" class="pg">1</a>'; if($s>2) echo '<span class="pg off">…</span>'; }
        for ($p=$s;$p<=$e;$p++) echo '<a href="'.pgUrl(['page'=>$p]).'" class="pg '.($p===$page?'on':'').'">'.$p.'</a>';
        if ($e<$totalPages) { if($e<$totalPages-1) echo '<span class="pg off">…</span>'; echo '<a href="'.pgUrl(['page'=>$totalPages]).'" class="pg">'.$totalPages.'</a>'; }
        ?>
        <a href="<?= pgUrl(['page'=>$page+1]) ?>" class="pg <?= $page>=$totalPages?'off':'' ?>">›</a>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>