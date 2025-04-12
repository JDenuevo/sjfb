<?php
include '../conn.php'; // adjust path as needed

// Example queries (replace with actual logic or queries from your controller):
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM accounts"))['count'];
$logs = mysqli_query($conn, "SELECT * FROM logs ORDER BY log_date DESC LIMIT 10"); //
?>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
  <!-- Total Users Card -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl">
    <div class="p-4 md:p-5">
      <div class="flex items-center gap-x-2">
        <p class="text-xs uppercase tracking-wide text-gray-500">Total users</p>
      </div>
      <div class="mt-1 flex items-center gap-x-2">
        <h3 class="text-xl sm:text-2xl font-medium text-gray-800"><?= number_format($totalUsers) ?></h3>
      </div>
    </div>
  </div>

  <!-- Placeholder Cards -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl">
    <div class="p-4 md:p-5">
      <p class="text-xs uppercase tracking-wide text-gray-500">Orders Today</p>
      <h3 class="text-xl sm:text-2xl font-medium text-gray-800 mt-1"></h3>
    </div>
  </div>

  <div class="flex flex-col bg-white border shadow-sm rounded-xl">
    <div class="p-4 md:p-5">
      <p class="text-xs uppercase tracking-wide text-gray-500">Pending Orders</p>
      <h3 class="text-xl sm:text-2xl font-medium text-gray-800 mt-1"></h3>
    </div>
  </div>

  <div class="flex flex-col bg-white border shadow-sm rounded-xl">
    <div class="p-4 md:p-5">
      <p class="text-xs uppercase tracking-wide text-gray-500">Pageviews</p>
      <h3 class="text-xl sm:text-2xl font-medium text-gray-800 mt-1">92,913</h3>
    </div>
  </div>
</div>

<!-- Logs Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-6">
  <div class="px-6 py-4 border-b border-gray-200">
    <h2 class="text-xl font-semibold text-gray-800">Logs</h2>
  </div>

  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Type</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">User Type</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Description</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Status</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800 uppercase">Date and Time</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 text-sm text-gray-800">
      <?php while ($log = mysqli_fetch_assoc($logs)): ?>
        <tr>
          <td class="px-6 py-2"><?= htmlspecialchars($log['product_status']) ?></td>
          <td class="px-6 py-2"><?= htmlspecialchars($log['user_type'] ?? 'Admin') ?></td>
          <td class="px-6 py-2"><?= htmlspecialchars($log['description']) ?></td>
          <td class="px-6 py-2"><?= htmlspecialchars($log['status']) ?></td>
          <td class="px-6 py-2"><?= date("F j, Y @ h:i A", strtotime($log['log_date'])) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="px-6 py-4 border-t border-gray-200 text-sm text-gray-600">
    Showing latest logs
  </div>
</div>


  <!-- Footer -->
  <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 ">
    <div>
      <p class="text-sm text-gray-600 dark:text-neutral-400">
        <span class="font-semibold text-gray-800 ">1</span> results
      </p>
    </div>

    <div>
      <div class="inline-flex gap-x-2">
        <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 ">
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6" />
          </svg>
          Prev
        </button>

        <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50 ">
          Next
          <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6" />
          </svg>
        </button>
      </div>
    </div>
  </div>
  <!-- End Footer -->
</div>