<?php
session_start();
require_once 'conn.php';
require_once './functions/paymongo_checker.php';

$paymentSuccess = false;
$orderId = null;

// Check if we have an order ID from URL or session
$orderId = $_GET['order_id'] ?? $_SESSION['order_id'] ?? null;

if ($orderId) {
    // Check the order status directly from database
    $stmt = $conn->prepare("SELECT order_status, payment_method FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderCheck = $stmt->get_result()->fetch_assoc();
    
    if ($orderCheck) {
        // If order is already marked as paid, show success
        if ($orderCheck['order_status'] === 'paid') {
            $paymentSuccess = true;
            // Clear cart only if payment is confirmed
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            // Clear payment session variables
            unset($_SESSION['current_order_id']);
            unset($_SESSION['pending_payment_order']);
            $_SESSION['success'] = "Payment successful! Your order has been confirmed.";
        } 
        // For COD orders
        elseif ($orderCheck['payment_method'] === 'cod') {
            $paymentSuccess = true;
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            $_SESSION['success'] = "Order placed successfully! Your COD order has been confirmed.";
        }
        // For pending online payments, check if they might have been completed via webhook
        elseif ($orderCheck['order_status'] === 'Pending') {
            // Show "processing" message - the webhook will update this later
            $_SESSION['info'] = "Your order #$orderId is being processed. Please check your email for confirmation.";
            
            // For local testing, allow manual verification
            if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                $_SESSION['info'] .= " Since you're on localhost, you may need to verify payment manually.";
            }
        }
    }
}

// Handle session_id parameter if it exists (for backward compatibility)
if (isset($_GET['session_id']) && $_GET['session_id'] !== '{CHECKOUT_SESSION_ID}') {
    // Valid session ID - proceed with verification
    $verification = verifyPayMongoPayment($_GET['session_id'], $_GET['order_id'] ?? null);
    
    if ($verification['success']) {
        $paymentSuccess = true;
        $orderId = $verification['order_id'] ?? $orderId;
        
        if ($orderId) {
            updateOrderPaymentStatus($conn, $orderId, $verification['payment_method']);
            $_SESSION['order_id'] = $orderId;
            clearCartOnSuccess();
            $_SESSION['success'] = "Payment successful! Your order has been confirmed.";
        }
    }
}

// If no order ID found, redirect to home
if (!$orderId) {
    $_SESSION['error'] = "No order found.";
    header("Location: index.php");
    exit();
}

// Get order details for display
$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.total_price, o.order_status, 
           o.payment_method, o.first_name, o.last_name, o.email,
           o.phone_number, o.address, o.postal_code, o.city
    FROM orders o
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header("Location: index.php");
    exit();
}

// For pending online payments, check if payment was completed via webhook while page was loading
if ($order['order_status'] === 'Pending' && $order['payment_method'] !== 'cod') {
    // Double-check payment status
    $stmt = $conn->prepare("SELECT COUNT(*) as payment_count FROM payments WHERE order_id = ? AND payment_status = 'succeeded'");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $paymentResult = $stmt->get_result()->fetch_assoc();
    
    if ($paymentResult['payment_count'] > 0) {
        // Update order status
        $updateStmt = $conn->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?");
        $updateStmt->bind_param("i", $orderId);
        $updateStmt->execute();
        
        $paymentSuccess = true;
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        $_SESSION['success'] = "Payment verified! Your order is now confirmed.";
        $order['order_status'] = 'paid'; // Update for display
    }
}

// Get order items
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
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link href="./style.css" rel="stylesheet">
  <link href="./output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
<?php include('./components/preloader.php'); ?>

