<?php
session_start();
include '../conn.php';

// Check if the supadmin is logged in
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$account_id = $_SESSION['account_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total categories count
$countQuery = "SELECT COUNT(*) as total FROM product_categories WHERE is_active = 1";
$countResult = $conn->query($countQuery);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $perPage);

$query = "SELECT 
            c.*,
            COUNT(DISTINCT pcl.product_id) as product_count,
            COUNT(DISTINCT pvc.variant_id) as variant_count,
            pc.category_name as parent_name
          FROM product_categories c
          LEFT JOIN product_category_links pcl ON c.category_id = pcl.category_id
          LEFT JOIN product_variants_categories pvc ON c.category_id = pvc.category_id
          LEFT JOIN product_categories pc ON c.parent_id = pc.category_id
          WHERE c.is_active = 1
          GROUP BY c.category_id
          ORDER BY c.sort_order ASC, c.category_name ASC
          LIMIT $perPage OFFSET $offset";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | St. Joseph Fish Brokerage Inc.</title>
    
    <!-- Favicons -->
    <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
    <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link href="../style.css" rel="stylesheet">
    <link href="../output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include('./components/header.php'); ?>
    
    <!-- Sidebar -->
    <?php include('./components/sidebar.php'); ?>

    <!-- Content -->
    <div class="w-full lg:ps-64">
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            
            <?php
            if (!empty($_SESSION['message'])) {
                $message = $_SESSION['message'];
                $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
                echo '
                <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-lg p-4" role="alert">
                    <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
                </div>';
                unset($_SESSION['message']);
            }
            ?>

            <!-- Categories Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            
                            <!-- Header -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:items-center border-b border-gray-200">
                                <div class="flex justify-between items-center w-full">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800">Categories</h2>
                                        <p class="text-sm text-gray-600">Manage your product categories</p>
                                    </div>
                                    <div class="inline-flex gap-x-2">
                                        <button class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700" 
                                                data-modal-target="addCategoryModal">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14" />
                                                <path d="M12 5v14" />
                                            </svg>
                                            Add Category
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="ps-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Category Name</span>
                                        </th>
                                        <th class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Slug</span>
                                        </th>
                                        <th class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Parent Category</span>
                                        </th>
                                        <th class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Level</span>
                                        </th>
                                        <th class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Products</span>
                                        </th>
                                        <th class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Sort Order</span>
                                        </th>
                                        <th class="px-6 py-3 text-end">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="ps-6 py-3">
                                            <div class="flex items-center gap-x-3">
                                                <?php if ($row['category_image']): ?>
                                                <img src="../uploads/categories/<?= htmlspecialchars($row['category_image']) ?>" 
                                                     class="w-8 h-8 rounded-full object-cover" alt="">
                                                <?php endif; ?>
                                                <span class="block text-sm font-semibold text-gray-800">
                                                    <?= htmlspecialchars($row['category_name']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="text-sm text-gray-600"><?= htmlspecialchars($row['category_slug']) ?></span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="text-sm text-gray-600">
                                                <?= $row['parent_name'] ? htmlspecialchars($row['parent_name']) : '<span class="text-gray-400">—</span>' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-1 text-xs font-medium <?= $row['category_level'] == 1 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?> rounded-full">
                                                Level <?= $row['category_level'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="text-sm font-medium text-gray-900"><?= $row['product_count'] ?></span>
                                            <span class="text-xs text-gray-500">(<?= $row['variant_count'] ?> variants)</span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="text-sm text-gray-600"><?= $row['sort_order'] ?></span>
                                        </td>
                                        <td class="px-6 py-3 text-end">
                                            <div class="inline-flex gap-1">
                                                <button onclick="openEditCategoryModal(<?= $row['category_id'] ?>)" 
                                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                        <path d="M16 5l3 3" />
                                                    </svg>
                                                </button>
                                                <button onclick="openDeleteCategoryModal(<?= $row['category_id'] ?>, '<?= htmlspecialchars($row['category_name']) ?>')" 
                                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M4 7l16 0" />
                                                        <path d="M10 11l0 6" />
                                                        <path d="M14 11l0 6" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <!-- Pagination -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-semibold text-gray-800"><?= $totalItems ?></span> results
                                    </p>
                                </div>
                                <div>
                                    <div class="inline-flex gap-x-2">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?= $page - 1 ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50">Prev</a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <a href="?page=<?= $i ?>" class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-800' ?> shadow-sm hover:bg-gray-50">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?= $page + 1 ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50">Next</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
      <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Add New Category</h3>
            
            <form action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Category Name *</label>
                        <input type="text" name="category_name" required 
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="e.g., Fresh Fish">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Slug (URL-friendly name)</label>
                        <input type="text" name="category_slug" 
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="e.g., fresh-fish">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from name</p>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                        <select name="parent_id" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">— No Parent (Top Level) —</option>
                            <?php
                            $parentQuery = "SELECT category_id, category_name, category_level FROM product_categories WHERE is_active = 1 ORDER BY category_level, category_name";
                            $parentResult = $conn->query($parentQuery);
                            while ($parent = $parentResult->fetch_assoc()):
                                $indent = str_repeat('— ', $parent['category_level'] - 1);
                            ?>
                                <option value="<?= $parent['category_id'] ?>">
                                    <?= $indent . htmlspecialchars($parent['category_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="category_description" rows="3" 
                                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Category description..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                        <input type="number" name="sort_order" value="0" min="0"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category Image</label>
                        <input type="file" name="category_image" accept="image/*" class="hidden" id="categoryImage">
                        <button type="button" onclick="document.getElementById('categoryImage').click()"
                                class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            📸 Upload Image
                        </button>
                    </div>
                </div>
                
                <div id="categoryImagePreview" class="hidden mt-2">
                    <img src="" alt="Preview" class="w-32 h-32 object-cover rounded-lg border">
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="submit" name="add_category" 
                            class="py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Add Category
                    </button>
                    <button type="button" onclick="closeModal('addCategoryModal')" 
                            class="py-2 px-4 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
      <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div id="editCategoryContent"></div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div id="deleteCategoryModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
      <div class="bg-white w-96 p-6 rounded-2xl shadow-2xl flex flex-col">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Delete Category</h3>
            <form action="./functions/delete.php" method="POST">
                <input type="hidden" name="category_id" id="deleteCategoryId">
                <p id="deleteCategoryName" class="text-gray-600 mb-4"></p>
                <p class="text-sm text-red-600 mb-4">
                    ⚠️ This will remove the category from all products and variants. Products without categories will be uncategorized.
                </p>
                <div class="flex justify-end space-x-3">
                    <button type="submit" name="delete_category" 
                            class="py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-600 transition">
                        Delete Category
                    </button>
                    <button type="button" onclick="closeModal('deleteCategoryModal')" 
                            class="py-2 px-4 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal controls
        window.closeModal = function(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        };

        // Open modals
        document.querySelectorAll("[data-modal-target]").forEach(button => {
            button.addEventListener("click", function() {
                const modalId = this.getAttribute("data-modal-target");
                document.getElementById(modalId).classList.remove("hidden");
            });
        });

        // Category image preview
        document.getElementById('categoryImage')?.addEventListener('change', function(e) {
            const preview = document.getElementById('categoryImagePreview');
            const img = preview.querySelector('img');
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Auto-generate slug from name
        document.querySelector('input[name="category_name"]')?.addEventListener('input', function(e) {
            const slugInput = document.querySelector('input[name="category_slug"]');
            if (slugInput && !slugInput.value) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
        });

        // Edit category
        window.openEditCategoryModal = function(categoryId) {
            fetch(`./functions/fetch_category.php?category_id=${categoryId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editCategoryContent').innerHTML = data;
                    document.getElementById('editCategoryModal').classList.remove('hidden');
                })
                .catch(error => console.error('Error:', error));
        };

        // Delete category
        window.openDeleteCategoryModal = function(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').innerText = `Delete "${categoryName}"?`;
            document.getElementById('deleteCategoryModal').classList.remove('hidden');
        };
    </script>

    <?php $conn->close(); ?>

    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>