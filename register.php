<?php
session_start();
include 'conn.php';

// Retrieve the logged-in account_id
$account_id = $_SESSION['account_id'];

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
  <title>Register | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<body>
<?php include('./components/preloader.php'); ?>

<!-- Register Section -->
<section id="register-section">
    
    <?php include('./components/navigation.php'); ?>

    <!-- HTML + TailwindCSS + JavaScript -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
      <div class="max-w-xl mx-auto" data-aos="fade-up" data-aos-duration="1500">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Ready to be with us?</h1>
        <p class="mt-1 text-gray-600 dark:text-neutral-400">
          You can directly message us on Facebook and Messenger
        </p>
      </div>
        <div class="mt-5">  
          <?php
          if (!empty($_SESSION['success']) || !empty($_SESSION['error'])) {
              $messageText = !empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error'];
              $messageType = !empty($_SESSION['success']) ? 'success' : 'error';
              $alertType = ($messageType === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';

              echo '
              <div class="mt-2 ' . htmlspecialchars($alertType) . ' text-sm rounded-lg p-4 text-center" role="alert">
                  <span class="font-bold">' . ucfirst(htmlspecialchars($messageType)) . '!</span> ' . htmlspecialchars($messageText) . '
              </div>';

              // Unset messages after displaying
              unset($_SESSION['success'], $_SESSION['error']);
          }
          ?>
          <!-- Form -->
          <form action="./functions/add.php" method="POST">
            <div class="grid gap-y-4">
              <!-- Form Group -->
              <div>
                <label for="email" class="block text-sm mb-2 text-dark">Email address</label>
                <div class="relative">
                  <input type="email" id="email" name="email" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="email-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="username" class="block text-sm mb-2 text-dark">Username</label>
                <div class="relative">
                  <input type="username" id="username" name="username" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="username-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="username-error">Enter a valid name no special characters</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="password" class="block text-sm mb-2 text-dark">Password</label>
                <div class="relative">
                  <input type="password" id="password" name="password" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="password-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="password-error">8+ characters required</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="confirm-password" class="block text-sm mb-2 text-dark">Confirm Password</label>
                <div class="relative">
                  <input type="password" id="confirm_password" name="confirm_password" class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required aria-describedby="confirm-password-error">
                  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
                    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                  </div>
                </div>
                <p class="hidden text-xs text-red-600 mt-2" id="confirm-password-error">Password does not match the password</p>
              </div>
              <!-- End Form Group -->

              <button type="submit" name="register_account" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Sign up</button>
            </div>
          </form>
          <!-- End Form -->
        </div>
      </div>
    </div>

  </section>
  
  <?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
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
