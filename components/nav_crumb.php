<?php
$pageMap = [
  'Home' => '/sjfbi-js/indexclone.php',
  'About' => '/about1.php',
  'Sustainability' => '/sustainability.php',
  'Services' => '/services.php',
  'Careers' => '/careers.php',
];
?>

<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto lg:hidden">
  <div class="sticky top-0 inset-x-0 z-20 bg-white border-y border-gray-200 px-4 sm:px-6 lg:px-8 lg:hidden">
    <div class="flex items-center py-2">
      <ol class="flex items-center whitespace-nowrap text-sm">

        <!-- Home -->
        <li class="flex items-center text-gray-800">
          <a href="/sjfbi-js/indexclone.php" class="hover:underline">Home</a>
        </li>

        <?php if (!empty($pageTitle) && $pageTitle !== 'Home' && isset($pageMap[$pageTitle])): ?>
          <li class="flex items-center">
            <svg xmlns="shrink-0 mx-3 text-gray-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
            <a href="<?= $pageMap[$pageTitle]; ?>" class="hover:underline">
              <?= htmlspecialchars($pageTitle); ?>
            </a>
          </li>
        <?php endif; ?>

      </ol>
    </div>
  </div>
</div>
