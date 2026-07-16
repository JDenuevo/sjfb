<?php
session_start();
include '../conn.php';

$pageTitle = 'Events';

// Pagination
$page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$eventsPerPage = 6;
$offset        = ($page - 1) * $eventsPerPage;

// Total published events
$totalQuery  = "SELECT COUNT(*) as total FROM events WHERE event_status = 'published'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalEvents = $totalRow['total'];
$totalPages  = ceil($totalEvents / $eventsPerPage);

// Filter by category (optional)
$categoryFilter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$whereClause    = "WHERE event_status = 'published'";
if ($categoryFilter) {
  $whereClause .= " AND event_category = '$categoryFilter'";
}

// Fetch events with pagination
$eventsQuery  = "SELECT * FROM events 
                 $whereClause 
                 ORDER BY event_date DESC 
                 LIMIT $offset, $eventsPerPage";
$eventsResult = mysqli_query($conn, $eventsQuery);
$events       = mysqli_fetch_all($eventsResult, MYSQLI_ASSOC);

// Fetch categories for filter tabs
$categoriesResult = mysqli_query($conn, "SELECT DISTINCT event_category FROM events WHERE event_status = 'published' ORDER BY event_category ASC");
$categories       = mysqli_fetch_all($categoriesResult, MYSQLI_ASSOC);
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

  <title>Events & Activities | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="Browse upcoming and past events, team activities, CSR programs, and industry participation at St. Joseph Fish Brokerage Inc. — the largest fish brokerage in the Philippines.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/events/">
  <meta property="og:title" content="Events & Activities | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Upcoming and past events, team activities, CSR programs, and industry participation at St. Joseph Fish Brokerage Inc.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="canonical" href="https://fishbrokers.net/events/">

  <!-- BreadcrumbList Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home",              "item": "https://fishbrokers.net/" },
      { "@type": "ListItem", "position": 2, "name": "Events & Activities","item": "https://fishbrokers.net/events/" }
    ]
  }
  </script>

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
    body        { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

    /* Eyebrow — matches blogs/index.php */
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

    /* Event card — same hover as blog-card */
    .event-card {
      transition: transform 0.3s ease;
    }
    .event-card:hover {
      transform: translateY(-5px);
    }

    /* Category pill filter */
    .cat-pill {
      display: inline-flex;
      align-items: center;
      gap: .375rem;
      padding: .375rem 1rem;
      border-radius: 9999px;
      font-size: .75rem;
      font-weight: 600;
      border: 1px solid #e5e7eb;
      background: #fff;
      color: #6b7280;
      transition: all .2s;
      white-space: nowrap;
    }
    .cat-pill:hover,
    .cat-pill.active {
      background: #ea580c;
      border-color: #ea580c;
      color: #fff;
    }

    /* Upcoming badge pulse */
    @keyframes pulse-dot {
      0%, 100% { opacity: 1; }
      50%       { opacity: .4; }
    }
    .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
  </style>
</head>

<body id="content">
  <?php include('../components/preloaders.php'); ?>
  <?php include('../components/navigation.php'); ?>

  <!-- ══════════════════════════════════════════════
       HEADER + CATEGORY FILTER
  ══════════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-4">
    <?php include('../components/nav_crumb.php'); ?>
    <div class="text-center mb-8" data-aos="fade-up">
      <span class="section-eyebrow justify-center">Life at St. Joseph</span>
      <h1 class="font-display text-3xl md:text-4xl font-bold text-gray-900">
        Events &amp; Activities
      </h1>
      <p class="text-gray-500 mt-3 max-w-xl mx-auto">
        Upcoming gatherings, team activities, CSR initiatives, and highlights
        from the St. Joseph Fish Brokerage family through the years.
      </p>
    </div>

    <!-- Category filter pills -->
    <?php if (!empty($categories)): ?>
    <div class="flex flex-wrap justify-center gap-2 mb-8" data-aos="fade-up" data-aos-delay="100">
      <a href="/events/"
         class="cat-pill <?= !$categoryFilter ? 'active' : '' ?>">
        All Events
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="/events/?category=<?= urlencode($cat['event_category']) ?>"
         class="cat-pill <?= $categoryFilter === $cat['event_category'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['event_category']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </section>

  <!-- ══════════════════════════════════════════════
       EVENT CARDS GRID
  ══════════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <?php if (!empty($events)): ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 my-10">
        <?php foreach ($events as $event):
          $dateObj   = new DateTime($event['event_date']);
          $isUpcoming = strtotime($event['event_date']) > time();
        ?>

        <!-- Event Card -->
        <a href="/events/<?= htmlspecialchars($event['event_slug']) ?>"
           class="event-card group block bg-white rounded-2xl shadow-sm hover:shadow-xl overflow-hidden border border-gray-100 transition-all duration-300">

          <!-- Featured Image -->
          <div class="relative h-56 overflow-hidden">
            <?php if (!empty($event['event_image'])): ?>
              <img src="<?= htmlspecialchars($event['event_image']) ?>"
                   alt="<?= htmlspecialchars($event['event_title']) ?>"
                   class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                   loading="lazy">
            <?php else: ?>
              <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-700 flex items-center justify-center">
                <span class="text-white text-4xl font-bold font-display">SJ</span>
              </div>
            <?php endif; ?>

            <!-- Date badge — top left -->
            <div class="absolute top-4 left-4 bg-white rounded-xl shadow-md px-3 py-1.5 text-center leading-tight">
              <p class="text-orange-600 font-bold text-lg leading-none"><?= $dateObj->format('d') ?></p>
              <p class="text-gray-500 text-[10px] uppercase font-semibold tracking-wide"><?= $dateObj->format('M Y') ?></p>
            </div>

            <!-- Upcoming / Past badge — top right -->
            <div class="absolute top-4 right-4">
              <?php if ($isUpcoming): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 text-white text-xs font-semibold rounded-full">
                <span class="pulse-dot size-1.5 rounded-full bg-white inline-block"></span>
                Upcoming
              </span>
              <?php else: ?>
              <span class="px-3 py-1 bg-gray-800/70 text-white text-xs font-semibold rounded-full backdrop-blur">
                Past Event
              </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-6">

            <!-- Category + Audience -->
            <div class="flex items-center gap-2 mb-3 flex-wrap">
              <?php if (!empty($event['event_category'])): ?>
              <span class="px-2.5 py-1 bg-orange-50 text-orange-600 text-xs font-semibold rounded-full border border-orange-100">
                <?= htmlspecialchars($event['event_category']) ?>
              </span>
              <?php endif; ?>
              <?php if (!empty($event['event_audience'])): ?>
              <span class="px-2.5 py-1 bg-teal-50 text-teal-700 text-xs font-semibold rounded-full border border-teal-100">
                <?= htmlspecialchars($event['event_audience']) ?>
              </span>
              <?php endif; ?>
            </div>

            <!-- Title -->
            <h2 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($event['event_title']) ?>
            </h2>

            <!-- Excerpt -->
            <p class="text-gray-600 mb-4 line-clamp-3 text-sm">
              <?= htmlspecialchars($event['event_excerpt'] ?? '') ?>
            </p>

            <!-- Meta: time + location -->
            <div class="text-xs text-gray-400 space-y-1 mb-4">
              <?php if (!empty($event['event_time'])): ?>
              <p class="flex items-center gap-1.5">
                <svg class="size-3.5 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?= htmlspecialchars($event['event_time']) ?>
              </p>
              <?php endif; ?>
              <?php if (!empty($event['event_location'])): ?>
              <p class="flex items-center gap-1.5">
                <svg class="size-3.5 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?= htmlspecialchars($event['event_location']) ?>
              </p>
              <?php endif; ?>
            </div>

            <!-- Read More -->
            <div class="flex items-center text-orange-600 font-medium text-sm">
              <?= $isUpcoming ? 'View Details & RSVP' : 'Read Recap' ?>
              <svg class="w-4 h-4 ml-1 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>

          </div>
        </a>
        <!-- End Event Card -->

        <?php endforeach; ?>
      </div>

      <!-- Pagination — identical pattern to blogs -->
      <?php if ($totalPages > 1): ?>
      <div class="flex justify-center mt-12">
        <nav class="flex items-center gap-2" aria-label="Pagination">

          <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
             class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Previous
          </a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
             class="px-4 py-2 text-sm font-medium <?= $i == $page ? 'text-white bg-orange-600 border-orange-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg border">
            <?= $i ?>
          </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>"
             class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Next
          </a>
          <?php endif; ?>

        </nav>
      </div>
      <?php endif; ?>

    <?php else: ?>

      <!-- Empty state — mirrors blogs empty state -->
      <div class="text-center py-20" data-aos="fade-up">
        <div class="flex justify-center mb-4">
          <span class="inline-flex items-center justify-center size-20 rounded-full bg-orange-50">
            <svg class="size-10 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </span>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">No events found</h3>
        <p class="mt-2 text-sm text-gray-500">
          <?= $categoryFilter ? 'No events in this category yet.' : 'Check back soon for upcoming events from St. Joseph Fish Brokerage Inc.' ?>
        </p>
        <?php if ($categoryFilter): ?>
        <a href="/events/" class="mt-4 inline-flex items-center gap-1.5 text-sm text-orange-600 font-semibold hover:underline">
          ← View all events
        </a>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </section>

  <?php include('../components/footer.php'); ?>

  <!-- JS PLUGINS -->
  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>AOS.init();</script>
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>