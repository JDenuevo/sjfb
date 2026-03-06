<?php
session_start();
include 'conn.php';
$pageTitle = 'Sustainability';
$metaDescription = 'St. Joseph Fish Brokerage Inc. is committed to responsible fish brokerage, sustainable seafood practices, supporting Filipino fishing communities, and ethical operations across Philippine fish ports.';
$ogImage = 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1200&q=80';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth">
<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $pageTitle ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="<?= $metaDescription ?>">
  <meta property="og:type" content="website"><meta property="og:url" content="https://fishbrokers.net/sustainability">
  <meta property="og:title" content="Sustainability | St. Joseph Fish Brokerage Inc."><meta property="og:description" content="<?= $metaDescription ?>">
  <meta property="og:image" content="<?= $ogImage ?>"><meta name="twitter:card" content="summary_large_image">
  <link rel="canonical" href="https://fishbrokers.net/sustainability">
  <link rel="shortcut icon" href="./assets/icons/logo.ico"><link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link href="style.css" rel="stylesheet"><link href="output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <style>
    /* Only truly custom styles — hero, animations, brand accents */
    body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    .sustain-hero { position:relative; height:90vh; min-height:600px; overflow:hidden; display:flex; align-items:center; }
    .sustain-hero__bg { position:absolute; inset:0; background:url('https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1920&q=85') center/cover; transform:scale(1.05); transition:transform 8s ease-out; }
    .sustain-hero__bg.loaded { transform:scale(1); }
    .sustain-hero__overlay { position:absolute; inset:0; background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%); opacity:.88; }

    .section-eyebrow { display:inline-flex; align-items:center; gap:.5rem; font-size:.75rem; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:#fb923c; margin-bottom:.75rem; }
    .section-eyebrow::before { content:''; display:block; width:2rem; height:2px; background:#fb923c; }

    /* Pillar card accent line */
    .pillar-card { position:relative; overflow:hidden; }
    .pillar-card::after { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; transform:scaleX(0); transition:transform .3s ease; }
    .pillar-card:hover::after { transform:scaleX(1); }
    .pillar-card--blue::after  { background:#fb923c; }
    .pillar-card--green::after { background:#22c55e; }
    .pillar-card--teal::after  { background:#14b8a6; }
    .pillar-card--orange::after{ background:#f97316; }

    /* Check item */
    .check-item { display:flex; align-items:flex-start; gap:.75rem; padding:.875rem 1rem; border-radius:.75rem; background:rgba(26,111,168,.04); border-left:3px solid transparent; transition:all .2s; }
    .check-item:hover { background:rgba(26,111,168,.08); border-left-color:#fb923c; }
    .check-dot { width:1.5rem; height:1.5rem; border-radius:50%; background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }

    /* Timeline */
    .timeline-item { position:relative; padding-left:2.5rem; }
    .timeline-item:not(:last-child)::before { content:''; position:absolute; left:.65rem; top:1.75rem; bottom:-1rem; width:2px; background:linear-gradient(to bottom,#fb923c,transparent); }
    .timeline-dot { position:absolute; left:0; top:.25rem; width:1.375rem; height:1.375rem; border-radius:50%; background:white; border:3px solid #fb923c; box-shadow:0 0 0 4px rgba(251,146,60,.15); }

    /* Deep section number */
    .deep-section { scroll-margin-top:80px; }
    .deep-num { font-family:'Playfair Display',serif; font-size:8rem; line-height:1; opacity:.06; position:absolute; top:-1rem; left:-1rem; color:#f97316; font-weight:700; pointer-events:none; }

    /* Image hover zoom */
    .img-zoom { position:relative; overflow:hidden; border-radius:1.5rem; }
    .img-zoom img { width:100%; height:100%; object-fit:cover; transition:transform .6s ease; }
    .img-zoom:hover img { transform:scale(1.03); }
    @media (min-width:1024px) {
      .img-shadow-r { box-shadow:24px 24px 0 rgba(26,111,168,.1); }
      .img-shadow-l { box-shadow:-24px 24px 0 rgba(26,122,94,.1); }
    }

    /* Counter */
    .impact-num { font-family:'Playfair Display',serif; font-size:3.5rem; font-weight:700; background:linear-gradient(135deg,#fff,rgba(255,255,255,.7)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

    /* CTA bg */
    .cta-bg { position:absolute; inset:0; background:url('./assets/images/contents/sustainability_2.png') center/cover; filter:brightness(.35); }
    .quote-mark { font-family:'Playfair Display',serif; font-size:20rem; position:absolute; top:-4rem; left:2rem; color:rgba(26,111,168,.06); line-height:1; pointer-events:none; }
  </style>
</head>
<body class="bg-white">
<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- HERO -->
<section class="sustain-hero">
  <div class="sustain-hero__bg" id="heroBg"></div>
  <div class="sustain-hero__overlay"></div>
  <div class="relative z-10 w-full max-w-5xl mx-auto px-6 text-white">
    <div data-aos="fade-up" data-aos-duration="800">
      <span class="inline-block text-xs font-bold tracking-widest uppercase text-emerald-300 mb-4">Our Commitment</span>
      <h1 class="font-display text-5xl md:text-7xl font-bold leading-tight mb-6 max-w-3xl">
        Sustaining the Sea,<br><em class="not-italic text-emerald-300">Sustaining Lives</em>
      </h1>
      <p class="text-lg md:text-xl text-white/80 max-w-2xl leading-relaxed mb-8">
        At St. Joseph Fish Brokerage Inc., sustainability isn't a policy — it's how we've always done business.
      </p>
      <div class="flex flex-wrap gap-3">
        <a href="#pillars" class="py-3 px-5 inline-flex items-center gap-2 rounded-xl bg-white text-orange-600 font-semibold text-sm hover:bg-orange-50 transition-all hover:-translate-y-0.5 shadow-md">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
          Explore Our Commitments
        </a>
        <a href="/contact" class="py-3 px-5 inline-flex items-center gap-2 rounded-xl border border-white/40 text-white font-semibold text-sm hover:bg-white/10 transition-all hover:-translate-y-0.5">
          Partner With Us
        </a>
      </div>
    </div>
  </div>
  <div class="absolute bottom-0 left-0 right-0 z-10">
    <svg viewBox="0 0 1440 80" fill="none" preserveAspectRatio="none" style="width:100%;display:block">
      <path d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,50 1440,40 L1440,80 L0,80 Z" fill="white"/>
    </svg>
  </div>
</section>

<!-- INTRO + SDG BADGES -->
<section class="py-20 bg-white">
  <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
    <span class="section-eyebrow justify-center">Who We Are</span>
    <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">
      More Than a Fish Broker — A Partner in the Philippine Seafood Ecosystem
    </h2>
    <p class="text-lg text-gray-600 leading-relaxed mb-8">
      With operations spanning <strong class="text-blue-700">Navotas</strong>, <strong class="text-blue-700">Malabon</strong>, <strong class="text-blue-700">Davao Toril</strong>, and growing, we serve as the critical link between hardworking Filipino fishermen and the buyers who depend on fresh, traceable seafood.
    </p>
    <!-- Preline badge pattern: inline-flex + rounded-full + color utilities -->
    <div class="flex flex-wrap gap-2 justify-center mt-2">
      <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">🌱 SDG 14 — Life Below Water</span>
      <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">🤝 SDG 17 — Partnerships</span>
      <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200">🏘️ SDG 8 — Decent Work</span>
      <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">⚖️ SDG 10 — Reduced Inequalities</span>
    </div>
  </div>
</section>

<!-- PILLARS -->
<section id="pillars" class="py-20 bg-slate-50">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-14" data-aos="fade-up">
      <span class="section-eyebrow justify-center">Our Framework</span>
      <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Four Pillars of Sustainable Practice</h2>
      <p class="text-gray-500 mt-3 max-w-xl mx-auto">Each pillar represents a core area where we measure, improve, and remain accountable.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php
      $pillars = [
        ['href'=>'#responsible-brokerage','mod'=>'--blue','ibg'=>'bg-blue-50','ic'=>'text-blue-600','lc'=>'text-blue-600','title'=>'Responsible Brokerage','desc'=>'Ethical trading, regulatory compliance, and full traceability from catch to buyer.'],
        ['href'=>'#people-workplace','mod'=>'--green','ibg'=>'bg-emerald-50','ic'=>'text-emerald-600','lc'=>'text-emerald-600','title'=>'People & Workplace','desc'=>'Fair wages, safe conditions, inclusive culture, and continuous employee growth.'],
        ['href'=>'#community-livelihoods','mod'=>'--teal','ibg'=>'bg-teal-50','ic'=>'text-teal-600','lc'=>'text-teal-600','title'=>'Community & Livelihoods','desc'=>'Direct support for fishing families, local suppliers, and coastal communities.'],
        ['href'=>'#environmental-responsibility','mod'=>'--orange','ibg'=>'bg-orange-50','ic'=>'text-orange-600','lc'=>'text-orange-600','title'=>'Environmental Stewardship','desc'=>'Mindful operations, waste reduction, and support for marine conservation efforts.'],
      ];
      $icons = [
        'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z',
        'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
      ];
      foreach ($pillars as $i => $p):
      ?>
      <!-- Preline-style card: bg-white rounded-2xl border shadow-sm hover:shadow-xl transition-all -->
      <a href="<?= $p['href'] ?>" class="pillar-card pillar-card<?= $p['mod'] ?> block bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group" data-aos="fade-up" data-aos-delay="<?= $i*100 ?>">
        <div class="size-16 <?= $p['ibg'] ?> rounded-2xl flex items-center justify-center mb-4 transition-transform group-hover:scale-105">
          <svg class="size-7 <?= $p['ic'] ?>" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icons[$i] ?>"/></svg>
        </div>
        <h3 class="font-bold text-gray-900 mb-2"><?= $p['title'] ?></h3>
        <p class="text-sm text-gray-500 leading-relaxed"><?= $p['desc'] ?></p>
        <span class="mt-4 inline-flex items-center <?= $p['lc'] ?> text-xs font-semibold gap-1">
          Learn more <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DEEP DIVE 1 -->
<section id="responsible-brokerage" class="deep-section py-24 bg-white">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-10 items-center">
      <div class="img-zoom img-shadow-r aspect-[4/3]" data-aos="fade-right">
        <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=800&q=80" alt="Fish brokerage operations" loading="lazy">
      </div>
      <div data-aos="fade-left" data-aos-delay="100">
        <div class="relative">
          <div class="deep-num">01</div>
          <span class="section-eyebrow">Pillar One</span>
          <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">Responsible Fish Brokerage</h2>
          <p class="text-gray-600 leading-relaxed mb-6">As a licensed fish broker, we ensure every transaction is fair, transparent, and legally compliant. Our reputation across three decades is built on integrity.</p>
          <div class="space-y-3">
            <?php foreach ([
              ['Fair-price guarantee','fishermen receive market-competitive rates with no hidden deductions'],
              ['Full regulatory compliance','licensed under BFAR, FDA-registered, HACCP compliant'],
              ['Supply chain transparency','traceable documentation from catch origin to end buyer'],
              ['Anti-illegal fishing stance','we verify all suppliers and refuse products from illegal sources'],
            ] as $item): ?>
            <div class="check-item">
              <div class="check-dot"><svg class="size-3" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>
              <p class="text-sm text-gray-600"><strong class="text-gray-800"><?= $item[0] ?></strong> — <?= $item[1] ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DEEP DIVE 2 -->
<section id="people-workplace" class="deep-section py-24 bg-slate-50">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-10 items-center">
      <div data-aos="fade-right">
        <div class="deep-num">02</div>
        <span class="section-eyebrow">Pillar Two</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">People & Workplace</h2>
        <p class="text-gray-600 leading-relaxed mb-8">Our people are the backbone of St. Joseph. Every team member is treated with dignity, compensated fairly, and given the tools to grow.</p>
        <div class="space-y-6">
          <?php foreach ([
            ['Safe Working Conditions','PPE provided to all port-side staff; regular safety audits at all locations.'],
            ['Good Compensation','Salaries regional minimum wage; 13th month, SSS, PhilHealth, Pag-IBIG covered.'],
            ['Career Development','In-house training, fisheries knowledge programs, and internal promotion pathways.'],
            ['Inclusive Culture','Equal opportunity employer — we value diversity in gender, background, and experience.'],
          ] as $item): ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <h4 class="font-semibold text-gray-900 mb-1"><?= $item[0] ?></h4>
            <p class="text-sm text-gray-500"><?= $item[1] ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="img-zoom img-shadow-l aspect-[4/3]" data-aos="fade-left">
        <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=80" alt="St. Joseph team" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- DEEP DIVE 3 -->
<section id="community-livelihoods" class="deep-section py-24 bg-white">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-10 items-center">
      <div class="img-zoom img-shadow-r aspect-[4/3]" data-aos="fade-right">
        <img src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&q=80" alt="Filipino fishing community" loading="lazy">
      </div>
      <div data-aos="fade-left" data-aos-delay="100">
        <div class="deep-num">03</div>
        <span class="section-eyebrow">Pillar Three</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">Community & Livelihoods</h2>
        <p class="text-gray-600 leading-relaxed mb-6">Fishing communities around Navotas, Malabon, and Davao aren't just our supply chain — they are our neighbors and partners.</p>
        <div class="space-y-3">
          <?php foreach ([
            ['Priority local sourcing','95%+ of supply comes from registered small-scale and artisanal fishermen'],
            ['Advance payment program','helps fishermen cover fuel and equipment costs before catch'],
            ['Community scholarships','supporting education for children of long-term supplier families'],
            ['Market access programs','connecting small suppliers to institutional buyers they couldn\'t reach alone'],
          ] as $item): ?>
          <div class="check-item" style="border-left-color:transparent">
            <div class="check-dot" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%);"><svg class="size-3" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>
            <p class="text-sm text-gray-600"><strong class="text-gray-800"><?= $item[0] ?></strong> — <?= $item[1] ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DEEP DIVE 4 -->
<section id="environmental-responsibility" class="deep-section py-24 bg-slate-50">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-10 items-center">
      <div data-aos="fade-right">
        <div class="deep-num">04</div>
        <span class="section-eyebrow">Pillar Four</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">Environmental Stewardship</h2>
        <p class="text-gray-600 leading-relaxed mb-6">The ocean is not a resource to exploit — it's a living system that sustains millions of Filipinos.</p>
        <!-- Preline-style mini cards: bg-white rounded-xl border p-4 -->
        <div class="grid sm:grid-cols-2 gap-4">
          <?php foreach ([
            ['bg-blue-50','text-blue-600','Closed Season Compliance','We halt trading of regulated species during mandated closed seasons.','M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
            ['bg-green-50','text-green-600','Zero-Waste Drive','Fish scraps and unsold portions redirect to fishmeal processors — nothing wasted.','M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
            ['bg-teal-50','text-teal-600','Biodiversity Awareness','Staff trained to refuse endangered or undersize catches per BFAR guidelines.','M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064'],
            ['bg-orange-50','text-orange-600','Cold Chain Efficiency','Optimized ice and refrigeration logistics reduce spoilage and energy use.','M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707'],
          ] as [$ibg,$ic,$title,$desc,$path]): ?>
          <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="size-10 <?= $ibg ?> rounded-lg flex items-center justify-center mb-3">
              <svg class="size-5 <?= $ic ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $path ?>"/></svg>
            </div>
            <h4 class="font-semibold text-gray-900 text-sm mb-1"><?= $title ?></h4>
            <p class="text-xs text-gray-500"><?= $desc ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="img-zoom img-shadow-l aspect-[4/3]" data-aos="fade-left">
        <img src="https://images.unsplash.com/photo-1518020382113-a7e8fc38eac9?w=800&q=80" alt="Marine conservation" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- IMPACT NUMBERS -->
<section class="relative py-24 overflow-hidden" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%);">
  <div class="relative z-10 max-w-5xl mx-auto px-6">
    <div class="text-center mb-14" data-aos="fade-up">
      <span class="text-xs font-bold tracking-widest uppercase text-emerald-300">By The Numbers</span>
      <h2 class="font-display text-3xl md:text-4xl font-bold text-white mt-2">Our Impact at a Glance</h2>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center" id="impact-section">
      <?php foreach ([['32','+','Brokerage Stalls','Nationwide'],['3','','Major Fish Ports','Luzon to Mindanao'],['100','+','Supplier Partners','Local Fishermen'],['30','+','Years of Service','Since the 90s']] as $i=>[$n,$s,$l,$sub]): ?>
      <div data-aos="fade-up" data-aos-delay="<?= $i*100 ?>">
        <div class="impact-num" data-target="<?= $n ?>" data-suffix="<?= $s ?>">0<?= $s ?></div>
        <p class="text-white/70 text-sm mt-2 font-medium"><?= $l ?></p>
        <p class="text-white/40 text-xs mt-1"><?= $sub ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- QUOTE -->
<section class="relative py-24 bg-amber-50 overflow-hidden">
  <div class="quote-mark">"</div>
  <div class="relative z-10 max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
    <p class="font-display text-2xl md:text-3xl italic text-gray-700 leading-relaxed mb-8">
      "Sustainability is not a destination — it is a commitment we live every day through responsible business, trusted relationships, and continuous improvement for the Filipino fishing industry."
    </p>
    <div class="flex items-center justify-center gap-3">
      <div class="w-12 h-px bg-gray-300"></div>
      <span class="text-sm font-semibold text-gray-500">St. Joseph Fish Brokerage, Inc.</span>
      <div class="w-12 h-px bg-gray-300"></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="relative py-20 overflow-hidden">
  <div class="absolute inset-0 z-0">
    <div class="cta-bg"></div>
  </div>
  
  <div class="relative z-10 max-w-3xl mx-auto px-6 text-center text-white" data-aos="fade-up">
    <span class="text-xs font-bold tracking-widest uppercase text-orange-300 block mb-4">Work With Us</span>
    <h2 class="font-display text-3xl md:text-5xl font-bold mb-6">Building a Sustainable Future — Together</h2>
    <p class="text-lg text-white/75 mb-10 leading-relaxed">Whether you're a buyer, a cooperative, or a business partner — St. Joseph is ready.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="./services.php" class="py-3 px-6 inline-flex items-center justify-center rounded-xl bg-white text-orange-600 font-semibold hover:bg-orange-50 transition-all hover:-translate-y-0.5 shadow-md">Explore Our Markets</a>
      <a href="./contact.php" class="py-3 px-6 inline-flex items-center justify-center rounded-xl border border-white/40 text-white font-semibold hover:bg-white/10 transition-all hover:-translate-y-0.5">Contact Us Today</a>
    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>
<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>
<script>
AOS.init({ once:true, easing:'ease-out-cubic' });
window.addEventListener('load', () => document.getElementById('heroBg')?.classList.add('loaded'));
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); window.scrollTo({ top: t.offsetTop - 80, behavior:'smooth' }); }
  });
});
function animateCounter(el) {
  const target = parseInt(el.dataset.target), suffix = el.dataset.suffix || '';
  let cur = 0, step = Math.max(1, Math.floor(target/40));
  const t = setInterval(() => { cur = Math.min(cur+step,target); el.textContent=cur+suffix; if(cur>=target)clearInterval(t); }, 40);
}
new IntersectionObserver(entries => {
  entries.forEach(e => { if(e.isIntersecting) { e.target.querySelectorAll('.impact-num').forEach(animateCounter); } });
}, { threshold:0.3 }).observe(document.getElementById('impact-section'));
</script>
</body>
</html>