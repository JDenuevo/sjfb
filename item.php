<?php
include 'conn.php'; // Adjust as necessary

// Get product ID from the request
$productID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productID === 0) {
    die("Invalid product ID.");
}

// Fetch product details
$query = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productID);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Fetch product images
$imageQuery = "SELECT image_path, is_primary FROM product_images WHERE product_id = ?";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param("i", $productID);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();

$images = [];
$primaryImage = 'default-image.jpg'; // Fallback image

while ($row = $imageResult->fetch_assoc()) {
    $images[] = $row['image_path'];
    if ($row['is_primary']) {
        $primaryImage = $row['image_path'];
    }
}

// If no images are found, use the default image
if (empty($images)) {
    $images[] = 'default-image.jpg';
}

// Handle missing discount
$productDiscount = isset($product['product_discount']) ? floatval($product['product_discount']) : 0;
$discountedPrice = $product['product_price'] * ((100 - $productDiscount) / 100);
?>
<?php
session_start();
include 'conn.php';

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
  <title>St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

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

<!-- Hero Section -->
  <section id="home-section">
    
    <?php include('./components/navigation.php'); ?>
        <div class="container max-w-6xl mx-auto p-6">
            <div class="grid grid-cols-2 w-full">
                
                <!-- Left Side: Product Image & Thumbnails -->
                <div class="flex flex-col items-center">
                    <!-- Main Image -->
                    <div class="max-w-xl">
                        <img id="mainImage" src="http://localhost/sjfbi-js/admin/uploads/products/<?php echo htmlspecialchars($primaryImage); ?>" 
                            class="w-full h-80 object-contain rounded-lg bg-gray-200 bg-red" 
                            alt="Product Image">
                    </div>

                    <!-- Thumbnail Images -->
                    <div class="flex justify-center space-x-3 mt-4">
                        <?php foreach ($images as $image) { ?>
                            <img src="http://localhost/sjfbi-js/admin/uploads/products/<?php echo htmlspecialchars($image); ?>" 
                                class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-gray-500"
                                onclick="changeImage('<?php echo htmlspecialchars($image); ?>')">
                        <?php } ?>
                    </div>
                </div>

                <!-- Right Side: Product Details -->
                <div class="bg-white rounded-lg p-6 shadow-lg">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h1>

                    <!-- Price and Discount -->
                    <div class="flex items-center space-x-3 mt-4">
                        <span class="text-gray-500 line-through text-lg">₱<?php echo number_format($product['product_price'], 2); ?></span>
                        <span class="text-red-600 font-bold text-2xl">₱<?php echo number_format($discountedPrice, 2); ?></span>
                        <?php if ($productDiscount > 0) { ?>
                            <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full">
                                SAVE <?php echo $productDiscount; ?>%
                            </span>
                        <?php } ?>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="flex items-center mt-5">
                        <span class="mr-3 text-gray-700">Quantity:</span>
                        <div class="inline-flex border rounded-md overflow-hidden">
                            <button class="px-3 py-1 bg-gray-200 text-gray-700" onclick="changeQuantity(-1)">-</button>
                            <input id="quantity" type="text" value="1" class="w-10 text-center border-none outline-none">
                            <button class="px-3 py-1 bg-gray-200 text-gray-700" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>

                    <!-- Call-to-Action Buttons -->
                    <div class="flex flex-col space-y-3 mt-6">
                        <button type="button" class="bg-orange-500 hover:bg-orange-600 text-black font-bold py-2 rounded-md">ADD TO CART</button>
                        <button type="submit" class=" bg-green-500 hover:bg-green-600 text-black font-bold py-2 rounded-md">BUY IT NOW</button>
                    </div>
                </div>

            </div>
        </div>

    </section>
  
  <?php include('./components/footer.php'); ?>

<script>
    function changeQuantity(amount) {
        let quantityElement = document.getElementById('quantity');
        let quantity = parseInt(quantityElement.value);
        quantity = Math.max(1, quantity + amount);
        quantityElement.value = quantity;
    }
</script>

<script>
    let currentImageIndex = 0;
    const images = document.querySelectorAll('.hs-carousel-item');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const totalImages = images.length;

    function changeImage(imageSrc) {
    document.getElementById('mainImage').src = "http://localhost/sjfbi-js/admin/uploads/products/" + imageSrc;
    }

    function showImage(index) {
        images[currentImageIndex].classList.remove('active');
        thumbnails[currentImageIndex].classList.remove('active');
        currentImageIndex = index;
        images[currentImageIndex].classList.add('active');
        thumbnails[currentImageIndex].classList.add('active');
    }
</script>

<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>