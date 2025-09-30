<div class="max-w-5xl px-4 py-10 sm:px-6 lg:px-8 mx-auto">
  <!-- Card -->
  <div class="bg-white rounded-xl shadow-xs p-8 sm:p-7">
    <div class="mb-8">
      <h2 class="text-xl font-bold text-gray-800 ">
        Profile
      </h2>
      <p class="text-sm text-gray-600 ">
        Manage your name, password and account settings.
      </p>
    </div>

    <form action="./functions/update.php" method="POST">
      <!-- Grid -->
      <div class="grid sm:grid-cols-12 gap-2 sm:gap-6">
        
        <div class="sm:col-span-3">
          <label for="af-account-username" class="inline-block text-sm text-gray-800 mt-2.5 ">
            Username
          </label>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <input id="af-account-email" type="text" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Username">
        </div>
        <!-- End Col -->

        
        <div class="sm:col-span-3">
          <label for="af-account-password" class="inline-block text-sm text-gray-800 mt-2.5 ">
            Password
          </label>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="space-y-2">
            <input id="af-account-password" type="password" name="password" id="password" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs rounded-lg sm:text-sm focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Leave blank to keep current password">
            <input type="password" name="confirm_password" id="confirm_password" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs rounded-lg sm:text-sm focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Confirm password">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <label for="af-account-full-name" class="inline-block text-sm text-gray-800 mt-2.5 ">
            Full name
          </label>
          
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input id="af-account-full-name" type="text" name="first_name" value="<?php echo htmlspecialchars($row['first_name']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none " placeholder="First name">
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($row['last_name']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none " placeholder="Last name">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <label for="af-account-email" class="inline-block text-sm text-gray-800 mt-2.5 ">
            Email
          </label>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <input id="af-account-email" type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs sm:text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Email">
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              Phone
            </label>
            <!-- <span class="text-sm text-gray-400 ">
              (Optional)
            </span> -->
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input id="af-account-phone" type="number" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="+63-xxx-xxx-xx">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <label for="af-account-bio" class="inline-block text-sm text-gray-800 mt-2.5 ">
            Address
          </label>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <textarea id="af-account-bio" name="address" class=" py-2 px-3  block w-full border border-gray-300 rounded-lg sm:text-sm focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none " rows="3" placeholder="Enter your full address"><?php echo htmlspecialchars($row['address']); ?></textarea>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              City
            </label>
            <!-- <span class="text-sm text-gray-400 ">
              (Optional)
            </span> -->
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input id="af-account-phone" type="text" name="city" value="<?php echo htmlspecialchars($row['city']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="City">          
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              Postal Code
            </label>
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input id="af-account-postal" type="number" name="postal_code" value="<?php echo htmlspecialchars($row['postal_code']); ?>" class=" py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Postal Code">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              Vehicle Type
            </label>
            <!-- <span class="text-sm text-gray-400 ">
              (Optional)
            </span> -->
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <select name="vehicle_type_display" id="vehicle_type" class="py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Vehicle Type" disabled>
              <option value="motorcycle" <?php echo ($row['vehicle_type'] === 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
              <option value="bicycle" <?php echo ($row['vehicle_type'] === 'bicycle') ? 'selected' : ''; ?>>Bicycle</option>
              <option value="car" <?php echo ($row['vehicle_type'] === 'car') ? 'selected' : ''; ?>>Car</option>
              <option value="truck" <?php echo ($row['vehicle_type'] === 'truck') ? 'selected' : ''; ?>>Truck</option>
            </select>
            <!-- Hidden field to actually submit -->
            <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($row['vehicle_type']); ?>">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              Vehicle Plate Number
            </label>
            <!-- <span class="text-sm text-gray-400 ">
              (Optional)
            </span> -->
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input type="text" name="vehicle_plate_number_display" value="<?php echo htmlspecialchars($row['vehicle_plate_number']); ?>" class="py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="Vehicle Plate Number" disabled>
            <!-- Hidden field to actually submit -->
            <input type="hidden" name="vehicle_plate_number" value="<?php echo htmlspecialchars($row['vehicle_plate_number']); ?>">
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-3">
          <div class="inline-block">
            <label for="af-account-phone" class="inline-block text-sm text-gray-800 mt-2.5 ">
              License Number
            </label>
            <!-- <span class="text-sm text-gray-400 ">
              (Optional)
            </span> -->
          </div>
        </div>
        <!-- End Col -->

        <div class="sm:col-span-9">
          <div class="sm:flex">
            <input type="text" name="license_number_display" id="license_number" value="<?php echo htmlspecialchars($row['license_number']); ?>" class="py-2 px-3  pe-11 block w-full border border-gray-300 shadow-2xs -mt-px -ms-px first:rounded-t-lg last:rounded-b-lg sm:first:rounded-s-lg sm:mt-0 sm:first:ms-0 sm:first:rounded-se-none sm:last:rounded-es-none sm:last:rounded-e-lg sm:text-sm relative focus:z-10 focus:border-orange-500 focus:ring-orange-500 checked:border-orange-500 disabled:opacity-50 disabled:pointer-events-none" placeholder="License Number" disabled>
            <!-- Hidden field to actually submit -->
            <input type="hidden" name="license_number" value="<?php echo htmlspecialchars($row['license_number']); ?>">
          </div>
        </div>
      </div>
      <!-- End Grid -->

      <div class="mt-5 flex justify-end gap-x-2">
        <button type="submit" name="update_profile" class="py-2 px-3  inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-orange-600 text-white hover:bg--700 focus:outline-hidden focus:bg-orange-700 disabled:opacity-50 disabled:pointer-events-none">
          Save changes
        </button>
      </div>
    </form>
  </div>
  <!-- End Card -->
</div>
<!-- End Card Section -->
