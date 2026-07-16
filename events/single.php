<?php
session_start();
include '../conn.php';

/*
  URL: /events/[slug]
  .htaccess rewrite:
    RewriteRule ^events/([a-z0-9-]+)/?$ events/single.php?slug=$1 [L,QSA]
*/
$slug = $_GET['slug'] ?? '';
$slug = mysqli_real_escape_string($conn, $slug);

$stmt = $conn->prepare("SELECT * FROM events WHERE event_slug = ? AND event_status = 'published' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
  http_response_code(404);
}

$pageTitle   = 'Events'; // keeps it mapped to /events/ in pageMap
$currentPage = $event ? $event['event_title'] : 'Event Not Found';

$isPast    = $event && strtotime($event['event_date']) < time();

/* Decode gallery JSON if present */
$gallery = [];
if ($event && !empty($event['event_gallery'])) {
  $gallery = json_decode($event['event_gallery'], true) ?? [];
}
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
  <meta name="robots" content="<?= $event ? 'max-snippet:-1, max-image-preview:large, max-video-preview:-1' : 'noindex' ?>">

  <title><?= $event ? htmlspecialchars($event['event_title']) . ' | St. Joseph Fish Brokerage Inc.' : 'Event Not Found | St. Joseph Fish Brokerage Inc.' ?></title>

  <?php if ($event): ?>
  <meta name="description" content="<?= htmlspecialchars($event['event_meta_description'] ?? $event['event_excerpt'] ?? '') ?>">
  <link rel="canonical" href="https://fishbrokers.net/events/<?= htmlspecialchars($slug) ?>">
  <meta property="og:type"        content="article">
  <meta property="og:url"         content="https://fishbrokers.net/events/<?= htmlspecialchars($slug) ?>">
  <meta property="og:title"       content="<?= htmlspecialchars($event['event_title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($event['event_excerpt'] ?? '') ?>">
  <meta property="og:image"       content="<?= htmlspecialchars($event['event_image'] ?? '') ?>">
  <meta name="twitter:card"       content="summary_large_image">

  <!-- Event Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "<?= htmlspecialchars($event['event_title']) ?>",
    "startDate": "<?= $event['event_date'] ?>",
    "endDate": "<?= $event['event_end_date'] ?? $event['event_date'] ?>",
    "eventStatus": "https://schema.org/<?= $isPast ? 'EventScheduled' : 'EventScheduled' ?>",
    "eventAttendanceMode": "<?= str_contains((string)($event['event_location'] ?? ''), 'Online') ? 'https://schema.org/MixedEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode' ?>",
    "location": {
      "@type": "Place",
      "name": "<?= htmlspecialchars($event['event_location'] ?? 'Philippines') ?>",
      "address": "<?= htmlspecialchars($event['event_address'] ?? 'Philippines') ?>"
    },
    "image": ["<?= htmlspecialchars($event['event_image'] ?? '') ?>"],
    "description": "<?= htmlspecialchars($event['event_excerpt'] ?? '') ?>",
    "organizer": {
      "@type": "Organization",
      "name": "St. Joseph Fish Brokerage Inc.",
      "url": "https://fishbrokers.net"
    }
  }
  </script>

  <!-- BreadcrumbList Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home",               "item": "https://fishbrokers.net/" },
      { "@type": "ListItem", "position": 2, "name": "Events & Activities", "item": "https://fishbrokers.net/events/" },
      { "@type": "ListItem", "position": 3, "name": "<?= htmlspecialchars($event['event_title']) ?>", "item": "https://fishbrokers.net/events/<?= htmlspecialchars($slug) ?>" }
    ]
  }
  </script>
  <?php endif; ?>

  <!-- Favicons -->
  <link rel="shortcut icon" href="../assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="../assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="../assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="../assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <style>
    body          { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    #event-hero { height: clamp(260px, 36vw, 460px); }

    /* Sidebar detail grid — aligned labels */
    .nob-row { display: grid; grid-template-columns: 1fr; gap: .25rem 1.5rem; }
    @media (min-width: 640px) {
      .nob-row { grid-template-columns: 130px 1fr; align-items: baseline; }
    }
    .nob-label {
      font-weight: 700;
      color: #c2410c;
      font-size: .75rem;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .nob-value { color: #374151; font-size: .875rem; }

    /* Rich text content */
    .prose-event p   { margin-bottom: 1rem; line-height: 1.75; color: #4b5563; }
    .prose-event h3  { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 700; color: #111827; margin: 1.5rem 0 .75rem; }
    .prose-event ul  { list-style: disc; padding-left: 1.25rem; color: #4b5563; margin-bottom: 1rem; }
    .prose-event ul li { margin-bottom: .375rem; line-height: 1.7; }

    /* Eyebrow */
    .section-eyebrow {
      display: inline-flex; align-items: center; gap: .5rem;
      font-size: .75rem; font-weight: 700; letter-spacing: .15em;
      text-transform: uppercase; color: #fb923c; margin-bottom: .75rem;
    }
    .section-eyebrow::before { content: ''; display: block; width: 2rem; height: 2px; background: #fb923c; }
  </style>
</head>

<body id="content" class="bg-white">
  <?php include('../components/preloaders.php'); ?>
  <?php include('../components/navigation.php'); ?>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-4">
    <?php include('../components/nav_crumb.php'); ?>
  </section>
  <?php if (!$event): ?>

  <!-- ══════════════════════════════════════════════
       404 — EVENT NOT FOUND
  ══════════════════════════════════════════════ -->
  <section class="py-24 bg-white">
    <div class="max-w-xl mx-auto px-6 text-center" data-aos="fade-up">
      <span class="inline-flex items-center justify-center size-16 rounded-full bg-orange-50 mb-6">
        <svg class="size-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </span>
      <h1 class="font-display text-2xl font-bold text-gray-900 mb-2">Event Not Found</h1>
      <p class="text-gray-500 mb-6">The event you're looking for doesn't exist or may have been moved.</p>
      <a href="/events/"
         class="inline-flex items-center gap-2 py-3 px-8 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold transition-all duration-150">
        Back to Events
      </a>
    </div>
  </section>

  <?php else: ?>

  <!-- ══════════════════════════════════════════════
       EVENT HERO
  ══════════════════════════════════════════════ -->
  <section id="event-hero" class="relative w-full overflow-hidden bg-slate-900">
    <?php if (!empty($event['event_image'])): ?>
      <img src="<?= htmlspecialchars($event['event_image']) ?>"
           alt="<?= htmlspecialchars($event['event_title']) ?>"
           class="absolute inset-0 w-full h-full object-cover">
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

    <div class="relative z-10 h-full flex items-end px-5 pb-8 sm:px-12 lg:px-16">
      <div class="max-w-3xl" data-aos="fade-up">
        <div class="flex items-center gap-2 mb-3 flex-wrap">
          <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-bold tracking-widest uppercase bg-orange-600 text-white">
            <?= $isPast ? 'Past Event' : 'Upcoming' ?>
          </span>
          <?php if (!empty($event['event_category'])): ?>
          <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-bold tracking-widest uppercase bg-white/15 text-white border border-white/25 backdrop-blur">
            <?= htmlspecialchars($event['event_category']) ?>
          </span>
          <?php endif; ?>
        </div>
        <h1 class="font-display text-[clamp(1.5rem,4.5vw,3rem)] font-bold text-white leading-tight drop-shadow-lg">
          <?= htmlspecialchars($event['event_title']) ?>
        </h1>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       EVENT DETAILS + SIDEBAR
  ══════════════════════════════════════════════ -->
  <section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-4">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

        <!-- Main content -->
        <div class="lg:col-span-2" data-aos="fade-up">

          <a href="/events/"
             class="inline-flex items-center gap-1.5 text-sm text-orange-600 font-semibold mb-6 hover:underline">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Events
          </a>

          <h2 class="font-display text-2xl font-bold text-gray-900 mb-4">About This Event</h2>
          <div class="prose-event">
            <?php
            /* Render rich content — supports plain text paragraphs or stored HTML */
            $content = $event['event_content'] ?? $event['event_excerpt'] ?? '';
            /* If it looks like plain text (no HTML tags), wrap paragraphs */
            if (strip_tags($content) === $content) {
              foreach (explode("\n\n", trim($content)) as $para) {
                if (trim($para)) echo '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
              }
            } else {
              echo $content; /* Stored HTML from a rich text editor */
            }
            ?>
          </div>

          <!-- Photo Gallery -->
          <?php if (!empty($gallery)): ?>
          <h3 class="font-display text-xl font-bold text-gray-900 mt-10 mb-4">Photo Gallery</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($gallery as $img): ?>
            <a data-fancybox="event-gallery" href="<?= htmlspecialchars($img) ?>"
               class="block rounded-xl overflow-hidden aspect-video">
              <img src="<?= htmlspecialchars($img) ?>"
                   alt="<?= htmlspecialchars($event['event_title']) ?> photo"
                   class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                   loading="lazy">
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1" data-aos="fade-up" data-aos-delay="100">
          <div class="bg-slate-50 rounded-2xl border border-gray-100 p-6 sticky top-24">
            <h3 class="font-display text-lg font-bold text-gray-900 mb-4">Event Details</h3>

            <div class="space-y-3 mb-6">
              <div class="nob-row">
                <span class="nob-label">Date</span>
                <span class="nob-value"><?= (new DateTime($event['event_date']))->format('F d, Y') ?></span>
              </div>
              <?php if (!empty($event['event_end_date']) && $event['event_end_date'] !== $event['event_date']): ?>
              <div class="nob-row">
                <span class="nob-label">End Date</span>
                <span class="nob-value"><?= (new DateTime($event['event_end_date']))->format('F d, Y') ?></span>
              </div>
              <?php endif; ?>
              <?php if (!empty($event['event_time'])): ?>
              <div class="nob-row">
                <span class="nob-label">Time</span>
                <span class="nob-value"><?= htmlspecialchars($event['event_time']) ?></span>
              </div>
              <?php endif; ?>
              <?php if (!empty($event['event_location'])): ?>
              <div class="nob-row">
                <span class="nob-label">Location</span>
                <span class="nob-value"><?= htmlspecialchars($event['event_location']) ?></span>
              </div>
              <?php endif; ?>
              <?php if (!empty($event['event_audience'])): ?>
              <div class="nob-row">
                <span class="nob-label">Audience</span>
                <span class="nob-value"><?= htmlspecialchars($event['event_audience']) ?></span>
              </div>
              <?php endif; ?>
              <?php if (!$isPast && !empty($event['event_rsvp_deadline'])): ?>
              <div class="nob-row">
                <span class="nob-label">RSVP By</span>
                <span class="nob-value"><?= (new DateTime($event['event_rsvp_deadline']))->format('F d, Y') ?></span>
              </div>
              <?php endif; ?>
            </div>

            <!-- RSVP / concluded -->
            <?php if (!$isPast && !empty($event['event_rsvp_url'])): ?>
            <a href="<?= htmlspecialchars($event['event_rsvp_url']) ?>"
               class="flex items-center justify-center gap-2 py-3 px-6 rounded-xl bg-orange-600 hover:bg-orange-700 active:scale-95 text-white text-sm font-bold transition-all duration-150 w-full">
              RSVP / Register
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <?php else: ?>
            <div class="text-center text-sm text-gray-500 bg-white rounded-xl p-3 border border-gray-100">
              This event has concluded. Thanks to everyone who joined!
            </div>
            <?php endif; ?>

            <!-- Share -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">Share This Event</p>
              <div class="flex items-center gap-2">
                <a href="https://www.facebook.com/sharer/sharer.php?u=https://fishbrokers.net/events/<?= urlencode($slug) ?>"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center size-9 rounded-full bg-white border border-gray-200 text-gray-500 hover:text-orange-600 hover:border-orange-200 transition-colors">
                  <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54v-2.89h2.54V9.797c0-2.506 1.493-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.991 22 12z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url=https://fishbrokers.net/events/<?= urlencode($slug) ?>&text=<?= rawurlencode($event['event_title']) ?>"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center size-9 rounded-full bg-white border border-gray-200 text-gray-500 hover:text-orange-600 hover:border-orange-200 transition-colors">
                  <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
              </div>
            </div>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       MORE EVENTS CTA
  ══════════════════════════════════════════════ -->
  <section class="py-16 bg-slate-50 border-t border-gray-100">
    <div class="max-w-4xl mx-auto px-6 text-center" data-aos="fade-up">
      <span class="section-eyebrow justify-center">More From St. Joseph</span>
      <h2 class="font-display text-2xl font-bold text-gray-900 mb-4">Browse Other Events</h2>
      <p class="text-gray-500 mb-6">Upcoming gatherings, past highlights, and team activities.</p>
      <a href="/events/"
         class="inline-flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-8 rounded-xl transition-all duration-200 hover:-translate-y-0.5 shadow-sm hover:shadow-md">
        <span>View All Events &amp; Activities</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
      </a>
    </div>
  </section>

  <?php endif; ?>

  <?php $conn->close(); ?>
  <?php include('../components/footer.php'); ?>
  <?php include('../live_chat.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true, easing: 'ease-out-cubic', duration: 750 });</script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-B73TDMXKF5');
  </script>
</body>
</html>