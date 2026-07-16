<?php

if (!isset($conn)) {
    include 'conn.php';
}

define('MARKET_IMG_URL', 'uploads/markets/');

$locs_query  = "SELECT * FROM markets WHERE is_active = 1 ORDER BY display_order";
$locs_result = mysqli_query($conn, $locs_query);

$locs = [];
if ($locs_result) {
    while ($row = mysqli_fetch_assoc($locs_result)) {
        $locs[] = [
            'tag'    => $row['tag'] ?? '',
            'name'   => $row['market_name'] ?? '',
            'region' => $row['location_short'] ?? '',
            'desc'   => mb_strimwidth($row['description'] ?? '', 0, 140, '…'),
            'image'  => !empty($row['main_image'])
                          ? MARKET_IMG_URL . $row['main_image']
                          : './assets/images/contents/home_3.jpg', // fallback if no image uploaded yet
            'link'   => './services.php?market=' . urlencode($row['market_key'] ?? ''),
        ];
    }
}
?>

<!-- ═══════════════════════════════════════
       OPERATIONS / LOCATIONS
  ═══════════════════════════════════════ -->
  <section id="operations"
            class="relative overflow-hidden bg-slate-900 py-20
                   bg-[radial-gradient(circle,rgba(249,115,22,.05)_1px,transparent_1px)] bg-[length:30px_30px]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-[.7rem] font-bold uppercase tracking-widest mb-4">
          <span class="animate-pulse-dot w-2 h-2 rounded-full bg-orange-400 shrink-0"></span>
          Port Operations
        </span>
        <h2 class="font-display text-2xl lg:text-3xl font-bold text-white">
          <?= count($locs) ?> Ports. <span class="bg-grad-orange bg-clip-text text-transparent">One National Network.</span>
        </h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php foreach ($locs as $i => $loc):
          $modalId = 'port-modal-' . $i;
        ?>
        <button type="button"
                onclick="openPortModal('<?= $modalId ?>')"
                aria-haspopup="dialog"
                aria-controls="<?= $modalId ?>"
                class="w-full text-left p-5 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 hover:border-orange-500/30 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/50"
                data-aos="fade-up" data-aos-delay="<?= $i * 70 ?>">
          <?php if (!empty($loc['tag'])): ?>
          <span class="inline-block text-[.625rem] font-bold uppercase tracking-widest text-orange-400 mb-3"><?= htmlspecialchars($loc['tag']) ?></span>
          <?php endif; ?>
          <h3 class="font-display text-lg font-bold text-white mb-0.5"><?= htmlspecialchars($loc['name']) ?></h3>
          <p class="text-xs font-semibold text-slate-400 mb-2"><?= htmlspecialchars($loc['region']) ?></p>
          <p class="text-sm text-slate-400 leading-relaxed"><?= htmlspecialchars($loc['desc']) ?></p>
        </button>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- ═══════════════════════════════════════
         PORT IMAGE-SPLASH MODALS
    ═══════════════════════════════════════ -->
  <?php foreach ($locs as $i => $loc):
    $modalId = 'port-modal-' . $i;
  ?>
  <div id="<?= $modalId ?>"
       class="port-modal-overlay hidden fixed inset-0 z-[999] flex items-center justify-center bg-black/55 backdrop-blur-sm overflow-y-auto p-4"
       role="dialog"
       aria-labelledby="<?= $modalId ?>-label"
       tabindex="-1">
    <div class="port-modal-box w-full max-w-xl mx-auto">
      <div class="flex flex-col bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden">

        <!-- Image splash -->
        <div class="relative">
          <img src="<?= htmlspecialchars($loc['image']) ?>"
               alt="<?= htmlspecialchars($loc['name']) ?>"
               class="w-full h-56 sm:h-72 object-cover"
               onerror="this.src='./assets/images/contents/home_4.jpg'">
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/10 to-transparent"></div>

          <button type="button"
                  class="absolute top-3 end-3 flex items-center justify-center w-9 h-9 rounded-full bg-slate-900/70 border border-white/10 text-white hover:bg-slate-900/90 transition-colors"
                  onclick="closePortModal('<?= $modalId ?>')"
                  aria-label="Close">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>

          <?php if (!empty($loc['tag'])): ?>
          <span class="absolute bottom-3 start-4 inline-block text-[.625rem] font-bold uppercase tracking-widest text-orange-400"><?= htmlspecialchars($loc['tag']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Content -->
        <div class="p-6">
          <h3 id="<?= $modalId ?>-label" class="font-display text-xl font-bold text-white mb-0.5"><?= htmlspecialchars($loc['name']) ?></h3>
          <p class="text-xs font-semibold text-slate-400 mb-3"><?= htmlspecialchars($loc['region']) ?></p>
          <p class="text-sm text-slate-400 leading-relaxed"><?= htmlspecialchars($loc['desc']) ?></p>
        </div>

        <!-- Footer actions -->
        <div class="flex justify-between items-center gap-x-2 py-3 px-6 border-t border-white/10">
          <button type="button"
                  class="py-2 px-3 text-sm font-semibold rounded-lg text-slate-300 hover:bg-white/5 transition-colors"
                  onclick="closePortModal('<?= $modalId ?>')">
            Close
          </button>
          <a href="<?= htmlspecialchars($loc['link']) ?>"
             class="py-2 px-4 text-sm font-semibold rounded-lg bg-grad-orange text-white hover:opacity-90 transition-opacity">
            View Full Port Profile &rarr;
          </a>
        </div>

      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <style>
  .port-modal-overlay.hidden { display: none; }
  .port-modal-box { animation: portModalSlideIn .28s cubic-bezier(.22,.61,.36,1) both; }
  @keyframes portModalSlideIn {
    from { opacity: 0; transform: translateY(-18px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
  @keyframes portModalSlideOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to   { opacity: 0; transform: translateY(-14px) scale(.97); }
  }
  </style>

  <script>
    function openPortModal(id) {
      var overlay = document.getElementById(id);
      if (!overlay) return;
      overlay.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }

    function closePortModal(id) {
      var overlay = document.getElementById(id);
      if (!overlay) return;
      var box = overlay.querySelector('.port-modal-box');
      if (box) {
        box.style.animation = 'portModalSlideOut .2s cubic-bezier(.55,0,.1,1) both';
      }
      setTimeout(function () {
        overlay.classList.add('hidden');
        if (box) box.style.animation = '';
        document.body.classList.remove('overflow-hidden');
      }, 200);
    }

    // Click on the dark backdrop (not the card itself) closes the modal
    document.addEventListener('click', function (e) {
      var overlay = e.target.closest('.port-modal-overlay');
      if (overlay && e.target === overlay) {
        closePortModal(overlay.id);
      }
    });

    // ESC closes whichever port modal is currently open
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      document.querySelectorAll('.port-modal-overlay:not(.hidden)').forEach(function (ov) {
        closePortModal(ov.id);
      });
    });
  </script>