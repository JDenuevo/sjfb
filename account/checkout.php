<?php
session_start();
include '../conn.php';

// Check if the user is logged in as user and account_id exists
if (!isset($_SESSION["loggedinasuser"]) || $_SESSION["loggedinasuser"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../../index.php");
    exit;
}

// Fetch all products with their primary image and variants
$productQuery = "SELECT p.product_id, p.product_name, p.product_description, 
                 pi.image_path, 
                 v.variant_id, v.variant_name, v.variant_price, v.discount_price
          FROM products p
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN product_variants v ON p.product_id = v.product_id
          ORDER BY p.created_at DESC";

$productResult = $conn->query($productQuery);

if (!$productResult) {
    die("Error fetching products: " . $conn->error);
}

// Initialize user details array
$userDetails = [];

// Check if user is logged in
if (isset($_SESSION['account_id'])) {
    
    $accountId = $_SESSION['account_id'];
    
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, postal_code, city FROM accounts WHERE account_id = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userDetails = $userResult->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | St. Joseph Fish Brokerage Inc.</title>

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
  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
<?php include('../components/preloader.php'); ?>
<!-- Our Story Section -->
<section id="checkout-section">
    <?php
        if (!empty($_SESSION['success']) || !empty($_SESSION['error'])) {
            $messageText = !empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error'];
            $messageType = !empty($_SESSION['success']) ? 'success' : 'error';
            $alertType = ($messageType === 'success') ? 'bg-teal-500 text-green' : 'bg-red-500 text-red';

            echo '
            <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-lg p-4 text-center text-red-500" role="alert">
                <span class="font-bold">' . ucfirst(htmlspecialchars($messageType)) . '!</span> ' . htmlspecialchars($messageText) . '
            </div>';

            // Unset messages after displaying
            unset($_SESSION['success'], $_SESSION['error']);
        }
    ?>
    <div class="my-10">
        <?php include('./components/to_checkout.php'); ?>
    </div>
    
</section>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <?php include('../live_chat.php'); ?>
  
</body>
</html>

