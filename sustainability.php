<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sustainability | St. Joseph Fish Brokerage Inc.</title>

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
<body>
<?php include('./components/preloader.php'); ?>
<!-- Hero Section -->
<section id="sustainability-section">
    
    <?php include('./components/navigation.php'); ?>
    
    <div class="relative" data-aos="zoom-in" data-aos-duration="1000" data-aos-anchor-placement="center-bottom">
      <img class="h-80 w-full object-cover rounded-md" src="./assets/images/sustainability.jpg" loading="lazy" alt="St. Joseph Fish Brokerage Inc. Navotas Market">
      <div class="absolute inset-0 bg-black opacity-60"></div>
      <div class="absolute inset-0 flex items-center justify-center text-white"> 
        <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
          <div class="grid md:grid-cols-3 gap-x-3 md:items-center">
            
            <div class="md:col-span-1" data-aos="zoom-out-right" data-aos-duration="2000"> 
              <h2 class="text-3xl font-bold text-center">Our Commitment to Sustainability</h2>
            </div>  
         
            <div class="md:col-span-2 md:mt-0" data-aos="zoom-out-left" data-aos-duration="2000">
            
              <p class="text-justify font-semibold">
               At St. Joseph Fish Brokerage Inc., we source our seafood from responsible fish producers who prioritize the health of marine ecosystems. Our goal is to offer quality fresh seafood from our fish producers while actively supporting and aimed at conserving marine life.
              </p>
          
            </div>
            
          </div>
        </div>
      </div>
      
    </div>
   
    <div>
        <?php include('./components/our_fresh_produce.php'); ?>
    </div>

    <div class="overflow-hidden shadow-lg pb-5" id="bottom-page">
        <?php include('./components/sourcing_producers.php'); ?>
    </div>

</section>

<?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <?php include('live_chat.php'); ?>


</body>
</html>
