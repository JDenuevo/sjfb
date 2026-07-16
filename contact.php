<?php
session_start();
include 'conn.php';
$pageTitle = 'Contact';
$metaDescription = 'Get in touch with St. Joseph Fish Brokerage Inc. — send us a general inquiry, request a quote for fresh Philippine seafood, or apply to join our team at Navotas, Malabon, or Davao Toril.';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth">
<head>
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $pageTitle ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="description" content="<?= $metaDescription ?>">
  <meta property="og:type" content="website"><meta property="og:url" content="https://fishbrokers.net/contact">
  <meta property="og:title" content="Contact Us | St. Joseph Fish Brokerage Inc."><meta property="og:description" content="<?= $metaDescription ?>">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg">
  <meta name="twitter:card" content="summary_large_image">
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

  <!-- ✅ UNIFIED CART CORE — must load before cart.php / products.php -->
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>

  <style>
    body { font-family:'Lexend',sans-serif; background:#f9fafb; }
    .font-display { font-family:'Playfair Display',serif; }
  </style>
</head>
<body>
<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- HERO -->
<section class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400 px-6 pt-20 pb-20 md:pt-28 md:pb-20">
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_20%,rgba(255,255,255,.05)_0%,transparent_40%),radial-gradient(circle_at_90%_80%,rgba(255,255,255,.04)_0%,transparent_40%)]"></div>

  <div class="relative z-10 max-w-3xl mx-auto text-center text-white">
    <div data-aos="fade-up" data-aos-duration="700">
      <span class="block mb-4 text-xs font-bold tracking-widest uppercase text-white">Reach Out</span>
      <h1 class="font-display text-4xl md:text-6xl font-bold mb-5">
        Let's Talk <em class="not-italic text-white">Fish</em>
      </h1>
      <p class="text-lg text-white/75 leading-relaxed max-w-xl mx-auto">
        Whether you're a buyer looking for fresh supply, a fisherman wanting a trusted broker, or someone ready to build a career with us — we're here and ready.
      </p>
    </div>
  </div>
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" preserveAspectRatio="none" class="block w-full">
      <path d="M0,20 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#f9fafb"/>
    </svg>
  </div>
</section>

