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

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">

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
       CORE VALUES
  ═══════════════════════════════════════ -->
  <div class="">
    <?php include('./components/history.php'); ?>
  </div>

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