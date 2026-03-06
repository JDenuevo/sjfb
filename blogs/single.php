<?php
session_start();
include '../conn.php';

// Get the slug from URL
$slug = isset($_GET['slug']) ? mysqli_real_escape_string($conn, $_GET['slug']) : '';

// Set breadcrumb variables
$pageTitle = 'Blogs';
$currentPage = '';
$showCategories = false;
$showMobileCategories = false;

// IMPORTANT: If no slug is provided, this is NOT a single post request
// Redirect to blogs listing page
if (empty($slug)) {
    header('Location: /sjfbi-js/blogs/');
    exit;
}

// Fetch blog post - only proceed if we have a slug
$query = "SELECT * FROM blogs WHERE blog_slug = '$slug' AND blog_status = 'published' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    // Blog not found - show 404
    header('HTTP/1.0 404 Not Found');
    $notFound = true;
} else {
    $blog = mysqli_fetch_assoc($result);
    $currentPage = $blog['blog_title'];

    // Set meta tags for SEO
    $metaTitle = !empty($blog['blog_meta_title']) ? $blog['blog_meta_title'] : $blog['blog_title'] . ' | St. Joseph Fish Brokerage Inc.';
    $metaDescription = !empty($blog['blog_meta_description']) ? $blog['blog_meta_description'] : ($blog['blog_excerpt'] ?? '');
    $metaKeywords = $blog['blog_meta_keywords'] ?? '';
    $ogImage = !empty($blog['blog_featured_image']) ? $blog['blog_featured_image'] : 'https://fishbrokers.net/assets/icons/logo.svg';
    
    // Get related posts
    $relatedQuery = "SELECT * FROM blogs 
                     WHERE blog_status = 'published' 
                     AND blog_id != {$blog['blog_id']} 
                     ORDER BY blog_published_date DESC 
                     LIMIT 3";
    $relatedResult = mysqli_query($conn, $relatedQuery);
    $relatedPosts = mysqli_fetch_all($relatedResult, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth"> 
<head>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="robots" content="max-snippet:-1, max-image-preview:large, max-video-preview:-1">
  
  <?php if (isset($notFound)): ?>
    <title>Blog Post Not Found | St. Joseph Fish Brokerage Inc.</title>
    <meta name="description" content="The requested blog post could not be found.">
  <?php else: ?>
    <title><?= htmlspecialchars($metaTitle) ?> | St. Joseph Fish Brokerage Inc.</title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <?php endif; ?>
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://fishbrokers.net/blogs/<?= htmlspecialchars($blog['blog_slug']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($blog['blog_title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="article:published_time" content="<?= $blog['blog_published_date'] ?>">
    <meta property="article:author" content="<?= htmlspecialchars($blog['blog_author'] ?? '') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($blog['blog_title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($blog['blog_excerpt'] ?? '') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://fishbrokers.net/blogs/<?= htmlspecialchars($blog['blog_slug']) ?>">
  <?php endif; ?>
  
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

  <link href="../style.css" rel="stylesheet">
  <link href="../output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
</head>

<style>
  body { font-family: 'Lexend', sans-serif; }
    .font-display { font-family: 'Playfair Display', serif; }

  .blog-content h1, .blog-content h2, .blog-content h3, .blog-content h4 {
    font-weight: 700;
    color: #111827;
    margin-top: 1.75em;
    margin-bottom: 0.75em;
    line-height: 1.3;
  }
  .blog-content h2 { font-size: 1.5rem; border-bottom: 2px solid #fed7aa; padding-bottom: 0.3em; }
  .blog-content h3 { font-size: 1.2rem; }
  .blog-content p { margin-bottom: 1.25em; line-height: 1.8; color: #374151; }
  .blog-content strong, .blog-content b { font-weight: 700; color: #111827; }
  .blog-content em, .blog-content i { font-style: italic; }
  .blog-content ul, .blog-content ol { padding-left: 1.5em; margin-bottom: 1.25em; }
  .blog-content li { margin-bottom: 0.4em; line-height: 1.7; }
  .blog-content a { color: #ea580c; text-decoration: underline; }
  .blog-content a:hover { color: #c2410c; }
  .blog-content blockquote { 
    border-left: 4px solid #ea580c; 
    padding-left: 1.25em; 
    margin: 1.5em 0; 
    font-style: italic; 
    color: #6b7280; 
  }
  .blog-content img { max-width: 100%; border-radius: 0.75rem; margin: 1.5em auto; display: block; }
  .blog-content table { width: 100%; border-collapse: collapse; margin-bottom: 1.5em; }
  .blog-content td, .blog-content th { border: 1px solid #e5e7eb; padding: 0.6em 1em; }
  .blog-content th { background: #f9fafb; font-weight: 600; }
  .blog-content hr { border: none; border-top: 2px solid #f3f4f6; margin: 2em 0; }

  /* Container: Dark Green Base */
  .header-blog-container {
    position: relative;
    width: 100%;
    min-height: 250px;
    background-color: #145207;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    align-items: center;
    box-sizing: border-box;
  }

  /* Container: Dark Green Base */
  .footer-blog-container {
    position: relative;
    width: 100%;
    min-height: 100px;
    background-color: #145207;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    align-items: center;
    box-sizing: border-box;
  }

  /* Orange Slash Layer */
  .orange-slash {
    position: absolute;
    inset: 0;
    background-color: #FF3F00;
    /* Recreates your specific SVG "Slashed" look */
    clip-path: polygon(0% 0%, 100% 0%, 68% 68%, 0% 100%);
    z-index: 1;
    /* High-fidelity shadow for depth */
    filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.4));
  }

  /* Content Layer */
  .title-blog-content {
    position: relative;
    z-index: 2;
    padding: 2.5rem;
    width: 100%;
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  /* Mobile Responsive Adjustments */
  @media (max-width: 768px) {
    .header-blog-container {
      min-height: 450px;
    }

    .footer-blog-container {
      min-height: 100px;
    }
    
    .orange-slash {
      /* Covers more area on mobile to protect text readability */
      clip-path: polygon(0% 0%, 100% 0%, 100% 90%, 0% 100%);
    }
    
    .title-blog-content {
      padding: 1.5rem;
      gap: 1rem;
    }
  }
</style>

<body id="content">
  <?php include('../components/preloaders.php'); ?>
  <?php include('../components/navigation.php'); ?>

   <?php if (isset($notFound)): ?>
    <!-- 404 Content -->
    <div class="max-w-[85rem] mx-auto px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Blog Post Not Found</h1>
        <p class="text-xl text-gray-600 mb-8">Sorry, the blog post you're looking for doesn't exist or has been moved.</p>
        <a href="/sjfbi-js/blogs/" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
          Back to Blogs
        </a>
      </div>
    </div>
  <?php else: ?>
    <!-- Blog Article -->
    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">
      <?php include('../components/nav_crumb.php'); ?>
      <div class="grid lg:grid-cols-3 gap-y-8 lg:gap-y-0 lg:gap-x-6">
        <!-- Content -->
        <div class="lg:col-span-2">
          <div class="py-8 lg:pe-8">
            <div class="space-y-5 lg:space-y-8">
              <a class="inline-flex items-center gap-x-1.5 text-sm text-muted-foreground-2 decoration-2 hover:underline focus:outline-hidden focus:underline" href="/sjfbi-js/blogs/">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Back to Blog
              </a>

              <div class="header-blog-container">
                <div class="orange-slash"></div>
                
                <div class="title-blog-content">
                    <div class="flex items-center gap-x-4">
                        <span class="inline-flex items-center py-1.5 px-4 rounded-full text-xs font-bold bg-white text-black uppercase tracking-wide">
                            <?= ucfirst(htmlspecialchars($blog['blog_status'])) ?>
                        </span>
                        <p class="text-xs sm:text-sm text-white/90 font-medium">
                            <?= date('F d, Y', strtotime($blog['blog_published_date'])) ?>
                        </p>
                    </div>

                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
                        <?= htmlspecialchars($blog['blog_title']) ?>
                    </h2>

                    <?php if (!empty($blog['blog_excerpt'])): ?>
                        <p class="text-base sm:text-lg text-white italic border-l-[5px] border-black ps-5 leading-relaxed">
                            <?= htmlspecialchars($blog['blog_excerpt']) ?>
                        </p>
                    <?php endif; ?>
                </div>
              </div>

              <?php if (!empty($blog['blog_featured_image'])): ?>
              <div class="text-center">
                <img src="<?= htmlspecialchars($blog['blog_featured_image']) ?>" 
                     alt="<?= htmlspecialchars($blog['blog_title']) ?>" 
                     class="w-full h-auto rounded-xl shadow-lg">
              </div>
              <?php endif; ?>

              <div class="text-base text-gray-700 blog-content max-w-none">
                <?= $blog['blog_content'] ?>
              </div>

              <div class="w-full h-full">
                <svg preserveAspectRatio="xMidYMid slice" class="w-full h-full object-cover" fill="none" width="1509" height="213" viewBox="0 0 1509 213" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="-41" y="39" width="1550" height="185" fill="#145207"/>
                  <path d="M761.338 1.91965C764.714 0.650291 768.291 0 771.897 0H1549C1565.57 0 1579 13.4315 1579 30V183C1579 199.569 1565.57 213 1549 213H365.016C331.632 213 323.209 166.67 354.456 154.92L761.338 1.91965Z" fill="#FF3F00"/>
                </svg>
              </div>
              
            </div>
          </div>
        </div>
        <!-- End Content -->

        <!-- Sidebar -->
        <div class="lg:col-span-1 lg:w-full lg:h-full lg:bg-linear-to-r lg:from-background lg:via-transparent lg:to-transparent">
          <div class="sticky top-0 start-0 py-8 lg:ps-8">
            <!-- Avatar Media -->
            <div class="group flex items-center gap-x-3 border-b border-line-2 pb-8 mb-8">
              <div class="block shrink-0 focus:outline-hidden">
                <img class="size-10 rounded-full" src="https://images.unsplash.com/photo-1669837401587-f9a4cfe3126e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=320&h=320&q=80" alt="Avatar">
              </div>

              <div class="group grow block focus:outline-hidden">
                <h5 class="group-hover:text-muted-foreground-2 group-focus:text-muted-foreground-2 text-sm font-semibold text-foreground">
                  <?= htmlspecialchars($blog['blog_author'] ?? 'Admin') ?>
                </h5>
                <p class="text-sm text-muted-foreground-1">
                  Author
                </p>
              </div>
            </div>
            <!-- End Avatar Media -->

            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
            <div class="space-y-6">
              <h4 class="text-lg font-semibold text-foreground">Related Posts</h4>
              
              <?php foreach ($relatedPosts as $related): ?>
              <a class="group flex items-center gap-x-6 focus:outline-hidden" href="/sjfbi-js/blogs/<?= $related['blog_slug'] ?>">
                <div class="grow">
                  <span class="text-sm font-bold text-foreground group-hover:text-primary-hover group-focus:text-primary-focus">
                    <?= htmlspecialchars($related['blog_title']) ?>
                  </span>
                  <p class="text-xs text-muted-foreground-1 mt-1">
                    <?= date('M d, Y', strtotime($related['blog_published_date'])) ?>
                  </p>
                </div>

                <?php if (!empty($related['blog_featured_image'])): ?>
                <div class="shrink-0 relative rounded-lg overflow-hidden size-20">
                  <img class="size-full absolute top-0 start-0 object-cover rounded-lg" 
                       src="<?= htmlspecialchars($related['blog_featured_image']) ?>" 
                       alt="<?= htmlspecialchars($related['blog_title']) ?>">
                </div>
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <!-- End Related Posts -->
          </div>
        </div>
        <!-- End Sidebar -->
      </div>
    </div>
    <!-- End Blog Article -->
  <?php endif; ?>

  <?php include('../components/footer.php'); ?>

  <!-- JS Scripts -->
  <script>
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(function() {
        alert('Link copied to clipboard!');
      }, function(err) {
        console.error('Could not copy text: ', err);
      });
    }
  </script>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>