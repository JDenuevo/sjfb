<?php
session_start();
include 'conn.php';

$pageTitle = 'Home';

$blogsQuery = "SELECT * FROM blogs WHERE blog_status = 'published' ORDER BY blog_published_date DESC LIMIT 3";
$blogsResult = mysqli_query($conn, $blogsQuery);
$blogs = mysqli_fetch_all($blogsResult, MYSQLI_ASSOC);

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
  <script>
    // Extend Tailwind's CDN config so the hero animations, fonts, and
    // brand gradient are all real utility classes instead of hand-written CSS.
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans:    ['Lexend', 'sans-serif'],
            display: ['"Playfair Display"', 'Georgia', 'serif'],
          },
          backgroundImage: {
            'grad-orange': 'linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%)',
          },
          keyframes: {
            'fade-up':     { '0%': { opacity: 0, transform: 'translateY(16px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
            'fade-in':     { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
            'pulse-dot':   { '0%,100%': { opacity: 1, transform: 'scale(1)' }, '50%': { opacity: .5, transform: 'scale(.7)' } },
            'scroll-pulse':{ '0%,100%': { opacity: .8, transform: 'scaleY(1)' }, '50%': { opacity: .25, transform: 'scaleY(.55)' } },
          },
          animation: {
            'fade-up-1': 'fade-up .7s ease .35s forwards',
            'fade-up-2': 'fade-up .8s ease .5s forwards',
            'fade-up-3': 'fade-up .7s ease .68s forwards',
            'fade-up-4': 'fade-up .7s ease .85s forwards',
            'fade-in-1': 'fade-in .8s ease 1.1s forwards',
            'fade-in-2': 'fade-in .8s ease 1.5s forwards',
            'fade-in-3': 'fade-in 1s ease 1.3s forwards',
            'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
            'scroll-pulse': 'scroll-pulse 2s ease-in-out 1.5s infinite',
          },
        },
      },
    };
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <style>
    .hero-anim { opacity: 0; }

    @media (prefers-reduced-motion: reduce) {
      .hero-anim { opacity: 1 !important; animation: none !important; transform: none !important; }
    }
  </style>
</head>

