<!-- Page Transition Overlay -->
<div id="page-transition" aria-hidden="true">
  <div id="transition-curtain"></div>
</div>

<style>
  /* ── CURTAIN LAYERS ── */
  #page-transition {
    position: fixed;
    inset: 0;
    z-index: 99999;
    pointer-events: none;
  }

  #transition-curtain {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background-color: #FFFFFF;
    transform: translateY(-100%);
    will-change: transform;
  }

  /* ── STATE: PAGE ENTERING (curtain sweeps DOWN to cover screen) ── */
  #page-transition.is-leaving #transition-curtain {
    animation: curtain-cover 0.55s cubic-bezier(0.76, 0, 0.24, 1) forwards;
    pointer-events: all;
  }

  /* ── STATE: PAGE ENTERING (curtain sweeps DOWN off screen to reveal page) ── */
  #page-transition.is-entering #transition-curtain {
    transform: translateY(0%); /* start already covering */
    animation: curtain-reveal 0.6s cubic-bezier(0.76, 0, 0.24, 1) forwards;
  }

  @keyframes curtain-cover {
    0%   { transform: translateY(-100%); }
    100% { transform: translateY(0%); }
  }

  @keyframes curtain-reveal {
    0%   { transform: translateY(0%); }
    100% { transform: translateY(100%); }
  }
</style>

<script>
(function () {
  const transition = document.getElementById('page-transition');
  const curtain    = document.getElementById('transition-curtain');
  const SESSION_KEY = 'sjfb_page_transition';

  // ── On page load: only reveal if we arrived via a transition link click ──
  window.addEventListener('DOMContentLoaded', function () {
    const wasTransitioned = sessionStorage.getItem(SESSION_KEY) === '1';

    if (wasTransitioned) {
      // Clear the flag immediately so refreshes don't re-trigger
      sessionStorage.removeItem(SESSION_KEY);

      // Curtain starts covering the screen, then sweeps down to reveal
      curtain.style.transform = 'translateY(0%)';
      transition.classList.add('is-entering');

      curtain.addEventListener('animationend', function handler() {
        transition.classList.remove('is-entering');
        curtain.style.transform = 'translateY(-100%)';
        curtain.removeEventListener('animationend', handler);
      });
    }
    // else: normal page load / refresh — no animation, page just appears instantly
  });

  // ── On link click: curtain sweeps DOWN to cover, set flag, then navigate ──
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Skip non-navigating links
    if (
      href.startsWith('#') ||
      href.startsWith('javascript') ||
      href.startsWith('mailto') ||
      href.startsWith('tel') ||
      link.target === '_blank' ||
      link.hasAttribute('data-fancybox') ||
      e.ctrlKey || e.metaKey || e.shiftKey || e.altKey
    ) return;

    // Only intercept same-origin links
    try {
      const url = new URL(href, window.location.href);
      if (url.origin !== window.location.origin) return;
    } catch (_) { return; }

    e.preventDefault();

    // Reset curtain position above screen, then animate it covering down
    curtain.style.animation = 'none';
    curtain.style.transform = 'translateY(-100%)';
    void curtain.offsetHeight; // force reflow
    curtain.style.animation = '';

    transition.classList.add('is-leaving');

    curtain.addEventListener('animationend', function handler() {
      curtain.removeEventListener('animationend', handler);
      // Set flag so the NEXT page knows to play the reveal
      sessionStorage.setItem(SESSION_KEY, '1');
      window.location.href = href;
    });
  });
})();
</script>