<style> 
  .marquee-wrapper {
  display: flex;
  overflow: hidden;
  width: 100%;
  position: relative;
  margin-bottom: 2rem;
}

.marquee-content {
  display: flex;
  animation: marquee 60s linear infinite; /* Increased from 15s to 30s */
  gap: 10px; /* Add space between images */
}

.marquee-item {
  flex-shrink: 0; /* Prevent image resizing */
  width: auto;
  height: 200px; /* Maintain aspect ratio */
  overflow: hidden; /* Crop image overflow */
  border-radius: 10px;
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
}

.marquee-item img {
  width: auto;
  height: 200px; /* Maintain aspect ratio */
  object-fit: cover; /* Maintain image aspect ratio */
}

/* Hover pause effect */
.marquee-wrapper:hover .marquee-content {
  animation-play-state: paused;
}

/* Keyframe animation for infinite scroll */
@keyframes marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-100%);
  }
}


  /* Responsive design */
  @media (max-width: 768px) {
    .marquee-item img {
      width: 200px;
      height: 150px;
    }
    
    .marquee-item {
      width: 200px;
      height: 150px;
    }
    
    .marquee-content {
      animation-duration: 120s; /* Double the duration for mobile */
    }
  }
  
</style>

<!-- Section Header -->
<div class="relative">
  <img src="./assets/images/wave-title.svg" alt="Wave Background" loading="lazy" class="w-full h-auto" data-aos="fade-zoom-in" data-aos-duration="1000">
  <h1 class="absolute inset-0 flex items-center justify-center text-4xl font-bold text-white">Company Events</h1>
</div>

<!-- Container for Dynamic Events -->
<div id="company-events"></div>

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
      "./assets/images/Events2024/TB24_8.jpg",
      "./assets/images/Events2024/TB24_9.jpg",
      "./assets/images/Events2024/TB24_10.jpg",

      "./assets/images/Events2024/HP24_1.jpg",
      "./assets/images/Events2024/HP24_2.jpg",
      "./assets/images/Events2024/HP24_3.jpg",
      "./assets/images/Events2024/HP24_4.jpg",
      "./assets/images/Events2024/HP24_5.jpg",
      "./assets/images/Events2024/HP24_6.jpg",
      "./assets/images/Events2024/HP24_7.jpg",
      "./assets/images/Events2024/HP24_8.jpg",
      "./assets/images/Events2024/HP24_9.jpg",
      "./assets/images/Events2024/HP24_10.jpg"
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

  // Dynamically generate marquee sections
  Object.keys(eventsData).forEach((year) => {
    const yearContainer = document.createElement("div");
    yearContainer.innerHTML = `
      <!-- Section Title -->
      <div class="flex flex-row items-center max-w-[70rem] my-3 mx-auto">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icon-tabler-square text-orange-600">
          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
          <path d="M19 2h-14a3 3 0 0 0 -3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3 -3v-14a3 3 0 0 0 -3 -3z" />
        </svg>
        <h1 class="ms-2 font-bold text-xl">Events/Activities ${year}</h1>
      </div>

      <div class="marquee-wrapper">
        <div class="marquee-content" id="marquee-${year}">
          <div class="marquee-item">
          </div>
          <div class="marquee-item">
          </div>
          <!-- Add more images here -->
        </div>
      </div>
    `;
    sectionContainer.appendChild(yearContainer);

    // Populate images dynamically
    const marqueeContent = document.getElementById(`marquee-${year}`);
    eventsData[year].forEach((imageUrl) => {
      const marqueeItem = document.createElement("div");
      marqueeItem.classList.add("marquee-item");
      marqueeItem.innerHTML = `
        <a data-fancybox="gallery-${year}" href="${imageUrl}">
          <img src="${imageUrl}" alt="Event ${year}">
        </a>
      `;
      marqueeContent.appendChild(marqueeItem);
    });


    // Duplicate images for smooth infinite scrolling
    eventsData[year].forEach((imageUrl) => {
      const marqueeItem = document.createElement("div");
      marqueeItem.classList.add("marquee-item");
      marqueeItem.innerHTML = `
        <a data-fancybox="gallery-${year}" href="${imageUrl}">
          <img src="${imageUrl}" alt="Event ${year}">
        </a>
      `;
      marqueeContent.appendChild(marqueeItem);
    });
  });
</script>