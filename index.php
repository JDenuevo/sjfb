<?php
session_start();
include 'conn.php';

$pageTitle = 'Home';

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
  <link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <style>
    body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }
    /* Minimal hero height tokens only — everything else is Tailwind */
    #hero-wrap {
      --h-mobile:  clamp(280px, 52vw, 380px);
      --h-tablet:  clamp(340px, 45vw, 440px);
      --h-desktop: clamp(380px, 38vw, 520px);
      height: var(--h-mobile);
    }
    @media (min-width: 640px)  { #hero-wrap { height: var(--h-tablet); } }
    @media (min-width: 1024px) { #hero-wrap { height: var(--h-desktop); } }

    /* Ken-Burns on active slide */
    .hero-slide .hero-bg { transform: scale(1.06); transition: transform 8s linear; }
    .hero-slide.is-active .hero-bg { transform: scale(1); }

    /* Content stagger reveal */
    .hero-inner { opacity: 0; transform: translateY(18px); transition: opacity .45s ease .18s, transform .45s ease .18s; }
    .hero-slide.is-active .hero-inner { opacity: 1; transform: none; }
    .hero-tag  { opacity: 0; transform: translateY(10px); transition: opacity .35s ease .05s, transform .35s ease .05s; }
    .hero-slide.is-active .hero-tag { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
      .hero-inner, .hero-tag { transition: none; opacity: 1; transform: none; }
    }

    /* Progress dot sizing */
    .hero-dot { height: 4px; width: 18px; transition: width .3s ease, background .3s ease; }
    .hero-dot.is-active { width: 32px; }
    #hero-progress { transition: width linear; }

    /* Quote mark decoration */
    .quote-mark {
      font-family: 'Playfair Display', serif;
      font-size: 18rem;
      position: absolute;
      top: -4rem; left: 1rem;
      color: rgba(249,115,22,.06);
      line-height: 1;
      pointer-events: none;
    }
  </style>
</head>

<body class="bg-white">
  <?php include './components/preloaders.php'; ?>
  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>
  
  <!-- ═══════════ HERO CAROUSEL ═══════════ -->
  <div id="hero-wrap"
       class="relative w-full overflow-hidden bg-slate-900 transform-gpu"
       role="region"
       aria-label="Hero slideshow"
       aria-roledescription="carousel">

    <!-- Track -->
    <div id="hero-track"
         class="flex h-full will-change-transform"
         style="transition: transform 600ms cubic-bezier(.45,0,.15,1)"
         aria-live="polite">

      <?php
      $slides = [
        [
          'bg'       => '#1e3a5f',
          'image'    => $baseUrl . 'assets/images/contents/hero_banner1.jpg',
          'tag'      => 'Fresh Daily',
          'headline' => 'Premium Seafood<br>Direct from the Port',
          'sub'      => 'Bangus, Tilapia, Crab — fresh catch delivered straight from Navotas Fish Port.',
          'cta_text' => 'Shop Now',
          'cta_url'  => $baseUrl . 'shop',
        ],
        [
          'bg'       => '#14532d',
          'image'    => $baseUrl . 'assets/images/contents/hero_banner2.jpg',
          'tag'      => 'Best Sellers',
          'headline' => 'Bangus Pangasinan<br>Now Available',
          'sub'      => 'Premium milkfish sourced from Pangasinan — firm texture, clean taste.',
          'cta_text' => 'Order Now',
          'cta_url'  => $baseUrl . 'item/bangus-pangasinan',
        ],
        [
          'bg'       => '#7c2d12',
          'image'    => $baseUrl . 'assets/images/contents/hero_banner3.jpg',
          'tag'      => 'Wholesale',
          'headline' => 'Bulk Orders<br>Welcome',
          'sub'      => 'Competitive pricing for restaurants, retailers, and food businesses.',
          'cta_text' => 'Contact Us',
          'cta_url'  => $baseUrl . 'contact',
        ],
        [
          'bg'       => '#1e3a5f',
          'image'    => $baseUrl . 'assets/images/contents/hero_banner4.png',
          'tag'      => 'New Arrivals',
          'headline' => 'Fresh Catch<br>Every Morning',
          'sub'      => 'Order before 10AM for same-day dispatch from our Navotas facility.',
          'cta_text' => 'Browse Products',
          'cta_url'  => $baseUrl . 'shop',
        ],
      ];
      ?>

      <?php foreach ($slides as $i => $s): ?>
      <div class="hero-slide flex-none w-full relative overflow-hidden<?= $i === 0 ? ' is-active' : '' ?>"
           role="group"
           aria-roledescription="slide"
           aria-label="Slide <?= $i+1 ?> of <?= count($slides) ?>"
           aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">

        <!-- BG image -->
        <div class="hero-bg absolute inset-0 bg-cover bg-center"
             style="background-image:url('<?= htmlspecialchars($s['image']) ?>');background-color:<?= $s['bg'] ?>"></div>

        <!-- Gradient overlays — pure Tailwind -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-transparent"></div>

        <!-- Content -->
        <div class="absolute inset-0 flex items-end sm:items-center px-5 pb-6 sm:px-12 sm:pb-0 lg:px-16">
          <div class="hero-inner max-w-xl pointer-events-auto">
            <!-- Tag pill using Preline badge -->
            <span class="hero-tag inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-bold tracking-widest uppercase bg-orange-600 text-white mb-3">
              <?= htmlspecialchars($s['tag']) ?>
            </span>
            <h1 class="font-display text-[clamp(1.375rem,4.5vw,2.75rem)] font-bold text-white leading-tight mb-2 drop-shadow-lg">
              <?= $s['headline'] ?>
            </h1>
            <p class="hidden sm:block text-sm sm:text-base text-white/80 leading-relaxed mb-4 max-w-[38ch]">
              <?= htmlspecialchars($s['sub']) ?>
            </p>
            <a href="<?= htmlspecialchars($s['cta_url']) ?>"
               class="inline-flex items-center gap-2 py-2.5 px-5 rounded-lg bg-orange-600 hover:bg-orange-700 active:scale-95 text-white text-sm font-bold transition-all duration-150 whitespace-nowrap">
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

    <!-- Prev arrow — Preline-style ghost button -->
    <button id="hero-prev"
            class="absolute top-1/2 left-3 -translate-y-1/2 z-20 size-9 rounded-full flex items-center justify-center bg-white/15 border border-white/25 backdrop-blur-sm text-white hover:bg-white/30 hover:-translate-y-[calc(50%+1px)] active:scale-95 transition-all duration-150"
            aria-label="Previous slide">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
    </button>

    <!-- Next arrow -->
    <button id="hero-next"
            class="absolute top-1/2 right-3 -translate-y-1/2 z-20 size-9 rounded-full flex items-center justify-center bg-white/15 border border-white/25 backdrop-blur-sm text-white hover:bg-white/30 hover:-translate-y-[calc(50%+1px)] active:scale-95 transition-all duration-150"
            aria-label="Next slide">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
    </button>

    <!-- Dot nav -->
    <div id="hero-dots" class="absolute bottom-3.5 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5"
         role="tablist" aria-label="Slide navigation">
      <?php foreach ($slides as $i => $s): ?>
      <button class="hero-dot rounded-full bg-white/35 border-none p-0 cursor-pointer<?= $i === 0 ? ' is-active !bg-orange-600' : '' ?>"
              role="tab"
              aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
              aria-label="Go to slide <?= $i+1 ?>: <?= htmlspecialchars($s['tag']) ?>">
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Slide counter -->
    <div id="hero-counter"
         class="absolute top-3.5 right-3.5 z-20 text-[.6875rem] font-bold tracking-wide text-white/65 bg-black/28 backdrop-blur px-2.5 py-0.5 rounded-full pointer-events-none"
         aria-hidden="true">
      1 / <?= count($slides) ?>
    </div>

    <!-- Auto-play progress bar -->
    <div id="hero-progress" class="absolute bottom-0 left-0 h-0.5 bg-orange-500/70 z-20 w-0" aria-hidden="true"></div>

  </div><!-- /hero-wrap -->

  <!-- ══════════════════════════════════════════════
       EXPLORE / ABOUT COMPONENT
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-gray-200" id="explore-more">
    <?php include('./components/explore.php'); ?>
  </section>

  <!-- ══════════════════════════════════════════════
       INTRO / WHO WE ARE
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">

      <!-- Eyebrow — Preline badge style -->
      <span class="inline-flex items-center gap-2 mb-4 text-xs font-bold tracking-[.15em] uppercase text-orange-500
                   before:block before:w-8 before:h-px before:bg-orange-400">
        Who We Are
      </span>

      <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 mb-6">
        More Than a Fish Broker —<br>A Partner in Philippine Seafood
      </h2>
      <p class="text-lg text-gray-600 leading-relaxed">
        With over <strong class="text-orange-600">47 years</strong> of operations spanning
        <strong class="text-orange-600">Navotas</strong>, <strong class="text-orange-600">Malabon</strong>,
        <strong class="text-orange-600">Lucena</strong>, <strong class="text-orange-600">Davao</strong>,
        and growing, we serve as the critical link between hardworking Filipino fishermen and the buyers
        who depend on fresh, traceable seafood every day.
      </p>

    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       WHY CHOOSE US — Preline card grid
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-slate-50">
    <div class="max-w-6xl mx-auto px-6">

      <div class="text-center mb-14" data-aos="fade-up">
        <span class="inline-flex items-center gap-2 mb-3 text-xs font-bold tracking-[.15em] uppercase text-orange-500
                     before:block before:w-8 before:h-px before:bg-orange-400">
          Why Choose Us
        </span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">
          Built on Trust, Freshness &amp; Integrity
        </h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
          Four pillars that set us apart in the Philippine fish brokerage industry.
        </p>
      </div>

      <!-- Preline card grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $features = [
          [
            'icon'  => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
            'ibg'   => 'bg-orange-50',
            'ic'    => 'text-orange-600',
            'title' => 'Direct Source',
            'desc'  => 'Fresh catch sourced daily from numerous verified Filipino fishermen and cooperatives.',
          ],
          [
            'icon'  => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
            'ibg'   => 'bg-blue-50',
            'ic'    => 'text-blue-600',
            'title' => 'Fair Pricing',
            'desc'  => 'Transparent market-rate pricing — no hidden deductions, no unfair markups.',
          ],
          [
            'icon'  => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            'ibg'   => 'bg-teal-50',
            'ic'    => 'text-teal-600',
            'title' => 'Nationwide Reach',
            'desc'  => 'Operating from Luzon to Mindanao — Navotas, Malabon, Lucena, Davao, and beyond.',
          ],
          [
            'icon'  => 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
            'ibg'   => 'bg-emerald-50',
            'ic'    => 'text-emerald-600',
            'title' => 'Premium Quality Assurance',
            'desc'  => 'Strict quality control from catch to delivery — ensuring freshness, safety, and export-grade seafood standards.',
          ],
        ];
        foreach ($features as $i => $f):
        ?>
        <!-- Preline card -->
        <div class="group flex flex-col bg-white border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 rounded-2xl p-6"
             data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
          <div class="inline-flex items-center justify-center size-16 <?= $f['ibg'] ?> rounded-2xl mb-4 transition-transform duration-200 group-hover:scale-105">
            <svg class="size-7 <?= $f['ic'] ?>" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="<?= $f['icon'] ?>"/>
            </svg>
          </div>
          <div class="mt-auto">
            <h3 class="text-sm font-bold text-gray-900 mb-2"><?= $f['title'] ?></h3>
            <p class="text-sm text-gray-500 leading-relaxed"><?= $f['desc'] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       PRODUCTS SECTION
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-slate-50 py-20" id="shop-products">

    <!-- Subtle blob accent -->
    <div class="pointer-events-none absolute -top-16 -right-20 w-96 h-96 rounded-full bg-orange-500/5 blur-3xl" aria-hidden="true"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-flex items-center gap-2 mb-3 text-xs font-bold tracking-[.15em] uppercase text-orange-500
                     before:block before:w-8 before:h-px before:bg-orange-400">
          The Freshest Fish in the Market Today
        </span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Premium Seafood Products</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
          Sourced daily from verified fishermen across the Philippines — delivered fresh to your door.
        </p>
      </div>

      <?php $fp_limit = 9; ?>
      <?php include('./components/products.php'); ?>

    </div>

    <!-- View All CTA -->
    <div class="text-center mt-10" data-aos="fade-up">
      <a href="<?= $baseUrl ?>shop"
         class="inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-8 rounded-xl transition-all duration-200 hover:-translate-y-0.5 shadow-sm hover:shadow-md">
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
       BRAND QUOTE — Preline blockquote card style
  ══════════════════════════════════════════════ -->
  <section class="relative py-24 bg-amber-50 overflow-hidden border-y border-amber-100">
    <!-- Decorative giant quote mark -->
    <div class="quote-mark select-none" aria-hidden="true">"</div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
      <!-- Preline blockquote-style card -->
      <figure class="max-w-3xl mx-auto">
        <blockquote>
          <p class="font-display text-2xl md:text-3xl italic text-gray-700 leading-relaxed">
            "Every fish that passes through our hands carries with it a commitment — to the fishermen who caught it,
            the families who will eat it, and the ocean that gave it."
          </p>
        </blockquote>
        <figcaption class="mt-8 flex items-center justify-center gap-3">
          <div class="w-12 h-px bg-orange-300"></div>
          <span class="text-sm font-semibold text-gray-500">St. Joseph Fish Brokerage, Inc.</span>
          <div class="w-12 h-px bg-orange-300"></div>
        </figcaption>
      </figure>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       BLOG / NEWS — Preline card grid
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <div class="text-center mb-12" data-aos="fade-up">
        <span class="inline-flex items-center gap-2 mb-3 text-xs font-bold tracking-[.15em] uppercase text-orange-500
                     before:block before:w-8 before:h-px before:bg-orange-400">
          Latest Insights
        </span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Blogs / News &amp; Updates</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
          Stay informed with the latest news from St. Joseph and the Philippine fishing industry.
        </p>
      </div>

      <?php if (!empty($blogs)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($blogs as $idx => $blog): ?>

        <!-- Preline card with hover lift -->
        <a href="/blogs/<?= $blog['blog_slug'] ?>"
           class="group flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300"
           data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 100 ?>">

          <!-- Image -->
          <div class="relative h-56 overflow-hidden flex-none">
            <?php if (!empty($blog['blog_featured_image'])): ?>
              <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>"
                   alt="<?= htmlspecialchars($blog['blog_title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full bg-gradient-to-br from-orange-500 via-orange-400 to-amber-300 flex items-center justify-center">
                <span class="text-white text-4xl font-bold font-display">SJ</span>
              </div>
            <?php endif; ?>

            <!-- Status badge — Preline badge -->
            <div class="absolute top-4 left-4">
              <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-semibold bg-orange-600 text-white">
                <?= ucfirst($blog['blog_status']) ?>
              </span>
            </div>
          </div>

          <!-- Body -->
          <div class="flex flex-col flex-1 p-6">
            <!-- Author row -->
            <div class="flex items-center gap-3 mb-4">
              <span class="inline-flex items-center justify-center size-10 rounded-full bg-orange-100 text-orange-600 font-semibold text-sm flex-shrink-0">
                <?= strtoupper(substr($blog['blog_author'] ?? 'A', 0, 1)) ?>
              </span>
              <div>
                <p class="text-sm font-medium text-gray-900 leading-none mb-0.5">
                  <?= htmlspecialchars($blog['blog_author'] ?? 'Admin') ?>
                </p>
                <p class="text-xs text-gray-400"><?= date('F d, Y', strtotime($blog['blog_published_date'])) ?></p>
              </div>
            </div>

            <h3 class="font-display text-lg font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($blog['blog_title']) ?>
            </h3>
            <p class="text-gray-500 text-sm mb-4 line-clamp-3 flex-1">
              <?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?>
            </p>

            <!-- Read more link -->
            <div class="flex items-center gap-1 text-orange-600 font-semibold text-sm mt-auto">
              Read More
              <svg class="size-4 group-hover:translate-x-1.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>

        </a>
        <?php endforeach; ?>
      </div>

      <?php else: ?>

      <!-- Preline empty state -->
      <div class="text-center py-20" data-aos="fade-up">
        <div class="flex justify-center mb-4">
          <span class="inline-flex items-center justify-center size-20 rounded-full bg-orange-50">
            <svg class="size-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/>
            </svg>
          </span>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No blog posts yet</h3>
        <p class="mt-2 text-sm text-gray-500">Check back soon for updates from St. Joseph Fish Brokerage Inc.</p>
      </div>

      <?php endif; ?>

    </div>
  </section>

  <?php $conn->close(); ?>
  <?php include './components/footer.php'; ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <?php include('live_chat.php'); ?>
  <script src="./functions/product_process.js"></script>

  <script>
    // ── AOS init ──────────────────────────────────────────
    AOS.init({ once: true, easing: 'ease-out-cubic', duration: 750 });

    // ── Smooth anchor scroll ──────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); window.scrollTo({ top: t.offsetTop - 80, behavior: 'smooth' }); }
      });
    });

    // ── GTM ───────────────────────────────────────────────
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');

    // ── Hero slider ───────────────────────────────────────
    (function () {
      const DELAY   = 5200;
      const slides  = document.querySelectorAll('.hero-slide');
      const dots    = document.querySelectorAll('.hero-dot');
      const track   = document.getElementById('hero-track');
      const progress= document.getElementById('hero-progress');
      const counter = document.getElementById('hero-counter');
      const total   = slides.length;
      const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      let cur = 0, timer = null, paused = false;

      function goTo(n) {
        const prev = cur;
        cur = ((n % total) + total) % total;

        track.style.transform = `translateX(-${cur * 100}%)`;

        slides[prev].classList.remove('is-active');
        slides[prev].setAttribute('aria-hidden', 'true');
        slides[cur].classList.add('is-active');
        slides[cur].setAttribute('aria-hidden', 'false');

        dots[prev].classList.remove('is-active', '!bg-orange-600');
        dots[prev].setAttribute('aria-selected', 'false');
        dots[cur].classList.add('is-active', '!bg-orange-600');
        dots[cur].setAttribute('aria-selected', 'true');

        if (counter) counter.textContent = `${cur + 1} / ${total}`;
        startProgress();
      }

      function startProgress() {
        if (reduced || !progress) return;
        progress.style.transition = 'none';
        progress.style.width = '0%';
        void progress.offsetWidth;
        progress.style.transition = `width ${DELAY}ms linear`;
        progress.style.width = '100%';
      }

      function startAuto() {
        clearInterval(timer);
        if (!paused) timer = setInterval(() => goTo(cur + 1), DELAY);
      }
      function pause()  { paused = true;  clearInterval(timer); if (progress) progress.style.transition = 'none'; }
      function resume() { paused = false; startAuto(); startProgress(); }

      document.getElementById('hero-prev').addEventListener('click', () => { goTo(cur - 1); startAuto(); });
      document.getElementById('hero-next').addEventListener('click', () => { goTo(cur + 1); startAuto(); });
      dots.forEach((dot, i) => dot.addEventListener('click', () => { goTo(i); startAuto(); }));

      // Keyboard
      document.getElementById('hero-wrap').addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft')  { goTo(cur - 1); startAuto(); }
        if (e.key === 'ArrowRight') { goTo(cur + 1); startAuto(); }
      });

      // Touch swipe
      let tx = 0, ty = 0, dragging = false;
      const wrap = document.getElementById('hero-wrap');
      wrap.addEventListener('touchstart', e => { tx = e.touches[0].clientX; ty = e.touches[0].clientY; dragging = true; }, { passive: true });
      wrap.addEventListener('touchmove', e => {
        if (!dragging) return;
        if (Math.abs(e.touches[0].clientX - tx) > Math.abs(e.touches[0].clientY - ty) + 10) e.preventDefault();
      }, { passive: false });
      wrap.addEventListener('touchend', e => {
        if (!dragging) return;
        dragging = false;
        const dx = e.changedTouches[0].clientX - tx;
        const dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 45) { goTo(cur + (dx < 0 ? 1 : -1)); startAuto(); }
      }, { passive: true });

      // Pause on hover/focus
      wrap.addEventListener('mouseenter', pause);
      wrap.addEventListener('mouseleave', resume);
      wrap.addEventListener('focusin', pause);
      wrap.addEventListener('focusout', e => { if (!wrap.contains(e.relatedTarget)) resume(); });
      document.addEventListener('visibilitychange', () => document.hidden ? pause() : resume());

      if (reduced) { paused = true; if (progress) progress.style.display = 'none'; }

      goTo(0);
      startAuto();
    })();
  </script>
</body>
</html>