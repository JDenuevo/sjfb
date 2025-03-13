<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Alaskan King Crab Legs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-image {
            max-height: 400px;
            object-fit: cover;
            display: none; /* Hide all images initially */
        }
        .product-image.active {
            display: block; /* Show the active image */
        }
        .thumbnail {
            width: 80px; /* Adjust thumbnail size */
            height: 80px;
            object-fit: cover;
            border: 2px solid transparent; /* default border */
        }
        .thumbnail.active {
            border-color: blue; /* active border */
        }
        .product-container{
            position: relative;
        }
        .nav-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            z-index: 1;
        }

        .prev-button {
            left: 0;
        }

        .next-button {
            right: 0;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="container max-w-5xl mx-auto p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <div class="flex flex-col product-container">
            <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Fresh Alaskan King Crab Legs 1" class="product-image rounded-lg mb-4 active">
            <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Fresh Alaskan King Crab Legs 2" class="product-image rounded-lg mb-4">
            <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Fresh Alaskan King Crab Legs 3" class="product-image rounded-lg mb-4">
            <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Fresh Alaskan King Crab Legs 4" class="product-image rounded-lg mb-4">
            
            <button class="nav-button prev-button" onclick="changeImage(-1)">Prev</button>
            <button class="nav-button next-button" onclick="changeImage(1)">Next</button>

            <div class="flex space-x-2">
                <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Thumbnail 1" class="thumbnail rounded-lg cursor-pointer active" onclick="showImage(0)">
                <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Thumbnail 2" class="thumbnail rounded-lg cursor-pointer" onclick="showImage(1)">
                <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Thumbnail 3" class="thumbnail rounded-lg cursor-pointer" onclick="showImage(2)">
                <img src="https://fisherscart.com/cdn/shop/files/Bostonlobster_1024x1024@2x.png?v=1735189606" alt="Thumbnail 4" class="thumbnail rounded-lg cursor-pointer" onclick="showImage(3)">
            </div>
        </div>

        <div class="bg-white rounded-lg p-4">
            <h1 class="text-2xl font-bold mb-2">Fresh Alaskan King Crab Legs (1kg)</h1>
            <div class="flex items-center mb-2">
                <span class="line-through text-gray-500">₱24,700.00</span>
                <span class="text-red-600 font-bold ml-2">₱8,499.99</span>
                <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full ml-2">SAVE 65%</span>
            </div>
            <p class="text-sm text-gray-600 mb-2">36 people are viewing this right now <!--Realtime of how many accounts in session has been viewing this product--> </p>

            <div class="flex items-center mb-4">
                <span class="mr-2">Quantity</span>
                <button class="bg-gray-200 px-3 py-1 rounded">-</button>
                <span class="mx-2">1</span>
                <button class="bg-gray-200 px-3 py-1 rounded">+</button>
            </div>

            <div class="flex flex-col space-y-2 mb-4">
                <button class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">ADD TO CART</button>
                <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">BUY IT NOW</button>
            </div>

            <p class="text-center text-sm">Secure Checkout With</p>
            <div class="flex justify-center space-x-2">
                <img src="./assets/visa.png" alt="Visa" class="h-6">
                <img src="./assets/mastercard.png" alt="Mastercard" class="h-6">
                <img src="./assets/paypal.png" alt="PayPal" class="h-6">
                <img src="./assets/cash-on-delivery.png" alt="Cash on Delivery" class="h-6">
                <img src="./assets/satisfaction.png" alt="100% Satisfaction" class="h-6">
            </div>
        </div>
    </div>
</div>

<script>
    let currentImageIndex = 0;
    const images = document.querySelectorAll('.product-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const totalImages = images.length;

    function changeImage(direction) {
        images[currentImageIndex].classList.remove('active');
        thumbnails[currentImageIndex].classList.remove('active');
        currentImageIndex += direction;

        if (currentImageIndex < 0) {
            currentImageIndex = totalImages - 1; // Corrected line
        } else if (currentImageIndex >= totalImages) {
            currentImageIndex = 0;
        }

        images[currentImageIndex].classList.add('active');
        thumbnails[currentImageIndex].classList.add('active');
    }

    function showImage(index) {
        images[currentImageIndex].classList.remove('active');
        thumbnails[currentImageIndex].classList.remove('active');
        currentImageIndex = index;
        images[currentImageIndex].classList.add('active');
        thumbnails[currentImageIndex].classList.add('active');
    }

    // Timer Logic (Replace with PHP for dynamic countdown)
    function startTimer(duration, display) {
        var timer = duration, minutes, seconds;
        setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + " minutes " + seconds + " seconds";

            if (--timer < 0) {
                timer = duration; // Reset timer if needed
            }
        }, 1000);
    }

    window.onload = function () {
        var oneHourFortyOne = 1 * 60 * 60 + 41 * 60; // 1 hour 41 minutes
        var display = document.querySelector('#timer');
        startTimer(oneHourFortyOne, display);
    };
</script>

</body>
</html>