<?php
$rows = [
  [ [7, 38], [8, 32], [9, 30] ],
  [ [10, 55], [11, 45] ],
  [ [12, 34], [13, 33], [14, 33] ],
  [ [15, 50], [16, 50]],
];
?>

<section class="relative overflow-hidden bg-white dot-grid" id="mosaic-section">

  <div class="pointer-events-none absolute top-1/3 -right-24 w-[380px] h-[380px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.09)_0%,transparent_70%)]" aria-hidden="true"></div>

  <div class="relative z-10 mx-auto px-4 sm:px-6 lg:px-8 py-20">

    <div class="text-center mb-10" data-aos="fade-up">
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-4">
        <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
        Behind the Scenes
      </div>
      <h2 class="ff-display text-3xl lg:text-4xl font-bold text-slate-900 leading-tight">
        A Look Inside <span class="text-grad">SJFB</span>
      </h2>
    </div>

    <div class="space-y-4 lg:space-y-5 mx-auto">
      <?php foreach ($rows as $ri => $row): ?>
        <div
          class="photo-splitter-row flex h-56 sm:h-72 lg:h-[26rem] rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(249,115,22,.14),0_4px_16px_rgba(15,23,42,.07)]"
          data-aos="fade-up" data-aos-delay="<?= $ri * 100 ?>"
        >
          <?php foreach ($row as $pi => $panel): [$n, $ratio] = $panel; ?>
            <?php if ($pi > 0): ?>
              <div class="photo-splitter-handle relative flex-none w-3.5 sm:w-4 bg-white cursor-col-resize group z-10" role="separator" aria-orientation="vertical" tabindex="0">
                <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center w-4 h-7 sm:h-8 rounded-md bg-white border border-orange-200 text-orange-500 shadow-sm group-hover:bg-orange-50 group-active:bg-orange-100 transition-colors">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/>
                    <circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>
                  </svg>
                </span>
              </div>
            <?php endif; ?>

            <div class="photo-splitter-panel photo-slot relative overflow-hidden" style="flex: <?= $ratio ?> 1 0px;">
              <img
                src="./assets/images/contents/about_<?= $n ?>.jpg"
                alt="Life at St. Joseph Fish Brokerage"
                loading="lazy"
                class="w-full h-full object-cover select-none pointer-events-none"
                draggable="false"
                onerror="phFallback(this, 'Candid workplace moment')"
              >
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<script>
(function () {
  const MIN_PANEL_PX = 70; // floor width so a panel never gets squeezed to nothing

  function startDrag(handle, startEvent) {
    const row = handle.closest('.photo-splitter-row');
    const panels = Array.from(row.querySelectorAll('.photo-splitter-panel'));
    const handles = Array.from(row.querySelectorAll('.photo-splitter-handle'));
    const prevPanel = handle.previousElementSibling;
    const nextPanel = handle.nextElementSibling;
    if (!prevPanel || !nextPanel) return;

    const containerWidth = row.getBoundingClientRect().width;
    const handlesWidth = handles.reduce((sum, h) => sum + h.getBoundingClientRect().width, 0);
    const freeSpace = containerWidth - handlesWidth;
    const sumRatios = panels.reduce((sum, p) => sum + parseFloat(p.style.flexGrow || 1), 0);
    const pxPerRatio = freeSpace / sumRatios;
    const minRatio = MIN_PANEL_PX / pxPerRatio;

    const startPrev = parseFloat(prevPanel.style.flexGrow);
    const startNext = parseFloat(nextPanel.style.flexGrow);
    const startX = startEvent.touches ? startEvent.touches[0].clientX : startEvent.clientX;

    document.body.classList.add('select-none');

    function onMove(e) {
      const x = e.touches ? e.touches[0].clientX : e.clientX;
      let deltaRatio = (x - startX) / pxPerRatio;
      const maxDelta = startNext - minRatio;
      const minDelta = -(startPrev - minRatio);
      deltaRatio = Math.min(maxDelta, Math.max(minDelta, deltaRatio));
      prevPanel.style.flex = (startPrev + deltaRatio) + ' 1 0px';
      nextPanel.style.flex = (startNext - deltaRatio) + ' 1 0px';
      if (e.cancelable) e.preventDefault();
    }

    function onEnd() {
      document.body.classList.remove('select-none');
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onEnd);
      document.removeEventListener('touchmove', onMove);
      document.removeEventListener('touchend', onEnd);
    }

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onEnd);
    document.addEventListener('touchmove', onMove, { passive: false });
    document.addEventListener('touchend', onEnd);
  }

  document.querySelectorAll('.photo-splitter-handle').forEach(function (handle) {
    handle.addEventListener('mousedown', function (e) { e.preventDefault(); startDrag(handle, e); });
    handle.addEventListener('touchstart', function (e) { startDrag(handle, e); }, { passive: true });

    // Basic keyboard support: left/right arrow nudges the divider
    handle.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      const prevPanel = handle.previousElementSibling;
      const nextPanel = handle.nextElementSibling;
      if (!prevPanel || !nextPanel) return;
      const step = 2;
      const dir = e.key === 'ArrowRight' ? 1 : -1;
      const prevVal = parseFloat(prevPanel.style.flexGrow);
      const nextVal = parseFloat(nextPanel.style.flexGrow);
      const minRatio = Math.min(prevVal, nextVal) * 0.3;
      if (prevVal + dir * step < minRatio || nextVal - dir * step < minRatio) return;
      prevPanel.style.flex = (prevVal + dir * step) + ' 1 0px';
      nextPanel.style.flex = (nextVal - dir * step) + ' 1 0px';
      e.preventDefault();
    });
  });
})();
</script>