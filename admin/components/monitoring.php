<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
  <!-- Card -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl  ">
    <div class="p-4 md:p-5">
      <div class="flex items-center gap-x-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 ">
          Total users
        </p>
        <div class="hs-tooltip">
          <div class="hs-tooltip-toggle">
            <svg class="shrink-0 size-4 text-gray-500 " xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
              <path d="M12 17h.01" />
            </svg>
            <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm " role="tooltip">
              The number of daily users
            </span>
          </div>
        </div>
      </div>

      <div class="mt-1 flex items-center gap-x-2">
        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 ">
          72,540
        </h3>
        <span class="flex items-center gap-x-1 text-green-600">
          <svg class="inline-block size-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
            <polyline points="16 7 22 7 22 13" />
          </svg>
          <span class="inline-block text-sm">
            1.7%
          </span>
        </span>
      </div>
    </div>
  </div>
  <!-- End Card -->

  <!-- Card -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl  ">
    <div class="p-4 md:p-5">
      <div class="flex items-center gap-x-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 ">
          Sessions
        </p>
      </div>

      <div class="mt-1 flex items-center gap-x-2">
        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 ">
          29.4%
        </h3>
      </div>
    </div>
  </div>
  <!-- End Card -->

  <!-- Card -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl  ">
    <div class="p-4 md:p-5">
      <div class="flex items-center gap-x-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 ">
          Avg. Click Rate
        </p>
      </div>

      <div class="mt-1 flex items-center gap-x-2">
        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 ">
          56.8%
        </h3>
        <span class="flex items-center gap-x-1 text-red-600">
          <svg class="inline-block size-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 17 13.5 8.5 8.5 13.5 2 7" />
            <polyline points="16 17 22 17 22 11" />
          </svg>
          <span class="inline-block text-sm">
            1.7%
          </span>
        </span>
      </div>
    </div>
  </div>
  <!-- End Card -->

  <!-- Card -->
  <div class="flex flex-col bg-white border shadow-sm rounded-xl  ">
    <div class="p-4 md:p-5">
      <div class="flex items-center gap-x-2">
        <p class="text-xs uppercase tracking-wide text-gray-500 ">
          Pageviews
        </p>
      </div>

      <div class="mt-1 flex items-center gap-x-2">
        <h3 class="text-xl sm:text-2xl font-medium text-gray-800 ">
          92,913
        </h3>
      </div>
    </div>
  </div>
  <!-- End Card -->
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden  ">
  <!-- Header -->
  <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 ">
    <div>
      <h2 class="text-xl font-semibold text-gray-800 ">
        Logs
      </h2>
    </div>
  </div>
  <!-- End Header -->

  <!-- Activity Log Table -->
  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th scope="col" class="px-6 py-3 text-start">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">
            User Type
          </span>
        </th>
        <th scope="col" class="px-6 py-3 text-start">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">
            Action
          </span>
        </th>
        <th scope="col" class="px-6 py-3 text-start">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">
            Order Code
          </span>
        </th>
        <th scope="col" class="px-6 py-3 text-start">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">
            Details
          </span>
        </th>
        <th scope="col" class="px-6 py-3 text-start">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-800">
            Date
          </span>
        </th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      <?php if (!empty($activity_log)): ?>
        <?php foreach ($activity_log as $row): ?>
          <tr class="logs-row bg-white">
            <td class="px-6 py-3">
              <?php 
                $status = $row['user_type'];

                switch ($status) {
                  case 'super_admin':
                    $badgeColor = 'bg-orange-500 text-white';
                    $label = 'Super Admin';
                    break;
                  case 'admin':
                    $badgeColor = 'bg-yellow-500 text-white';
                    $label = 'Admin';
                    break;
                  case 'customer':
                    $badgeColor = 'bg-blue-500 text-white';
                    $label = 'Customer';
                    break;
                  case 'rider':
                    $badgeColor = 'bg-purple-500 text-white';
                    $label = 'Rider';
                    break;
                  default:
                    $badgeColor = 'bg-gray-400 text-white';
                    $label = ucfirst($status);
                    break;
                }
              ?>
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $badgeColor; ?> whitespace-nowrap">
                <?php echo $label; ?>
              </span>
            </td>

            <!-- Action -->
            <td class="px-6 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium">
                <?= htmlspecialchars($row['action']) ?>
              </span>
            </td>

            <!-- Entity (entity_type + entity_id) -->
            <td class="px-6 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium">
                <?= htmlspecialchars($row['order_code']) ?>
              </span>
            </td>

            <!-- Details -->
            <td class="px-6 py-3">
              <span class="block text-sm text-gray-500 rounded-full">
                <?= !empty($row['details']) ? htmlspecialchars($row['details']) : '-' ?>
              </span>
            </td>

            <!-- Date -->
            <td class="px-6 py-3">
              <span class="block text-sm text-gray-500 whitespace-nowrap">
                <?= date("M d, Y h:i A", strtotime($row['created_at'])) ?>
              </span>
            </td>

          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" class="px-6 py-3 text-center text-sm text-gray-500">
            No logs found
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Footer -->
  <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
    <div>
      <p class="text-sm text-gray-600">
        <span class="font-semibold text-gray-800">
          <?php echo $totalItems; ?>
        </span> results
      </p>
    </div>

    <div>
      <div class="inline-flex gap-x-2">
        <?php
        // Previous button
        if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            Prev
          </a>
        <?php else: ?>
          <span class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            Prev
          </span>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php 
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        // Show first page if not in range
        if ($start > 1): ?>
          <a href="?page=1" class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
            1
          </a>
          <?php if ($start > 2): ?>
            <span class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-gray-800">...</span>
          <?php endif;
        endif;
        
        for ($i = $start; $i <= $end; $i++): ?>
          <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-800'; ?> py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
            <?php echo $i; ?>
          </a>
        <?php endfor; 
        
        // Show last page if not in range
        if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?>
            <span class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium text-gray-800">...</span>
          <?php endif; ?>
          <a href="?page=<?php echo $totalPages; ?>" class="py-1.5 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50">
            <?php echo $totalPages; ?>
          </a>
        <?php endif; ?>

        <!-- Next button -->
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?php echo $page + 1; ?>" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50">
            Next
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m9 18 6-6-6-6" />
            </svg>
          </a>
        <?php else: ?>
          <span class="py-1.5 px-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed">
            Next
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m9 18 6-6-6-6" />
            </svg>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- End Footer -->
    
</div>

<style>
  .logs-row {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
  }

  .logs-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border-left-color: #3b82f6;
  }
</style>