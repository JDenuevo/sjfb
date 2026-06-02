<?php
session_start();
include 'conn.php';

$pageTitle = 'Home';

$productsQuery = "SELECT p.*,
            pi.image_path, 
            v.variant_id, v.variant_name, v.variant_price, v.discount_price,
            v.unit_type, v.minimum_order, v.order_increment, v.stock_quantity,
            v.stock_status, v.is_deleted,
            c.category_name
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0
    LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
    LEFT JOIN product_categories c ON pcl.category_id = c.category_id
    WHERE p.is_deleted = 0
    ORDER BY p.created_at DESC
    LIMIT 12";

$productsResult = $conn->query($productsQuery);

$totalQuery = "SELECT COUNT(*) as total FROM blogs WHERE blog_status = 'published'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);

$blogsQuery = "SELECT * FROM blogs 
          WHERE blog_status = 'published' 
          ORDER BY blog_published_date DESC";
$blogsResult = mysqli_query($conn, $blogsQuery);
$blogs = mysqli_fetch_all($blogsResult, MYSQLI_ASSOC);
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
  
  <title>St. Joseph Fish Brokerage Inc. | Largest Fish Brokerage in the Philippines</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. is the largest fish brokerage in the Philippines, providing fresh seafood trading, wholesale supply, and nationwide sourcing services.">
  
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc. | Largest Fish Brokerage in the Philippines">
  <meta name="twitter:description" content="The largest fish brokerage in the Philippines, offering professional seafood trading and brokerage services nationwide.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  
  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <!-- ✅ UNIFIED CART CORE — must load before cart.php / products.php -->
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  <style>
    /* ── Core tokens (mirrors sustainability.php) ── */
    body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    /* ── Hero tokens ────────────────────────────────────────────────── */
    :root {
      --h-mobile:  clamp(280px, 52vw, 380px);
      --h-tablet:  clamp(340px, 45vw, 440px);
      --h-desktop: clamp(380px, 38vw, 520px);
      --slide-dur: 600ms;
      --ease-slide: cubic-bezier(.45,0,.15,1);
    }
    
    /* ── Wrapper ────────────────────────────────────────────────────── */
    #hero-wrap {
      position: relative;
      width: 100%;
      height: var(--h-mobile);
      overflow: hidden;
      background: #0f1923;
      /* GPU layer — prevents repaints on slide */
      transform: translateZ(0);
    }
    @media (min-width: 640px)  { #hero-wrap { height: var(--h-tablet); } }
    @media (min-width: 1024px) { #hero-wrap { height: var(--h-desktop); } }
    
    /* ── Track ──────────────────────────────────────────────────────── */
    #hero-track {
      display: flex;
      height: 100%;
      will-change: transform;
      transition: transform var(--slide-dur) var(--ease-slide);
    }
    @media (prefers-reduced-motion: reduce) {
      #hero-track { transition: none; }
    }
    
    /* ── Individual slide ───────────────────────────────────────────── */
    .hero-slide {
      flex: 0 0 100%;
      position: relative;
      overflow: hidden;
    }
    
    /* Background image layer */
    .hero-bg {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
      /* Ken-Burns only on active slide */
      transition: transform 8s linear;
      transform: scale(1.06);
    }
    .hero-slide.is-active .hero-bg {
      transform: scale(1);
    }
    
    /* Gradient overlay — stronger on mobile for legibility */
    .hero-overlay {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to top,   rgba(0,0,0,.72) 0%, transparent 55%),
        linear-gradient(to right, rgba(0,0,0,.62) 0%, transparent 65%);
    }
    @media (min-width: 768px) {
      .hero-overlay {
        background:
          linear-gradient(to top,   rgba(0,0,0,.55) 0%, transparent 50%),
          linear-gradient(to right, rgba(0,0,0,.70) 0%, transparent 60%);
      }
    }
    
    /* Content */
    .hero-content {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: flex-end;          /* bottom-aligned on mobile */
      padding: 1.25rem 1.125rem 1.5rem;
      /* Staggered reveal on active */
      pointer-events: none;
    }
    @media (min-width: 640px) {
      .hero-content {
        align-items: center;          /* vertically centred on larger screens */
        padding: 2rem 3rem;
      }
    }
    @media (min-width: 1024px) {
      .hero-content { padding: 2.5rem 4rem; }
    }
    
    .hero-inner {
      max-width: min(560px, 100%);
      opacity: 0;
      transform: translateY(18px);
      transition: opacity .45s ease .18s, transform .45s ease .18s;
      pointer-events: auto;
    }
    .hero-slide.is-active .hero-inner {
      opacity: 1;
      transform: none;
    }
    @media (prefers-reduced-motion: reduce) {
      .hero-inner { transition: none; opacity: 1; transform: none; }
    }
    
    /* Tag pill */
    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: .375rem;
      background: #ea580c;
      color: #fff;
      font-size: .6875rem;
      font-weight: 800;
      letter-spacing: .1em;
      text-transform: uppercase;
      padding: .25rem .75rem;
      border-radius: 9999px;
      margin-bottom: .625rem;
      /* Slight delay after headline */
      opacity: 0;
      transform: translateY(10px);
      transition: opacity .35s ease .05s, transform .35s ease .05s;
    }
    .hero-slide.is-active .hero-tag {
      opacity: 1; transform: none;
    }
    
    /* Headline */
    .hero-h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.375rem, 4.5vw, 2.75rem);
      font-weight: 700;
      color: #fff;
      line-height: 1.18;
      margin: 0 0 .5rem;
      text-shadow: 0 2px 12px rgba(0,0,0,.35);
    }
    
    /* Sub */
    .hero-sub {
      font-size: clamp(.75rem, 2vw, .9375rem);
      color: rgba(255,255,255,.82);
      line-height: 1.55;
      margin: 0 0 1rem;
      max-width: 38ch;
    }
    @media (max-width: 479px) {
      /* Compact: hide sub on very small screens to save space */
      .hero-sub { display: none; }
    }
    
    /* CTA button */
    .hero-cta {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: #ea580c;
      color: #fff;
      font-size: .8125rem;
      font-weight: 700;
      padding: .5625rem 1.125rem;
      border-radius: .5rem;
      text-decoration: none;
      transition: background .15s, transform .12s;
      white-space: nowrap;
    }
    .hero-cta:hover  { background: #c2410c; transform: translateY(-1px); }
    .hero-cta:active { transform: scale(.97); }
    .hero-cta svg   { flex-shrink: 0; }
    
    /* ── Arrow buttons ──────────────────────────────────────────────── */
    .hero-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 20;
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 50%;
      background: rgba(255,255,255,.14);
      border: 1px solid rgba(255,255,255,.25);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .15s, transform .12s;
    }
    .hero-arrow:hover  { background: rgba(255,255,255,.28); transform: translateY(calc(-50% - 1px)); }
    .hero-arrow:active { transform: translateY(-50%) scale(.94); }
    #hero-prev { left: .75rem; }
    #hero-next { right: .75rem; }
    @media (max-width: 479px) {
      /* Smaller arrows on tiny screens */
      .hero-arrow { width: 1.875rem; height: 1.875rem; }
      #hero-prev { left: .5rem; }
      #hero-next { right: .5rem; }
    }
    
    /* ── Progress dots ──────────────────────────────────────────────── */
    #hero-dots {
      position: absolute;
      bottom: .875rem;
      left: 50%;
      transform: translateX(-50%);
      z-index: 20;
      display: flex;
      align-items: center;
      gap: .375rem;
    }
    .hero-dot {
      height: 4px;
      border-radius: 9999px;
      background: rgba(255,255,255,.35);
      cursor: pointer;
      transition: width .3s ease, background .3s ease;
      width: 18px;
      border: none;
      padding: 0;
    }
    .hero-dot.is-active {
      width: 32px;
      background: #ea580c;
    }
    
    /* ── Progress bar (auto-play timer) ────────────────────────────── */
    #hero-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 2px;
      background: #ea580c;
      z-index: 20;
      width: 0%;
      transition: width linear;
      opacity: .7;
    }
    
    /* ── Slide counter (top-right) ──────────────────────────────────── */
    #hero-counter {
      position: absolute;
      top: .875rem;
      right: .875rem;
      z-index: 20;
      font-size: .6875rem;
      font-weight: 700;
      letter-spacing: .08em;
      color: rgba(255,255,255,.65);
      background: rgba(0,0,0,.28);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      padding: .2rem .6rem;
      border-radius: 9999px;
      pointer-events: none;
    }

    /* Eyebrow label — orange accent line matches sustainability */
    .section-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      font-size: .75rem;
      font-weight: 700;
      letter-spacing: .15em;
      text-transform: uppercase;
      color: #fb923c;
      margin-bottom: .75rem;
    }
    .section-eyebrow::before {
      content: '';
      display: block;
      width: 2rem;
      height: 2px;
      background: #fb923c;
    }

    /* ── Blog card ── */
    .blog-card {
      transition: transform .3s ease, box-shadow .3s ease;
    }
    .blog-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(249,115,22,.12);
    }

    /* ── Decorative blobs ── */
    .blob-decoration {
      position: absolute;
      pointer-events: none;
      opacity: .07;
      z-index: 0;
    }

    /* ── CTA gradient (same as sustainability impact section) ── */
    .gradient-orange {
      background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24 100%);
    }

    /* ── Quote mark ── */
    .quote-mark {
      font-family: 'Playfair Display', serif;
      font-size: 18rem;
      position: absolute;
      top: -4rem; left: 1rem;
      color: rgba(249,115,22,.06);
      line-height: 1;
      pointer-events: none;
    }

    /* ── Pill badge ── */
    .sdg-badge {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .375rem .875rem;
      border-radius: 9999px;
      font-size: .7rem;
      font-weight: 700;
    }

    @media (max-width: 768px) {
      .blob-decoration { display: none; }
      .shop-btn { display: none; }
    }
  </style>
