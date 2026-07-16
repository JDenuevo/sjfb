<?php
session_start();
include 'conn.php';
$pageTitle = 'Services';
$metaDescription = 'St. Joseph Fish Brokerage Inc. operates across Navotas Fish Port Complex, Malabon Fish Port, and Davao Toril — connecting fresh Philippine seafood from fishermen to buyers nationwide.';

// ── Image path helpers ────────────────────────────────────────────────────────
// All filenames are stored bare in the DB (e.g. "abc123_main.jpg").
// Prepend the correct relative URL path before output.
define('MARKET_IMG_URL',  'uploads/markets/');
define('MEMBER_IMG_URL',  'uploads/members/');

// Get markets data
$markets_query = "SELECT * FROM markets WHERE is_active = 1 ORDER BY display_order";
$markets_result = mysqli_query($conn, $markets_query);
$markets = [];

while ($market = mysqli_fetch_assoc($markets_result)) {
    $market_id = $market['market_id'];

    // Decode highlights from JSON
    $market['highlights'] = json_decode($market['highlights'], true) ?: [];

    // Decode gallery images from JSON — stored as bare filenames
    $raw_gallery = json_decode($market['gallery_images'], true) ?: [];

    // FIXED: prepend upload path to every gallery filename
    $market['gallery_images'] = array_map(
        fn($f) => MARKET_IMG_URL . $f,
        array_filter($raw_gallery)
    );

    // FIXED: prepend upload path to main_image filename
    $market['main_image'] = !empty($market['main_image'])
        ? MARKET_IMG_URL . $market['main_image']
        : '';

    // Get team members for this market
    $team_query = "SELECT member_name, position, image_url FROM market_members
                   WHERE market_id = $market_id AND is_active = 1
                   ORDER BY display_order";
    $team_result = mysqli_query($conn, $team_query);
    $market['team'] = [];
    while ($member = mysqli_fetch_assoc($team_result)) {
        // FIXED: prepend upload path to member image_url filename
        if (!empty($member['image_url'])) {
            $member['image_url'] = MEMBER_IMG_URL . $member['image_url'];
        }
        $market['team'][] = $member;
    }

    // Get products for this market (with variants info)
    $products_query = "
        SELECT p.product_id, p.product_name, pv.variant_price, pv.unit_type, pv.stock_status
        FROM market_products mp
        INNER JOIN products p ON mp.product_id = p.product_id
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_deleted = 0
        WHERE mp.market_id = $market_id
          AND p.is_deleted = 0
          AND (pv.stock_status = 'In Stock' OR pv.stock_status IS NULL)
        GROUP BY p.product_id
        ORDER BY mp.display_order
        LIMIT 8";

    $products_result = mysqli_query($conn, $products_query);
    $market['products'] = [];
    while ($product = mysqli_fetch_assoc($products_result)) {
        $market['products'][] = $product;
    }

    $market['map_url'] = '';
    if (!empty($market['map_embed'])) {
        $decoded = html_entity_decode($market['map_embed'], ENT_QUOTES);
        if (preg_match('/src=["\']([^"\']+)["\']/', $decoded, $matches)) {
            $market['map_url'] = $matches[1];
        } else {
            $market['map_url'] = $decoded;
        }
    }

    $markets[] = $market;
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
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">

  <title>Services | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="Buy fresh seafood online in the Philippines. St. Joseph Fish Brokerage Inc. delivers premium bangus, tilapia, and more directly from Filipino fishermen to your door.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/shop">
  <meta property="og:title" content="Services & Markets | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Buy fresh seafood online — bangus, tilapia, tinapa & more. Sourced directly from Filipino fishermen. Wholesale & retail. Nationwide delivery.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Services & Markets | St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Buy fresh seafood online — sourced directly from Filipino fishermen. Nationwide delivery.">
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
    .hero-fish-pattern {
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 20 C25 15, 15 20, 12 30 C15 40, 25 45, 30 40 C35 45, 45 40, 48 30 C45 20, 35 15, 30 20Z' fill='none' stroke='rgba(255,255,255,0.04)' stroke-width='1'/%3E%3C/svg%3E");
      background-size: 60px;
    }
  </style>
</head>
<body class="bg-white">
<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- HERO -->
<section class="relative overflow-hidden pt-32 pb-24 bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400">
  <div class="hero-fish-pattern absolute inset-0"></div>
  <div class="absolute -top-[30%] -right-[10%] w-[600px] h-[600px] rounded-full bg-[radial-gradient(circle,rgba(255,255,255,.06)_0%,transparent_70%)] pointer-events-none"></div>

  <div class="relative z-10 max-w-5xl mx-auto px-6 text-center text-white">
    <div data-aos="fade-up" data-aos-duration="700">
      <span class="block mb-4 text-xs font-bold tracking-widest uppercase text-white">Our Footprint</span>
      <h1 class="font-display text-4xl md:text-6xl font-bold mb-6">
        Servicing Markets<br><em class="not-italic text-white">Across the Philippines</em>
      </h1>
      <p class="text-lg text-white/75 max-w-2xl mx-auto leading-relaxed mb-8">
        From the massive Navotas Fish Port in Metro Manila to the deep-sea tuna docks of Davao Toril, St. Joseph Fish Brokerage Inc. is where Filipino seafood moves — reliably, fairly, and freshly.
      </p>
      <div class="flex flex-wrap gap-4 justify-center text-sm text-white/60">
        <?php foreach ([['🗺️','4 Major Ports'],['🏪','26 Market Stalls'],['🤝','1000+ Fishing Partners']] as [$icon,$label]): ?>
        <span class="inline-flex items-center gap-2 py-1.5 px-3 rounded-full bg-white/10 border border-white/15 text-white/80 text-xs font-medium"><?= $icon ?> <?= $label ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" preserveAspectRatio="none" class="w-full block mt-12">
      <path d="M0,30 C480,60 960,0 1440,30 L1440,60 L0,60 Z" fill="white"/>
    </svg>
  </div>
</section>

<!-- MARKET TABS + PANELS (Preline hs-tab) -->
<section id="market-tabs" class="py-16 bg-white">
  <div class="max-w-6xl mx-auto px-6">

    <!-- Market Tabs -->
    <div class="border-b border-gray-200 mb-12 overflow-x-auto" data-aos="fade-up">
      <nav class="flex gap-1 min-w-max" role="tablist" aria-label="Markets" aria-orientation="horizontal">
        <?php foreach ($markets as $i => $m):
          // Pre-computed, literal Tailwind classes for this tab's own accent color.
          // Written out fully here (not built at runtime) so the Tailwind CDN
          // JIT scanner can see and compile them from the start.
          $activeClasses = "border-[{$m['accent_color']}] bg-[{$m['accent_color']}]/10 text-[{$m['accent_color']}] font-bold";
        ?>
        <button type="button"
                id="tab-<?= $m['market_key'] ?>"
                data-tab-target="panel-<?= $m['market_key'] ?>"
                data-active-classes="<?= htmlspecialchars($activeClasses) ?>"
                class="market-tab-btn <?= $i===0 ? $activeClasses : 'border-transparent text-gray-500' ?> py-3 px-5 inline-flex items-center whitespace-nowrap border-b-2 rounded-t-lg text-sm font-semibold hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-all duration-200"
                aria-controls="panel-<?= $m['market_key'] ?>"
                aria-selected="<?= $i===0 ? 'true' : 'false' ?>"
                role="tab">
          <?= htmlspecialchars($m['market_name']) ?>
        </button>
        <?php endforeach; ?>
      </nav>
    </div>

    <!-- Market Panels -->
    <?php foreach ($markets as $i => $m): ?>
    <div id="panel-<?= $m['market_key'] ?>"
         class="<?= $i===0 ? '' : 'hidden' ?>"
         role="tabpanel"
         aria-labelledby="tab-<?= $m['market_key'] ?>">

      <!-- Market Overview -->
      <div class="grid lg:grid-cols-2 gap-10 items-start mb-12">
        <div data-aos="fade-right">

          <!-- Main Image -->
          <div class="group relative rounded-3xl overflow-hidden aspect-video shadow-2xl shadow-black/15">
            <?php if (!empty($m['main_image'])): ?>
              <!-- FIXED: main_image already has path prepended in PHP above -->
              <img src="<?= htmlspecialchars($m['main_image']) ?>"
                   alt="<?= htmlspecialchars($m['market_name']) ?>"
                   loading="lazy"
                   id="main-img-<?= $m['market_key'] ?>"
                   class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="hidden w-full h-full items-center justify-center bg-gray-100">
                <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" stroke-width="1">
                  <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 9 4-4 4 4 4-6 6 9"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                </svg>
              </div>
            <?php else: ?>
              <!-- No image uploaded yet -->
              <div class="w-full h-full flex items-center justify-center bg-gray-100">
                <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" stroke-width="1">
                  <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 9 4-4 4 4 4-6 6 9"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                </svg>
              </div>
            <?php endif; ?>

            <div class="absolute top-4 left-4 flex items-center gap-2 bg-white rounded-xl px-3.5 py-2 text-xs font-bold shadow-lg">
              <svg class="size-4 text-orange-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
              <?= htmlspecialchars($m['location_short']) ?>
            </div>
            <div class="absolute bottom-4 right-4 rounded-xl bg-[#0c3d6e]/90 px-4 py-2.5 text-[13px] font-semibold text-white backdrop-blur-sm">
              <?= intval($m['stall_count']) ?> Stalls
            </div>
          </div>

          <!-- Gallery Thumbnails -->
          <?php if (!empty($m['gallery_images']) && count($m['gallery_images']) > 0): ?>
          <div class="flex gap-2 mt-3 flex-wrap">
            <?php foreach ($m['gallery_images'] as $idx => $img): ?>
            <!-- FIXED: $img already has path prepended in PHP above -->
            <img src="<?= htmlspecialchars($img) ?>"
                 alt="Gallery <?= $idx + 1 ?>"
                 class="w-16 h-16 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-orange-500 transition-all"
                 loading="lazy"
                 onerror="this.style.display='none'"
                 onclick="swapMainImage('<?= $m['market_key'] ?>', '<?= htmlspecialchars($img) ?>')">
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Map Toggle (Preline hs-collapse) -->
          <?php if (!empty($m['map_embed'])): ?>
          <button type="button"
                  id="map-toggle-<?= $m['market_key'] ?>"
                  class="hs-collapse-toggle mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white py-2.5 px-4 text-sm font-semibold text-gray-600 transition-all hover:border-blue-400 hover:text-blue-600"
                  data-hs-collapse="#map-<?= $m['market_key'] ?>">
            <svg class="size-4 hs-collapse-open:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <svg class="size-4 hidden hs-collapse-open:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span class="hs-collapse-open:hidden">View on Map</span>
            <span class="hidden hs-collapse-open:inline">Hide Map</span>
          </button>
          <div id="map-<?= $m['market_key'] ?>"
               class="hs-collapse hidden w-full overflow-hidden transition-[height] duration-300 mt-4 rounded-2xl"
               aria-labelledby="map-toggle-<?= $m['market_key'] ?>">
              <iframe src="<?= htmlspecialchars($m['map_url']) ?>" class="w-full" height="300" style="border:0;border-radius:.75rem" allowfullscreen loading="lazy"></iframe>
          </div>
          <?php endif; ?>
        </div>

        <div data-aos="fade-left">
          <span class="before:content-[''] before:block before:w-6 before:h-0.5 before:bg-orange-600 inline-flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.12em] text-orange-600">
            <?= htmlspecialchars($m['market_name']) ?>
          </span>
          <h2 class="font-display text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($m['market_name']) ?></h2>
          <p class="text-gray-600 leading-relaxed mb-6"><?= htmlspecialchars($m['description']) ?></p>

          <!-- Highlights -->
          <?php if (!empty($m['highlights'])): ?>
          <ul class="space-y-2">
            <?php foreach ($m['highlights'] as $h): ?>
            <li class="flex items-start gap-3 p-3 rounded-xl" style="background: <?= htmlspecialchars($m['accent_color']) ?>18">
              <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full"
                   style="background:<?= htmlspecialchars($m['accent_color']) ?>">
                <svg width="10" height="10" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
              </div>
              <span class="text-sm text-gray-700"><?= htmlspecialchars($h) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>

      <!-- Products Section -->
      <?php if (!empty($m['products'])): ?>
      <div class="mb-12">
        <h3 class="font-bold text-gray-900 text-lg mb-4">🐟 Products Always Available</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <?php foreach ($m['products'] as $product): ?>
          <?php $slug = urlencode(strtolower(str_replace(' ', '-', $product['product_name']))); ?>
          <a href="item/<?= $slug ?>"
             class="block cursor-pointer rounded-xl border border-gray-200 bg-white p-4 transition-all duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg hover:shadow-black/10">
            <div class="font-semibold text-gray-900 text-sm mb-1">
              <?= htmlspecialchars($product['product_name']) ?>
            </div>
            <?php if (!empty($product['variant_price'])): ?>
            <div class="text-xs font-semibold text-orange-500">
              ₱<?= number_format($product['variant_price'], 2) ?>/<?= htmlspecialchars($product['unit_type'] ?? 'kg') ?>
            </div>
            <?php endif; ?>
            <?php if (($product['stock_status'] ?? '') === 'Out of Stock'): ?>
            <div class="text-xs text-red-500 mt-1">Out of Stock</div>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Team Section -->
      <div>
        <h3 class="font-bold text-gray-900 text-lg mb-6">👥 Our Team at <?= htmlspecialchars($m['market_name']) ?></h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          <?php foreach ($m['team'] as $member): ?>
          <div class="rounded-2xl border border-gray-100 bg-white px-4 py-5 text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5">
            <?php if (!empty($member['image_url'])): ?>
              <!-- FIXED: $member['image_url'] already has path prepended in PHP above -->
              <img src="<?= htmlspecialchars($member['image_url']) ?>"
                   alt="<?= htmlspecialchars($member['member_name']) ?>"
                   class="mx-auto mb-3 block h-16 w-16 rounded-full border-[3px] border-gray-200 object-cover"
                   loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="mx-auto mb-3 hidden h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-xl font-bold text-blue-700">
                <?= strtoupper(substr($member['member_name'], 0, 1)) ?>
              </div>
            <?php else: ?>
              <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-xl font-bold text-blue-700">
                <?= strtoupper(substr($member['member_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($member['member_name']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($member['position']) ?></p>
          </div>
          <?php endforeach; ?>

          <!-- Join the team card -->
          <a href="contact.php?tab=panel-career"
             class="group flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 px-4 py-5 text-center transition-all hover:border-blue-400 hover:bg-blue-50">
            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 transition-colors group-hover:bg-blue-100">
              <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24" class="transition-colors group-hover:stroke-blue-500"><path d="M12 4v16m8-8H4"/></svg>
            </div>
            <p class="text-sm font-semibold text-gray-400 transition-colors group-hover:text-blue-500">Join the Team</p>
            <p class="text-xs text-gray-300 mt-0.5">View openings</p>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- WHY CHOOSE US -->
<section class="py-20 bg-slate-50">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-12" data-aos="fade-up">
      <span class="before:content-[''] before:block before:w-6 before:h-0.5 before:bg-orange-600 inline-flex items-center justify-center gap-2 mb-3 text-xs font-bold uppercase tracking-[0.12em] text-orange-600">Why Partner With Us</span>
      <h2 class="font-display text-3xl font-bold text-gray-900">What Sets Us Apart</h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ([
        ['⚖️','Fair Pricing Always','We do not manipulate market prices. Fishermen and buyers both receive transparent, competitive rates on every transaction.'],
        ['🕐','24-Hour Port Operations','Our Navotas and Malabon teams operate before dawn to receive fresh landings — so your stock arrives at peak freshness.'],
        ['📋','Full Documentation','Every lot comes with complete paperwork: origin, weight, price, and transport chain — traceable end to end.'],
        ['🇵🇭','Nationwide Reach','From Metro Manila to Mindanao, our network ensures consistent supply across the Philippine seafood ecosystem.'],
        ['🤝','Long-Term Relationships','Many of our partners have been with us for over a decade. Trust is the foundation of everything.'],
        ['📈','Volume Flexibility','Whether you\'re ordering 20 kg or 2 tons, we accommodate all order sizes with equal care.'],
      ] as $w): ?>
      <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" data-aos="fade-up">
        <div class="text-3xl mb-4"><?= $w[0] ?></div>
        <h3 class="font-bold text-gray-900 mb-2"><?= $w[1] ?></h3>
        <p class="text-sm text-gray-500 leading-relaxed"><?= $w[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400">
  <div class="max-w-2xl mx-auto px-6 text-center text-white" data-aos="fade-up">
    <h2 class="font-display text-3xl font-bold mb-4">Ready to source fresh Philippine seafood?</h2>
    <p class="text-white/70 mb-5 leading-relaxed">Contact our team at any of our markets or send us a message — we'll connect you with the right catch.</p>
    <a href="contact.php" class="inline-flex items-center gap-2 rounded-xl bg-white py-3 px-8 font-bold text-orange-600 shadow-lg transition-all hover:-translate-y-0.5 hover:bg-gray-100">
      <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      Get in Touch
    </a>
  </div>
</section>

<?php include('./components/footer.php'); ?>
<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>
<script>
AOS.init({ once: true });

// Swap main image when thumbnail is clicked
function swapMainImage(marketKey, imgSrc) {
  const main = document.getElementById('main-img-' + marketKey);
  if (main) {
    main.style.display = '';
    if (main.nextElementSibling) main.nextElementSibling.style.display = 'none';
    main.src = imgSrc;
  }
}

// ── Market tabs: plain JS, no Preline dependency ──
// Handles clicking, the accent-colored active highlight, and showing/hiding panels.
function activateMarketTab(targetPanelId) {
  document.querySelectorAll('.market-tab-btn').forEach(function (btn) {
    const activeClasses = btn.getAttribute('data-active-classes').split(' ');
    const isTarget = btn.getAttribute('data-tab-target') === targetPanelId;

    btn.classList.remove(...activeClasses, 'border-transparent', 'text-gray-500');
    btn.classList.add(...(isTarget ? activeClasses : ['border-transparent', 'text-gray-500']));
    btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
  });

  document.querySelectorAll('[role="tabpanel"]').forEach(function (panel) {
    panel.classList.toggle('hidden', panel.id !== targetPanelId);
  });
}

document.querySelectorAll('.market-tab-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    activateMarketTab(btn.getAttribute('data-tab-target'));
  });
});

// ── Deep-link into a specific market tab, e.g. services.php?market=navotas ──
document.addEventListener('DOMContentLoaded', function () {
  const params    = new URLSearchParams(window.location.search);
  const marketKey = params.get('market');
  if (!marketKey) return;

  const tabBtn = document.getElementById('tab-' + marketKey);
  if (!tabBtn) return;

  tabBtn.click();

  document.getElementById('market-tabs')?.scrollIntoView({
    behavior: 'smooth',
    block: 'start'
  });

  // Clean "?market=..." out of the address bar so a page refresh
  // doesn't keep re-triggering the same tab switch.
  window.history.replaceState({}, document.title, window.location.pathname);
});
</script>
</body>
</html>