<?php
/**
 * rider/navigation.php
 * Shared bottom navigation — include at the bottom of every rider page body.
 * Highlights the active tab based on the current file.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$nav = [
    ['file'=>'dashboard.php',     'icon'=>'🛵',  'label'=>'Dashboard'],
    ['file'=>'deliveries.php',    'icon'=>'📦',  'label'=>'Deliveries'],
    ['file'=>'notifications.php', 'icon'=>'🔔',  'label'=>'Alerts'],
    ['file'=>'my-profile.php',    'icon'=>'👤',  'label'=>'Profile'],
];
?>
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 safe-bottom">
  <div class="max-w-2xl mx-auto flex">
    <?php foreach ($nav as $item):
      $active = ($currentPage === $item['file']);
    ?>
    <a href="<?= $item['file'] ?>"
       class="flex-1 flex flex-col items-center gap-0.5 py-2.5 text-center transition-colors <?= $active ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600' ?>">
      <span class="text-xl leading-none"><?= $item['icon'] ?></span>
      <span class="text-[10px] font-semibold tracking-wide"><?= $item['label'] ?></span>
      <?php if ($active): ?>
      <span class="absolute bottom-0 block h-0.5 w-10 rounded-full bg-orange-500"></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</nav>
<style>
  /* push page content above the nav bar */
  body { padding-bottom: 64px; }
  .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }
</style>