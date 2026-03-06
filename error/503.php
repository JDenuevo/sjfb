<?php
http_response_code(503);
header("Retry-After: 3600");
$pageTitle = "Service Unavailable";

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error 503</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="width=device-width, initial-scale=1.0">
  
  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Preline UI -->
  <link href="https://cdn.jsdelivr.net/npm/preline@1.8.0/dist/preline.min.css" rel="stylesheet">
  
  <style>
    @keyframes swim-left {
      0% { 
        transform: translateX(100vw) scaleX(1); /* Start right side, facing left */
        opacity: 0;
      }
      10% { opacity: 0.7; }
      90% { opacity: 0.7; }
      100% { 
        transform: translateX(-100px) scaleX(1); /* Move left, still facing left */
        opacity: 0;
      }
    }
    
    @keyframes swim-right {
      0% { 
        transform: translateX(-100px) scaleX(-1); /* Start left side, flipped right */
        opacity: 0;
      }
      10% { opacity: 0.7; }
      90% { opacity: 0.7; }
      100% { 
        transform: translateX(100vw) scaleX(-1); /* Move right, still facing right */
        opacity: 0;
      }
    }
    
    .bubble {
      position: absolute;
      background-color: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      animation: float-up linear infinite;
    }
    
    @keyframes float-up {
      0% {
        transform: translateY(0) translateX(0);
        opacity: 0;
      }
      10% {
        opacity: 0.5;
      }
      100% {
        transform: translateY(-100vh) translateX(20px);
        opacity: 0; 
      }
    }
    
    .fish {
      position: absolute;
      filter: blur(1px);
      opacity: 0;
      z-index: 1;
    }
    
    .fish svg {
      width: 100%;
      height: 100%;
    }
     
  </style>
</head>

