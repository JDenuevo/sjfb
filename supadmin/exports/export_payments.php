<?php
/**
 * export_payments.php — Payments reconciliation
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * GET params (all optional):
 *   ?status=Paid
 *   ?method=gcash
 *   ?from=2025-01-01&to=2025-12-31
 *   ?month=5&year=2025
 *
 * Place in: /supadmin/exports/export_payments.php
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

// ── Build WHERE ───────────────────────────────────────────────────────────────
$conditions = ["o.is_deleted = 0"];

if (!empty($_GET['status'])) {
    $v            = $conn->real_escape_string($_GET['status']);
    $conditions[] = "py.payment_status = '$v'";
}
if (!empty($_GET['method'])) {
    $v            = $conn->real_escape_string($_GET['method']);
    $conditions[] = "py.source_type = '$v'";
}
if (!empty($_GET['month']) && !empty($_GET['year'])) {
    $m            = (int) $_GET['month'];
    $y            = (int) $_GET['year'];
    $conditions[] = "MONTH(py.paid_at) = $m AND YEAR(py.paid_at) = $y";
} elseif (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from         = $conn->real_escape_string($_GET['from']);
    $to           = $conn->real_escape_string($_GET['to']);
    $conditions[] = "DATE(py.paid_at) BETWEEN '$from' AND '$to'";
}

$where = implode(' AND ', $conditions);

// ── Query ─────────────────────────────────────────────────────────────────────
$sql = "
    SELECT
        o.order_code,
        py.source_type                                        AS payment_method,
        py.gross_amount,
        COALESCE(py.fee, 0)                                   AS gateway_fee,
        COALESCE(py.vat, 0)                                   AS vat,
        COALESCE(py.total_fee, 0)                             AS total_fee,
        COALESCE(py.net_amount, py.gross_amount)              AS net_amount,
        py.payment_status,
        COALESCE(py.refunded_amount, 0)                       AS refunded_amount,
        COALESCE(py.refund_status, '')                        AS refund_status,
        COALESCE(py.provider_id, '')                          AS reference_number,
        COALESCE(py.billing_name, '')                         AS billing_name,
        COALESCE(py.billing_email, '')                        AS billing_email,
        COALESCE(py.billing_phone, '')                        AS billing_phone,
        COALESCE(py.billing_city, '')                         AS billing_city,
        py.currency,
        py.mode,
        py.paid_at
    FROM payments py
    JOIN orders o ON py.order_id = o.order_id
    WHERE $where
    ORDER BY py.paid_at DESC
";

$result = $conn->query($sql);
if (!$result) die('Query error: ' . $conn->error);

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

// ── Labels ────────────────────────────────────────────────────────────────────
$dateLabel = !empty($_GET['month'])
    ? date('F Y', mktime(0,0,0,(int)$_GET['month'],1,(int)$_GET['year']))
    : (!empty($_GET['from']) ? $_GET['from'].' to '.$_GET['to'] : 'All time');

$filename = 'Payments_Export_' . date('Ymd_His');
$subtitle = 'Generated: ' . date('F j, Y g:i A')
          . '  |  Period: ' . $dateLabel
          . '  |  Rows: ' . count($rows);

// ── Spreadsheet ───────────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setTitle('Payments Export');

$headers = [
    'Order Code','Payment Method','Gross Amount','Gateway Fee',
    'VAT','Total Fee','Net Amount','Payment Status',
    'Refunded Amount','Refund Status','Reference No.',
    'Billing Name','Billing Email','Billing Phone','Billing City',
    'Currency','Mode','Paid At',
];
$colCount = count($headers); // 18

// ════════════════════════════════════════════════════════════════════
// SHEET 1 — Full detail
// ════════════════════════════════════════════════════════════════════
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Payment Detail');

$hRow = ExportHelper::addReportTitle($sheet, 'Payments Report', $subtitle, $colCount);
foreach ($headers as $c => $h) {
    ExportHelper::cell($sheet, $c + 1, $hRow, $h);
}
ExportHelper::styleHeader($sheet, 'A'.$hRow.':'.Coordinate::stringFromColumnIndex($colCount).$hRow);
$sheet->getRowDimension($hRow)->setRowHeight(28);
ExportHelper::freezeHeader($sheet, $hRow + 1);

$dStart = $hRow + 1;

$pStatusColors = [
    'Paid'     => ['FF2E7D32', 'FFE8F5E9'],
    'Refunded' => ['FFF57F17', 'FFFFF8E1'],
    'Failed'   => ['FFC62828', 'FFFFEBEE'],
    'Pending'  => ['FF1565C0', 'FFE3F2FD'],
];

foreach ($rows as $i => $row) {
    $r = $dStart + $i;
    ExportHelper::cell($sheet,  1, $r, $row['order_code']);
    ExportHelper::cell($sheet,  2, $r, strtoupper($row['payment_method']));
    ExportHelper::cell($sheet,  3, $r, (float) $row['gross_amount']);
    ExportHelper::cell($sheet,  4, $r, (float) $row['gateway_fee']);
    ExportHelper::cell($sheet,  5, $r, (float) $row['vat']);
    ExportHelper::cell($sheet,  6, $r, (float) $row['total_fee']);
    ExportHelper::cell($sheet,  7, $r, (float) $row['net_amount']);
    ExportHelper::cell($sheet,  8, $r, $row['payment_status']);
    ExportHelper::cell($sheet,  9, $r, (float) $row['refunded_amount']);
    ExportHelper::cell($sheet, 10, $r, $row['refund_status']);
    ExportHelper::cell($sheet, 11, $r, $row['reference_number']);
    ExportHelper::cell($sheet, 12, $r, $row['billing_name']);
    ExportHelper::cell($sheet, 13, $r, $row['billing_email']);
    ExportHelper::cell($sheet, 14, $r, $row['billing_phone']);
    ExportHelper::cell($sheet, 15, $r, $row['billing_city']);
    ExportHelper::cell($sheet, 16, $r, $row['currency']);
    ExportHelper::cell($sheet, 17, $r, $row['mode']);
    ExportHelper::cell($sheet, 18, $r, $row['paid_at'] ?? '');

    $s = $row['payment_status'];
    if (isset($pStatusColors[$s])) {
        ExportHelper::colorCell($sheet, 'H'.$r, $pStatusColors[$s][1], $pStatusColors[$s][0]);
    }
    // flag test mode
    if (($row['mode'] ?? '') === 'test') {
        ExportHelper::colorCell($sheet, 'Q'.$r, 'FFFFF8E1', 'FFFF6F00');
    }
}

$dEnd = $dStart + count($rows) - 1;
ExportHelper::styleRows($sheet, $dStart, $dEnd, $colCount);
foreach (['C','D','E','F','G','I'] as $col) {
    ExportHelper::formatCurrency($sheet, $col.$dStart.':'.$col.$dEnd);
}
ExportHelper::addTotalsRow($sheet, $dEnd + 1, $colCount, ['C','D','E','F','G','I'], $dStart, $dEnd);
ExportHelper::autoFitColumns($sheet, $colCount);
$sheet->getColumnDimension('K')->setWidth(28);
$sheet->getColumnDimension('M')->setWidth(28);
$sheet->getColumnDimension('R')->setWidth(20);

// ════════════════════════════════════════════════════════════════════
// SHEET 2 — By payment method
// ════════════════════════════════════════════════════════════════════
$byMethod = [];
foreach ($rows as $row) {
    $pm = strtoupper($row['payment_method']);
    $byMethod[$pm]['count']    = ($byMethod[$pm]['count']    ?? 0) + 1;
    $byMethod[$pm]['gross']    = ($byMethod[$pm]['gross']    ?? 0) + (float) $row['gross_amount'];
    $byMethod[$pm]['fees']     = ($byMethod[$pm]['fees']     ?? 0) + (float) $row['total_fee'];
    $byMethod[$pm]['net']      = ($byMethod[$pm]['net']      ?? 0) + (float) $row['net_amount'];
    $byMethod[$pm]['refunded'] = ($byMethod[$pm]['refunded'] ?? 0) + (float) $row['refunded_amount'];
}

$s2 = $spreadsheet->createSheet();
$s2->setTitle('By Payment Method');
ExportHelper::addReportTitle($s2, 'Payments by Method', $subtitle, 6);
foreach (['Payment Method','Transactions','Gross Amount','Total Fees','Net Amount','Refunded'] as $c => $h) {
    ExportHelper::cell($s2, $c + 1, 3, $h);
}
ExportHelper::styleHeader($s2, 'A3:F3');
$r2 = 4;
foreach ($byMethod as $pm => $data) {
    ExportHelper::cell($s2, 1, $r2, $pm);
    ExportHelper::cell($s2, 2, $r2, $data['count']);
    ExportHelper::cell($s2, 3, $r2, $data['gross']);
    ExportHelper::cell($s2, 4, $r2, $data['fees']);
    ExportHelper::cell($s2, 5, $r2, $data['net']);
    ExportHelper::cell($s2, 6, $r2, $data['refunded']);
    $r2++;
}
ExportHelper::styleRows($s2, 4, $r2 - 1, 6);
foreach (['C','D','E','F'] as $col) {
    ExportHelper::formatCurrency($s2, $col.'4:'.$col.($r2 - 1));
}
ExportHelper::addTotalsRow($s2, $r2, 6, ['B','C','D','E','F'], 4, $r2 - 1);
ExportHelper::autoFitColumns($s2, 6);

$spreadsheet->setActiveSheetIndex(0);
ExportHelper::download($spreadsheet, $filename);