<?php
session_start();
require_once '../conn.php';

if (!isset($_SESSION['account_id'])) {
    header("Location: ../register.php");
    exit();
}

$accountId = intval($_SESSION['account_id']);
$stmt = $conn->prepare("
    SELECT username, account_email, email_verified, profile_completed,
           account_first_name, account_last_name, account_phone,
           account_address, city, postal_code
    FROM accounts WHERE account_id = ? LIMIT 1
");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    header("Location: ../register.php");
    exit();
}

if (!$account['email_verified']) {
    header("Location: ../verify_pending.php");
    exit();
}

if ($account['profile_completed']) {
    header("Location: home.php");
    exit();
}

$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Complete Your Profile — St. Joseph Fish Brokerage Inc.</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="../assets/icons/logo.ico" type="image/x-icon">
<link rel="shortcut icon"             href="../assets/icons/logo.ico">
<link rel="icon" type="image/x-icon"  href="../assets/icons/logo.ico" sizes="16x16 32x32">
<link rel="icon" type="image/svg+xml" href="../assets/icons/logo.svg">
<link rel="apple-touch-icon"          href="../assets/icons/logo.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
<link href="../style.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<script>window.CART_BASE = '';</script>
<script src="./functions/cart_process.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-10">

    <div class="max-w-lg w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

        <div class="text-center mb-6">
            <div class="mx-auto w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-[#E85D20]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Complete your profile</h1>
            <p class="text-sm text-gray-500 mt-1">Just a few more details before you can start ordering.</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2 mb-5">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="../functions/add.php" method="POST" class="space-y-4">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="first_name" required
                           value="<?= htmlspecialchars($account['account_first_name'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="last_name" required
                           value="<?= htmlspecialchars($account['account_last_name'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="phone" placeholder="e.g. 09171234567"
                       value="<?= htmlspecialchars($account['account_phone'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" required placeholder="House/Unit No., Street, Barangay"
                       value="<?= htmlspecialchars($account['account_address'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" required
                           value="<?= htmlspecialchars($account['city'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                    <input type="text" name="postal_code" required
                           value="<?= htmlspecialchars($account['postal_code'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#E85D20] focus:border-[#E85D20] outline-none">
                </div>
            </div>

            <button type="submit" name="complete_profile"
                    class="w-full bg-[#E85D20] text-white text-sm font-semibold rounded-lg py-2.5 hover:bg-orange-600 transition mt-2">
                Continue to My Account
            </button>

        </form>

    </div>
<script src="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
</body>
</html>