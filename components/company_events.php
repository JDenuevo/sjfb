<!-- Container for Dynamic Events -->
<div id="company-events" class="py-4"></div>

<script>
  const eventsData = {
    "2025": [
      "./assets/images/Events2025/KO25_1.jpg",
      "./assets/images/Events2025/KO25_2.jpg",
      "./assets/images/Events2025/KO25_3.jpg",
      "./assets/images/Events2025/KO25_4.jpg",
      "./assets/images/Events2025/KO25_5.jpg",
      "./assets/images/Events2025/VAL25_1.jpg",
      "./assets/images/Events2025/HP25_1.jpg",
      "./assets/images/Events2025/HP25_2.jpg",
      "./assets/images/Events2025/HP25_3.jpg",
      "./assets/images/Events2025/TB25_1.jpg",
      "./assets/images/Events2025/TB25_2.jpg",
      "./assets/images/Events2025/TB25_3.jpg",
      "./assets/images/Events2025/TB25_4.jpg",
      "./assets/images/Events2025/TB25_5.jpg",
      "./assets/images/Events2025/TB25_6.jpg",
      "./assets/images/Events2025/TB25_7.jpg",
      "./assets/images/Events2025/TB25_8.jpg",
      "./assets/images/Events2025/YE25_1.jpg",
      "./assets/images/Events2025/YE25_2.jpg",
      "./assets/images/Events2025/YE25_3.jpg",
    ],
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
    const isRTL = index % 2 !== 0;
    const dirClass = isRTL ? 'marquee-rtl' : 'marquee-ltr';
    const badgeClass = isRTL ? 'rtl' : 'ltr';
    const directionLabel = isRTL ? 'Right to Left' : 'Left to Right';
    const arrowIcon = isRTL
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5-7 7 7 7"/></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>`;

    const yearContainer = document.createElement("div");
    yearContainer.setAttribute('data-aos', 'fade-up');
    yearContainer.innerHTML = `
      <div class="flex flex-row items-center justify-between gap-3 max-w-[70rem] mb-3 mx-auto px-4">
        <div class="flex items-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" class="text-orange-600 shrink-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M19 2h-14a3 3 0 0 0 -3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3 -3v-14a3 3 0 0 0 -3 -3z" />
          </svg>
          <h2 class="font-bold text-xl title-year font-display">Events / Activities ${year}</h2>
        </div>
        <span class="direction-badge ${badgeClass}">
          ${arrowIcon} ${directionLabel}
        </span>
      </div>

      <div class="marquee-wrapper ${dirClass}">
        <div class="marquee-content" id="marquee-${year}"></div>
      </div>
    `;
    sectionContainer.appendChild(yearContainer);

    const marqueeContent = document.getElementById(`marquee-${year}`);

    const buildItems = () => {
      eventsData[year].forEach((imageUrl) => {
        const marqueeItem = document.createElement("div");
        marqueeItem.classList.add("marquee-item");
        marqueeItem.innerHTML = `
          <a data-fancybox="gallery-${year}" href="${imageUrl}">
            <img src="${imageUrl}" alt="St. Joseph Fish Brokerage Event ${year}" loading="lazy">
          </a>
        `;
        marqueeContent.appendChild(marqueeItem);
      });
    };

    buildItems(); // original set
    buildItems(); // duplicate for seamless infinite scroll
  });
</script>