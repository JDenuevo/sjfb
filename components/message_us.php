<!-- HTML + TailwindCSS + JavaScript -->
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div class="max-w-xl mx-auto">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl">Ready to be with us?</h1>
        <p class="mt-1 text-gray-600 dark:text-neutral-400">
          Tell us your story and we'll be in touch.
        </p>
      </div>

      <div class="mt-12">
        <!-- Form - Note: Removed action attribute, using JavaScript -->
        <form id="contact-form" method="POST" enctype="multipart/form-data">
          <!-- CSRF Token -->
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
          
          <!-- Honeypot Field -->
          <div style="display: none; opacity: 0; position: absolute; left: -9999px;">
            <label for="website">Website</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
          </div>
          
          <div class="grid gap-4 lg:gap-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
              <div>
                <label for="firstName" class="block mb-2 text-sm text-gray-700 font-medium">First Name</label>
                <input type="text" name="firstName" id="firstName" placeholder="First Name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
              </div>
  
              <div>
                <label for="lastName" class="block mb-2 text-sm text-gray-700 font-medium">Last Name</label>
                <input type="text" name="lastName" id="lastName" placeholder="Last Name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
              <div>
                <label for="email" class="block mb-2 text-sm text-gray-700 font-medium">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" autocomplete="email" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
              </div>

              <div>
                <label for="contact" class="block mb-2 text-sm text-gray-700 font-medium">Contact Number</label>
                <input type="tel" name="contact" id="contact" placeholder="Contact Number" pattern="\+?[0-9]{10,15}" title="Enter a valid phone number" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
              </div>
            </div>

            <div>
              <label for="subject" class="block mb-2 text-sm text-gray-700 font-medium">Subject</label>
              <input type="text" name="subject" id="subject" placeholder="Enter Subject" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
            </div>

            <div>
              <label for="message" class="block mb-2 text-sm text-gray-700 font-medium">Message</label>
              <textarea name="message" id="message" rows="4" placeholder="Enter your message here..." class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required></textarea>
            </div>

            <!-- Attachment Section -->
            <div class="relative">
              <label for="attachment" class="cursor-pointer flex flex-col items-center gap-4 px-6 py-4 bg-gray-100 border border-dashed border-gray-400/60 rounded-3xl hover:border-orange-300 group transition-transform duration-300 active:scale-95">
                <div class="w-12 h-12 flex justify-center items-center bg-white rounded-full shadow-sm">
                  <svg width="24px" height="24px" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#F2571B">
                    <path d="M17 17H17.01M15.6 14H18C18.9319 14 19.3978 14 19.7654 14.1522C20.2554 14.3552 20.6448 14.7446 20.8478 15.2346C21 15.6022 21 16.0681 21 17C21 17.9319 21 18.3978 20.8478 18.7654C20.6448 19.2554 20.2554 19.6448 19.7654 19.8478C19.3978 20 18.9319 20 18 20H6C5.06812 20 4.60218 20 4.23463 19.8478C3.74458 19.6448 3.35523 19.2554 3.15224 18.7654C3 18.3978 3 17.9319 3 17C3 16.0681 3 15.6022 3.15224 15.2346C3.35523 14.7446 3.74458 14.3552 4.23463 14.1522C4.60218 14 5.06812 14 6 14H8.4M12 15V4M12 4L15 7M12 4L9 7" stroke="#F2571B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <span class="block text-base font-semibold text-gray-900 group-hover:text-orange-500">Upload a file</span>
                <span class="mt-1 block text-sm text-gray-500">Supported: JPG, PNG, PDF. Max size: 2MB</span>
              </label>
              <input type="file" id="attachment" name="attachments[]" accept=".jpg,.jpeg,.png,.pdf" multiple class="hidden">
              <div id="file-list" class="mt-4 text-sm text-gray-600"></div>
            </div>
          </div>

          <!-- Status Messages -->
          <div id="loading" class="hidden text-center mt-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-600 border-t-transparent"></div>
            <p class="mt-2 text-gray-600">Sending your inquiry...</p>
          </div>

          <div id="success" class="hidden text-center mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <p>Your inquiry has been sent successfully!</p>
          </div>

          <div id="error" class="hidden text-center mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <p id="error-message"></p>
          </div>

          <div class="mt-6 grid">
            <button type="submit" id="submit-btn" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
              Send inquiry
            </button>
          </div>

          <div class="mt-3 text-center">
            <p class="text-sm text-gray-500">We'll get back to you in 5-7 business days.</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const loadingIndicator = document.getElementById('loading');
    const successMessage = document.getElementById('success');
    const errorMessage = document.getElementById('error');
    const errorMessageText = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');
    const fileList = document.getElementById('file-list');
    const attachmentInput = document.getElementById('attachment');

    // File input change handler
    attachmentInput.addEventListener('change', function() {
        const files = this.files;
        fileList.innerHTML = '';
        
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileItem = document.createElement('div');
                fileItem.className = 'flex justify-between items-center p-2 bg-gray-50 rounded-lg mb-2';
                fileItem.innerHTML = `
                    <span class="text-sm">${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>
                    <button type="button" class="text-red-500 hover:text-red-700 text-sm font-medium" data-index="${i}">Remove</button>
                `;
                fileList.appendChild(fileItem);
            }
            
            // Add remove functionality
            fileList.querySelectorAll('button').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const index = this.dataset.index;
                    const dt = new DataTransfer();
                    const files = attachmentInput.files;
                    
                    for (let i = 0; i < files.length; i++) {
                        if (i != index) {
                            dt.items.add(files[i]);
                        }
                    }
                    
                    attachmentInput.files = dt.files;
                    this.closest('.flex').remove();
                });
            });
        } else {
            fileList.innerHTML = '<p class="text-sm text-gray-500">No files selected.</p>';
        }
    });

    // Form submit handler
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Hide all messages
        loadingIndicator.classList.remove('hidden');
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');
        submitBtn.disabled = true;

        const formData = new FormData(form);

        try {
            const scriptPath = './functions/send_email.php'; // Relative path
            const response = await fetch(scriptPath, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            loadingIndicator.classList.add('hidden');
            submitBtn.disabled = false;

            if (data.status === 'success') {
                successMessage.classList.remove('hidden');
                form.reset();
                fileList.innerHTML = '<p class="text-sm text-gray-500">No files selected.</p>';
                
                // Hide success message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                }, 5000);
            } else {
                errorMessageText.textContent = data.message || 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            loadingIndicator.classList.add('hidden');
            submitBtn.disabled = false;
            errorMessageText.textContent = 'Connection error. Please check if the server is accessible. Error: ' + error.message;
            errorMessage.classList.remove('hidden');
        }
    });
});
</script>