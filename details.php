<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['account_id'])) {
  $_SESSION['error'] = "Unauthorized access!";
  header("Location: register.php");
  exit();
}

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
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://fishbrokers.net/">
  <meta property="og:title" content="St. Joseph Fish Brokerage Inc.">
  <meta property="og:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta property="og:image" content="https://fishbrokers.net/assets/icons/logo.svg"> 
  <meta name="google-site-verification" content="SEvyztm_VEss7pZNU7eN79PfVCh0D6MskG7f9mKpJow" />
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="St. Joseph Fish Brokerage Inc.">
  <meta name="twitter:description" content="Professional fish brokerage services with excellence and integrity.">
  <meta name="twitter:image" content="https://fishbrokers.net/assets/icons/logo.svg">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">
    
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">

  <link rel="stylesheet" href="https://preline.co/assets/css/main.css?v=3.0.1">
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
</head>
<style>
  .error-border {
    border-color: #ef4444 !important;
  }

  .success-border {
    border-color: #10b981 !important;
  }

  .requirement-list {
    margin-top: 0.5rem;
    padding-left: 1rem;
    list-style-type: none;
  }

  .requirement-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
  }

  .requirement-item.valid {
    color: #10b981;
  }

  .requirement-icon {
    margin-right: 0.5rem;
    width: 1rem;
    height: 1rem;
  }

  .requirement-icon.valid {
    color: #10b981;
  }
</style>
<body>
<?php include('./components/preloader.php'); ?>

