<?php
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
?>

<!-- ========== FOOTER ========== -->

<!-- Wave divider -->
<div class="overflow-hidden leading-none -mb-px">
  <svg viewBox="0 0 1440 60" preserveAspectRatio="none" class="w-full h-14 block" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 30 C360 60 1080 0 1440 30 L1440 60 L0 60 Z" fill="#0F172A"/>
  </svg>
</div>

<footer class="text-gray-400" style="background-color: #0F172A;">
  <div class="max-w-7xl mx-auto px-6 pt-12 pb-8">

    <!-- ── Main grid ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

      <!-- Brand -->
      <div class="flex flex-col gap-4 sm:col-span-2 lg:col-span-1">
        <a href="<?= $baseUrl ?>" class="inline-block hover:opacity-75 transition-opacity duration-200">
          <img src="<?= $baseUrl ?>assets/icons/logo.svg"
               alt="St. Joseph Fish Brokerage Inc."
               class="h-11 w-auto"
               loading="lazy">
        </a>
        <p class="text-sm text-white/80 leading-relaxed max-w-xs">
          Isda sa Hapag ng Bawat Isa.
        </p>
        <!-- Socials -->
        <div class="flex gap-2 mt-1">
          <!-- Facebook -->
          <a href="https://www.facebook.com/stjosephbroker"
             target="_blank" rel="noopener"
             title="Facebook"
             class="w-9 h-9 flex items-center justify-center rounded-full border border-white/10 text-white hover:bg-orange-600 hover:border-orange-600 hover:text-white hover:-translate-y-0.5 transition-all duration-200">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M7 10v4h3v7h4v-7h3l1-4h-4v-2a1 1 0 0 1 1-1h3v-4h-3a5 5 0 0 0-5 5v2h-3"/>
            </svg>
          </a>
          <!-- TikTok -->
          <a href="https://www.tiktok.com/@sjfbinc"
             target="_blank" rel="noopener"
             title="TikTok"
             class="w-9 h-9 flex items-center justify-center rounded-full border border-white/10 text-white hover:bg-orange-600 hover:border-orange-600 hover:text-white hover:-translate-y-0.5 transition-all duration-200">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 7.917v4.034a9.948 9.948 0 0 1-5-1.951v4.5a6.5 6.5 0 1 1-8-6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917z"/>
            </svg>
          </a>
          <!-- Email -->
          <a href="mailto:marketing@fishbrokers.net"
             title="Email us"
             class="w-9 h-9 flex items-center justify-center rounded-full border border-white/10 text-white hover:bg-orange-600 hover:border-orange-600 hover:text-white hover:-translate-y-0.5 transition-all duration-200">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
          </a>
        </div>
      </div>

      <!-- Explore (site navigation) -->
      <div>
        <h5 class="text-[0.7rem] font-bold uppercase tracking-widest text-orange-500 mb-4">Explore</h5>
        <nav class="flex flex-col gap-2">
          <?php
          $pages = [
            'Home'           => '',
            'Shop'           => 'shop.php',
            'About'          => 'about.php',
            'Events'         => 'events.php',
            'Services'       => 'services.php',
            'Contact'        => 'contact.php',
          ];
          foreach ($pages as $label => $path): ?>
          <a href="<?= $baseUrl . $path ?>"
             class="group flex items-center gap-1.5 text-sm text-white/80 hover:text-orange-400 transition-colors duration-150">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 class="opacity-0 group-hover:opacity-100 -translate-x-1 group-hover:translate-x-0 transition-all duration-150">
              <path d="M9 18l6-6-6-6"/>
            </svg>
            <?= $label ?>
          </a>
          <?php endforeach; ?>
        </nav>
      </div>

      <!-- Legal -->
      <div>
        <h5 class="text-[0.7rem] font-bold uppercase tracking-widest text-orange-500 mb-4">Legal</h5>
        <nav class="flex flex-col gap-2">
          <button type="button" onclick="openModal('privacyModal')"
            class="group flex items-center gap-1.5 text-sm text-white/80 hover:text-orange-400 transition-colors duration-150 text-left">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 class="opacity-0 group-hover:opacity-100 -translate-x-1 group-hover:translate-x-0 transition-all duration-150">
              <path d="M9 18l6-6-6-6"/>
            </svg>
            Privacy Policy
          </button>
          <button type="button" onclick="openModal('termsModal')"
            class="group flex items-center gap-1.5 text-sm text-white/80 hover:text-orange-400 transition-colors duration-150 text-left">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 class="opacity-0 group-hover:opacity-100 -translate-x-1 group-hover:translate-x-0 transition-all duration-150">
              <path d="M9 18l6-6-6-6"/>
            </svg>
            Terms &amp; Conditions
          </button>
        </nav>
      </div>

      <!-- Contact -->
      <div>
        <h5 class="text-[0.7rem] font-bold uppercase tracking-widest text-orange-500 mb-4">Get in Touch</h5>
        <div class="flex flex-col gap-3.5">

          <!-- Address -->
          <div class="flex gap-3 items-start text-sm leading-relaxed">
            <span class="shrink-0 w-7 h-7 rounded-md bg-white/5 flex items-center justify-center text-orange-500 mt-0.5">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
            </span>
            <p class="text-white/80">Bulungan Ave. corner HACCP St., NFPC NBBS, Navotas, Philippines</p>
          </div>

          <!-- Phone -->
          <div class="flex gap-3 items-center text-sm">
            <span class="shrink-0 w-7 h-7 rounded-md bg-white/5 flex items-center justify-center text-orange-500">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </span>
            <a href="tel:+639464973689" class="text-white/80 hover:text-orange-400 transition-colors duration-150">(+63) 946-497-3689</a>
          </div>

          <!-- Email -->
          <div class="flex gap-3 items-center text-sm">
            <span class="shrink-0 w-7 h-7 rounded-md bg-white/5 flex items-center justify-center text-orange-500">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
              </svg>
            </span>
            <a href="mailto:stjosephbrokerage23@gmail.com" class="text-white/80 hover:text-orange-400 transition-colors duration-150 break-all">stjosephbrokerage23@gmail.com</a>
          </div>

          <!-- Hours badge -->
          <div class="mt-1 inline-flex items-center gap-2 text-xs font-semibold text-white bg-white/5 border border-white/10 rounded-full px-3.5 py-1.5 w-fit">
            <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_0_3px_rgba(52,211,153,0.2)] animate-pulse"></span>
            Open Mon – Sat &nbsp;·&nbsp; 8:00 AM – 6:00 PM
          </div>

        </div>
      </div>

    </div>

    <!-- ── Bottom bar ── -->
    <div class="mt-10 pt-5 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
      <p class="text-xs text-white/50">
        © <?= date('Y') ?> St. Joseph Fish Brokerage Inc. All rights reserved.
      </p>
      <p class="text-xs text-white/50">
        Made with care in Navotas City, Philippines
      </p>
    </div>

  </div>
</footer>
<!-- ========== END FOOTER ========== -->

<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/components/privacy_policy.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/components/terms_condition.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/sjfbi-js/components/shipping_refund.php'; ?>