<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SJFBI – Features Section</title>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<!-- Preline UI JS -->
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.js"></script>
<!-- AOS -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">

<style>
  /* Only what Tailwind CDN can't express */
  :root {
    --grad-orange: linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%);
  }

  body { font-family: 'Lexend', sans-serif; }
  .ff-display { font-family: 'Playfair Display', Georgia, serif; }

  /* Gradient text */
  .text-grad {
    background: var(--grad-orange);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Gradient background utility */
  .bg-grad { background: var(--grad-orange); }

  /* Dot-grid texture */
  .dot-grid::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(249,115,22,.05) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none;
    z-index: 0;
  }

  /* Active tab left-bar accent */
  .tab-btn.is-active::before {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 3.5px; height: 55%;
    background: var(--grad-orange);
    border-radius: 0 4px 4px 0;
  }

  /* Pulsing dot */
  @keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(.7); }
  }
  .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

  /* Floating badge */
  @keyframes float-y {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-7px); }
  }
  .float-anim   { animation: float-y 3.5s ease-in-out infinite; }
  .float-anim-2 { animation: float-y 4s ease-in-out infinite .8s; }

  /* Image entrance */
  @keyframes img-in {
    from { opacity:0; transform:scale(1.04); }
    to   { opacity:1; transform:scale(1); }
  }
  .entering { animation: img-in .42s cubic-bezier(.25,.46,.45,.94) forwards; }

  /* Progress bar */
  #explore-progress-bar { transition: width .3s cubic-bezier(.34,1.56,.64,1); }
</style>

<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          display: ["'Playfair Display'", 'Georgia', 'serif'],
          body:    ["'Lexend'", 'system-ui', 'sans-serif'],
        }
      }
    }
  }
</script>
</head>
<body class="bg-white">

<!-- ═══════════════════════════════════════════════
     STRUCTURED DATA
═══════════════════════════════════════════════ -->
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@graph":[
    {
      "@type":"Organization",
      "@id":"https://fishbrokers.net/#organization",
      "name":"St. Joseph Fish Brokerage Inc.",
      "url":"https://fishbrokers.net",
      "description":"The largest fish brokerage network in the Philippines.",
      "areaServed":"Philippines",
      "telephone":"(+63) 946-497-3689"
    }
  ]
}
</script>

<!-- ═══════════════════════════════════════════════
     SECTION
