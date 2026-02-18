<!-- Hire Us - Buyer/Producer Form -->
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
  <!-- Grid -->
  <div class="grid md:grid-cols-2 items-center gap-12">
    <div>
      <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl lg:text-5xl lg:leading-tight">
        Are you a fish <span class="text-orange-600">Buyer</span> or <span class="text-orange-600">Producer</span>
      </h1>
      <p class="mt-1 md:text-lg text-gray-800">
        We help fish buyers and producers trade fresh seafood through a reliable fish brokerage platform with nationwide reach.
      </p>

      <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-800">
          What can you expect?
        </h2>

        <ul class="mt-2 space-y-2">
          <li class="flex gap-x-3">
            <svg class="shrink-0 mt-0.5 size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="text-gray-600">
              Nationwide network of buyers and producers such as Navotas, Malabon, Davao, and Lucena.
            </span>
          </li>
          <li class="flex gap-x-3">
            <svg class="shrink-0 mt-0.5 size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="text-gray-600">
              Freshly sourced seafood you can trust
            </span>
          </li>
          <li class="flex gap-x-3">
            <svg class="shrink-0 mt-0.5 size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="text-gray-600">
              Transparent, efficient, and documented transactions
            </span>
          </li>
        </ul>
      </div>

      <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-800">
          Trusted by:
        </h2>
        <div class="mt-2 flex items-center gap-x-5">
          <div class="flex -space-x-2">
            <img class="inline-block size-8 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2&w=320&h=320&q=80" alt="Avatar">
            <img class="inline-block size-8 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1531927557220-a9e23c1e4794?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=2.5&w=320&h=320&q=80" alt="Avatar">
            <img class="inline-block size-8 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1541101767792-f9b2b1c4f127?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=facearea&facepad=3&w=320&h=320&q=80" alt="Avatar">
            <span class="inline-flex justify-center items-center size-8 rounded-full bg-blue-600 text-white ring-2 ring-white">
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
              </svg>
            </span>
          </div>
          <span class="text-sm text-gray-500">
            Trusted by over 37k customers
          </span>
        </div>
      </div>
    </div>

    <div class="relative">
      <!-- Card -->
      <div class="flex flex-col border border-gray-200 rounded-xl p-4 sm:p-6 lg:p-10">
        <h2 class="text-xl font-semibold text-gray-800">
          Fill in the form
        </h2>

        <form id="buyerProducerForm">
          <!-- CSRF Token -->
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token_bp'] ?? ''; ?>">
          
          <!-- Honeypot - Hidden from real users -->
          <input type="text" name="website" id="website" style="display:none;" tabindex="-1" autocomplete="off">

          <div class="mt-6 grid gap-2 lg:gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 lg:gap-4">
              <div>
                <label for="fullName" class="block mb-2 text-sm text-gray-700 font-medium">Full name</label>
                <input type="text" name="fullName" id="fullName" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Enter your fullname" required>
              </div>

              <div>
                <label for="type" class="block mb-2 text-sm text-gray-700 font-medium">Type</label>
                <select name="type" id="type" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
                  <option disabled selected value="">Select here</option>
                  <option value="Producer">Producer</option>
                  <option value="Buyer">Buyer</option>
                </select>              
              </div>
            </div>

            <div>
              <label for="email" class="block mb-2 text-sm text-gray-700 font-medium">Email Address</label>
              <input type="email" name="email" id="email" autocomplete="email" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Enter your email" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 lg:gap-4">
              <div>
                <label for="contactNumber" class="block mb-2 text-sm text-gray-700 font-medium">Contact Number</label>
                <input type="text" name="contactNumber" id="contactNumber" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Enter your number" required>
              </div>

              <div>
                <label for="location" class="block mb-2 text-sm text-gray-700 font-medium">Location/Origin</label>
                <input type="text" name="location" id="location" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Enter your location/origin" required>
              </div>
            </div>

            <div>
              <label for="details" class="block mb-2 text-sm text-gray-700 font-medium">Details</label>
              <textarea id="details" name="details" rows="4" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Tell us about your requirements, volume, preferred fish types, etc..." required></textarea>
            </div>
          </div>

          <div class="mt-6 grid">
            <button type="submit" id="submitBtn" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">
              <span id="buttonText">Send inquiry</span>
              <span id="buttonSpinner" style="display: none;" class="animate-spin inline-block size-4 border-2 border-white border-t-transparent rounded-full"></span>
            </button>
          </div>

          <div class="mt-3 text-center">
            <p class="text-sm text-gray-500">
              We'll get back to you in 3-5 business days.
            </p>
          </div>
        </form>

        <!-- Success/Error Message -->
        <div id="formMessage" class="mt-4" style="display: none;"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('buyerProducerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const buttonText = document.getElementById('buttonText');
    const buttonSpinner = document.getElementById('buttonSpinner');
    const formMessage = document.getElementById('formMessage');
    
    submitBtn.disabled = true;
    buttonText.style.display = 'none';
    buttonSpinner.style.display = 'inline-block';
    formMessage.style.display = 'none';
    
    // Get form data
    const formData = new FormData(this);
    
    // Send AJAX request
    fetch('/functions/approach.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message
            formMessage.className = 'mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg';
            formMessage.innerHTML = '<strong>Success!</strong> ' + data.message + 
                (data.inquiry_code ? '<br>Your inquiry code: <span class="font-bold">' + data.inquiry_code + '</span>' : '');
            formMessage.style.display = 'block';
            
            // Reset form
            document.getElementById('buyerProducerForm').reset();
            
            // Scroll to message
            formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            // Show error message
            formMessage.className = 'mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg';
            formMessage.innerHTML = '<strong>Error!</strong> ' + data.message;
            formMessage.style.display = 'block';
        }
    })
    .catch(error => {
        // Show error message
        formMessage.className = 'mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg';
        formMessage.innerHTML = '<strong>Error!</strong> An unexpected error occurred. Please try again.';
        formMessage.style.display = 'block';
        console.error('Error:', error);
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        buttonText.style.display = 'inline';
        buttonSpinner.style.display = 'none';
    });
});
</script>