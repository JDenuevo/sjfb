<style>
  :root {
    --grad-orange: linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%);
  }
  .ff-display { font-family: 'Playfair Display', Georgia, serif; }

  /* Gradient text / bg */
  .text-grad {
    background: var(--grad-orange);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .bg-grad { background: var(--grad-orange); }

  /* Dot-grid texture */
  .dot-grid::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(249,115,22,.05) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none; z-index: 0;
  }

  /* Photo entrance (used with data-aos when available, harmless fallback otherwise) */
  .photo-card { will-change: transform; }

  /* Dashed orbit ring behind the collage — pure decoration */
  .orbit-ring {
    position: absolute;
    border: 1.5px dashed rgba(251,146,60,.28);
    border-radius: 9999px;
  }

  /* ── Gentle floating motion, applied to a wrapper so it doesn't fight
       the figure's own rotate / hover-scale transform ── */
  @keyframes floatY {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-14px); }
  }
  .float-wrap {
    animation: floatY 6s ease-in-out infinite;
  }
  /* Pause the float on hover so the figure's hover-scale/rotate reads cleanly */
  .float-wrap:hover { animation-play-state: paused; }
</style>

<section
  class="relative overflow-hidden bg-white dot-grid"
  aria-labelledby="network-heading"
  itemscope
  itemtype="https://schema.org/Organization">

  <!-- Glow blobs -->
  <div class="pointer-events-none absolute -top-32 -right-40 w-[560px] h-[560px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.14)_0%,transparent_70%)]" aria-hidden="true"></div>
  <div class="pointer-events-none absolute -bottom-24 -left-28 w-[420px] h-[420px] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,.11)_0%,transparent_70%)]" aria-hidden="true"></div>
  <div class="pointer-events-none absolute top-1/3 left-1/4 w-[320px] h-[320px] rounded-full bg-[radial-gradient(circle,rgba(251,146,60,.06)_0%,transparent_70%)]" aria-hidden="true"></div>

  <div class="relative z-10 mx-auto max-w-7xl px-6 py-20 lg:py-24">

    <!-- Eyebrow -->
    <div class="flex items-center justify-center gap-3 mb-10" data-aos="fade-up" data-aos-duration="600">
      <span class="w-8 h-px bg-orange-300"></span>
      <span class="text-xs font-bold tracking-[0.22em] text-orange-600 uppercase">Since 1979 &middot; St. Joseph Fish Brokerage Inc.</span>
      <span class="w-8 h-px bg-orange-300"></span>
    </div>

    <!-- ── DESKTOP COLLAGE (photos scattered around centered heading) ── -->
    <div class="relative hidden lg:flex items-center justify-center min-h-[820px]">

      <!-- Decorative orbit rings behind everything -->
      <div class="orbit-ring w-[520px] h-[520px]" aria-hidden="true"></div>
      <div class="orbit-ring w-[680px] h-[680px] opacity-60" aria-hidden="true"></div>

      <!-- Connective dotted line accents -->
      <svg class="absolute inset-0 w-full h-full pointer-events-none" aria-hidden="true">
        <line x1="24%" y1="18%" x2="41%" y2="34%" stroke="rgba(251,146,60,.3)" stroke-width="1.5" stroke-dasharray="4 5"/>
        <line x1="76%" y1="20%" x2="59%" y2="35%" stroke="rgba(251,146,60,.3)" stroke-width="1.5" stroke-dasharray="4 5"/>
        <line x1="22%" y1="82%" x2="40%" y2="65%" stroke="rgba(251,146,60,.3)" stroke-width="1.5" stroke-dasharray="4 5"/>
        <line x1="78%" y1="80%" x2="60%" y2="64%" stroke="rgba(251,146,60,.3)" stroke-width="1.5" stroke-dasharray="4 5"/>
      </svg>

      <!-- LEFT COLUMN — stacked top → bottom with a fixed 28px gap between each -->

      <!-- Photo 1 : top-left, LANDSCAPE — top: 0 → bottom: 220 -->
      <div class="float-wrap absolute top-0 left-[8%] w-[330px] h-[220px] z-10" style="animation-duration: 6.4s; animation-delay: 0s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[-4deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-down" data-aos-duration="700" data-aos-delay="0">
          <img src="./assets/images/contents/home_1.jpg" alt="Fishermen sorting the day's catch at Navotas Fish Port" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- Photo 3 : mid-left, PORTRAIT — top: 248 (220 + 28 gap) → bottom: 548 -->
      <div class="float-wrap absolute top-[248px] left-[3%] w-[230px] h-[300px] z-10" style="animation-duration: 7.2s; animation-delay: .8s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[4deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-right" data-aos-duration="700" data-aos-delay="150">
          <img src="./assets/images/contents/home_2.jpg" alt="Brokers negotiating with buyers at the port" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- Photo 5 : bottom-left, LANDSCAPE — top: 576 (548 + 28 gap) → bottom: 796; shifted further left so it clears the center stat pills -->
      <div class="float-wrap absolute top-[576px] left-[2%] w-[330px] h-[220px] z-10" style="animation-duration: 6.8s; animation-delay: 1.6s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[5deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-up" data-aos-duration="700" data-aos-delay="250">
          <img src="./assets/images/contents/home_3.jpg" alt="Wet market seafood display" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- RIGHT COLUMN — stacked top → bottom with a fixed 28px gap between each -->

      <!-- Photo 2 : top-right, LANDSCAPE — top: 0 → bottom: 220 -->
      <div class="float-wrap absolute top-0 right-[8%] w-[330px] h-[220px] z-10" style="animation-duration: 6.6s; animation-delay: .4s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[4deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-down" data-aos-duration="700" data-aos-delay="100">
          <img src="./assets/images/contents/home_4.jpg" alt="Fresh catch laid out on ice" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- Photo 4 : mid-right, PORTRAIT — top: 248 (220 + 28 gap) → bottom: 548 -->
      <div class="float-wrap absolute top-[248px] right-[3%] w-[230px] h-[300px] z-10" style="animation-duration: 7s; animation-delay: 1.2s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[-4deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-left" data-aos-duration="700" data-aos-delay="200">
          <img src="./assets/images/contents/home_5.jpg" alt="Buyer inspecting fresh seafood quality" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- Photo 6 : bottom-right, LANDSCAPE — top: 576 (548 + 28 gap) → bottom: 796; kept clear of the center stat pills -->
      <div class="float-wrap absolute top-[576px] right-[2%] w-[330px] h-[220px] z-10" style="animation-duration: 6.2s; animation-delay: 2s;">
        <figure
          class="photo-card w-full h-full rounded-2xl overflow-hidden ring-4 ring-white shadow-[0_25px_60px_rgba(15,23,42,.18)] rotate-[-5deg] transition-transform duration-500 hover:rotate-0 hover:scale-105"
          data-aos="fade-up" data-aos-duration="700" data-aos-delay="300">
          <img src="./assets/images/contents/home_6.jpg" alt="Cold-chain delivery of seafood to buyers nationwide" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>

      <!-- Center text block -->
      <div class="relative z-30 text-center max-w-lg px-6" data-aos="zoom-in" data-aos-duration="700" data-aos-delay="150">
        <div class="inline-flex mb-5 items-center justify-center size-14 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-400 shadow-lg shadow-orange-500/30 rotate-6">
          <svg class="size-7 text-white -rotate-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fish-christianity">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M22 7s-5.646 10 -12.308 10c-3.226 .025 -6.194 -1.905 -7.692 -5c1.498 -3.095 4.466 -5.025 7.692 -5c6.662 0 12.308 10 12.308 10" />
          </svg>
        </div>
        <h2 id="network-heading" class="ff-display text-4xl xl:text-5xl font-bold text-slate-900 leading-[1.15] tracking-tight">
          The Largest Fish Brokerage<br>Network in the <em class="text-grad not-italic">Philippines</em>
        </h2>

        <div class="mt-8 inline-flex flex-wrap justify-center gap-2.5">
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 backdrop-blur-sm shadow-sm pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">1000+</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Verified Suppliers</span>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 backdrop-blur-sm shadow-sm pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">PH</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Nationwide Coverage</span>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 backdrop-blur-sm shadow-sm pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">Daily</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Fresh Catch Sourced</span>
          </div>
        </div>
      </div>

    </div>

    <!-- ── MOBILE / TABLET: heading first, then a scrollable photo strip ── -->
    <div class="lg:hidden">
      <div class="text-center max-w-xl mx-auto" data-aos="fade-up" data-aos-duration="700">
        <div class="inline-flex mb-4 items-center justify-center size-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-400 shadow-lg shadow-orange-500/30 rotate-6">
          <svg class="size-6 text-white -rotate-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-fish-christianity">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M22 7s-5.646 10 -12.308 10c-3.226 .025 -6.194 -1.905 -7.692 -5c1.498 -3.095 4.466 -5.025 7.692 -5c6.662 0 12.308 10 12.308 10" />
          </svg>
        </div>
        <h2 class="ff-display text-3xl sm:text-4xl font-bold text-slate-900 leading-[1.15] tracking-tight">
          The Largest Fish Brokerage Network in the <em class="text-grad not-italic">Philippines</em>
        </h2>
        <p class="mt-5 text-slate-500 leading-relaxed">
          Connecting Filipino fishermen with buyers, wholesalers, restaurants, and retailers —
          delivering fresh seafood with full traceability, fair pricing, and nationwide reach.
        </p>
        <div class="mt-7 inline-flex flex-wrap justify-center gap-2.5">
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">1000+</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Verified Suppliers</span>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">PH</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Nationwide Coverage</span>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 pl-3 pr-4 py-2">
            <span class="ff-display text-sm font-bold text-grad">Daily</span>
            <span class="text-[.7rem] font-semibold text-slate-500 tracking-wide">Fresh Catch Sourced</span>
          </div>
        </div>
      </div>

      <!-- snap-scroll photo strip -->
      <div class="mt-10 -mx-6 px-6 flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
        <figure class="snap-center shrink-0 w-56 h-72 rounded-2xl overflow-hidden shadow-lg ring-4 ring-white">
          <img src="./assets/images/contents/home_1.jpg" alt="Fishermen sorting the day's catch" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
        <figure class="snap-center shrink-0 w-56 h-72 rounded-2xl overflow-hidden shadow-lg ring-4 ring-white">
          <img src="./assets/images/contents/home_2.jpg" alt="Buyer inspecting fresh seafood quality" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
        <figure class="snap-center shrink-0 w-56 h-72 rounded-2xl overflow-hidden shadow-lg ring-4 ring-white">
          <img src="./assets/images/contents/home_3.jpg" alt="Brokers negotiating with buyers at the port" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
        <figure class="snap-center shrink-0 w-56 h-72 rounded-2xl overflow-hidden shadow-lg ring-4 ring-white">
          <img src="./assets/images/contents/home_5.jpg" alt="Wet market seafood display" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
        <figure class="snap-center shrink-0 w-56 h-72 rounded-2xl overflow-hidden shadow-lg ring-4 ring-white">
          <img src="./assets/images/contents/home_6.jpg" alt="Cold-chain delivery to buyers nationwide" class="w-full h-full object-cover" loading="lazy" decoding="async">
        </figure>
      </div>
    </div>

  </div>
</section>

<p class="sr-only">Fish brokerage Philippines, fresh seafood supplier Philippines, wholesale fish distributor, seafood trading platform, Philippine fish market, buy fresh fish Philippines, seafood wholesaler Navotas, bulk fish supply restaurant.</p>