<!-- Hero Section -->
<section id="details-section">
    
    <?php include('./components/navigation.php'); ?>

    <!-- HTML + TailwindCSS + JavaScript -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
      <div class="max-w-xl mx-auto" data-aos="fade-up" data-aos-duration="1500">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Personal and Billing Address Details</h1>
        <p class="mt-1 text-gray-600 dark:text-neutral-400">
          Put your details for your shipping adrress and contact.
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
          <form action="./functions/update.php" method="POST" id="detailsForm">
            <div class="grid gap-y-4">

              <div class="grid grid-cols-2 gap-4">
                <!-- Form Group -->
                <div>
                  <label for="first_name" class="block text-sm mb-2 text-dark">First name</label>
                  <div class="relative">
                    <input type="text" id="first_name" name="first_name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                  </div>
                  <p class="hidden text-xs text-red-600 mt-2" id="first_name-error">Please enter your first name.</p>
                </div>
                <!-- End Form Group -->
           
                <!-- Form Group -->
                <div>
                  <label for="last_name" class="block text-sm mb-2 text-dark">Last name</label>
                  <div class="relative">
                    <input type="text" id="last_name" name="last_name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                  </div>
                  <p class="hidden text-xs text-red-600 mt-2" id="last_name-error">Please enter your last name.</p>
                </div>
                <!-- End Form Group -->
              </div>
              
              <!-- Form Group -->
              <div>
                <label for="phone_number" class="block text-sm mb-2 text-dark">Phone Number</label>
                <div class="relative">
                  <input type="text" id="phone_number" name="phone_number" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                </div>
                <ul class="requirement-list" id="phone-requirements">
                  <li class="requirement-item" id="phone-format">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Valid phone number format (e.g., 09123456789)
                  </li>
                  <li class="requirement-item" id="phone-length">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 11 digits
                  </li>
                </ul>
                <p class="hidden text-xs text-red-600 mt-2" id="phone_number-error">Please enter a valid phone number (digits only, at least 11 characters).</p>
              </div>
              <!-- End Form Group -->

              <!-- Form Group -->
              <div>
                <label for="address" class="block text-sm mb-2 text-dark">Address</label>
                <div class="relative">
                  <input type="text" id="address" name="address" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                </div>
                <ul class="requirement-list" id="address-requirements">
                  <li class="requirement-item" id="address-length">
                    <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    At least 10 characters
                  </li>
                </ul>
                <p class="hidden text-xs text-red-600 mt-2" id="address-error">Please enter a valid address (at least 10 characters).</p>
              </div>
              <!-- End Form Group -->

              <div class="grid grid-cols-2 gap-4">
                <!-- Form Group -->
                <div>
                  <label for="city" class="block text-sm mb-2 text-dark">City</label>
                  <div class="relative">
                    <input type="text" id="city" name="city" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                  </div>
                  <p class="hidden text-xs text-red-600 mt-2" id="city-error">Please enter your city.</p>
                </div>
                <!-- End Form Group -->

                <!-- Form Group -->
                <div>
                  <label for="postal_code" class="block text-sm mb-2 text-dark">Postal Code</label>
                  <div class="relative">
                    <input type="text" id="postal_code" name="postal_code" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none" required>
                  </div>
                  <ul class="requirement-list" id="postal-requirements">
                    <li class="requirement-item" id="postal-format">
                      <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      Must be a valid postal code (digits only)
                    </li>
                    <li class="requirement-item" id="postal-length">
                      <svg class="requirement-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      Must be 4 digits
                    </li>
                  </ul>
                  <p class="hidden text-xs text-red-600 mt-2" id="postal_code-error">Please enter a valid 4-digit postal code.</p>
                </div>
                <!-- End Form Group -->
              </div>

              <button type="submit" name="update_new_account" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">Confirm details</button>
            </div>
          </form>
          <!-- End Form -->
        </div>
      </div>
    </div>

  </section>
  
  <?php include('./components/footer.php'); ?>

  <script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.getElementById("detailsForm");
      const firstNameInput = document.getElementById("first_name");
      const lastNameInput = document.getElementById("last_name");
      const phoneInput = document.getElementById("phone_number");
      const addressInput = document.getElementById("address");
      const cityInput = document.getElementById("city");
      const postalInput = document.getElementById("postal_code");

      // Requirement elements
      const phoneFormatReq = document.getElementById("phone-format");
      const phoneLengthReq = document.getElementById("phone-length");
      const addressLengthReq = document.getElementById("address-length");
      const postalFormatReq = document.getElementById("postal-format");
      const postalLengthReq = document.getElementById("postal-length");

      function validateFirstName() {
        const value = firstNameInput.value.trim();
        if (value === "") {
          firstNameInput.classList.add("error-border");
          firstNameInput.classList.remove("success-border");
          document.getElementById("first_name-error").classList.remove("hidden");
          return false;
        } else {
          firstNameInput.classList.remove("error-border");
          firstNameInput.classList.add("success-border");
          document.getElementById("first_name-error").classList.add("hidden");
          return true;
        }
      }

      function validateLastName() {
        const value = lastNameInput.value.trim();
        if (value === "") {
          lastNameInput.classList.add("error-border");
          lastNameInput.classList.remove("success-border");
          document.getElementById("last_name-error").classList.remove("hidden");
          return false;
        } else {
          lastNameInput.classList.remove("error-border");
          lastNameInput.classList.add("success-border");
          document.getElementById("last_name-error").classList.add("hidden");
          return true;
        }
      }

      function validatePhone() {
        const value = phoneInput.value.trim();
        const phoneRegex = /^[0-9]{11,}$/;
        let isValid = true;

        // Check phone format
        if (/^[0-9]+$/.test(value)) {
          phoneFormatReq.classList.add("valid");
          phoneFormatReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          phoneFormatReq.classList.remove("valid");
          phoneFormatReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check phone length
        if (value.length >= 11) {
          phoneLengthReq.classList.add("valid");
          phoneLengthReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          phoneLengthReq.classList.remove("valid");
          phoneLengthReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        if (!phoneRegex.test(value)) {
          phoneInput.classList.add("error-border");
          phoneInput.classList.remove("success-border");
          document.getElementById("phone_number-error").classList.remove("hidden");
          return false;
        } else {
          phoneInput.classList.remove("error-border");
          phoneInput.classList.add("success-border");
          document.getElementById("phone_number-error").classList.add("hidden");
          return true;
        }
      }

      function validateAddress() {
        const value = addressInput.value.trim();
        
        // Check address length
        if (value.length >= 10) {
          addressLengthReq.classList.add("valid");
          addressLengthReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          addressLengthReq.classList.remove("valid");
          addressLengthReq.querySelector('.requirement-icon').classList.remove('valid');
        }

        if (value.length < 10) {
          addressInput.classList.add("error-border");
          addressInput.classList.remove("success-border");
          document.getElementById("address-error").classList.remove("hidden");
          return false;
        } else {
          addressInput.classList.remove("error-border");
          addressInput.classList.add("success-border");
          document.getElementById("address-error").classList.add("hidden");
          return true;
        }
      }

      function validateCity() {
        const value = cityInput.value.trim();
        if (value === "") {
          cityInput.classList.add("error-border");
          cityInput.classList.remove("success-border");
          document.getElementById("city-error").classList.remove("hidden");
          return false;
        } else {
          cityInput.classList.remove("error-border");
          cityInput.classList.add("success-border");
          document.getElementById("city-error").classList.add("hidden");
          return true;
        }
      }

      function validatePostal() {
        const value = postalInput.value.trim();
        const postalRegex = /^[0-9]{4}$/;
        let isValid = true;

        // Check postal format
        if (/^[0-9]+$/.test(value)) {
          postalFormatReq.classList.add("valid");
          postalFormatReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          postalFormatReq.classList.remove("valid");
          postalFormatReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        // Check postal length
        if (value.length === 4) {
          postalLengthReq.classList.add("valid");
          postalLengthReq.querySelector('.requirement-icon').classList.add('valid');
        } else {
          postalLengthReq.classList.remove("valid");
          postalLengthReq.querySelector('.requirement-icon').classList.remove('valid');
          isValid = false;
        }

        if (!postalRegex.test(value)) {
          postalInput.classList.add("error-border");
          postalInput.classList.remove("success-border");
          document.getElementById("postal_code-error").classList.remove("hidden");
          return false;
        } else {
          postalInput.classList.remove("error-border");
          postalInput.classList.add("success-border");
          document.getElementById("postal_code-error").classList.add("hidden");
          return true;
        }
      }

      // Add event listeners
      firstNameInput.addEventListener("input", validateFirstName);
      lastNameInput.addEventListener("input", validateLastName);
      phoneInput.addEventListener("input", validatePhone);
      addressInput.addEventListener("input", validateAddress);
      cityInput.addEventListener("input", validateCity);
      postalInput.addEventListener("input", validatePostal);

      // Form submission validation
      form.addEventListener("submit", function(event) {
        let isValid = true;
        
        if (!validateFirstName()) isValid = false;
        if (!validateLastName()) isValid = false;
        if (!validatePhone()) isValid = false;
        if (!validateAddress()) isValid = false;
        if (!validateCity()) isValid = false;
        if (!validatePostal()) isValid = false;
        
        if (!isValid) {
          event.preventDefault();
          // Scroll to the first error
          const firstError = document.querySelector(".error-border");
          if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      });
    });

    $(document).ready(function() {
      <?php if (isset($_SESSION['hs-modal-signin'])): ?>
        $("#signinModal").modal("show"); // Open the modal
        <?php unset($_SESSION['hs-modal-signin']); // Remove flag after use ?>
      <?php endif; ?>
    });
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