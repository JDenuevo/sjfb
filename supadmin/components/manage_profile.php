<div class="max-w-4xl px-4 py-10 sm:px-6 lg:px-8 mx-auto">
  
  <?php if (!empty($_SESSION['message'])): 
    $message = $_SESSION['message'];
    $alertClass = ($message['type'] === 'success') ? 'alert-success' : 'alert-error';
    unset($_SESSION['message']);
  ?>
  <div class="alert <?= $alertClass ?> mb-6" role="alert">
    <span class="font-bold"><?= ucfirst($message['type']) ?>!</span> <?= htmlspecialchars($message['text']) ?>
  </div>
  <?php endif; ?>

  <!-- Profile Card -->
  <div class="profile-card">
    
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-900">Profile Settings</h2>
          <p class="text-sm text-gray-500 mt-0.5">Manage your account information and security</p>
        </div>
      </div>
    </div>

    <div class="p-6">
      <form action="./functions/update.php" method="POST">
        
        <!-- Account Information Section -->
        <div class="mb-8">
          <h3 class="section-title mb-4">Account Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Username -->
            <div>
              <label class="form-label">Username</label>
              <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" 
                     class="form-input" placeholder="Username">
            </div>
            
            <!-- Role (Read-only) -->
            <div>
              <label class="form-label">Role</label>
              <input type="text" value="<?= ucfirst(str_replace('_',' ', $row['role'])) ?>" 
                     class="form-input" readonly disabled>
            </div>
          </div>
        </div>

        <!-- Password Change Section -->
        <div class="mb-8">
          <h3 class="section-title mb-4">Change Password</h3>
          <p class="text-xs text-gray-400 mb-3">Leave blank to keep current password</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">New Password</label>
              <input type="password" name="password" class="form-input" placeholder="••••••••">
            </div>
            <div>
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-input" placeholder="••••••••">
            </div>
          </div>
        </div>

        <!-- Personal Information Section -->
        <div class="mb-8">
          <h3 class="section-title mb-4">Personal Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" value="<?= htmlspecialchars($row['first_name']) ?>" 
                     class="form-input" placeholder="First name">
            </div>
            <div>
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" value="<?= htmlspecialchars($row['last_name']) ?>" 
                     class="form-input" placeholder="Last name">
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="form-label">Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" 
                     class="form-input" placeholder="Email">
            </div>
            <div>
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone_number" value="<?= htmlspecialchars($row['phone_number']) ?>" 
                     class="form-input" placeholder="+63 xxx xxx xxxx">
            </div>
          </div>
        </div>

        <!-- Address Information Section -->
        <div class="mb-8">
          <h3 class="section-title mb-4">Address Information</h3>
          
          <div class="space-y-4">
            <div>
              <label class="form-label">Address</label>
              <textarea name="address" rows="3" class="form-input resize-none" 
                        placeholder="Enter your full address"><?= htmlspecialchars($row['address']) ?></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($row['city']) ?>" 
                       class="form-input" placeholder="City">
              </div>
              <div>
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" value="<?= htmlspecialchars($row['postal_code']) ?>" 
                       class="form-input" placeholder="Postal Code">
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
          <button type="reset" class="btn-secondary">
            Reset
          </button>
          <button type="submit" name="update_profile" class="btn-primary">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Account Info Card -->
  <div class="profile-card mt-6">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><circle cx="12" cy="8" r="0.5" fill="currentColor"/>
        </svg>
        Account Details
      </h3>
    </div>
    
    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Account ID -->
      <div class="stat-card">
        <div class="text-xs text-gray-500 mb-1">Account ID</div>
        <div class="text-sm font-mono font-semibold text-gray-900">#<?= $row['account_id'] ?></div>
      </div>
      
      <!-- Account Created -->
      <div class="stat-card">
        <div class="text-xs text-gray-500 mb-1">Member Since</div>
        <div class="text-sm font-semibold text-gray-900"><?= date('F j, Y', strtotime($row['created_at'])) ?></div>
      </div>

    </div>
  </div>
</div>