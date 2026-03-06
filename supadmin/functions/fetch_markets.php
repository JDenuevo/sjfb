<?php
session_start();
require '../../conn.php';

if (!isset($_GET['market_id'])) {
    die('<p class="text-red-500">No market ID provided</p>');
}

$market_id = intval($_GET['market_id']);

// Get market details
$stmt = $conn->prepare("SELECT * FROM markets WHERE market_id = ?");
$stmt->bind_param("i", $market_id);
$stmt->execute();
$market = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$market) {
    die('<p class="text-red-500">Market not found</p>');
}

// Get team members
$stmt = $conn->prepare("SELECT * FROM market_members WHERE market_id = ? AND is_active = 1 ORDER BY display_order");
$stmt->bind_param("i", $market_id);
$stmt->execute();
$members = $stmt->get_result();
$stmt->close();

// Get linked products
$stmt = $conn->prepare("
    SELECT mp.*, p.product_name, pv.variant_price
    FROM market_products mp
    JOIN products p ON mp.product_id = p.product_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE mp.market_id = ?
    GROUP BY mp.id
    ORDER BY mp.display_order
");
$stmt->bind_param("i", $market_id);
$stmt->execute();
$linked_products = $stmt->get_result();
$stmt->close();

// All products for dropdown
$all_products = $conn->query("
    SELECT p.product_id, p.product_name, pv.variant_price
    FROM products p
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.is_deleted = 0
    GROUP BY p.product_id
    ORDER BY p.product_name
");
?>

<form action="./functions/update.php" method="POST" enctype="multipart/form-data" id="editMarketForm">
    <div class="px-6 py-5 space-y-1">
        <input type="hidden" name="market_id" value="<?= $market['market_id'] ?>">

        <!-- ── Basic Information ─────────────────────────────────────────────── -->
        <p class="section-title">Basic Information</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Market Key</label>
                <input type="text" name="market_key" value="<?= htmlspecialchars($market['market_key']) ?>"
                       class="form-input bg-gray-50" readonly>
                <p class="text-xs text-gray-400 mt-1">Cannot be changed</p>
            </div>
            <div>
                <label class="form-label">Market Name <span class="text-red-500">*</span></label>
                <input type="text" name="market_name" value="<?= htmlspecialchars($market['market_name']) ?>"
                       required class="form-input">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <div>
                <label class="form-label">Location Short <span class="text-red-500">*</span></label>
                <input type="text" name="location_short" value="<?= htmlspecialchars($market['location_short']) ?>"
                       required class="form-input">
            </div>
            <div>
                <label class="form-label">Stall Count <span class="text-red-500">*</span></label>
                <input type="number" name="stall_count" value="<?= $market['stall_count'] ?>"
                       required class="form-input">
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label">Location Full <span class="text-red-500">*</span></label>
            <input type="text" name="location_full" value="<?= htmlspecialchars($market['location_full']) ?>"
                   required class="form-input">
        </div>

        <div class="mt-3">
            <label class="form-label">Description <span class="text-red-500">*</span></label>
            <textarea name="description" rows="3" required class="form-input"><?= htmlspecialchars($market['description']) ?></textarea>
        </div>

        <div class="mt-3">
            <label class="form-label">Highlights <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-2">One highlight per line</p>
            <?php
            $highlights      = json_decode($market['highlights'], true);
            $highlights_text = is_array($highlights) ? implode("\n", $highlights) : '';
            ?>
            <textarea name="highlights" rows="4" required class="form-input"><?= htmlspecialchars($highlights_text) ?></textarea>
        </div>

        <!-- ── Media ──────────────────────────────────────────────────────────── -->
        <p class="section-title">Media</p>

        <!-- Current main image -->
        <?php if (!empty($market['main_image'])): ?>
        <div class="mt-3" id="mainImageWrapper">
            <label class="form-label">Current Main Image</label>
            <div class="image-thumb" style="width:8rem;">
                <img src="../uploads/markets/<?= htmlspecialchars($market['main_image']) ?>"
                     alt="Main" style="height:8rem; border-radius:0.75rem; border:1px solid #e5e7eb;">
                <button type="button" class="del-btn"
                        data-action="delete_market_image"
                        data-market-id="<?= $market['market_id'] ?>"
                        data-image-type="main"
                        data-target="mainImageWrapper"
                        title="Delete main image">×</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Replace / add main image — FILE UPLOAD, not URL -->
        <div class="mt-3">
            <label class="form-label"><?= !empty($market['main_image']) ? 'Replace Main Image' : 'Main Image' ?></label>
            <input type="file" id="editMainImageFile" name="main_image" accept="image/*" class="hidden"
                   onchange="previewEditMainImage(this)">
            <button type="button" onclick="document.getElementById('editMainImageFile').click()"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
                📸 <?= !empty($market['main_image']) ? 'Upload New Main Image' : 'Select Main Image' ?>
            </button>
            <div id="editMainImagePreview" class="mt-3 hidden">
                <img src="" alt="New main image preview" class="w-full h-40 object-cover rounded-lg">
            </div>
        </div>

        <!-- Current gallery -->
        <?php if (!empty($market['gallery_images'])):
            $gallery = json_decode($market['gallery_images'], true);
            if (!empty($gallery)):
        ?>
        <div class="mt-3">
            <label class="form-label">Current Gallery</label>
            <div class="grid grid-cols-4 gap-2" id="currentGalleryGrid">
                <?php foreach ($gallery as $img): ?>
                <div class="image-thumb">
                    <img src="../uploads/markets/<?= htmlspecialchars($img) ?>"
                         alt="Gallery">
                    <button type="button" class="del-btn"
                            data-action="delete_market_image"
                            data-market-id="<?= $market['market_id'] ?>"
                            data-image-type="gallery"
                            data-image="<?= htmlspecialchars($img) ?>"
                            title="Delete gallery image">×</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; endif; ?>

        <!-- Add new gallery images — FILE UPLOAD, not URL -->
        <div class="mt-3">
            <label class="form-label">Add New Gallery Images</label>
            <input type="file" id="editGalleryFiles" name="gallery_images[]" multiple accept="image/*" class="hidden"
                   onchange="previewEditGallery(this)">
            <button type="button" onclick="document.getElementById('editGalleryFiles').click()"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-orange-400 hover:text-orange-500 transition-colors">
                📸 Select Gallery Images (multiple)
            </button>
            <div id="editGalleryPreview" class="grid grid-cols-4 gap-2 mt-3"></div>
        </div>

        <div class="mt-3">
            <label class="form-label">Map Embed URL</label>
            <input type="text" name="map_embed" value="<?= htmlspecialchars($market['map_embed'] ?? '') ?>"
                   class="form-input" placeholder="Google Maps embed iframe src">
        </div>

        <!-- ── Styling ────────────────────────────────────────────────────────── -->
        <p class="section-title">Styling</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="form-label">Accent Color</label>
                <input type="color" name="accent_color" value="<?= htmlspecialchars($market['accent_color'] ?? '#f97316') ?>"
                       class="form-input h-10">
            </div>
            <div>
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" value="<?= intval($market['display_order'] ?? 0) ?>"
                       class="form-input">
            </div>
        </div>

        <!-- ── Team Members ───────────────────────────────────────────────────── -->
        <p class="section-title">Team Members</p>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Member photos are saved to <code>uploads/members/</code></p>

        <div id="members-container">
            <?php if ($members && $members->num_rows > 0):
                while ($member = $members->fetch_assoc()): ?>
            <div class="member-row" data-member-id="<?= $member['member_id'] ?>">
                <input type="hidden" name="member_id[]" value="<?= $member['member_id'] ?>">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label">Name</label>
                        <input type="text" name="member_name[]"
                               value="<?= htmlspecialchars($member['name']) ?>"
                               class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Position</label>
                        <input type="text" name="member_position[]"
                               value="<?= htmlspecialchars($member['position']) ?>"
                               class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Display Order</label>
                        <input type="number" name="member_order[]"
                               value="<?= $member['display_order'] ?>"
                               class="form-input">
                    </div>
                    <!-- FIXED: file upload for member photo, not text URL -->
                    <div>
                        <label class="form-label">Replace Photo</label>
                        <input type="file" name="member_image_file[<?= $member['member_id'] ?>]"
                               accept="image/*" class="form-input text-xs py-1.5">
                        <?php if (!empty($member['image_url'])): ?>
                        <div class="flex items-center gap-2 mt-1">
                            <img src="../uploads/members/<?= htmlspecialchars($member['image_url']) ?>"
                                 alt="" class="size-8 rounded-full object-cover border border-gray-200">
                            <span class="text-xs text-gray-400">Current photo</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-end col-span-2">
                        <button type="button"
                                class="btn-danger delete-member-btn"
                                data-member-id="<?= $member['member_id'] ?>">Delete Member</button>
                    </div>
                </div>
            </div>
            <?php endwhile;
            else: ?>
            <p class="text-gray-400 text-sm py-2">No team members yet. Add one below.</p>
            <?php endif; ?>
        </div>

        <button type="button" id="add-member-btn" class="btn-success mt-2">+ Add Team Member</button>

        <!-- ── Products ───────────────────────────────────────────────────────── -->
        <p class="section-title mt-5">Products</p>

        <div id="products-container">
            <?php if ($linked_products && $linked_products->num_rows > 0):
                while ($product = $linked_products->fetch_assoc()): ?>
            <div class="product-row">
                <input type="hidden" name="product_link_id[]" value="<?= $product['id'] ?>">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label">Product</label>
                        <input type="text"
                               value="<?= htmlspecialchars($product['product_name']) ?> — ₱<?= number_format($product['variant_price'] ?? 0, 2) ?>"
                               class="form-input bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="form-label">Display Order</label>
                        <input type="number" name="product_order[]" value="<?= $product['display_order'] ?>"
                               class="form-input">
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="btn-danger delete-product-link-btn"
                                data-link-id="<?= $product['id'] ?>">Remove</button>
                    </div>
                </div>
            </div>
            <?php endwhile;
            else: ?>
            <p class="text-gray-400 text-sm py-2">No products linked yet.</p>
            <?php endif; ?>
        </div>

        <div class="mt-3">
            <label class="form-label">Add New Products</label>
            <select name="new_product_ids[]" multiple class="form-input" size="5">
                <?php if ($all_products && $all_products->num_rows > 0):
                    while ($p = $all_products->fetch_assoc()): ?>
                <option value="<?= $p['product_id'] ?>">
                    <?= htmlspecialchars($p['product_name']) ?> — ₱<?= number_format($p['variant_price'] ?? 0, 2) ?>
                </option>
                <?php endwhile; endif; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd + click to select multiple</p>
        </div>

        <div class="mt-3">
            <label class="form-label">New Products Display Order (start)</label>
            <input type="number" name="new_products_order" value="0" class="form-input">
        </div>
    </div><!-- /px-6 py-5 -->

    <div class="modal-footer">
        <button type="button" onclick="closeModal('editMarketModal')" class="btn-secondary">Cancel</button>
        <button type="submit" name="update_market" class="btn-primary">Update Market</button>
    </div>
</form>

<script>
/* ── Image previews ─────────────────────────────────────────────────────────── */
function previewEditMainImage(input) {
    const preview = document.getElementById('editMainImagePreview');
    const img     = preview.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}

let editGalleryFiles = [];
function previewEditGallery(input) {
    const newFiles = Array.from(input.files);
    editGalleryFiles.push(...newFiles);
    renderEditGalleryPreview();
}
function renderEditGalleryPreview() {
    const container = document.getElementById('editGalleryPreview');
    container.innerHTML = '';
    editGalleryFiles.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'image-thumb';
            div.innerHTML = `<img src="${e.target.result}" alt=""><button type="button" class="del-btn edit-gallery-del" data-index="${i}">×</button>`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    // Sync the file input
    const dt = new DataTransfer();
    editGalleryFiles.forEach(f => dt.items.add(f));
    document.getElementById('editGalleryFiles').files = dt.files;
}
document.getElementById('editGalleryPreview').addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-gallery-del')) {
        editGalleryFiles.splice(parseInt(e.target.dataset.index), 1);
        renderEditGalleryPreview();
    }
});

