<?php
// rider_list.php — improved

// Rider stats
$rStats = [];
$r = $conn->query("SELECT COUNT(*) as v FROM riders"); $rStats['total'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM riders WHERE is_available=1"); $rStats['available'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(DISTINCT assigned_rider_id) as v FROM orders WHERE order_status='OutForDelivery' AND assigned_rider_id IS NOT NULL"); $rStats['delivering'] = (int)$r->fetch_assoc()['v'];
$r = $conn->query("SELECT COUNT(*) as v FROM orders WHERE order_status='Delivered' AND assigned_rider_id IS NOT NULL"); $rStats['delivered'] = (int)$r->fetch_assoc()['v'];
?>

<!-- Stats strip -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
  <div class="bg-purple-50 border border-purple-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-purple-700"><?= $rStats['total'] ?></div>
    <div class="text-xs text-purple-600">Total Riders</div>
  </div>
  <div class="bg-green-50 border border-green-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-green-700"><?= $rStats['available'] ?></div>
    <div class="text-xs text-green-600">Available</div>
  </div>
  <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-orange-700"><?= $rStats['delivering'] ?></div>
    <div class="text-xs text-orange-600">Out Delivering</div>
  </div>
  <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
    <div class="text-xl font-bold text-blue-700"><?= $rStats['delivered'] ?></div>
    <div class="text-xs text-blue-600">Total Delivered</div>
  </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
  <!-- Header -->
  <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
    <div class="flex-1">
      <h2 class="text-lg font-semibold text-gray-800">Riders</h2>
      <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> registered riders</p>
    </div>
    <button type="button" data-modal-target="addRiderModal"
      class="flex items-center gap-x-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
      Add Rider
    </button>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rider</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Vehicle</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Contact</th>
          <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Active</th>
          <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Done</th>
          <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($riders as $rider):
          $initials = strtoupper(substr($rider['account_first_name'],0,1).substr($rider['account_last_name'],0,1));
          
          // Get active delivery count + total delivered
          $adRes = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE assigned_rider_id=? AND order_status='OutForDelivery'");
          $adRes->bind_param("i", $rider['rider_id']);
          $adRes->execute();
          $activeDeliveries = (int)$adRes->get_result()->fetch_assoc()['cnt'];

          $tdRes = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE assigned_rider_id=? AND order_status='Delivered'");
          $tdRes->bind_param("i", $rider['rider_id']);
          $tdRes->execute();
          $totalDone = (int)$tdRes->get_result()->fetch_assoc()['cnt'];
        ?>
        <tr class="rider-row hover:bg-purple-50/20 transition-colors">
          <!-- Rider -->
          <td class="px-6 py-3">
            <div class="flex items-center gap-3">
              <div class="size-10 rounded-full bg-purple-100 flex items-center justify-center text-sm font-bold text-purple-600 shrink-0">
                <?= $initials ?>
              </div>
              <div>
                <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($rider['account_first_name'].' '.$rider['account_last_name']) ?></div>
                <div class="text-xs text-gray-400"><?= htmlspecialchars($rider['account_email']) ?></div>
              </div>
            </div>
          </td>
          <!-- Vehicle -->
          <td class="px-4 py-3">
            <div class="text-sm font-medium text-gray-800"><?= ucfirst(htmlspecialchars($rider['vehicle_type'])) ?></div>
            <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($rider['vehicle_plate_number'] ?? '—') ?></div>
          </td>
          <!-- Contact -->
          <td class="px-4 py-3">
            <div class="text-xs text-gray-600"><?= htmlspecialchars($rider['phone_number'] ?? '—') ?></div>
            <div class="text-xs text-gray-400 font-mono text-xs"><?= htmlspecialchars(substr($rider['license_number'],0,16)) ?></div>
          </td>
          <!-- Active deliveries -->
          <td class="px-4 py-3 text-center">
            <?php if ($activeDeliveries > 0): ?>
            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700"><?= $activeDeliveries ?></span>
            <?php else: ?>
            <span class="text-xs text-gray-400">0</span>
            <?php endif; ?>
          </td>
          <!-- Total done -->
          <td class="px-4 py-3 text-center">
            <span class="text-sm font-bold text-gray-800"><?= $totalDone ?></span>
          </td>
          <!-- Status -->
          <td class="px-4 py-3 text-center">
            <?php if ($activeDeliveries > 0): ?>
              <span class="flex items-center justify-center gap-1 text-xs text-orange-600">
                <span class="size-2 rounded-full bg-orange-400 animate-pulse"></span>Delivering
              </span>
            <?php elseif ($rider['is_available']): ?>
              <span class="flex items-center justify-center gap-1 text-xs text-green-600">
                <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>Available
              </span>
            <?php else: ?>
              <span class="flex items-center justify-center gap-1 text-xs text-gray-400">
                <span class="size-2 rounded-full bg-gray-300"></span>Offline
              </span>
            <?php endif; ?>
          </td>
          <!-- Actions -->
          <td class="px-4 py-3 text-right">
            <div class="inline-flex gap-1">
              <button onclick="openEditRider(<?= $rider['rider_id'] ?>)"
                class="size-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <!-- Toggle availability
              <form action="./functions/toggle_rider.php" method="POST">
                <input type="hidden" name="rider_id" value="<?= $rider['rider_id'] ?>">
                <input type="hidden" name="is_available" value="<?= $rider['is_available'] ? 0 : 1 ?>">
                <button type="submit" title="<?= $rider['is_available'] ? 'Set Offline' : 'Set Available' ?>"
                  class="size-8 flex items-center justify-center rounded-lg <?= $rider['is_available'] ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' ?> transition-colors">
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </button>
              </form> -->
            </div>
          </td>
        </tr>

        <!-- Edit Rider Modal -->
        <div id="editRiderModal<?= $rider['rider_id'] ?>" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
          <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Edit Rider</h3>
              <button onclick="closeModal('editRiderModal<?= $rider['rider_id'] ?>')" class="text-gray-400 hover:text-gray-600">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </button>
            </div>
            <form action="./functions/rider_process.php" method="POST" class="space-y-3">
              <input type="hidden" name="rider_id" value="<?= $rider['rider_id'] ?>">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vehicle Type</label>
                <input type="text" name="vehicle_type" value="<?= htmlspecialchars($rider['vehicle_type']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Plate Number</label>
                <input type="text" name="vehicle_plate_number" value="<?= htmlspecialchars($rider['vehicle_plate_number'] ?? '') ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">License Number</label>
                <input type="text" name="license_number" value="<?= htmlspecialchars($rider['license_number']) ?>" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Availability</label>
                <select name="is_available" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
                  <option value="1" <?= $rider['is_available'] ? 'selected' : '' ?>>Available</option>
                  <option value="0" <?= !$rider['is_available'] ? 'selected' : '' ?>>Offline</option>
                </select>
              </div>
              <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeModal('editRiderModal<?= $rider['rider_id'] ?>')" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" name="update_rider" class="flex-1 px-4 py-2 text-sm bg-orange-600 hover:bg-orange-500 text-white rounded-lg">Save</button>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
    <p class="text-xs text-gray-500"><span class="font-semibold text-gray-700"><?= $totalItems ?></span> riders</p>
    <div class="flex gap-1">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
      <?php endif; ?>
      <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1.5 text-xs border rounded-lg <?= $i==$page ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-200 hover:bg-gray-50' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>" class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Rider Modal -->
