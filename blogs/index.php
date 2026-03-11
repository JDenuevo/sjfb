<?php
session_start();
include '../conn.php';

$pageTitle = 'Blogs';
$showCategories = false;
$showMobileCategories = false;

// Pagination for public blogs
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$blogsPerPage = 6;
$offset = ($page - 1) * $blogsPerPage;

// Get total published blogs
$totalQuery = "SELECT COUNT(*) as total FROM blogs WHERE blog_status = 'published'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalBlogs = $totalRow['total'];
$totalPages = ceil($totalBlogs / $blogsPerPage);

// Fetch published blogs with pagination
$blogsQuery = "SELECT * FROM blogs 
          WHERE blog_status = 'published' 
          ORDER BY blog_published_date DESC 
          LIMIT $offset, $blogsPerPage";
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
  
  <title>St. Joseph Fish Brokerage Inc. - Latest News & Updates</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. is the largest fish brokerage in the Philippines, providing fresh seafood trading, wholesale supply, and nationwide sourcing services.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/blogs/">
  <meta property="og:title" content="Blog | St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Latest news and updates from the largest fish brokerage in the Philippines.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  
  <!-- Favicon and CSS links -->
  <link rel="shortcut icon" href="../assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="../assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="../assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="../assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="../style.css" rel="stylesheet">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

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
    .blog-card {
      transition: transform 0.3s ease;
    }
    .blog-card:hover {
      transform: translateY(-5px);
    }
  </style>
</head>

<body id="content">

  <?php include('../components/preloaders.php'); ?>
  <?php include('../components/navigation.php'); ?>

  <!-- Blog Grid -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php include('../components/nav_crumb.php'); ?>
    <div class="text-center mb-12" data-aos="fade-up">
      <span class="section-eyebrow justify-center">Latest Insights</span>
      <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900">Blogs / News &amp; Updates</h2>
      <p class="text-gray-500 mt-3 max-w-xl mx-auto">Stay informed with the latest news from St. Joseph and the Philippine fishing industry.</p>
    </div>
    
    <?php if (!empty($blogs)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 my-10">
        <?php foreach ($blogs as $blog): ?>
        <!-- Blog Card -->
        <a href="/sjfbi-js/blogs/<?= $blog['blog_slug'] ?>" class="blog-card group block bg-white rounded-2xl shadow-sm hover:shadow-xl overflow-hidden border border-gray-100 transition-all duration-300">
          <!-- Featured Image -->
          <div class="relative h-56 overflow-hidden">
            <?php if (!empty($blog['blog_featured_image'])): ?>
              <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>" 
                    alt="<?= htmlspecialchars($blog['blog_title']) ?>"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
              <div class="w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                <span class="text-white text-4xl font-bold">SJ</span>
              </div>
            <?php endif; ?>
            
            <!-- Category/Status Badge -->
            <div class="absolute top-4 left-4">
              <span class="px-3 py-1 bg-orange-600 text-white text-xs font-semibold rounded-full">
                <?= ucfirst($blog['blog_status']) ?>
              </span>
            </div>
          </div>
          
          <!-- Content -->
          <div class="p-6">
            <!-- Author and Date -->
            <div class="flex items-center mb-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                  <span class="text-orange-600 font-semibold text-sm">
                    <?= strtoupper(substr($blog['blog_author'] ?? 'A', 0, 1)) ?>
                  </span>
                </div>
              </div>
              <div class="ms-3">
                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($blog['blog_author'] ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500"><?= date('F d, Y', strtotime($blog['blog_published_date'])) ?></p>
              </div>
            </div>
            
            <!-- Title -->
            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors line-clamp-2">
              <?= htmlspecialchars($blog['blog_title']) ?>
            </h3>
            
            <!-- Excerpt -->
            <p class="text-gray-600 mb-4 line-clamp-3">
              <?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?>
            </p>
            
            <!-- Read More -->
            <div class="flex items-center text-orange-600 font-medium">
              Read More 
              <svg class="w-4 h-4 ml-1 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </div>
          </div>
        </a>
        <!-- End Blog Card -->
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="flex justify-center mt-12">
        <nav class="flex items-center gap-2" aria-label="Pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
              Previous
            </a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm font-medium <?= $i == $page ? 'text-white bg-orange-600 border-orange-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg">
              <?= $i ?>
            </a>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
              Next
            </a>
          <?php endif; ?>
        </nav>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- No blogs found -->
      <div class="text-center py-20">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No blog posts yet</h3>
        <p class="mt-2 text-gray-500">Check back soon for updates and news from St. Joseph Fish Brokerage Inc.</p>
      </div>
    <?php endif; ?>
  </section>

  <?php include('../components/footer.php'); ?>

  <!-- JS PLUGINS -->
  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>