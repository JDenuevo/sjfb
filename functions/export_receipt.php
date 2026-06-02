<?php
// functions/export_receipt.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';

error_reporting(0);
ini_set('display_errors', 0);

class PDF extends FPDF
{
    public string $orderType   = '';
    public string $paymentNote = '';

    function Header() {}

    function Footer()
    {
        $this->SetY(-14);
        $this->SetFont('Helvetica', 'I', 7);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, 'THIS DOCUMENT IS SYSTEM GENERATED - NO SIGNATURE REQUIRED', 0, 1, 'C');
        $this->Cell(0, 5, 'St. Joseph Fish Brokerage Inc.  |  (+63) 946-497-3689  |  fisbrokers.net', 0, 1, 'C');
    }
}

if (!isset($_GET['order_code'])) die('Order code required');

$orderCode = $_GET['order_code'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->bind_param("s", $orderCode);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) die('Order not found');

$orderId  = $order['order_id'];
$isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';
$isCOP    = $order['payment_method'] === 'cop';
$isOnline = in_array($order['payment_method'], ['gcash','paymaya','grab_pay','card','qrph']);

$itemsStmt = $conn->prepare("
    SELECT oi.*, p.product_name, v.variant_name, v.variant_price
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

function getPaymentMethodDisplay(string $method): string {
    return [
        'gcash'    => 'GCash',
        'paymaya'  => 'Maya',
        'grab_pay' => 'GrabPay',
        'card'     => 'Credit / Debit Card',
        'cop'      => 'Cash on Pickup',
        'qrph'     => 'QR Ph',
    ][$method] ?? ucfirst($method);
}

/**
 * Convert a UTF-8 string to FPDF-safe latin-1.
 * Replaces common problematic UTF-8 chars with ASCII equivalents FIRST,
 * then utf8_decode() handles the rest.
 */
function safe(string $str): string {
    $replacements = [
        // Dashes
        "\xe2\x80\x94" => ' - ',   // em dash  —
        "\xe2\x80\x93" => ' - ',   // en dash  -
        "\xe2\x80\x90" => '-',     // hyphen
        // Quotes
        "\xe2\x80\x9c" => '"',     // left double quote
        "\xe2\x80\x9d" => '"',     // right double quote
        "\xe2\x80\x98" => "'",     // left single quote
        "\xe2\x80\x99" => "'",     // right single quote
        // Ellipsis
        "\xe2\x80\xa6" => '...',   // ellipsis
        // Bullets
        "\xe2\x80\xa2" => '*',     // bullet
        // Non-breaking space
        "\xc2\xa0"     => ' ',
    ];
    $str = str_replace(array_keys($replacements), array_values($replacements), $str);
    return utf8_decode(htmlspecialchars_decode(htmlspecialchars($str, ENT_QUOTES, 'UTF-8')));
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 9);

$pageW = 180;

// ── HEADER ────────────────────────────────────────────────────────────────
$logoPath = __DIR__ . '/../assets/icons/square-logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 14, 38);
    $pdf->SetY(14);
} else {
    $pdf->SetXY(15, 14);
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(38, 10, 'SJFBI', 0, 0, 'C', true);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Ln(11);
    $pdf->SetX(15);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(38, 4, 'St. Joseph Fish Brokerage', 0, 1, 'C');
}

$pdf->SetXY(15, 12);
$pdf->SetFont('Helvetica', 'B', 26);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($pageW, 12, 'ACKNOWLEDGEMENT', 0, 1, 'R');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', 'B', 26);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($pageW, 10, 'RECEIPT', 0, 1, 'R');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($pageW, 5, 'Receipt No.: ' . $orderCode, 0, 1, 'R');
$pdf->SetX(15);
$pdf->Cell($pageW, 5, 'Date of Issue: ' . date('m/d/Y', strtotime($order['order_date'])), 0, 1, 'R');
$pdf->SetTextColor(30, 30, 30);
$pdf->Ln(4);

$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($pageW, 0.8, '', 0, 1, 'L', true);
$pdf->Ln(5);

// ── BILL TO | ORDER TYPE | PAYMENT METHOD ────────────────────────────────
$colW = $pageW / 3;

