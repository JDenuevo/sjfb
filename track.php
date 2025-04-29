<?php
session_start();
include 'conn.php';

// Set the time zone
date_default_timezone_set('Asia/Manila');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderId = trim($_POST['order_id']);
    $email = trim($_POST['email']);

    // Basic validation
    if (empty($orderId)) {
        $_SESSION['error'] = "Order ID is required";
        header("Location: track.php");
        exit();
    }

    if (empty($email)) {
        $_SESSION['error'] = "Email is required";
        header("Location: track.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: track.php");
        exit();
    }

    // Query the database
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_date, o.total_price, o.order_status, 
           o.payment_method, o.first_name, o.last_name, o.email,
           o.phone_number, o.address, o.postal_code, o.city
        FROM orders o
        WHERE o.order_id = ? 
        AND o.email = ?
    ");
    $stmt->bind_param("is", $orderId, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    
    // Get order items
    $itemsStmt = $conn->prepare("
    SELECT oi.*, p.product_name as product_name, v.variant_price, v.variant_name as variant_name
      FROM order_items oi
      LEFT JOIN products p ON oi.product_id = p.product_id
      LEFT JOIN product_variants v ON oi.variant_id = v.variant_id
      WHERE oi.order_id = ?");
      $itemsStmt->bind_param("i", $orderId);
      $itemsStmt->execute();
      // After you fetch the order items
      $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No order found with that ID and email combination";
        header("Location: track.php");
        exit();
    }

    $order = $result->fetch_assoc();
    $_SESSION['tracked_order'] = $order;
    $_SESSION['tracked_order_items'] = $items;  // Add this line
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
          <p class="mt-2 text-sm text-gray-600">Enter your order ID and email address to view your order status</p>
        </div>

        <form method="POST" class="space-y-6 mt-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="order_id" class="block text-sm font-medium text-gray-700">Order ID</label>
              <div class="mt-1">
                <input type="text" id="order_id" name="order_id" required
                  class="py-3 px-4 block w-full border border-black rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
              </div>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
              <div class="mt-1">
                <input type="email" id="email" name="email" required
                  class="py-3 px-4 block w-full border border-black rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
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

      <div class="sm:w-11/12 lg:w-3/4 mx-auto my-4">
        <div class="max-w-[40rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">

          <?php
            $orderStatus = $_SESSION['tracked_order']['status'] ?? 'Pending';

            $steps = [
              'Shipped' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 12.5l-5 -3l5 -3l5 3v5.5l-5 3z" /><path d="M11 9.5v5.5l5 3" /><path d="M16 12.545l5 -3.03" /><path d="M7 9h-5" /><path d="M7 12h-3" /><path d="M7 15h-1" /></svg>',
              'Out for Delivery' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5" /><path d="M3 9l4 0" /></svg>',
              'Delivered' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>',
            ];

            $stepLabels = array_keys($steps);
            $currentIndex = array_search($orderStatus, $stepLabels);
            $totalSteps = count($steps);

            // Define color mapping for statuses
            $statusColors = [
              'Pending' => ['bg-gray-100', 'text-gray-800', 'border-gray-300'],
              'Shipped' => ['bg-blue-100', 'text-blue-800', 'border-blue-500'],
              'Out for Delivery' => ['bg-orange-100', 'text-orange-800', 'border-orange-500'],
              'Delivered' => ['bg-green-100', 'text-green-800', 'border-green-500'],
            ];
          ?>

          <div class="w-full max-w-4xl mx-auto mt-10">
            <div class="flex justify-between items-center relative">
              <?php foreach ($steps as $label => $icon): ?>
                <?php
                  $stepIndex = array_search($label, $stepLabels);
                  $isDone = $stepIndex < $currentIndex;
                  $isActive = $stepIndex === $currentIndex;

                  // Determine colors based on status
                  $circleBg = 'bg-white';
                  $circleText = 'text-gray-400';
                  $circleBorder = 'border-gray-300';
                  $labelTextClass = 'text-gray-700';
                  $connectorBg = 'bg-gray-300';

                  if (isset($statusColors[$label])) {
                    if ($isDone) {
                      $circleBg = 'bg-green-500';
                      $circleText = 'text-white';
                      $circleBorder = 'border-green-500';
                      $connectorBg = 'bg-green-500';
                    } elseif ($isActive) {
                      $circleBg = $statusColors[$label][0];
                      $circleText = $statusColors[$label][1];
                      $circleBorder = $statusColors[$label][2];
                      $labelTextClass = 'text-blue-600 font-semibold';
                    }
                  } elseif ($isDone) {
                    $circleBg = 'bg-green-500';
                    $circleText = 'text-white';
                    $circleBorder = 'border-green-500';
                    $connectorBg = 'bg-green-500';
                  } elseif ($isActive) {
                    $circleBg = 'bg-orange-500'; // Default active color
                    $circleText = 'text-white';
                    $circleBorder = 'border-orange-500';
                    $labelTextClass = 'text-blue-600 font-semibold';
                  }
                ?>

                <div class="flex-1 flex items-center justify-center relative">
                  <div class="z-10 flex items-center justify-center w-10 h-10 rounded-full border-4
                    <?= $circleBorder ?> <?= $circleBg ?> <?= $circleText ?>">
                    <?= $isDone ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>' : $icon ?>
                  </div>

                  <?php if ($stepIndex < $totalSteps - 1): ?>
                    <div class="absolute top-1/2 left-full w-full h-1 -translate-y-1/2 bg-gray-300 z-0">
                      <div class="h-1 transition-all duration-500 <?= $stepIndex < $currentIndex ? 'bg-green-500' : 'bg-gray-300' ?>"></div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="flex justify-between mt-4 text-sm">
              <?php foreach ($stepLabels as $label): ?>
                <?php
                  $stepIndex = array_search($label, $stepLabels);
                  $isActive = $stepIndex === $currentIndex;
                  $labelTextClass = 'text-gray-700';
                  if (isset($statusColors[$label]) && $isActive) {
                    $labelTextClass = 'text-blue-600 font-semibold';
                  } elseif ($isActive && !isset($statusColors[$label])) {
                    $labelTextClass = 'text-blue-600 font-semibold'; // Default active label color
                  }
                ?>
                <div class="w-1/3 text-center <?= $labelTextClass ?>">
                  <?= $label ?>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="mt-4 text-center text-sm font-medium text-gray-700">
              <?= round((($currentIndex + 1) / $totalSteps) * 100) ?>% Complete
            </div>
          </div>
        </div>

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
              <span class="mt-1 block text-gray-500 text-lg"><?= htmlspecialchars($_SESSION['tracked_order']['order_id']) ?></span>

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