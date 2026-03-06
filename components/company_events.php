<style> 
  /* ===== MARQUEE STYLES ===== */
  .marquee-wrapper {
    display: flex;
    overflow: hidden;
    width: 100%;
    position: relative;
    margin-bottom: 1.5rem;
  }

  /* Left-to-right (default) */
  .marquee-content {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
    min-width: 100%;
  }

  .marquee-ltr .marquee-content {
    animation: marquee-ltr 90s linear infinite;
  }

  .marquee-rtl .marquee-content {
    animation: marquee-rtl 90s linear infinite;
  }

  /* Hover pause */
  .marquee-wrapper:hover .marquee-content {
    animation-play-state: paused;
  }

  .marquee-item {
    flex-shrink: 0;
    height: 210px;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .marquee-item:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
  }

  .marquee-item img {
    height: 210px;
    width: auto;
    object-fit: cover;
    display: block;
  }

  @keyframes marquee-ltr {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
  }

  @keyframes marquee-rtl {
    0%   { transform: translateX(-50%); }
    100% { transform: translateX(0); }
  }

  /* Direction badge */
  .direction-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    padding: 2px 8px;
    border-radius: 20px;
    opacity: 0.7;
  }
  .direction-badge.ltr {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
  }
  .direction-badge.rtl {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }

  @media (max-width: 768px) {
    .marquee-item,
    .marquee-item img {
      height: 150px;
    }
    .marquee-ltr .marquee-content,
    .marquee-rtl .marquee-content {
      animation-duration: 90s;
    }
    .title, .title-year{
      font-size: 12px;
    }
  }
</style>

<!-- Section Header -->
<div class="relative">
  <img src="./assets/images/wave-title.svg" alt="Wave Background" loading="lazy" class="w-full h-auto" data-aos="fade-zoom-in" data-aos-duration="1000">
  <h1 class="absolute inset-0 flex items-center justify-center text-4xl font-bold text-white title">Company Events</h1>
</div>

<!-- Container for Dynamic Events -->
<div id="company-events" class="py-4"></div>

<script>
  const eventsData = {
    "2024": [
      "./assets/images/Events2024/TB24_1.jpg",
      "./assets/images/Events2024/TB24_2.jpg",
      "./assets/images/Events2024/TB24_3.jpg",
      "./assets/images/Events2024/TB24_4.jpg",
      "./assets/images/Events2024/TB24_5.jpg",
      "./assets/images/Events2024/TB24_6.jpg",
      "./assets/images/Events2024/TB24_7.jpg",
      "./assets/images/Events2024/HP24_1.jpg",
      "./assets/images/Events2024/HP24_2.jpg",
      "./assets/images/Events2024/HP24_3.jpg",
      "./assets/images/Events2024/HP24_4.jpg",
      "./assets/images/Events2024/HP24_5.jpg",
      "./assets/images/Events2024/HP24_6.jpg",
      "./assets/images/Events2024/HP24_7.jpg",
      "./assets/images/Events2024/YE24_1.jpg",
      "./assets/images/Events2024/YE24_2.jpg",
      "./assets/images/Events2024/YE24_3.jpg",
      "./assets/images/Events2024/YE24_4.jpg",
      "./assets/images/Events2024/YE24_5.jpg",
      "./assets/images/Events2024/YE24_6.jpg",
    ],
    "2023": [
      "./assets/images/Events2023/TB23_1.jpg",
      "./assets/images/Events2023/TB23_2.jpg",
      "./assets/images/Events2023/TB23_3.jpg",
      "./assets/images/Events2023/TB23_4.jpg",
      "./assets/images/Events2023/TB23_5.jpg",
      "./assets/images/Events2023/TB23_6.jpg",
      "./assets/images/Events2023/TB23_7.jpg",
      "./assets/images/Events2023/HP23_1.jpg",
      "./assets/images/Events2023/HP23_2.jpg",
      "./assets/images/Events2023/HP23_3.jpg",
      "./assets/images/Events2023/HP23_4.jpg",
      "./assets/images/Events2023/HP23_5.jpg",
      "./assets/images/Events2023/HP23_6.jpg",
      "./assets/images/Events2023/YE23_1.jpg",
      "./assets/images/Events2023/YE23_2.jpg",
      "./assets/images/Events2023/YE23_3.jpg",
      "./assets/images/Events2023/YE23_4.jpg",
      "./assets/images/Events2023/YE23_5.jpg",
      "./assets/images/Events2023/YE23_6.jpg",
      "./assets/images/Events2023/YE23_7.jpg",
    ]
  };

  const sectionContainer = document.getElementById("company-events");
  const years = Object.keys(eventsData);

  years.forEach((year, index) => {
    // Alternate: even index = LTR, odd index = RTL
    const isRTL = index % 2 !== 0;
    const dirClass = isRTL ? 'marquee-rtl' : 'marquee-ltr';
    const badgeClass = isRTL ? 'rtl' : 'ltr';
    const arrowIcon = isRTL
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5-7 7 7 7"/></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>`;

    const yearContainer = document.createElement("div");
    yearContainer.innerHTML = `
      <div class="flex flex-row items-center gap-3 max-w-[70rem] mb-3 mx-auto px-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" class="text-orange-600 shrink-0">
          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
          <path d="M19 2h-14a3 3 0 0 0 -3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3 -3v-14a3 3 0 0 0 -3 -3z" />
        </svg>
        <h2 class="font-bold text-xl title-year">Events/Activities ${year}</h2>
      </div>

      <div class="marquee-wrapper ${dirClass}">
        <div class="marquee-content" id="marquee-${year}"></div>
      </div>
    `;
    sectionContainer.appendChild(yearContainer);

    const marqueeContent = document.getElementById(`marquee-${year}`);

    // Build all items (original + duplicate for seamless loop)
    const buildItems = () => {
      eventsData[year].forEach((imageUrl) => {
        const marqueeItem = document.createElement("div");
        marqueeItem.classList.add("marquee-item");
        marqueeItem.innerHTML = `
          <a data-fancybox="gallery-${year}" href="${imageUrl}">
            <img src="${imageUrl}" alt="Event ${year}" loading="lazy">
          </a>
        `;
        marqueeContent.appendChild(marqueeItem);
      });
    };

    buildItems(); // original set
    buildItems(); // duplicate for seamless infinite scroll
  });
</script>