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
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
    <link href="../style.css" rel="stylesheet">

    <style>
        /* Import products.php design language */
        .category-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .category-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

        .modal-overlay {
            position: fixed; inset: 0; z-index: 999;
            display: flex; align-items: flex-start; justify-content: center;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        .modal-overlay.hidden { display: none; }

        .modal-box {
            background: white;
            width: 100%; max-width: 48rem;
            border-radius: 1.25rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            background: #fafafa;
        }
        .modal-header h3 { font-size: 1.125rem; font-weight: 700; color: #111827; }
        .modal-header p { font-size: 0.75rem; color: #6b7280; margin-top: 1px; }

        .modal-close {
            width: 2rem; height: 2rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #f3f4f6;
            color: #6b7280; border: none; cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .modal-close:hover { background: #fee2e2; color: #dc2626; }

        .modal-body { padding: 1.5rem; max-height: 75vh; overflow-y: auto; }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex; justify-content: flex-end; gap: 0.625rem;
        }

        .form-label {
            display: block; font-size: 0.8125rem; font-weight: 600; color: #374151;
            margin-bottom: 0.375rem;
        }
        
        .form-input {
            width: 100%; padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb; border-radius: 0.5rem;
            font-size: 0.875rem; color: #111827;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .form-input:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,0.1); }

        .section-title {
            font-size: 0.9375rem; font-weight: 700; color: #111827;
            border-left: 3px solid #ea580c;
            padding-left: 0.625rem;
            margin: 1.25rem 0 0.75rem;
        }

        .btn-primary {
            padding: 0.5rem 1.25rem;
            background: #ea580c; color: white;
            border-radius: 0.625rem; border: none;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: background 0.15s, transform 0.1s;
        }
        .btn-primary:hover { background: #c2410c; }
        .btn-primary:active { transform: scale(0.97); }

        .btn-secondary {
            padding: 0.5rem 1.25rem;
            background: white; color: #374151;
            border-radius: 0.625rem; border: 1px solid #e5e7eb;
            font-size: 0.875rem; font-weight: 500;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-secondary:hover { background: #f9fafb; }

        .btn-success {
            padding: 0.5rem 1rem;
            background: #dcfce7; color: #16a34a;
            border-radius: 0.5rem; border: none;
            font-size: 0.8125rem; font-weight: 600;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-success:hover { background: #bbf7d0; }

        .badge {
            display: inline-flex; align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.7rem; font-weight: 600;
        }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; }
        .badge-gray { background: #f3f4f6; color: #374151; }

        .stats-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.25rem;
            transition: all 0.2s ease;
        }
        .stats-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .image-thumb {
            position: relative;
            display: inline-block;
        }
        .image-thumb img {
            width: 5rem;
            height: 5rem;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .image-thumb .del-btn {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            width: 1.5rem;
            height: 1.5rem;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            opacity: 0;
            transition: opacity 0.15s;
        }
        .image-thumb:hover .del-btn { opacity: 1; }
    </style>
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
                <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
                    <span class="font-bold">' . ucfirst(htmlspecialchars($message['type'])) . '!</span> ' . htmlspecialchars($message['text']) . '
                </div>';
                unset($_SESSION['message']);
            }
            ?>

            <!-- Categories Content -->
            <?php include('./components/category_list.php'); ?>

        </div>
    </div>

    <!-- Add Category Modal (Redesigned) -->
    <div id="addCategoryModal" class="modal-overlay hidden">      
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3>Add New Category</h3>
                    <p>Create a new product category</p>
                </div>
                <button class="modal-close" onclick="closeModal('addCategoryModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <form id="addCategoryForm" action="./functions/add.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    
                    <p class="section-title">Basic Information</p>
                    
                    <div>
                        <label class="form-label">Category Name <span class="text-red-500">*</span></label>
                        <input type="text" name="category_name" required 
                               class="form-input" placeholder="e.g., Fresh Fish">
                    </div>
                    
                    <div>
                        <label class="form-label">Slug (URL-friendly name)</label>
                        <input type="text" name="category_slug" 
                               class="form-input" placeholder="e.g., fresh-fish">
                        <p class="text-xs text-gray-400 mt-1">Leave empty to auto-generate from name</p>
                    </div>
                    
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="category_description" rows="3" 
                                  class="form-input" style="resize:none"
                                  placeholder="Category description..."></textarea>
                    </div>
                    
                    <p class="section-title">Category Settings</p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Parent Category</label>
                            <select name="parent_id" class="form-input">
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
                        
                        <div>
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" value="0" min="0"
                                   class="form-input">
                        </div>
                    </div>
                    
                    <p class="section-title">Category Image</p>
                    
                    <div>
                        <input type="file" id="addCategoryImage" name="category_image" accept="image/*" class="hidden">
                        <button type="button" onclick="document.getElementById('addCategoryImage').click()"
                                class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
                            📸 Click to upload category image
                        </button>
                        <div id="addCategoryImagePreview" class="hidden mt-3">
                            <img src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border">
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('addCategoryModal')" class="btn-secondary">Cancel</button>
                <button type="submit" form="addCategoryForm" name="add_category" class="btn-primary">
                    Add Category
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal (Redesigned) -->
    <div id="editCategoryModal" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3>Edit Category</h3>
                    <p>Update category details below</p>
                </div>
                <button class="modal-close" onclick="closeModal('editCategoryModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="editCategoryContent" class="modal-body">
                <div class="flex items-center justify-center py-12 text-gray-400">
                    <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    Loading category data...
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal (Redesigned) -->
    <div id="deleteCategoryModal" class="modal-overlay hidden">
        <div class="modal-box" style="max-width:28rem">
            <div class="modal-header">
                <div>
                    <h3>Delete Category</h3>
                    <p>This action cannot be undone</p>
                </div>
                <button class="modal-close" onclick="closeModal('deleteCategoryModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="modal-body text-center">
                <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </div>
                
                <form action="./functions/delete.php" method="POST" id="deleteCategoryForm">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <p id="deleteCategoryName" class="text-sm font-semibold text-gray-800 mb-1"></p>
                    <p class="text-xs text-red-500 mb-5">
                        This will remove the category from all products and variants.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <button type="button" onclick="closeModal('deleteCategoryModal')" class="btn-secondary">Cancel</button>
                        <button type="submit" name="delete_category" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal controls
        window.closeModal = function(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = '';
        };

        // Open modals
        document.querySelectorAll("[data-modal-target]").forEach(button => {
            button.addEventListener("click", function() {
                const modalId = this.getAttribute("data-modal-target");
                document.getElementById(modalId).classList.remove("hidden");
                document.body.style.overflow = 'hidden';
            });
        });

        // Close on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Add Category image preview
        document.getElementById('addCategoryImage')?.addEventListener('change', function(e) {
            const preview = document.getElementById('addCategoryImagePreview');
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
            const modal = document.getElementById('editCategoryModal');
            const content = document.getElementById('editCategoryContent');
            
            content.innerHTML = `
                <div class="flex items-center justify-center py-12 text-gray-400">
                    <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    Loading category data...
                </div>`;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            fetch(`./functions/fetch_category.php?category_id=${categoryId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(error => {
                    content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load category.</p>';
                    console.error('Error:', error);
                });
        };

        // Delete category
        window.openDeleteCategoryModal = function(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').innerHTML = `Are you sure you want to delete <strong>"${categoryName}"</strong>?`;
            document.getElementById('deleteCategoryModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        // Event delegation for dynamically loaded modal content
        document.addEventListener('click', function(e) {
            // Delete category image button
            const delBtn = e.target.closest('[data-delete-category-image]');
            if (delBtn) {
                const categoryId = delBtn.dataset.deleteCategoryImage;
                if (!confirm('Delete this image?')) return;

                fetch('./functions/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete_category_image&category_id=' + categoryId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        delBtn.closest('div')?.remove();
                    } else {
                        alert('Failed to delete image: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(() => alert('Network error. Please try again.'));
            }
        });

        // Edit category image preview (delegated — handles dynamically loaded input)
        document.addEventListener('change', function(e) {
            if (e.target.id !== 'editCategoryImage') return;
            const preview = document.getElementById('editCategoryImagePreview');
            const img = preview?.querySelector('img');
            if (e.target.files[0] && img) {
                const reader = new FileReader();
                reader.onload = ev => {
                    img.src = ev.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>

    <?php $conn->close(); ?>

    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>