<section id="order-success-section" class="flex-grow">
  <?php include('./components/navigation.php'); ?>

  <!-- Display success/error messages -->
  <?php if (isset($_SESSION['success'])): ?>
    <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-4">
      <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <?php if ($paymentSuccess): ?>
          <p class="mt-2 text-sm">Your cart has been cleared.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-4">
      <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <?php if (!$paymentSuccess): ?>
          <p class="mt-2 text-sm">Your cart items have been preserved for retry.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['info'])): ?>
    <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-4">
      <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4">
        <?= htmlspecialchars($_SESSION['info']) ?>
      </div>
    </div>
    <?php unset($_SESSION['info']); ?>
  <?php endif; ?>

  <!-- Receipt -->
  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto my-4 sm:my-10 mt-10">
    
    <div class="text-center mb-12" data-aos="fade-up">
      <?php if ($paymentSuccess): ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
        <p class="text-gray-600 mb-6">Thank you for shopping with us. Your order and payment has been confirmed.</p>
      <?php else: ?>
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100 mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-yellow-600" viewBox="0 极 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Processing</h1>
        <p class="text-gray-600 mb-6">Your order has been received but payment is still processing.</p>
        
        <!-- Manual verification for local testing -->
        <?php if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false): ?>
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
          <p class="text-blue-800 mb-2">Local Testing: If you've completed payment, verify manually:</p>
          <form method="POST" action="./functions/verify_payment.php" class="flex gap-2">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
              Verify Payment
            </button>
          </form>
        </div>
        <?php endif; ?>
      <?php endif; ?>
      
      <!-- Order Status Badge -->
      <?php
        $statusClass = '';
        $statusText = '';
        switch (strtolower($order['order_status'])) {
          case 'paid':
            $statusClass = 'bg-green-100 text-green-800';
            $statusText = 'Payment Confirmed';
            break;
          case 'pending':
            $statusClass = 'bg-yellow-100 text-yellow-800';
            $statusText = 'Payment Pending';
            break;
          default:
            $statusClass = 'bg-blue-100 text-blue-800';
            $statusText = 'Order Placed';
        }
      ?>
      <span class="inline-block px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
        <?= $statusText ?>
      </span>
    </div>

    <!-- Rest of your receipt HTML remains exactly the same -->
    <div class="sm:w-11/12 lg:w-3/4 mx-auto">
      <!-- Card -->
      <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl" id="orderReceipt">
        <!-- Grid -->
        <div class="flex justify-between">
          <div>
            <img src="./assets/icons/logo.svg" class="w-24 h-24 hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">

            <h1 class="mt-2 md:text-lg font-semibold text-orange-600 ">St. Joseph Fish Brokerage Inc.</h1>
          </div>
          <!-- Col -->

          <div class="text-end">
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Order #</h2>
            <span class="mt-1 block text-gray-500 text-lg"><?= htmlspecialchars($order['order_id']) ?></span>

            <address class="mt-4 not-italic text-gray-800 ">
              Bulungan Avenue corner HACCP St.<br>
              NFPC NBBS, Navotas, Philippines<br>
              Boulevard South Proper, Navotas, 
              Philippines<br>
            </address>
          </div>
          <!-- Col -->
        </div>
        <!-- End Grid -->

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
          <!-- Col -->
           
          <div class="sm:text-end space-y-2">
            <!-- Grid -->
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
            <!-- End Grid -->
          </div>
          <!-- Col -->
        </div>
        <!-- End Grid -->

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
        <!-- End Table -->

        <!-- Subtotal Section -->
        <div class="mt-8 p-4">
          <div class="grid grid-cols-4 gap-2">
            <!-- Empty columns to push subtotal to the right -->
           
            <dt class="text-lg font-semibold text-gray-800">Total Amount:</dt>
      
            <div></div>
            <div></div>
            
            <dd class="text-lg font-semibold text-gray-800">₱<?= number_format($order['total_price'], 2) ?></dd>
          
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
      <!-- End Card -->

      <!-- Buttons -->
      <div class="mt-6 flex justify-end gap-x-3"> 
        <?php if (!$paymentSuccess && $order['payment_method'] !== 'cod'): ?>
          <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 shadow-2xs disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50" href="checkout.php" >
            Retry Payment
          </a>
        <?php endif; ?>
        <a id="downloadBtn" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" href="javascript:void(0);">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 极 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
          Download Receipt
        </a>
        <a class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg text-white bg-orange-600 hover:bg-orange-700 shadow-2xs disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50" href="index.php" >
          Continue Shopping
        </a>
        
       
      </div>
      <!-- End Buttons -->
    </div>
  </div>
  <!-- End Receipt -->

  <?php include('./components/footer.php'); ?>
</section>

<script>
  document.getElementById('downloadBtn').addEventListener('click', function () {
    const receipt = document.getElementById('orderReceipt');
    html2canvas(receipt, { scale: 2 }).then(canvas => {
      const link = document.createElement('a');
      link.href = canvas.toDataURL('image/png');
      link.download = 'order-receipt-<?= $order['order_id'] ?>.png';
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