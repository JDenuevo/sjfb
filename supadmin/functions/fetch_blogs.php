<?php
session_start();
include '../../conn.php';
include 'slug_helper.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    header("Location: ../index.php");
    exit;
}

$blog_id = isset($_GET['blog_id']) ? (int)$_GET['blog_id'] : 0;

if (!$blog_id) {
    echo '<p class="text-red-500 p-4 text-center">Invalid blog ID.</p>';
    exit;
}

// Fetch blog details
$stmt = $conn->prepare("SELECT * FROM blogs WHERE blog_id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    echo '<p class="text-red-500 p-4 text-center">Blog post not found.</p>';
    exit;
}
?>

<form id="editBlogForm" action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="blog_id" value="<?= $blog['blog_id'] ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />
    
    <p class="section-title">Basic Information</p>
    
    <div>
        <label class="form-label">Title <span class="text-red-500">*</span></label>
        <input type="text" name="blog_title" required 
               value="<?= htmlspecialchars($blog['blog_title'] ?? '') ?>"
               class="form-input" placeholder="Enter a compelling title...">
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">Author <span class="text-red-500">*</span></label>
            <input type="text" name="blog_author" required 
                   value="<?= htmlspecialchars($blog['blog_author'] ?? '') ?>"
                   class="form-input" placeholder="Author name">
        </div>
        <div>
            <label class="form-label">Status</label>
            <select name="blog_status" class="form-input">
                <option value="draft" <?= ($blog['blog_status'] ?? '') == 'draft' ? 'selected' : '' ?>>📝 Draft</option>
                <option value="published" <?= ($blog['blog_status'] ?? '') == 'published' ? 'selected' : '' ?>>✅ Published</option>
            </select>
        </div>
    </div>
    
    <div>
        <label class="form-label">Excerpt <span class="text-red-500">*</span></label>
        <textarea name="blog_excerpt" rows="2" required 
                  class="form-input resize-none" placeholder="A short summary of the post..."><?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?></textarea>
    </div>
    
    <p class="section-title">Media</p>
    
    <!-- Current Featured Image Section - Make sure this is present -->
    <?php if (!empty($blog['blog_featured_image'])): ?>
    <div>
        <label class="form-label">Current Featured Image</label>
        <div class="relative inline-block">
            <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>" 
                 class="w-32 h-32 object-cover rounded-lg border border-gray-200" alt="Featured image">
            <button type="button" onclick="if(confirm('Delete this image?')) deleteBlogImage(<?= $blog['blog_id'] ?>)" 
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
                ×
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-1">Current image. Upload a new one below to replace it.</p>
    </div>
    <?php endif; ?>
    
    <!-- File input for new image -->
    <div>
        <label class="form-label">New Featured Image (optional)</label>
        <input type="file" name="blog_featured_image" accept="image/jpeg,image/png,image/webp,image/gif"
               class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
        <p class="text-xs text-gray-400 mt-1">Leave empty to keep current image. JPG, PNG, WebP, GIF · Max 5MB</p>
    </div>
    
    <div>
        <label class="form-label">Content <span class="text-red-500">*</span></label>
        <textarea name="blog_content" id="edit_editor" rows="15" class="w-full"><?= htmlspecialchars($blog['blog_content'] ?? '') ?></textarea>
    </div>
    
    <details class="group border border-gray-200 rounded-xl overflow-hidden" <?= (!empty($blog['blog_meta_title']) || !empty($blog['blog_meta_description']) || !empty($blog['blog_meta_keywords'])) ? 'open' : '' ?>>
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
                <input type="text" name="blog_meta_title" 
                       value="<?= htmlspecialchars($blog['blog_meta_title'] ?? '') ?>"
                       class="form-input" placeholder="Leave empty to use post title">
            </div>
            <div>
                <label class="form-label">Meta Description</label>
                <textarea name="blog_meta_description" rows="2" 
                          class="form-input resize-none" placeholder="Leave empty to use excerpt"><?= htmlspecialchars($blog['blog_meta_description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="blog_meta_keywords" 
                       value="<?= htmlspecialchars($blog['blog_meta_keywords'] ?? '') ?>"
                       class="form-input" placeholder="fish, seafood, brokerage">
            </div>
        </div>
    </details>
    
    <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
        <button type="button" onclick="closeModal('editBlogModal')" class="btn-secondary">Cancel</button>
        <button type="submit" name="update_blog" class="btn-primary">Update Post</button>
    </div>
</form>