<script>

  // Updated Gallery Data (Dynamic Array)
    const gallery = [
      { id: 1, src: './assets/images/products/image-1.jpg', name: 'Bangus Batangas', title: 'Bangus (Batangas)',
        description: 'Premium Bangus sourced from Batangas, known for its clean taste and firm texture.' 
      },
      { id: 2, src: './assets/images/products/image-2.jpg', name: 'Bangus Bulacan', title: 'Bangus (Bulacan)',
        description: 'Milkfish from Bulacan, celebrated for its firm texture and flavorful meat.' 
      },
      { id: 3, src: './assets/images/products/image-3.jpg', name: 'Bangus Cardona', title: 'Bangus (Cardona)',
        description: 'Milkfish grown in the clean waters of Cardona, perfect for grilling or frying.' 
      },
      { id: 4, src: './assets/images/products/image-4.jpg', name: 'Bangus Dagupan', title: 'Bangus (Dagupan)',
        description: 'Dagupan Bangus, famous for its sweet taste and tender flesh, ideal for Filipino dishes.' 
      },
      { id: 5, src: './assets/images/products/image-5.jpg', name: 'Lapu-Lapu', title: 'Grouper (Lapu-Lapu)',
        description: 'A prized fish for steaming, celebrated for its soft and delicate texture.' 
      },
      { id: 6, src: './assets/images/products/image-6.jpg', name: 'Blue Marlin', title: 'Blue Marlin',
        description: 'A meaty fish perfect for grilling or steaks, known for its rich taste.' 
      },
      { id: 7, src: './assets/images/products/image-7.jpg', name: 'Yellow Fin', title: 'Yellowfin Tuna',
        description: 'Premium Yellowfin Tuna prized for sashimi, steaks, and grilling.' 
      },
      { id: 8, src: './assets/images/products/image-8.jpg', name: 'Tanigue', title: 'Spanish Mackerel (Tanigue)',
        description: 'Firm, meaty flesh ideal for fish steaks or Filipino dishes.' 
      },
      { id: 9, src: './assets/images/products/image-9.jpg', name: 'Durado', title: 'Dorado (Mahi-Mahi)',
        description: 'A sweet and flaky fish perfect for grilling and pan-searing.' 
      },
      { id: 10, src: './assets/images/products/image-10.jpg', name: 'Imelda', title: 'Imelda Fish',
        description: 'A rare local fish variety with a distinct, delicate flavor.' 
      },
      { id: 11, src: './assets/images/products/image-11.jpg', name: 'Pangasius', title: 'Pangasius',
        description: 'A mild, versatile fish, perfect for frying, filleting, or grilling.' 
      },
      { id: 12, src: './assets/images/products/image-12.jpg', name: 'Arroyo', title: 'Arroyo Fish',
        description: 'Locally caught fish appreciated for its firm and flavorful meat.' 
      },
      { id: 13, src: './assets/images/products/image-13.jpg', name: 'Bisugo', title: 'Bisugo (Threadfin Bream)',
        description: 'A small, sweet-tasting fish, often served fried or in stews.' 
      },
      { id: 14, src: './assets/images/products/image-14.jpg', name: 'Betilla', title: 'Betilla Fish',
        description: 'A local fish variety perfect for frying and Filipino recipes.' 
      },
      { id: 15, src: './assets/images/products/image-15.jpg', name: 'Labahita', title: 'Labahita (Surgeonfish)',
        description: 'A tender and savory fish commonly used in Filipino dishes.' 
      },
      { id: 16, src: './assets/images/products/image-16.jpg', name: 'Dalagang Bukid', title: 'Dalagang Bukid',
        description: 'A bright red fish often served fried or in traditional soups.' 
      },
      { id: 17, src: './assets/images/products/image-17.jpg', name: 'Maya-Maya', title: 'Red Snapper (Maya-Maya)',
        description: 'A premium fish for grilling and stews, known for its sweet flavor.' 
      },
      { id: 18, src: './assets/images/products/image-18.jpg', name: 'Suaje', title: 'Shrimp (Suaje)',
        description: 'Fresh local shrimp, perfect for sinigang or sautéed dishes.' 
      },
      { id: 19, src: './assets/images/products/image-19.jpg', name: 'Crabs', title: 'Fresh Crabs',
        description: 'Sweet and meaty crabs ideal for steaming or crab dishes.' 
      },
      { id: 20, src: './assets/images/products/image-20.jpg', name: 'Pusit', title: 'Squid (Pusit)',
        description: 'Versatile squid perfect for adobo, grilling, or calamares.' 
      }
    ];


   // Function to dynamically render the gallery
  function renderGallery() {
    const container = document.getElementById('gallery');
    gallery.forEach(image => {
      const galleryItem = `
        <div class="gallery-item group relative cursor-pointer" data-aos="flip-left" data-aos-duration="1000">
          <img
            src="${image.src}"
            alt="${image.name}"
            loading="lazy"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 rounded-lg"
          />
          <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center rounded-lg">
            <span class="text-white text-lg font-semibold">${image.name}</span>
          </div>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', galleryItem);
    });
  }

  // Render the gallery on page load
  document.addEventListener('DOMContentLoaded', renderGallery);
</script>

<style>
  /* Enforce square aspect ratio and consistent image dimensions */
  .gallery-item {
    @apply w-full aspect-square overflow-hidden rounded-lg;
  }
  .gallery-item img {
    @apply w-full h-full object-cover;
  }
</style>

<!-- Container -->
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto mt-10">
  <h1 class="text-center text-2xl font-bold" data-aos="fade-up" data-aos-duration="2000">
    Our Fresh Produce
  </h1>
  <p class="text-gray-600 text-center mb-10" data-aos="fade-up" data-aos-duration="2000">
    We provide a variety of fresh fish from our trusted fish producers
  </p>

  <!-- Gallery -->
  <div id="gallery" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-10"></div>
</div>