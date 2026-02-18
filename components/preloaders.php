<!-- Preloader -->
<div id="preloader" class="shadow-xl" style="position: fixed; top: 0; left: 0; width: 100%; height: 0; background-color: #fff; display: flex; justify-content: center; align-items: center; z-index: 9999; overflow: hidden;">
  <div class="loader" style="text-align: center; opacity: 0; animation: pulse 2s infinite;">
    <svg id="preloaderSVG" width="150" height="150" viewBox="0 0 502 402" fill="none" xmlns="http://www.w3.org/2000/svg">
      <!-- Your SVG paths here -->
    </svg>
  </div>
</div>

<style scoped>
  @keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
  }

  @keyframes slideDownBG {
    0% { height: 0; }
    100% { height: 100%; }
  }

  @keyframes fadeInLoader {
    0% { opacity: 0; }
    100% { opacity: 1; }
  }

  #preloader.show {
    animation: slideDownBG 0.8s forwards;
  }

  .loader.show {
    animation: pulse 2s infinite, fadeInLoader 0.8s forwards;
    animation-delay: 0.8s;
  }
</style>

<script>
  $(document).ready(function () {
    // Slide down the white background
    $('#preloader').addClass('show');

    // Fade in loader after background slides
    setTimeout(function () {
      $('.loader').addClass('show');
    }, 800);

    // Wait a bit while preloader is down (1s) and swap content
    setTimeout(function () {
      $('#preloader').fadeOut(800, function () {
        $(this).remove();
        $('#content').fadeIn(500);
      });
    }, 2500); // slightly longer pause
  });
</script>
