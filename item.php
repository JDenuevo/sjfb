<?php
include 'conn.php'; // Database connection

// Get the base URL for your site
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';

// Get product name from URL and convert back from URL format
$productName = isset($_GET['name']) ? urldecode(str_replace('-', ' ', $_GET['name'])) : '';

if (empty($productName)) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

// Fetch product by name with its variants and images
$query = "SELECT p.*, 
                 pv.variant_id, pv.variant_name, pv.variant_price, pv.discount_price, pv.stock_quantity,
                 pi.image_path, pi.is_primary
          FROM products p
          LEFT JOIN product_variants pv ON p.product_id = pv.product_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id
          WHERE LOWER(REPLACE(p.product_name, ' ', '-')) = LOWER(REPLACE(?, ' ', '-'))
          ORDER BY p.product_id, pv.variant_id, pi.is_primary DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $productName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    include('404.php');
    die();
}

// Process the result set
$product = null;
$variants = [];
$images = [];
$primaryImage = 'default-image.jpg';

while ($row = $result->fetch_assoc()) {
    if ($product === null) {
        $product = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_description' => $row['product_description']
        ];
    }
    
    // Handle variants
    if ($row['variant_id'] && !isset($variants[$row['variant_id']])) {
        $variants[$row['variant_id']] = [
            'variant_name' => $row['variant_name'],
            'variant_price' => $row['variant_price'],
            'discount_price' => $row['discount_price'] ? $row['discount_price'] : $row['variant_price'],
            'stock' => $row['stock_quantity']
        ];
    }
    
    // Handle images
    if ($row['image_path']) {
        $images[] = $row['image_path'];
        if ($row['is_primary']) {
            $primaryImage = $row['image_path'];
        }
    }
}

// If no images are found, use the default image
if (empty($images)) {
    $images[] = 'default-image.jpg';
}

// Set default price to first variant if exists
$defaultVariant = reset($variants);
$defaultPrice = $defaultVariant ? $defaultVariant['variant_price'] : 0;
$defaultDiscountPrice = $defaultVariant ? $defaultVariant['discount_price'] : 0;