$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($colW, 5, 'BILL TO', 0, 0, 'L');
$pdf->Cell($colW, 5, 'ORDER TYPE', 0, 0, 'C');
$pdf->Cell($colW, 5, 'PAYMENT METHOD', 0, 1, 'R');

$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(30, 30, 30);
$customerName   = safe($order['recipient_first_name'] . ' ' . $order['recipient_last_name']);
$orderTypeLabel = $isPickup ? 'For Pickup' : 'Delivery';
$paymentLabel   = getPaymentMethodDisplay($order['payment_method']);

$pdf->Cell($colW, 5, $customerName, 0, 0, 'L');
$pdf->Cell($colW, 5, $orderTypeLabel, 0, 0, 'C');
$pdf->Cell($colW, 5, $paymentLabel, 0, 1, 'R');

$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(80, 80, 80);

if ($isPickup) {
    $pdf->Cell($colW, 4, safe($order['recipient_email']), 0, 0, 'L');
    $pdf->Cell($colW, 4, 'Main Store, Navotas', 0, 0, 'C');
} else {
    $pdf->Cell($colW, 4, safe($order['recipient_email']), 0, 0, 'L');
    $pdf->Cell($colW, 4, '', 0, 0, 'C');
}

if ($isCOP) {
    $pdf->Cell($colW, 4, 'Pay at Pickup', 0, 1, 'R');
} elseif ($isOnline) {
    $pdf->Cell($colW, 4, 'Paid Online', 0, 1, 'R');
} else {
    $pdf->Cell($colW, 4, '', 0, 1, 'R');
}

$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell($colW, 4, safe($order['recipient_phone']), 0, 1, 'L');
$pdf->Ln(4);

$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($pageW, 0.8, '', 0, 1, 'L', true);
$pdf->Ln(3);

// ── ITEMS TABLE ───────────────────────────────────────────────────────────
$cDesc  = 95;
$cQty   = 15;
$cPrice = 35;
$cAmt   = 35;

$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->Cell($cDesc,  7, 'DESCRIPTION', 0, 0, 'L', true);
$pdf->Cell($cQty,   7, 'QTY',         0, 0, 'C', true);
$pdf->Cell($cPrice, 7, 'UNIT PRICE',  0, 0, 'R', true);
$pdf->Cell($cAmt,   7, 'AMOUNT',      0, 1, 'R', true);

$calcSubtotal = 0;
$pdf->SetTextColor(30, 30, 30);

foreach ($items as $i => $item) {
    $lineTotal     = (float)$item['price'] * (int)$item['quantity'];
    $calcSubtotal += $lineTotal;
    $fill          = ($i % 2 === 0);
    if ($fill) $pdf->SetFillColor(250, 250, 250);

    // Use plain ASCII hyphen-space for variant separator
    $variantPart = $item['variant_name'] ? ' - ' . $item['variant_name'] : '';
    $desc = safe($item['product_name'] . $variantPart);

    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell($cDesc,  6, $desc,                                              0, 0, 'L', $fill);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Cell($cQty,   6, $item['quantity'],                                  0, 0, 'C', $fill);
    $pdf->Cell($cPrice, 6, 'PHP ' . number_format((float)$item['price'], 2),   0, 0, 'R', $fill);
    $pdf->Cell($cAmt,   6, 'PHP ' . number_format($lineTotal, 2),              0, 1, 'R', $fill);
}

$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($pageW, 0.4, '', 0, 1, 'L', true);
$pdf->Ln(4);

// ── NOTES + TOTALS ────────────────────────────────────────────────────────
$deliveryFee    = (float)($order['delivery_fee']    ?? 0);
$discountAmount = (float)($order['discount_amount'] ?? 0);
$total          = (float)$order['total_price'];
$voucherCode    = $order['voucher_code'] ?? '';

$notesW  = 95;
$totalsW = $pageW - $notesW;

$yBeforeTotals = $pdf->GetY();

// Left — notes
$pdf->SetXY(15, $yBeforeTotals);
$pdf->SetFont('Helvetica', '', 7.5);
$pdf->SetTextColor(80, 80, 80);

