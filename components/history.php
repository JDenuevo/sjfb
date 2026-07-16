<style>
  /* Floating photo-collage system, reused from the homepage's
     network section so both pages share the same visual language. */
  .orbit-ring {
    position: absolute;
    border: 1.5px dashed rgba(251,146,60,.28);
    border-radius: 9999px;
  }
  .photo-card { will-change: transform; }

  @keyframes floatY {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
  }
  .float-wrap { animation: floatY 6s ease-in-out infinite; }
  .float-wrap:hover { animation-play-state: paused; }
</style>

<!-- ═══════════════════════════════════════
       ABOUT HERO HEADER
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden bg-white dot-grid">

    <div class="pointer-events-none absolute -top-24 -right-32 w-[480px] h-[480px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.11)_0%,transparent_70%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-20 w-[320px] h-[320px] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,.08)_0%,transparent_70%)]" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-16 lg:pb-12">
      <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">

        <!-- Left: copy -->
        <div data-aos="fade-right" data-aos-duration="700">

          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-5">
            <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
            Established 1979 · Philippines
          </div>

          <h1 class="ff-display text-4xl lg:text-5xl font-bold text-slate-900 leading-tight tracking-tight mb-5">
            The <em class="text-grad not-italic">Story</em> Behind the<br>Philippines' Most Trusted<br>Fish Broker
          </h1>

          <p class="text-slate-500 leading-relaxed max-w-lg mb-8">
            St. Joseph Fish Brokerage, Inc. (SJFB) has been connecting Filipino fishermen with buyers, traders, and markets for over four decades — built on integrity, hard work, and a deep love for the Philippine seafood industry.
          </p>

          <!-- Quick facts pills -->
          <div class="flex flex-wrap gap-3 mb-8">
            <?php
            $pills = [
              '40+ Years Experience',
              'Navotas · Malabon · Lucena · Davao',
              '26 Stalls',
              'Fresh produced Seafood',
            ];
            foreach ($pills as $p): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-xs font-semibold">
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                <?= $p ?>
              </span>
            <?php endforeach; ?>
          </div>

          <div class="flex flex-wrap gap-3">
            <a href="shop" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-grad shadow-[0_4px_16px_rgba(249,115,22,.28)] hover:-translate-y-0.5 transition-all duration-200">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              Shop seafood
            </a>
            <a href="contact" class="cursor-pointer inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-orange-600 border border-orange-200 hover:bg-orange-50 hover:-translate-y-px transition-all duration-200">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Contact Us
            </a>
          </div>
        </div>

        <!-- Right: floating photo collage -->
        <div class="relative min-h-[420px] sm:min-h-[520px] lg:min-h-[600px]" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">

          <!-- decorative orbit ring, desktop only -->
          <div class="orbit-ring hidden lg:block w-[420px] h-[420px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" aria-hidden="true"></div>

          <!-- Anchor photo -->
          <div class="float-wrap absolute top-0 right-0 w-[80%] h-[220px] sm:h-[260px] lg:h-[280px] z-20" style="animation-duration:6.4s;">
            <figure class="photo-card photo-slot w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[-2deg] transition-transform duration-500 hover:rotate-0 hover:scale-105">
              <img
                src="./assets/images/contents/about_2.jpg"
                alt="About St. Joseph Fish Brokerage Inc. — Navotas Fish Port Complex"
                class="w-full h-full object-cover"
                loading="eager"
                onerror="phFallback(this, 'Warehouse / stall exterior, golden hour')">
            </figure>
          </div>

          <!-- Satellite: portrait, bottom-left, overlapping the anchor -->
          <div class="float-wrap absolute bottom-4 left-0 w-[58%] sm:w-[54%] h-[190px] sm:h-[230px] lg:h-[260px] z-10" style="animation-duration:7.2s; animation-delay:.6s;">
            <figure class="photo-card photo-slot w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[4deg] transition-transform duration-500 hover:rotate-0 hover:scale-105">
              <img
                src="./assets/images/contents/about_4.jpg"
                alt="Team member selecting fresh seafood"
                class="w-full h-full object-cover"
                loading="lazy"
                onerror="phFallback(this, 'Employee selecting fresh seafood, candid')">
            </figure>
          </div>

          <!-- Satellite: small square, top-left, tucked behind the anchor's corner -->
          <div class="float-wrap absolute top-[-10px] left-[8%] w-[92px] h-[92px] sm:w-[120px] sm:h-[120px] z-30" style="animation-duration:6.8s; animation-delay:1.1s;">
            <figure class="photo-card photo-slot w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_20px_45px_rgba(15,23,42,.16)] rotate-[-8deg] transition-transform duration-500 hover:rotate-0 hover:scale-110">
              <img
                src="./assets/images/contents/about_1.jpg"
                alt="Close-up of fresh catch on ice"
                class="w-full h-full object-cover"
                loading="lazy"
                onerror="phFallback(this, 'Close-up of fresh catch on ice')">
            </figure>
          </div>

          <!-- Satellite: small square, bottom-right -->
          <div class="float-wrap absolute bottom-0 right-[2%] w-[100px] h-[100px] sm:w-[136px] sm:h-[136px] z-30" style="animation-duration:6.2s; animation-delay:1.6s;">
            <figure class="photo-card photo-slot w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_20px_45px_rgba(15,23,42,.16)] rotate-[7deg] transition-transform duration-500 hover:rotate-0 hover:scale-110">
              <img
                src="./assets/images/contents/about_5.jpg"
                alt="Loading a delivery truck with seafood orders"
                class="w-full h-full object-cover"
                loading="lazy"
                onerror="phFallback(this, 'Loading a delivery truck with seafood orders')">
            </figure>
          </div>

          <!-- Satellite: circular accent, mid-right, desktop/tablet only -->
          <div class="float-wrap absolute top-[40%] right-[-4%] w-[96px] h-[96px] z-20 hidden md:block" style="animation-duration:7s; animation-delay:.3s;">
            <figure class="photo-card photo-slot w-full h-full rounded-full overflow-hidden ring-4 ring-white shadow-[0_20px_45px_rgba(15,23,42,.16)] transition-transform duration-500 hover:scale-110">
              <img
                src="./assets/images/contents/about_3.jpg"
                alt="Staff member smiling at work"
                class="w-full h-full object-cover"
                loading="lazy"
                onerror="phFallback(this, 'Staff member smiling, candid portrait')">
            </figure>
          </div>

        </div>
      </div>

    </div>
  </div>
  <!-- /hero -->

  <style>
    /* Scoped to #ourstory-section — won't leak into other components */
    #ourstory-section .story-dropcap::first-letter {
      font-family: 'Playfair Display', Georgia, serif;
      font-weight: 700;
      font-size: 3.4rem;
      line-height: .8;
      float: left;
      padding: .08em .08em 0 0;
      color: #f97316;
    }
    #ourstory-section .story-ghost-year {
      font-family: 'Playfair Display', Georgia, serif;
      font-weight: 700;
      font-size: clamp(7rem, 20vw, 19rem);
      line-height: 1;
      color: transparent;
      -webkit-text-stroke: 1.5px rgba(251,146,60,.14);
      white-space: nowrap;
    }
  </style>

  <!-- ═══════════════════════════════════════
       OUR STORY — HISTORY SECTION
  ═══════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-white dot-grid" id="ourstory-section">

    <!-- Ghost year, sitting behind everything -->
    <div class="pointer-events-none absolute top-8 left-1/2 -translate-x-1/2 select-none z-0" aria-hidden="true">
      <span class="story-ghost-year">1979</span>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-4" data-aos="fade-right" data-aos-duration="800">
        <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
        Our Story
      </div>

      <div class="grid lg:grid-cols-[1.05fr_1fr] gap-14 lg:gap-20 items-start">

        <!-- Text column, laid out as a timeline: 1979 → Today -->
        <div class="relative pl-9 border-l-2 border-dashed border-orange-200" data-aos="fade-right" data-aos-duration="800">

          <!-- Marker: 1979 -->
          <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-grad ring-4 ring-white shadow-[0_2px_8px_rgba(249,115,22,.4)]" aria-hidden="true"></div>
          <span class="ff-display italic text-orange-500 text-sm tracking-wide">1979</span>

          <h2 class="ff-display text-3xl lg:text-4xl font-bold text-slate-900 leading-tight mt-2 mb-6">
            A Legacy Built on <span class="text-grad">Hard Work</span>
          </h2>

          <p class="story-dropcap text-slate-600 leading-relaxed mb-5">
            <span class="font-semibold text-slate-900">St. Joseph Fish Brokerage, Inc. (SJFB)</span> is a trusted fish brokerage and seafood trading company in the Philippines, with over four decades of industry experience. Operating in major fish ports such as Navotas Fish Port Complex, Malabon Bayan Market, Lucena Fish Port Complex, and Davao Fish Port Complex, SJFB provides reliable <a href="<?= $baseUrl ?>services" class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-2 hover:decoration-orange-500 transition-colors">fish brokerage services</a> to traders, suppliers, and buyers nationwide.
          </p>
          <p class="text-slate-600 leading-relaxed">
            Founded as a family-owned business and officially established in 1979, SJFB continues to grow through integrity, strong partnerships, and a deep commitment to the Philippine seafood industry.
          </p>

          <!-- Marker: Today -->
          <div class="relative mt-12 pt-1">
            <div class="absolute -left-[41px] top-2 w-4 h-4 rounded-full bg-white border-2 border-orange-400" aria-hidden="true"></div>
            <span class="ff-display italic text-slate-400 text-sm tracking-wide">Today</span>
            <p class="ff-display text-lg text-slate-900 font-semibold mt-1">
              40+ years later — still hand-picking every batch ourselves.
            </p>
          </div>
        </div>

        <!-- Photo column, with a stamp badge instead of plain corner accents -->
        <div class="relative" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">

          <div class="absolute -top-7 -left-7 z-20 w-[92px] h-[92px] rounded-full border-2 border-dashed border-orange-400 bg-white flex items-center justify-center -rotate-12 shadow-[0_8px_20px_rgba(15,23,42,.1)]" aria-hidden="true">
            <span class="ff-display text-[9.5px] font-bold uppercase tracking-wide text-orange-600 text-center leading-tight">
              Est.<br>1979<br>Navotas
            </span>
          </div>

          <div class="rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(249,115,22,.14),0_4px_16px_rgba(15,23,42,.07)] rotate-[1.5deg] transition-transform duration-500 hover:rotate-0 group">
            <img
              src="./assets/images/contents/about_6.jpg"
              alt="St. Joseph Fish Brokerage stall at Navotas Fish Port Complex"
              loading="lazy"
              class="w-full h-[380px] object-cover block transition-transform duration-700 group-hover:scale-[1.04]">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-orange-900/15 to-transparent"></div>
          </div>
        </div>

      </div>

    </div>
  </section>
  <!-- /our story -->