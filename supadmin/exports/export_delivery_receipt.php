<?php
// functions/export_delivery_receipt.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../conn.php'; // provides $conn (mysqli)

error_reporting(0);
ini_set('display_errors', 0);

class DeliveryPDF extends FPDF
{
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

// ── Fetch order ───────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->bind_param("s", $orderCode);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) die('Order not found');

$orderId  = $order['order_id'];
$isPickup = ($order['order_type'] ?? 'delivery') === 'pickup';
$isCOP    = $order['payment_method'] === 'cop';
$isOnline = in_array($order['payment_method'], ['gcash','paymaya','grab_pay','card','qrph']);

// ── Fetch order items ─────────────────────────────────────────────────────
$itemsStmt = $conn->prepare("
    SELECT oi.*, p.product_name, v.variant_name, v.unit_type
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Fetch delivery info ───────────────────────────────────────────────────
$delStmt = $conn->prepare("
    SELECT d.*,
           r.rider_name,
           r.vehicle_type,
           r.vehicle_plate_number,
           r.rider_phone,
           IF(d.is_third_party = 1, d.third_party_name, r.rider_name) AS courier_name
    FROM deliveries d
    LEFT JOIN riders r ON d.rider_id = r.rider_id
    WHERE d.order_id = ?
    ORDER BY d.delivery_id DESC
    LIMIT 1
");
$delStmt->bind_param("i", $orderId);
$delStmt->execute();
$delivery = $delStmt->get_result()->fetch_assoc();

// ── Helpers ───────────────────────────────────────────────────────────────
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

function safe(string $str): string {
    $replacements = [
        "\xe2\x80\x94" => ' - ',
        "\xe2\x80\x93" => ' - ',
        "\xe2\x80\x90" => '-',
        "\xe2\x80\x9c" => '"',
        "\xe2\x80\x9d" => '"',
        "\xe2\x80\x98" => "'",
        "\xe2\x80\x99" => "'",
        "\xe2\x80\xa6" => '...',
        "\xe2\x80\xa2" => '*',
        "\xc2\xa0"     => ' ',
    ];
    $str = str_replace(array_keys($replacements), array_values($replacements), $str);
    return utf8_decode(htmlspecialchars_decode(htmlspecialchars($str, ENT_QUOTES, 'UTF-8')));
}

function fmtDate(?string $dt, string $fallback = 'N/A'): string {
    if (empty($dt) || $dt === '0000-00-00 00:00:00') return $fallback;
    return date('M d, Y  g:i A', strtotime($dt));
}

function infoRow(object $pdf, string $label, string $value, int $pageW): void {
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(45, 6, $label . ':', 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell($pageW - 45, 6, $value, 0, 1, 'L');
}

// ── Build PDF ─────────────────────────────────────────────────────────────
$pdf = new DeliveryPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 9);

$pageW = 180;

// ═══════════════════════════════════════════════════════════════════════════
// HEADER — Logo left | Title right
// ═══════════════════════════════════════════════════════════════════════════
$logoPath = __DIR__ . '/../../assets/icons/square-logo.png';
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
$pdf->Cell($pageW, 12, 'DELIVERY', 0, 1, 'R');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', 'B', 26);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($pageW, 10, 'RECEIPT', 0, 1, 'R');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($pageW, 5, 'Order No.: ' . $orderCode, 0, 1, 'R');
$pdf->SetX(15);
$pdf->Cell($pageW, 5, 'Date Issued: ' . date('m/d/Y', strtotime($order['order_date'])), 0, 1, 'R');
if (!empty($delivery['delivered_at'])) {
    $pdf->SetX(15);
    $pdf->Cell($pageW, 5, 'Date Delivered: ' . date('m/d/Y', strtotime($delivery['delivered_at'])), 0, 1, 'R');
}
$pdf->SetTextColor(30, 30, 30);
$pdf->Ln(3);

// Divider
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($pageW, 0.8, '', 0, 1, 'L', true);
$pdf->Ln(4);

// ═══════════════════════════════════════════════════════════════════════════
// TWO-COLUMN INFO: Recipient left | Delivery Info right
// ═══════════════════════════════════════════════════════════════════════════
$halfW = $pageW / 2 - 5;
$yInfo = $pdf->GetY();

// ── LEFT: Recipient ───────────────────────────────────────────────────────
$pdf->SetXY(15, $yInfo);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($halfW, 5, 'RECIPIENT', 0, 1, 'L');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($halfW, 5,
    safe($order['recipient_first_name'] . ' ' . $order['recipient_last_name']), 0, 1, 'L');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell($halfW, 4, safe($order['recipient_phone']), 0, 1, 'L');
$pdf->SetX(15);
$pdf->Cell($halfW, 4, safe($order['recipient_email']), 0, 1, 'L');

// Delivery address
$pdf->Ln(2);
$pdf->SetX(15);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($halfW, 5, 'DELIVERY ADDRESS', 0, 1, 'L');

$pdf->SetX(15);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(30, 30, 30);
if ($isPickup) {
    $pdf->Cell($halfW, 4, 'FOR PICKUP - Main Store, Navotas', 0, 1, 'L');
    $pdf->SetX(15);
    $pdf->Cell($halfW, 4, 'Bulungan Ave corner HACCP St., NFPC NBBS', 0, 1, 'L');
} else {
    $pdf->MultiCell($halfW, 4, safe($order['recipient_address']), 0, 'L');
    $pdf->SetX(15);
    $pdf->Cell($halfW, 4, safe($order['city'] . (!empty($order['postal_code']) ? '  ' . $order['postal_code'] : '')), 0, 1, 'L');
}

if (!empty($order['delivery_notes'])) {
    $pdf->Ln(1);
    $pdf->SetX(15);
    $pdf->SetFont('Helvetica', 'I', 7.5);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->MultiCell($halfW, 4, 'Note: ' . safe($order['delivery_notes']), 0, 'L');
}

$yAfterLeft = $pdf->GetY();

// ── RIGHT: Delivery & courier info ───────────────────────────────────────
$rightX = 15 + $halfW + 10;
$pdf->SetXY($rightX, $yInfo);

$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell($halfW, 5, 'DELIVERY INFORMATION', 0, 1, 'L');

$pdf->SetXY($rightX, $pdf->GetY());
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(30, 30, 30);

// Order type badge
$badgeLabel = $isPickup ? 'For Pickup' : 'Standard Delivery';
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetFillColor(230, 230, 230);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetX($rightX);
$pdf->Cell($halfW, 6, '  ' . $badgeLabel, 0, 1, 'L', true);

$pdf->Ln(2);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(80, 80, 80);

// Payment method
$pdf->SetX($rightX);
$pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Payment:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, getPaymentMethodDisplay($order['payment_method']), 0, 1, 'L');

// Payment status
$payStatus = $isCOP ? 'Due at Pickup' : ($isOnline ? 'Paid Online' : 'N/A');
$pdf->SetX($rightX);
$pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Payment Status:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, $payStatus, 0, 1, 'L');

// Courier section
if (!empty($delivery)) {
    $pdf->Ln(3);
    $pdf->SetX($rightX);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell($halfW, 5, 'COURIER / RIDER', 0, 1, 'L');

    $pdf->SetTextColor(30, 30, 30);
    $courierName = safe($delivery['courier_name'] ?? 'N/A');
    $pdf->SetX($rightX);
    $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Name:', 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, $courierName, 0, 1, 'L');

    if (!empty($delivery['rider_phone'])) {
        $pdf->SetX($rightX);
        $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Phone:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, safe($delivery['rider_phone']), 0, 1, 'L');
    }

