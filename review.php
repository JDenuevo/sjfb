<?php
session_start();
include 'conn.php';

// This page is display-only. Submitting the form posts to process/add.php
// (its 'submit_review' branch), which validates the token, inserts the
// review + photos, and redirects back here. $submitted / $validationErrors
// below are just reading that redirect's flash data out of the session.

// ── Variables ───────────────────────────────────────────────────────────────
$orderCode       = isset($_GET['order']) ? trim($_GET['order']) : '';
$token           = isset($_GET['token']) ? trim($_GET['token']) : '';

$error           = null;
$order           = null;
$orderItems      = [];
$alreadyReviewed = false;
$pageTitle       = 'Leave a Review';

// ── Token Generator (still needed here to validate the link itself) ────────
function generateReviewToken(string $orderCode, string $email, string $salt = 'sjfbi_review_2025'): string {
    return strtoupper(substr(hash('sha256', $orderCode . $email . $salt), 0, 12));
}

// ── Flash data left behind by process/add.php's redirect ───────────────────
$submitted        = !empty($_SESSION['review_submitted']);
$validationErrors = $_SESSION['review_errors'] ?? [];
unset($_SESSION['review_submitted'], $_SESSION['review_errors']);

// ── Fetch Order & Validate Token ────────────────────────────────────────────
if (!$orderCode || !$token) {
    $error = 'invalid_link';
} else {
    $stmt = $conn->prepare("
        SELECT o.*
        FROM orders o
        WHERE o.order_code = ? AND o.order_status = 'Delivered'
        LIMIT 1
    ");
    $stmt->bind_param('s', $orderCode);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $error = 'not_delivered';
    } else {
        $expectedToken = generateReviewToken($orderCode, $order['recipient_email'] ?? '');

        if (!hash_equals($expectedToken, strtoupper($token))) {
            $error = 'invalid_token';
        } else {
            // Fetch order items with primary image
            $iStmt = $conn->prepare("
                SELECT oi.*, p.product_name, p.product_id,
                       pv.variant_name, pv.variant_price,
                       (SELECT image_path FROM product_images 
                        WHERE product_id = p.product_id AND is_primary = 1 
                        LIMIT 1) as product_image
                FROM order_items oi
                LEFT JOIN products p          ON p.product_id  = oi.product_id
                LEFT JOIN product_variants pv ON pv.variant_id = oi.variant_id
                WHERE oi.order_id = ?
            ");
            $iStmt->bind_param('i', $order['order_id']);
            $iStmt->execute();
            $orderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $iStmt->close();

            // Check if already fully reviewed
            $alreadyReviewed = !empty($orderItems) && array_reduce($orderItems, fn($carry, $item) => $carry && $item['is_reviewed'], true);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="robots" content="noindex, nofollow">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/x-icon" href="./assets/icons/logo.ico" sizes="16x16 32x32">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">
  <link rel="apple-touch-icon" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />
  <link href="https://cdn.jsdelivr.net/npm/preline/dist/preline.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            display: ['"Playfair Display"', 'serif'],
            sans: ['Lexend', 'ui-sans-serif', 'sans-serif'],
          },
          colors: {
            brand: {
              50:  '#fff7ed',
              100: '#ffedd5',
              200: '#fed7aa',
              500: '#f97316',
              600: '#ea580c',
              700: '#c2410c',
            },
          },
          keyframes: {
            dash: { to: { 'stroke-dashoffset': '0' } },
          },
          animation: {
            'dash-circle': 'dash .9s ease forwards .2s',
            'dash-tick':   'dash .4s ease forwards .9s',
          },
        },
      },
    };
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script>window.CART_BASE = '';</script>
  <script src="./functions/cart_process.js"></script>

  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2JQR66S" height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
</head>
<body class="min-h-screen m-0 font-sans bg-stone-50">

<!-- Hero -->
<div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-400 to-amber-400 px-6 pt-12 pb-20">
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_80%_10%,rgba(255,255,255,.12),transparent_55%),radial-gradient(ellipse_at_10%_90%,rgba(0,0,0,.06),transparent_45%)]"></div>
  <div class="relative z-10 max-w-xl mx-auto text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md mb-5">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round">
        <path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.46-3.44 6-7 6-3.56 0-7.56-2.54-8.5-6z"/>
        <path d="M18 12h.01M2 12c1 2 2.5 3 4 3s3-1 4-3-1.5-3-4-3-3 1-4 3z"/>
      </svg>
    </div>
    <h1 class="font-display text-3xl sm:text-4xl text-white mb-2 leading-tight">How was your order?</h1>
    <p class="text-white/80 text-sm">Your feedback helps us serve you better — and helps other buyers make great choices.</p>
    <?php if ($order): ?>
    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md border border-white/30 rounded-full px-4 py-1.5 text-white text-xs font-semibold mt-4 tracking-wider">
      <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
      Order <?= htmlspecialchars($orderCode) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Main -->
