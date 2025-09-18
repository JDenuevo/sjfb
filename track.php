<?php
session_start();
include 'conn.php';

// Set the time zone
date_default_timezone_set('Asia/Manila');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderCode = trim($_POST['order_code']);

    // Basic validation
    if (empty($orderCode)) {
        $_SESSION['error'] = "Order Code is required";
        header("Location: track.php");
        exit();
    }

    // Query the database - FIXED: Changed to search by order_code instead of order_id
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_code, o.order_date, o.total_price, o.order_status, 
           o.payment_method, o.first_name, o.last_name, o.email,
           o.phone_number, o.address, o.postal_code, o.city
        FROM orders o
        WHERE o.order_code = ? 
    ");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No order found with that Order Code";
        header("Location: track.php");
        exit();
    }

    $order = $result->fetch_assoc();
    $orderId = $order['order_id'];
    
    // Get order items
    $itemsStmt = $conn->prepare("
    SELECT oi.*, p.product_name as product_name, v.variant_price, v.variant_name as variant_name
      FROM order_items oi
      LEFT JOIN products p ON oi.product_id = p.product_id
      LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
      WHERE oi.order_id = ?");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $_SESSION['tracked_order'] = $order;
    $_SESSION['tracked_order_items'] = $items;
    header("Location: track.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<!-- End Google Tag Manager -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale-1.0">
  <title>Track Order | St. Joseph Fish Brokerage Inc.</title>

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
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<body>
<?php include('./components/preloader.php'); ?>

<?php include('./components/navigation.php'); ?>

<!-- Track Order Section -->
<section id="checkout-section" class="py-12 bg-gray-50">
  <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="p-6 sm:p-8">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-gray-900">Track Your Order</h2>
          <p class="mt-2 text-sm text-gray-600">Enter your order code to view your order status</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6 mt-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="order_code" class="block text-sm font-medium text-gray-700">Order Code</label>
              <div class="mt-1">
                <input type="text" id="order_code" name="order_code" required
                  class="py-3 px-4 block w-full border border-black rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500"
                  placeholder="Enter your order code">
              </div>
            </div>
          </div>

          <div>
            <button type="submit" name="track_order" class="w-full my-10 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none disabled:opacity-50 disabled:pointer-events-none">
              Track Order
            </button>
          </div>
        </form>

      </div>
    </div>

    <?php if (isset($_SESSION['tracked_order'])): ?>
      <?php
        $orderStatus = $_SESSION['tracked_order']['order_status'] ?? '';
        
        // Define the order status flow - INCLUDING PENDING
        $statusFlow = ['Pending', 'Processing', 'OutForDelivery', 'Delivered', 'Cancelled'];
        
        // Map status to display names
        $statusDisplay = [
          'Pending' => 'Pending',
          'Processing' => 'Processing',
          'OutForDelivery' => 'Out for Delivery', 
          'Delivered' => 'Delivered',
          'Cancelled' => 'Cancelled'
        ];
        
        // Get current status index
        $currentStatusIndex = array_search($orderStatus, $statusFlow);
        if ($currentStatusIndex === false) {
          $currentStatusIndex = 0; // Default to first status if not found
        }
        
        // Calculate progress percentage
        $progressPercentage = ($currentStatusIndex + 1) / count($statusFlow) * 100;
        
        // Define status icons
        $statusIcons = [
          'Pending' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
          'Processing' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 9 9"/><path d="M14.5 9h-5L7 12l2.5 3h5L17 12l-2.5-3z"/></svg>',
          'OutForDelivery' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M5 17h14v-3.5a1.5 1.5 0 0 0-1.5-1.5h-11A1.5 1.5 0 0 0 5 13.5V17z"/><path d="M5 9V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2"/></svg>',
          'Delivered' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
          'Cancelled' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>'
        ];
        
        // Define status colors
        $statusColors = [
          'Pending' => ['bg-gray-100', 'text-gray-800', 'border-gray-500'],
          'Processing' => ['bg-blue-100', 'text-blue-800', 'border-blue-500'],
          'OutForDelivery' => ['bg-orange-100', 'text-orange-800', 'border-orange-500'],
          'Delivered' => ['bg-green-100', 'text-green-800', 'border-green-500'],
          'Cancelled' => ['bg-red-100', 'text-red-800', 'border-red-500']
        ];
      ?>

      <div class="sm:w-11/12 lg:w-3/4 mx-auto my-4">
        <!-- Order Status Progress -->
        <div class="max-w-4xl mx-auto mt-10 bg-white p-6 rounded-lg shadow">
          <h3 class="text-xl font-semibold text-gray-800 mb-6 text-center">Order Status</h3>
          
          <!-- Progress Bar -->
          <div class="mb-8">
            <div class="inline-block mb-2 ms-[calc(50%-20px)] py-0.5 px-1.5 bg-blue-50 border border-blue-200 text-xs font-medium text-blue-600 rounded-lg">
              <?= round($progressPercentage) ?>%
            </div>
            <div class="flex w-full h-2 bg-gray-200 rounded-full overflow-hidden" role="progressbar" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="flex flex-col justify-center rounded-full overflow-hidden bg-blue-600 text-xs text-white text-center whitespace-nowrap transition duration-500" style="width: <?= $progressPercentage ?>%"></div>
            </div>
          </div>
          
          <!-- Status Steps -->
          <div class="flex justify-between relative">
            <?php foreach ($statusFlow as $index => $status): ?>
              <?php
                $isCompleted = $index < $currentStatusIndex;
                $isCurrent = $index === $currentStatusIndex;
                $isCancelled = $orderStatus === 'Cancelled';
                
                // Determine styling based on status
                if ($isCancelled && $status === 'Cancelled') {
                  $circleClass = "bg-red-500 text-white border-red-500";
                  $textClass = "text-red-600 font-semibold";
                } elseif ($isCompleted) {
                  $circleClass = "bg-green-500 text-white border-green-500";
                  $textClass = "text-green-600";
                } elseif ($isCurrent) {
                  $circleClass = (isset($statusColors[$status][0]) ? $statusColors[$status][0] : 'bg-gray-100') . " " . 
                                (isset($statusColors[$status][1]) ? $statusColors[$status][1] : 'text-gray-800') . " border-2 " . 
                                (isset($statusColors[$status][2]) ? $statusColors[$status][2] : 'border-gray-500');
                  $textClass = "text-blue-600 font-semibold";
                } else {
                  $circleClass = "bg-gray-100 text-gray-400 border-gray-300";
                  $textClass = "text-gray-500";
                }
              ?>
              
              <div class="flex flex-col items-center relative z-10" style="width: <?= 100/count($statusFlow) ?>%">
                <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center mb-2 <?= $circleClass ?>">
                  <?= isset($statusIcons[$status]) ? $statusIcons[$status] : $statusIcons['Pending'] ?>
                </div>
                <span class="text-sm font-medium <?= $textClass ?> text-center"><?= isset($statusDisplay[$status]) ? $statusDisplay[$status] : $status ?></span>
              </div>
              
              <!-- Connector line (except for last item) -->
              <?php if ($index < count($statusFlow) - 1): ?>
                <div class="absolute top-5 left-<?= ($index + 1) * (100 / count($statusFlow)) - (100 / (count($statusFlow) * 2)) ?>% w-<?= 100 / count($statusFlow) - 10 ?>% h-0.5 bg-gray-300 z-0"></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          
          <!-- Current Status Display -->
          <div class="mt-8 text-center">
            <p class="text-lg font-medium text-gray-700">Current Status:</p>
            <p class="text-xl font-bold <?= isset($statusColors[$orderStatus][1]) ? $statusColors[$orderStatus][1] : 'text-gray-800' ?>">
              <?= isset($statusDisplay[$orderStatus]) ? $statusDisplay[$orderStatus] : $orderStatus ?>
            </p>
            <?php if ($orderStatus === 'Delivered'): ?>
              <p class="text-sm text-gray-600 mt-2">Your order has been successfully delivered.</p>
            <?php elseif ($orderStatus === 'Cancelled'): ?>
              <p class="text-sm text-gray-600 mt-2">This order has been cancelled.</p>
            <?php elseif ($orderStatus === 'Pending'): ?>
              <p class="text-sm text-gray-600 mt-2">Your order is pending confirmation.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Order Details Card -->
        <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl mt-8" id="orderReceipt">
          <!-- Grid -->
          <div class="flex justify-between">
            <div>
              <img src="./assets/icons/logo.svg" class="w-24 h-24 hover:scale-110 duration-200" alt="St. Joseph Fish Brokerage Inc. Logo">
              <h1 class="mt-2 md:text-lg font-semibold text-orange-600 ">St. Joseph Fish Brokerage Inc.</h1>
            </div>
            <!-- Col -->

            <div class="text-end">
              <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 ">Order #</h2>
              <span class="mt-1 block text-gray-500 text-lg"><?= htmlspecialchars($_SESSION['tracked_order']['order_code']) ?></span>

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
              <h3 class="text-lg font-semibold text-gray-500 "><?= htmlspecialchars($_SESSION['tracked_order']['first_name'] . ' ' . $_SESSION['tracked_order']['last_name']) ?></h3>
              <h3 class="mt-2 text-lg font-semibold text-gray-800 ">Address:</h3>
              <address class="not-italic text-gray-500 ">
                <?= htmlspecialchars($_SESSION['tracked_order']['address']) ?><br>
                <?= htmlspecialchars($_SESSION['tracked_order']['city']) ?>,<?= htmlspecialchars($_SESSION['tracked_order']['postal_code']) ?>
              </address>
            
            </div>
            <!-- Col -->
            
            <div class="sm:text-end space-y-2">
              <!-- Grid -->
              <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                <dl class="grid sm:grid-cols-5 gap-x-3">
                  <dt class="col-span-3 font-semibold text-gray-800 ">Payment Method:</dt>
                  <dd class="col-span-2 text-gray-500">
                    <?php
                      $method = strtolower($_SESSION['tracked_order']['payment_method'] ?? '');
                      switch ($method) {
                        case 'ewallet':
                          $methodLabel = 'G-Cash';
                          $methodClass = 'inline-block px-2 py-1 rounded bg-purple-100 text-purple-800 text-sm font-medium';
                          break;
                        case 'cod':
                          $methodLabel = 'Cash on Delivery';
                          $methodClass = 'inline-block px-2 py-1 rounded bg-orange-100 text-orange-800 text-sm font-medium';
                          break;
                        case 'bank':
                          $methodLabel = 'Bank Transfer';
                          $methodClass = 'inline-block px-2 py-1 rounded bg-blue-100 text-blue-800 text-sm font-medium';
                          break;
                        default:
                          $methodLabel = ucfirst(htmlspecialchars($method));
                          $methodClass = 'inline-block px-2 py-1 rounded bg-gray-100 text-gray-800 text-sm font-medium';
                      }
                    ?>
                    <p class="<?= $methodClass ?>">
                      <?= $methodLabel ?>
                    </p>
                  </dd>
                </dl>
                <dl class="grid sm:grid-cols-5 gap-x-3">
                  <dt class="col-span-3 font-semibold text-gray-800 ">Date Ordered:</dt>
                  <dd class="col-span-2 text-gray-500 "><?= date('F j, Y \a\t g:i A', strtotime($_SESSION['tracked_order']['order_date'])) ?></dd>
                </dl>
                <dl class="grid sm:grid-cols-5 gap-x-3">
                  <dt class="col-span-3 font-semibold text-gray-800 ">Order Status:</dt>
                  <dd class="col-span-2">
                    <?php
                    $statusClass = 'bg-gray-100 text-gray-800';
                    if (isset($statusColors[$orderStatus])) {
                      $statusClass = $statusColors[$orderStatus][0] . ' ' . $statusColors[$orderStatus][1];
                    }
                    ?>
                    <span class="inline-block px-2 py-1 rounded <?= $statusClass ?> text-sm font-medium">
                      <?= isset($statusDisplay[$orderStatus]) ? $statusDisplay[$orderStatus] : $orderStatus ?>
                    </span>
                  </dd>
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

              <?php foreach ($_SESSION['tracked_order_items'] as $item): ?>
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                  <div class="col-span-full sm:col-span-2">
                    <p class="font-medium text-gray-800 "><?= htmlspecialchars($item['product_name']) ?></p>
                  </div>
                  <div>
                    <p class="text-gray-800 "><?= htmlspecialchars($item['variant_name']) ?></p>
                  </div>
                  <div>
                    <p class="text-gray-800 ">₱<?= number_format($item['variant_price']) ?></p>
                  </div>
                  <div>
                    <p class="text-gray-800 "><?= htmlspecialchars($item['quantity']) ?></p>
                  </div>
                  <div>
                    <p class="sm:text-end text-gray-800 ">₱<?= number_format($item['price'], 2) ?></p>
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
              
              <dd class="text-lg font-semibold text-gray-800">₱<?= number_format($_SESSION['tracked_order']['total_price'], 2) ?></dd>
            
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
      <?php 
        unset($_SESSION['tracked_order']); 
        unset($_SESSION['tracked_order_items']);
      ?>
    <?php endif; ?>

  </div>
</section>

  <?php include('./components/footer.php'); ?>
    
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