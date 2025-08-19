<?php
require_once('../../fpdf.php');
require_once('../../conn.php');

class WaybillPDF extends FPDF
{
    private $orderId;
    private $paymentMethod;

    public function setOrderDetails($orderId, $paymentMethod) {
        $this->orderId = $orderId;
        $this->paymentMethod = $paymentMethod;
    }

    function Header()
    {
        // Outer cell dimensions
        $logoX = 10;
        $logoY = 10;
        $logoCellW = 40;
        $logoCellH = 10;

        // Logo image size
        $logoImgW = 35;
        $logoImgH = 10;

        // Centered image inside logo cell
        $logoImgX = $logoX + ($logoCellW - $logoImgW) / 2;
        $logoImgY = $logoY + ($logoCellH - $logoImgH) / 2;

        // Right cell (Order ID / Payment)
        $rightColX = $logoX + $logoCellW;
        $rightColW = 40;
        $rowHeight = $logoCellH / 2;

        // Draw logo
        $this->Rect($logoX, $logoY, $logoCellW, $logoCellH);
        $this->Image('../../assets/icons/landscape-logo.png', $logoImgX, $logoImgY, $logoImgW, $logoImgH);

        // Order ID
        $this->SetXY($rightColX, $logoY);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($rightColW, $rowHeight, 'Order ID: ' . $this->orderId, 1, 2, 'C');

        // Payment Method
        $paymentText = ($this->paymentMethod == 'cod') ? 'Cash on Delivery' : ucfirst($this->paymentMethod);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($rightColW, $rowHeight, $paymentText, 1, 2, 'C');

        // Reset position for next content like Buyer's Name
        $this->SetY($logoY + $logoCellH + 5); // Added extra space below header
        $this->SetX($logoX); // reset to left margin
    }

    function BuyerSection($buyerData)
    {
        $this->SetFont('Arial', 'B', 8);

        // Draw merged BUYER cell on the left (4 rows high × 5 = 20 height)
        $buyerLabelHeight = 30;
        $this->MultiCell(20, $buyerLabelHeight, 'BUYER', 1, 'C');

        // Move to the right of the BUYER cell
        $this->SetXY($this->GetX() + 20, $this->GetY() - $buyerLabelHeight); // Align Y to top of label, move X to the right

        // Right section content
        $this->SetFont('Arial', '', 7);

        // Name Row
        $this->Cell(0, 5, $buyerData['first_name'] . ' ' . $buyerData['last_name'], 1, 1);

        // Address Row
        $this->SetX(30); // Align under name
        $this->MultiCell(0, 5, $buyerData['address'], 1);

        // City, Province, Postal Code Row
        $this->SetX(30);
        $this->Cell(23, 5, $buyerData['city'], 1, 0);
        $this->Cell(23, 5, 'Metro Manila', 1, 0);
        $this->Cell(0, 5, $buyerData['postal_code'], 1, 1);

        // Phone number Row
        $this->SetX(30);
        $this->Cell(0, 5, $buyerData['phone_number'], 1, 1);
    }

    function SellerSection()
    {
        $this->SetFont('Arial', 'B', 8);

        // Draw merged SELLER cell on the left (3 rows high × 5 = 15 height, adjust if needed)
        $sellerLabelHeight = 15;
        $this->MultiCell(20, $sellerLabelHeight, 'SELLER', 1, 'C');

        // Move to the right of the SELLER cell
        $this->SetXY($this->GetX() + 20, $this->GetY() - $sellerLabelHeight); // Align Y to top of label, move X to the right

        // Right section content
        $this->SetFont('Arial', '', 7);

        // Seller name
        $this->Cell(0, 5, 'St. Joseph Fish Brokerage Inc.', 1, 1);

        // Address
        $this->SetX(30);
        $this->MultiCell(0, 5, 'Bulungan Ave, NFPC NBBS, Navotas', 1);

        // City, Province, Postal Code Row
        $this->SetX(30);
        $this->Cell(23, 5, 'Navotas City', 1, 0);
        $this->Cell(23, 5, 'Metro Manila', 1, 0);
        $this->Cell(0, 5, '1411', 1, 1);
    }

    function DeliveryDetails()
    {
        $cellHeight = 10;
        $startY = $this->GetY();

        $this->SetFont('Arial', '', 7);
        
        // First row headers
        $this->Cell(20, 5, 'Quantity: pcs', 1, 0);
        $this->Cell(30, 5, 'Delivery Attempt', 1, 0, 'C');
        $this->Cell(30, 5, 'Return Attempt', 1, 1, 'C');

        // Second row inputs
        $this->Cell(20, 5, 'Weight: kg', 1, 0);
        
        // Attempt numbers
        $this->Cell(10, 5, '1', 1, 0, 'C');
        $this->Cell(10, 5, '2', 1, 0, 'C');
        $this->Cell(10, 5, '3', 1, 0, 'C');
        $this->Cell(10, 5, '1', 1, 0, 'C');
        $this->Cell(10, 5, '2', 1, 0, 'C');
        $this->Cell(10, 5, '3', 1, 1, 'C');

        // Input boxes
        $this->Cell(20, $cellHeight, '', 1, 0);
        $this->Cell(10, $cellHeight, '', 1, 0, 'C');
        $this->Cell(10, $cellHeight, '', 1, 0, 'C');
        $this->Cell(10, $cellHeight, '', 1, 0, 'C');
        $this->Cell(10, $cellHeight, '', 1, 0, 'C');
        $this->Cell(10, $cellHeight, '', 1, 0, 'C');
        $this->Cell(10, $cellHeight, '', 1, 1, 'C');
        
        // Add some space after the section
        $this->SetY($this->GetY() + 5);
    }
}

// Get order ID from POST
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if ($order_id <= 0) {
    die('Invalid order ID');
}

// Fetch order details
$order_query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die('Order not found');
}

$order = $order_result->fetch_assoc();

// Create PDF with custom size
$pdf = new WaybillPDF('P', 'mm', [100, 150]);
$pdf->setOrderDetails(
    $order['order_id'],
    $order['payment_method']
);

$pdf->AddPage();
$pdf->BuyerSection($order);
$pdf->SellerSection();
$pdf->DeliveryDetails();

// Output PDF for download
$pdf->Output('D', 'Waybill_Order_' . $order_id . '.pdf');

// Close database connection
$conn->close();
?>