<div class="max-w-xl mx-auto px-4 -mt-10 relative z-20 mb-12">

  <?php if ($error): ?>
  <!-- ERROR -->
  <div class="bg-white rounded-3xl shadow-lg shadow-black/5 border border-gray-100 p-12 text-center">
    <?php if ($error === 'not_delivered'): ?>
      <div class="w-[72px] h-[72px] rounded-full flex items-center justify-center mx-auto mb-5 bg-yellow-100">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#ca8a04" stroke-width="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900 mb-2">Not yet delivered</h2>
      <p class="text-sm text-gray-600 mb-6">You can leave a review once your order has been delivered. We'll send you a link when it's ready!</p>
    <?php elseif ($error === 'invalid_token'): ?>
      <div class="w-[72px] h-[72px] rounded-full flex items-center justify-center mx-auto mb-5 bg-red-100">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900 mb-2">Invalid review link</h2>
      <p class="text-sm text-gray-600 mb-6">This review link is invalid or has expired. Please use the original link sent to your email or SMS.</p>
    <?php else: ?>
      <div class="w-[72px] h-[72px] rounded-full flex items-center justify-center mx-auto mb-5 bg-red-100">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900 mb-2">Link not found</h2>
      <p class="text-sm text-gray-600 mb-6">We couldn't find a valid review link. Please check the link in your email or SMS and try again.</p>
    <?php endif; ?>
    <a href="index.php" class="inline-flex items-center gap-2 bg-brand-600 text-white py-3 px-6 rounded-2xl font-bold text-sm no-underline hover:bg-brand-700 transition-colors">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Back to Homepage
    </a>
  </div>

  <?php elseif ($submitted): ?>
  <!-- SUCCESS -->
  <div class="bg-white rounded-3xl shadow-lg shadow-black/5 border border-gray-100 overflow-hidden">
    <div class="text-center py-12 px-6">
      <svg class="w-[90px] h-[90px] mx-auto mb-6" viewBox="0 0 52 52">
        <circle class="[stroke-dasharray:166] [stroke-dashoffset:166] animate-dash-circle" cx="26" cy="26" r="25" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>
        <path   class="[stroke-dasharray:48] [stroke-dashoffset:48] animate-dash-tick"   fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l8 8 16-16"/>
      </svg>
      <h2 class="font-display text-3xl text-gray-900 mb-2">Thank you, <?= htmlspecialchars($order['recipient_first_name']) ?>!</h2>
      <p class="text-sm text-gray-600 mb-6">Your review is pending approval and will appear on the product page soon. We really appreciate your time.</p>
      <div class="flex gap-3 justify-center flex-wrap">
        <a href="index.php" class="inline-flex items-center gap-2 bg-brand-600 text-white py-3 px-6 rounded-2xl font-bold text-sm no-underline hover:bg-brand-700 transition-colors">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
          Shop Again
        </a>
        <a href="track.php?order_code=<?= urlencode($orderCode) ?>" class="inline-flex items-center gap-2 bg-white text-gray-700 py-3 px-6 rounded-2xl font-semibold text-sm no-underline border border-gray-200 hover:bg-gray-50 transition-colors">
          Track Order
        </a>
      </div>
    </div>
  </div>

  <?php elseif ($alreadyReviewed): ?>
  <!-- ALREADY REVIEWED -->
  <div class="bg-white rounded-3xl shadow-lg shadow-black/5 border border-gray-100 overflow-hidden">
    <div class="text-center py-12 px-6">
      <div class="text-5xl mb-4">⭐</div>
      <h2 class="font-display text-3xl text-gray-900 mb-2">Already reviewed!</h2>
      <p class="text-sm text-gray-600 mb-6">You've already submitted your review for this order. Thank you for taking the time!</p>
      <a href="index.php" class="inline-flex items-center gap-2 bg-brand-600 text-white py-3 px-6 rounded-2xl font-bold text-sm no-underline hover:bg-brand-700 transition-colors">
        Back to Shop
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- REVIEW FORM -->

  <!-- Greeting card — uses renamed recipient_* columns -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-4 flex items-center gap-3.5 mb-5">
    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-orange-500 to-amber-400 text-white font-extrabold text-lg flex items-center justify-center flex-shrink-0">
      <?= strtoupper(substr($order['recipient_first_name'], 0, 1)) ?>
    </div>
    <div>
      <p class="text-[13px] text-gray-500 m-0">Reviewing as</p>
      <p class="text-[15px] font-bold text-gray-900 mt-0.5 mb-0">
        <?= htmlspecialchars($order['recipient_first_name'].' '.$order['recipient_last_name']) ?>
        · <span class="font-normal text-gray-500"><?= htmlspecialchars($order['recipient_email']) ?></span>
      </p>
    </div>
    <span class="ml-auto flex-shrink-0 inline-flex items-center gap-1.5 bg-green-100 text-green-800 px-3 py-1.5 rounded-full text-xs font-semibold">
      <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
      Verified Purchase
    </span>
  </div>

  <!-- Validation errors -->
  <?php if (!empty($validationErrors)): ?>
  <div class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-5">
    <p class="font-bold text-red-800 text-sm m-0 mb-1.5">Please fix the following:</p>
    <?php foreach ($validationErrors as $ve): ?>
    <p class="text-sm text-red-800 font-medium my-1"><?= htmlspecialchars($ve) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Adjust this path if your add.php lives somewhere other than process/add.php -->
  <form method="POST" action="process/add.php" enctype="multipart/form-data" id="reviewForm">
    <input type="hidden" name="submit_review" value="1">
    <input type="hidden" name="order_code" value="<?= htmlspecialchars($orderCode) ?>">
    <input type="hidden" name="token"      value="<?= htmlspecialchars($token) ?>">

    <p class="text-[11px] font-bold tracking-widest uppercase text-brand-600 mb-3">
      <?= count($orderItems) ?> Product<?= count($orderItems) !== 1 ? 's' : '' ?> in this Order
    </p>

    <?php foreach ($orderItems as $item):
      $iid = $item['order_item_id'];
    ?>
    <div class="bg-white rounded-3xl shadow-lg shadow-black/5 border border-gray-100 mb-5 overflow-hidden" id="card-<?= $iid ?>">
      <div class="flex items-center gap-4 px-6 py-5 border-b border-gray-50">
        <?php if (!empty($item['product_image'])): ?>
          <img src="./uploads/products/<?= htmlspecialchars($item['product_image']) ?>" alt="" class="w-[52px] h-[52px] rounded-xl object-cover bg-gray-50 border border-gray-100 flex-shrink-0">
        <?php else: ?>
          <div class="w-[52px] h-[52px] rounded-xl bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center text-2xl flex-shrink-0">🐟</div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="text-[15px] font-bold text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
          <?php if (!empty($item['variant_name'])): ?>
          <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($item['variant_name']) ?> · Qty: <?= $item['quantity'] ?></p>
          <?php endif; ?>
        </div>
        <?php if ($item['is_reviewed']): ?>
        <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-800 px-3 py-1.5 rounded-full text-xs font-semibold">
          <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
          Reviewed
        </span>
        <?php endif; ?>
      </div>

      <?php if (!$item['is_reviewed']): ?>
      <div class="p-6">
        <!-- Star rating -->
        <p class="text-[11px] font-bold tracking-widest uppercase text-brand-600 mb-2">Your Rating</p>
        <div class="flex gap-1.5 mb-5 star-group" data-item="<?= $iid ?>">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <label class="cursor-pointer">
            <input type="radio" name="rating_<?= $iid ?>" value="<?= $s ?>" class="hidden star-input">
            <svg width="36" height="36" viewBox="0 0 24 24" class="fill-gray-200 transition-colors duration-150 star-svg" data-val="<?= $s ?>" data-item="<?= $iid ?>">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
          </label>
          <?php endfor; ?>
        </div>
        <p class="text-[13px] font-semibold text-amber-500 min-h-[1.25rem] mb-3" id="rating-label-<?= $iid ?>">Click to rate</p>

        <!-- Feedback -->
        <p class="text-[11px] font-bold tracking-widest uppercase text-brand-600 mb-2">Your Review</p>
        <textarea
          name="feedback_<?= $iid ?>"
          class="w-full px-4 py-3.5 border-[1.5px] border-gray-200 rounded-2xl font-sans text-sm text-gray-900 resize-y min-h-[110px] bg-white transition-colors focus:outline-none focus:border-brand-600 focus:ring-[3px] focus:ring-brand-600/10"
          placeholder="What did you like or dislike? Was it fresh? Would you order again? (min. 10 characters)"
          maxlength="1000"
          data-counter="counter-<?= $iid ?>"
          oninput="updateCounter(this)"><?= htmlspecialchars($_POST["feedback_{$iid}"] ?? '') ?></textarea>
        <p class="text-[11px] text-gray-400 text-right mt-1" id="counter-<?= $iid ?>">0 / 1000</p>

        <!-- Photos -->
        <p class="text-[11px] font-bold tracking-widest uppercase text-brand-600 mt-4 mb-2">Add Photos <span class="font-normal text-gray-400 normal-case tracking-normal">(optional)</span></p>
        <div class="photo-zone border-2 border-dashed border-gray-200 rounded-2xl p-5 text-center cursor-pointer transition-colors duration-200 mt-2 bg-gray-50"
             id="zone-<?= $iid ?>"
             onclick="document.getElementById('photos-<?= $iid ?>').click()"
             ondragover="dragOver(event,this)" ondragleave="dragLeave(this)" ondrop="dropFiles(event,<?= $iid ?>)">
          <div class="w-9 h-9 bg-brand-50 rounded-lg flex items-center justify-center mx-auto mb-2">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <p class="text-sm font-semibold text-gray-700 m-0">Upload photos</p>
          <p class="text-xs text-gray-400 mt-1 mb-0">JPG, PNG, WebP — max 5MB each, up to 5 photos</p>
        </div>
        <input type="file" id="photos-<?= $iid ?>" name="photos_<?= $iid ?>[]"
               multiple accept="image/*,.heic,.heif" class="hidden"
               onchange="handleFiles(this, <?= $iid ?>)">
        <div class="flex flex-wrap gap-2.5 mt-3.5" id="preview-<?= $iid ?>"></div>
      </div>
      <?php else: ?>
      <div class="px-6 py-3.5 text-gray-500 text-sm">
        ✓ You've already reviewed this product. Thank you!
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php $hasUnreviewed = array_filter($orderItems, fn($i) => !$i['is_reviewed']); ?>
    <?php if (!empty($hasUnreviewed)): ?>

    <!-- Reviewer context -->
    <div class="bg-white rounded-3xl shadow-lg shadow-black/5 border border-gray-100 mb-5 overflow-hidden">
      <div class="p-6">
        <p class="text-[11px] font-bold tracking-widest uppercase text-brand-600 mb-2">About You <span class="font-normal text-gray-400 normal-case tracking-normal">(optional)</span></p>
        <p class="text-[13px] text-gray-500 mb-4">Share a bit about yourself to give your review more context.</p>
        <div class="grid grid-cols-2 gap-3.5">
          <div>
            <label class="block text-[13px] font-semibold text-gray-700 mb-1.5">Your Title / Role</label>
            <input type="text" name="position" placeholder="e.g. Restaurant Owner"
                   value="<?= htmlspecialchars($_POST['position'] ?? '') ?>"
                   class="w-full py-2.5 px-3.5 border-[1.5px] border-gray-200 rounded-xl font-sans text-sm outline-none transition-colors focus:border-brand-600 focus:ring-[3px] focus:ring-brand-600/10">
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-gray-700 mb-1.5">Company / Restaurant</label>
            <input type="text" name="company" placeholder="e.g. Dela Cruz Eatery"
                   value="<?= htmlspecialchars($_POST['company'] ?? '') ?>"
                   class="w-full py-2.5 px-3.5 border-[1.5px] border-gray-200 rounded-xl font-sans text-sm outline-none transition-colors focus:border-brand-600 focus:ring-[3px] focus:ring-brand-600/10">
          </div>
        </div>
      </div>
    </div>

    <p class="text-xs text-gray-400 text-center mb-4 leading-relaxed">
      By submitting, you confirm this review is based on a genuine purchase and represents your honest experience.
      Reviews are moderated before publishing.
    </p>

    <button type="submit" class="w-full py-4 bg-brand-600 text-white border-0 rounded-2xl font-sans text-base font-bold cursor-pointer flex items-center justify-center gap-2.5 shadow-lg shadow-brand-600/30 transition-all hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-600/40 active:scale-[.98] disabled:bg-gray-300 disabled:cursor-not-allowed disabled:shadow-none" id="submitBtn">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Submit My Review
    </button>
    <?php endif; ?>
  </form>

  <?php endif; ?>

  <!-- Footer -->
  <div class="text-center py-8 text-xs text-gray-400">
    <img src="./assets/icons/logo.svg" alt="SJFBI" class="h-7 opacity-40 mx-auto mb-2 block">
    St. Joseph Fish Brokerage Inc. — Navotas City, Philippines
  </div>

