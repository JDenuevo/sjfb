<?php
session_start();
include 'conn.php';

$pageTitle = 'Sustainability';

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
  
  <title><?= $pageTitle ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="St. Joseph Fish Brokerage Inc. - Providing professional fish brokerage services with excellence and integrity.">

  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

</head>

<body>
<?php include('./components/preloader.php'); ?>

<style>    
    .parallax-bg {
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }
    
</style>
</head>
<body class="font-sans antialiased">
      <?php include('./components/navigation.php'); ?>

    <?php include('./components/nav_crumb.php'); ?>

    <!-- 1️⃣ HERO SECTION -->
    <section class="relative h-[70vh] min-h-[500px] flex items-center justify-center overflow-hidden">
        <!-- Background image with parallax effect -->
        <div class="absolute inset-0 z-0">
            <div class="parallax-bg w-full h-full" style="background-image: url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2068&q=80');"></div>
            <div class="absolute inset-0 bg-black opacity-40"></div>
        </div>
        
        <!-- Hero content -->
        <div class="relative z-10 text-center text-white px-4 max-w-4xl mx-auto fade-in">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">Sustainability at St. Joseph Fish Brokerage, Inc.</h1>
            <p class="text-xl md:text-2xl font-light">Responsible operations, ethical partnerships, and long-term growth for the Philippine seafood industry.</p>
        </div>
        
        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10">
            <div class="animate-bounce">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
        </div>
    </section>

    <!-- 2️⃣ INTRO STATEMENT -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-3xl">
            <div class="flex flex-col items-center text-center fade-in">
                <div class="mb-6">
                    <svg class="w-16 h-16 text-[var(--sustainability-green)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <p class="text-xl md:text-2xl text-gray-700 leading-relaxed">
                    At St. Joseph Fish Brokerage, sustainability means conducting business with integrity, respecting marine resources, and fostering <span class="font-semibold text-[var(--primary-blue)]">responsible</span> trade practices. We are committed to supporting the livelihoods of fishing communities while ensuring the long-term viability of our industry through ethical <span class="font-semibold text-[var(--primary-blue)]">partnerships</span> and transparent operations that benefit both <span class="font-semibold text-[var(--primary-blue)]">people</span> and the environment.
                </p>
            </div>
        </div>
    </section>

    <!-- 3️⃣ SUSTAINABILITY PILLARS -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-[var(--primary-blue)] mb-12">Our Sustainability Pillars</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Pillar 1 -->
                <a href="#responsible-brokerage" class="block">
                    <div class="card-hover bg-white rounded-xl p-6 h-full border border-gray-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 rounded-full bg-blue-50 icon-hover">
                                <svg class="w-10 h-10 text-[var(--primary-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--primary-blue)] mb-3">Responsible Fish Brokerage</h3>
                            <p class="text-gray-600">Ethical trading, regulatory compliance, and transparent operations.</p>
                        </div>
                    </div>
                </a>
                
                <!-- Pillar 2 -->
                <a href="#people-workplace" class="block">
                    <div class="card-hover bg-white rounded-xl p-6 h-full border border-gray-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 rounded-full bg-green-50 icon-hover">
                                <svg class="w-10 h-10 text-[var(--sustainability-green)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-6.201a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--primary-blue)] mb-3">People & Workplace</h3>
                            <p class="text-gray-600">Safe, fair, and inclusive environment for our employees.</p>
                        </div>
                    </div>
                </a>
                
                <!-- Pillar 3 -->
                <a href="#community-livelihoods" class="block">
                    <div class="card-hover bg-white rounded-xl p-6 h-full border border-gray-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 rounded-full bg-teal-50 icon-hover">
                                <svg class="w-10 h-10 text-[var(--accent-teal)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--primary-blue)] mb-3">Community & Livelihoods</h3>
                            <p class="text-gray-600">Supporting local suppliers and fishing communities.</p>
                        </div>
                    </div>
                </a>
                
                <!-- Pillar 4 -->
                <a href="#environmental-responsibility" class="block">
                    <div class="card-hover bg-white rounded-xl p-6 h-full border border-gray-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 rounded-full bg-blue-50 icon-hover">
                                <svg class="w-10 h-10 text-[var(--primary-blue)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--primary-blue)] mb-3">Environmental Responsibility</h3>
                            <p class="text-gray-600">Mindful operations that respect marine resources.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- 4️⃣ DEEP-DIVE SECTIONS (SCROLL STORY) -->
    <div class="bg-white">
        <!-- Section 1: Responsible Brokerage -->
        <section id="responsible-brokerage" class="py-16 border-b border-gray-100">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row items-center gap-12">
                    <div class="lg:w-1/2 z-pattern-img">
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Fish port operations" class="rounded-xl shadow-lg w-full h-auto">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-3xl font-bold text-[var(--primary-blue)] mb-6">Responsible Fish Brokerage</h2>
                        <ul class="space-y-4">
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Ethical trading practices that ensure fair prices for both suppliers and buyers</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Full compliance with national fisheries regulations and international standards</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Transparent operations with clear documentation and traceability systems</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: People & Workplace -->
        <section id="people-workplace" class="py-16 border-b border-gray-100">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row-reverse items-center gap-12">
                    <div class="lg:w-1/2 z-pattern-img right">
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Team collaboration" class="rounded-xl shadow-lg w-full h-auto">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-3xl font-bold text-[var(--primary-blue)] mb-6">People & Workplace</h2>
                        <ul class="space-y-4">
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Safe working conditions with comprehensive training and protective equipment</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Fair wages and benefits that support employee wellbeing and development</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Inclusive culture that values diversity and promotes equal opportunities</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Community & Livelihoods -->
        <section id="community-livelihoods" class="py-16 border-b border-gray-100">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row items-center gap-12">
                    <div class="lg:w-1/2 z-pattern-img">
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Fishing community" class="rounded-xl shadow-lg w-full h-auto">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-3xl font-bold text-[var(--primary-blue)] mb-6">Community & Livelihoods</h2>
                        <ul class="space-y-4">
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Direct partnerships with local fishing communities and small-scale suppliers</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Support for community initiatives that enhance local livelihoods</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Capacity building programs to improve fishing practices and business skills</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Environmental Responsibility -->
        <section id="environmental-responsibility" class="py-16">
            <div class="container mx-auto px-4">
                <div class="flex flex-col lg:flex-row-reverse items-center gap-12">
                    <div class="lg:w-1/2 z-pattern-img right">
                        <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Marine conservation" class="rounded-xl shadow-lg w-full h-auto">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-3xl font-bold text-[var(--primary-blue)] mb-6">Environmental Responsibility</h2>
                        <ul class="space-y-4">
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Promotion of sustainable fishing practices and responsible resource management</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Minimizing waste and implementing eco-friendly operations at our facilities</span>
                            </li>
                            <li class="flex items-start stagger-item">
                                <svg class="w-6 h-6 text-[var(--sustainability-green)] mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Support for marine conservation initiatives and biodiversity protection</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- 5️⃣ IMPACT SNAPSHOT -->
    <section class="py-16 bg-gradient-to-r from-[var(--primary-blue)] to-[var(--sustainability-green)] text-dark">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">Our Impact</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold mb-2 " data-target="32">0</div>
                    <div class="text-lg font-medium">Brokerage Stalls Nationwide</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold mb-2 " data-target="3">0</div>
                    <div class="text-lg font-medium">Major Fish Ports</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold mb-2 " data-target="100">0</div>
                    <div class="text-lg font-medium">Trusted Partnerships</div>
                </div>
                
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-bold mb-2 " data-target="500">0</div>
                    <div class="text-lg font-medium">People-Centered Operations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6️⃣ HANDSHAKE / PARTNERSHIP SECTION -->
    <section class="relative py-20">
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1551836026-d5c2c5af78e4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Partnership handshake" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-[var(--primary-blue)] opacity-80"></div>
        </div>
        
        <div class="relative z-10 container mx-auto px-4 text-center text-white">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Building a Sustainable Future Together</h2>
            <p class="text-xl max-w-3xl mx-auto mb-10">
                Our commitment to sustainability extends beyond our operations to every partnership we form. We believe that through collaboration, responsibility, and shared growth, we can create lasting positive impact across the Philippine seafood industry.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="/services" class="inline-block bg-white text-[var(--primary-blue)] font-semibold py-3 px-8 rounded-lg hover:bg-gray-100 transition duration-300 transform hover:-translate-y-1">
                    Our Services
                </a>
                <a href="/contact" class="inline-block border-2 border-white text-white font-semibold py-3 px-8 rounded-lg hover:bg-white hover:text-[var(--primary-blue)] transition duration-300 transform hover:-translate-y-1">
                    Partner With Us
                </a>
            </div>
        </div>
    </section>

    <!-- 7️⃣ FOOTER TRANSITION -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="text-center">
                <p class="text-2xl md:text-3xl text-gray-700 italic leading-relaxed">
                    "Sustainability is not a destination—it is a commitment we live every day through responsible business, trusted relationships, and continuous improvement."
                </p>
                <div class="mt-8 pt-8 border-t border-gray-300">
                    <p class="text-lg text-gray-600">St. Joseph Fish Brokerage, Inc.</p>
                </div>
            </div>
        </div>
    </section>

   
<?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@next/dist/aos.js"></script> 
  <!-- Preline UI JS -->
    <script src="https://cdn.jsdelivr.net/npm/@preline/ui@latest/dist/js/preline.min.js"></script>
    
    <!-- Custom JavaScript for animations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Parallax effect for hero background
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const parallaxElement = document.querySelector('.parallax-bg');
                
                if (parallaxElement) {
                    const rate = scrolled * 0.5;
                    parallaxElement.style.transform = `translate3d(0px, ${rate}px, 0px)`;
                }
            });
        });
    </script>

  <script>
    AOS.init();
  </script>
  <script src="node_modules/preline/dist/preline.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  
  <?php include('live_chat.php'); ?>


</body>
</html>
