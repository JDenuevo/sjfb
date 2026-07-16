<?php
session_start();
include 'conn.php';

$pageTitle = 'Events';

/* ──────────────────────────────────────────────
   events — from DB
   Pulls published events with a future date
────────────────────────────────────────────── */
$upcomingQuery = "SELECT * FROM events 
                  WHERE event_status = 'published'
                  ORDER BY event_date DESC 
                  LIMIT 6";
$upcomingResult = mysqli_query($conn, $upcomingQuery);
$upcomingEvents = mysqli_fetch_all($upcomingResult, MYSQLI_ASSOC);

/* ──────────────────────────────────────────────
   PAST EVENTS — from DB
   Pulls published events with a past date
────────────────────────────────────────────── */
$pastQuery  = "SELECT * FROM events 
               WHERE event_status = 'published' AND event_date < CURDATE() 
               ORDER BY event_date DESC 
               LIMIT 3";
$pastResult = mysqli_query($conn, $pastQuery);
$pastEvents = mysqli_fetch_all($pastResult, MYSQLI_ASSOC);
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

  <title>Events | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="Discover events, past highlights, team activities, and community initiatives at St. Joseph Fish Brokerage Inc. — the largest fish brokerage in the Philippines.">
  <link rel="canonical" href="https://fishbrokers.net/events/">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/events/">
  <meta property="og:title" content="Events | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="events, past highlights, team activities, and community initiatives at St. Joseph Fish Brokerage Inc.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Event Schema — one block per upcoming event (SEO rich results) -->
  <?php foreach ($upcomingEvents as $e): ?>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "<?= htmlspecialchars($e['event_title']) ?>",
    "startDate": "<?= $e['event_date'] ?>",
    "endDate": "<?= $e['event_end_date'] ?? $e['event_date'] ?>",
    "eventStatus": "https://schema.org/EventScheduled",
    "eventAttendanceMode": "<?= str_contains((string)($e['event_location'] ?? ''), 'Online') ? 'https://schema.org/MixedEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode' ?>",
    "location": {
      "@type": "Place",
      "name": "<?= htmlspecialchars($e['event_location'] ?? 'Philippines') ?>",
      "address": "<?= htmlspecialchars($e['event_address'] ?? 'Philippines') ?>"
    },
    "image": ["<?= htmlspecialchars($e['event_image'] ?? '') ?>"],
    "description": "<?= htmlspecialchars($e['event_excerpt'] ?? '') ?>",
    "organizer": {
      "@type": "Organization",
      "name": "St. Joseph Fish Brokerage Inc.",
      "url": "https://fishbrokers.net"
    }
  }
  </script>
  <?php endforeach; ?>

  <!-- BreadcrumbList Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home",               "item": "https://fishbrokers.net/" },
      { "@type": "ListItem", "position": 2, "name": "Events & Activities", "item": "https://fishbrokers.net/events/" }
    ]
  }
  </script>

  <!-- Favicons -->
  <link rel="shortcut icon" href="assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <!--
    Trimmed down to only what Tailwind's utility classes genuinely can't express here:
    font-family aliases, and the marquee gallery, which needs real @keyframes (the Tailwind
    Play CDN has no config file to extend, so arbitrary utilities can reference a keyframe
    name but can't define one). The marquee rules are also relied on by
    components/company_events.php, so they're kept as-is rather than guessed at.
    Everything else (eyebrows, hero height, the nature-of-business grid, event-card hover,
    the unused pulse-dot) has been moved to Tailwind utilities directly in the markup, or
    removed if it wasn't actually used anywhere in this template.
  -->
  <style>
    body         { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    /* ── Marquee (gallery) — used by components/company_events.php ── */
    .marquee-wrapper { display: flex; overflow: hidden; width: 100%; position: relative; margin-bottom: 1.5rem; }
    .marquee-content { display: flex; gap: 12px; flex-shrink: 0; min-width: 100%; }
    .marquee-ltr .marquee-content { animation: marquee-ltr 90s linear infinite; }
    .marquee-rtl .marquee-content { animation: marquee-rtl 90s linear infinite; }
    .marquee-wrapper:hover .marquee-content { animation-play-state: paused; }
    .marquee-item { flex-shrink: 0; height: 210px; overflow: hidden; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.15); transition: transform .3s ease, box-shadow .3s ease; }
    .marquee-item:hover { transform: scale(1.03); box-shadow: 0 8px 24px rgba(0,0,0,.25); }
    .marquee-item img { height: 210px; width: auto; object-fit: cover; display: block; }
    @keyframes marquee-ltr { 0% { transform: translateX(0); }    100% { transform: translateX(-50%); } }
    @keyframes marquee-rtl { 0% { transform: translateX(-50%); } 100% { transform: translateX(0); } }
    .direction-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; letter-spacing: .04em; padding: 2px 8px; border-radius: 20px; opacity: .7; }
    .direction-badge.ltr { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .direction-badge.rtl { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .title-year { font-family: 'Playfair Display', serif; }
    @media (max-width: 768px) {
      .marquee-item, .marquee-item img { height: 150px; }
      .title-year { font-size: 14px; }
    }
  </style>
</head>

<body id="content" class="bg-white">
  <?php include('components/preloaders.php'); ?>
  <?php include('components/navigation.php'); ?>
  <?php include('components/nav_crumb.php'); ?>

  <!-- ══════════════════════════════════════════════
       PAGE HERO
  ══════════════════════════════════════════════ -->
  <section id="page-hero" class="relative flex h-[clamp(220px,30vw,360px)] w-full items-center justify-center overflow-hidden bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400 px-6 text-center">
    <div class="relative z-10 max-w-2xl" data-aos="fade-up">
      <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-bold tracking-widest uppercase bg-orange-600 text-white mb-3">
        Life at St. Joseph
      </span>
      <h1 class="font-display text-[clamp(1.75rem,5vw,3.25rem)] font-bold text-white leading-tight mb-3 drop-shadow-lg">
        Events &amp; Activities
      </h1>
      <p class="text-sm sm:text-base text-white/80 leading-relaxed max-w-[42ch] mx-auto">
        Upcoming gatherings, past celebrations, and the moments that bring our St. Joseph family — and our industry community — together.
      </p>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       NATURE OF BUSINESS
  ══════════════════════════════════════════════ -->
  <section class="py-14 bg-white border-b border-gray-100">
    <div class="max-w-4xl mx-auto px-6" data-aos="fade-up">
      <div class="text-center mb-8">
        <span class="before:content-[''] before:block before:w-8 before:h-0.5 before:bg-orange-400 inline-flex items-center justify-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.15em] text-orange-400">About These Events</span>
        <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900">Nature of Business</h2>
      </div>
      <div class="space-y-4 max-w-2xl mx-auto">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-[220px_1fr] md:items-baseline md:gap-6">
          <span class="text-[13px] font-bold uppercase tracking-wide text-orange-700">Industry</span>
          <span class="text-gray-600 leading-relaxed">Fish Brokerage &amp; Wholesale Seafood Trading</span>
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-[220px_1fr] md:items-baseline md:gap-6">
          <span class="text-[13px] font-bold uppercase tracking-wide text-orange-700">Coverage Area</span>
          <span class="text-gray-600 leading-relaxed">Navotas, Malabon, Lucena, Davao, and expanding nationwide</span>
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-[220px_1fr] md:items-baseline md:gap-6">
          <span class="text-[13px] font-bold uppercase tracking-wide text-orange-700">Audience</span>
          <span class="text-gray-600 leading-relaxed">Employees, partner fishermen and cooperatives, retail &amp; wholesale buyers, and the public</span>
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-[220px_1fr] md:items-baseline md:gap-6">
          <span class="text-[13px] font-bold uppercase tracking-wide text-orange-700">Frequency</span>
          <span class="text-gray-600 leading-relaxed">Quarterly internal activities; annual participation in major seafood industry expos</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       1. events
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-slate-50" id="upcoming-events">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <div class="text-center mb-12" data-aos="fade-up">
        <span class="before:content-[''] before:block before:w-8 before:h-0.5 before:bg-orange-400 inline-flex items-center justify-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.15em] text-orange-400">Mark Your Calendar</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Events</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
          Join us at our next gatherings — open to employees, partners, and the public depending on the event.
        </p>
      </div>

      <?php if (!empty($upcomingEvents)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($upcomingEvents as $idx => $e):
          $dateObj = new DateTime($e['event_date']);
        ?>
        <a href="/events/<?= htmlspecialchars($e['event_slug']) ?>"
           class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-[5px] hover:shadow-xl"
           data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 100 ?>">

          <!-- Image -->
          <div class="relative h-48 overflow-hidden flex-none">
            <?php if (!empty($e['event_image'])): ?>
              <img src="<?= htmlspecialchars($e['event_image']) ?>"
                   alt="<?= htmlspecialchars($e['event_title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                   loading="lazy">
            <?php else: ?>
              <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-700 flex items-center justify-center">
                <span class="text-white text-4xl font-bold font-display">SJ</span>
              </div>
            <?php endif; ?>

            <!-- Date badge -->
            <div class="absolute top-4 left-4 bg-white rounded-xl shadow-md px-3 py-1.5 text-center leading-tight">
              <p class="text-orange-600 font-bold text-lg leading-none"><?= $dateObj->format('d') ?></p>
              <p class="text-gray-500 text-[10px] uppercase font-semibold tracking-wide"><?= $dateObj->format('M Y') ?></p>
            </div>

          </div>

          <!-- Body -->
          <div class="flex flex-col flex-1 p-6">
            <?php if (!empty($e['event_audience'])): ?>
            <span class="inline-flex items-center gap-1.5 mb-3 text-xs font-bold uppercase tracking-wide text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full w-fit">
              <?= htmlspecialchars($e['event_audience']) ?>
            </span>
            <?php endif; ?>

            <h2 class="font-display text-lg font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($e['event_title']) ?>
            </h2>
            <p class="text-gray-500 text-sm mb-4 line-clamp-3 flex-1">
              <?= htmlspecialchars($e['event_excerpt'] ?? '') ?>
            </p>

            <div class="text-xs text-gray-400 space-y-1 mb-4">
              <?php if (!empty($e['event_time'])): ?>
              <p class="flex items-center gap-1.5">
                <svg class="size-3.5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= htmlspecialchars($e['event_time']) ?>
              </p>
              <?php endif; ?>
              <?php if (!empty($e['event_location'])): ?>
              <p class="flex items-center gap-1.5">
                <svg class="size-3.5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <?= htmlspecialchars($e['event_location']) ?>
              </p>
              <?php endif; ?>
            </div>

            <div class="flex items-center text-orange-600 font-medium text-sm mt-auto">
              View Details
              <svg class="w-4 h-4 ml-1 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <div class="text-center py-16" data-aos="fade-up">
        <span class="inline-flex items-center justify-center size-16 rounded-full bg-orange-50 mb-4">
          <svg class="size-8 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </span>
        <h3 class="text-lg font-semibold text-gray-800">No events at the moment</h3>
        <p class="mt-2 text-sm text-gray-500">Check back soon or subscribe below for updates.</p>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       3 & 4. COMPANY ACTIVITIES + EXTERNAL PARTICIPATION
  ══════════════════════════════════════════════ -->
  <section class="py-20 bg-slate-50" id="participation">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

        <!-- Internal -->
        <div data-aos="fade-up">
          <span class="before:content-[''] before:block before:w-8 before:h-0.5 before:bg-orange-400 inline-flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.15em] text-orange-400">Internal Engagement</span>
          <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-4">Company Activities</h2>
          <p class="text-gray-500 mb-6 leading-relaxed">
            We invest in our people through regular team building, training, wellness programs, and
            community-driven CSR initiatives that reflect our values as a company.
          </p>
          <ul class="space-y-3">
            <li class="flex items-start gap-3 bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
              <span class="inline-flex items-center justify-center size-9 rounded-lg bg-teal-50 text-teal-600 shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
              </span>
              <div>
                <p class="font-bold text-gray-900 text-sm">Team Building Events</p>
                <p class="text-gray-500 text-sm">Cross-department activities that build collaboration and trust.</p>
              </div>
            </li>
            <li class="flex items-start gap-3 bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
              <span class="inline-flex items-center justify-center size-9 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
              </span>
              <div>
                <p class="font-bold text-gray-900 text-sm">Training &amp; Workshops</p>
                <p class="text-gray-500 text-sm">Skills development for handling, quality control, and customer service.</p>
              </div>
            </li>

            <li class="flex items-start gap-3 bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
              <span class="inline-flex items-center justify-center size-9 rounded-lg bg-orange-50 text-orange-600 shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 10-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              </span>
              <div>
                <p class="font-bold text-gray-900 text-sm">Wellness Programs</p>
                <p class="text-gray-500 text-sm">Sports fests and mental health days for a balanced work life.</p>
              </div>
            </li>
          </ul>
        </div>

        <!-- External -->
        <div data-aos="fade-up" data-aos-delay="100">
          <span class="before:content-[''] before:block before:w-8 before:h-0.5 before:bg-orange-400 inline-flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.15em] text-orange-400">Industry Presence</span>
          <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-4">External Participation</h2>
          <p class="text-gray-500 mb-6 leading-relaxed">
            We actively engage with the broader seafood industry through conferences, expos, and
            speaking engagements — strengthening our network and sharing what we've learned.
          </p>
          <?php
          $extQuery  = "SELECT * FROM events 
                        WHERE event_status = 'published' 
                        AND event_category IN ('Conference','Trade Show','Expo','External')
                        ORDER BY event_date DESC LIMIT 3";
          $extResult = mysqli_query($conn, $extQuery);
          $extEvents = mysqli_fetch_all($extResult, MYSQLI_ASSOC);
          ?>
          <?php if (!empty($extEvents)): ?>
          <div class="space-y-4">
            <?php foreach ($extEvents as $p):
              $dateObj = new DateTime($p['event_date']);
            ?>
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
              <div class="flex items-center justify-between gap-3 mb-2">
                <span class="text-xs font-bold uppercase tracking-wide text-orange-600"><?= htmlspecialchars($p['event_category']) ?></span>
                <span class="text-xs text-gray-400"><?= $dateObj->format('F Y') ?></span>
              </div>
              <h3 class="font-display font-bold text-gray-900 mb-1"><?= htmlspecialchars($p['event_title']) ?></h3>
              <p class="text-gray-500 text-sm"><?= htmlspecialchars($p['event_excerpt'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-gray-400 text-sm italic">Industry participation events will appear here.</p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════════
       5. ANNOUNCEMENTS
  ══════════════════════════════════════════════ -->
  <?php
  $annQuery  = "SELECT * FROM events 
                WHERE event_status = 'published' AND event_date >= CURDATE()
                ORDER BY event_rsvp_deadline ASC LIMIT 1";
  $annResult = mysqli_query($conn, $annQuery);
  $ann       = mysqli_fetch_assoc($annResult);
  ?>
  <?php if ($ann && !empty($ann['event_rsvp_deadline'])): ?>
  <section class="py-12 bg-white" id="announcements">
    <div class="max-w-4xl mx-auto px-6" data-aos="fade-up">
      <div class="flex items-start gap-4 bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <span class="inline-flex items-center justify-center size-10 rounded-full bg-amber-100 text-amber-600 shrink-0">
          <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <div>
          <p class="font-bold text-gray-900 mb-1">Event Announcement</p>
          <p class="text-gray-600 text-sm leading-relaxed">
            Registration for <strong><?= htmlspecialchars($ann['event_title']) ?></strong> closes
            <strong><?= (new DateTime($ann['event_rsvp_deadline']))->format('F d, Y') ?></strong>.
            Schedules are subject to change — please check this page or subscribe below for the latest updates.
          </p>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════
       6. GALLERY (MARQUEE)
  ══════════════════════════════════════════════ -->
  <section class="relative overflow-hidden bg-slate-50 py-20" id="gallery">
    <div class="pointer-events-none absolute -top-16 -right-20 w-96 h-96 rounded-full bg-orange-500/5 blur-3xl" aria-hidden="true"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12" data-aos="fade-up">
        <span class="before:content-[''] before:block before:w-8 before:h-0.5 before:bg-orange-400 inline-flex items-center justify-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.15em] text-orange-400">Through the Years</span>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Photo Gallery</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
          Hover over a row to pause and explore — click any photo for a closer look.
        </p>
      </div>
    </div>
    <?php include('components/company_events.php'); ?>
  </section>

  <!-- ══════════════════════════════════════════════
       7. CALL TO ACTION / SUBSCRIBE
  ══════════════════════════════════════════════ -->
  <section class="relative py-20 bg-amber-50 overflow-hidden border-y border-amber-100">
    <div class="absolute -top-16 left-4 text-[14rem] font-display text-orange-600/5 pointer-events-none select-none" aria-hidden="true">"</div>
    <div class="relative z-10 max-w-3xl mx-auto px-6 text-center" data-aos="fade-up">
      <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-800 mb-4">
        Never Miss an Event
      </h2>
      <p class="text-gray-600 mb-6 leading-relaxed">
        Subscribe to get notified about events, CSR activities, and industry
        engagements from St. Joseph Fish Brokerage Inc.
      </p>
      <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto" onsubmit="return false;">
        <input type="email" required placeholder="Enter your email address"
               class="flex-1 px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 py-3 px-6 rounded-xl bg-orange-600 hover:bg-orange-700 active:scale-95 text-white text-sm font-bold transition-all duration-150 shadow-sm hover:shadow-md whitespace-nowrap">
          Subscribe
        </button>
      </form>
      <p class="text-xs text-gray-400 mt-4">
        Or <a href="/contact" class="text-orange-600 font-semibold hover:underline">contact us directly</a>
        to RSVP for our next event.
      </p>
    </div>
  </section>

  <?php $conn->close(); ?>
  <?php include('components/footer.php'); ?>
  <?php include('live_chat.php'); ?>

  <!-- JS PLUGINS -->
  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init({ once: true, easing: 'ease-out-cubic', duration: 750 });</script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script>
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
  </script>
</body>
</html>