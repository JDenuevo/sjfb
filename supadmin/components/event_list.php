<?php
// Stats
$statsTotal     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM company_events"))['c'];
$statsPublished = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM company_events WHERE event_status = 'published'"))['c'];
$statsDraft     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM company_events WHERE event_status = 'draft'"))['c'];
$statsUpcoming  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM company_events WHERE event_status = 'published' AND event_date >= CURDATE()"))['c'];

// Map categories to badge colors
$categoryBadgeMap = [
  'Company Celebration' => 'badge-orange',
  'Team Engagement'     => 'badge-teal',
  'Business Review'     => 'badge-blue',
  'Leadership Program'  => 'badge-purple',
  'Seminar & Training'  => 'badge-green',
  'External / Industry' => 'badge-gray',
];
?>

<!-- ══════════════════════════════════════════════
     STATS CARDS
══════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

  <div class="stats-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 mb-1">Total Events</p>
        <p class="text-2xl font-bold text-gray-900"><?= $statsTotal ?></p>
      </div>
      <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
        <svg width="18" height="18" fill="none" stroke="#6b7280" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="stats-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 mb-1">Published</p>
        <p class="text-2xl font-bold text-teal-600"><?= $statsPublished ?></p>
      </div>
      <div class="w-10 h-10 bg-teal-50 rounded-lg flex items-center justify-center">
        <svg width="18" height="18" fill="none" stroke="#0d9488" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="stats-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 mb-1">Drafts</p>
        <p class="text-2xl font-bold text-amber-600"><?= $statsDraft ?></p>
      </div>
      <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
        <svg width="18" height="18" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="stats-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 mb-1">Upcoming</p>
        <p class="text-2xl font-bold text-orange-600"><?= $statsUpcoming ?></p>
      </div>
      <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
        <svg width="18" height="18" fill="none" stroke="#ea580c" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════════════
     EVENT LIST CARD
══════════════════════════════════════════════ -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-5 border-b border-gray-100">
    <div>
      <h2 class="text-lg font-bold text-gray-900">All Events</h2>
      <p class="text-xs text-gray-500 mt-0.5">Manage company events &amp; activities</p>
    </div>
    <button data-modal-target="addEventModal" class="btn-primary flex items-center gap-2">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      New Event
    </button>
  </div>

  <!-- Table (desktop) -->
  <div class="hidden md:block overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Event</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Audience</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php if (mysqli_num_rows($result) > 0): ?>
          <?php while ($event = mysqli_fetch_assoc($result)):
            $badgeClass = $categoryBadgeMap[$event['event_category']] ?? 'badge-gray';
            $statusBadge = $event['event_status'] === 'published' ? 'badge-green' : 'badge-yellow';
            $statusLabel = $event['event_status'] === 'published' ? 'Published' : 'Draft';
            $isUpcoming  = strtotime($event['event_date']) >= strtotime('today');
          ?>
          <tr class="event-row">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <?php if (!empty($event['event_image'])): ?>
                  <img src="<?= htmlspecialchars($event['event_image']) ?>" class="image-thumb" alt="">
                <?php else: ?>
                  <div class="image-thumb bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm">SJ</div>
                <?php endif; ?>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate max-w-[220px]"><?= htmlspecialchars($event['event_title']) ?></p>
                  <p class="text-xs text-gray-400 truncate max-w-[220px]"><?= htmlspecialchars($event['event_location'] ?? '—') ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <?php if (!empty($event['event_category'])): ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($event['event_category']) ?></span>
              <?php else: ?>
                <span class="text-xs text-gray-400">—</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm text-gray-700"><?= date('M d, Y', strtotime($event['event_date'])) ?></p>
              <?php if ($isUpcoming): ?>
                <span class="text-xs text-emerald-600 font-medium">Upcoming</span>
              <?php else: ?>
                <span class="text-xs text-gray-400">Past</span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm text-gray-700 truncate max-w-[160px]"><?= htmlspecialchars($event['event_audience'] ?? '—') ?></p>
            </td>
            <td class="px-5 py-3.5">
              <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex justify-end gap-2">
                <button onclick="openEditModal(<?= $event['event_id'] ?>)"
                        class="btn-secondary !px-3 !py-1.5 text-xs">Edit</button>
                <button onclick="openDeleteModal(<?= $event['event_id'] ?>, '<?= htmlspecialchars(addslashes($event['event_title'])) ?>')"
                        class="btn-danger">Delete</button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="px-5 py-16 text-center">
              <div class="flex flex-col items-center gap-2">
                <span class="inline-flex items-center justify-center size-14 rounded-full bg-orange-50">
                  <svg class="size-7 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </span>
                <p class="text-sm font-semibold text-gray-700">No events yet</p>
                <p class="text-xs text-gray-400">Click "New Event" to create your first one.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Card list (mobile) -->
  <div class="md:hidden divide-y divide-gray-100">
    <?php
    mysqli_data_seek($result, 0);
    if (mysqli_num_rows($result) > 0):
      while ($event = mysqli_fetch_assoc($result)):
        $badgeClass  = $categoryBadgeMap[$event['event_category']] ?? 'badge-gray';
        $statusBadge = $event['event_status'] === 'published' ? 'badge-green' : 'badge-yellow';
        $statusLabel = $event['event_status'] === 'published' ? 'Published' : 'Draft';
    ?>
    <div class="event-card p-4 flex gap-3">
      <?php if (!empty($event['event_image'])): ?>
        <img src="<?= htmlspecialchars($event['event_image']) ?>" class="image-thumb flex-shrink-0" alt="">
      <?php else: ?>
        <div class="image-thumb flex-shrink-0 bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm">SJ</div>
      <?php endif; ?>
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($event['event_title']) ?></p>
          <span class="badge <?= $statusBadge ?> flex-shrink-0"><?= $statusLabel ?></span>
        </div>
        <p class="text-xs text-gray-400 mt-0.5"><?= date('M d, Y', strtotime($event['event_date'])) ?> · <?= htmlspecialchars($event['event_location'] ?? '—') ?></p>
        <?php if (!empty($event['event_category'])): ?>
        <span class="badge <?= $badgeClass ?> mt-2 inline-flex"><?= htmlspecialchars($event['event_category']) ?></span>
        <?php endif; ?>
        <div class="flex gap-2 mt-3">
          <button onclick="openEditModal(<?= $event['event_id'] ?>)" class="btn-secondary !px-3 !py-1.5 text-xs flex-1">Edit</button>
          <button onclick="openDeleteModal(<?= $event['event_id'] ?>, '<?= htmlspecialchars(addslashes($event['event_title'])) ?>')" class="btn-danger flex-1">Delete</button>
        </div>
      </div>
    </div>
    <?php endwhile; else: ?>
      <div class="p-10 text-center text-sm text-gray-400">No events yet.</div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="flex justify-center p-5 border-t border-gray-100">
    <nav class="flex items-center gap-2" aria-label="Pagination">
      <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Previous</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>"
         class="px-3 py-1.5 text-sm font-medium <?= $i == $page ? 'text-white bg-orange-600 border-orange-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg border">
        <?= $i ?>
      </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Next</a>
      <?php endif; ?>
    </nav>
  </div>
  <?php endif; ?>

</div>