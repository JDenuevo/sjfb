<?php
session_start();
require '../../../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// MySQL database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ecom';

// Connect to MySQL database
$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

// Execute the SQL query
$result = $mysqli->query($sql);

// Check if any records exist
if ($result->num_rows > 0) {
    // Excel Generation
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header Information
    $sheet->setCellValue('A1', 'Product Summary Report');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $sheet->setCellValue('A2', 'Bulungan Avenue corner HACCP St. NFPC NBBS, Navotas, Philippines Boulevard South Proper, Navotas, Philippines');
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2:G2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Table Header
    $sheet->setCellValue('A4', 'Product Name');
    $sheet->setCellValue('B4', 'Brand');
    $sheet->setCellValue('C4', 'Variant');
    $sheet->setCellValue('D4', 'Restocked');
    $sheet->setCellValue('E4', 'Released');
    $sheet->setCellValue('F4', 'Stock');
    $sheet->setCellValue('G4', 'Remarks');

    $sheet->getStyle('A4:G4')->getFont()->setBold(true);
    $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:G4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Add data rows
    $row = 5;
    $currentCategory = '';

    while ($data = $result->fetch_assoc()) {
        if ($data['category'] !== $currentCategory) {
            $currentCategory = $data['category'];

            $sheet->setCellValue('A' . $row, strtoupper($currentCategory));
            $sheet->mergeCells("A$row:G$row");

            // Styling the category header
            $style = $sheet->getStyle("A$row:G$row");
            $style->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF'); // White text
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFA500'); // Orange background (hex: #FFA500)


            $row++; // Move to next row for actual data
        }

        $sheet->setCellValue('A' . $row, $data['product_name']);
        $sheet->setCellValue('B' . $row, $data['product_brand']);
        $sheet->setCellValue('C' . $row, $data['product_variant']);
        $sheet->setCellValue('D' . $row, $data['restock']);
        $sheet->setCellValue('E' . $row, $data['released']);
        $sheet->setCellValue('F' . $row, $data['product_stock']);

        // Add your remark logic here if needed
        $sheet->setCellValue('G' . $row, '');

        $sheet->getStyle("A$row:G$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A$row:G$row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'G') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Apply border to the entire table
    $tableRange = 'A4:G' . ($row - 1);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // File output
    $fileName = "Product_Summary_Report.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
} else {
    // No records found
    echo "<script>alert('No records found for the selected filters.'); window.history.back();</script>";
}

// Close the database connection
$mysqli->close();
?>
