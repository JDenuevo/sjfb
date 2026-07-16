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

  <title>About | St. Joseph Fish Brokerage Inc. — Leading Fish Broker in the Philippines</title>
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
    "foundingDate": "1981",
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
  <link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

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

  <!-- ═══════════════════════════════════════
       HISTORY — hero + "Our Story" (already built in)
  ═══════════════════════════════════════ -->
  <div class="">
    <?php include('./components/history.php'); ?>
  </div>

  <!-- ═══════════════════════════════════════
       PHOTO MOSAIC — atmosphere, no captions
  ═══════════════════════════════════════ -->
  <?php include('./components/photo_mosaic.php'); ?>

  <!-- ═══════════════════════════════════════
       CORE VALUES
  ═══════════════════════════════════════ -->
  <div class="">
    <?php include('./components/core_values.php'); ?>
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