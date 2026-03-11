<?php
session_start();
include 'conn.php';

$pageTitle = 'Shop';

$showCategories = true;
$showMobileCategories = true;

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
    ORDER BY p.created_at DESC";

$productsResult = $conn->query($productsQuery);

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
  
  <title>Fresh Seafood Shop in the Philippines | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="Buy fresh seafood online in the Philippines. St. Joseph Fish Brokerage Inc. delivers premium bangus, tilapia, and more directly from Filipino fishermen to your door.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/shop">
  <meta property="og:title" content="Fresh Seafood Shop | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Buy fresh seafood online — bangus, tilapia, tinapa & more. Sourced directly from Filipino fishermen. Wholesale & retail. Nationwide delivery.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Fresh Seafood Shop | St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Buy fresh seafood online — sourced directly from Filipino fishermen. Nationwide delivery.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Store",
    "name": "St. Joseph Fish Brokerage Inc. – Seafood Shop",
    "url": "https://fishbrokers.net/shop.php",
    "description": "Buy fresh, premium seafood online in the Philippines. Wholesale and retail orders welcome.",
    "areaServed": "Philippines",
    "telephone": "(+63) 946-497-3689",
    "priceRange": "₱₱",
    "hasMap": "https://maps.app.goo.gl/navotas"
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

</head>

<style>
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
  /* Gradient bg */
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

  /* Pulsing dot */
  @keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(.7); }
  }
  .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

  /* Floating badge */
  @keyframes float-y {
    0%,100% { transform:translateY(0); }
    50%      { transform:translateY(-6px); }
  }
  .float-anim { animation: float-y 3.5s ease-in-out infinite; }

  /* Trust badge row scroll on mobile */
  .trust-scroll { overflow-x: auto; scrollbar-width: none; }
  .trust-scroll::-webkit-scrollbar { display: none; }
</style>

