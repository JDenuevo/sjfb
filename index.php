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
  
  <link rel="canonical" href="https://fishbrokers.net/">
  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <style>
    /* ── Core tokens (mirrors sustainability.php) ── */
    body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

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

    .home-hero {
      height: 90vh;
      min-height: 580px;
    }

    .home-hero__bg {
      transform: scale(1.05);
      transition: transform 8s ease-out;
    }
    .home-hero__bg.loaded { transform: scale(1); }
    
    /* Wave divider */
    .wave-divider { display: block; line-height: 0; }

    /* ── Scroll indicator bounce ── */
    @keyframes soft-bounce {
      0%,100% { transform: translateY(0); }
      40%      { transform: translateY(-12px); }
      70%      { transform: translateY(-5px); }
    }
    .animate-soft-bounce { animation: soft-bounce 2s ease-in-out infinite; }

    /* ── Stat card impact numbers ── */
    .impact-num {
      font-family: 'Playfair Display', serif;
      font-size: 3rem;
      font-weight: 700;
      background: linear-gradient(135deg, #fff, rgba(255,255,255,.75));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      line-height: 1;
    }

    /* ── Feature card hover accent line ── */
    .feat-card {
      position: relative;
      overflow: hidden;
    }
    .feat-card::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, #f97316, #fbbf24);
      transform: scaleX(0);
      transition: transform .3s ease;
    }
    .feat-card:hover::after { transform: scaleX(1); }

    /* ── Check dot (from sustainability) ── */
    .check-dot {
      width: 1.5rem; height: 1.5rem;
      border-radius: 50%;
      background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24 100%);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    /* ── Blog card ── */
    .blog-card {
      transition: transform .3s ease, box-shadow .3s ease;
    }
    .blog-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(249,115,22,.12);
    }

    /* ── Image zoom (from sustainability) ── */
    .img-zoom { position: relative; overflow: hidden; border-radius: 1.5rem; }
    .img-zoom img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s ease; }
    .img-zoom:hover img { transform: scale(1.04); }

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

<body id="content" class="bg-white">

  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>
  <?php include './components/preloaders.php'; ?>

  <!-- ══════════════════════════════════════════════
       HERO — parallax bg + orange-to-amber gradient
  ══════════════════════════════════════════════ -->
    <div class="home-hero relative overflow-hidden rounded-2xl items-center" data-aos="fade-down">
      <img src="./assets/images/contents/herobanner.png" 
        alt="St. Joseph Fish Brokerage Navotas Fish Port Complex" 
        loading="eager" 
        class="absolute bottom-0 left-0 w-full h-auto min-h-full object-cover z-0 home-hero__bg">
      <div class="relative z-20 h-full flex flex-col justify-center items-center text-center px-4">
        <!-- Scroll indicator -->
        <a id="scroll-indicator" href="#explore-more"
          class="absolute bottom-10 pb-5 left-1/2 -translate-x-1/2 z-20 flex flex-col items-center gap-1 cursor-pointer animate-soft-bounce text-white hover:text-white transition-colors">
          
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 7a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4z"/>
            <path d="M12 7v4"/>
          </svg>

          <span class="text-xs font-medium">Scroll to explore</span>
        </a>
      </div>
      <!-- Wave bottom -->
      <div class="absolute bottom-0 left-0 right-0 z-10">
        <svg viewBox="0 0 1440 80" fill="none" preserveAspectRatio="none" style="width:100%;display:block">
          <path d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,50 1440,40 L1440,80 L0,80 Z" fill="white"/>
        </svg>
      </div>
    </div>
  <!-- End Hero -->

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

  <!-- ══════════════════════════════════════════════
       CTA SECTION — full gradient (sustainability style)
  ══════════════════════════════════════════════ -->
  <section class="relative py-24 overflow-hidden" data-aos="fade-up">
    <div class="absolute inset-0 gradient-orange opacity-95"></div>
    <div class="absolute inset-0" style="background-image:radial-gradient(circle,rgba(255,255,255,.06) 1px,transparent 1px);background-size:28px 28px"></div>

    <div class="relative z-10 max-w-3xl mx-auto px-6 text-center text-white">
      <span class="text-xs font-bold tracking-widest uppercase text-emerald-300 block mb-4">Ready to Order?</span>
      <h2 class="font-display text-3xl md:text-5xl font-bold mb-6">
        Fresh Seafood,<br>Delivered to Your Business
      </h2>
      <p class="text-lg text-white/75 mb-10 leading-relaxed">
        Whether you're a restaurant, retailer, or household — St. Joseph has the freshest catch waiting for you.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center mt-5">
        <a href="./shop.php"
          class="py-3.5 px-8 inline-flex items-center justify-center gap-2 rounded-xl bg-white text-orange-600 font-semibold hover:bg-orange-50 transition-all hover:-translate-y-0.5 shadow-md">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
          Shop Seafood
        </a>
        <a href="./contact.php"
          class="py-3.5 px-8 inline-flex items-center justify-center gap-2 rounded-xl border border-white/40 text-white font-semibold hover:bg-white/10 transition-all hover:-translate-y-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          Contact Us Today
        </a>
      </div>
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
  </script>
</body>
</html>