    if (!empty($delivery['vehicle_type'])) {
        $vehicle = safe($delivery['vehicle_type']);
        if (!empty($delivery['vehicle_plate_number'])) {
            $vehicle .= '  (' . safe($delivery['vehicle_plate_number']) . ')';
        }
        $pdf->SetX($rightX);
        $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Vehicle:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, $vehicle, 0, 1, 'L');
    }

    if (!empty($delivery['is_third_party']) && $delivery['is_third_party'] == 1) {
        $pdf->SetX($rightX);
        $pdf->SetFont('Helvetica', 'B', 8); $pdf->Cell(38, 5, 'Provider:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 8);  $pdf->Cell($halfW - 38, 5, safe($delivery['third_party_name'] ?? 'Third Party'), 0, 1, 'L');
    }

    // Timestamps
    $pdf->Ln(3);
    $pdf->SetX($rightX);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell($halfW, 5, 'TIMESTAMPS', 0, 1, 'L');
    $pdf->SetTextColor(80, 80, 80);

    $timestamps = [
        'Order Placed'  => $order['order_date']          ?? null,
        'Assigned'      => $delivery['assigned_at']      ?? null,
        'Accepted'      => $delivery['accepted_at']      ?? null,
        'Picked Up'     => $delivery['picked_up_at']     ?? null,
        'Delivered'     => $delivery['delivered_at']     ?? null,
    ];
    foreach ($timestamps as $label => $dt) {
        if (empty($dt) || $dt === '0000-00-00 00:00:00') continue;
        $pdf->SetX($rightX);
        $pdf->SetFont('Helvetica', 'B', 7.5); $pdf->Cell(28, 4.5, $label . ':', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 7.5);  $pdf->Cell($halfW - 28, 4.5, fmtDate($dt), 0, 1, 'L');
    }

    // Estimated distance/time if available
    if (!empty($delivery['estimated_distance']) || !empty($delivery['estimated_time'])) {
        $pdf->Ln(1);
        $pdf->SetX($rightX);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(120, 120, 120);
        $meta = [];
        if (!empty($delivery['estimated_distance'])) $meta[] = $delivery['estimated_distance'] . ' km';
        if (!empty($delivery['estimated_time']))     $meta[] = $delivery['estimated_time'] . ' min est.';
        $pdf->Cell($halfW, 4, implode('  |  ', $meta), 0, 1, 'L');
    }
}