/* ── Delete existing market images — unified handler ────────────────────────── */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action="delete_market_image"]');
    if (!btn) return;

    const type     = btn.dataset.imageType;
    const marketId = btn.dataset.marketId;
    const label    = type === 'main' ? 'main image' : 'gallery image';

    if (!confirm('Delete this ' + label + '? This cannot be undone.')) return;

    /* Visual feedback while request is in-flight */
    const original = btn.textContent;
    btn.textContent  = '…';
    btn.disabled     = true;
    btn.style.opacity = '1'; // keep visible during loading

    let body = 'action=delete_market_image&market_id=' + marketId + '&image_type=' + type;
    if (type === 'gallery') body += '&image=' + encodeURIComponent(btn.dataset.image);

    fetch('./functions/delete.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (type === 'main') {
                /* Remove the whole "Current Main Image" wrapper */
                const wrapper = document.getElementById(btn.dataset.target);
                if (wrapper) {
                    wrapper.style.transition = 'opacity 0.2s';
                    wrapper.style.opacity    = '0';
                    setTimeout(() => wrapper.remove(), 200);
                }
            } else {
                /* Remove just this gallery tile */
                const thumb = btn.closest('.image-thumb');
                if (thumb) {
                    thumb.style.transition = 'opacity 0.2s, transform 0.2s';
                    thumb.style.opacity    = '0';
                    thumb.style.transform  = 'scale(0.85)';
                    setTimeout(() => {
                        thumb.remove();
                        /* Hide the gallery label row if no tiles left */
                        const grid = document.getElementById('currentGalleryGrid');
                        if (grid && grid.children.length === 0) {
                            grid.closest('.mt-3')?.remove();
                        }
                    }, 200);
                }
            }
        } else {
            btn.textContent  = original;
            btn.disabled     = false;
            btn.style.opacity = '';
            alert('Failed to delete: ' + (d.message || 'Unknown error'));
        }
    })
    .catch(() => {
        btn.textContent  = original;
        btn.disabled     = false;
        btn.style.opacity = '';
        alert('Request failed. Please try again.');
    });
});

