<?php
/**
 * export_inventory.php — Product inventory + low-stock alerts
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * GET params (all optional):
 *   ?low_stock_threshold=10
 *
 * Place in: /supadmin/exports/export_inventory.php
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

$lowThreshold = max(1, (int) ($_GET['low_stock_threshold'] ?? 10));

// ── Query ─────────────────────────────────────────────────────────────────────
$sql = "
    SELECT
        p.product_name,
        p.product_unit,
        pv.variant_name,
        pv.unit_type,
        pv.variant_price,
        COALESCE(pv.discount_price, pv.variant_price)       AS selling_price,
        pv.stock_quantity,
        pv.stock_status,
        pv.minimum_order,
        pv.order_increment,
        IF(pv.is_hidden=1,'Hidden','Visible')                AS visibility,
        IF(p.is_deleted=1,'Deleted','Active')                AS product_status,
        GROUP_CONCAT(pc.category_name
            ORDER BY pc.category_name SEPARATOR ', ')        AS categories,
        COALESCE(sold.total_sold, 0)                         AS total_units_sold
    FROM products p
    JOIN product_variants pv ON p.product_id = pv.product_id
    LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
    LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
    LEFT JOIN (
        SELECT oi.variant_id, SUM(oi.quantity) AS total_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.is_deleted = 0 AND o.order_status NOT IN ('Cancelled')
        GROUP BY oi.variant_id
    ) sold ON pv.variant_id = sold.variant_id
    WHERE pv.is_deleted = 0
    GROUP BY pv.variant_id
    ORDER BY p.product_name, pv.variant_name
";

$result = $conn->query($sql);
if (!$result) die('Query error: ' . $conn->error);

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$lowStockRows   = array_values(array_filter($rows, fn($r) => (int)$r['stock_quantity'] <= $lowThreshold && (int)$r['stock_quantity'] > 0));
$outOfStockRows = array_values(array_filter($rows, fn($r) => (int)$r['stock_quantity'] === 0));

$filename = 'Inventory_Export_' . date('Ymd_His');
$subtitle = 'Generated: ' . date('F j, Y g:i A')
          . '  |  Total variants: ' . count($rows)
          . '  |  Low stock (≤'.$lowThreshold.'): ' . count($lowStockRows)
          . '  |  Out of stock: ' . count($outOfStockRows);

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()->setTitle('Inventory Export');

$headers = [
    'Product Name','Product Unit','Variant','Unit Type',
    'Regular Price','Selling Price','Stock Qty','Stock Status',
    'Min Order','Order Increment','Visibility','Product Status',
    'Categories','Total Units Sold',
];
$colCount = count($headers); // 14

// ── Reusable sheet builder ────────────────────────────────────────────────────
$buildSheet = function ($sheet, array $data, string $title)
    use ($headers, $colCount, $subtitle, $lowThreshold)
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
        ExportHelper::cell($sheet,  1, $r, $row['product_name']);
        ExportHelper::cell($sheet,  2, $r, $row['product_unit']);
        ExportHelper::cell($sheet,  3, $r, $row['variant_name']);
        ExportHelper::cell($sheet,  4, $r, $row['unit_type']);
        ExportHelper::cell($sheet,  5, $r, (float) $row['variant_price']);
        ExportHelper::cell($sheet,  6, $r, (float) $row['selling_price']);
        ExportHelper::cell($sheet,  7, $r, (int)   $row['stock_quantity']);
        ExportHelper::cell($sheet,  8, $r, $row['stock_status']);
        ExportHelper::cell($sheet,  9, $r, (float) $row['minimum_order']);
        ExportHelper::cell($sheet, 10, $r, (float) $row['order_increment']);
        ExportHelper::cell($sheet, 11, $r, $row['visibility']);
        ExportHelper::cell($sheet, 12, $r, $row['product_status']);
        ExportHelper::cell($sheet, 13, $r, $row['categories'] ?? '');
        ExportHelper::cell($sheet, 14, $r, (int)   $row['total_units_sold']);

        $qty = (int) $row['stock_quantity'];
        if ($qty === 0) {
            ExportHelper::colorCell($sheet, 'G'.$r, 'FFFFEBEE', 'FFCC0000');
            ExportHelper::colorCell($sheet, 'H'.$r, 'FFFFEBEE', 'FFCC0000');
        } elseif ($qty <= $lowThreshold) {
            ExportHelper::colorCell($sheet, 'G'.$r, 'FFFFF3E0', 'FFE65100');
        }
        if ($row['visibility'] === 'Hidden') {
            $sheet->getStyle('K'.$r)->getFont()->setItalic(true)->getColor()->setARGB('FF9E9E9E');
        }
    }

    $dEnd = $dStart + count($data) - 1;
    ExportHelper::styleRows($sheet, $dStart, $dEnd, $colCount);
    ExportHelper::formatCurrency($sheet, 'E'.$dStart.':F'.$dEnd);
    ExportHelper::autoFitColumns($sheet, $colCount);
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('M')->setWidth(35);
};

$buildSheet($spreadsheet->getActiveSheet(), $rows, 'All Products');

$s2 = $spreadsheet->createSheet();
$buildSheet($s2, $lowStockRows, 'Low Stock Alert');

$s3 = $spreadsheet->createSheet();
$buildSheet($s3, $outOfStockRows, 'Out of Stock');

$spreadsheet->setActiveSheetIndex(0);
ExportHelper::download($spreadsheet, $filename);