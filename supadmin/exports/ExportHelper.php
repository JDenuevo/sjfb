<?php
/**
 * ExportHelper.php
 * Shared utilities for all Excel exports.
 * Compatible: PhpSpreadsheet 2.x / 3.x / 5.x
 *
 * Place in: /supadmin/exports/ExportHelper.php
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportHelper
{
    const COLOR_HEADER_BG   = 'FF1E3A5F'; // dark navy — adjust to your brand
    const COLOR_HEADER_TEXT = 'FFFFFFFF';
    const COLOR_BORDER      = 'FFB0BEC5';
    const COLOR_ODD_ROW     = 'FFFAFAFA';
    const COLOR_EVEN_ROW    = 'FFFFFFFF';
    const FONT_NAME         = 'Arial';

    /**
     * Stream the spreadsheet to the browser as a download.
     */
    public static function download(Spreadsheet $spreadsheet, string $filename): void
    {
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * Write a cell using [col, row] array — the v2+ API.
     * Column is 1-based: 1 = A, 2 = B, etc.
     */
    public static function cell($sheet, int $col, int $row, mixed $value): void
    {
        $sheet->setCellValue([$col, $row], $value);
    }

    /**
     * Apply standard header row styling.
     */
    public static function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name'  => self::FONT_NAME,
                'bold'  => true,
                'size'  => 10,
                'color' => ['argb' => self::COLOR_HEADER_TEXT],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => self::COLOR_BORDER],
                ],
            ],
        ]);
    }

    /**
     * Apply alternating row background colors.
     */
    public static function styleRows($sheet, int $startRow, int $endRow, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        for ($row = $startRow; $row <= $endRow; $row++) {
            $bg = ($row % 2 === 0) ? self::COLOR_EVEN_ROW : self::COLOR_ODD_ROW;
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                'font'      => ['name' => self::FONT_NAME, 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                 'color'       => ['argb' => self::COLOR_BORDER]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
    }

    /**
     * Write two-row title block above the column headers.
     * Returns row 3 — always write your column headers there.
     */
    public static function addReportTitle($sheet, string $title, string $subtitle, int $colSpan): int
    {
        $lastCol = Coordinate::stringFromColumnIndex($colSpan);

        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['name' => self::FONT_NAME, 'bold' => true, 'size' => 13,
                            'color' => ['argb' => self::COLOR_HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['name' => self::FONT_NAME, 'size' => 9,
                            'color' => ['argb' => 'FF607D8B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(16);

        return 3; // caller writes headers on this row
    }

    /**
     * Freeze the header row so it stays visible while scrolling.
     */
    public static function freezeHeader($sheet, int $freezeRow = 4): void
    {
        $sheet->freezePane('A' . $freezeRow);
    }

    /**
     * Format a range as Philippine Peso.
     */
    public static function formatCurrency($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    /**
     * Auto-size every column up to $colCount.
     */
    public static function autoFitColumns($sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    /**
     * Append a navy totals row with SUM formulas.
     * $numericCols: column letters to sum, e.g. ['E','F','G']
     */
    public static function addTotalsRow(
        $sheet, int $totalRow, int $colCount,
        array $numericCols, int $dataStart, int $dataEnd,
        string $label = 'TOTAL'
    ): void {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->setCellValue('A' . $totalRow, $label);

        foreach ($numericCols as $col) {
            $sheet->setCellValue($col . $totalRow,
                '=SUM(' . $col . $dataStart . ':' . $col . $dataEnd . ')');
        }

        $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
            'font' => ['name' => self::FONT_NAME, 'bold' => true, 'size' => 10,
                       'color' => ['argb' => self::COLOR_HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => self::COLOR_HEADER_BG]],
        ]);
    }

    /**
     * Helper: colour a single cell (status badges, flags, etc.)
     */
    public static function colorCell($sheet, string $cellRef, string $bgArgb, string $fgArgb): void
    {
        $sheet->getStyle($cellRef)->applyFromArray([
            'font' => ['color' => ['argb' => $fgArgb], 'bold' => true, 'size' => 9,
                       'name' => self::FONT_NAME],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgArgb]],
        ]);
    }
}