</head>

<body class="bg-white">
  <?php include './components/preloaders.php'; ?>
  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>
  
  <!-- ═══════════ HERO ═══════════ -->
  <div id="hero-wrap" role="region" aria-label="Hero slideshow" aria-roledescription="carousel">
  
    <!-- Slides track -->
    <div id="hero-track" aria-live="polite">
      <?php
      $slides = [
        [
          'bg'       => '#1e3a5f',
          'image'    => $baseUrl . 'assets/images/contents/hero_banner.png',
          'tag'      => 'Fresh Daily',
          'headline' => 'Premium Seafood<br>Direct from the Port',
          'sub'      => 'Bangus, Tilapia, Crab — fresh catch delivered straight from Navotas Fish Port.',
          'cta_text' => 'Shop Now',
          'cta_url'  => $baseUrl . 'shop.php',
        ],
        [
          'bg'       => '#14532d',
          'image'    => $baseUrl . 'assets/images/contents/hero_baner.png',
          'tag'      => 'Best Sellers',
          'headline' => 'Bangus Pangasinan<br>Now Available',
          'sub'      => 'Premium milkfish sourced from Pangasinan — firm texture, clean taste.',
          'cta_text' => 'Order Now',
          'cta_url'  => $baseUrl . 'item/bangus-pangasinan',
        ],
        [
          'bg'       => '#7c2d12',
          'image'    => $baseUrl . 'assets/images/contents/hero_baner.png',
          'tag'      => 'Wholesale',
          'headline' => 'Bulk Orders<br>Welcome',
          'sub'      => 'Competitive pricing for restaurants, retailers, and food businesses.',
          'cta_text' => 'Contact Us',
          'cta_url'  => $baseUrl . 'contact.php',
        ],
        [
          'bg'       => '#1e3a5f',
          'image'    => $baseUrl . 'assets/images/contents/hero_anner.png',
          'tag'      => 'New Arrivals',
          'headline' => 'Fresh Catch<br>Every Morning',
          'sub'      => 'Order before 10AM for same-day dispatch from our Navotas facility.',
          'cta_text' => 'Browse Products',
          'cta_url'  => $baseUrl . 'shop.php',
        ],
      ];
      ?>
  
      <?php foreach ($slides as $i => $s): ?>
      <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
          role="group"
          aria-roledescription="slide"
          aria-label="Slide <?= $i+1 ?> of <?= count($slides) ?>"
          aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
  
        <!-- Background image + Ken-Burns zoom -->
        <div class="hero-bg"
            style="background-image:url('<?= htmlspecialchars($s['image']) ?>');background-color:<?= $s['bg'] ?>"></div>
  
        <!-- Dark overlay -->
        <div class="hero-overlay"></div>
  
        <!-- Text content -->
        <div class="hero-content">
          <div class="hero-inner">
            <span class="hero-tag"><?= htmlspecialchars($s['tag']) ?></span>
            <h1 class="hero-h1"><?= $s['headline'] ?></h1>
            <p class="hero-sub"><?= htmlspecialchars($s['sub']) ?></p>
            <a href="<?= htmlspecialchars($s['cta_url']) ?>" class="hero-cta">
              <?= htmlspecialchars($s['cta_text']) ?>
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
            </a>
          </div>
        </div>
  
      </div>
      <?php endforeach; ?>
    </div><!-- /track -->
  
    <!-- Prev arrow -->
    <button id="hero-prev" class="hero-arrow" aria-label="Previous slide">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <path d="m15 18-6-6 6-6"/>
      </svg>
    </button>
  
    <!-- Next arrow -->
    <button id="hero-next" class="hero-arrow" aria-label="Next slide">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <path d="m9 18 6-6-6-6"/>
      </svg>
    </button>
  
    <!-- Dot nav -->
    <div id="hero-dots" role="tablist" aria-label="Slide navigation">
      <?php foreach ($slides as $i => $s): ?>
      <button class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>"
              role="tab"
              aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
              aria-label="Go to slide <?= $i+1 ?>: <?= htmlspecialchars($s['tag']) ?>">
      </button>
      <?php endforeach; ?>
    </div>
  
    <!-- Slide counter -->
    <div id="hero-counter" aria-hidden="true">1 / <?= count($slides) ?></div>
  
    <!-- Auto-play progress bar -->
    <div id="hero-progress" aria-hidden="true"></div>
  
  </div><!-- /hero-wrap -->

   <!-- ══════════════════════════════════════════════
       EXPLORE / ABOUT COMPONENT
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-gray-200" id="explore-more">
    <?php include('./components/explore.php'); ?>
  </section>

  <!-- ══════════════════════════════════════════════
       INTRO / WHO WE ARE — SDG badges + paragraph
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
      <span class="section-eyebrow justify-center">Who We Are</span>
      <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        More Than a Fish Broker —<br>A Partner in Philippine Seafood
      </h2>
      <p class="text-lg text-gray-600 leading-relaxed mb-8">
        With over <strong class="text-orange-600">30 years</strong> of operations spanning <strong class="text-orange-600">Navotas</strong>, <strong class="text-orange-600">Malabon</strong>, <strong class="text-orange-600">Davao Toril</strong>, and growing, we serve as the critical link between hardworking Filipino fishermen and the buyers who depend on fresh, traceable seafood every day.
      </p>
      <div class="flex flex-wrap gap-2 justify-center">
        <span class="sdg-badge bg-green-100 text-green-800 border border-green-200">🌱 HACCP Certified</span>
        <span class="sdg-badge bg-blue-100 text-blue-800 border border-blue-200">🐟 BFAR Licensed</span>
        <span class="sdg-badge bg-orange-100 text-orange-800 border border-orange-200">🏘️ 500+ Supplier Partners</span>
        <span class="sdg-badge bg-amber-100 text-amber-800 border border-amber-200">⚓ Daily Fresh Catch</span>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       WHY CHOOSE US — 4 feature cards (pillar style)
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-slate-50">
    <div class="max-w-6xl mx-auto px-6">
      <div class="text-center mb-14" data-aos="fade-up">
        <span class="section-eyebrow justify-center">Why Choose SJFBI</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Built on Trust, Freshness &amp; Integrity</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">Four pillars that set us apart in the Philippine fish brokerage industry.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $features = [
          ['icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'ibg' => 'bg-orange-50', 'ic' => 'text-orange-600', 'title' => 'Direct Source', 'desc' => 'Fresh catch sourced daily from 500+ verified Filipino fishermen and cooperatives.'],
          ['icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3', 'ibg' => 'bg-blue-50', 'ic' => 'text-blue-600', 'title' => 'Fair Pricing', 'desc' => 'Transparent market-rate pricing — no hidden deductions, no unfair markups.'],
          ['icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'ibg' => 'bg-teal-50', 'ic' => 'text-teal-600', 'title' => 'Nationwide Reach', 'desc' => 'Operating from Luzon to Mindanao — Navotas, Malabon, Davao, and beyond.'],
          ['icon' => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z', 'ibg' => 'bg-emerald-50', 'ic' => 'text-emerald-600', 'title' => 'Quality Standards', 'desc' => 'HACCP-compliant, BFAR-licensed, FDA-registered operations at every port.'],
        ];
        foreach ($features as $i => $f):
        ?>
        <div class="feat-card bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group"
          data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
          <div class="size-16 <?= $f['ibg'] ?> rounded-2xl flex items-center justify-center mb-4 transition-transform group-hover:scale-105">
            <svg class="size-7 <?= $f['ic'] ?>" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="<?= $f['icon'] ?>"/>
            </svg>
          </div>
          <h3 class="font-bold text-gray-900 mb-2"><?= $f['title'] ?></h3>
          <p class="text-sm text-gray-500 leading-relaxed"><?= $f['desc'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       PRODUCTS SECTION
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-slate-50 py-20" id="shop-products">
    <!-- Blob accent -->
    <div class="blob-decoration" style="top:-60px;right:-80px">
      <svg width="400" height="400" viewBox="0 0 594 842" fill="#f97316">
        <path d="M329.82 626.72C321.08 572.98 312.04 517.59 272.09 481.26C250.53 461.66 215.48 447.41 178.17 432.27C93.15 397.82-3.02 358.7 18.08 241.05C37.91 130.85 101.29 138.16 176.59 146.75C206.06 150.14 237.41 153.77 268.54 150.37C307.81 146.08 347.38 124.07 384.09 103.72C437 74.32 483.81 48.32 516.37 82.31C562.04 130.02 543.8 190.02 526.54 246.77C517.12 277.75 508 307.75 509.96 333.9C512.82 371.96 527.98 419.68 543.88 469.57C556.62 509.6 569.81 551.05 577.5 590.02C595 678.96 584.2 755.84 476.2 777.02C453.73 781.46 430.22 780.94 409.34 773.7C390.88 767.29 374.52 755.61 362.68 737.37C342.94 707.3 336.45 667.5 329.82 626.72Z"/>
      </svg>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="section-eyebrow justify-center">The Freshest Fish in the Market Today</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Premium Seafood Products</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">Sourced daily from verified fishermen across the Philippines — delivered fresh to your door.</p>
      </div>
      <?php include('./components/products.php'); ?>
    </div>

    <div class="text-center mt-10" data-aos="fade-up">
      <a href="<?= $baseUrl ?>shop"
        class="inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-8 rounded-xl transition-all hover:-translate-y-0.5 shadow-sm hover:shadow-md">
        <span>View All Seafood</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16.69 7.44a6.973 6.973 0 0 0-1.69 4.56c0 1.747.64 3.345 1.699 4.571"/>
          <path d="M2 9.504c7.715 8.647 14.75 10.265 20 2.498c-5.25-7.761-12.285-6.142-20 2.504"/>
          <path d="M18 11v.01"/><path d="M11.5 10.5c-.667 1-.667 2 0 3"/>
        </svg>
      </a>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       TESTIMONIALS COMPONENT
  ══════════════════════════════════════════════ -->
  <div class="relative overflow-hidden">
    <?php include('./components/testimonials.php'); ?>
  </div>

   <!-- ══════════════════════════════════════════════
       QUOTE / BRAND STATEMENT (mirrors sustainability quote section)
  ══════════════════════════════════════════════ -->
  <section class="relative py-24 bg-amber-50 overflow-hidden border">
    <div class="quote-mark">"</div>
    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
      <p class="font-display text-2xl md:text-3xl italic text-gray-700 leading-relaxed mb-8">
        "Every fish that passes through our hands carries with it a commitment — to the fishermen who caught it, the families who will eat it, and the ocean that gave it."
      </p>
      <div class="flex items-center justify-center gap-3">
        <div class="w-12 h-px bg-orange-300"></div>
        <span class="text-sm font-semibold text-gray-500">St. Joseph Fish Brokerage, Inc.</span>
        <div class="w-12 h-px bg-orange-300"></div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       BLOG / NEWS SECTION
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="section-eyebrow justify-center">Latest Insights</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Blogs / News &amp; Updates</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">Stay informed with the latest news from St. Joseph and the Philippine fishing industry.</p>
      </div>

      <?php if (!empty($blogs)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($blogs as $idx => $blog): ?>
        <a href="/sjfbi-js/blogs/<?= $blog['blog_slug'] ?>"
          class="blog-card group block bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100"
          data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 100 ?>">
          <div class="relative h-56 overflow-hidden">
            <?php if (!empty($blog['blog_featured_image'])): ?>
              <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>"
                   alt="<?= htmlspecialchars($blog['blog_title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full gradient-orange flex items-center justify-center">
                <span class="text-white text-4xl font-bold font-display">SJ</span>
              </div>
            <?php endif; ?>
            <div class="absolute top-4 left-4">
              <span class="px-3 py-1 bg-orange-600 text-white text-xs font-semibold rounded-full">
                <?= ucfirst($blog['blog_status']) ?>
              </span>
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-center mb-4">
              <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                <span class="text-orange-600 font-semibold text-sm">
                  <?= strtoupper(substr($blog['blog_author'] ?? 'A', 0, 1)) ?>
                </span>
              </div>
              <div class="ms-3">
                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($blog['blog_author'] ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500"><?= date('F d, Y', strtotime($blog['blog_published_date'])) ?></p>
              </div>
            </div>
            <h3 class="font-display text-lg font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($blog['blog_title']) ?>
            </h3>
            <p class="text-gray-500 text-sm mb-4 line-clamp-3"><?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?></p>
            <div class="flex items-center text-orange-600 font-semibold text-sm gap-1">
              Read More
              <svg class="w-4 h-4 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="text-center py-20" data-aos="fade-up">
        <div class="size-20 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-10 h-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No blog posts yet</h3>
        <p class="mt-2 text-gray-500">Check back soon for updates from St. Joseph Fish Brokerage Inc.</p>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php $conn->close(); ?>
  <?php include './components/footer.php'; ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://preline.co/assets/js/preline.js"></script>
  <?php include('live_chat.php'); ?>

  <script src="./functions/product_process.js"></script>

  <script>
    // ── Init AOS (same settings as sustainability) ──
    AOS.init({ once: true, easing: 'ease-out-cubic', duration: 750 });

    // ── Hero bg parallax load ──
    window.addEventListener('load', () => {
      document.getElementById('heroBg')?.classList.add('loaded');
    });

    // ── Smooth scroll for anchor links ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); window.scrollTo({ top: t.offsetTop - 80, behavior: 'smooth' }); }
      });
    });

    // ── Animated impact counters (from sustainability) ──
    function animateCounter(el) {
      const target = parseInt(el.dataset.target);
      const suffix = el.dataset.suffix || '';
      let cur = 0;
      const step = Math.max(1, Math.floor(target / 50));
      const timer = setInterval(() => {
        cur = Math.min(cur + step, target);
        el.textContent = cur + suffix;
        if (cur >= target) clearInterval(timer);
      }, 35);
    }

    const impactSection = document.getElementById('impact-section');
    if (impactSection) {
      new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.querySelectorAll('.impact-num').forEach(animateCounter);
          }
        });
      }, { threshold: 0.3 }).observe(impactSection);
    }

    // ── GTM / GA ──
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');

    (function () {
      var DELAY    = 5200;   // ms between slides
      var slides   = document.querySelectorAll('.hero-slide');
      var dots     = document.querySelectorAll('.hero-dot');
      var track    = document.getElementById('hero-track');
      var progress = document.getElementById('hero-progress');
      var counter  = document.getElementById('hero-counter');
      var total    = slides.length;
      var cur      = 0;
      var timer    = null;
      var progTimer= null;
      var paused   = false;
      var reduced  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
      /* ── Core: go to slide n ───────────────────────────── */
      function goTo(n, announce) {
        var prev = cur;
        cur = ((n % total) + total) % total;
    
        // Move track
        track.style.transform = 'translateX(-' + (cur * 100) + '%)';
    
        // Active class (triggers Ken-Burns + content reveal)
        slides[prev].classList.remove('is-active');
        slides[prev].setAttribute('aria-hidden', 'true');
        slides[cur].classList.add('is-active');
        slides[cur].setAttribute('aria-hidden', 'false');
    
        // Dots
        dots[prev].classList.remove('is-active');
        dots[prev].setAttribute('aria-selected', 'false');
        dots[cur].classList.add('is-active');
        dots[cur].setAttribute('aria-selected', 'true');
    
        // Counter
        if (counter) counter.textContent = (cur + 1) + ' / ' + total;
    
        // Progress bar reset
        startProgress();
      }
    
      /* ── Progress bar animation ────────────────────────── */
      function startProgress() {
        if (reduced) return;
        clearTimeout(progTimer);
        if (progress) {
          progress.style.transition = 'none';
          progress.style.width = '0%';
          // Force reflow
          void progress.offsetWidth;
          progress.style.transition = 'width ' + DELAY + 'ms linear';
          progress.style.width = '100%';
        }
      }
    
      /* ── Auto-play ─────────────────────────────────────── */
      function startAuto() {
        clearInterval(timer);
        if (!paused) {
          timer = setInterval(function () { goTo(cur + 1); }, DELAY);
        }
      }
      function stopAuto() { clearInterval(timer); }
      function pause()    { paused = true;  stopAuto(); if (progress) progress.style.transition = 'none'; }
      function resume()   { paused = false; startAuto(); startProgress(); }
    
      /* ── Arrows ────────────────────────────────────────── */
      document.getElementById('hero-prev').addEventListener('click', function () { goTo(cur - 1); startAuto(); });
      document.getElementById('hero-next').addEventListener('click', function () { goTo(cur + 1); startAuto(); });
    
      /* ── Dots ──────────────────────────────────────────── */
      dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () { goTo(i); startAuto(); });
      });
    
      /* ── Keyboard ──────────────────────────────────────── */
      document.getElementById('hero-wrap').addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft')  { goTo(cur - 1); startAuto(); }
        if (e.key === 'ArrowRight') { goTo(cur + 1); startAuto(); }
      });
    
      /* ── Touch / swipe ─────────────────────────────────── */
      var touchStartX = 0;
      var touchStartY = 0;
      var dragging    = false;
    
      document.getElementById('hero-wrap').addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        dragging = true;
      }, { passive: true });
    
      document.getElementById('hero-wrap').addEventListener('touchmove', function (e) {
        if (!dragging) return;
        var dx = Math.abs(e.touches[0].clientX - touchStartX);
        var dy = Math.abs(e.touches[0].clientY - touchStartY);
        // Prevent page scroll only when clearly horizontal swipe
        if (dx > dy && dx > 10) e.preventDefault();
      }, { passive: false });
    
      document.getElementById('hero-wrap').addEventListener('touchend', function (e) {
        if (!dragging) return;
        dragging = false;
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 45) {
          goTo(cur + (dx < 0 ? 1 : -1));
          startAuto();
        }
      }, { passive: true });
    
      /* ── Pause on hover / focus ────────────────────────── */
      var wrap = document.getElementById('hero-wrap');
      wrap.addEventListener('mouseenter', pause);
      wrap.addEventListener('mouseleave', resume);
      wrap.addEventListener('focusin',    pause);
      wrap.addEventListener('focusout',   function (e) {
        if (!wrap.contains(e.relatedTarget)) resume();
      });
    
      /* ── Visibility API — pause when tab hidden ─────────── */
      document.addEventListener('visibilitychange', function () {
        document.hidden ? pause() : resume();
      });
    
      /* ── Reduced motion: disable auto-play ─────────────── */
      if (reduced) {
        paused = true;
        if (progress) progress.style.display = 'none';
      }
    
      /* ── Boot ──────────────────────────────────────────── */
      goTo(0);   // sets initial active state + progress bar
      startAuto();
    })();
  </script>
</body>
</html>