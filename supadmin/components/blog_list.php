<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Blog Posts</h1>
      <p class="text-sm text-gray-500 mt-0.5">
        <?php echo $totalItems; ?> post<?php echo $totalItems !== 1 ? 's' : ''; ?> total
      </p>
    </div>
    <div class="flex items-center gap-3">
      <!-- View Toggle -->
      <div class="flex items-center bg-gray-100 rounded-lg p-1 gap-1">
        <button id="gridViewBtn" onclick="setView('grid')" class="view-btn active p-1.5 rounded-md text-gray-600 hover:text-gray-900 transition-colors" title="Grid view">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
          </svg>
        </button>
        <button id="listViewBtn" onclick="setView('list')" class="view-btn p-1.5 rounded-md text-gray-600 hover:text-gray-900 transition-colors" title="List view">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
        </button>
      </div>
      <!-- Filter -->
      <select id="statusFilter" onchange="filterPosts(this.value)" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 focus:outline-none">
        <option value="all">All Posts</option>
        <option value="published">Published</option>
        <option value="draft">Drafts</option>
      </select>
      <!-- Add Button -->
      <button onclick="openModal('addBlogModal')" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M5 12h14"/><path d="M12 5v14"/>
        </svg>
        New Post
      </button>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="grid grid-cols-3 gap-4">
    <?php
      $publishedCount = 0;
      $draftCount = 0;
      // Quick count from existing result — we'll use PHP vars set in blogs.php
      $statsQuery = "SELECT blog_status, COUNT(*) as cnt FROM blogs GROUP BY blog_status";
      $statsResult = mysqli_query($conn, $statsQuery);
      $statusCounts = ['published' => 0, 'draft' => 0];
      while ($sr = mysqli_fetch_assoc($statsResult)) {
        $statusCounts[$sr['blog_status']] = $sr['cnt'];
      }
    ?>
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
      </div>
      <div>
        <p class="text-2xl font-bold text-gray-900"><?php echo $totalItems; ?></p>
        <p class="text-xs text-gray-500">Total Posts</p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <div>
        <p class="text-2xl font-bold text-gray-900"><?php echo $statusCounts['published']; ?></p>
        <p class="text-xs text-gray-500">Published</p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <div>
        <p class="text-2xl font-bold text-gray-900"><?php echo $statusCounts['draft']; ?></p>
        <p class="text-xs text-gray-500">Drafts</p>
      </div>
    </div>
  </div>

  <!-- Blog Posts Grid -->
  <?php if (mysqli_num_rows($result) > 0): ?>
    <div id="postsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 posts-container">
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="blog-card group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg hover:border-orange-200 transition-all duration-300 flex flex-col" data-status="<?php echo $row['blog_status']; ?>">
          
          <!-- Image -->
          <div class="relative h-44 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden flex-shrink-0">
            <?php if (!empty($row['blog_featured_image'])): ?>
              <img src="<?php echo htmlspecialchars($row['blog_featured_image']); ?>" 
                   alt="<?php echo htmlspecialchars($row['blog_title']); ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <circle cx="8.5" cy="8.5" r="1.5"></circle>
                  <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
              </div>
            <?php endif; ?>
            
            <!-- Status Badge Overlay -->
            <div class="absolute top-3 left-3">
              <?php if ($row['blog_status'] === 'published'): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-500 text-white shadow-sm">
                  <span class="w-1.5 h-1.5 rounded-full bg-white inline-block"></span>
                  Published
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-400 text-white shadow-sm">
                  <span class="w-1.5 h-1.5 rounded-full bg-white inline-block"></span>
                  Draft
                </span>
              <?php endif; ?>
            </div>

            <!-- Quick Action Overlay -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
              <a href="/sjfbi-js/blogs/<?php echo $row['blog_slug']; ?>" target="_blank"
                 class="w-9 h-9 rounded-full bg-white/90 flex items-center justify-center text-blue-600 hover:bg-white hover:scale-110 transition-all shadow-md" 
                 title="View Post">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                  <polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
              </a>
              <button onclick="openEditModal(<?php echo $row['blog_id']; ?>)"
                      class="w-9 h-9 rounded-full bg-white/90 flex items-center justify-center text-orange-600 hover:bg-white hover:scale-110 transition-all shadow-md"
                      title="Edit Post">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                </svg>
              </button>
              <button onclick="openDeleteModal(<?php echo $row['blog_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['blog_title'])); ?>')"
                      class="w-9 h-9 rounded-full bg-white/90 flex items-center justify-center text-red-600 hover:bg-white hover:scale-110 transition-all shadow-md"
                      title="Delete Post">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-5 flex flex-col flex-1">
            <div class="flex-1">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-orange-700 transition-colors">
                  <?php echo htmlspecialchars($row['blog_title']); ?>
                </h3>
              </div>
              <?php if (!empty($row['blog_excerpt'])): ?>
                <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                  <?php echo htmlspecialchars($row['blog_excerpt']); ?>
                </p>
              <?php endif; ?>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-orange-700 text-xs font-bold">
                    <?php echo strtoupper(substr($row['blog_author'] ?? 'A', 0, 1)); ?>
                  </span>
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-medium text-gray-700 truncate"><?php echo htmlspecialchars($row['blog_author'] ?? 'Unknown'); ?></p>
                  <p class="text-xs text-gray-400">
                    <?php echo $row['blog_published_date'] ? date('M d, Y', strtotime($row['blog_published_date'])) : date('M d, Y', strtotime($row['blog_created_at'])); ?>
                  </p>
                </div>
              </div>

              <!-- Slug copy button -->
              <button onclick="copySlug('<?php echo htmlspecialchars($row['blog_slug']); ?>')" 
                      class="flex items-center gap-1 text-xs text-gray-400 hover:text-orange-600 transition-colors flex-shrink-0 ml-2"
                      title="Copy slug">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span class="truncate max-w-20"><?php echo htmlspecialchars($row['blog_slug']); ?></span>
              </button>
            </div>
          </div>

          <!-- ID Footer -->
          <div class="px-5 pb-3">
            <span class="text-xs text-gray-300 font-mono">#<?php echo $row['blog_id']; ?></span>
          </div>
        </div>

        <!-- Update Blog Modal -->
        <div id="updateBlogModal<?php echo $row['blog_id']; ?>" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
          <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
              <div>
                <h3 class="text-xl font-bold text-gray-900">Edit Blog Post</h3>
                <p class="text-sm text-gray-500 mt-0.5">ID #<?php echo $row['blog_id']; ?></p>
              </div>
              <button type="button" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors" onclick="closeModal('updateBlogModal<?php echo $row['blog_id']; ?>')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <form action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-5">
              <input type="hidden" name="blog_id" value="<?php echo htmlspecialchars($row['blog_id']); ?>">
              <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($row['blog_featured_image'] ?? ''); ?>">

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                <input type="text" name="blog_title" value="<?php echo htmlspecialchars($row['blog_title']); ?>" required 
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Author <span class="text-red-500">*</span></label>
                  <input type="text" name="blog_author" value="<?php echo htmlspecialchars($row['blog_author'] ?? ''); ?>" required 
                         class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                  <select name="blog_status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 bg-white">
                    <option value="draft" <?php echo ($row['blog_status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($row['blog_status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                  </select>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Excerpt <span class="text-red-500">*</span></label>
                <textarea name="blog_excerpt" rows="2" required 
                          class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none"><?php echo htmlspecialchars($row['blog_excerpt'] ?? ''); ?></textarea>
              </div>
              
              <?php if (!empty($row['blog_featured_image'])): ?>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Featured Image</label>
                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
                  <img src="<?php echo htmlspecialchars($row['blog_featured_image']); ?>" alt="Current" class="w-20 h-20 object-cover rounded-lg border">
                  <div>
                    <p class="text-xs text-gray-500 mb-2">Current image preview</p>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" name="remove_current_image" id="remove_image_<?php echo $row['blog_id']; ?>" value="1" 
                             class="w-4 h-4 rounded text-red-500 border-gray-300 focus:ring-red-400">
                      <span class="text-sm text-red-600 font-medium">Remove this image</span>
                    </label>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  <?php echo !empty($row['blog_featured_image']) ? 'Replace Featured Image' : 'Featured Image'; ?>
                </label>
                <div class="relative">
                  <input type="file" name="blog_featured_image" accept="image/jpeg,image/png,image/webp,image/gif"
                         class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                </div>
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, GIF · Max 5MB</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Content <span class="text-red-500">*</span></label>
                <textarea name="blog_content" id="editor_<?php echo $row['blog_id']; ?>" rows="10" required 
                          class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm"><?php echo htmlspecialchars($row['blog_content']); ?></textarea>
              </div>
              
              <details class="group border border-gray-200 rounded-xl overflow-hidden">
                <summary class="flex items-center justify-between px-4 py-3 cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors text-sm font-medium text-gray-700">
                  SEO Settings
                  <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </summary>
                <div class="p-4 space-y-4 border-t border-gray-100">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Title</label>
                    <input type="text" name="blog_meta_title" value="<?php echo htmlspecialchars($row['blog_meta_title'] ?? ''); ?>"
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Description</label>
                    <textarea name="blog_meta_description" rows="2" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400 resize-none"><?php echo htmlspecialchars($row['blog_meta_description'] ?? ''); ?></textarea>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Keywords</label>
                    <input type="text" name="blog_meta_keywords" value="<?php echo htmlspecialchars($row['blog_meta_keywords'] ?? ''); ?>"
                           placeholder="fish, seafood, brokerage"
                           class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                  </div>
                </div>
              </details>

              <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('updateBlogModal<?php echo $row['blog_id']; ?>')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                  Cancel
                </button>
                <button type="submit" name="update_blog"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-orange-600 rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Delete Blog Modal -->
        <div id="deleteBlogModal<?php echo $row['blog_id']; ?>" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
          <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div class="text-center mb-5">
              <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </div>
              <h3 class="text-lg font-bold text-gray-900">Delete Post?</h3>
              <p class="text-sm text-gray-500 mt-1">This action cannot be undone.</p>
            </div>

            <?php if (!empty($row['blog_featured_image'])): ?>
            <div class="mb-4 flex justify-center">
              <img src="<?php echo htmlspecialchars($row['blog_featured_image']); ?>" alt="" class="w-24 h-24 object-cover rounded-xl border border-gray-200">
            </div>
            <?php endif; ?>
            
            <div class="bg-red-50 border border-red-100 rounded-xl px-4 py-3 mb-5 text-center">
              <p class="text-sm font-semibold text-red-800 line-clamp-2">"<?php echo htmlspecialchars($row['blog_title']); ?>"</p>
            </div>
            
            <form action="./functions/delete.php" method="POST">
              <input type="hidden" name="blog_id" value="<?php echo $row['blog_id']; ?>">
              <input type="hidden" name="blog_image" value="<?php echo htmlspecialchars($row['blog_featured_image'] ?? ''); ?>">
              
              <div class="flex gap-3">
                <button type="button" onclick="closeModal('deleteBlogModal<?php echo $row['blog_id']; ?>')"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                  Cancel
                </button>
                <button type="submit" name="delete_blog"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 active:scale-95 transition-all">
                  Delete
                </button>
              </div>
            </form>
          </div>
        </div>

      <?php endwhile; ?>
    </div>

    <!-- List View (hidden by default) -->
    <div id="postsList" class="hidden bg-white border border-gray-200 rounded-2xl overflow-hidden posts-container">
      <?php
        // Re-run query for list view
        $result2 = mysqli_query($conn, "SELECT * FROM blogs ORDER BY blog_created_at DESC LIMIT $offset, $recordsPerPage");
        while ($row2 = mysqli_fetch_assoc($result2)):
      ?>
      <div class="list-item flex items-center gap-4 px-5 py-4 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors" data-status="<?php echo $row2['blog_status']; ?>">
        <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
          <?php if (!empty($row2['blog_featured_image'])): ?>
            <img src="<?php echo htmlspecialchars($row2['blog_featured_image']); ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
              </svg>
            </div>
          <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-0.5">
            <?php if ($row2['blog_status'] === 'published'): ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Published</span>
            <?php else: ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Draft</span>
            <?php endif; ?>
            <span class="text-xs text-gray-400 font-mono">#<?php echo $row2['blog_id']; ?></span>
          </div>
          <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($row2['blog_title']); ?></p>
          <p class="text-xs text-gray-400">
            By <?php echo htmlspecialchars($row2['blog_author'] ?? 'Unknown'); ?> · 
            <?php echo $row2['blog_published_date'] ? date('M d, Y', strtotime($row2['blog_published_date'])) : date('M d, Y', strtotime($row2['blog_created_at'])); ?>
          </p>
        </div>
        <div class="flex items-center gap-1 flex-shrink-0">
          <a href="/sjfbi-js/blogs/<?php echo $row2['blog_slug']; ?>" target="_blank"
             class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="View">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              <polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
          </a>
          <button onclick="openEditModal(<?php echo $row2['blog_id']; ?>)" class="p-2 text-orange-500 hover:bg-orange-50 rounded-lg transition-colors" title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
            </svg>
          </button>
          <button onclick="openDeleteModal(<?php echo $row2['blog_id']; ?>, '<?php echo htmlspecialchars(addslashes($row2['blog_title'])); ?>')" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

  <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white border-2 border-dashed border-gray-200 rounded-2xl py-20 text-center">
      <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="12" y1="18" x2="12" y2="12"></line>
          <line x1="9" y1="15" x2="15" y2="15"></line>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-800 mb-1">No blog posts yet</h3>
      <p class="text-sm text-gray-500 mb-6">Share your story — create your first blog post.</p>
      <button onclick="openModal('addBlogModal')" class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700 transition-colors shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Write First Post
      </button>
    </div>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="flex items-center justify-between px-1">
    <p class="text-sm text-gray-500">
      Showing <span class="font-semibold text-gray-800"><?php echo $offset + 1; ?>–<?php echo min($offset + $recordsPerPage, $totalItems); ?></span> of <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> posts
    </p>
    <div class="flex items-center gap-1.5">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
          Prev
        </a>
      <?php else: ?>
        <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
          Prev
        </span>
      <?php endif; ?>
      
      <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'; ?> w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>
      </div>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
          Next
          <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      <?php else: ?>
        <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
          Next
          <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </span>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-5 right-5 z-[9999] hidden">
  <div class="bg-gray-900 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span id="toastMsg">Copied!</span>
  </div>
</div>

<style>
  .blog-card { animation: fadeInUp 0.4s ease both; }
  .blog-card:nth-child(1) { animation-delay: 0.05s; }
  .blog-card:nth-child(2) { animation-delay: 0.1s; }
  .blog-card:nth-child(3) { animation-delay: 0.15s; }
  .blog-card:nth-child(4) { animation-delay: 0.2s; }
  .blog-card:nth-child(5) { animation-delay: 0.25s; }
  .blog-card:nth-child(6) { animation-delay: 0.3s; }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .blog-card.hidden-card { display: none; }
  .list-item.hidden-card { display: none; }

  .view-btn.active {
    background: white;
    color: #ea580c;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  #toast.show { display: block; animation: slideInRight 0.3s ease; }
  @keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
  }

  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<script>
  let currentView = 'grid';

  function setView(view) {
    currentView = view;
    const grid = document.getElementById('postsGrid');
    const list = document.getElementById('postsList');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');

    if (view === 'grid') {
      grid && grid.classList.remove('hidden');
      list && list.classList.add('hidden');
      gridBtn.classList.add('active');
      listBtn.classList.remove('active');
    } else {
      list && list.classList.remove('hidden');
      grid && grid.classList.add('hidden');
      listBtn.classList.add('active');
      gridBtn.classList.remove('active');
    }
  }

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

  function copySlug(slug) {
    navigator.clipboard.writeText(slug).then(() => showToast('Slug copied: ' + slug));
  }

  function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  function openEditModal(blogId) {
    openModal('updateBlogModal' + blogId);

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

    // Validate + sync edit form before submit
    const form = document.querySelector('#updateBlogModal' + blogId + ' form');
    if (form && !form.dataset.listenerAdded) {
      form.dataset.listenerAdded = 'true';
      form.addEventListener('submit', function() {
        if (tinymce.get(editorId)) {
          tinymce.get(editorId).save();
        }
      });
    }
  }

  function openDeleteModal(blogId, blogTitle) {
    document.getElementById('deleteBlogModal' + blogId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = '';
  }

  window.addEventListener('click', function(e) {
    if (e.target.id && e.target.id.startsWith('updateBlogModal') && e.target.classList.contains('fixed')) {
      closeModal(e.target.id);
    }
    if (e.target.id && e.target.id.startsWith('deleteBlogModal') && e.target.classList.contains('fixed')) {
      closeModal(e.target.id);
    }
  });
</script>