<?php
session_start();
include 'conn.php';

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<!-- End Google Tag Manager -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<body>
<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
  <section id="home-section">
    
    <?php include('./components/navigation.php'); ?>

    <div class="h-screen">
      <img src="./assets/images/banyera.png" loading="lazy" alt="St. Joseph Fish Brokerage Navotas Fish Port Complex" class="absolute top-0 left-0 w-full h-full object-cover z-0" data-aos="fade-bottom" data-aos-duration="1000" data-aos-anchor-placement="top-bottom">
      <div class="absolute inset-0 bg-black opacity-70"></div>

      <div class="relative z-10 text-center flex flex-col justify-center items-center h-full">
        <h1 id="phrase" class="text-5xl font-bold text-white mb-44 opacity-100">Your Fresh Seafood Connection</h1>
      </div>
    </div>
  
    <div class="overflow-hidden shadow-lg pb-5" id="bottom-page">
        <?php include('./components/products.php'); ?>
    </div>

  </section>
  
  <?php include('./components/footer.php'); ?>

  <script>
      // Phrases Array
      const phrases = [
          'Your Fresh Seafood Connection',
          'Connecting Fishers to Markets',
          'Your reliable seafood partner.',
          'Quality seafood, exceptional value.',
          'Your seafood partner, from start to finish.'
      ];
      
      let index = 0;

      // Function to Cycle through Phrases
      function changePhrase() {
          const phraseElement = $('#phrase');
          setInterval(() => {
              phraseElement.addClass('fade-out'); // Fade out
              setTimeout(() => {
                  index = (index + 1) % phrases.length;
                  phraseElement.text(phrases[index]); // Update phrase
                  phraseElement.removeClass('fade-out').addClass('fade-in'); // Fade in
              }, 1000); // 1-second duration
          }, 8000); // Change every 8 seconds
      }

      // Call Phrase Transition on Load
      $(document).ready(() => {
          changePhrase();
      });
  </script>

  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>
