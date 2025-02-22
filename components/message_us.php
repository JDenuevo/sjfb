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
        <!-- Form -->
        <form action="https://fishbrokers.net/send_email.php" method="POST" enctype="multipart/form-data" id="contact-form">
          <div class="grid gap-4 lg:gap-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
              <div>
                <label for="firstName" class="block mb-2 text-sm text-gray-700 font-medium">First Name</label>
                <input type="text" name="firstName" placeholder="First Name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
              </div>

              <div>
                <label for="lastName" class="block mb-2 text-sm text-gray-700 font-medium">Last Name</label>
                <input type="text" name="lastName" placeholder="Last Name" class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" required>
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
              <input type="file" id="attachment" name="attachments[]" accept=".jpg,.png,.pdf" multiple class="hidden">
              <!-- Display Selected Files -->
              <div id="file-list" class="mt-4 text-sm text-gray-600"></div>
            </div>
          </div>

          <!-- Loading Spinner -->
          <div id="loading" class="hidden text-center mt-4">
            <p>Sending your inquiry..</p>
          </div>

          <!-- Success Message -->
          <div id="success" class="hidden text-center mt-4 text-green-500">
            <p>Your inquiry has been sent successfully!</p>
          </div>

          <div class="mt-6 grid">
            <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg-orange-700" aria-label="Submit the form">Send inquiry</button>
          </div>

          <div class="mt-3 text-center">
            <p class="text-sm text-gray-500 dark:text-neutral-500">We'll get back to you in 5-7 business days.</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
  const form = document.getElementById('contact-form');
  const loadingIndicator = document.getElementById('loading');
  const successMessage = document.getElementById('success');
  const fileList = document.getElementById('file-list');
  const attachmentInput = document.getElementById('attachment');

  form.addEventListener('submit', function(event) {
    event.preventDefault();

    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    successMessage.classList.add('hidden'); // Hide success message if form is resubmitted

    const formData = new FormData(form);

    fetch('https://fishbrokers.net/send_email.php', {
      method: 'POST',
      body: formData,
    })
    .then(response => response.json())
    .then(data => {
      // Handle success
      loadingIndicator.classList.add('hidden');
      successMessage.classList.remove('hidden');
    })
    .catch(error => {
      // Handle error
      loadingIndicator.classList.add('hidden');
      alert('There was an error sending your message.');
    });
  });

  attachmentInput.addEventListener('change', function() {
    const files = this.files;
    fileList.innerHTML = ''; // Clear the existing list

    if (files.length > 0) {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileItem = document.createElement('div');
        fileItem.classList.add('file-item');
        fileItem.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;

        // Create a remove button for each file
        const removeButton = document.createElement('button');
        removeButton.textContent = 'Remove';
        removeButton.classList.add('ml-2', 'text-red-500', 'text-sm');
        removeButton.addEventListener('click', function() {
          // Remove the file from the list and the input
          const dataTransfer = new DataTransfer(); // This will allow us to remove files from the input element
          for (let j = 0; j < files.length; j++) {
            if (j !== i) {
              dataTransfer.items.add(files[j]);
            }
          }
          attachmentInput.files = dataTransfer.files; // Update the input file list
          fileItem.remove(); // Remove the file item from the display list
        });

        // Append the remove button to the file item
        fileItem.appendChild(removeButton);

        // Append the file item to the list
        fileList.appendChild(fileItem);
      }
    } else {
      fileList.innerHTML = 'No files selected.';
    }
  });
</script>
