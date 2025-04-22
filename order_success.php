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
    SELECT oi.*, p.product_name as product_name, v.variant_name as variant_name
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
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
<?php include('./components/preloader.php'); ?>

<section id="order-success-section" class="flex-grow">
  <?php include('./components/navigation.php'); ?>

  <div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
      <!-- Success Message -->
      <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>        
      </div>

      <div style="width: 400px;" class="mx-auto bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200" id="orderReceipt">
        <!-- Receipt Header -->
        <div class="bg-blue-600 text-white text-center py-3">
          <h2 class="text-lg font-semibold">Order # <?= htmlspecialchars($order['order_id']) ?></h2>
        </div>

        <!-- Payment Success Info -->
        <div class="text-center py-4 border-b border-gray-200">
         
          <h3 class="text-gray-900 font-medium mt-2"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></h3>
          <p class="text-sm text-gray-500"><?= htmlspecialchars($order['address']) ?></p>
        </div>

        <!-- Order Summary -->
        <div class="px-6 py-4">
          <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Order Summary</h3>
          <div class="divide-y divide-gray-200 mt-2">
            <?php foreach ($items as $item): ?>
              <div class="py-2 flex justify-between items-center">
                <div>
                  <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
                  <p class="text-xs text-gray-500">Qty: <?= htmlspecialchars($item['quantity']) ?></p>
                </div>
                <p class="text-sm font-semibold">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="border-t mt-4 pt-2 flex justify-between font-medium text-gray-900">
            <p>Total</p>
            <p>₱<?= number_format($order['total_price'], 2) ?></p>
          </div>
        </div>

        <!-- Payment Information -->
        <div class="px-6 py-4 border-t border-gray-200">
          <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Payment Details</h3>
          <p class="text-sm text-gray-500 mt-2">Payment Method</p>
          <p class="text-sm font-medium"><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></p>

          <p class="text-sm text-gray-500 mt-2">Date & Time</p>
          <p class="text-sm font-medium"><?= $orderDate ?></p>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-xs text-gray-500 py-4">
          Please show this receipt for verification.<br>
          Thank you for shopping with us. Your order has been confirmed.
        </div>
      </div>

      <br><br>
      <!-- Actions -->
      <div class="flex flex-col sm:flex-row justify-center gap-4 py-6">
        <a href="index.php" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700">
          Continue Shopping
        </a>
        <a href="track.php" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
          Track Your Order
        </a>
        <button id="downloadBtn" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
          Print Receipt
        </button>
      </div>
      <br><br>
    </div>
  </div>

  <div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
      <!-- Success Message -->
      <div class="text-center mb-12" data-aos="fade-up">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
        <p class="text-gray-600 mb-6">Thank you for shopping with us. Your order has been confirmed.</p>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 inline-block">
          <p class="text-blue-800 font-medium">Order #<?= htmlspecialchars($order['order_id']) ?></p>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="bg-white shadow rounded-lg overflow-hidden mb-8" data-aos="fade-up" data-aos-delay="100">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Order Summary</h2>
        </div>
        <div class="divide-y divide-gray-200">
          <?php foreach ($items as $item): ?>
            <div class="px-6 py-4 flex items-center">
              <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-md overflow-hidden">
                <!-- Product image would go here -->
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-4 flex-1">
                <h3 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></h3>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($item['variant_name']) ?></p>
                <p class="text-sm text-gray-500">Qty: <?= htmlspecialchars($item['quantity']) ?></p>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex justify-between text-base font-medium text-gray-900">
            <p>Total</p>
            <p>₱<?= number_format($order['total_price'], 2) ?></p>
          </div>
        </div>
      </div>

      <!-- Order Details -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mb-8">
        <!-- Shipping Information -->
        <div class="bg-white shadow rounded-lg overflow-hidden" data-aos="fade-up" data-aos-delay="150">
          <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Shipping Information</h2>
          </div>
          <div class="px-6 py-4">
            <div class="mb-4">
              <p class="text-sm font-medium text-gray-500">Name</p>
              <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
            </div>
            <div class="mb-4">
              <p class="text-sm font-medium text-gray-500">Contact</p>
              <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($order['phone_number']) ?></p>
            </div>
            <div class="mb-4">
              <p class="text-sm font-medium text-gray-500">Email</p>
              <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($order['email']) ?></p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500">Shipping Address</p>
              <p class="mt-1 text-sm text-gray-900">
                <?= htmlspecialchars($order['address']) ?><br>
                <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['postal_code']) ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white shadow rounded-lg overflow-hidden" data-aos="fade-up" data-aos-delay="200">
          <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Payment Information</h2>
          </div>
          <div class="px-6 py-4">
            <div class="mb-4">
              <p class="text-sm font-medium text-gray-500">Payment Method</p>
              <p class="mt-1 text-sm text-gray-900"><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></p>
            </div>
            <div class="mb-4">
              <p class="text-sm font-medium text-gray-500">Order Date</p>
              <p class="mt-1 text-sm text-gray-900"><?= $orderDate ?></p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500">Status</p>
              <p class="mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                  <?= 
                    $order['order_status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                    ($order['order_status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                  ?>">
                  <?= htmlspecialchars($order['order_status']) ?>
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row justify-center gap-4" data-aos="fade-up" data-aos-delay="250">
        <a href="index.php" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700">
          Continue Shopping
        </a>
        <a href="track.php" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
          Track Your Order
        </a>
        <button onclick="window.print()" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
          Print Receipt
        </button>
      </div>
    </div>
  </div>

  <?php include('./components/footer.php'); ?>
</section>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>
  AOS.init();
</script>
<script src="node_modules/preline/dist/preline.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>

</body>
</html>