═══════════════════════════════════════════════ -->
<section
  class="relative overflow-hidden bg-white dot-grid"
  aria-labelledby="explore-heading"
  itemscope
  itemtype="https://schema.org/Organization">

  <!-- Glow blobs -->
  <div class="pointer-events-none absolute -top-32 -right-40 w-[560px] h-[560px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.12)_0%,transparent_70%)]" aria-hidden="true"></div>
  <div class="pointer-events-none absolute -bottom-24 -left-28 w-[420px] h-[420px] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,.09)_0%,transparent_70%)]" aria-hidden="true"></div>

  <div class="relative z-10 mx-auto max-w-6xl px-6 py-24">

    <!-- ── HEADER ── -->
    <div class="grid lg:grid-cols-2 gap-10 items-end mb-16">

      <!-- Left: heading -->
      <div data-aos="fade-right" data-aos-duration="700">

        <!-- Eyebrow pill -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-5">
          <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
          Established in the Philippines
        </div>

        <h2 id="explore-heading" class="ff-display text-4xl lg:text-5xl font-bold text-slate-900 leading-tight tracking-tight mb-5">
          The Largest Fish Brokerage<br>Network in the <em class="text-grad not-italic">Philippines</em>
        </h2>
        <p class="text-slate-500 leading-relaxed max-w-lg">
          Connecting Filipino fishermen with buyers, wholesalers, restaurants, and retailers — delivering fresh seafood with full traceability, fair pricing, and nationwide reach.
        </p>
      </div>

      <!-- Right: stat strip -->
      <div data-aos="fade-left" data-aos-duration="700" data-aos-delay="100">
        <div class="grid grid-cols-3 sm:grid-cols-3 divide-x divide-orange-200 rounded-2xl border border-orange-200 bg-orange-50 overflow-hidden">

          <div class="py-7 px-5 text-center hover:bg-orange-100/60 transition-colors">
            <div class="ff-display text-4xl font-bold text-grad leading-none mb-2">500+</div>
            <div class="text-[.75rem] font-semibold text-slate-500 tracking-wide">Verified Suppliers</div>
          </div>

          <div class="py-7 px-5 text-center hover:bg-orange-100/60 transition-colors">
            <div class="ff-display text-4xl font-bold text-grad leading-none mb-2">All PH</div>
            <div class="text-[.75rem] font-semibold text-slate-500 tracking-wide">Nationwide Coverage</div>
          </div>

          <div class="py-7 px-5 text-center hover:bg-orange-100/60 transition-colors">
            <div class="ff-display text-4xl font-bold text-grad leading-none mb-2">Daily</div>
            <div class="text-[.75rem] font-semibold text-slate-500 tracking-wide">Fresh Catch Sourced</div>
          </div>

        </div>
      </div>
    </div><!-- /header grid -->

    <!-- ── MAIN GRID ── -->
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- IMAGE COLUMN -->
      <div class="relative" data-aos="fade-right" data-aos-duration="800">

        <!-- Corner accents -->
        <div class="pointer-events-none absolute -top-2.5 -left-2.5 w-11 h-11 border-t-[2.5px] border-l-[2.5px] border-orange-300/50 rounded-md" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-2.5 -right-2.5 w-11 h-11 border-b-[2.5px] border-r-[2.5px] border-orange-300/50 rounded-md" aria-hidden="true"></div>

        <div class="relative rounded-2xl overflow-hidden shadow-[0_28px_80px_rgba(249,115,22,.18),0_4px_16px_rgba(15,23,42,.1)] group">
          <img
            id="explore-active-img"
            src="./assets/images/contents/intro_1.png"
            alt="Fresh seafood sourced directly from verified Filipino fishermen"
            width="600" height="440"
            class="w-full h-[440px] object-cover block transition-transform duration-700 group-hover:scale-[1.03]"
            loading="lazy" decoding="async">
          <!-- overlay -->
          <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-orange-400/20"></div>
        </div>

        <!-- Badge: HACCP -->
        <div class="float-anim absolute -bottom-5 -right-5 z-10 flex items-center gap-2.5 bg-white border border-orange-200 rounded-xl px-4 py-3 shadow-[0_8px_32px_rgba(249,115,22,.22)] whitespace-nowrap" aria-label="HACCP-compliant facility">
          <span class="bg-grad flex items-center justify-center w-9 h-9 rounded-xl shrink-0">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
          </span>
          <div>
            <strong class="block text-[.8rem] font-bold text-slate-900 leading-tight">HACCP Certified</strong>
            <span class="text-[.68rem] text-slate-400">Navotas Fish Port</span>
          </div>
        </div>

        <!-- Badge: BFAR -->
        <div class="float-anim-2 absolute -top-4 -left-4 z-10 flex items-center gap-2 bg-grad text-white text-[.75rem] font-bold rounded-xl px-4 py-2.5 shadow-[0_8px_32px_rgba(249,115,22,.22)] whitespace-nowrap" aria-label="BFAR Licensed">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
          BFAR Licensed
        </div>
      </div><!-- /image col -->

      <!-- TAB COLUMN -->
      <div class="flex flex-col" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">

        <p class="text-[.65rem] tracking-[.18em] uppercase text-slate-400 font-bold mb-4">Why Choose SJFBI</p>

        <div
          class="flex flex-col gap-1.5"
          role="tablist"
          aria-orientation="vertical"
          aria-label="Features of St. Joseph Fish Brokerage Inc.">

          <!-- Tab 1 -->
          <button
            type="button"
            class="tab-btn is-active relative flex items-start gap-4 text-left px-5 py-4 rounded-xl border border-transparent transition-all duration-200 hover:bg-orange-50 hover:border-orange-200"
            role="tab" id="explore-tab-1" aria-selected="true" aria-controls="explore-panel-1"
            data-img="./assets/images/contents/intro_1.png"
            data-alt="Fresh seafood from verified Filipino fishermen"
            data-index="0">
            <span class="absolute top-4 right-4 ff-display text-[.65rem] italic text-slate-200 pointer-events-none tab-num">01</span>
            <span class="tab-icon shrink-0 mt-0.5 w-11 h-11 rounded-[.875rem] flex items-center justify-center bg-slate-100 border border-slate-100 text-slate-400 transition-all duration-200">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 5.5A3.5 3.5 0 0 1 8.5 2H12v7H8.5A3.5 3.5 0 0 1 5 5.5z"/><path d="M12 2h3.5a3.5 3.5 0 1 1 0 7H12V2z"/><path d="M5 14.5A3.5 3.5 0 0 0 8.5 18H12v-7H8.5A3.5 3.5 0 0 0 5 14.5z"/><path d="M12 11h3.5a3.5 3.5 0 1 1 0 7H12v-7z"/></svg>
            </span>
            <span class="flex-1 min-w-0">
              <span class="tab-title block text-[.93rem] font-semibold text-slate-500 leading-snug mb-1 transition-colors duration-200">Fresh Seafood Direct from Filipino Fishermen</span>
              <span class="tab-desc block text-[.8rem] text-slate-400 leading-relaxed transition-colors duration-200">We source premium seafood directly from verified fishermen and suppliers across the Philippines — ensuring quality, traceability, and daily freshness.</span>
            </span>
          </button>

          <!-- Tab 2 -->
          <button
            type="button"
            class="tab-btn relative flex items-start gap-4 text-left px-5 py-4 rounded-xl border border-transparent transition-all duration-200 hover:bg-orange-50 hover:border-orange-200"
            role="tab" id="explore-tab-2" aria-selected="false" aria-controls="explore-panel-2"
            data-img="./assets/images/contents/intro_2.png"
            data-alt="Transparent seafood trading platform"
            data-index="1">
            <span class="absolute top-4 right-4 ff-display text-[.65rem] italic text-slate-200 pointer-events-none tab-num">02</span>
            <span class="tab-icon shrink-0 mt-0.5 w-11 h-11 rounded-[.875rem] flex items-center justify-center bg-slate-100 border border-slate-100 text-slate-400 transition-all duration-200">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
            </span>
            <span class="flex-1 min-w-0">
              <span class="tab-title block text-[.93rem] font-semibold text-slate-500 leading-snug mb-1 transition-colors duration-200">Trusted Seafood Trading &amp; Brokerage Network</span>
              <span class="tab-desc block text-[.8rem] text-slate-400 leading-relaxed transition-colors duration-200">A transparent, efficient seafood trading platform connecting buyers, sellers, restaurants, and wholesalers nationwide — fair pricing, reliable supply chain.</span>
            </span>
          </button>

          <!-- Tab 3 -->
          <button
            type="button"
            class="tab-btn relative flex items-start gap-4 text-left px-5 py-4 rounded-xl border border-transparent transition-all duration-200 hover:bg-orange-50 hover:border-orange-200"
            role="tab" id="explore-tab-3" aria-selected="false" aria-controls="explore-panel-3"
            data-img="./assets/images/contents/intro_3.png"
            data-alt="Wholesale seafood supply for restaurants and retailers"
            data-index="2">
            <span class="absolute top-4 right-4 ff-display text-[.65rem] italic text-slate-200 pointer-events-none tab-num">03</span>
            <span class="tab-icon shrink-0 mt-0.5 w-11 h-11 rounded-[.875rem] flex items-center justify-center bg-slate-100 border border-slate-100 text-slate-400 transition-all duration-200">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
            </span>
            <span class="flex-1 min-w-0">
              <span class="tab-title block text-[.93rem] font-semibold text-slate-500 leading-snug mb-1 transition-colors duration-200">Wholesale Seafood for Restaurants &amp; Retailers</span>
              <span class="tab-desc block text-[.8rem] text-slate-400 leading-relaxed transition-colors duration-200">Designed for bulk buyers — restaurants, hotels, and retailers — seeking consistent seafood supply, fast ordering, and dependable nationwide distribution.</span>
            </span>
          </button>

        </div><!-- /tablist -->

        <!-- Progress bar -->
        <div class="h-[3px] rounded-full bg-orange-100 mt-4 overflow-hidden" aria-hidden="true">
          <div id="explore-progress-bar" class="h-full bg-grad rounded-full" style="width:33.33%"></div>
        </div>

        <!-- Checklist -->
        <div class="flex flex-col gap-2.5 mt-6" id="explore-checklist" aria-live="polite"></div>

        <!-- Hidden panels for SEO/a11y -->
        <div id="explore-panel-1" role="tabpanel" aria-labelledby="explore-tab-1" class="sr-only">Fresh seafood sourced directly from verified Filipino fishermen, ensuring quality and daily freshness.</div>
        <div id="explore-panel-2" role="tabpanel" aria-labelledby="explore-tab-2" class="sr-only" hidden>A transparent seafood trading platform connecting buyers, sellers, restaurants, and wholesalers nationwide.</div>
        <div id="explore-panel-3" role="tabpanel" aria-labelledby="explore-tab-3" class="sr-only" hidden>Wholesale seafood supply for restaurants, hotels, and retailers with dependable nationwide distribution.</div>

      </div><!-- /tab col -->
    </div><!-- /main grid -->

    <!-- ── CTA STRIP ── -->
    <div
      class="relative flex flex-wrap items-center justify-between gap-5 mt-20 px-8 py-7 bg-white border border-orange-200 rounded-2xl shadow-sm overflow-hidden"
      data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">

      <!-- left accent bar -->
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-grad rounded-l-2xl" aria-hidden="true"></div>

      <div>
        <h3 class="ff-display text-lg font-bold text-slate-900 mb-1 leading-snug">Ready to source fresh seafood for your business?</h3>
        <p class="text-[.82rem] text-slate-500 leading-relaxed">Order online or call us — we deliver fresh catch to your door nationwide.</p>
      </div>

      <div class="flex gap-2.5 flex-wrap">
        <a href="shop.php"
           class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-[.875rem] text-[.84rem] font-semibold text-white bg-grad shadow-[0_4px_16px_rgba(249,115,22,.28)] hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(249,115,22,.38)] transition-all duration-200">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Shop Seafood
        </a>
        <a href="contact.php"
           class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-[.875rem] text-[.84rem] font-semibold text-orange-600 bg-transparent border border-orange-200 hover:bg-orange-50 hover:border-orange-300 hover:-translate-y-px transition-all duration-200">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Contact Us
        </a>
      </div>
    </div><!-- /cta -->

  </div><!-- /inner -->
