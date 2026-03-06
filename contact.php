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
  <meta name="twitter:card" content="summary_large_image"><link rel="canonical" href="https://fishbrokers.net/contact">
  <link rel="shortcut icon" href="./assets/icons/logo.ico"><link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css">
  <link href="style.css" rel="stylesheet"><link href="output.css" rel="stylesheet">
  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

  <style>
    /* Absolute minimum custom CSS — only what Preline/Tailwind can't express */
    body { font-family:'Lexend',sans-serif; background:#f9fafb; }
    .font-display { font-family:'Playfair Display',serif; }

    /* Hero — brand gradient */
    .contact-hero { background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%); padding:7rem 1.5rem 5rem; position:relative; overflow:hidden; }
    .contact-hero::before { content:''; position:absolute; inset:0; background-image:radial-gradient(circle at 10% 20%,rgba(255,255,255,.05) 0%,transparent 40%),radial-gradient(circle at 90% 80%,rgba(255,255,255,.04) 0%,transparent 40%); }
    @media (max-width:768px) { .contact-hero { padding:5rem 1.25rem 4rem; } }

    /* Form panel toggle */
    .form-panel { display:none; }
    .form-panel.active { display:block; }

    /* Position card (career) */
    .position-card { padding:1rem 1.125rem; border-radius:.875rem; border:1.5px solid #e5e7eb; cursor:pointer; transition:all .2s; background:white; }
    .position-card:hover { border-color:#f59e0b; background:#fffbeb; }
    .position-card.selected { border-color:#f59e0b; background:#fffbeb; box-shadow: 0 0 0 3px rgba(245,158,11,.25); }
    .position-card input[type="radio"] { display:none; }

    /* Industry tag toggle */
    .tag-btn { padding:.375rem .625rem; border-radius:.5rem; border:1.5px solid #fbbf24; font-size:.75rem; font-weight:600; cursor:pointer; background:white; color:#92400e; transition:all .15s; }
    .tag-btn.selected { border-color:#f59e0b; background:#fffbeb; color:#b45309; }

    /* Progress bar gradient */
    .career-progress-bar { background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%); transition:width .4s ease; }

    /* File dropzone */
    .file-dropzone { border:2px dashed #d1d5db; border-radius:.875rem; padding:1.5rem; text-align:center; cursor:pointer; transition:all .2s; background:#fafafa; }
    .file-dropzone:hover, .file-dropzone.dragover { border-color:#f59e0b; background:#fffbeb; }

    /* Form section label */
    .form-section-label { font-size:.6875rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#f97316; margin:1.5rem 0 .875rem; display:flex; align-items:center; gap:.5rem; }
    .form-section-label::after { content:''; flex:1; height:1px; background:#e5e7eb; }

    /* Honeypot */
    .hp { display:none; opacity:0; position:absolute; left:-9999px; }
  </style>
</head>
<body>
<?php include('./components/preloaders.php'); ?>
<?php include('./components/navigation.php'); ?>
<?php include('./components/nav_crumb.php'); ?>

<!-- HERO -->
<section class="contact-hero">
  <div class="relative z-10 max-w-3xl mx-auto text-center text-white">
    <div data-aos="fade-up" data-aos-duration="700">
      <span class="text-xs font-bold tracking-widest uppercase text-green-300 block mb-4">Reach Out</span>
      <h1 class="font-display text-4xl md:text-6xl font-bold mb-5">
        Let's Talk <em class="not-italic text-orange-300">Fish</em>
      </h1>
      <p class="text-lg text-white/75 leading-relaxed max-w-xl mx-auto">
        Whether you're a buyer looking for fresh supply, a fisherman wanting a trusted broker, or someone ready to build a career with us — we're here and ready.
      </p>
    </div>
  </div>
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" preserveAspectRatio="none" style="width:100%;display:block">
      <path d="M0,20 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#f9fafb"/>
    </svg>
  </div>
</section>

<!-- MAIN CONTENT -->
<section class="py-16">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid lg:grid-cols-[1fr_340px] gap-10 items-start">

      <!-- LEFT: FORM AREA -->
      <div>
        <!-- Tab Toggle — Preline pill nav pattern -->
        <div class="mb-8" data-aos="fade-up">
          <p class="text-sm font-semibold text-gray-500 mb-3">What brings you here today?</p>
          <div class="inline-flex bg-gray-100 rounded-2xl p-1 gap-1">
            <button id="tab-inquiry" onclick="switchForm('inquiry',this)"
                    class="form-tab active inline-flex items-center gap-2 py-3 px-5 rounded-xl text-sm font-semibold transition-all bg-white text-blue-800 shadow-sm">
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              General Inquiry
            </button>
            <button id="tab-career" onclick="switchForm('career',this)"
                    class="form-tab inline-flex items-center gap-2 py-3 px-5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:text-gray-700">
              <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              Apply / Careers
            </button>
          </div>
        </div>

        <!-- PANEL 1: GENERAL INQUIRY -->
        <div class="form-panel active" id="panel-inquiry" data-aos="fade-up" data-aos-delay="100">
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
              Message sent! We'll get back to you within 1–2 business days.
            </div>
            <div id="inq-error" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium">
              <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span id="inq-error-text">Something went wrong. Please try again.</span>
            </div>

            <form id="inquiry-form" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
              <input type="hidden" name="form_type" value="inquiry">
              <div class="hp"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <!-- Preline input pattern: py-3 px-4 border border-gray-200 rounded-lg focus:ring-2 -->
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
                <div class="file-dropzone" id="inq-dropzone" onclick="document.getElementById('inq-files').click()">
                  <div class="size-10 bg-orange-200 rounded-full flex items-center justify-center mx-auto mb-2">
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
                      class="w-full py-3 px-4 inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 hover:bg-orange-600 text-white font-bold text-sm transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Message
              </button>
              <p class="text-center text-xs text-gray-400 mt-3">We respond within 1–2 business days. No spam, ever.</p>
            </form>
          </div>
        </div>

        <!-- PANEL 2: CAREER -->
        <div class="form-panel" id="panel-career" data-aos="fade-up">
          <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
            <h2 class="font-display text-xl font-bold text-gray-900 mb-1">Apply to Join Our Team</h2>
            <p class="text-sm text-gray-500 mb-4">We're always looking for hardworking, honest people who are passionate about the fishing industry.</p>

            <!-- Preline progress bar -->
            <div class="flex items-center gap-2 mb-6">
              <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                <div class="career-progress-bar h-full rounded-full" id="career-progress" style="width:0%"></div>
              </div>
              <span class="text-xs font-semibold text-gray-400" id="career-pct">0%</span>
            </div>

            <!-- Alerts -->
            <div id="career-loading" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-medium">
              <svg class="animate-spin size-5 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="20"/></svg>
              Submitting your application…
            </div>
            <div id="career-success" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
              <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Application received! We'll review it and reach out if you're a good fit. Thank you!
            </div>
            <div id="career-error" class="hidden mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium">
              <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span id="career-error-text">Something went wrong. Please try again.</span>
            </div>

            <form id="career-form" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
              <input type="hidden" name="form_type" value="career">
              <div class="hp"><input type="text" name="website2" tabindex="-1" autocomplete="off"></div>

              <div class="form-section-label">Personal Information</div>
              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                  <input type="text" name="firstName" placeholder="Juan" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                  <input type="text" name="lastName" placeholder="dela Cruz" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
              </div>
              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                  <input type="email" name="email" placeholder="you@example.com" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                  <input type="tel" name="contact" placeholder="+63 9XX XXX XXXX" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
              </div>
              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Age</label>
                  <input type="number" name="age" min="18" max="65" placeholder="e.g. 25" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Address <span class="text-red-500">*</span></label>
                  <input type="text" name="address" placeholder="City, Province" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                </div>
              </div>

              <div class="form-section-label">Position & Location</div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Location applying for <span class="text-red-500">*</span></label>
                <select name="apply_location" required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                  <option value="">— Select a market —</option>
                  <option value="navotas">Navotas Fish Port Complex</option>
                  <option value="malabon">Malabon Consignacion</option>
                  <option value="davao">Davao Toril Fish Port</option>
                </select>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Position Interested In <span class="text-red-500">*</span></label>
                <div class="grid sm:grid-cols-2 gap-2">
                  <?php foreach ([
                    ['broker','🤝 Fish Broker','Negotiate between fishermen and buyers'],
                    ['coordinator','📋 Market Coordinator','Manage daily stall operations'],
                    ['logistics','🚚 Logistics Officer','Handle transport & delivery'],
                    ['accounting','📊 Finance & Accounting','Billing, records, and reports'],
                    ['quality','✅ Quality Control','Inspect and grade fish products'],
                    ['operations','⚙️ Port Operations','On-ground port support'],
                  ] as [$val,$lbl,$desc]): ?>
                  <label class="position-card" onclick="selectPosition(this)">
                    <input type="radio" name="position" value="<?= $val ?>" class="career-field">
                    <p class="text-sm font-semibold text-gray-900"><?= $lbl ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= $desc ?></p>
                  </label>
                  <?php endforeach; ?>
                </div>
                <input type="text" name="position_other" placeholder="Other position (if not listed above)" class="mt-3 py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white font-[Lexend]">
              </div>

              <div class="form-section-label">Work Experience</div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Years of Experience <span class="text-red-500">*</span></label>
                <select name="experience_years" required class="pe-9 py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                  <option value="">— Select —</option>
                  <option value="fresh">Fresh Graduate / No experience</option>
                  <option value="1-2">1–2 years</option>
                  <option value="3-5">3–5 years</option>
                  <option value="5+">5+ years</option>
                </select>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Industry Background</label>
                <div class="flex flex-wrap gap-2">
                  <?php foreach (['Fish / Seafood Industry','Food & Beverage','Logistics / Trucking','Market Trading','Government / BFAR','Banking / Finance','Fresh Graduate'] as $ind): ?>
                  <button type="button" class="tag-btn" onclick="toggleTag(this,'industry_tags')"><?= $ind ?></button>
                  <?php endforeach; ?>
                </div>
                <input type="hidden" name="industry_tags" id="industry_tags">
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Brief Work History <span class="text-red-500">*</span></label>
                <textarea name="work_history" rows="4" placeholder="Describe your relevant experience, previous employers, and roles..." required class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white resize-y career-field font-[Lexend]"></textarea>
              </div>

              <div class="form-section-label">Availability</div>
              <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Available to Start <span class="text-red-500">*</span></label>
                  <select name="start_date" required class="pe-9 py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white career-field font-[Lexend]">
                    <option value="">— Select —</option>
                    <option value="immediately">Immediately</option>
                    <option value="2-weeks">In 2 weeks</option>
                    <option value="1-month">In 1 month</option>
                    <option value="negotiable">Negotiable</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 mb-1.5">Work Arrangement</label>
                  <select name="work_type" class="pe-9 py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white font-[Lexend]">
                    <option value="">— Any —</option>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contractual">Contractual / Project-based</option>
                  </select>
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Expected Salary Range</label>
                <select name="expected_salary" class="pe-9 py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white font-[Lexend]">
                  <option value="">— Prefer not to say —</option>
                  <option value="minimum">Minimum wage</option>
                  <option value="15k-20k">₱15,000 – ₱20,000/month</option>
                  <option value="20k-30k">₱20,000 – ₱30,000/month</option>
                  <option value="30k+">₱30,000+/month</option>
                  <option value="negotiable">Negotiable</option>
                </select>
              </div>

              <div class="form-section-label">Resume & Documents</div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Upload Resume / CV <span class="text-red-500">*</span></label>
                <div class="file-dropzone" onclick="document.getElementById('career-resume').click()">
                  <div class="size-10 bg-orange-200 rounded-full flex items-center justify-center mx-auto mb-2">
                    <svg class="size-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-gray-700">Upload your resume</p>
                  <p class="text-xs text-gray-400 mt-1">PDF or DOCX preferred — max 5MB</p>
                </div>
                <input type="file" id="career-resume" name="resume" accept=".pdf,.doc,.docx" class="hidden career-field" required>
                <div id="career-resume-list" class="mt-2 space-y-1"></div>
              </div>
              <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Supporting Documents <span class="text-gray-400 font-normal">(optional)</span></label>
                <div class="file-dropzone" onclick="document.getElementById('career-docs').click()">
                  <div class="size-10 bg-orange-200 rounded-full flex items-center justify-center mx-auto mb-2">
                    <svg class="size-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  </div>
                  <p class="text-sm font-semibold text-gray-700">TOR, Certificates, IDs</p>
                  <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF — max 2MB each</p>
                </div>
                <input type="file" id="career-docs" name="documents[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden">
                <div id="career-docs-list" class="mt-2 space-y-1"></div>
              </div>
              <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Anything else you'd like us to know?</label>
                <textarea name="extra_notes" rows="3" placeholder="Special skills, language abilities, references, etc." class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500 outline-none transition bg-white resize-y font-[Lexend]"></textarea>
              </div>

              <button type="submit" id="career-submit"
                      class="w-full py-3 px-4 inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-sm transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                Submit Application
              </button>
              <p class="text-center text-xs text-gray-400 mt-3">We review all applications and contact qualified candidates within 5–7 business days.</p>
            </form>
          </div>
        </div>
      </div>

      <!-- RIGHT: SIDEBAR -->
      <div data-aos="fade-left" data-aos-delay="200" class="space-y-4">

        <!-- Market Locations — Preline card -->
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

        <!-- Response Times — Preline gradient card -->
        <div class="rounded-2xl p-5 shadow-sm" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 60%, #fbbf24  100%);">
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
          <!-- Preline list -->
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

// Tab switch
function switchForm(type, btn) {
  document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.form-tab').forEach(b => {
    b.classList.remove('bg-white','text-blue-800','shadow-sm');
    b.classList.add('text-gray-500');
  });
  document.getElementById('panel-'+type).classList.add('active');
  btn.classList.add('bg-white','text-blue-800','shadow-sm');
  btn.classList.remove('text-gray-500');
  const perks = document.getElementById('career-perks-card');
  perks.classList.toggle('hidden', type !== 'career');
}

// Position picker
function selectPosition(label) {
  document.querySelectorAll('.position-card').forEach(c => c.classList.remove('selected'));
  label.classList.add('selected');
  label.querySelector('input[type="radio"]').checked = true;
}

// Tag toggle
function toggleTag(btn, hiddenId) {
  btn.classList.toggle('selected');
  const sel = [...document.querySelectorAll('.tag-btn.selected')].map(b => b.textContent.trim());
  document.getElementById(hiddenId).value = sel.join(', ');
}

// File input
function setupFileInput(inputId, listId) {
  const input = document.getElementById(inputId), list = document.getElementById(listId);
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
setupFileInput('career-resume','career-resume-list');
setupFileInput('career-docs','career-docs-list');

// Drag & drop
['inq-dropzone'].forEach(id => {
  const el = document.getElementById(id); if(!el) return;
  el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('dragover'); });
  el.addEventListener('dragleave', () => el.classList.remove('dragover'));
  el.addEventListener('drop', e => { e.preventDefault(); el.classList.remove('dragover'); });
});

// Career progress
const careerFields = document.querySelectorAll('.career-field');
careerFields.forEach(f => f.addEventListener('input', updateProgress));
function updateProgress() {
  const filled = [...careerFields].filter(f => f.type==='radio' ? document.querySelector(`[name="${f.name}"]:checked`) : f.value.trim()).length;
  const pct = Math.round((filled/careerFields.length)*100);
  document.getElementById('career-progress').style.width = pct+'%';
  document.getElementById('career-pct').textContent = pct+'%';
}
// Form submits
async function submitForm(formId, loadingId, successId, errorId, errorTextId, btnId) {
    const form = document.getElementById(formId);
    const [loading, success, error, btn] = [loadingId, successId, errorId, btnId].map(id => document.getElementById(id));
    
    // Hide all messages
    [loading, success, error].forEach(el => el.classList.add('hidden'));
    loading.classList.remove('hidden');
    btn.disabled = true;

    try {
        const formData = new FormData(form);
        
        // Debug log
        console.log('Submitting form:', formId);
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

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
            
            // Clear file lists
            document.querySelectorAll(`#${formId} [id$="-list"]`).forEach(list => list.innerHTML = '');
            
            // Reset career-specific UI if it's the career form
            if (formId === 'career-form') {
                document.querySelectorAll('.position-card').forEach(c => c.classList.remove('selected'));
                document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('selected'));
                document.getElementById('career-progress').style.width = '0%';
                document.getElementById('career-pct').textContent = '0%';
                
                // Show application reference if provided
                if (data.app_ref) {
                    const successMsg = success.querySelector('p');
                    if (successMsg) {
                        successMsg.innerHTML = `Application received! Reference: <strong>${data.app_ref}</strong><br>We'll review it and reach out if you're a good fit. Thank you!`;
                    }
                }
            }
            
            // Auto-hide success message after 5 seconds
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

// Bind form submissions
document.getElementById('inquiry-form').addEventListener('submit', e => { 
    e.preventDefault(); 
    submitForm('inquiry-form', 'inq-loading', 'inq-success', 'inq-error', 'inq-error-text', 'inq-submit'); 
});

document.getElementById('career-form').addEventListener('submit', e => { 
    e.preventDefault(); 
    submitForm('career-form', 'career-loading', 'career-success', 'career-error', 'career-error-text', 'career-submit'); 
});

// Auto-switch via URL param
if (new URLSearchParams(location.search).get('tab') === 'career') {
  switchForm('career', document.getElementById('tab-career'));
}
</script>
</body>
</html>