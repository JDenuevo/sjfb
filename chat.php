<?php
session_start();
include 'conn.php';


?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<!-- End Google Tag Manager -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Details | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- [Keep all your existing head content] -->
  <style>
    .dot-typing::after {
      content: '...';
      animation: dots 1.2s steps(4, end) infinite;
    }
    @keyframes dots {
      0%, 20% { content: ''; }
      40% { content: '.'; }
      60% { content: '..'; }
      80%, 100% { content: '...'; }
    }
  </style>
</head>
<body>
<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
<section id="details-section">
    <?php include('./components/navigation.php'); ?>

    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
      <div class="max-w-xl mx-auto" data-aos="fade-up" data-aos-duration="1500">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Welcome Ka-Sariwa!</h1>
            <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-4 mb-8">
              <div id="chatLog" class="h-96 overflow-y-auto mb-4 space-y-4">
                  <!-- Initial bot message -->
                  <div class="flex gap-x-2 sm:gap-x-4">
                      <svg class="shrink-0 size-9.5 rounded-full" width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <rect width="38" height="38" rx="6" fill="#2563EB"/>
                          <path d="M10 28V18.64C10 13.8683 14.0294 10 19 10C23.9706 10 28 13.8683 28 18.64C28 23.4117 23.9706 27.28 19 27.28H18.25" stroke="white" stroke-width="1.5"/>
                          <path d="M13 28V18.7552C13 15.5104 15.6863 12.88 19 12.88C22.3137 12.88 25 15.5104 25 18.7552C25 22 22.3137 24.6304 19 24.6304H18.25" stroke="white" stroke-width="1.5"/>
                          <ellipse cx="19" cy="18.6554" rx="3.75" ry="3.6" fill="white"/>
                      </svg>
                      <div class="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3">
                          <h2 class="font-medium text-gray-800">How can we help?</h2>
                          <div class="space-y-1.5">
                              <p class="mb-1.5 text-sm text-gray-800">You can ask questions about our seafood products, services, or anything else!</p>
                          </div>
                      </div>
                  </div>
              </div>
              
              <div class="flex gap-2">
                  <input type="text" id="userInput" placeholder="Ask something..." 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        onkeypress="if(event.key === 'Enter') sendMessage()">
                  <button onclick="sendMessage()" 
                          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                      Send
                  </button>
              </div>
          </div>
        </div>
      </div>
    </div>
</section>
  
<?php include('./components/footer.php'); ?>

<script>
function sendMessage() {
    const userInput = document.getElementById("userInput");
    const userMessage = userInput.value.trim();
    
    if (!userMessage) return;
    
    // Add user message to chat
    addMessageToChat(userMessage, 'user');
    userInput.value = "";
    
    // Show typing indicator
    const typingId = showTypingIndicator();
    
    fetch("chatbot.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ message: userMessage }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        removeTypingIndicator(typingId);
        if (data.error) {
            addMessageToChat("Sorry, there was an error: " + data.error, 'bot');
        } else {
            addMessageToChat(data.reply || "I didn't understand that. Could you rephrase?", 'bot');
        }
    })
    .catch(error => {
        removeTypingIndicator(typingId);
        addMessageToChat("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
        console.error('Error:', error);
    });
}

function addMessageToChat(message, sender) {  
    const chatLog = document.getElementById("chatLog");
    const messageDiv = document.createElement("div");
    
    if (sender === 'user') {
        messageDiv.className = "max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4";
        messageDiv.innerHTML = `
            <div class="grow text-end space-y-3">
                <div class="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
                    <p class="text-sm text-white">${message}</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                <span class="text-sm font-medium text-white">YO</span>
            </span>
        `;
    } else {
        messageDiv.className = "flex gap-x-2 sm:gap-x-4";
        messageDiv.innerHTML = `
            <svg class="shrink-0 size-9.5 rounded-full" width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="38" height="38" rx="6" fill="#2563EB"/>
                <path d="M10 28V18.64C10 13.8683 14.0294 10 19 10C23.9706 10 28 13.8683 28 18.64C28 23.4117 23.9706 27.28 19 27.28H18.25" stroke="white" stroke-width="1.5"/>
                <path d="M13 28V18.7552C13 15.5104 15.6863 12.88 19 12.88C22.3137 12.88 25 15.5104 25 18.7552C25 22 22.3137 24.6304 19 24.6304H18.25" stroke="white" stroke-width="1.5"/>
                <ellipse cx="19" cy="18.6554" rx="3.75" ry="3.6" fill="white"/>
            </svg>
            <div class="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3">
                <p class="text-sm text-gray-800">${message}</p>
            </div>
        `;
    }
    
    chatLog.appendChild(messageDiv);
    chatLog.scrollTop = chatLog.scrollHeight;
}

function showTypingIndicator() {
    const chatLog = document.getElementById("chatLog");
    const typingDiv = document.createElement("div");
    const typingId = 'typing-' + Date.now();
    typingDiv.id = typingId;
    typingDiv.className = "flex gap-x-2 sm:gap-x-4";
    typingDiv.innerHTML = `
        <svg class="shrink-0 size-9.5 rounded-full" width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="38" height="38" rx="6" fill="#2563EB"/>
            <path d="M10 28V18.64C10 13.8683 14.0294 10 19 10C23.9706 10 28 13.8683 28 18.64C28 23.4117 23.9706 27.28 19 27.28H18.25" stroke="white" stroke-width="1.5"/>
            <path d="M13 28V18.7552C13 15.5104 15.6863 12.88 19 12.88C22.3137 12.88 25 15.5104 25 18.7552C25 22 22.3137 24.6304 19 24.6304H18.25" stroke="white" stroke-width="1.5"/>
            <ellipse cx="19" cy="18.6554" rx="3.75" ry="3.6" fill="white"/>
        </svg>
        <div class="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3">
            <p class="text-sm text-gray-500">Typing<span class="dot-typing">...</span></p>
        </div>
    `;
    chatLog.appendChild(typingDiv);
    chatLog.scrollTop = chatLog.scrollHeight;
    return typingId;
}

function removeTypingIndicator(id) {
    const typingDiv = document.getElementById(id);
    if (typingDiv) typingDiv.remove();
}
</script>



  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
  <script src="node_modules/preline/dist/preline.js"></script>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

<?php include('live_chat.php'); ?>
  
</body>
</html>