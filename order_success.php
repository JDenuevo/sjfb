<?php
session_start();
include 'conn.php';

// Check if there's an order_id in session
if (!isset($_SESSION['order_id'])) {
    header("Location: index.php");
    exit();
}

// Get order details
$orderId = $_SESSION['order_id'];
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
  <title>Order Confirmation | St. Joseph Fish Brokerage Inc.</title>

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

  <!-- Receipt -->
  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto my-4 sm:my-10 mt-10">
    
    <div class="text-center mb-12" data-aos="fade-up">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5" />
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
      <p class="text-gray-600 mb-6">Thank you for shopping with us. Your order has been confirmed.</p>
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
                      case 'ewallet':
                        $methodLabel = 'G-Cash';
                        $methodClass = 'bg-purple-100 text-purple-800';
                        break;
                      case 'cod':
                        $methodLabel = 'Cash on Delivery';
                        $methodClass = 'bg-orange-100 text-orange-800';
                        break;
                      case 'bank':
                        $methodLabel = 'Bank Transfer';
                        $methodClass = 'bg-blue-100 text-blue-800';
                        break;
                      default:
                        $methodLabel = ucfirst($method);
                        $methodClass = 'bg-gray-100 text-gray-800';
                    }
                  ?>
                  <p class="<?php echo $methodClass; ?>">
                    <?php echo $methodLabel; ?>
                  </p>
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
                  <p class="text-gray-800 ">₱<?= htmlspecialchars($item['variant_price']) ?></p>
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
           
            <dt class="text-lg font-semibold text-gray-800">Subtotal:</dt>
      
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
        <a id="downloadBtn" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" href="javascript:void(0);">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
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

  <!-- <a href="track.php" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
    Track Your Order
  </a> -->
 
  <?php include('./components/footer.php'); ?>
</section>

<script>
  document.getElementById('downloadBtn').addEventListener('click', function () {
    const receipt = document.getElementById('orderReceipt');
    html2canvas(receipt, { scale: 2 }).then(canvas => {
      const link = document.createElement('a');
      link.href = canvas.toDataURL('image/png');
      link.download = 'receipt.png';
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