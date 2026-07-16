<?php
session_start();
include '../../conn.php';
include 'slug_helper.php';

if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    header("Location: ../index.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (!$event_id) {
    echo '<p class="text-red-500 p-4 text-center">Invalid event ID.</p>';
    exit;
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM company_events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    echo '<p class="text-red-500 p-4 text-center">Event not found.</p>';
    exit;
}

$eventCategories = [
  'Company Celebration',
  'Team Engagement',
  'Business Review',
  'Leadership Program',
  'Seminar & Training',
  'External / Industry',
];
?>

<form id="editEventForm" action="./functions/update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880" />

    <p class="section-title">Basic Information</p>

    <div>
        <label class="form-label">Event Title <span class="text-red-500">*</span></label>
        <input type="text" name="event_title" required
               value="<?= htmlspecialchars($event['event_title'] ?? '') ?>"
               class="form-input" placeholder="e.g. Annual Company Town Hall 2026">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">Category <span class="text-red-500">*</span></label>
            <select name="event_category" required class="form-input">
                <option value="">Select category</option>
                <?php foreach ($eventCategories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($event['event_category'] ?? '') === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Status</label>
            <select name="event_status" class="form-input">
                <option value="draft" <?= ($event['event_status'] ?? '') == 'draft' ? 'selected' : '' ?>>📝 Draft</option>
                <option value="published" <?= ($event['event_status'] ?? '') == 'published' ? 'selected' : '' ?>>✅ Published</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">Start Date <span class="text-red-500">*</span></label>
            <input type="date" name="event_date" required
                   value="<?= htmlspecialchars($event['event_date'] ?? '') ?>"
                   class="form-input">
        </div>
        <div>
            <label class="form-label">End Date</label>
            <input type="date" name="event_end_date"
                   value="<?= htmlspecialchars($event['event_end_date'] ?? '') ?>"
                   class="form-input">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">Time</label>
            <input type="text" name="event_time"
                   value="<?= htmlspecialchars($event['event_time'] ?? '') ?>"
                   class="form-input" placeholder="e.g. 2:00 PM – 4:00 PM">
        </div>
        <div>
            <label class="form-label">Audience</label>
            <input type="text" name="event_audience"
                   value="<?= htmlspecialchars($event['event_audience'] ?? '') ?>"
                   class="form-input" placeholder="e.g. All Employees, Managers Only">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">Location</label>
            <input type="text" name="event_location"
                   value="<?= htmlspecialchars($event['event_location'] ?? '') ?>"
                   class="form-input" placeholder="e.g. Navotas Main Office">
        </div>
        <div>
            <label class="form-label">Full Address</label>
            <input type="text" name="event_address"
                   value="<?= htmlspecialchars($event['event_address'] ?? '') ?>"
                   class="form-input" placeholder="Street, City, Province">
        </div>
    </div>

    <p class="section-title">RSVP</p>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="form-label">RSVP / Registration Link</label>
            <input type="text" name="event_rsvp_url"
                   value="<?= htmlspecialchars($event['event_rsvp_url'] ?? '') ?>"
                   class="form-input" placeholder="/contact or external URL">
        </div>
        <div>
            <label class="form-label">RSVP Deadline</label>
            <input type="date" name="event_rsvp_deadline"
                   value="<?= htmlspecialchars($event['event_rsvp_deadline'] ?? '') ?>"
                   class="form-input">
        </div>
    </div>

    <p class="section-title">Content</p>

    <div>
        <label class="form-label">Excerpt <span class="text-red-500">*</span></label>
        <textarea name="event_excerpt" rows="2" required
                  class="form-input resize-none" placeholder="A short summary of the event..."><?= htmlspecialchars($event['event_excerpt'] ?? '') ?></textarea>
    </div>

    <p class="section-title">Media</p>

    <!-- Current Featured Image -->
    <?php if (!empty($event['event_image'])): ?>
    <div>
        <label class="form-label">Current Featured Image</label>
        <div class="relative inline-block">
            <img src="<?= htmlspecialchars($event['event_image']) ?>"
                 class="w-32 h-32 object-cover rounded-lg border border-gray-200" alt="Featured image">
            <button type="button" onclick="if(confirm('Delete this image?')) deleteEventImage(<?= $event['event_id'] ?>)"
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
        <input type="file" name="event_image" accept="image/jpeg,image/png,image/webp,image/gif"
               class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
        <p class="text-xs text-gray-400 mt-1">Leave empty to keep current image. JPG, PNG, WebP, GIF · Max 5MB</p>
    </div>

    <div>
        <label class="form-label">Full Content <span class="text-red-500">*</span></label>
        <textarea name="event_content" id="edit_editor" rows="15" class="w-full"><?= htmlspecialchars($event['event_content'] ?? '') ?></textarea>
    </div>

    <details class="group border border-gray-200 rounded-xl overflow-hidden" <?= (!empty($event['event_meta_title']) || !empty($event['event_meta_description']) || !empty($event['event_meta_keywords'])) ? 'open' : '' ?>>
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
                <input type="text" name="event_meta_title"
                       value="<?= htmlspecialchars($event['event_meta_title'] ?? '') ?>"
                       class="form-input" placeholder="Leave empty to use event title">
            </div>
            <div>
                <label class="form-label">Meta Description</label>
                <textarea name="event_meta_description" rows="2"
                          class="form-input resize-none" placeholder="Leave empty to use excerpt"><?= htmlspecialchars($event['event_meta_description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="event_meta_keywords"
                       value="<?= htmlspecialchars($event['event_meta_keywords'] ?? '') ?>"
                       class="form-input" placeholder="town hall, company event, fish brokerage">
            </div>
        </div>
    </details>

    <div class="modal-footer" style="margin:1.5rem -1.5rem -1.5rem;">
        <button type="button" onclick="closeModal('editEventModal')" class="btn-secondary">Cancel</button>
        <button type="submit" name="update_event" class="btn-primary">Update Event</button>
    </div>
</form>