<div id="addRiderModal" class="fixed inset-0 z-100 flex items-start justify-center bg-black bg-opacity-50 hidden overflow-y-auto py-10">      
  <div class="bg-white w-full max-w-4xl p-6 rounded-2xl shadow-2xl flex flex-col">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800">Add New Rider</h3>
      <button onclick="closeModal('addRiderModal')" class="text-gray-400 hover:text-gray-600">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <form action="./functions/rider_process.php" method="POST" class="p-6 space-y-4">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Assign Account</label>
        <select name="account_id" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
          <option value="">Select account to assign as rider</option>
          <?php foreach ($availableAccounts as $acc): ?>
          <option value="<?= $acc['account_id'] ?>"><?= htmlspecialchars($acc['account_first_name'].' '.$acc['account_last_name'].' ('.$acc['account_email'].')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Vehicle Type</label>
          <input type="text" name="vehicle_type" placeholder="e.g. Motorcycle" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Plate Number</label>
          <input type="text" name="vehicle_plate_number" placeholder="ABC-1234" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">License Number</label>
        <input type="text" name="license_number" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-orange-400">
      </div>
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="closeModal('addRiderModal')" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" name="add_rider" class="flex-1 px-4 py-2 text-sm bg-orange-600 hover:bg-orange-500 text-white rounded-lg">Add Rider</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditRider(riderId) {
  document.getElementById('editRiderModal' + riderId).classList.remove('hidden');
}
document.querySelectorAll('[data-modal-target]').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById(this.getAttribute('data-modal-target'))?.classList.remove('hidden');
  });
});
function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }
// Close on backdrop click
document.querySelectorAll('[id^="editRiderModal"], #addRiderModal').forEach(modal => {
  modal.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});
</script>