<?php
include '../conn.php'; // Include the database connection
session_start();

$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN product_categories c ON p.product_category = c.category_id";

$result = $conn->query($query);

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

  <!-- CSS Files -->
  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">

  <!-- CSS Preline -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<body class="bg-gray-50 dark:bg-neutral-900">
  
  <!-- Header -->
  <?php include('./components/header.php'); ?>

  <!-- ========== MAIN CONTENT ========== -->
  <!-- Breadcrumb -->
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y px-4 sm:px-6 lg:px-8 lg:hidden dark:bg-neutral-800 dark:border-neutral-700">
    <div class="flex items-center py-2">
      <!-- Navigation Toggle -->
      <button type="button" class="size-8 flex justify-center items-center gap-x-2 border border-gray-200 text-gray-800 hover:text-gray-500 rounded-lg focus:outline-none focus:text-gray-500 disabled:opacity-50 disabled:pointer-events-none dark:border-neutral-700 dark:text-neutral-200 dark:hover:text-neutral-500 dark:focus:text-neutral-500" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
        <span class="sr-only">Toggle Navigation</span>
        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="3" rx="2" />
          <path d="M15 3v18" />
          <path d="m8 9 3 3-3 3" />
        </svg>
      </button>
      <!-- End Navigation Toggle -->

      <!-- Breadcrumb -->
      <ol class="ms-3 flex items-center whitespace-nowrap">
        <li class="flex items-center text-sm text-gray-800 dark:text-neutral-400">
          Navigation
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400 dark:text-neutral-500" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate dark:text-neutral-400" aria-current="page">
          Products
        </li>
      </ol>
      <!-- End Breadcrumb -->
    </div>
  </div>
  <!-- End Breadcrumb -->

  <!-- Sidebar -->
  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      
      <!-- Table Card -->
      <?php include('./components/product_list.php'); ?>
      <!-- Table End -->

    </div>
  </div>
  <!-- End Content -->

<!-- Add Product Modal -->
<div id="addProductModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all scale-95 hover:scale-100">
    <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Product</h3>
    
    <form action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
      
      <!-- Product Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Product Name</label>
        <input type="text" name="product_name" placeholder="Product Name" required class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div class="grid grid-cols-2 gap-4">
       <!-- Product Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Product Description</label>
          <input type="text" name="product_description" placeholder="Description" required class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <!-- Product Size -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Product Size</label>
          <input type="text" name="product_size" placeholder="Size" class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Category</label>
          <select name="product_category" required class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="" disabled selected>Select a category</option>
            <?php
            $sql = "SELECT * FROM product_categories";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $category_id = $row['category_id'];
                $category_name = $row['category_name'];
                echo "<option value=\"$category_id\">$category_name</option>";
              }
            ?>
          </select>
        </div>

        <!-- Stock -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Stock</label>
          <input type="number" name="product_stock" placeholder="Stock" required class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Price</label>
          <input type="text" name="product_price" placeholder="Price" required class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <!-- Discounted Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Discounted Price</label>
          <input type="text" name="discount_price" placeholder="Discounted Price" class="w-full px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
      </div>

      <!-- Product Images Upload -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Upload Product Images</label>
        <input type="file" id="productImages" name="product_images[]" multiple required class="hidden" accept="image/*" onchange="previewImages(event)">
        <button type="button" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center" onclick="document.getElementById('productImages').click()">📸 Select Images</button>
        <p class="text-xs text-gray-500 mt-1">You can select up to 5 images.</p>

        <!-- Image Preview Section -->
        <div id="imagePreview" class="grid grid-cols-5 gap-2 mt-3"></div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3 mt-4">
        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                onclick="document.getElementById('addProductModal').classList.add('hidden')">Cancel</button>
        
        <button type="submit" name="add_product" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">Add Product</button>
      </div>

    </form>
  </div>
</div>

<script>
  const imageInput = document.getElementById("productImages");
  const previewContainer = document.getElementById("imagePreview");
  let selectedFiles = []; // Store selected files

  imageInput.addEventListener("change", function (event) {
    const newFiles = Array.from(event.target.files);

    // Prevent adding more than 5 images
    if (selectedFiles.length + newFiles.length > 5) {
      alert("You can only upload up to 5 images.");
      return;
    }

    newFiles.forEach((file) => {
      selectedFiles.push(file);
    });

    updateImagePreview();
  });

  function updateImagePreview() {
    previewContainer.innerHTML = ""; // Clear previous preview

    selectedFiles.forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = function (e) {
        const div = document.createElement("div");
        div.classList.add("relative");

        const img = document.createElement("img");
        img.src = e.target.result;
        img.classList.add("w-16", "h-16", "object-cover", "rounded-lg", "border");

        // Remove Button
        const removeBtn = document.createElement("button");
        removeBtn.innerHTML = "X";
        removeBtn.classList.add(
          "absolute", "top-0", "right-0", "bg-red-600", "text-white",
          "rounded-full", "text-xs", "w-5", "h-5", "flex", "items-center", "justify-center"
        );
        removeBtn.addEventListener("click", function () {
          selectedFiles.splice(index, 1); // Remove from array
          updateImagePreview(); // Refresh preview
        });

        div.appendChild(img);
        div.appendChild(removeBtn);
        previewContainer.appendChild(div);
      };
      reader.readAsDataURL(file);
    });

    // Update input field with modified file list
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach((file) => dataTransfer.items.add(file));
    imageInput.files = dataTransfer.files;
  }
</script>


<script>
  document.querySelectorAll('[data-modal-target]').forEach(button => {
    button.addEventListener('click', function() {
      const modalId = this.getAttribute('data-modal-target');
      document.getElementById(modalId).classList.remove('hidden');
    });
  });

  function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
  }
</script>

<?php $conn->close(); ?>

  <!-- JS Implementing Plugins -->

  <!-- JS PLUGINS -->
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="../node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- Apexcharts -->
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://preline.co/assets/js/hs-apexcharts-helpers.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>

