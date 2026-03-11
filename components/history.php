<!-- ═══════════════════════════════════════
       ABOUT HERO HEADER
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden bg-white dot-grid">

    <div class="pointer-events-none absolute -top-24 -right-32 w-[480px] h-[480px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.11)_0%,transparent_70%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-20 w-[320px] h-[320px] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,.08)_0%,transparent_70%)]" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-12">
      <div class="grid lg:grid-cols-2 gap-10 items-center">

        <!-- Left: copy -->
        <div data-aos="fade-right" data-aos-duration="700">

          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-5">
            <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
            Established 1988 · Philippines
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
              'Founded 1988',
              '40+ Years Experience',
              '32 Brokerage Stalls',
              'Navotas · Malabon · Davao',
              'BFAR Licensed',
            ];
            foreach ($pills as $p): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-xs font-semibold">
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                <?= $p ?>
              </span>
            <?php endforeach; ?>
          </div>

          <div class="flex flex-wrap gap-3">
            <a href="services.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-grad shadow-[0_4px_16px_rgba(249,115,22,.28)] hover:-translate-y-0.5 transition-all duration-200">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              Shop seafood
            </a>
            <a href="contact.php" class="cursor-pointer inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-orange-600 border border-orange-200 hover:bg-orange-50 hover:-translate-y-px transition-all duration-200">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Contact Us
            </a>
          </div>
        </div>

        <!-- Right: hero image + badges -->
        <div class="relative" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">

          <div class="relative rounded-2xl overflow-hidden shadow-[0_24px_64px_rgba(249,115,22,.16),0_4px_16px_rgba(15,23,42,.08)] group">
            <img
              src="./assets/images/contents/about_2.jpg"
              alt="About St. Joseph Fish Brokerage Inc. — Navotas Fish Port Complex"
              class="w-full h-72 lg:h-80 object-cover block transition-transform duration-700 group-hover:scale-[1.03]"
              loading="eager" width="640" height="320">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-orange-900/20 to-transparent"></div>
          </div>

          <!-- Floating: Est. badge -->
          <div class="float-anim absolute -bottom-5 -left-5 z-10 flex items-center gap-3 bg-white border border-orange-200 rounded-xl px-4 py-3 shadow-[0_8px_32px_rgba(249,115,22,.2)] whitespace-nowrap">
            <span class="bg-grad flex items-center justify-center w-9 h-9 rounded-xl shrink-0">
              <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="3"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </span>
            <div>
              <strong class="block text-[.8rem] font-bold text-slate-900 leading-tight">Est. 1988</strong>
              <span class="text-[.68rem] text-slate-400">Over 40 years of service</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Stat strip -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-12" data-aos="fade-up" data-aos-delay="150">
        <?php
        $stats = [
          ['num'=>'1975', 'label'=>'Business Started'],
          ['num'=>'1988', 'label'=>'Officially Incorporated'],
          ['num'=>'32',   'label'=>'Brokerage Stalls'],
          ['num'=>'500+', 'label'=>'Verified Partners'],
        ];
        foreach ($stats as $s): ?>
          <div class="text-center py-5 px-4 bg-orange-50 border border-orange-200 rounded-2xl hover:bg-orange-100 transition-colors">
            <div class="ff-display text-3xl font-bold text-grad leading-none mb-2"><?= $s['num'] ?></div>
            <div class="text-[.73rem] font-semibold text-slate-500 tracking-wide"><?= $s['label'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
  <!-- /hero -->

  <!-- ═══════════════════════════════════════
       OUR STORY — HISTORY SECTION
  ═══════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-white" id="ourstory-section">
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">

      <!-- Section label -->
      <div class="text-center mb-14" data-aos="fade-up">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-4">
          <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
          Our Story
        </div>
        <h2 class="ff-display text-3xl lg:text-4xl font-bold text-slate-900 leading-tight">
          A Legacy Built on <span class="text-grad">Hard Work</span>
        </h2>
        <p class="text-slate-400 text-sm mt-3 max-w-xl mx-auto">
          From a small fish stall in 1975 to the Philippines' leading fish brokerage network — this is our story.
        </p>
      </div>

      <!-- Intro text + image -->
      <div class="grid lg:grid-cols-2 gap-14 items-center mb-20">

        <div data-aos="fade-right" data-aos-duration="800">
          <p class="text-slate-600 leading-relaxed mb-5">
            <span class="font-semibold text-slate-900">St. Joseph Fish Brokerage, Inc. (SJFB)</span> is a trusted fish brokerage and seafood trading company in the Philippines, with over four decades of industry experience. Operating in major fish ports such as Navotas Fish Port Complex, Malabon Bayan Market, and Davao Fish Port Complex, SJFB provides reliable <a href="<?= $baseUrl ?>services" class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-2 hover:decoration-orange-500 transition-colors">fish brokerage services</a> to traders, suppliers, and buyers nationwide.
          </p>
          <p class="text-slate-600 leading-relaxed">
            Founded as a family-owned business and officially established in 1988, SJFB continues to grow through integrity, strong partnerships, and a deep commitment to the Philippine seafood industry.
          </p>

          <!-- Inline highlights -->
          <div class="flex flex-col gap-3 mt-8">
            <?php
            $highlights = [
              ['icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z M9 12l2 2 4-4', 'text'=>'BFAR & FDA licensed brokerage operations'],
              ['icon'=>'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', 'text'=>'27 stalls in Navotas · 5 in Malabon · Davao'],
              ['icon'=>'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'text'=>'Family-owned, trusted by 500+ partner fishermen'],
            ];
            foreach ($highlights as $h): ?>
              <div class="flex items-center gap-3 px-4 py-3 bg-orange-50 border border-orange-100 rounded-xl hover:border-orange-200 transition-colors">
                <span class="bg-grad flex items-center justify-center w-8 h-8 rounded-lg shrink-0">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2" aria-hidden="true"><path d="<?= $h['icon'] ?>"/></svg>
                </span>
                <p class="text-[.82rem] text-slate-700 font-medium"><?= $h['text'] ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="relative" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">
          <!-- Corner accents -->
          <div class="pointer-events-none absolute -top-3 -left-3 w-10 h-10 border-t-[3px] border-l-[3px] border-orange-300/50 rounded-md" aria-hidden="true"></div>
          <div class="pointer-events-none absolute -bottom-3 -right-3 w-10 h-10 border-b-[3px] border-r-[3px] border-orange-300/50 rounded-md" aria-hidden="true"></div>
          <div class="rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(249,115,22,.14),0_4px_16px_rgba(15,23,42,.07)] group">
            <img
              src="./assets/images/contents/about_3.jpg"
              alt="St. Joseph Fish Brokerage stall at Navotas Fish Port Complex"
              loading="lazy"
              class="w-full h-[380px] object-cover block transition-transform duration-700 group-hover:scale-[1.03]">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-orange-900/15 to-transparent"></div>
          </div>
        </div>
      </div>

    </div>
  </section>
  <!-- /our story -->