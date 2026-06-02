<?php
/**
 * export_customers.php — Customer list with spend summary
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * GET params (all optional):
 *   ?group=vip
 *   ?inactive_days=90
 *
 * Place in: /supadmin/exports/export_customers.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/ExportHelper.php';
require_once __DIR__ . '/../../conn.php'; // provides $conn (mysqli)

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

session_start();
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403); exit('Access denied.');
}

$inactiveDays = max(1, (int) ($_GET['inactive_days'] ?? 90));

// ── Query ─────────────────────────────────────────────────────────────────────
$groupFilter = '';
if (!empty($_GET['group'])) {
    $g           = $conn->real_escape_string($_GET['group']);
    $groupFilter = "AND cg.group_code = '$g'";
}

$sql = "
    SELECT
        a.account_id,
        CONCAT(a.account_first_name,' ',a.account_last_name)      AS full_name,
        a.account_email,
        a.account_phone,
        a.city,
        a.account_address,
        a.postal_code,
        COALESCE(cg.group_name,'Regular')                          AS customer_group,
        COALESCE(cg.group_code,'regular')                          AS group_code,
        COALESCE(cg.discount_percentage, 0)                        AS group_discount_pct,
        COUNT(o.order_id)                                           AS total_orders,
        COALESCE(SUM(o.total_price), 0)                            AS total_spending,
        COALESCE(AVG(o.total_price), 0)                            AS avg_order_value,
        MAX(o.order_date)                                           AS last_order_date,
        a.created_at                                                AS registered_at
    FROM accounts a
    LEFT JOIN orders o
        ON a.account_id = o.account_id AND o.is_deleted = 0 AND o.order_status != 'Cancelled'
    LEFT JOIN account_groups ag ON a.account_id = ag.account_id
    LEFT JOIN customer_groups cg ON ag.group_id = cg.group_id AND cg.is_active = 1
        $groupFilter
    WHERE a.role = 'customer'
      AND a.is_deleted = 0
    GROUP BY a.account_id
    ORDER BY total_spending DESC
";

$result = $conn->query($sql);
if (!$result) die('Query error: ' . $conn->error);

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$now = new DateTime();
$inactiveRows = array_values(array_filter($rows, function ($r) use ($now, $inactiveDays) {
    if (empty($r['last_order_date'])) return true;
    return $now->diff(new DateTime($r['last_order_date']))->days >= $inactiveDays;
}));
$noOrderRows = array_values(array_filter($rows, fn($r) => (int) $r['total_orders'] === 0));

$filename = 'Customers_Export_' . date('Ymd_His');
$subtitle = 'Generated: ' . date('F j, Y g:i A')
          . '  |  Total: ' . count($rows)
          . '  |  Inactive (≥'.$inactiveDays.'d): ' . count($inactiveRows)
          . '  |  No orders: ' . count($noOrderRows);

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setTitle('Customer Export');

$headers = [
    'Full Name','Email','Phone','City','Address','Postal Code',
    'Customer Group','Group Discount (%)',
    'Total Orders','Total Spending (PHP)','Avg Order Value (PHP)',
    'Last Order Date','Registered At',
];
$colCount = count($headers); // 13

$groupColors = [
    'vip'        => ['FFF57F17', 'FFFFF8E1'],
    'employee'   => ['FF2E7D32', 'FFE8F5E9'],
    'subscriber' => ['FF1565C0', 'FFE3F2FD'],
    'regular'    => ['FF546E7A', 'FFFAFAFA'],
];

// ── Reusable sheet builder ────────────────────────────────────────────────────
$buildSheet = function ($sheet, array $data, string $title)
    use ($headers, $colCount, $subtitle, $now, $inactiveDays, $groupColors)
{
    $sheet->setTitle($title);

    $hRow = ExportHelper::addReportTitle($sheet, $title, $subtitle, $colCount);
    foreach ($headers as $c => $h) {
        ExportHelper::cell($sheet, $c + 1, $hRow, $h);
    }
    ExportHelper::styleHeader($sheet, 'A'.$hRow.':'.Coordinate::stringFromColumnIndex($colCount).$hRow);
    $sheet->getRowDimension($hRow)->setRowHeight(28);
    ExportHelper::freezeHeader($sheet, $hRow + 1);

    $dStart = $hRow + 1;

    if (empty($data)) {
        ExportHelper::cell($sheet, 1, $dStart, 'No records found.');
        return;
    }

    foreach ($data as $i => $row) {
        $r = $dStart + $i;
        ExportHelper::cell($sheet,  1, $r, $row['full_name']);
        ExportHelper::cell($sheet,  2, $r, $row['account_email']);
        ExportHelper::cell($sheet,  3, $r, $row['account_phone']);
        ExportHelper::cell($sheet,  4, $r, $row['city']);
        ExportHelper::cell($sheet,  5, $r, $row['account_address']);
        ExportHelper::cell($sheet,  6, $r, $row['postal_code']);
        ExportHelper::cell($sheet,  7, $r, $row['customer_group']);
        ExportHelper::cell($sheet,  8, $r, (float) $row['group_discount_pct']);
        ExportHelper::cell($sheet,  9, $r, (int)   $row['total_orders']);
        ExportHelper::cell($sheet, 10, $r, (float) $row['total_spending']);
        ExportHelper::cell($sheet, 11, $r, round((float) $row['avg_order_value'], 2));
        ExportHelper::cell($sheet, 12, $r, $row['last_order_date'] ?? 'No orders yet');
        ExportHelper::cell($sheet, 13, $r, $row['registered_at']);

        // colour group badge
        $code = strtolower($row['group_code']);
        if (isset($groupColors[$code])) {
            ExportHelper::colorCell($sheet, 'G'.$r, $groupColors[$code][1], $groupColors[$code][0]);
        }
        // flag inactive last order date
        if (!empty($row['last_order_date'])) {
            if ($now->diff(new DateTime($row['last_order_date']))->days >= $inactiveDays) {
                $sheet->getStyle('L'.$r)->getFont()->getColor()->setARGB('FFCC0000');
            }
        }
        // dim zero-order customers
        if ((int) $row['total_orders'] === 0) {
            $sheet->getStyle('I'.$r)->getFont()->setItalic(true)->getColor()->setARGB('FF9E9E9E');
        }
    }

    $dEnd = $dStart + count($data) - 1;
    ExportHelper::styleRows($sheet, $dStart, $dEnd, $colCount);
    ExportHelper::formatCurrency($sheet, 'J'.$dStart.':K'.$dEnd);
    ExportHelper::addTotalsRow($sheet, $dEnd + 1, $colCount, ['I','J'], $dStart, $dEnd);
    ExportHelper::autoFitColumns($sheet, $colCount);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(35);
    $sheet->getColumnDimension('L')->setWidth(22);
    $sheet->getColumnDimension('M')->setWidth(22);
};

$buildSheet($spreadsheet->getActiveSheet(), $rows, 'All Customers');

$s2 = $spreadsheet->createSheet();
$buildSheet($s2, $inactiveRows, 'Inactive Customers');

$s3 = $spreadsheet->createSheet();
$buildSheet($s3, $noOrderRows, 'No Orders Yet');

$spreadsheet->setActiveSheetIndex(0);
ExportHelper::download($spreadsheet, $filename);