<!-- MAIN CONTENT -->
<section class="py-16">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-[1fr_340px] gap-10 items-start">

      <!-- LEFT: FORM AREA -->
      <div>
        <!-- Tab Toggle — Preline hs-tab pill nav -->
        <div class="mb-8" data-aos="fade-up">
          <p class="text-sm font-semibold text-gray-500 mb-3">What brings you here today?</p>
          <nav class="inline-flex gap-1 rounded-2xl bg-gray-100 p-1" role="tablist" aria-label="Contact reason">
            <button type="button" id="tab-inquiry"
                    class="hs-tab-active:bg-white hs-tab-active:text-blue-800 hs-tab-active:shadow-sm active inline-flex items-center gap-2 rounded-xl py-3 px-5 text-sm font-semibold text-gray-500 transition-all hover:text-gray-700 focus:outline-none"
                    data-hs-tab="#panel-inquiry" aria-controls="panel-inquiry" role="tab">
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              General Inquiry
            </button>
            <button type="button" id="tab-career"
                    class="hs-tab-active:bg-white hs-tab-active:text-blue-800 hs-tab-active:shadow-sm inline-flex items-center gap-2 rounded-xl py-3 px-5 text-sm font-semibold text-gray-500 transition-all hover:text-gray-700 focus:outline-none"
                    data-hs-tab="#panel-career" aria-controls="panel-career" role="tab">
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              Apply / Careers
            </button>
          </nav>
        </div>

        <!-- PANEL 1: GENERAL INQUIRY -->
        <div id="panel-inquiry" role="tabpanel" aria-labelledby="tab-inquiry" data-aos="fade-up" data-aos-delay="100">
          <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
            <h2 class="font-display text-xl font-bold text-gray-900 mb-1">Send Us a Message</h2>
            <p class="text-sm text-gray-500 mb-6">For orders, partnership inquiries, supplier registration, or general questions.</p>

            <!-- Preline alert components -->
            <div id="inq-loading" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-medium">
              <svg class="animate-spin size-5 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="20"/></svg>
              Sending your message…
            </div>
            <div id="inq-success" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
              <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <p>Message sent! We'll get back to you within 1–2 business days.</p>
            </div>
            <div id="inq-error" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium">
              <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span id="inq-error-text">Something went wrong. Please try again.</span>
            </div>

            <form id="inquiry-form" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
              <input type="hidden" name="form_type" value="inquiry">
              <div class="hidden"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                  <input type="text" name="firstName" placeholder="Juan" required
                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white font-[Lexend]">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                  <input type="text" name="lastName" placeholder="dela Cruz" required
                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white font-[Lexend]">
                </div>
              </div>
              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                  <input type="email" name="email" placeholder="you@example.com" required autocomplete="email"
                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white font-[Lexend]">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                  <input type="number" name="contact" placeholder="XXXX XXX XXXX" required
                    class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white font-[Lexend]">
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">I am a… <span class="text-red-500">*</span></label>
                <select name="sender_type" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white font-[Lexend]">
                  <option value="">— Select one —</option>
                  <option value="buyer">Buyer / Restaurant / Business</option>
                  <option value="fisherman">Fisherman / Supplier</option>
                  <option value="processor">Fish Processor / Exporter</option>
                  <option value="partner">Potential Business Partner</option>
                  <option value="media">Media / Researcher</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" placeholder="e.g. Bulk bangus order inquiry" required
                  class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white font-[Lexend]">
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Message <span class="text-red-500">*</span></label>
                <textarea name="message" rows="4" placeholder="Tell us what you need — species, quantity, delivery location, or any questions." required
                  class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 bg-white resize-y font-[Lexend]"></textarea>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Preferred Market Location</label>
                <select name="market" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white font-[Lexend]">
                  <option value="">— Any / Not sure —</option>
                  <option value="navotas">Navotas Fish Port Complex</option>
                  <option value="malabon">Malabon Consignacion</option>
                  <option value="davao">Davao Toril Fish Port</option>
                </select>
              </div>
              <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Attachments <span class="text-gray-400 font-normal">(optional)</span></label>
                <div id="inq-dropzone"
                     class="cursor-pointer rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition-all hover:border-amber-500 hover:bg-amber-50"
                     onclick="document.getElementById('inq-files').click()">
                  <div class="mx-auto mb-2 flex size-10 items-center justify-center rounded-full bg-orange-200">
                    <svg class="size-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-gray-700">Click to upload or drag & drop</p>
                  <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF — max 2MB each</p>
                </div>
                <input type="file" id="inq-files" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden">
                <div id="inq-file-list" class="mt-2 space-y-1"></div>
              </div>
              <!-- Preline button style -->
              <button type="submit" id="inq-submit"
                      class="w-full py-3 px-4 inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Message
              </button>
              <p class="text-center text-xs text-gray-400 mt-3">We respond within 1–2 business days. No spam, ever.</p>
            </form>
          </div>
        </div>

        <!-- PANEL 2: CAREER -->
        <div id="panel-career" class="hidden" role="tabpanel" aria-labelledby="tab-career" data-aos="fade-up">

          <!-- ── HR Branding Header ── -->
          <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-4 mb-4">
              <span class="h-px w-16 bg-orange-500"></span>
              <span class="size-1.5 rounded-full bg-orange-500"></span>
              <span class="h-px w-16 bg-orange-500"></span>
            </div>
            <h2 class="font-display text-3xl md:text-4xl font-extrabold text-emerald-950 tracking-tight mb-2">HR BRANDING</h2>
            <p class="text-sm md:text-base font-medium text-gray-500">
              <span class="text-emerald-900 font-semibold">Built on Trust.</span>
              <span class="text-orange-600 font-semibold">Strengthened by People.</span>
              <span class="text-emerald-900 font-semibold">Defined by Legacy.</span>
            </p>
          </div>

          <!-- ── Two Column Cards ── -->
          <div class="grid md:grid-cols-2 gap-6 mb-8">

            <!-- WHO WE ARE -->
            <div class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-white">
              <div class="flex items-center gap-3 bg-emerald-950 px-5 py-4">
                <div class="size-9 rounded-full bg-emerald-900 border-2 border-emerald-700 flex items-center justify-center shrink-0">
                  <svg class="size-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                </div>
                <h3 class="text-white font-bold text-sm tracking-wide">WHO WE ARE</h3>
              </div>
              <div class="divide-y divide-gray-100">
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-emerald-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A <strong class="text-emerald-950">stable</strong> and <strong class="text-emerald-950">resilient</strong> organization</p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-emerald-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-4.13a4 4 0 100-8 4 4 0 000 8zm7 4c1.66 0 3-1.34 3-3a3 3 0 00-3-3m-14 0a3 3 0 00-3 3c0 1.66 1.34 3 3 3"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">Employees who are <strong class="text-emerald-950">pioneers</strong> — many have stayed with us for <strong class="text-emerald-950">over 30 years</strong></p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-emerald-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21C12 21 4 16.5 4 10.5C4 7.42 6.42 5 9.5 5C11.04 5 12.5 5.8 13.4 7.05C14.3 5.8 15.75 5 17.29 5C20.37 5 22.79 7.42 22.79 10.5C22.79 16.5 14.79 21 14.79 21H12Z"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5"><strong class="text-emerald-950">No layoffs</strong> even during the pandemic, because we value people beyond circumstances</p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-emerald-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1118.879 6.196 9 9 0 015.12 17.804zM15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A workplace where careers last, with many employees proudly retiring at ages <strong class="text-emerald-950">60–65</strong></p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-emerald-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A company that believes in recognizing potential and <strong class="text-emerald-950">promoting from within</strong></p>
                </div>
              </div>
            </div>

            <!-- WHAT WE WANT TO BE -->
            <div class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-white">
              <div class="flex items-center gap-3 bg-orange-600 px-5 py-4">
                <div class="size-9 rounded-full bg-orange-500 border-2 border-orange-300 flex items-center justify-center shrink-0">
                  <svg class="size-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </div>
                <h3 class="text-white font-bold text-sm tracking-wide">WHAT WE WANT TO BE</h3>
              </div>
              <div class="divide-y divide-gray-100">
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A <strong class="text-orange-600">fun</strong> and <strong class="text-orange-600">engaging</strong> place to work</p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7m-9-9v9m0 9H3m11 0h5m-5 0v-5a2 2 0 00-2-2h-2a2 2 0 00-2 2v5"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A workplace that feels like a <strong class="text-orange-600">second home</strong> — supportive, warm, and welcoming</p>
                </div>
                <div class="flex items-start gap-4 px-5 py-4">
                  <div class="size-9 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                    <svg class="size-4 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed pt-1.5">A culture where people <strong class="text-orange-600">grow, belong</strong>, and <strong class="text-orange-600">thrive</strong> together</p>
                </div>
              </div>
            </div>
          </div>

          <!-- ── HR Brand Promise Banner ── -->
          <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-950 to-emerald-900 px-6 md:px-8 py-6 mb-10">
            <svg class="absolute right-0 bottom-0 h-full opacity-15" viewBox="0 0 200 100" fill="none" preserveAspectRatio="xMaxYMax meet">
              <path d="M10 80 L60 60 L110 75 L160 55 L195 65 L195 100 L10 100 Z" fill="white"/>
            </svg>
            <div class="relative flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
              <div class="size-16 rounded-full border-2 border-orange-500 flex items-center justify-center shrink-0">
                <svg class="size-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21C12 21 4 16.5 4 10.5C4 7.42 6.42 5 9.5 5C11.04 5 12.5 5.8 13.4 7.05C14.3 5.8 15.75 5 17.29 5C20.37 5 22.79 7.42 22.79 10.5C22.79 16.5 14.79 21 14.79 21H12Z"/></svg>
              </div>
              <div>
                <span class="inline-block bg-orange-500 text-white text-[0.65rem] font-bold tracking-widest uppercase rounded-full px-3 py-1 mb-2">Our HR Brand Promise</span>
                <p class="text-lg md:text-xl font-extrabold text-white leading-tight">
                  Where <span class="text-orange-500">Careers</span> Last. Where <span class="text-orange-500">People Belong</span>.
                </p>
                <p class="text-sm text-white/70 mt-1">
                  Stability is not just our message — it is <strong class="text-white">our track record</strong>.
                </p>
              </div>
            </div>
          </div>

          <!-- ── Hiring / Contact Card ── -->
          <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
            <div class="flex items-start gap-4 mb-5">
              <div class="size-11 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                <svg class="size-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h2 class="font-display text-xl font-bold text-gray-900 mb-1">We're always looking for people who stay.</h2>
                <p class="text-sm text-gray-500 leading-relaxed">
                  We don't have a long list of open roles posted here — that's on purpose. We hire in small numbers, when we know a role is a real, lasting fit, not just a seat to fill.
                </p>
              </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 mb-6">
              <div class="rounded-xl bg-gray-50 p-4 text-center">
                <div class="text-2xl font-extrabold text-emerald-950">30+</div>
                <div class="text-xs text-gray-500 mt-1">years, our longest-tenured teammates</div>
              </div>
              <div class="rounded-xl bg-gray-50 p-4 text-center">
                <div class="text-2xl font-extrabold text-emerald-950">0</div>
                <div class="text-xs text-gray-500 mt-1">layoffs, even through the pandemic</div>
              </div>
              <div class="rounded-xl bg-gray-50 p-4 text-center">
                <div class="text-2xl font-extrabold text-emerald-950">60–65</div>
                <div class="text-xs text-gray-500 mt-1">the age many of our team retire at, with us</div>
              </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
              <p class="text-sm text-gray-700 leading-relaxed mb-4">
                If that sounds like the kind of place you want to build a career, not just take a job, send us your resume and a short note about what you're looking for. Our HR team reads every message personally.
              </p>
              <a href="mailto:hrd@fishbrokers.net?subject=Job%20Application%20-%20St.%20Joseph%20Fish%20Brokerage"
                class="inline-flex items-center gap-2 py-3 px-6 rounded-xl bg-orange-600 text-white font-bold text-sm hover:bg-orange-700 hover:-translate-y-0.5 transition-all shadow-lg shadow-orange-600/25">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Email hrd@fishbrokers.net
              </a>
              <p class="text-xs text-gray-400 mt-3">We typically respond within 3–5 business days.</p>
            </div>
          </div>

        </div>
      </div>

      <!-- RIGHT: SIDEBAR -->
      <div data-aos="fade-left" data-aos-delay="200" class="space-y-4">

        <!-- Market Locations -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <h4 class="flex items-center gap-2 text-sm font-bold text-gray-900 mb-4">
            <svg class="size-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Our Market Locations
          </h4>
          <?php foreach ([
            ['#eff6ff','#1a6fa8','Navotas Fish Port Complex','North Bay Blvd South, Navotas City','Open Daily, Back Office (8:30 AM - 6:00 PM) till Friday | Markets (6:00 PM - 2:30 AM) till Sunday'],
            ['#f0fdfa','#0d9488','Malabon Consignacion','Malabon City, Metro Manila','Open daily, 6:00 PM - 2:30 PM'],
            ['#fff7ed','#ea580c','Davao Toril Fish Port','Toril District, Davao City','Open daily, 8:30 AM - 6:00 PM'],
          ] as $i => [$bg,$color,$name,$addr,$hours]): ?>
          <div class="flex gap-3 <?= $i<2 ? 'pb-3 mb-3 border-b border-gray-50' : '' ?>">
            <div class="size-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $bg ?>">
              <svg class="size-4" style="color:<?= $color ?>" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </div>
            <div>
              <p class="font-semibold text-sm text-gray-900"><?= $name ?></p>
              <p class="text-xs text-gray-500 mt-0.5"><?= $addr ?></p>
              <p class="text-xs text-gray-400"><?= $hours ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Contact Info -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <h4 class="flex items-center gap-2 text-sm font-bold text-gray-900 mb-4">
            <svg class="size-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Get in Touch Directly
          </h4>
          <div class="space-y-3 text-sm">
            <a href="mailto:marketing@fishbrokers.net" class="flex items-center gap-3 text-gray-600 hover:text-orange-600 transition-colors">
              <svg class="size-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              marketing@fishbrokers.net
            </a>
            <a href="tel:+6328001234" class="flex items-center gap-3 text-gray-600 hover:text-orange-600 transition-colors">
              <svg class="size-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              (02) 8397-4929
            </a>
            <a href="https://facebook.com/stjosephfishbrokerage" target="_blank" rel="noopener" class="flex items-center gap-3 text-gray-600 hover:text-orange-600 transition-colors">
              <svg class="size-4 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              Facebook Page
            </a>
          </div>
        </div>

        <!-- Response Times -->
        <div class="rounded-2xl bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400 p-5 shadow-sm">
          <h4 class="flex items-center gap-2 text-sm font-bold text-white mb-4">
            <svg class="size-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Response Times
          </h4>
          <div class="space-y-2 text-xs">
            <?php foreach ([['General Inquiries','1–2 days'],['Bulk/Partnership Orders','Same day'],['Career Applications','5–7 days']] as [$label,$time]): ?>
            <div class="flex justify-between items-center">
              <span class="text-white/90"><?= $label ?></span>
              <span class="font-semibold text-white/90"><?= $time ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Career Perks — hidden initially -->
        <div id="career-perks-card" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm">
          <h4 class="text-sm font-bold text-amber-800 mb-3">⭐ Why Work With Us</h4>
          <ul class="space-y-1.5 text-xs text-amber-800">
            <?php foreach (['Good minimum compensation + benefits','SSS, PhilHealth, Pag-IBIG covered','13th month pay + performance bonuses','Career growth within our growing network','Stable, family-oriented workplace culture','Meals / allowances for port-side roles'] as $perk): ?>
            <li class="flex items-center gap-2">
              <svg class="size-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <?= $perk ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('./components/footer.php'); ?>