<body class="bg-gradient-to-b from-sky-300 via-blue-600 to-blue-900 min-h-screen flex flex-col items-center justify-center overflow-hidden relative">
  <!-- Content -->
  <div class="relative z-10 text-center px-4">
    <h1 class="text-5xl font-bold text-white mb-2">Uh Oh! Lost at Sea?</h1>
    <p class="text-sm md:text-xl text-blue-100 mb-8">Looks like this page drifted a bit too far into the deep. Let's navigate you back to calmer waters!</p>
    
    <!-- 404 Logo -->
    <div class="flex justify-center mb-8 md:my-40">
      <img src="../assets/icons/404-logo.svg" loading="lazy" class="w-64 md:w-96 h-auto">
    </div>
    
    <!-- Go Home Button -->
    <a href="/" class="hs-button inline-flex items-center justify-center gap-x-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-full px-6 py-3 transition-all duration-300 hover:scale-105">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" />
      </svg>
      Go Back
    </a>
  </div>

  <!-- Bubbles -->
  <div id="bubbles-container" class="absolute inset-0 overflow-hidden"></div>
  
  <!-- Fish Container -->
  <div id="fish-container" class="absolute inset-0 overflow-hidden blur-md"></div>
  
  <script src="https://cdn.jsdelivr.net/npm/preline@1.8.0/dist/preline.min.js"></script>

  <script>
    // Create bubbles
    document.addEventListener('DOMContentLoaded', () => {
      const bubblesContainer = document.getElementById('bubbles-container');
      const fishContainer = document.getElementById('fish-container');
      
      function createBubble() {
        const bubble = document.createElement('div');
        bubble.classList.add('bubble');
        
        // Random size between 5 and 20px
        const size = Math.random() * 15 + 5;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        
        // Random position at bottom
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.bottom = '0';
        
        // Random animation duration (10-20s)
        const duration = Math.random() * 10 + 10;
        bubble.style.animationDuration = `${duration}s`;
        
        // Random delay
        bubble.style.animationDelay = `${Math.random() * 5}s`;
        
        bubblesContainer.appendChild(bubble);
        
        // Remove bubble after animation completes
        setTimeout(() => {
          bubble.remove();
        }, duration * 1000);
      }
      
      // Create fish
      function createFish() {
        const fish = document.createElement('div');
        fish.classList.add('fish');
        
        // Random size between 40 and 120px
        const size = Math.random() * 80 + 40;
        fish.style.width = `${size}px`;
        fish.style.height = `${size}px`;
        
        // Random position vertically
        fish.style.top = `${Math.random() * 100}%`;
        
        // Random animation duration (20-60s)
        const duration = Math.random() * 40 + 20;
        
        // Randomly decide direction (left or right)
        const isRightSwimming = Math.random() > 0.5;
        
        if (isRightSwimming) {
          // Fish swimming right (flip horizontally)
          fish.style.animation = `swim-right ${duration}s linear infinite`;
          fish.style.transform = 'scaleX(-1)'; // Flip horizontally
        } else {
          // Fish swimming left (natural orientation)
          fish.style.animation = `swim-left ${duration}s linear infinite`;
        }
        
        // Random delay
        fish.style.animationDelay = `${Math.random() * 10}s`;
        
        // Add fish SVG
        fish.innerHTML = `
          <svg width="312" height="208" viewBox="0 0 312 208" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M241.44 104.001C241.44 104.001 233.44 112.001 220.16 122.721C204.208 135.934 187.007 147.562 168.8 157.441C158.414 163.074 147.406 167.477 136 170.561C129.264 172.351 122.33 173.292 115.36 173.361C105.687 173.38 96.0375 172.415 86.56 170.481C82.7063 169.852 78.8878 169.025 75.12 168.001C71.0668 166.854 67.0863 165.465 63.2 163.841H62.48C33.44 151.121 0 104.001 0 104.001C0 104.001 30.88 59.8406 56.88 47.2806C58.96 46.3206 61.12 45.3606 63.28 44.4806C67.12 42.8806 71.28 41.5205 75.2 40.2405L79.76 38.9605C81.76 38.4805 83.68 38.0005 85.76 37.6805C89.2 36.8805 92.8 36.4006 96.4 35.8406C102.603 34.9862 108.858 34.5585 115.12 34.5606C127.697 34.9403 140.094 37.653 151.68 42.5606C156.927 44.6195 162.055 46.9699 167.04 49.6006L168.88 50.5606C177.157 54.9415 185.171 59.8031 192.88 65.1206C202.96 71.8406 212.08 78.8806 219.68 85.0406C233.12 96.0006 241.44 104.001 241.44 104.001Z" fill="#F7B1AD"/>
            <path d="M285.04 103.441C301.04 118.241 312 171.361 312 171.361C312 171.361 257.28 175.921 240.8 163.361C232.8 156.801 224.8 138.881 220.16 122.961C233.2 112.001 241.44 104.001 241.44 104.001C241.44 104.001 233.44 96.0006 219.84 85.0406C224.88 69.0406 232.16 50.3206 240.8 43.6806C257.28 30.8806 312 35.6806 312 35.6806C312 35.6806 300.8 88.6406 285.04 103.441ZM97.04 104.001C97.0306 116.001 93.9075 127.795 87.976 138.227C82.0446 148.66 73.5078 157.375 63.2 163.521H62.48C33.44 151.121 0 104.001 0 104.001C0 104.001 30.88 59.8406 56.88 47.2806C58.96 46.3206 61.12 45.3606 63.28 44.4806C73.5836 50.6243 82.1132 59.3411 88.0317 69.7757C93.9503 80.2104 97.0546 92.0043 97.04 104.001Z" fill="#F98D85"/>
            <path d="M141.36 90.1605L127.44 76.2405L141.36 62.4005" stroke="#F9D9D9" stroke-width="2" stroke-miterlimit="10"/>
            <path d="M176 117.841L162.16 104.001L176 90.1605" stroke="#F9D9D9" stroke-width="2" stroke-miterlimit="10"/>
            <path d="M141.36 145.601L127.44 131.761L141.36 117.841" stroke="#F9D9D9" stroke-width="2" stroke-miterlimit="10"/>
            <circle cx="55.4399" cy="83.2006" r="8" fill="black"/>
            <ellipse cx="51" cy="82.5" rx="4" ry="5.5" fill="white"/>
            <path d="M180.24 0.000543052L172.24 41.4405C171.612 44.6171 170.535 47.688 169.04 50.5605L167.2 49.6005C162.215 46.9699 157.087 44.6195 151.84 42.5605C140.254 37.653 127.857 34.9403 115.28 34.5605C109.018 34.5585 102.763 34.9862 96.5601 35.8405C92.9601 36.3205 89.4401 36.8805 85.9201 37.6805C83.9201 37.6805 82.0001 38.4805 79.9201 38.9605L75.3601 40.2405L77.8401 27.8405C79.4183 19.9568 83.6898 12.8675 89.9223 7.78814C96.1548 2.70874 103.96 -0.0444589 112 0.000543052H180.24ZM180.24 208.001H112C103.988 208.008 96.22 205.238 90.0205 200.162C83.821 195.086 79.573 188.017 78.0001 180.161L75.1201 168.001C78.7769 169.122 82.4884 170.056 86.2401 170.801C95.7176 172.735 105.367 173.7 115.04 173.681C122.131 173.535 129.174 172.486 136 170.561C147.489 167.495 158.578 163.091 169.04 157.441C170.551 160.306 171.63 163.379 172.24 166.561L180.24 208.001Z" fill="#EFA0A0"/>
          </svg>
        `;
        
        fishContainer.appendChild(fish);
        
        // Remove fish after animation completes
        setTimeout(() => {
          fish.remove();
        }, duration * 1000);
      }
      
      // Create initial bubbles
      for (let i = 0; i < 20; i++) {
        createBubble();
      }
      
      // Create initial fish
      for (let i = 0; i < 5; i++) {
        createFish();
      }
      
      // Create new bubbles periodically
      setInterval(createBubble, 1000);
      
      // Create new fish periodically
      setInterval(createFish, 3000);
    });
  </script>
</body>
</html>