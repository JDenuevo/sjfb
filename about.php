<?php
session_start();
include 'conn.php';

$pageTitle = 'About';
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth"> 

<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">

  <title>About Us | St. Joseph Fish Brokerage Inc. — Leading Fish Broker in the Philippines</title>
  <meta name="description" content="Learn about St. Joseph Fish Brokerage Inc. — a trusted fish brokerage and seafood trading company in the Philippines with over 40 years of industry experience operating at Navotas Fish Port Complex.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/about">
  <meta property="og:title" content="About St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Over 40 years of trusted fish brokerage in the Philippines. Operating at Navotas, Malabon, and Davao Fish Port.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="About St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Over 40 years of trusted fish brokerage in the Philippines.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": "https://fishbrokers.net/#organization",
    "name": "St. Joseph Fish Brokerage Inc.",
    "url": "https://fishbrokers.net",
    "foundingDate": "1988",
    "description": "Leading fish brokerage and seafood trading company in the Philippines with over 40 years of experience.",
    "areaServed": "Philippines",
    "telephone": "(+63) 946-497-3689"
  }
  </script>

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
</head>

<style>
  :root { --grad-orange: linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%); }

  body { font-family: 'Lexend', sans-serif; }
  .ff-display { font-family: 'Playfair Display', Georgia, serif; }

  .text-grad {
    background: var(--grad-orange);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .bg-grad { background: var(--grad-orange); }

  .dot-grid::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(249,115,22,.05) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none;
    z-index: 0;
  }

  @keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(.7); }
  }
  .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

  @keyframes float-y {
    0%,100% { transform:translateY(0); }
    50%      { transform:translateY(-6px); }
  }
  .float-anim   { animation: float-y 3.5s ease-in-out infinite; }
  .float-anim-2 { animation: float-y 4s ease-in-out infinite .8s; }

  /* Timeline */
  .timeline-line {
    position: absolute;
    left: 50%;
    top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, transparent, #fed7aa 10%, #f97316 50%, #fed7aa 90%, transparent);
    transform: translateX(-50%);
  }
  @media (max-width: 768px) {
    .timeline-line { left: 20px; }
  }

  /* Core values desktop/mobile toggle */
  @media (max-width: 960px) { .core-values-stairs { display: none; } }
  @media (min-width: 960px) { .core-values-accordion { display: none; } }
</style>

<body>

  <?php include('./components/preloaders.php'); ?>
  <?php include('./components/navigation.php'); ?>
  <?php include('./components/nav_crumb.php'); ?>

  <div class="mt-2" data-aos="fade-down" data-aos-duration="700">
    <img src="./assets/images/contents/about_1.png" 
      alt="About Banner St. Joseph Fish Brokerage Inc." 
      loading="eager" 
      class="w-full h-auto object-cover">
  </div>

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

  <!-- ═══════════════════════════════════════
       CORE VALUES
  ═══════════════════════════════════════ -->
  <div class="">
    <?php include('./components/core_values.php'); ?>
  </div>

  <!-- ═══════════════════════════════════════
       COMPANY EVENTS
  ═══════════════════════════════════════ -->
  <div class="">
    <?php include('./components/company_events.php'); ?>
  </div>

  <!-- ═══════════════════════════════════════
       CTA STRIP
  ═══════════════════════════════════════ -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" data-aos="fade-up">
    <div class="relative flex flex-wrap items-center justify-between gap-5 px-8 py-7 bg-white border border-orange-200 rounded-2xl shadow-sm overflow-hidden">
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-grad rounded-l-2xl" aria-hidden="true"></div>
      <div>
        <h3 class="ff-display text-xl font-bold text-slate-900 mb-1">Ready to work with the Philippines' most trusted fish broker?</h3>
        <p class="text-[.85rem] text-slate-500">Wholesale, retail, or partnership inquiries — we'd love to hear from you.</p>
      </div>
      <div class="flex gap-3 flex-wrap">
        <a href="shop.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-[.84rem] font-semibold text-white bg-grad shadow-[0_4px_16px_rgba(249,115,22,.28)] hover:-translate-y-0.5 transition-all duration-200">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Shop Seafood
        </a>
        <a href="contact.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-[.84rem] font-semibold text-orange-600 border border-orange-200 hover:bg-orange-50 hover:-translate-y-px transition-all duration-200">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Get in Touch
        </a>
      </div>
    </div>
  </div>

  <?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true, easing: 'ease-out-cubic' });</script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const cards       = document.querySelectorAll('.core-value-card');
    const contentDivs = document.querySelectorAll('.core-content');

    cards.forEach(card => {
      card.addEventListener('click', function () {
        const id = this.dataset.content;

        // Swap content
        contentDivs.forEach(d => d.classList.add('hidden'));
        document.getElementById('content-' + id)?.classList.remove('hidden');

        // Swap opacity
        cards.forEach(c => {
          const img = c.querySelector('img');
          if (!img) return;
          if (c === this) {
            img.classList.replace('opacity-40', 'opacity-100');
            c.classList.add('active-card');
          } else {
            img.classList.replace('opacity-100', 'opacity-40');
            c.classList.remove('active-card');
          }
        });
      });
    });
  });
  </script>

  <?php include('live_chat.php'); ?>

</body>
</html>