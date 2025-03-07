<?php
include '../conn.php'; // Include the database connection
session_start();

$query = "SELECT * FROM product_categories"; // Adjust table name if necessary
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category | St. Joseph Fish Brokerage Inc.</title>

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
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">

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
          Application Layout
          <svg class="shrink-0 mx-3 overflow-visible size-2.5 text-gray-400 dark:text-neutral-500" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </li>
        <li class="text-sm font-semibold text-gray-800 truncate dark:text-neutral-400" aria-current="page">
          Category
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
      <?php
        if (isset($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $alertType = ($message['type'] == 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';

          echo '
          <div class="mt-2 ' . $alertType . ' text-sm rounded-lg p-4" role="alert" tabindex="-1">
              <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
          </div>';

          // Clear the message after displaying it
          unset($_SESSION['message']);
        }
      ?>
    
      <!-- Table Card -->
      <?php include('./components/category_list.php'); ?>
      <!-- Table End -->

    </div>
  </div>
  <!-- End Content -->

  
<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96">
    <h3 class="text-lg font-semibold mb-4">Add New Category</h3>
    <form action="./functions/add.php" method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Name</label>
        <input type="text" name="category_name" required class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Description</label>
        <input type="text" name="category_description" required class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div class="flex justify-end">
        <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="document.getElementById('addCategoryModal').classList.add('hidden')">Cancel</button>
        <button type="submit" name="add_category" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Add</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96">
    <h3 class="text-lg font-semibold mb-4">Edit Category</h3>
    <form action="./functions/update.php" method="POST">
      <input type="hidden" name="category_id" id="edit_category_id">
      
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Name</label>
        <input type="text" name="category_name" required class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Description</label>
        <input type="text" name="category_description" required class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div class="flex justify-end">
        <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="document.getElementById('addCategoryModal').classList.add('hidden')">Cancel</button>
        <button type="submit" name="add_Category" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Add</button>
      </div>
      
      <div class="flex justify-end">
        <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="document.getElementById('editCategoryModal').classList.add('hidden')">Cancel</button>
        <button type="submit" name="update_Category" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<div id="deleteCategoryModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96">
    <h3 class="text-lg font-semibold mb-4">Delete Category</h3>
    <form action="./functions/delete.php" method="POST">
      <input type="hidden" name="category_id" id="delete_category_id">
      
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Name</label>
        <input type="text" name="category_name" required class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div class="mb-3">
        <label class="block text-sm font-medium">Category Description</label>
        <input type="text" name="category_description" required class="w-full px-3 py-2 border rounded-lg">
      </div>
   
      <div class="flex justify-end">
        <button type="button" class="mr-2 px-4 py-2 bg-gray-300 rounded-lg" onclick="document.getElementById('deleteCategoryModal').classList.add('hidden')">Cancel</button>
        <button type="submit" name="update_Category" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.querySelector('[data-modal-target="addCategoryModal"]').addEventListener('click', function() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
  });
</script>

<?php $conn->close(); ?>

  <!-- JS Implementing Plugins -->

  <!-- JS PLUGINS -->
  <!-- Required plugins -->
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- Apexcharts -->
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://preline.co/assets/js/hs-apexcharts-helpers.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>

