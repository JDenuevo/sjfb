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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">

  <!-- TinyMCE loaded in HEAD so it's ready before body scripts -->
  <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
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
          echo '<div class="mt-2 ' . $alertType . ' text-sm rounded-lg p-4" role="alert">
              <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
          </div>';
          unset($_SESSION['message']);
        }
      ?>

      <!-- Add Blog Modal -->
      <div id="addBlogModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
        <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
          <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
            <div>
              <h3 class="text-xl font-bold text-gray-900">New Blog Post</h3>
              <p class="text-sm text-gray-500 mt-0.5">Fill in the details below to publish</p>
            </div>
            <button type="button" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors" onclick="closeModal('addBlogModal')">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <form method="POST" action="./functions/add.php" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />
            <input type="hidden" name="add_blog" value="1">
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
              <input type="text" name="blog_title" required placeholder="Enter a compelling title..."
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Author <span class="text-red-500">*</span></label>
                <input type="text" name="blog_author" required placeholder="Author name"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="blog_status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                  <option value="draft">📝 Draft</option>
                  <option value="published">✅ Published</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Excerpt <span class="text-red-500">*</span></label>
              <textarea name="blog_excerpt" rows="2" placeholder="A short summary of the post..."
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none"></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Featured Image</label>
              <input type="file" name="blog_featured_image" accept="image/jpeg,image/png,image/webp,image/gif"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
              <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, GIF · Max 5MB</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Content <span class="text-red-500">*</span></label>
              <textarea name="blog_content" id="add_editor" class="w-full"></textarea>
            </div>
            
            <details class="group border border-gray-200 rounded-xl overflow-hidden">
              <summary class="flex items-center justify-between px-4 py-3 cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors text-sm font-medium text-gray-700 select-none">
                <span class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  SEO Settings <span class="text-xs font-normal text-gray-400">(optional)</span>
                </span>
                <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </summary>
              <div class="p-4 space-y-4 border-t border-gray-100">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Title</label>
                  <input type="text" name="blog_meta_title" placeholder="Leave empty to use post title"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Description</label>
                  <textarea name="blog_meta_description" rows="2" placeholder="Leave empty to use excerpt"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Keywords</label>
                  <input type="text" name="blog_meta_keywords" placeholder="fish, seafood, brokerage"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
              </div>
            </details>
            
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" onclick="closeModal('addBlogModal')"
                      class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                Cancel
              </button>
              <button type="submit"
                      class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-orange-600 rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/>
                </svg>
                Publish Post
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Blog List -->
      <?php include('./components/blog_list.php'); ?>

    </div>
  </div>

  <script>
    tinymce.init({
      selector: '#add_editor',
      height: 400,
      menubar: false,
      plugins: 'lists link image table code wordcount',
      toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | code',
      content_style: 'body { font-family: Lexend, sans-serif; font-size: 15px; line-height: 1.7; color: #374151; padding: 12px; }',
      branding: false,
      promotion: false,
      setup: function(editor) {
        editor.on('change', function() {
          editor.save(); // syncs content back to the textarea on every change
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

      // Sync TinyMCE content to textarea before submit
      if (tinymce.get('add_editor')) {
        tinymce.get('add_editor').save();
      }
    });

    function openModal(modalId) {
      document.getElementById(modalId).classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
      document.body.style.overflow = '';
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://preline.co/assets/js/hs-apexcharts-helpers.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
</body>
</html>