<body class="bg-white font-sans" id="content">
  <?php include './components/preloaders.php'; ?>
  <?php include './components/navigation.php'; ?>
  <?php include './components/nav_crumb.php'; ?>

  <!-- ══════════════════════════════════════════
       HERO — FULL-BLEED VIDEO OVERVIEW
  ═══════════════════════════════════════════════ -->
  <section id="hero" role="banner" class="relative w-full h-[100svh] min-h-[560px] max-h-[900px] overflow-hidden bg-slate-900">

    <!-- Poster fallback shown until video is ready -->
    <div id="hero-poster"
         class="absolute inset-0 bg-cover bg-center"
         style="background-image:url('<?=$baseUrl?>assets/images/contents/hero_banner1.jpg')"></div>

    <!--
      REPLACE THE src BELOW with your actual video file.
      Recommended: .mp4 H.264, 1920×1080, 8–15 Mbps, muted autoplay, 20–40s loop.
      Host on your server at /assets/videos/Company-Branding.mp4
      For best performance, also provide a WebM version.
    -->
    <video id="hero-video"
           autoplay muted loop playsinline
           preload="metadata"
           poster="<?=$baseUrl?>assets/images/contents/hero_banner1.jpg"
           class="absolute inset-0 w-full h-full object-cover object-center opacity-0 transition-opacity duration-[1400ms]">
      <source src="<?=$baseUrl?>assets/videos/Company-Branding.mp4"  type="video/mp4">
      <source src="<?=$baseUrl?>assets/videos/hero.webm" type="video/webm">
    </video>

    <!-- Cinematic top/bottom overlay for text legibility -->
    <div class="absolute inset-0 pointer-events-none bg-gradient-to-b from-slate-900/55 via-slate-900/15 to-slate-900/85"></div>
    <!-- Side overlay, stronger on the left where text sits -->
    <div class="absolute inset-0 pointer-events-none bg-gradient-to-r from-slate-900/60 via-transparent to-transparent"></div>

    <!-- Live chip, top-right -->
    <div class="hero-anim animate-fade-in-1 absolute z-10 top-5 sm:top-8 right-5 sm:right-12 lg:right-20">
      <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-sm text-white text-[.68rem] font-bold uppercase tracking-widest">
        <span class="animate-pulse-dot w-1.5 h-1.5 rounded-full bg-orange-400 shrink-0"></span>
        Navotas Fish Port — Live Operations
      </span>
    </div>

    <!-- Hero content -->
    <div class="absolute inset-0 z-10 flex flex-col justify-end px-5 sm:px-12 lg:px-20 pb-14 sm:pb-20 lg:pb-24">

      <p class="hero-anim animate-fade-up-1 inline-flex items-center gap-2 text-[.7rem] font-bold uppercase tracking-widest text-orange-400 mb-4">
        Established 1979 &mdash; Philippines
      </p>

      <h1 class="hero-anim animate-fade-up-2 font-display font-bold text-white leading-[1.05] max-w-[16ch]
                 text-[clamp(2.25rem,6vw,4.75rem)] tracking-tight [text-shadow:0_2px_24px_rgba(0,0,0,.25)]">
        Isda sa<br>Hapag ng<br>Bawat isa.
      </h1>

      <p class="hero-anim animate-fade-up-3 mt-5 text-[clamp(.9375rem,1.2vw,1.0625rem)] font-light text-white/70 max-w-[46ch] leading-relaxed">
        St. Joseph Fish Brokerage Inc. connects verified Filipino fishermen to institutional markets
        nationwide — with transparency, integrity, and nearly five decades of trust.
      </p>

      <div class="hero-anim animate-fade-up-4 mt-8 flex flex-wrap gap-3.5">
        <a href="<?=$baseUrl?>about"
           class="inline-flex items-center gap-2 py-3.5 px-8 rounded-full bg-grad-orange text-white text-sm font-semibold
                  shadow-[0_4px_20px_rgba(249,115,22,.4)] hover:-translate-y-0.5 hover:shadow-[0_8px_28px_rgba(249,115,22,.5)]
                  transition-all duration-200">
          Our Company
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="#operations"
           class="inline-flex items-center gap-2 py-3.5 px-8 rounded-full bg-white/10 border border-white/25 backdrop-blur-sm
                  text-white/85 text-sm font-medium hover:bg-white/[.16] hover:border-white/45 hover:text-white
                  transition-all duration-200">
          Our Operations
        </a>
      </div>
    </div>

    <!-- Scroll hint -->
    <div class="hero-anim animate-fade-in-3 hidden sm:flex absolute z-10 bottom-7 right-5 sm:right-12 lg:right-20 flex-col items-center gap-2" aria-hidden="true">
      <span class="text-[.625rem] font-semibold uppercase tracking-[.18em] text-white/45 [writing-mode:vertical-lr]">Scroll</span>
      <div class="animate-scroll-pulse w-px h-10 bg-gradient-to-b from-orange-400 to-transparent"></div>
    </div>

    <!-- Mute / unmute -->
    <button id="mute-btn" type="button" aria-label="Toggle video sound"
            class="hero-anim animate-fade-in-2 absolute z-10 bottom-7 left-5 sm:left-12 lg:left-20
                   flex items-center gap-2 py-2 px-4 rounded-full bg-slate-900/50 border border-white/[.18]
                   text-white/75 text-xs font-medium backdrop-blur-md cursor-pointer
                   hover:text-white hover:border-white/40 hover:bg-slate-900/70 transition-all duration-200">
      <svg id="icon-muted" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="shrink-0">
        <path d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
        <path d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <svg id="icon-sound" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="shrink-0 hidden">
        <path d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-6.414-3H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
      </svg>
      <span id="mute-label" class="hidden sm:inline">Sound off</span>
    </button>
  </section>

  <!-- ═══════════════════════════════════════
       STATS STRIP
  ═══════════════════════════════════════ -->
  <div class="bg-slate-900 border-b border-orange-500/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-orange-500/10">
        <?php
        $stats = [
          ['47+',   'Years in Operation', 'Since 1979'],
          ['1000+',  'Verified Fishermen', 'Direct network'],
          ['4',     'Port Locations',     'Luzon &amp; Mindanao'],
          ['100K+++', 'Kilos Daily',        'Fresh, not frozen'],
        ];
        foreach ($stats as [$n,$l,$s]):
        ?>
        <div class="py-7 px-5 sm:px-6">
          <div class="font-display bg-grad-orange bg-clip-text text-transparent text-3xl sm:text-4xl font-bold leading-none"><?= $n ?></div>
          <div class="text-white text-xs font-semibold tracking-wide mt-2"><?= $l ?></div>
          <div class="text-slate-500 text-[.6875rem] mt-0.5"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       EXPLORE / ABOUT COMPONENT
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-gray-200" id="explore-more">
    <?php include('./components/explore.php'); ?>
  </section>

  <!-- ═══════════════════════════════════════
       COMPANY OVERVIEW
  ═══════════════════════════════════════ -->
  <section class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
      <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.7rem] font-bold uppercase tracking-widest mb-5">
        <span class="animate-pulse-dot w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
        Company Overview
      </span>
      <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-6 leading-tight">
        More Than a Fish Broker —<br>
        <span class="bg-grad-orange bg-clip-text text-transparent">A Partner in Philippine Seafood</span>
      </h2>
      <p class="text-lg text-slate-500 leading-relaxed">
        With over <strong class="text-orange-600">47 years</strong> of operations spanning
        <strong class="text-orange-600">Navotas</strong>, <strong class="text-orange-600">Malabon</strong>,
        <strong class="text-orange-600">Lucena</strong>, <strong class="text-orange-600">Davao</strong>,
        and growing, we serve as the critical link between hardworking Filipino fishermen and the buyers
        who depend on fresh, traceable seafood every day.
      </p>
    </div>
  </section>

   <!-- ═══════════════════════════════════════
       OPERATIONS / LOCATIONS
  ═══════════════════════════════════════ -->
  <section id="operations">
    <?php include('./components/port_operations.php'); ?>
  </section>

  <!-- ═══════════════════════════════════════
       BLOG / NEWS
  ═══════════════════════════════════════ -->
  <?php if (!empty($blogs)): ?>
  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <div class="flex flex-wrap items-end justify-between gap-4 mb-10" data-aos="fade-up">
        <div>
          <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-100 border border-orange-200 text-orange-600 text-[.68rem] font-bold uppercase tracking-widest mb-3">
            <span class="animate-pulse-dot w-1.5 h-1.5 rounded-full bg-orange-500 shrink-0"></span>
            Latest Insights
          </span>
          <h2 class="font-display text-2xl lg:text-3xl font-bold text-slate-900">Blogs / News &amp; Updates</h2>
        </div>
        <a href="<?= $baseUrl ?>blogs"
           class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 hover:text-orange-700 transition-colors">
          All Blogs
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
        <?php foreach ($blogs as $idx => $blog): ?>
        <a href="/blogs/<?= $blog['blog_slug'] ?>"
           class="group flex flex-col bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
           data-aos="fade-up" data-aos-delay="<?= $idx * 80 ?>">

          <div class="relative h-48 overflow-hidden flex-none">
            <?php if (!empty($blog['blog_featured_image'])): ?>
              <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>" alt="<?= htmlspecialchars($blog['blog_title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full bg-grad-orange flex items-center justify-center">
                <span class="text-white text-3xl font-bold font-display">SJ</span>
              </div>
            <?php endif; ?>
            <div class="absolute top-3 left-3">
              <span class="inline-flex items-center py-1 px-3 rounded-full text-[.65rem] font-bold bg-white/90 text-orange-600">
                <?= date('M d, Y', strtotime($blog['blog_published_date'])) ?>
              </span>
            </div>
          </div>

          <div class="flex flex-col flex-1 p-5">
            <p class="text-xs font-semibold text-slate-400 mb-2"><?= htmlspecialchars($blog['blog_author'] ?? 'SJFB Editorial') ?></p>
            <h3 class="font-display text-lg font-bold text-slate-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($blog['blog_title']) ?>
            </h3>
            <p class="text-slate-500 text-sm mb-4 line-clamp-2 flex-1"><?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?></p>
            <div class="flex items-center gap-1 text-orange-600 font-semibold text-sm">
              Read More
              <svg class="size-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════
       CTA BANNER
  ═══════════════════════════════════════ -->
  <div class="relative overflow-hidden shadow-lg" data-aos="fade-up">

    <img src="<?= $baseUrl ?>assets/images/contents/hero_banner2.jpg" alt="St. Joseph Fish Brokerage operations"
         loading="lazy" class="absolute inset-0 w-full h-full object-cover z-0">

    <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/90 via-black/60 to-black/30"></div>

    <div class="relative z-20 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center space-y-7">

      <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-sm text-white text-[.7rem] font-bold uppercase tracking-widest">
        <span class="animate-pulse-dot w-2 h-2 rounded-full bg-orange-400 shrink-0"></span>
        Partner With Us
      </span>

      <h2 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
        Ready to Work With the<br>
        <span class="bg-grad-orange bg-clip-text text-transparent">Philippines&rsquo; Largest Fish Broker?</span>
      </h2>

      <div class="flex flex-wrap justify-center gap-3 pt-2">
        <a href="<?= $baseUrl ?>contact"
           class="inline-flex items-center gap-2 text-white text-sm font-semibold bg-grad-orange rounded-full py-3.5 px-8
                  shadow-[0_4px_20px_rgba(249,115,22,.4)] hover:-translate-y-0.5 hover:shadow-[0_8px_28px_rgba(249,115,22,.5)]
                  transition-all duration-200">
          Contact Our Team
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
        <a href="<?= $baseUrl ?>about"
           class="inline-flex items-center gap-2 text-white text-sm font-semibold bg-white/10 border border-white/25 backdrop-blur-sm
                  rounded-full py-3.5 px-7 hover:bg-white/20 transition-all duration-200">
          Company Profile
        </a>
      </div>

    </div>
  </div>

  <?php $conn->close(); ?>
  <?php include './components/footer.php'; ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <?php include('live_chat.php'); ?>

  <script>
    AOS.init({ once: true, easing: 'ease-out-cubic', duration: 700 });

    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); window.scrollTo({ top: t.offsetTop - 80, behavior: 'smooth' }); }
      });
    });

    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');

    /* Video load fade-in */
    const vid = document.getElementById('hero-video');
    if (vid) {
      vid.addEventListener('canplaythrough', () => vid.classList.remove('opacity-0'), { once: true });
      /* Fallback: if video 404s or takes too long, poster stays visible */
      vid.addEventListener('error', () => { vid.style.display = 'none'; }, { once: true });
    }

    /* Mute toggle */
    const muteBtn   = document.getElementById('mute-btn');
    const muteLabel = document.getElementById('mute-label');
    const iconMuted = document.getElementById('icon-muted');
    const iconSound = document.getElementById('icon-sound');
    let muted = true;
    if (muteBtn && vid) {
      muteBtn.addEventListener('click', () => {
        muted = !muted;
        vid.muted = muted;
        muteLabel.textContent = muted ? 'Sound off' : 'Sound on';
        iconMuted.classList.toggle('hidden', !muted);
        iconSound.classList.toggle('hidden', muted);
      });
    }
  </script>
</body>
</html>