/* ── Add / remove team member rows ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const memberContainer = document.getElementById('members-container');
    let newMemberIndex    = 0;

    document.getElementById('add-member-btn').addEventListener('click', function () {
        const idx = newMemberIndex++;
        /* FIXED: uses file input, not text URL */
        memberContainer.insertAdjacentHTML('beforeend', `
            <div class="member-row new-member-row" data-new-index="${idx}">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="form-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="new_member_name[]" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Position <span class="text-red-500">*</span></label>
                        <input type="text" name="new_member_position[]" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Display Order</label>
                        <input type="number" name="new_member_order[]" value="0" class="form-input">
                    </div>
                    <!-- FIXED: file upload for new member photo -->
                    <div>
                        <label class="form-label">Photo</label>
                        <input type="file" name="new_member_image_file[]" accept="image/*"
                               class="form-input text-xs py-1.5">
                        <p class="text-xs text-gray-400 mt-1">Saved to uploads/members/</p>
                    </div>
                    <div class="flex items-end col-span-2">
                        <button type="button" class="btn-danger remove-new-member">Remove</button>
                    </div>
                </div>
            </div>
        `);
    });

    memberContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-new-member')) {
            e.target.closest('.member-row').remove();
        }
    });

    /* Delete existing member via AJAX */
    document.querySelectorAll('.delete-member-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this team member permanently?')) return;
            const memberId = this.dataset.memberId;
            const row      = this.closest('.member-row');
            fetch('./functions/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'action=delete_market_member&member_id=' + memberId
            }).then(r => r.json()).then(d => { if (d.success) row.remove(); else alert('Failed: ' + d.message); });
        });
    });

    /* Delete existing product link via AJAX */
    document.querySelectorAll('.delete-product-link-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Remove this product from market?')) return;
            const linkId = this.dataset.linkId;
            const row    = this.closest('.product-row');
            fetch('./functions/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'action=delete_market_product&link_id=' + linkId
            }).then(r => r.json()).then(d => { if (d.success) row.remove(); else alert('Failed: ' + d.message); });
        });
    });
});
</script>