<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php include('live_chat.php'); ?>
<script>
AOS.init({ once:true });

// Tab switching (panel show/hide + hs-tab-active styling) is now handled by
// Preline's HSTabs plugin via data-hs-tab, auto-initialized from preline.min.js.
// We only need this tiny listener to also toggle the sidebar "career perks" card,
// which isn't part of what HSTabs manages.
document.getElementById('tab-inquiry').addEventListener('click', () => {
  document.getElementById('career-perks-card').classList.add('hidden');
});
document.getElementById('tab-career').addEventListener('click', () => {
  document.getElementById('career-perks-card').classList.remove('hidden');
});

// File input
function setupFileInput(inputId, listId) {
  const input = document.getElementById(inputId), list = document.getElementById(listId);
  if (!input || !list) return;
  let files = [];
  input.addEventListener('change', function() { files.push(...Array.from(this.files)); render(); });
  function render() {
    list.innerHTML = '';
    files.forEach((f,i) => {
      const el = document.createElement('div');
      el.className = 'flex items-center justify-between py-1.5 px-3 bg-gray-50 rounded-lg text-xs text-gray-600';
      el.innerHTML = `<span>📄 ${f.name} <span class="text-gray-400">(${(f.size/1024).toFixed(1)} KB)</span></span><button type="button" class="text-red-400 hover:text-red-600 ml-2 font-bold">×</button>`;
      el.querySelector('button').onclick = () => { files.splice(i,1); render(); const dt=new DataTransfer(); files.forEach(f=>dt.items.add(f)); input.files=dt.files; };
      list.appendChild(el);
    });
    const dt = new DataTransfer(); files.forEach(f=>dt.items.add(f)); input.files=dt.files;
  }
}
setupFileInput('inq-files','inq-file-list');