$yAfterRight = $pdf->GetY();
$pdf->SetY(max($yAfterLeft, $yAfterRight) + 5);

// Divider
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($pageW, 0.8, '', 0, 1, 'L', true);
$pdf->Ln(4);

// ═══════════════════════════════════════════════════════════════════════════
// ITEMS TABLE
// ═══════════════════════════════════════════════════════════════════════════
$cDesc  = 85;
$cUnit  = 20;
$cQty   = 15;
$cPrice = 30;
$cAmt   = 30;

$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->Cell($cDesc,  7, 'DESCRIPTION',   0, 0, 'L', true);
$pdf->Cell($cUnit,  7, 'UNIT',          0, 0, 'C', true);
$pdf->Cell($cQty,   7, 'QTY',           0, 0, 'C', true);
$pdf->Cell($cPrice, 7, 'UNIT PRICE',    0, 0, 'R', true);
$pdf->Cell($cAmt,   7, 'AMOUNT',        0, 1, 'R', true);

$calcSubtotal = 0;
$pdf->SetTextColor(30, 30, 30);

foreach ($items as $i => $item) {
    $lineTotal     = (float)$item['price'] * (int)$item['quantity'];
    $calcSubtotal += $lineTotal;
    $fill          = ($i % 2 === 0);
    if ($fill) $pdf->SetFillColor(250, 250, 250);

    $variantPart = $item['variant_name'] ? ' - ' . $item['variant_name'] : '';
    $desc        = safe($item['product_name'] . $variantPart);

    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell($cDesc,  6, $desc,                                            0, 0, 'L', $fill);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Cell($cUnit,  6, safe($item['unit_type'] ?? ''),                   0, 0, 'C', $fill);
    $pdf->Cell($cQty,   6, $item['quantity'],                                0, 0, 'C', $fill);
    $pdf->Cell($cPrice, 6, 'PHP ' . number_format((float)$item['price'], 2), 0, 0, 'R', $fill);
    $pdf->Cell($cAmt,   6, 'PHP ' . number_format($lineTotal, 2),           0, 1, 'R', $fill);
}

$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($pageW, 0.4, '', 0, 1, 'L', true);
$pdf->Ln(4);

