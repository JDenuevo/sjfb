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
               o.payment_method, o.first_name, o.last_name
        FROM orders o
        WHERE o.order_id = ? 
        AND o.email = ?
    ");
    $stmt->bind_param("is", $orderId, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No order found with that ID and email combination";
        header("Location: track.php");
        exit();
    }

    $order = $result->fetch_assoc();
    $_SESSION['tracked_order'] = $order;
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
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if (!empty($_SESSION['success']) || !empty($_SESSION['error'])): ?>
      <div class="mb-6">
        <div class="<?= !empty($_SESSION['success']) ? 'bg-teal-50 border-teal-500 text-teal-900' : 'bg-red-50 border-red-500 text-red-900' ?> border rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <?php if (!empty($_SESSION['success'])): ?>
                <svg class="h-5 w-5 text-teal-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              <?php else: ?>
                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              <?php endif; ?>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium">
                <?= htmlspecialchars(!empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error']) ?>
              </p>
            </div>
          </div>
        </div>
      </div>
      <?php unset($_SESSION['success'], $_SESSION['error']); ?>
    <?php endif; ?>

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

        <?php if (isset($_SESSION['tracked_order'])): ?>
          <div class="mt-10 border-t border-gray-200 pt-8">
            <h3 class="text-lg font-medium text-gray-900">Order Details</h3>
            
            <div class="mt-6 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-4">
              <div>
                <h4 class="text-sm font-medium text-gray-500">Order Number</h4>
                <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($_SESSION['tracked_order']['order_id']) ?></p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Date Placed</h4>
                <p class="mt-1 text-sm text-gray-900"><?= date('F j, Y \a\t g:i A', strtotime($_SESSION['tracked_order']['order_date'])) ?></p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Customer</h4>
                <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($_SESSION['tracked_order']['first_name'] . ' ' . $_SESSION['tracked_order']['last_name']) ?></p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Payment Method</h4>
                <p class="mt-1 text-sm text-gray-900"><?= ucfirst(htmlspecialchars($_SESSION['tracked_order']['payment_method'])) ?></p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Total Amount</h4>
                <p class="mt-1 text-sm text-gray-900">₱<?= number_format($_SESSION['tracked_order']['total_price'], 2) ?></p>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-500">Status</h4>
                <p class="mt-1">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    <?= 
                      $_SESSION['tracked_order']['order_status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                      ($_SESSION['tracked_order']['order_status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                    ?>">
                    <?= htmlspecialchars($_SESSION['tracked_order']['order_status']) ?>
                  </span>
                </p>
              </div>
            </div>
          </div>
          <?php unset($_SESSION['tracked_order']); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

    <?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <?php include('live_chat.php'); ?>
  
</body>
</html>