<div class="space-y-6">

  <!-- Stats Row - Redesigned to match products.php style -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <?php
      $statsQuery = "SELECT blog_status, COUNT(*) as cnt FROM blogs GROUP BY blog_status";
      $statsResult = mysqli_query($conn, $statsQuery);
      $statusCounts = ['published' => 0, 'draft' => 0];
      while ($sr = mysqli_fetch_assoc($statsResult)) {
        $statusCounts[$sr['blog_status']] = $sr['cnt'];
      }
    ?>
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 font-medium">Total Posts</p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $totalItems; ?></p>
        </div>
        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 text-xl">📄</div>
      </div>
    </div>
    
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 font-medium">Published</p>
          <p class="text-2xl font-bold text-green-600 mt-1"><?php echo $statusCounts['published']; ?></p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl">✅</div>
      </div>
    </div>
    
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 font-medium">Drafts</p>
          <p class="text-2xl font-bold text-yellow-600 mt-1"><?php echo $statusCounts['draft']; ?></p>
        </div>
        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 text-xl">📝</div>
      </div>
    </div>
  </div>

  <!-- Header Bar -->
  <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Blog Posts</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          <?php echo $totalItems; ?> post<?php echo $totalItems !== 1 ? 's' : ''; ?> total
        </p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Filter -->
        <select id="statusFilter" onchange="filterPosts(this.value)" 
                class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none w-48">
          <option value="all">All Posts</option>
          <option value="published">Published</option>
          <option value="draft">Drafts</option>
        </select>
        <!-- Add Button -->
        <button onclick="openModal('addBlogModal')" 
                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-xl hover:bg-orange-700 active:scale-95 transition-all shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M5 12h14"/><path d="M12 5v14"/>
          </svg>
          New Post
        </button>
      </div>
    </div>

    <!-- Blog Posts List -->
    <?php if (mysqli_num_rows($result) > 0): ?>
      <div class="divide-y divide-gray-100">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="list-item flex items-center gap-4 px-6 py-4 hover:bg-orange-50/40 transition-colors blog-row" data-status="<?php echo $row['blog_status']; ?>">
          <!-- Featured Image -->
          <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0 border border-gray-200">
            <?php if (!empty($row['blog_featured_image'])): ?>
              <img src="<?php echo htmlspecialchars($row['blog_featured_image']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center bg-orange-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                  <circle cx="8.5" cy="8.5" r="1.5"></circle>
                  <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <?php if ($row['blog_status'] === 'published'): ?>
                <span class="badge badge-green flex items-center gap-1">
                  <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                  Published
                </span>
              <?php else: ?>
                <span class="badge badge-yellow flex items-center gap-1">
                  <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                  Draft
                </span>
              <?php endif; ?>
              <span class="text-xs text-gray-400">ID: #<?php echo $row['blog_id']; ?></span>
            </div>
            <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($row['blog_title']); ?></p>
            <p class="text-xs text-gray-500">
              By <?php echo htmlspecialchars($row['blog_author'] ?? 'Unknown'); ?> · 
              <?php echo $row['blog_published_date'] ? date('M d, Y', strtotime($row['blog_published_date'])) : date('M d, Y', strtotime($row['blog_created_at'])); ?>
            </p>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center gap-1 flex-shrink-0">
            <a href="/sjfbi-js/blogs/<?php echo $row['blog_slug']; ?>" target="_blank"
               class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>
              </svg>
            </a>
            <button onclick="openEditModal(<?php echo $row['blog_id']; ?>)" 
                    class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors" title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
              </svg>
            </button>
            <button onclick="openDeleteModal(<?php echo $row['blog_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['blog_title'])); ?>')" 
                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
      <div class="px-6 py-16 text-center">
        <div class="flex flex-col items-center gap-3">
          <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="1.5">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="18" x2="12" y2="12"></line>
              <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
          </div>
          <p class="text-sm font-semibold text-gray-700">No blog posts yet</p>
          <p class="text-xs text-gray-400">Click "New Post" to create your first blog post.</p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Pagination (matching products.php style) -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100">
      <p class="text-sm text-gray-500">
        Showing <span class="font-semibold text-gray-800"><?php echo $offset + 1; ?>–<?php echo min($offset + $recordsPerPage, $totalItems); ?></span> 
        of <span class="font-semibold text-gray-800"><?php echo $totalItems; ?></span> posts
      </p>
      
      <div class="flex items-center gap-1.5">
        <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?>" 
             class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
          </a>
        <?php else: ?>
          <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Prev
          </span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1) {
          echo '<a href="?page=1" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">1</a>';
        }
        if ($start > 2) echo '<span class="text-gray-400 px-1">…</span>';
        
        for ($i = $start; $i <= $end; $i++):
        ?>
          <a href="?page=<?= $i ?>" 
             class="w-9 h-9 flex items-center justify-center text-sm font-medium rounded-xl border transition-colors
             <?= $i == $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
            <?= $i ?>
          </a>
        <?php
        endfor;
        
        if ($end < $totalPages - 1) echo '<span class="text-gray-400 px-1">…</span>';
        if ($end < $totalPages) {
          echo '<a href="?page='.$totalPages.'" class="w-9 h-9 flex items-center justify-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50">'.$totalPages.'</a>';
        }
        ?>

        <?php if ($page < $totalPages): ?>
          <a href="?page=<?php echo $page + 1; ?>" 
             class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
            Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </a>
        <?php else: ?>
          <span class="px-3 py-1.5 text-sm text-gray-300 bg-gray-50 border border-gray-100 rounded-xl cursor-not-allowed flex items-center gap-1">
            Next<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
  .hidden-card { display: none; }
</style>