</section>

<!-- Hidden SEO -->
<p class="sr-only">Fish brokerage Philippines, fresh seafood supplier Philippines, wholesale fish distributor, seafood trading platform, Philippine fish market, buy fresh fish Philippines, seafood wholesaler Navotas, bulk fish supply restaurant, HACCP seafood Philippines.</p>

<script>
AOS.init({ once: true, easing: 'ease-out-cubic' });

(function () {
  'use strict';

  const tabData = [
    { checks: [
        { label: 'Direct sea-to-table sourcing',      sub: 'No middlemen, full traceability' },
        { label: 'Verified fishermen & cooperatives', sub: '500+ registered supplier partners' },
        { label: 'HACCP-compliant cold chain',        sub: 'Quality maintained from catch to delivery' },
    ]},
    { checks: [
        { label: 'Fair market-rate pricing',          sub: 'Transparent quotations, no hidden fees' },
        { label: 'Nationwide buyer network',          sub: 'Restaurants, wet markets & retailers' },
        { label: 'BFAR & FDA regulatory compliance',  sub: 'Fully licensed brokerage operations' },
    ]},
    { checks: [
        { label: 'Bulk order management',             sub: 'Minimum order flexibility for all buyers' },
        { label: 'Reliable daily inventory',          sub: 'Consistent supply every market day' },
        { label: 'Multi-port distribution hubs',      sub: 'Navotas, Malabon, Davao Toril & more' },
    ]},
  ];

  const tabs        = document.querySelectorAll('.tab-btn');
  const img         = document.getElementById('explore-active-img');
  const checklist   = document.getElementById('explore-checklist');
  const progressBar = document.getElementById('explore-progress-bar');

  // Active tab styles (applied via JS so Tailwind JIT isn't needed)
  const ACTIVE_TAB_CLS  = ['bg-gradient-to-br','from-orange-50','to-orange-100/40','border-orange-200','shadow-sm','is-active'];
  const ACTIVE_ICON_CLS = ['bg-grad','border-transparent','text-white','shadow-[0_4px_14px_rgba(249,115,22,.3)]'];
  const ACTIVE_ICON_REM = ['bg-slate-100','border-slate-100','text-slate-400'];
  const ACTIVE_NUM_CLS  = ['text-orange-300/50'];
  const ACTIVE_TITLE_CLS = ['!text-slate-900'];
  const ACTIVE_DESC_CLS  = ['!text-slate-500'];

  function renderChecklist(idx) {
    checklist.innerHTML = (tabData[idx]?.checks ?? []).map(item => `
      <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-[.875rem] bg-orange-50 border border-orange-100 hover:bg-orange-100 hover:border-orange-200 transition-colors">
        <span class="bg-grad flex items-center justify-center w-[22px] h-[22px] rounded-full shrink-0">
          <svg width="11" height="11" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        </span>
        <p class="text-[.8rem] text-slate-700 m-0 leading-snug"><strong class="text-slate-900">${item.label}</strong> — ${item.sub}</p>
      </div>
    `).join('');
  }

  function activateTab(tab) {
    const idx = parseInt(tab.dataset.index ?? '0', 10);

    // Reset all
    tabs.forEach(t => {
      t.classList.remove(...ACTIVE_TAB_CLS);
      t.setAttribute('aria-selected', 'false');
      const icon  = t.querySelector('.tab-icon');
      const num   = t.querySelector('.tab-num');
      const title = t.querySelector('.tab-title');
      const desc  = t.querySelector('.tab-desc');
      icon?.classList.remove(...ACTIVE_ICON_CLS);
      icon?.classList.add(...ACTIVE_ICON_REM);
      num?.classList.remove(...ACTIVE_NUM_CLS);
      title?.classList.remove(...ACTIVE_TITLE_CLS);
      desc?.classList.remove(...ACTIVE_DESC_CLS);
    });

    // Activate clicked
    tab.classList.add(...ACTIVE_TAB_CLS);
    tab.setAttribute('aria-selected', 'true');
    const icon  = tab.querySelector('.tab-icon');
    const num   = tab.querySelector('.tab-num');
    const title = tab.querySelector('.tab-title');
    const desc  = tab.querySelector('.tab-desc');
    icon?.classList.add(...ACTIVE_ICON_CLS);
    icon?.classList.remove(...ACTIVE_ICON_REM);
    num?.classList.add(...ACTIVE_NUM_CLS);
    title?.classList.add(...ACTIVE_TITLE_CLS);
    desc?.classList.add(...ACTIVE_DESC_CLS);

    // Swap image
    const newSrc = tab.dataset.img;
    if (newSrc && newSrc !== img.src) {
      img.classList.remove('entering');
      void img.offsetWidth;
      img.src     = newSrc;
      img.alt     = tab.dataset.alt || '';
      img.classList.add('entering');
      img.addEventListener('animationend', () => img.classList.remove('entering'), { once: true });
    }

    // Checklist + progress
    renderChecklist(idx);
    if (progressBar) progressBar.style.width = `${((idx + 1) / tabs.length) * 100}%`;
  }

  tabs.forEach(tab => tab.addEventListener('click', () => activateTab(tab)));

  // Init
  activateTab(tabs[0]);
})();
</script>
</body>
</html>