<?php
session_start();
include '../conn.php';
include './functions/slug_helper.php';

// Check if the supadmin is logged in
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true || !isset($_SESSION['account_id'])) {
  header("Location: ../index.php");
  exit;
}

$account_id = $_SESSION['account_id'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$totalQuery = "SELECT COUNT(*) as total FROM blogs";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $recordsPerPage);

$query = "SELECT * FROM blogs ORDER BY blog_created_at DESC LIMIT $offset, $recordsPerPage";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blogs | St. Joseph Fish Brokerage Inc.</title>

  <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
  
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link href="../style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">

  <!-- TinyMCE loaded in HEAD so it's ready before body scripts -->
  <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>

  <style>
    /* Import products.php design language */
    .blog-row { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .blog-row:hover { background-color: #fafafa; border-left-color: #ea580c; }

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
      width: 100%; max-width: 56rem;
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

    .btn-danger {
      padding: 0.5rem 1rem;
      background: #fee2e2; color: #dc2626;
      border-radius: 0.5rem; border: none;
      font-size: 0.8125rem; font-weight: 600;
      cursor: pointer; transition: background 0.15s;
    }
    .btn-danger:hover { background: #fecaca; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.7rem; font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
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
      width: 4rem;
      height: 4rem;
      border-radius: 0.5rem;
      object-fit: cover;
      border: 1px solid #e5e7eb;
    }

    .blog-card {
      animation: fadeInUp 0.4s ease both;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(16px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="bg-gray-50">
  
  <?php include('./components/header.php'); ?>

  <?php include('./components/sidebar.php'); ?>

  <!-- Content -->
  <div class="w-full lg:ps-64">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

      <?php
        if (!empty($_SESSION['message'])) {
          $message = $_SESSION['message'];
          $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
          echo '<div class="mt-2 ' . $alertType . ' text-sm rounded-xl p-4 flex items-center gap-2" role="alert">
              <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
          </div>';
          unset($_SESSION['message']);
        }
      ?>

      <!-- Blog List -->
      <?php include('./components/blog_list.php'); ?>

    </div>
  </div>


  <!-- Add Blog Modal (Redesigned) -->
  <div id="addBlogModal" class="modal-overlay hidden">      
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>New Blog Post</h3>
          <p>Fill in the details below to publish</p>
        </div>
        <button class="modal-close" onclick="closeModal('addBlogModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form method="POST" action="./functions/add.php" enctype="multipart/form-data" id="addBlogForm" class="space-y-4">
          <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />
          <input type="hidden" name="add_blog" value="1">
          
          <p class="section-title">Basic Information</p>
          
          <div>
            <label class="form-label">Title <span class="text-red-500">*</span></label>
            <input type="text" name="blog_title" required placeholder="Enter a compelling title..."
                  class="form-input">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Author <span class="text-red-500">*</span></label>
              <input type="text" name="blog_author" required placeholder="Author name"
                    class="form-input">
            </div>
            <div>
              <label class="form-label">Status</label>
              <select name="blog_status" class="form-input">
                  <option value="draft">📝 Draft</option>
                  <option value="published">✅ Published</option>
              </select>
            </div>
          </div>
          
          <div>
            <label class="form-label">Excerpt <span class="text-red-500">*</span></label>
            <textarea name="blog_excerpt" rows="2" placeholder="A short summary of the post..."
                      class="form-input resize-none"></textarea>
          </div>
          
          <p class="section-title">Media</p>
          
          <div>
            <label class="form-label">Featured Image</label>
            <input type="file" name="blog_featured_image" accept="image/jpeg,image/png,image/webp,image/gif"
                  class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, GIF · Max 5MB</p>
          </div>
          
          <div>
            <label class="form-label">Content <span class="text-red-500">*</span></label>
            <textarea name="blog_content" id="add_editor" class="w-full"></textarea>
          </div>
          
          <details class="group border border-gray-200 rounded-xl overflow-hidden">
            <summary class="flex items-center justify-between px-4 py-3 cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors text-sm font-medium text-gray-700 select-none">
                <span class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    SEO Settings <span class="text-xs font-normal text-gray-400">(optional)</span>
                </span>
                <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </summary>
            <div class="p-4 space-y-4 border-t border-gray-100">
              <div>
                <label class="form-label">Meta Title</label>
                <input type="text" name="blog_meta_title" placeholder="Leave empty to use post title"
                      class="form-input">
              </div>
              <div>
                <label class="form-label">Meta Description</label>
                <textarea name="blog_meta_description" rows="2" placeholder="Leave empty to use excerpt"
                          class="form-input resize-none"></textarea>
              </div>
              <div>
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="blog_meta_keywords" placeholder="fish, seafood, brokerage"
                      class="form-input">
              </div>
            </div>
          </details>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeModal('addBlogModal')" class="btn-secondary">Cancel</button>
        <button type="submit" form="addBlogForm" name="add_blog" class="btn-primary">
            Publish Post
        </button>
      </div>
    </div>
  </div>

     

  <!-- Edit Blog Modal (Redesigned) - will be populated dynamically -->
  <div id="editBlogModal" class="modal-overlay hidden">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h3>Edit Blog Post</h3>
          <p>Update your blog post below</p>
        </div>
        <button class="modal-close" onclick="closeModal('editBlogModal')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>
      
      <div id="editBlogContent" class="modal-body">
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading blog post...
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Blog Modal (Redesigned) -->
  <div id="deleteBlogModal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:28rem">
      <div class="modal-header">
        <div>
          <h3>Delete Blog Post</h3>
          <p>This action cannot be undone</p>
        </div>
        <button class="modal-close" onclick="closeModal('deleteBlogModal')">
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
        
        <form action="./functions/delete.php" method="POST" id="deleteBlogForm">
          <input type="hidden" name="blog_id" id="deleteBlogId">
          <p id="deleteBlogTitle" class="text-sm font-semibold text-gray-800 mb-1"></p>
          <p class="text-xs text-red-500 mb-5">This will permanently delete this blog post.</p>
          <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeModal('deleteBlogModal')" class="btn-secondary">Cancel</button>
            <button type="submit" name="delete_blog" class="btn-primary" style="background:#dc2626">Delete Permanently</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    tinymce.init({
      selector: '#add_editor',
      license_key: 'gpl',
      height: 400,
      menubar: false,
      plugins: 'lists link image table code wordcount',
      toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | code',
      content_style: 'body { font-family: Lexend, sans-serif; font-size: 15px; line-height: 1.7; color: #374151; padding: 12px; }',
      branding: false,
      promotion: false,
      setup: function(editor) {
        editor.on('change', function() {
          editor.save();
        });
      }
    });

    // Validate add form before submit
    document.querySelector('#addBlogModal form').addEventListener('submit', function(e) {
      const excerpt = this.querySelector('[name="blog_excerpt"]').value.trim();
      const content = tinymce.get('add_editor') ? tinymce.get('add_editor').getContent() : '';

      if (!excerpt) {
        e.preventDefault();
        alert('Please fill in the Excerpt field.');
        return;
      }
      if (!content || content === '<p></p>' || content === '<p><br></p>') {
        e.preventDefault();
        alert('Please fill in the Content field.');
        return;
      }

      if (tinymce.get('add_editor')) {
        tinymce.get('add_editor').save();
      }
    });

    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    window.closeModal = function(modalId) {
      document.getElementById(modalId).classList.add('hidden');
      document.body.style.overflow = '';
    };

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
      });
    });

    // Open add modal via data-modal-target
    document.querySelectorAll("[data-modal-target]").forEach(btn => {
      btn.addEventListener("click", function() {
        openModal(this.getAttribute("data-modal-target"));
      });
    });

    // Edit blog
    window.openEditModal = function(blogId) {
      const modal = document.getElementById('editBlogModal');
      const content = document.getElementById('editBlogContent');
      
      content.innerHTML = `
        <div class="flex items-center justify-center py-12 text-gray-400">
          <svg class="animate-spin mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
          </svg>
          Loading blog post...
        </div>`;
      modal.classList.remove('hidden');
      
      fetch(`./functions/fetch_blogs.php?blog_id=${blogId}`)
        .then(r => r.text())
        .then(html => {
          content.innerHTML = html;
          // Re-initialize TinyMCE for edit form
          if (typeof tinymce !== 'undefined') {
            tinymce.remove('#edit_editor');
            tinymce.init({
              selector: '#edit_editor',
              license_key: 'gpl',
              height: 400,
              menubar: false,
              plugins: 'lists link image table code wordcount',
              toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | code',
              content_style: 'body { font-family: Lexend, sans-serif; font-size: 15px; line-height: 1.7; color: #374151; padding: 12px; }',
              branding: false,
              promotion: false,
              setup: function(editor) {
                editor.on('change', function() {
                  editor.save();
                });
              }
            });
          }
        })
        .catch(() => {
          content.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to load blog post.</p>';
        });
    };

    // Delete blog
    window.openDeleteModal = function(blogId, blogTitle) {
      document.getElementById('deleteBlogId').value = blogId;
      document.getElementById('deleteBlogTitle').innerHTML = `Are you sure you want to delete <strong>"${blogTitle}"</strong>?`;
      document.getElementById('deleteBlogModal').classList.remove('hidden');
    };

    function deleteBlogImage(blogId) {
        fetch('./functions/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_blog_image&blog_id=' + blogId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the image container
                document.querySelector('.relative.inline-block').remove();
                // Show success message (you can add a toast notification here)
                alert('Image deleted successfully');
            } else {
                alert('Failed to delete image: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }

    // Filter posts
    function filterPosts(status) {
      const cards = document.querySelectorAll('.blog-card, .list-item');
      cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
          card.classList.remove('hidden-card');
        } else {
          card.classList.add('hidden-card');
        }
      });
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>
</html>