// Generate canonical URL
$canonicalUrl = $baseUrl . "item/" . strtolower(str_replace(' ', '-', $product['product_name']));
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['product_name']) ?> | St. Joseph Fish Brokerage Inc.</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="<?= htmlspecialchars($product['product_description']) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>" />
  
  <!-- Favicons -->
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="<?= $baseUrl ?>assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link rel="stylesheet" href="<?= $baseUrl ?>style.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>output.css">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<!-- Hero Section -->
<section id="home-section">
  <?php include('./components/navigation.php'); ?>

  <div class="container max-w-6xl mx-auto p-6">
    <!-- Breadcrumbs -->
    <div class="mb-4 text-sm text-gray-600">
      <a href="<?= $baseUrl ?>" class="hover:text-orange-500">Home</a> &gt;
      <a href="<?= $baseUrl ?>products" class="hover:text-orange-500">Products</a> &gt;
      <span class="text-gray-800"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Left Side: Product Image & Thumbnails -->
      <div class="flex flex-col items-center">
        <!-- Main Image -->
        <div class="max-w-xl">
          <img id="mainImage" src="<?= $baseUrl ?>admin/uploads/products/<?= htmlspecialchars($primaryImage) ?>" 
               class="w-full h-80 object-contain rounded-lg bg-gray-200" 
               alt="<?= htmlspecialchars($product['product_name']) ?>">
        </div>

        <!-- Thumbnail Images -->
        <div class="flex justify-center space-x-3 mt-4">
          <?php foreach ($images as $image): ?>
            <img src="<?= $baseUrl ?>admin/uploads/products/<?= htmlspecialchars($image) ?>" 
                 class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-gray-500"
                 onclick="changeImage('<?= htmlspecialchars($image) ?>')"
                 alt="<?= htmlspecialchars($product['product_name']) ?> - Thumbnail">
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right Side: Product Details -->
      <div class="bg-white rounded-lg p-6 shadow-lg">
        <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p class="mt-2 text-gray-600"><?= htmlspecialchars($product['product_description']) ?></p>

        <!-- Price and Discount -->
        <div class="flex items-center space-x-3 mt-4">
          <?php if (!empty($variants) && $defaultDiscountPrice < $defaultPrice): ?>
            <span class="price-display text-gray-500 line-through text-lg">₱<?= number_format($defaultPrice, 2) ?></span>
          <?php endif; ?>
          <span class="discount-display text-red-600 font-bold text-2xl">₱<?= number_format($defaultDiscountPrice, 2) ?></span>
          <?php if (!empty($variants) && $defaultDiscountPrice < $defaultPrice): ?>
            <span class="discount-percent bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full">
              SAVE <?= number_format(100 - ($defaultDiscountPrice / $defaultPrice * 100), 0) ?>%
            </span>
          <?php endif; ?>
        </div>

        <!-- Variant Selection -->
        <?php if (!empty($variants)): ?>
          <div class="mt-4">
            <label class="block text-gray-700 mb-2">Variants:</label>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($variants as $variant_id => $variant): ?>
                <button type="button" 
                        class="variant-btn px-4 py-2 border rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 <?= $variant_id === array_key_first($variants) ? 'bg-orange-500 text-white' : '' ?>"
                        data-variant-id="<?= $variant_id ?>"
                        data-price="<?= htmlspecialchars($variant['variant_price']) ?>"
                        data-discount="<?= htmlspecialchars($variant['discount_price']) ?>"
                        onclick="selectVariant(this)">
                  <?= htmlspecialchars($variant['variant_name']) ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Quantity Selector -->
        <div class="flex items-center mt-5">
          <span class="mr-3 text-gray-700">Quantity:</span>
          <div class="inline-flex border rounded-md overflow-hidden">
            <button type="button" class="px-3 py-1 bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="changeQuantity(-1)">-</button>
            <input id="quantity" type="text" value="1" class="w-10 text-center border-none outline-none" readonly>
            <button type="button" class="px-3 py-1 bg-gray-200 text-gray-700 hover:bg-gray-300" onclick="changeQuantity(1)">+</button>
          </div>
          <span class="ml-2 text-sm text-gray-500">per pcs</span>
        </div>

        <!-- Call-to-Action Buttons -->
        <div class="flex flex-col space-y-3 mt-6">
          <button type="button" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-md transition duration-200">
            ADD TO CART
          </button>
          <button type="button" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-md transition duration-200">
            BUY IT NOW
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>

<script>
  function changeImage(newImage) {
    document.getElementById('mainImage').src = '<?= $baseUrl ?>admin/uploads/products/' + newImage;
  }

  function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newQuantity = parseInt(quantityInput.value) + change;
    if (newQuantity < 1) newQuantity = 1;
    quantityInput.value = newQuantity;
  }

  function selectVariant(button) {
    // Update active state
    document.querySelectorAll('.variant-btn').forEach(btn => {
      btn.classList.remove('bg-orange-500', 'text-white');
      btn.classList.add('border', 'hover:bg-gray-100');
    });
    button.classList.add('bg-orange-500', 'text-white');
    button.classList.remove('border', 'hover:bg-gray-100');
    
    // Update prices
    const price = parseFloat(button.dataset.price);
    const discountPrice = parseFloat(button.dataset.discount);
    
    // Get display elements
    const priceDisplay = document.querySelector('.price-display');
    const discountDisplay = document.querySelector('.discount-display');
    const discountPercent = document.querySelector('.discount-percent');
    
    // Update displayed prices
    discountDisplay.textContent = `₱${discountPrice.toFixed(2)}`;
    
    if (discountPrice < price) {
      priceDisplay.textContent = `₱${price.toFixed(2)}`;
      priceDisplay.classList.remove('hidden');
      discountPercent.textContent = `SAVE ${Math.round(100 - (discountPrice / price * 100))}%`;
      discountPercent.classList.remove('hidden');
    } else {
      priceDisplay.classList.add('hidden');
      discountPercent.classList.add('hidden');
    }
  }
</script>

<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script>
  AOS.init();
</script>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>

</body>
</html>