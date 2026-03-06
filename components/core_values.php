<!-- ═══════════════════════════════════════
       CORE VALUES SECTION
  ═══════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-orange-50 border-y border-orange-200" id="corevalues-section">
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">

      <!-- Section label -->
      <div class="text-center mb-14" data-aos="fade-up">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-4">
          <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
          What We Stand For
        </div>
        <h2 class="ff-display text-3xl lg:text-4xl font-bold text-slate-900 leading-tight">
          Our <span class="text-grad">Core Values</span>
        </h2>
        <p class="text-slate-400 text-sm mt-3">The principles that guide every decision we make.</p>
      </div>

      <!-- DESKTOP: Stair layout -->
      <div class="core-values-stairs grid lg:grid-cols-2 items-start gap-10 px-4">

        <!-- Left: dynamic content -->
        <div data-aos="fade-right">
          <div id="core-value-content" class="min-h-[220px]">
            <?php
            $coreValues = [
              ['id'=>'iam',          'key'=>'I AM',             'value'=>'Integrity',   'desc'=>'Doing the right thing at all times, even when no one is watching. We uphold honesty and strong moral principles in every action we take, ensuring that our word is our bond in the global marketplace.'],
              ['id'=>'imakeithappen','key'=>'I MAKE IT HAPPEN',  'value'=>'Commitment',  'desc'=>'We take ownership and responsibility to deliver results. Our dedication drives us to turn promises into reality, overcoming challenges with determination and perseverance.'],
              ['id'=>'biglove',      'key'=>'BIG LOVE',          'value'=>'Passion',     'desc'=>'We pour our hearts into everything we do. This passion fuels our creativity, energy, and dedication to serving our customers and community with genuine care and enthusiasm.'],
              ['id'=>'powerofone',   'key'=>'THE POWER OF ONE',  'value'=>'Unity',       'desc'=>'Every individual contributes to our collective success. We believe that one person can make a difference, and together, we can achieve extraordinary things through collaboration and teamwork.'],
              ['id'=>'livemark',     'key'=>'I LIVE MY MARK',    'value'=>'Excellence',  'desc'=>'We strive to leave a positive legacy in everything we do. Our actions today create lasting impact for tomorrow, inspiring others through our example of excellence and integrity.'],
              ['id'=>'ican',         'key'=>'I CAN',             'value'=>'Empowerment', 'desc'=>'We embrace a can-do attitude that turns challenges into opportunities. With confidence and capability, we believe in our ability to grow, adapt, and succeed in any situation.'],
            ];
            foreach ($coreValues as $idx => $cv): ?>
              <div id="content-<?= $cv['id'] ?>" class="core-content <?= $idx > 0 ? 'hidden' : '' ?>">
                <div class="mb-6">
                  <span class="inline-block bg-grad text-white text-[.68rem] font-bold px-3 py-1 rounded-full mb-3"><?= $cv['value'] ?></span>
                  <h3 class="ff-display text-4xl font-bold text-slate-900 leading-tight"><?= $cv['key'] ?></h3>
                </div>
                <p class="text-slate-500 leading-relaxed text-[.95rem] max-w-md"><?= $cv['desc'] ?></p>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Stats grid under content -->
          <div class="mt-10 grid grid-cols-2 gap-3 pt-8 border-t border-orange-200">
            <?php
            $cvStats = [
              ['icon'=>'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6', 'num'=>'40+',    'label'=>'Years of Excellence'],
              ['icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',                'num'=>'100%',   'label'=>'Quality Assured'],
              ['icon'=>'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z M2 12h20 M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10', 'num'=>'Global', 'label'=>'Supply Network'],
              ['icon'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75', 'num'=>'500+', 'label'=>'Trusted Partners'],
            ];
            foreach ($cvStats as $s): ?>
              <div class="flex items-center gap-3 p-4 bg-white border border-orange-100 rounded-2xl hover:border-orange-200 hover:shadow-sm transition-all">
                <span class="bg-grad flex items-center justify-center w-10 h-10 rounded-xl shrink-0">
                  <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2" aria-hidden="true"><path d="<?= $s['icon'] ?>"/></svg>
                </span>
                <div>
                  <strong class="block text-xl font-bold text-slate-900 leading-none"><?= $s['num'] ?></strong>
                  <span class="text-[.68rem] font-bold text-slate-400 uppercase tracking-wide"><?= $s['label'] ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right: stair cards -->
        <div class="flex flex-col items-end w-full" data-aos="fade-left">
          <img src="./assets/icons/logo.svg" class="w-16 h-16 hover:scale-110 duration-200 mb-4" alt="St. Joseph Fish Brokerage Inc. Logo">

          <?php
            $stairIcons = [
              'iam'          => 'M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3 M12 3v18 M3.5 12h17',
              'imakeithappen'=> 'M11 12a1 1 0 1 0 2 0a1 1 0 1 0 -2 0 M12 7a5 5 0 1 0 5 5 M13 3.055a9 9 0 1 0 7.941 7.945 M15 6v3h3l3 -3h-3v-3l-3 3 M15 9l-3 3',
              'biglove'      => 'M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572',
              'powerofone'   => 'M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873l-6.158 -3.245',
              'livemark'     => 'M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0 M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1',
              'ican'         => 'M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0 M9 12l2 2l4 -4',
            ];
            $bgImages = ['corebg_1','corebg_2','corebg_3','corebg_4','corebg_5','corebg_6'];
            foreach ($coreValues as $idx => $cv):
            $isFirst = $idx === 0;
          ?>
            <div class="w-fit core-value-card cursor-pointer <?= $isFirst ? 'active-card' : '' ?>" data-content="<?= $cv['id'] ?>">
              <div class="relative h-24">
                <img src="./assets/backdrops/<?= $bgImages[$idx] ?>.png" class="h-full w-auto block transition-opacity duration-300 <?= $isFirst ? 'opacity-100' : 'opacity-40' ?>">
                <div class="absolute inset-0 z-10 flex items-center justify-start ms-10">
                  <span class="text-slate-800 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="<?= $stairIcons[$cv['id']] ?>"/>
                    </svg>
                  </span>
                  <div class="ms-2.5">
                    <h4 class="font-bold text-base leading-tight uppercase text-slate-900 whitespace-nowrap"><?= $cv['key'] ?></h4>
                    <p class="text-sm font-semibold text-orange-600 whitespace-nowrap"><?= $cv['value'] ?></p>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- MOBILE: Accordion -->
      <div class="core-values-accordion">
        <div class="hs-accordion-group space-y-3">
          <?php foreach ($coreValues as $idx => $cv): ?>
            <div class="hs-accordion bg-white rounded-2xl border border-orange-100 hs-accordion-active:border-orange-400 hs-accordion-active:shadow-md transition-all duration-300" id="mobile-<?= $cv['id'] ?>">
              <button class="hs-accordion-toggle w-full px-5 py-4 text-left flex justify-between items-center group" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                <div class="flex items-center gap-3">
                  <div class="bg-grad flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-[0_4px_12px_rgba(249,115,22,.25)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="<?= $stairIcons[$cv['id']] ?>"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 uppercase leading-none tracking-tight text-[.88rem]"><?= $cv['key'] ?></h4>
                    <p class="text-xs font-bold text-orange-600 italic mt-0.5"><?= $cv['value'] ?></p>
                  </div>
                </div>
                <svg class="hs-accordion-active:rotate-45 w-5 h-5 text-orange-400 hs-accordion-active:text-orange-600 transition-transform duration-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
              </button>
              <div class="hs-accordion-content <?= $idx === 0 ? '' : 'hidden' ?> overflow-hidden transition-[height] duration-300">
                <div class="px-5 pb-5 pt-0 ml-14">
                  <p class="text-[.82rem] leading-relaxed text-slate-500 border-l-2 border-orange-200 pl-4">
                    <?= $cv['desc'] ?>
                  </p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </section>
  <!-- /core values -->