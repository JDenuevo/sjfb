<?php
session_start();
require '../../vendor/autoload.php'; // For PhpSpreadsheet
require('../../fpdf.php'); // Include FPDF for PDF generation

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
    
if ($result && $result->num_rows > 0) {
    if ($format == 'pdf') {
        // PDF Generation
        class PDF extends FPDF {
            private $logo; // Store the logo
            private $title; // Store the title
    
            public function __construct($logo, $title) {
                parent::__construct();
                $this->logo = $logo;
                $this->title = $title;
            }
    
            function Header() {
                // Logo
                $this->Image($this->logo, 10, 10, 33);
                
                // Title
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 7, $this->title, 0, 1, 'C');
            
                // Address (2-line and centered)
                $this->SetFont('Times', 'I', 9);
                
                // Center the first line manually
                $this->SetX(($this->w - 160) / 2); // 160 is the width of the text block
                $this->MultiCell(160, 5, 'Fish Port Complex, Bulungan Avenue, Corner HACCP St,', 0, 'C');
                
                // Center the second line manually
                $this->SetX(($this->w - 160) / 2);
                $this->MultiCell(160, 5, 'N Bay Blvd, South Proper, Navotas', 0, 'C');
                
                // Add space below the header
                $this->Ln(10);
            }
            
            function Body($data) {
                // Group data by month
                $groupedData = [];
                foreach ($data as $row) {
                    // Check if last_updated exists and is a valid date
                    if (isset($row['last_updated']) && strtotime($row['last_updated']) !== false) {
                        $monthYear = date('F Y', strtotime($row['last_updated']));
                    } else {
                        // Default to 'No Date' if last_updated is missing or invalid
                        $monthYear = 'No Date';
                    }
                    $groupedData[$monthYear][] = $row;
                }
            
                // Table headers
                $this->SetFont('Arial', 'B', 10);
                foreach ($groupedData as $monthYear => $rows) {
                    // Add the month header
                    $this->SetFont('Arial', 'B', 12);
                    $this->Cell(0, 3, "Summary in the month of $monthYear", 0, 1, 'C');
                    $this->Ln(2);
            
                     // Table headers (ensure same width for headers and cells)
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(35, 6, 'Product Name', 1, 0, 'C');
                    $this->Cell(20, 6, 'Brand', 1, 0, 'C');
                    $this->Cell(20, 6, 'Variant', 1, 0, 'C');
                    $this->Cell(30, 6, 'Category', 1, 0, 'C');
                    $this->Cell(15, 6, 'Cost', 1, 0, 'C');
                    $this->Cell(25, 6, 'Purchases', 1, 0, 'C');
                    $this->Cell(25, 6, 'Released', 1, 0, 'C');
                    $this->Cell(20, 6, 'Stock', 1, 0, 'C');
                    $this->Ln();
            
                    // Table data for the current month
                    $this->SetFont('Arial', '', 10);
                    foreach ($rows as $row) {
                        // Calculate the heights of the cells
                        $requestNameHeight = $this->GetStringHeight(40, $row['product_name'] ?? 'N/A');
                        $productNameHeight = 6; // Fixed height for product name
                        $cellHeight = max($requestNameHeight, $productNameHeight); // Use the greater height

                        // Product Name (MultiCell)
                        $x = $this->GetX();
                        $y = $this->GetY();
                        $this->MultiCell(35, 6, $row['product_name'] ?? 'N/A', 1, 'C');
                        $this->SetXY($x + 35, $y); // Move to the next column

                        // Other columns...
                        $this->Cell(20, $cellHeight, $row['product_brand'] ?? 'N/A', 1, 0, 'C');
                        $this->Cell(20, $cellHeight, $row['product_variant'] ?? 'N/A', 1, 0, 'C');
                        $this->Cell(30, $cellHeight, $row['category'] ?? 'N/A', 1, 0, 'C');
                        $this->Cell(15, $cellHeight, $row['product_cost'] ?? '0', 1, 0, 'C');
                        $this->Cell(25, $cellHeight, $row['purchases'] ?? '0', 1, 0, 'C');
                        $this->Cell(25, $cellHeight, $row['released'] ?? '0', 1, 0, 'C');
                        $this->Cell(20, $cellHeight, $row['product_stock'] ?? '0', 1, 0, 'C');
                        $this->Ln();
                    }

                    // Add some spacing after each month's data
                    $this->Ln(5);
                }
            }            
            
            // Helper function to calculate string height based on the width of the cell
            function GetStringHeight($w, $txt) {
                // Save current font settings
                $cw = $this->CurrentFont['cw'];
                $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                $s = str_replace("\r", '', $txt);
                $nb = strlen($s);
                if ($nb > 0 and $s[$nb - 1] == "\n") {
                    $nb--;
                }
                $sep = -1;
                $i = 0;
                $j = 0;
                $l = 0;
                $ns = 0;
                $height = 0;
                $line = 1;
                while ($i < $nb) {
                    $c = $s[$i];
                    if ($c == "\n") {
                        $line++;
                        $i++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $ns = 0;
                    } elseif ($c == ' ') {
                        $sep = $i;
                        $ns++;
                    }
                    $l += $cw[$c];
                    if ($l > $wmax) {
                        if ($sep == -1) {
                            if ($i == $j) {
                                $i++;
                            }
                        } else {
                            $i = $sep + 1;
                        }
                        $line++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $ns = 0;
                    } else {
                        $i++;
                    }
                }
                $height = $line * 6; // Multiply by line height (6 for Arial 10pt)
                return $height;
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
            }
        }

        // Create a new PDF document
        $logo = '../../assets/images/logos/logo.svg'; // Set your logo path
        $title = "Summary Report"; // Use dynamic title here
        $pdf = new PDF($logo, $title);

        // Set auto page break and add the first page
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Collect data for the PDF body
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // Call the Body function to generate the table
        $pdf->Body($data);

        // Output the PDF to the browser
        $pdf->Output('D', 'summary_report.pdf'); // D for download

        }
        else {
            echo "No records found.";
        }
    }
    
}

// Close the database connection
$mysqli->close();    
?>