// ═══════════════════════════════════════════════════════════════════════════
// TOTALS (right-aligned block)
// ═══════════════════════════════════════════════════════════════════════════
$deliveryFee    = (float)($order['delivery_fee']    ?? 0);
$discountAmount = (float)($order['discount_amount'] ?? 0);
$total          = (float)$order['total_price'];
$voucherCode    = $order['voucher_code'] ?? '';

$totalsX = 15 + 95;
$lW      = 50;
$vW      = $pageW - 95 - $lW; // 35

$pdf->SetXY($totalsX, $pdf->GetY());
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
$pdf->Cell($lW + $vW, 0.5, '', 0, 1, 'L', true);

$pdf->SetX($totalsX);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell($lW, 8, 'TOTAL AMOUNT', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($vW, 8, 'PHP ' . number_format($total, 2), 0, 1, 'R');
$pdf->SetTextColor(30, 30, 30);

$pdf->Ln(6);

// ── COD/COP payment note ──────────────────────────────────────────────────
if ($isCOP) {
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell($pageW, 5, 'PAYMENT COLLECTION', 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell($pageW, 4.5,
        'Amount to collect from customer: PHP ' . number_format($total, 2) .
        '. Please ensure exact amount or provide change. Obtain customer signature below upon collection.', 0, 'L');
    $pdf->Ln(3);
}

// ═══════════════════════════════════════════════════════════════════════════
// CONDITION & SIGNATURE BLOCK
// ═══════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Helvetica', 'I', 7.5);
$pdf->SetTextColor(80, 80, 80);
$pdf->MultiCell($pageW, 4.5,
    'I hereby confirm that I have received the items listed above in good condition and complete quantity, ' .
    'unless otherwise noted below. By signing this receipt, I acknowledge acceptance of the delivered goods.',
    0, 'L', true);
$pdf->Ln(4);

// Condition notes line
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell(40, 5, 'Condition Remarks:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell($pageW - 40, 5, '', 'B', 1, 'L');
$pdf->Ln(1);
$pdf->Cell($pageW, 5, '', 'B', 1, 'L'); // second line for remarks
$pdf->Ln(6);

// Divider
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($pageW, 0.4, '', 0, 1, 'L', true);
$pdf->Ln(5);

// ── THREE-COLUMN SIGNATURE BLOCK ─────────────────────────────────────────
// Customer | Rider | Authorized Staff
$sigW  = ($pageW - 20) / 3; // gap of 10 between each
$sigX1 = 15;
$sigX2 = 15 + $sigW + 10;
$sigX3 = 15 + ($sigW + 10) * 2;
$sigY  = $pdf->GetY();

foreach ([$sigX1, $sigX2, $sigX3] as $sx) {
    $pdf->SetXY($sx, $sigY);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell($sigW, 10, '', 0, 1, 'C'); // space for signature
    $pdf->SetX($sx);
    $pdf->Cell($sigW, 5, '', 'T', 1, 'C'); // signature line
}

// Labels below lines
$pdf->SetY($sigY + 16);
$pdf->SetFont('Helvetica', 'B', 7.5);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetX($sigX1); $pdf->Cell($sigW, 4, 'Customer Signature', 0, 0, 'C');
$pdf->SetX($sigX2); $pdf->Cell($sigW, 4, 'Rider / Courier', 0, 0, 'C');
$pdf->SetX($sigX3); $pdf->Cell($sigW, 4, 'Authorized by', 0, 1, 'C');

$pdf->SetY($pdf->GetY() + 1);
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetX($sigX1); $pdf->Cell($sigW, 4, 'Print Name & Date', 0, 0, 'C');
$pdf->SetX($sigX2); $pdf->Cell($sigW, 4, safe($delivery['courier_name'] ?? ''), 0, 0, 'C');
$pdf->SetX($sigX3); $pdf->Cell($sigW, 4, 'St. Joseph Fish Brokerage Inc.', 0, 1, 'C');

// ── Output ────────────────────────────────────────────────────────────────
$pdf->Output('D', 'DeliveryReceipt_' . $orderCode . '.pdf');
exit;