</div>

<script>
const ratingLabels = ['','Terrible','Poor','Average','Good','Excellent!'];

document.querySelectorAll('.star-group').forEach(function(group) {
  var itemId  = group.dataset.item;
  var stars   = group.querySelectorAll('.star-svg');
  var inputs  = group.querySelectorAll('.star-input');
  var label   = document.getElementById('rating-label-' + itemId);
  var current = 0;

  function fill(n) {
    stars.forEach(function(s, i) {
      s.classList.toggle('fill-amber-500', i < n);
      s.classList.toggle('fill-gray-200', i >= n);
    });
    if (label) label.textContent = n ? ratingLabels[n] : 'Click to rate';
  }

  stars.forEach(function(star, idx) {
    var val = idx + 1;
    star.parentElement.addEventListener('mouseenter', function() { fill(val); });
    star.parentElement.addEventListener('mouseleave', function() { fill(current); });
    star.parentElement.addEventListener('click', function() {
      current = val; inputs[idx].checked = true; fill(val);
    });
  });
});

function updateCounter(el) {
  var counter = document.getElementById(el.dataset.counter);
  if (counter) counter.textContent = el.value.length + ' / 1000';
}

var photoFiles = {};
function handleFiles(input, itemId) {
  if (!photoFiles[itemId]) photoFiles[itemId] = [];
  Array.from(input.files).forEach(function(f) {
    if (photoFiles[itemId].length >= 5) return;
    if (f.size > 5 * 1024 * 1024) { alert(f.name + ' is too large (max 5MB)'); return; }
    photoFiles[itemId].push(f);
  });
  renderPreviews(itemId, input);
}
function renderPreviews(itemId, input) {
  var container = document.getElementById('preview-' + itemId);
  container.innerHTML = '';
  (photoFiles[itemId] || []).forEach(function(file, i) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var div = document.createElement('div');
      div.className = 'relative w-[72px] h-[72px] rounded-xl overflow-hidden border border-gray-200';
      div.innerHTML = '<img src="' + e.target.result + '" alt="" class="w-full h-full object-cover">'
        + '<button type="button" class="absolute top-0.5 right-0.5 w-[18px] h-[18px] rounded-full bg-black/60 text-white text-[10px] flex items-center justify-center cursor-pointer border-0 leading-none" data-idx="' + i + '" data-item="' + itemId + '">×</button>';
      container.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
  var dt = new DataTransfer();
  (photoFiles[itemId] || []).forEach(function(f) { dt.items.add(f); });
  if (input) input.files = dt.files;
}
document.addEventListener('click', function(e) {
  if (e.target.matches('[data-idx][data-item]')) {
    var idx    = parseInt(e.target.dataset.idx);
    var itemId = e.target.dataset.item;
    photoFiles[itemId].splice(idx, 1);
    renderPreviews(itemId, document.getElementById('photos-' + itemId));
  }
});
function dragOver(e, el)  { e.preventDefault(); el.classList.add('border-brand-600', 'bg-brand-50'); el.classList.remove('border-gray-200', 'bg-gray-50'); }
function dragLeave(el)    { el.classList.remove('border-brand-600', 'bg-brand-50'); el.classList.add('border-gray-200', 'bg-gray-50'); }
function dropFiles(e, itemId) {
  e.preventDefault();
  dragLeave(document.getElementById('zone-' + itemId));
  if (!photoFiles[itemId]) photoFiles[itemId] = [];
  Array.from(e.dataTransfer.files).forEach(function(f) {
    if (photoFiles[itemId].length >= 5) return;
    if (!f.type.startsWith('image/')) return;
    if (f.size > 5 * 1024 * 1024) return;
    photoFiles[itemId].push(f);
  });
  renderPreviews(itemId, document.getElementById('photos-' + itemId));
}
document.getElementById('reviewForm')?.addEventListener('submit', function() {
  var btn = document.getElementById('submitBtn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Submitting…';
  }
});

// Preline UI component init (accordions, dropdowns, etc. if added later)
window.addEventListener('load', function() {
  if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
});
</script>
</body>
</html>