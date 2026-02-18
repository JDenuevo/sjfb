<?php
session_start();
require_once __DIR__ . '/conn.php';

// Load Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Include PayMongo helper
require_once __DIR__ . '/functions/paymongo_helper.php';

// Get order by CODE instead of ID
$orderCode = $_GET['order_code'] ?? $_SESSION['order_code'] ?? null;
$status = $_GET['status'] ?? null;
$sessionId = $_GET['session_id'] ?? null;

// If no order code, redirect to home
if (!$orderCode) {
    $_SESSION['error'] = "Invalid access to order page.";
    header("Location: index.php");
    exit();
}

try {
    // Get order details by ORDER CODE instead of ID
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        header("Location: index.php");
        exit();
    }
    
    $orderId = $order['order_id']; // Get the internal ID for database operations
    
    // Get latest payment status for this order (using internal order_id)
    $paymentStmt = $conn->prepare("SELECT payment_id, payment_status FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $paymentStmt->bind_param("i", $orderId);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result()->fetch_assoc();
    $paymentStatus = $paymentResult['payment_status'] ?? null;
    $paymentId = $paymentResult['payment_id'] ?? null;
    
    // SIMPLE PAYMENT STATUS UPDATE
    if ($status && $paymentId && in_array($order['payment_method'], ['gcash', 'paymaya', 'grab_pay', 'card', 'qrph'])) {
        $newPaymentStatus = ($status === 'success') ? 'Paid' : 'Failed';
        
        // Update payment status directly (using internal payment_id)
        $updateStmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
        $updateStmt->bind_param("si", $newPaymentStatus, $paymentId);
        
        if ($updateStmt->execute()) {
            // Update payment status for display
            $paymentStatus = $newPaymentStatus;
            
            // Set appropriate message
            if ($status === 'success') {
                $_SESSION['success'] = "Payment successful! Your order has been confirmed.";
                // Clear cart and session data
                if (isset($_SESSION['cart'])) unset($_SESSION['cart']);
                unset($_SESSION['current_order_id']);
                unset($_SESSION['current_order_code']);
                unset($_SESSION['pending_payment_order']);
            } else {
                $_SESSION['error'] = "Payment was cancelled or failed. Please try again.";
            }
        } else {
            error_log("Simple payment status update error: " . $updateStmt->error);
        }
    }
    
    // For COD orders, create a payment record with Pending status if it doesn't exist
    if ($order['payment_method'] === 'cod') {
        $codCheck = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
        $codCheck->bind_param("i", $orderId);
        $codCheck->execute();
        
        if ($codCheck->get_result()->num_rows === 0) {
            $codStmt = $conn->prepare("
                INSERT INTO payments (
                    order_id, currency, gross_amount, payment_status, 
                    mode, billing_name, billing_email, billing_phone,
                    billing_line1, billing_city, billing_postal_code, billing_country
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $billingName = $order['first_name'] . ' ' . $order['last_name'];
            $currency = 'PHP';
            $paymentStatusCod = 'Pending';
            $mode = 'test';
            $billingCountry = 'PH';
            
            $codStmt->bind_param(
                "isdsssssssss", 
                $orderId, // Still use internal order_id for database relationships
                $currency,
                $order['total_price'],
                $paymentStatusCod,
                $mode,
                $billingName,
                $order['email'],
                $order['phone_number'],
                $order['address'],
                $order['city'],
                $order['postal_code'],
                $billingCountry
            );
            
            if (!$codStmt->execute()) {
                error_log("COD payment insert error: " . $codStmt->error);
            }
            
            // Update payment status for display
            $paymentStatus = $paymentStatusCod;
        }
    }
    
    // Get order items for display (using internal order_id)
    $itemsStmt = $conn->prepare("
        SELECT oi.*, p.product_name as product_name, v.variant_price, v.variant_name as variant_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format order date
    $orderDate = date('F j, Y \a\t g:i A', strtotime($order['order_date']));
    
} catch (Exception $e) {
    error_log("Order receipt page error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while processing your order.";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order | St. Joseph Fish Brokerage Inc.</title>

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>
<body>
<?php include('./components/preloader.php'); ?>

<section id="order-success-section" class="flex-grow">
  <?php include('./components/navigation.php'); ?>

  <!-- Receipt -->
  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto my-4 sm:my-10">
    
    <div class="text-center mb-12" data-aos="fade-up">
      <?php if ($paymentStatus === 'Paid'): ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
          <!-- Big check -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Success</h1>
        <p class="text-gray-600 mb-6">Your payment was received. Our team will review your order before processing. Thank you!</p>

      <?php elseif ($paymentStatus === 'Pending'): ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
          <!-- Big check -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Success</h1>
        <p class="text-gray-600 mb-6">Your order was received. Our team will review your order before processing. Thank you!</p>

      <?php elseif ($paymentStatus === 'Failed'): ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
          <!-- Big X -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Failed</h1>
        <p class="text-gray-600 mb-6">It seems like your payment has failed or cancelled during the process. Please try again.</p>
 
      <?php else: ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
          <!-- Question mark for unknown -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 6h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Payment Status Unknown</h1>
        <p class="text-gray-600 mb-6">We couldn't retrieve your payment status. Please contact support.</p>
      <?php endif; ?>
    </div>

    <div class="sm:w-11/12 lg:w-3/4 mx-auto">
      <!-- Card -->
      <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl" id="orderReceipt">
        <!-- Grid -->
        <div class="flex justify-between">
          <div>
            <img src="./assets/icons/logo.svg" class="w-24 h-24 hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">
            <h1 class="mt-2 md:text-lg font-semibold text-orange-600 ">St. Joseph Fish Brokerage Inc.</h1>
          </div>

          <div class="text-end">
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Order #</h2>
            <!-- Display ORDER CODE instead of order_id -->
            <span class="mt-1 block text-gray-500 text-lg"><?= htmlspecialchars($order['order_code']) ?></span>

            <address class="mt-4 not-italic text-gray-800 ">
              Bulungan Avenue corner HACCP St.<br>
              NFPC NBBS, Navotas, Philippines<br>
              Boulevard South Proper, Navotas, Philippines<br>
            </address>
          </div>
        </div>

        <!-- Grid -->
        <div class="my-8 grid sm:grid-cols-2 gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 ">Shipping Information:</h3>
            <h3 class="text-lg font-semibold text-gray-500 "><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></h3>
            <h3 class="mt-2 text-lg font-semibold text-gray-800 ">Address:</h3>
            <address class="not-italic text-gray-500 ">
              <?= htmlspecialchars($order['address']) ?><br>
              <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['postal_code']) ?>
            </address>
          </div>
           
          <div class="sm:text-end space-y-2">
            <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
              <dl class="grid sm:grid-cols-5 gap-x-3">
                <dt class="col-span-3 font-semibold text-gray-800 ">Payment Method:</dt>
                <dd class="col-span-2 text-gray-500 ">
                  <?php
                    $method = strtolower($order['payment_method']);
                    switch ($method) {
                      case 'gcash':
                        $methodLabel = 'GCash';
                        $methodClass = 'bg-blue-100 text-blue-800';
                        break;
                      case 'paymaya':
                        $methodLabel = 'PayMaya';
                        $methodClass = 'bg-green-100 text-green-800';
                        break;
                      case 'grab_pay':
                        $methodLabel = 'GrabPay';
                        $methodClass = 'bg-green-100 text-green-800';
                        break;
                      case 'card':
                        $methodLabel = 'Credit/Debit Card';
                        $methodClass = 'bg-purple-100 text-purple-800';
                        break;
                      case 'qrph':
                        $methodLabel = 'QR Philippines';
                        $methodClass = 'bg-indigo-100 text-indigo-800';
                        break;
                      case 'cod':
                        $methodLabel = 'Cash on Delivery';
                        $methodClass = 'bg-orange-100 text-orange-800';
                        break;
                      default:
                        $methodLabel = ucfirst($method);
                        $methodClass = 'bg-gray-100 text-gray-800';
                    }
                  ?>
                  <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $methodClass; ?>">
                    <?php echo $methodLabel; ?>
                  </span>
                </dd>
              </dl>
              <dl class="grid sm:grid-cols-5 gap-x-3">
                <dt class="col-span-3 font-semibold text-gray-800 ">Order date:</dt>
                <dd class="col-span-2 text-gray-500 "><?= $orderDate ?></dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="mt-6">
          <div class="border border-gray-200 p-4 rounded-lg space-y-4 ">
            
            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 items-center">
              <div class="col-span-full sm:col-span-2">
                <h5 class="text-start text-xs font-medium text-black uppercase">Item Name</h5>
              </div>
              <div>
                <h5 class="text-start text-xs font-medium text-black uppercase ">Variant</h5>
              </div>
              <div>
                <h5 class="text-start text-xs font-medium text-black uppercase ">Price</h5>
              </div>
              <div>
                <h5 class="text-start text-xs font-medium text-black uppercase ">Qty</h5>
              </div>
              <div>
                <h5 class="text-start text-xs font-medium text-black uppercase ">Amount</h5>
              </div>
            </div>

            <hr>

            <?php foreach ($items as $item): ?>
              <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                <div class="col-span-full sm:col-span-2">
                  <p class="font-medium text-gray-800 "><?= htmlspecialchars($item['product_name']) ?></p>
                </div>
                <div>
                  <p class="text-gray-800 "><?= htmlspecialchars($item['variant_name']) ?></p>
                </div>
                <div>
                  <p class="text-gray-800 ">₱<?= number_format($item['variant_price'], 2) ?></p>
                </div>
                <div>
                  <p class="text-gray-800 "><?= htmlspecialchars($item['quantity']) ?></p>
                </div>
                <div>
                  <p class="sm:text-end text-gray-800 ">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                </div>
              </div>
            <?php endforeach; ?>

          </div>
        </div>

        <!-- Subtotal Section -->
        <div class="mt-8 p-4">
          <div class="grid grid-cols-4 gap-2">
            <dt class="text-lg font-semibold text-gray-800">Total Amount:</dt>
            <div></div>
            <div></div>
            <dd class="text-lg font-semibold text-gray-800">₱<?= number_format($order['total_price'], 2) ?></dd>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md p-6 my-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">What happens next?</h3>
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">1</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Confirmation</h4>
            <p class="text-sm text-gray-600">We'll call or text you within 24 hours to confirm your order details.</p>
          </div>
          
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">2</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Order Processing</h4>
            <p class="text-sm text-gray-600">Once confirmed, we'll prepare your order for delivery.</p>
          </div>
          
          <div class="text-center">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <span class="text-xl font-bold text-orange-600">3</span>
            </div>
            <h4 class="font-medium text-gray-800 mb-2">Delivery</h4>
              <p class="text-sm text-gray-600">
                <?php if ($order['payment_method'] === 'cod'): ?>
                  We'll deliver and collect payment in cash at your doorstep.
                <?php else: ?>
                  Sit back and relax. We'll deliver your order to your address.
                <?php endif; ?> 
              </p>         
            </div>
        </div>
        <div class="mt-4 sm:mt-8 p-4">
          <h4 class="text-lg font-semibold text-gray-800 ">Thank you!</h4>
          <p class="text-gray-500 ">If you have any questions concerning this receipt, use the following contact information:</p>
          <div class="mt-2">
            <p class="block text-sm font-medium text-gray-800 ">fisbrokers.net</p>
            <p class="block text-sm font-medium text-gray-800 ">(+63) 946-497-3689</p>
          </div>
        </div>
      </div>

      <!-- Buttons -->
      <div class="mt-6 flex justify-end gap-x-3"> 
        <a id="downloadBtn" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" href="javascript:void(0);">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>          
          Download Receipt
        </a>
        <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg text-white bg-orange-600 hover:bg-orange-700 shadow-2xs disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50" href="index.php" >
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>  
          Continue Shopping
        </a>
      </div>
    </div>
  </div>

  <?php include('./components/footer.php'); ?>
</section>

<script>
  document.getElementById('downloadBtn').addEventListener('click', function () {
    const receipt = document.getElementById('orderReceipt');
    html2canvas(receipt, { scale: 2 }).then(canvas => {
      const link = document.createElement('a');
      link.href = canvas.toDataURL('image/png');
      // Use order_code for filename instead of order_id
      link.download = 'order-receipt-<?= $order['order_code'] ?>.png';
      link.click();
    });
  });
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>
  AOS.init();
</script>
<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>

</body>
</html>