if ($isPickup && $isCOP) {
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->MultiCell($notesW - 5, 4.5, 'PAYMENT DUE AT PICKUP', 0, 'L');
    $pdf->SetXY(15, $pdf->GetY());
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell($notesW - 5, 4.5,
        'Please visit our store and present this acknowledgement receipt together with your order code ' . $orderCode . '. ' .
        'Payment of PHP ' . number_format($total, 2) . ' is due upon collection of your items.', 0, 'L');
} elseif ($isPickup && $isOnline) {
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->MultiCell($notesW - 5, 4.5, 'FOR PICKUP - ORDER CONFIRMED', 0, 'L');
    $pdf->SetXY(15, $pdf->GetY());
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell($notesW - 5, 4.5,
        'Your order has been confirmed. Please provide the payment at the pickup location. ' .
        'Present this acknowledgement receipt and your order code ' . $orderCode . ' at our store.', 0, 'L');
} elseif ($isOnline) {
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->MultiCell($notesW - 5, 4.5, 'ONLINE PAYMENT CONFIRMED', 0, 'L');
    $pdf->SetXY(15, $pdf->GetY());
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell($notesW - 5, 4.5,
        'Your online payment has been confirmed. Thank you for your purchase! ' .
        'Our team will process your delivery shortly.', 0, 'L');
} else {
    $pdf->MultiCell($notesW - 5, 4.5,
        'Thank you for your order. For inquiries please contact us at (+63) 946-497-3689 or visit fisbrokers.net.', 0, 'L');
}

$yAfterNotes = $pdf->GetY();

// Right — totals
$totalsX = 15 + $notesW;
$lW      = 50;
$vW      = $totalsW - $lW;

$pdf->SetXY($totalsX, $yBeforeTotals);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(80, 80, 80);

$pdf->SetX($totalsX);
$pdf->Cell($lW, 6, 'SUBTOTAL', 0, 0, 'L');
$pdf->Cell($vW, 6, 'PHP ' . number_format($calcSubtotal, 2), 0, 1, 'R');

if ($discountAmount > 0) {
    $discLabel = !empty($voucherCode) ? 'DISCOUNT (' . $voucherCode . ')' : 'DISCOUNT';
    $pdf->SetX($totalsX);
    $pdf->SetTextColor(22, 163, 74);
    $pdf->Cell($lW, 6, $discLabel, 0, 0, 'L');
    $pdf->Cell($vW, 6, '-PHP ' . number_format($discountAmount, 2), 0, 1, 'R');
    $pdf->SetTextColor(80, 80, 80);
}

$pdf->SetX($totalsX);
$pdf->Cell($lW, 6, 'DELIVERY FEE', 0, 0, 'L');
if ($isPickup || $deliveryFee === 0.0) {
    $pdf->SetTextColor(22, 163, 74);
    $pdf->Cell($vW, 6, $isPickup ? 'FREE (Pickup)' : 'FREE', 0, 1, 'R');
    $pdf->SetTextColor(80, 80, 80);
} else {
    $pdf->Cell($vW, 6, 'PHP ' . number_format($deliveryFee, 2), 0, 1, 'R');
}

$pdf->SetX($totalsX);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($totalsW, 0.5, '', 0, 1, 'L', true);

$pdf->SetX($totalsX);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($lW, 8, 'TOTAL', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($vW, 8, 'PHP ' . number_format($total, 2), 0, 1, 'R');
$pdf->SetTextColor(30, 30, 30);

$yAfterTotals = $pdf->GetY();
$pdf->SetY(max($yAfterNotes, $yAfterTotals) + 6);

// ── DIVIDER + SIGNATURE ───────────────────────────────────────────────────
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($pageW, 0.4, '', 0, 1, 'L', true);
$pdf->Ln(5);

$sigColW = $pageW / 2 - 10;
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($sigColW, 5, '', 'B', 0, 'C');
$pdf->Cell(20, 5, '', 0, 0);
$pdf->Cell($sigColW, 5, '', 'B', 1, 'C');

$pdf->SetFont('Helvetica', '', 7);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($sigColW, 4, 'Date', 0, 0, 'C');
$pdf->Cell(20, 4, '', 0, 0);
$pdf->Cell($sigColW, 4, 'Signature of Authorized Person', 0, 1, 'C');

$pdf->Output('D', 'Receipt_' . $orderCode . '.pdf');
exit;