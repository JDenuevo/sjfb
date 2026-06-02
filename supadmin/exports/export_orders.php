<?php
/**
 * export_orders.php — Orders + Order Items
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * GET params (all optional):
 *   ?status=Delivered
 *   ?from=2025-01-01&to=2025-12-31
 *   ?month=5&year=2025
 *
 * Place in: /supadmin/exports/export_orders.php
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

// ── Build WHERE clause ────────────────────────────────────────────────────────
$conditions = ["o.is_deleted = 0"];

if (!empty($_GET['status'])) {
    $status       = $conn->real_escape_string($_GET['status']);
    $conditions[] = "o.order_status = '$status'";
}
if (!empty($_GET['month']) && !empty($_GET['year'])) {
    $month        = (int) $_GET['month'];
    $year         = (int) $_GET['year'];
    $conditions[] = "MONTH(o.order_date) = $month AND YEAR(o.order_date) = $year";
} elseif (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from         = $conn->real_escape_string($_GET['from']);
    $to           = $conn->real_escape_string($_GET['to']);
    $conditions[] = "DATE(o.order_date) BETWEEN '$from' AND '$to'";
}

$where = implode(' AND ', $conditions);

// ── Query ─────────────────────────────────────────────────────────────────────
$sql = "
    SELECT
        o.order_code,
        CONCAT(o.recipient_first_name,' ',o.recipient_last_name) AS customer_name,
        o.recipient_email,
        o.recipient_phone,
        o.city,
        o.order_type,
        o.payment_method,
        o.order_status,
        IF(o.is_guest_order=1,'Guest','Registered')              AS customer_type,
        p.product_name,
        pv.variant_name,
        pv.unit_type,
        oi.quantity,
        oi.price                                                  AS unit_price,
        oi.discount                                               AS item_discount,
        (oi.quantity * oi.price) - oi.discount                   AS item_subtotal,
        o.subtotal                                                AS order_subtotal,
        o.discount_amount                                         AS order_discount,
        o.delivery_fee,
        o.total_price,
        COALESCE(o.voucher_code,'')                               AS voucher_code,
        IF(o.free_shipping_applied=1,'Yes','No')                  AS free_shipping,
        o.order_date,
        o.delivered_at
    FROM orders o
    JOIN order_items oi      ON o.order_id    = oi.order_id
    JOIN products p          ON oi.product_id = p.product_id
    JOIN product_variants pv ON oi.variant_id = pv.variant_id
    WHERE $where
    ORDER BY o.order_date DESC, o.order_code, p.product_name
";

$result = $conn->query($sql);
if (!$result) {
    die('Query error: ' . $conn->error);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// ── Labels ────────────────────────────────────────────────────────────────────
$dateLabel = !empty($_GET['month'])
    ? date('F Y', mktime(0,0,0,(int)$_GET['month'],1,(int)$_GET['year']))
    : (!empty($_GET['from']) ? $_GET['from'].' to '.$_GET['to'] : 'All time');

$filename = 'Orders_Export_' . date('Ymd_His');
$subtitle = 'Generated: ' . date('F j, Y g:i A')
          . '  |  Period: ' . $dateLabel
          . '  |  Rows: ' . count($rows);

// ── Spreadsheet ───────────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setTitle('Orders Export');

$headers = [
    'Order Code','Customer Name','Email','Phone','City',
    'Order Type','Payment Method','Order Status','Customer Type',
    'Product','Variant','Unit','Qty',
    'Unit Price','Item Discount','Item Subtotal',
    'Order Subtotal','Order Discount','Delivery Fee','Order Total',
    'Voucher Code','Free Shipping','Order Date','Delivered At',
];
$colCount = count($headers); // 24

// ════════════════════════════════════════════════════════════════════
// SHEET 1 — Full detail
// ════════════════════════════════════════════════════════════════════
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Order Detail');

$hRow = ExportHelper::addReportTitle($sheet, 'Orders Report', $subtitle, $colCount);
foreach ($headers as $c => $h) {
    ExportHelper::cell($sheet, $c + 1, $hRow, $h);
}
ExportHelper::styleHeader($sheet, 'A'.$hRow.':'.Coordinate::stringFromColumnIndex($colCount).$hRow);
$sheet->getRowDimension($hRow)->setRowHeight(28);
ExportHelper::freezeHeader($sheet, $hRow + 1);

$dStart = $hRow + 1;

$statusColors = [
    'Delivered'      => ['FF2E7D32', 'FFE8F5E9'],
    'Completed'      => ['FF2E7D32', 'FFE8F5E9'],
    'Cancelled'      => ['FFC62828', 'FFFFEBEE'],
    'Pending'        => ['FFF57F17', 'FFFFF8E1'],
    'Processing'     => ['FF1565C0', 'FFE3F2FD'],
    'OutForDelivery' => ['FF4527A0', 'FFEDE7F6'],
    'Paid'           => ['FF2E7D32', 'FFE8F5E9'],
];

foreach ($rows as $i => $row) {
    $r = $dStart + $i;
    ExportHelper::cell($sheet,  1, $r, $row['order_code']);
    ExportHelper::cell($sheet,  2, $r, $row['customer_name']);
    ExportHelper::cell($sheet,  3, $r, $row['recipient_email']);
    ExportHelper::cell($sheet,  4, $r, $row['recipient_phone']);
    ExportHelper::cell($sheet,  5, $r, $row['city']);
    ExportHelper::cell($sheet,  6, $r, ucfirst($row['order_type']));
    ExportHelper::cell($sheet,  7, $r, strtoupper($row['payment_method']));
    ExportHelper::cell($sheet,  8, $r, $row['order_status']);
    ExportHelper::cell($sheet,  9, $r, $row['customer_type']);
    ExportHelper::cell($sheet, 10, $r, $row['product_name']);
    ExportHelper::cell($sheet, 11, $r, $row['variant_name']);
    ExportHelper::cell($sheet, 12, $r, $row['unit_type']);
    ExportHelper::cell($sheet, 13, $r, (float) $row['quantity']);
    ExportHelper::cell($sheet, 14, $r, (float) $row['unit_price']);
    ExportHelper::cell($sheet, 15, $r, (float) $row['item_discount']);
    ExportHelper::cell($sheet, 16, $r, (float) $row['item_subtotal']);
    ExportHelper::cell($sheet, 17, $r, (float) $row['order_subtotal']);
    ExportHelper::cell($sheet, 18, $r, (float) $row['order_discount']);
    ExportHelper::cell($sheet, 19, $r, (float) $row['delivery_fee']);
    ExportHelper::cell($sheet, 20, $r, (float) $row['total_price']);
    ExportHelper::cell($sheet, 21, $r, $row['voucher_code']);
    ExportHelper::cell($sheet, 22, $r, $row['free_shipping']);
    ExportHelper::cell($sheet, 23, $r, $row['order_date']);
    ExportHelper::cell($sheet, 24, $r, $row['delivered_at'] ?? '');

    $s = $row['order_status'];
    if (isset($statusColors[$s])) {
        ExportHelper::colorCell($sheet, 'H'.$r, $statusColors[$s][1], $statusColors[$s][0]);
    }
}

$dEnd = $dStart + count($rows) - 1;
ExportHelper::styleRows($sheet, $dStart, $dEnd, $colCount);
foreach (['N','O','P','Q','R','S','T'] as $col) {
    ExportHelper::formatCurrency($sheet, $col.$dStart.':'.$col.$dEnd);
}
ExportHelper::addTotalsRow($sheet, $dEnd + 1, $colCount, ['N','O','P','Q','R','S','T'], $dStart, $dEnd);
ExportHelper::autoFitColumns($sheet, $colCount);
$sheet->getColumnDimension('C')->setWidth(28);
$sheet->getColumnDimension('J')->setWidth(30);
$sheet->getColumnDimension('W')->setWidth(20);
$sheet->getColumnDimension('X')->setWidth(20);

// ════════════════════════════════════════════════════════════════════
// SHEET 2 — By status
// ════════════════════════════════════════════════════════════════════
$byStatus = [];
foreach ($rows as $row) {
    $s = $row['order_status'];
    $byStatus[$s]['codes'][$row['order_code']] = true;
    $byStatus[$s]['revenue'] = ($byStatus[$s]['revenue'] ?? 0) + (float) $row['total_price'];
}

$s2 = $spreadsheet->createSheet();
$s2->setTitle('By Status');
ExportHelper::addReportTitle($s2, 'Orders by Status', $subtitle, 3);
foreach (['Order Status', 'Order Count', 'Revenue (PHP)'] as $c => $h) {
    ExportHelper::cell($s2, $c + 1, 3, $h);
}
ExportHelper::styleHeader($s2, 'A3:C3');
$r2 = 4;
foreach ($byStatus as $status => $data) {
    ExportHelper::cell($s2, 1, $r2, $status);
    ExportHelper::cell($s2, 2, $r2, count($data['codes']));
    ExportHelper::cell($s2, 3, $r2, $data['revenue']);
    $r2++;
}
ExportHelper::styleRows($s2, 4, $r2 - 1, 3);
ExportHelper::formatCurrency($s2, 'C4:C'.($r2 - 1));
ExportHelper::addTotalsRow($s2, $r2, 3, ['B', 'C'], 4, $r2 - 1);
ExportHelper::autoFitColumns($s2, 3);

// ════════════════════════════════════════════════════════════════════
// SHEET 3 — By payment method
// ════════════════════════════════════════════════════════════════════
$byPayment = [];
foreach ($rows as $row) {
    $pm = strtoupper($row['payment_method']);
    $byPayment[$pm]['codes'][$row['order_code']] = true;
    $byPayment[$pm]['revenue'] = ($byPayment[$pm]['revenue'] ?? 0) + (float) $row['total_price'];
}

$s3 = $spreadsheet->createSheet();
$s3->setTitle('By Payment Method');
ExportHelper::addReportTitle($s3, 'Orders by Payment Method', $subtitle, 3);
foreach (['Payment Method', 'Order Count', 'Revenue (PHP)'] as $c => $h) {
    ExportHelper::cell($s3, $c + 1, 3, $h);
}
ExportHelper::styleHeader($s3, 'A3:C3');
$r3 = 4;
foreach ($byPayment as $pm => $data) {
    ExportHelper::cell($s3, 1, $r3, $pm);
    ExportHelper::cell($s3, 2, $r3, count($data['codes']));
    ExportHelper::cell($s3, 3, $r3, $data['revenue']);
    $r3++;
}
ExportHelper::styleRows($s3, 4, $r3 - 1, 3);
ExportHelper::formatCurrency($s3, 'C4:C'.($r3 - 1));
ExportHelper::addTotalsRow($s3, $r3, 3, ['B', 'C'], 4, $r3 - 1);
ExportHelper::autoFitColumns($s3, 3);

$spreadsheet->setActiveSheetIndex(0);
ExportHelper::download($spreadsheet, $filename);