// Drag & drop (visual feedback swapped to Tailwind utility classes instead of a custom .dragover class)
['inq-dropzone'].forEach(id => {
  const el = document.getElementById(id); if(!el) return;
  el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('border-amber-500','bg-amber-50'); });
  el.addEventListener('dragleave', () => el.classList.remove('border-amber-500','bg-amber-50'));
  el.addEventListener('drop', e => { e.preventDefault(); el.classList.remove('border-amber-500','bg-amber-50'); });
});

// Form submit handler
async function submitForm(formId, loadingId, successId, errorId, errorTextId, btnId) {
    const form = document.getElementById(formId);
    const [loading, success, error, btn] = [loadingId, successId, errorId, btnId].map(id => document.getElementById(id));

    [loading, success, error].forEach(el => el.classList.add('hidden'));
    loading.classList.remove('hidden');
    btn.disabled = true;

    try {
        const formData = new FormData(form);

        const response = await fetch('./functions/process_contact.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        loading.classList.add('hidden');
        btn.disabled = false;

        if (data.status === 'success') {
            success.classList.remove('hidden');
            form.reset();
            document.querySelectorAll(`#${formId} [id$="-list"]`).forEach(list => list.innerHTML = '');

            setTimeout(() => {
                success.classList.add('hidden');
            }, 5000);
        } else {
            document.getElementById(errorTextId).textContent = data.message || 'Something went wrong. Please try again.';
            error.classList.remove('hidden');
        }
    } catch (err) {
        console.error('Form submission error:', err);
        loading.classList.add('hidden');
        btn.disabled = false;
        document.getElementById(errorTextId).textContent = 'Connection error: ' + err.message;
        error.classList.remove('hidden');
    }
}

// Bind form submission
document.getElementById('inquiry-form').addEventListener('submit', e => {
    e.preventDefault();
    submitForm('inquiry-form', 'inq-loading', 'inq-success', 'inq-error', 'inq-error-text', 'inq-submit');
});

// FIXED: there is no <form id="career-form"> anywhere in panel-career (it's an HR-branding
// block with only a mailto: link, no application form fields). Calling .addEventListener on
// a null element throws and used to silently kill the rest of this script — including the
// ?tab=career auto-switch below. Guarded so the page keeps working; add a real #career-form
// here if/when you build one.
const careerForm = document.getElementById('career-form');
if (careerForm) {
  careerForm.addEventListener('submit', e => {
    e.preventDefault();
    submitForm('career-form', 'career-loading', 'career-success', 'career-error', 'career-error-text', 'career-submit');
  });
}

// Auto-switch via URL param — triggers Preline's tab click handling directly
if (new URLSearchParams(location.search).get('tab') === 'career') {
  document.getElementById('tab-career').click();
}
</script>
</body>
</html>