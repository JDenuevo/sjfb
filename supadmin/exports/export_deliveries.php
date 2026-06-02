<?php
/**
 * export_deliveries.php — Deliveries + rider performance
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * GET params (all optional):
 *   ?status=delivered
 *   ?rider_id=5
 *   ?from=2025-01-01&to=2025-12-31
 *   ?month=5&year=2025
 *
 * Place in: /supadmin/exports/export_deliveries.php
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
    $conditions[] = "d.delivery_status = '$v'";
}
if (!empty($_GET['rider_id'])) {
    $conditions[] = "d.rider_id = " . (int) $_GET['rider_id'];
}
if (!empty($_GET['month']) && !empty($_GET['year'])) {
    $m            = (int) $_GET['month'];
    $y            = (int) $_GET['year'];
    $conditions[] = "MONTH(d.assigned_at) = $m AND YEAR(d.assigned_at) = $y";
} elseif (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from         = $conn->real_escape_string($_GET['from']);
    $to           = $conn->real_escape_string($_GET['to']);
    $conditions[] = "DATE(d.assigned_at) BETWEEN '$from' AND '$to'";
}

$where = implode(' AND ', $conditions);

// ── Query ─────────────────────────────────────────────────────────────────────
$sql = "
    SELECT
        o.order_code,
        CONCAT(o.recipient_first_name,' ',o.recipient_last_name)    AS customer_name,
        o.city                                                        AS delivery_city,
        o.total_price                                                 AS order_total,
        IF(d.is_third_party=1,'3rd Party','In-house')                AS delivery_type,
        COALESCE(d.third_party_name,'')                              AS third_party_name,
        COALESCE(r.rider_name,
            CONCAT(a.account_first_name,' ',a.account_last_name),
            'Unassigned')                                             AS rider_name,
        COALESCE(r.vehicle_type,'')                                  AS vehicle_type,
        COALESCE(r.vehicle_plate_number,'')                          AS plate_number,
        d.delivery_status,
        d.estimated_distance                                          AS distance_km,
        d.estimated_time                                              AS est_minutes,
        d.assigned_at,
        d.accepted_at,
        d.picked_up_at,
        d.delivered_at,
        TIMESTAMPDIFF(MINUTE, d.assigned_at, d.accepted_at)         AS accept_time_min,
        TIMESTAMPDIFF(MINUTE, d.accepted_at, d.picked_up_at)        AS pickup_time_min,
        TIMESTAMPDIFF(MINUTE, d.picked_up_at, d.delivered_at)       AS transit_time_min,
        TIMESTAMPDIFF(MINUTE, d.assigned_at, d.delivered_at)        AS total_time_min,
        COALESCE(d.notes,'')                                         AS notes
    FROM deliveries d
    JOIN orders o ON d.order_id = o.order_id
    LEFT JOIN riders r ON d.rider_id = r.rider_id
    LEFT JOIN accounts a ON r.account_id = a.account_id
    WHERE $where
    ORDER BY d.assigned_at DESC
";

$result = $conn->query($sql);
if (!$result) die('Query error: ' . $conn->error);

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$failedRows    = array_values(array_filter($rows, fn($r) => $r['delivery_status'] === 'failed'));
$deliveredRows = array_values(array_filter($rows, fn($r) => $r['delivery_status'] === 'delivered'));

$dateLabel = !empty($_GET['month'])
    ? date('F Y', mktime(0,0,0,(int)$_GET['month'],1,(int)$_GET['year']))
    : (!empty($_GET['from']) ? $_GET['from'].' to '.$_GET['to'] : 'All time');

$filename = 'Deliveries_Export_' . date('Ymd_His');
$subtitle = 'Generated: ' . date('F j, Y g:i A')
          . '  |  Period: ' . $dateLabel
          . '  |  Total: '.count($rows)
          . '  |  Delivered: '.count($deliveredRows)
          . '  |  Failed: '.count($failedRows);

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setTitle('Deliveries Export');

$headers = [
    'Order Code','Customer','Delivery City','Order Total (PHP)',
    'Delivery Type','3rd Party','Rider Name','Vehicle','Plate No.',
    'Status','Distance (km)','Est. Time (min)',
    'Assigned At','Accepted At','Picked Up At','Delivered At',
    'Accept (min)','Pickup (min)','Transit (min)','Total Time (min)',
    'Notes',
];
$colCount = count($headers); // 21

$dStatusColors = [
    'delivered'          => ['FF2E7D32', 'FFE8F5E9'],
    'failed'             => ['FFC62828', 'FFFFEBEE'],
    'in_transit'         => ['FF4527A0', 'FFEDE7F6'],
    'picked_up'          => ['FF1565C0', 'FFE3F2FD'],
    'accepted'           => ['FF00695C', 'FFE0F2F1'],
    'pending_acceptance' => ['FFF57F17', 'FFFFF8E1'],
    'cancelled'          => ['FF4E342E', 'FFEFEBE9'],
    'reassigned'         => ['FF880E4F', 'FFFCE4EC'],
];

// ── Reusable sheet builder ────────────────────────────────────────────────────
$buildSheet = function ($sheet, array $data, string $title)
    use ($headers, $colCount, $subtitle, $dStatusColors)
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
        ExportHelper::cell($sheet,  1, $r, $row['order_code']);
        ExportHelper::cell($sheet,  2, $r, $row['customer_name']);
        ExportHelper::cell($sheet,  3, $r, $row['delivery_city']);
        ExportHelper::cell($sheet,  4, $r, (float) $row['order_total']);
        ExportHelper::cell($sheet,  5, $r, $row['delivery_type']);
        ExportHelper::cell($sheet,  6, $r, $row['third_party_name']);
        ExportHelper::cell($sheet,  7, $r, $row['rider_name']);
        ExportHelper::cell($sheet,  8, $r, $row['vehicle_type']);
        ExportHelper::cell($sheet,  9, $r, $row['plate_number']);
        ExportHelper::cell($sheet, 10, $r, $row['delivery_status']);
        ExportHelper::cell($sheet, 11, $r, $row['distance_km'] !== null ? (float) $row['distance_km'] : '');
        ExportHelper::cell($sheet, 12, $r, $row['est_minutes']  !== null ? (int)   $row['est_minutes']  : '');
        ExportHelper::cell($sheet, 13, $r, $row['assigned_at']  ?? '');
        ExportHelper::cell($sheet, 14, $r, $row['accepted_at']  ?? '');
        ExportHelper::cell($sheet, 15, $r, $row['picked_up_at'] ?? '');
        ExportHelper::cell($sheet, 16, $r, $row['delivered_at'] ?? '');
        ExportHelper::cell($sheet, 17, $r, $row['accept_time_min']  !== null ? (int) $row['accept_time_min']  : '');
        ExportHelper::cell($sheet, 18, $r, $row['pickup_time_min']  !== null ? (int) $row['pickup_time_min']  : '');
        ExportHelper::cell($sheet, 19, $r, $row['transit_time_min'] !== null ? (int) $row['transit_time_min'] : '');
        ExportHelper::cell($sheet, 20, $r, $row['total_time_min']   !== null ? (int) $row['total_time_min']   : '');
        ExportHelper::cell($sheet, 21, $r, $row['notes']);

        $s = $row['delivery_status'];
        if (isset($dStatusColors[$s])) {
            ExportHelper::colorCell($sheet, 'J'.$r, $dStatusColors[$s][1], $dStatusColors[$s][0]);
        }
    }

    $dEnd = $dStart + count($data) - 1;
    ExportHelper::styleRows($sheet, $dStart, $dEnd, $colCount);
    ExportHelper::formatCurrency($sheet, 'D'.$dStart.':D'.$dEnd);
    ExportHelper::autoFitColumns($sheet, $colCount);
    $sheet->getColumnDimension('B')->setWidth(24);
    $sheet->getColumnDimension('G')->setWidth(22);
    foreach (['M','N','O','P'] as $col) $sheet->getColumnDimension($col)->setWidth(20);
    $sheet->getColumnDimension('U')->setWidth(30);
};

$buildSheet($spreadsheet->getActiveSheet(), $rows, 'All Deliveries');

$s2 = $spreadsheet->createSheet();
$buildSheet($s2, $failedRows, 'Failed Deliveries');

// ════════════════════════════════════════════════════════════════════
// SHEET 3 — Rider performance
// ════════════════════════════════════════════════════════════════════
$byRider = [];
foreach ($rows as $row) {
    $rider = $row['rider_name'];
    $byRider[$rider]['total']     = ($byRider[$rider]['total']     ?? 0) + 1;
    $byRider[$rider]['delivered'] = ($byRider[$rider]['delivered'] ?? 0) + ($row['delivery_status'] === 'delivered' ? 1 : 0);
    $byRider[$rider]['failed']    = ($byRider[$rider]['failed']    ?? 0) + ($row['delivery_status'] === 'failed'    ? 1 : 0);
    if (!empty($row['total_time_min'])) $byRider[$rider]['times'][] = (int) $row['total_time_min'];
}

$s3 = $spreadsheet->createSheet();
$s3->setTitle('Rider Performance');
ExportHelper::addReportTitle($s3, 'Rider Performance', $subtitle, 6);
foreach (['Rider','Total','Delivered','Failed','Success Rate (%)','Avg Time (min)'] as $c => $h) {
    ExportHelper::cell($s3, $c + 1, 3, $h);
}
ExportHelper::styleHeader($s3, 'A3:F3');
$r3 = 4;
foreach ($byRider as $rider => $d) {
    $avg     = !empty($d['times']) ? round(array_sum($d['times']) / count($d['times'])) : '';
    $success = $d['total'] > 0 ? round($d['delivered'] / $d['total'] * 100, 1) : 0;
    ExportHelper::cell($s3, 1, $r3, $rider);
    ExportHelper::cell($s3, 2, $r3, $d['total']);
    ExportHelper::cell($s3, 3, $r3, $d['delivered']);
    ExportHelper::cell($s3, 4, $r3, $d['failed']);
    ExportHelper::cell($s3, 5, $r3, $success);
    ExportHelper::cell($s3, 6, $r3, $avg);
    $r3++;
}
ExportHelper::styleRows($s3, 4, $r3 - 1, 6);
ExportHelper::addTotalsRow($s3, $r3, 6, ['B','C','D'], 4, $r3 - 1);
ExportHelper::autoFitColumns($s3, 6);

$spreadsheet->setActiveSheetIndex(0);
ExportHelper::download($spreadsheet, $filename);