<body id="content">

  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>
  <?php include './components/preloaders.php'; ?>

  <!-- ═══════════════════════════════════════
       SHOP HERO HEADER
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden bg-white dot-grid">

    <!-- Glow blob -->
    <div class="pointer-events-none absolute -top-24 -right-32 w-[480px] h-[480px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.11)_0%,transparent_70%)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-20 w-[320px] h-[320px] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,.08)_0%,transparent_70%)]" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-12">

      <div class="grid lg:grid-cols-2 gap-10 items-center">

        <!-- Left: copy -->
        <div data-aos="fade-right" data-aos-duration="700">

          <!-- Eyebrow -->
          <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-5">
            <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
            Fresh Catch — Updated Daily
          </div>

          <h1 class="ff-display text-4xl lg:text-5xl font-bold text-slate-900 leading-tight tracking-tight mb-5">
            Premium <em class="text-grad not-italic">Fresh Seafood</em><br>Delivered to Your Door
          </h1>

          <p class="text-slate-500 leading-relaxed max-w-lg mb-8">
            Sourced directly from verified Filipino fishermen across the Philippines. Fresh bangus, tilapia, tinapa, and more — available for retail and wholesale orders with nationwide delivery.
          </p>

          <!-- Quick benefit pills -->
          <div class="flex flex-wrap gap-2.5 mb-8">
            <?php
            $pills = [
              ['icon'=>'M5 13l4 4L19 7', 'text'=>'Direct from Fishermen'],
              ['icon'=>'M5 13l4 4L19 7', 'text'=>'HACCP Certified'],
              ['icon'=>'M5 13l4 4L19 7', 'text'=>'Nationwide Delivery'],
              ['icon'=>'M5 13l4 4L19 7', 'text'=>'Wholesale Available'],
            ];
            foreach ($pills as $pill): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-xs font-semibold">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="<?= $pill['icon'] ?>"/></svg>
                <?= $pill['text'] ?>
              </span>
            <?php endforeach; ?>
          </div>

          <!-- CTA buttons -->
          <div class="flex flex-wrap gap-3">
            <a href="#products" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-grad shadow-[0_4px_16px_rgba(249,115,22,.28)] hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(249,115,22,.38)] transition-all duration-200">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              Browse Products
            </a>
            <a href="contact.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-orange-600 bg-transparent border border-orange-200 hover:bg-orange-50 hover:border-orange-300 hover:-translate-y-px transition-all duration-200">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.98 3.4 2 2 0 0 1 3.94 1.01h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              Bulk Order Inquiry
            </a>
          </div>
        </div>

        <!-- Right: image + floating stats -->
        <div class="relative" data-aos="fade-left" data-aos-duration="800" data-aos-delay="100">

          <div class="relative rounded-2xl overflow-hidden shadow-[0_24px_64px_rgba(249,115,22,.16),0_4px_16px_rgba(15,23,42,.08)] group">
            <img
              src="./assets/images/contents/shop_1.png"
              alt="Fresh seafood available at St. Joseph Fish Brokerage Inc. shop"
              class="w-full h-72 lg:h-80 object-cover block transition-transform duration-700 group-hover:scale-[1.03]"
              loading="eager" width="640" height="320">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-orange-900/20 to-transparent"></div>
          </div>

          <!-- Floating stat: orders -->
          <div class="float-anim absolute -bottom-5 -left-5 z-10 flex items-center gap-2.5 bg-white border border-orange-200 rounded-xl px-4 py-3 shadow-[0_8px_32px_rgba(249,115,22,.2)] whitespace-nowrap">
            <span class="bg-grad flex items-center justify-center w-9 h-9 rounded-xl shrink-0">
              <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </span>
            <div>
              <strong class="block text-[.8rem] font-bold text-slate-900 leading-tight">Daily Orders</strong>
              <span class="text-[.68rem] text-slate-400">Packed fresh every morning</span>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Trust badge strip ── -->
      <div class="trust-scroll mt-10" data-aos="fade-up" data-aos-delay="150">
        <div class="flex items-center gap-3 min-w-max pb-1">

          <?php
          $trust = [
            ['icon'=>'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z M9 12l2 2 4-4', 'title'=>'HACCP Certified', 'sub'=>'Food-safe cold chain'],
            ['icon'=>'M5 13l4 4L19 7',                                              'title'=>'BFAR Licensed',    'sub'=>'Regulatory compliant'],
            ['icon'=>'M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z',       'title'=>'Nationwide Delivery','sub'=>'All major islands'],
            ['icon'=>'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10','title'=>'Navotas Hub',    'sub'=>'Largest fish port PH'],
            ['icon'=>'M12 2L2 7l10 5 10-5-10-5z M2 17l10 5 10-5 M2 12l10 5 10-5', 'title'=>'500+ Suppliers',   'sub'=>'Verified fishermen'],
            ['icon'=>'M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16','title'=>'Wholesale Ready','sub'=>'Bulk orders welcome'],
          ];
          foreach ($trust as $t): ?>
            <div class="flex items-center gap-2.5 px-4 py-2.5 bg-orange-50 border border-orange-200 rounded-xl shrink-0 hover:bg-orange-100 transition-colors">
              <span class="bg-grad flex items-center justify-center w-8 h-8 rounded-lg shrink-0">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2" aria-hidden="true"><path d="<?= $t['icon'] ?>"/></svg>
              </span>
              <div>
                <strong class="block text-[.78rem] font-bold text-slate-800 leading-tight"><?= $t['title'] ?></strong>
                <span class="text-[.68rem] text-slate-400"><?= $t['sub'] ?></span>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>

    </div>
  </div>
  <!-- /shop hero -->

  <!-- ═══════════════════════════════════════
       PRODUCTS GRID
  ═══════════════════════════════════════ -->
  <div id="products" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-14 mb-10">

    <!-- Section label -->
    <div class="flex flex-wrap items-end justify-between gap-4 mb-8" data-aos="fade-up">
      <div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.68rem] font-bold uppercase tracking-widest mb-3">
          <span class="pulse-dot w-1.5 h-1.5 rounded-full bg-orange-500 shrink-0"></span>
          Today's Selection
        </div>
        <h2 class="ff-display text-2xl lg:text-3xl font-bold text-slate-900 leading-tight">
          Fresh Seafood <span class="text-grad">Available Now</span>
        </h2>
        <p class="text-slate-400 text-sm mt-1">Inventory updated daily from Navotas Fish Port Complex</p>
      </div>
      <!-- Freshness badge -->
      <div class="flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-200 rounded-xl text-sm font-semibold text-green-700">
        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></span>
        Live Inventory — Updated Today
      </div>
    </div>

    <?php include('./components/products.php'); ?>

  </div>
  <!-- /products -->

  <!-- ═══════════════════════════════════════
       WHY BUY FROM US — 3-col cards
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden bg-orange-50 border-y border-orange-200 py-16" data-aos="fade-up">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-4">
          <span class="pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
          Why Buy From Us?
        </div>
        <h2 class="ff-display text-2xl lg:text-3xl font-bold text-slate-900">
          More Than Just a <span class="text-grad">Fish Market</span>
        </h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $cards = [
          [
            'icon'  => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z M9 12l2 2 4-4',
            'title' => 'Guaranteed Freshness',
            'desc'  => 'Every product is sourced same-day from our Navotas hub and kept in a HACCP-compliant cold chain — so freshness is never a question.',
          ],
          [
            'icon'  => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75 M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0-8 0',
            'title' => 'Verified Supplier Network',
            'desc'  => 'We partner only with 500+ BFAR-registered fishermen and cooperatives — giving you full traceability from sea to table.',
          ],
          [
            'icon'  => 'M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3 M13.5 21 9 21 9 14l-4-4h13l-1 4 M16 14l2 2 4-4',
            'title' => 'Fast Nationwide Delivery',
            'desc'  => 'From Luzon to Mindanao — our multi-port distribution network ensures your order arrives fresh wherever you are in the Philippines.',
          ],
          [
            'icon'  => 'M12 2L2 7l10 5 10-5-10-5z M2 17l10 5 10-5 M2 12l10 5 10-5',
            'title' => 'Wholesale & Retail Orders',
            'desc'  => 'Whether you\'re a household, restaurant, hotel, or wet market stall — we have flexible minimum orders and volume pricing for every buyer.',
          ],
          [
            'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
            'title' => 'Fair & Transparent Pricing',
            'desc'  => 'No hidden markups. Our prices reflect the real market rate — updated daily and visible to every buyer on our platform.',
          ],
          [
            'icon'  => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
            'title' => 'Dedicated Customer Support',
            'desc'  => 'Our team is available to assist with bulk orders, product inquiries, and delivery coordination — every business day.',
          ],
        ];
        foreach ($cards as $i => $c): ?>
          <div
            class="flex gap-4 p-5 bg-white border border-orange-100 rounded-2xl shadow-sm hover:shadow-md hover:border-orange-200 transition-all duration-200"
            data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
            <span class="bg-grad flex items-center justify-center w-11 h-11 rounded-xl shrink-0 shadow-[0_4px_12px_rgba(249,115,22,.25)]">
              <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2" aria-hidden="true"><path d="<?= $c['icon'] ?>"/></svg>
            </span>
            <div>
              <h3 class="text-[.9rem] font-bold text-slate-900 mb-1 leading-snug"><?= $c['title'] ?></h3>
              <p class="text-[.8rem] text-slate-500 leading-relaxed"><?= $c['desc'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <!-- /why buy -->

  <!-- ═══════════════════════════════════════
       CTA BANNER
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden mt-0 shadow-lg" data-aos="fade-up">
    <img
      src="./assets/images/contents/shop_2.png"
      alt="St. Joseph Fish Brokerage Navotas Fish Port Complex"
      loading="lazy"
      class="absolute inset-0 w-full h-full object-cover z-0">

    <!-- Dark overlay for readability -->
    <div class="absolute inset-0 z-10 cta-overlay" style="background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.6) 30%, rgba(0, 0, 0, 0.3) 100%);"></div>

    <div class="relative z-20 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center space-y-7">

      <!-- Member badge -->
      <div class="flex justify-center">
        <a
          class="group inline-flex items-center bg-white/10 border border-white/20 backdrop-blur-sm p-1 ps-4 rounded-full hover:bg-white/15 transition-colors"
          href="./register.php"
          title="Enjoy exclusive member discounts">
          <p class="me-2 text-white text-sm font-medium">
            Members enjoy up to <strong>10% off</strong> + free shipping
          </p>
          <span class="py-1.5 px-2.5 flex items-center gap-1 rounded-full bg-white/10 text-white text-xs font-semibold group-hover:bg-white/20 transition-colors">
            Join Free
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
          </span>
        </a>
      </div>

      <h2 class="ff-display text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
        Become a Member.<br>
        <span class="text-grad">Save More on Every Catch.</span>
      </h2>

      <p class="text-white/80 text-lg max-w-xl mx-auto leading-relaxed">
        Register today to unlock exclusive discounts, faster checkout, order history, and easy reordering for all your fresh seafood needs.
      </p>

      <!-- Benefit pills -->
      <div class="flex flex-wrap justify-center gap-2.5">
        <?php
        $perks = ['Member Discounts','Free Shipping','Priority Orders','Easy Reorders','Order Tracking'];
        foreach ($perks as $perk): ?>
          <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-white text-xs font-semibold backdrop-blur-sm">
            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
            <?= $perk ?>
          </span>
        <?php endforeach; ?>
      </div>

      <div class="flex flex-wrap justify-center gap-3 pt-2">
        <a
          class="inline-flex items-center gap-2 text-white text-sm font-semibold bg-grad rounded-full py-3.5 px-8 shadow-[0_4px_20px_rgba(249,115,22,.4)] hover:-translate-y-0.5 hover:shadow-[0_8px_28px_rgba(249,115,22,.5)] transition-all duration-200"
          href="./register.php"
          title="Join St. Joseph Fish Brokerage Inc. – Member Discounts">
          Register Now & Start Saving
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <a
          class="cursor-pointer inline-flex items-center gap-2 text-white text-sm font-semibold bg-white/10 border border-white/25 backdrop-blur-sm rounded-full py-3.5 px-7 hover:bg-white/20 transition-all duration-200"
          onclick="openModal()">
          Already a member? Sign In
        </a>
      </div>

    </div>
  </div>
  <!-- /cta banner -->

  <?php include('./components/footer.php'); ?>
  <?php include('live_chat.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true, easing: 'ease-out-cubic' });</script>

  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <!-- ✅ products_process.js handles all product card JS (variant selection, add-to-cart, search, share) -->
  <script src="./functions/product_process.js"></script>

</body>
</html>