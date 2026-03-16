<?php
session_start();
include 'conn.php';

// ── Token validation ───────────────────────────────────────────────────────────
$orderCode = isset($_GET['order']) ? trim($_GET['order']) : '';
$token     = isset($_GET['token']) ? trim($_GET['token']) : '';

$error     = null;
$order     = null;
$orderItems= [];
$submitted = false;
$alreadyReviewed = false;

function generateReviewToken(string $orderCode, string $email, string $salt = 'sjfbi_review_2025'): string {
  return strtoupper(substr(hash('sha256', $orderCode . $email . $salt), 0, 12));
}

if (!$orderCode || !$token) {
  $error = 'invalid_link';
} else {
  // Fetch order — must be Delivered
  $stmt = $conn->prepare("
    SELECT o.*, 
           GROUP_CONCAT(p.product_name SEPARATOR '||') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_code = ? AND o.order_status = 'Delivered'
    GROUP BY o.order_id
  ");
  $stmt->bind_param('s', $orderCode);
  $stmt->execute();
  $order = $stmt->get_result()->fetch_assoc();

  if (!$order) {
    $error = 'not_delivered'; // order not found or not delivered
  } else {
    // Validate token
    $expectedToken = generateReviewToken($orderCode, $order['email']);
    if (!hash_equals($expectedToken, strtoupper($token))) {
      $error = 'invalid_token';
    } else {
      // Fetch order items (only unreviewed)
      $iStmt = $conn->prepare("
        SELECT oi.*, p.product_name, p.product_id,
               pv.variant_name, pv.variant_price,
               (SELECT image_path FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1) as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
        WHERE oi.order_id = ?
      ");
      $iStmt->bind_param('i', $order['order_id']);
      $iStmt->execute();
      $orderItems = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

      // Check if ALL items already reviewed
      $allReviewed = true;
      foreach ($orderItems as $item) {
        if (!$item['is_reviewed']) { $allReviewed = false; break; }
      }
      if ($allReviewed && !empty($orderItems)) {
        $alreadyReviewed = true;
      }
    }
  }
}

// ── Handle POST submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && !$alreadyReviewed && $order) {
  // ── Read session flash (set by functions/add.php after redirect) ──────────────
  $submitted        = isset($_SESSION['review_submitted']) && $_SESSION['review_submitted'];
  $validationErrors = $_SESSION['review_errors'] ?? [];
  unset($_SESSION['review_submitted'], $_SESSION['review_errors']);

  $pageTitle = 'Leave a Review';
  $postedToken = $_POST['token'] ?? '';
  if ($postedOrder !== $orderCode || strtoupper($postedToken) !== strtoupper($token)) {
    $error = 'invalid_token';
  } else {
    foreach ($orderItems as $item) {
      if ($item['is_reviewed']) continue;

      $itemId    = $item['order_item_id'];
      $productId = $item['product_id'];
      $rating    = intval($_POST["rating_$itemId"] ?? 0);
      $feedback  = trim($_POST["feedback_$itemId"] ?? '');

      // Skip if not filled in (optional per item)
      if (!$rating && !$feedback) continue;

      if ($rating < 1 || $rating > 5) {
        $validationErrors[] = "Please select a rating for " . htmlspecialchars($item['product_name']);
        continue;
      }
      if (strlen($feedback) < 10) {
        $validationErrors[] = "Review for " . htmlspecialchars($item['product_name']) . " must be at least 10 characters.";
        continue;
      }

      $fullName = trim($order['first_name'] . ' ' . $order['last_name']);
      $email    = $order['email'];
      $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
      $ua       = $_SERVER['HTTP_USER_AGENT'] ?? null;

      // Insert review
      $rStmt = $conn->prepare("INSERT INTO reviews 
        (order_id, order_item_id, product_id, full_name, email, rating, feedback,
        is_verified_purchase, status, reviewer_ip, user_agent)
      VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'pending', ?, ?)
      ");
      $rStmt->bind_param('iiiississ',   // ← was 'iiissiiss'
          $order['order_id'], $itemId, $productId,
          $fullName, $email, $rating, $feedback, $ip, $ua
      );

      if ($rStmt->execute()) {
        $reviewId = $conn->insert_id;

        // Handle photo uploads
        if (!empty($_FILES["photos_$itemId"]['name'][0])) {
          $uploadDir = __DIR__ . '/uploads/reviews/';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

          foreach ($_FILES["photos_$itemId"]['tmp_name'] as $idx => $tmpPath) {
            if (!is_uploaded_file($tmpPath)) continue;
            $origName  = $_FILES["photos_$itemId"]['name'][$idx];
            $mimeType  = $_FILES["photos_$itemId"]['type'][$idx];
            $fileSize  = $_FILES["photos_$itemId"]['size'][$idx];
            if ($fileSize > 5 * 1024 * 1024) continue; // 5MB max
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($mimeType, $allowed)) continue;
            $ext      = pathinfo($origName, PATHINFO_EXTENSION);
            $fileName = 'review_' . $reviewId . '_' . $idx . '_' . uniqid() . '.' . $ext;
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($tmpPath, $destPath)) {
              $relPath = 'uploads/reviews/' . $fileName;
              $aStmt = $conn->prepare("INSERT INTO review_attachments (review_id, file_path, file_name, file_size, mime_type, upload_order) VALUES (?,?,?,?,?,?)");
              $order_idx = $idx + 1;
              $aStmt->bind_param('issiis', $reviewId, $relPath, $fileName, $fileSize, $mimeType, $order_idx);
              $aStmt->execute();
            }
          }
        }

        // Mark item as reviewed
        $uStmt = $conn->prepare("UPDATE order_items SET is_reviewed = 1, review_id = ? WHERE order_item_id = ?");
        $uStmt->bind_param('ii', $reviewId, $itemId);
        $uStmt->execute();
        $reviewedCount++;
      }
    }

    if (empty($validationErrors) && $reviewedCount > 0) {
      $submitted = true;
    } elseif (empty($validationErrors) && $reviewedCount === 0) {
      $validationErrors[] = "Please fill in at least one product review.";
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> | St. Joseph Fish Brokerage Inc.</title>
  <meta name="robots" content="noindex, nofollow">

  <link rel="shortcut icon" href="./assets/icons/logo.ico">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/logo.svg">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">

  <!-- CSS Files -->
  <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
  <link href="style.css" rel="stylesheet">

  <style>
    :root {
      --orange:     #ea580c;
      --orange-lt:  #fff7ed;
      --orange-mid: #fed7aa;
      --gray-900:   #111827;
      --gray-600:   #4b5563;
      --gray-200:   #e5e7eb;
      --gray-50:    #f9fafb;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      font-family: 'Lexend', sans-serif;
      background: #fafaf9;
      min-height: 100vh;
      margin: 0;
    }

    /* ── Hero strip ── */
    .review-hero {
      background: linear-gradient(135deg, #f97316 0%, #fb923c 55%, #fbbf24 100%);
      padding: 3rem 1.5rem 5rem;
      position: relative;
      overflow: hidden;
    }
    .review-hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse at 80% 10%, rgba(255,255,255,.12) 0%, transparent 55%),
                  radial-gradient(ellipse at 10% 90%, rgba(0,0,0,.06) 0%, transparent 45%);
    }
    .review-hero .inner { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; text-align: center; }
    .review-hero .fish-icon {
      width: 64px; height: 64px; background: rgba(255,255,255,.2);
      border-radius: 1.25rem; display: inline-flex; align-items: center; justify-content: center;
      margin-bottom: 1.25rem; backdrop-filter: blur(6px);
    }
    .review-hero h1 { font-family: 'Playfair Display', serif; font-size: 2.25rem; color: white; margin: 0 0 .5rem; line-height: 1.2; }
    .review-hero p  { color: rgba(255,255,255,.8); font-size: .9375rem; margin: 0; }

    /* order badge */
    .order-badge {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.18); backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.3);
      border-radius: 9999px; padding: .375rem 1rem;
      color: white; font-size: .8125rem; font-weight: 600;
      margin-top: 1rem; letter-spacing: .06em;
    }

    /* ── Main card area ── */
    .main-wrap { max-width: 680px; margin: -2.5rem auto 3rem; padding: 0 1rem; position: relative; z-index: 2; }

    /* ── Product review card ── */
    .review-card {
      background: white;
      border-radius: 1.5rem;
      box-shadow: 0 4px 24px rgba(0,0,0,.07);
      margin-bottom: 1.25rem;
      overflow: hidden;
      border: 1px solid #f3f4f6;
    }
    .review-card .card-head {
      display: flex; align-items: center; gap: 1rem;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f9fafb;
    }
    .card-thumb {
      width: 52px; height: 52px; border-radius: .75rem;
      object-fit: cover; background: #f9fafb; flex-shrink: 0;
      border: 1px solid #f3f4f6;
    }
    .card-thumb-placeholder {
      width: 52px; height: 52px; border-radius: .75rem;
      background: linear-gradient(135deg,#ffedd5,#fed7aa);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; flex-shrink: 0;
    }
    .card-product-name { font-size: .9375rem; font-weight: 700; color: var(--gray-900); }
    .card-variant      { font-size: .75rem; color: #6b7280; margin-top: .15rem; }
    .card-body         { padding: 1.5rem; }

    /* ── Star rating input ── */
    .star-group { display: flex; gap: .375rem; margin-bottom: 1.25rem; }
    .star-group label { cursor: pointer; }
    .star-group label svg { transition: transform .15s; }
    .star-group label:hover svg,
    .star-group label:hover ~ label svg { transform: scale(1.12); }
    .star-input { display: none; }

    /* stars fill via JS toggling .filled class */
    .star-svg   { fill: #e5e7eb; transition: fill .15s; }
    .star-svg.filled { fill: #f59e0b; }

    /* Rating label */
    .rating-label { font-size: .8125rem; font-weight: 600; color: #f59e0b; min-height: 1.25rem; margin-bottom: .75rem; }

    /* ── Textarea ── */
    .review-textarea {
      width: 100%; padding: .875rem 1rem;
      border: 1.5px solid var(--gray-200); border-radius: 1rem;
      font-family: 'Lexend', sans-serif; font-size: .875rem; color: var(--gray-900);
      resize: vertical; min-height: 110px;
      transition: border-color .15s, box-shadow .15s;
      outline: none; background: white;
    }
    .review-textarea:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(234,88,12,.1); }
    .char-count { font-size: .7rem; color: #9ca3af; text-align: right; margin-top: .25rem; }

    /* ── Photo upload area ── */
    .photo-zone {
      border: 2px dashed #e5e7eb; border-radius: 1rem;
      padding: 1.25rem; text-align: center; cursor: pointer;
      transition: border-color .2s, background .2s;
      margin-top: 1rem; background: #fafafa;
    }
    .photo-zone:hover { border-color: var(--orange); background: var(--orange-lt); }
    .photo-zone.dragover { border-color: var(--orange); background: var(--orange-lt); }
    .photo-preview { display: flex; flex-wrap: wrap; gap: .625rem; margin-top: .875rem; }
    .photo-thumb {
      position: relative; width: 72px; height: 72px;
      border-radius: .625rem; overflow: hidden; border: 1px solid #e5e7eb;
    }
    .photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .photo-thumb .rm-btn {
      position: absolute; top: 2px; right: 2px;
      width: 18px; height: 18px; border-radius: 50%;
      background: rgba(0,0,0,.6); color: white; font-size: 10px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; border: none; line-height: 1;
    }

    /* ── Already reviewed badge ── */
    .reviewed-badge {
      display: inline-flex; align-items: center; gap: .375rem;
      background: #dcfce7; color: #166534;
      padding: .35rem .8rem; border-radius: 9999px;
      font-size: .75rem; font-weight: 600;
    }

    /* ── Submit button ── */
    .submit-btn {
      width: 100%; padding: 1rem;
      background: var(--orange); color: white;
      border: none; border-radius: 1rem;
      font-family: 'Lexend', sans-serif; font-size: 1rem; font-weight: 700;
      cursor: pointer; transition: background .15s, transform .1s, box-shadow .15s;
      display: flex; align-items: center; justify-content: center; gap: .625rem;
      box-shadow: 0 4px 12px rgba(234,88,12,.3);
    }
    .submit-btn:hover   { background: #c2410c; box-shadow: 0 6px 20px rgba(234,88,12,.4); }
    .submit-btn:active  { transform: scale(.98); }
    .submit-btn:disabled{ background: #d1d5db; cursor: not-allowed; box-shadow: none; }

    /* ── Validation error pill ── */
    .val-error {
      background: #fee2e2; border: 1px solid #fecaca;
      border-radius: 1rem; padding: 1rem 1.25rem;
      margin-bottom: 1.25rem;
    }
    .val-error p { font-size: .875rem; color: #991b1b; font-weight: 500; margin: .25rem 0; }

    /* ── Success screen ── */
    .success-wrap { text-align: center; padding: 3rem 1.5rem; }
    .success-anim {
      width: 90px; height: 90px; margin: 0 auto 1.5rem;
    }
    @keyframes dash { to { stroke-dashoffset: 0; } }
    .anim-circle { stroke-dasharray: 166; stroke-dashoffset: 166; animation: dash .9s ease forwards .2s; }
    .anim-tick   { stroke-dasharray: 48;  stroke-dashoffset: 48;  animation: dash .4s ease forwards .9s; }
    .success-wrap h2 { font-family: 'Playfair Display',serif; font-size: 1.875rem; color: var(--gray-900); margin: 0 0 .5rem; }
    .success-wrap p  { color: var(--gray-600); font-size: .9375rem; margin: 0 0 1.5rem; }

    /* ── Error screen ── */
    .error-wrap { background: white; border-radius: 1.5rem; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 3rem 2rem; text-align: center; }
    .error-icon { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
    .error-wrap h2 { font-size: 1.375rem; font-weight: 700; color: var(--gray-900); margin: 0 0 .5rem; }
    .error-wrap p  { font-size: .9rem; color: var(--gray-600); margin: 0 0 1.5rem; }

    /* ── Customer greeting card ── */
    .greeting-card {
      background: white; border-radius: 1.25rem;
      border: 1px solid #f3f4f6; padding: 1rem 1.5rem;
      display: flex; align-items: center; gap: .875rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .greeting-avatar {
      width: 44px; height: 44px; border-radius: 50%;
      background: linear-gradient(135deg,#f97316,#fbbf24);
      color: white; font-weight: 800; font-size: 1.125rem;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    /* ── Section label ── */
    .section-label { font-size: .6875rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--orange); margin: 0 0 .5rem; }

    /* ── Responsive ── */
    @media (max-width: 480px) {
      .review-hero h1 { font-size: 1.75rem; }
      .card-body { padding: 1.125rem; }
    }
  </style>
</head>
<body>

<!-- Hero -->
<div class="review-hero">
  <div class="inner">
    <div class="fish-icon">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round">
        <path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.46-3.44 6-7 6-3.56 0-7.56-2.54-8.5-6z"/>
        <path d="M18 12h.01M2 12c1 2 2.5 3 4 3s3-1 4-3-1.5-3-4-3-3 1-4 3z"/>
      </svg>
    </div>
    <h1>How was your order?</h1>
    <p>Your feedback helps us serve you better — and helps other buyers make great choices.</p>
    <?php if ($order): ?>
    <div class="order-badge">
      <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
      Order <?= htmlspecialchars($orderCode) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Main content -->
<div class="main-wrap">

  <?php if ($error): ?>
  <!-- ══ ERROR STATE ══ -->
  <div class="error-wrap">
    <?php if ($error === 'not_delivered'): ?>
      <div class="error-icon" style="background:#fef9c3">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#ca8a04" stroke-width="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2>Not yet delivered</h2>
      <p>You can leave a review once your order has been delivered. We'll send you a link when it's ready!</p>
    <?php elseif ($error === 'invalid_token'): ?>
      <div class="error-icon" style="background:#fee2e2">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </div>
      <h2>Invalid review link</h2>
      <p>This review link is invalid or has expired. Please use the original link sent to your email or SMS.</p>
    <?php else: ?>
      <div class="error-icon" style="background:#fee2e2">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </div>
      <h2>Link not found</h2>
      <p>We couldn't find a valid review link. Please check the link in your email or SMS and try again.</p>
    <?php endif; ?>
    <a href="index.php" style="display:inline-flex;align-items:center;gap:.5rem;background:var(--orange);color:white;padding:.75rem 1.5rem;border-radius:.875rem;font-weight:700;font-size:.9rem;text-decoration:none;transition:background .15s" onmouseover="this.style.background='#c2410c'" onmouseout="this.style.background='#ea580c'">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Back to Homepage
    </a>
  </div>

  <?php elseif ($submitted): ?>
  <!-- ══ SUCCESS STATE ══ -->
  <div class="review-card">
    <div class="success-wrap">
      <svg class="success-anim" viewBox="0 0 52 52">
        <circle class="anim-circle" cx="26" cy="26" r="25" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>
        <path   class="anim-tick"   fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l8 8 16-16"/>
      </svg>
      <h2>Thank you, <?= htmlspecialchars($order['first_name']) ?>!</h2>
      <p>Your review is pending approval and will appear on the product page soon. We really appreciate your time.</p>
      <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:.5rem;background:var(--orange);color:white;padding:.75rem 1.5rem;border-radius:.875rem;font-weight:700;font-size:.875rem;text-decoration:none">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
          Shop Again
        </a>
        <a href="track.php?order_code=<?= urlencode($orderCode) ?>" style="display:inline-flex;align-items:center;gap:.5rem;background:white;color:#374151;padding:.75rem 1.5rem;border-radius:.875rem;font-weight:600;font-size:.875rem;text-decoration:none;border:1px solid #e5e7eb">
          Track Order
        </a>
      </div>
    </div>
  </div>

  <?php elseif ($alreadyReviewed): ?>
  <!-- ══ ALREADY REVIEWED ══ -->
  <div class="review-card">
    <div class="success-wrap">
      <div style="font-size:3.5rem;margin-bottom:1rem">⭐</div>
      <h2>Already reviewed!</h2>
      <p>You've already submitted your review for this order. Thank you for taking the time!</p>
      <a href="index.php" style="display:inline-flex;align-items:center;gap:.5rem;background:var(--orange);color:white;padding:.75rem 1.5rem;border-radius:.875rem;font-weight:700;font-size:.875rem;text-decoration:none">
        Back to Shop
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- ══ REVIEW FORM ══ -->

  <!-- Greeting -->
  <div class="greeting-card">
    <div class="greeting-avatar"><?= strtoupper(substr($order['first_name'],0,1)) ?></div>
    <div>
      <p style="font-size:.8125rem;color:#6b7280;margin:0">Reviewing as</p>
      <p style="font-size:.9375rem;font-weight:700;color:var(--gray-900);margin:.1rem 0 0"><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?> · <span style="font-weight:400;color:#6b7280"><?= htmlspecialchars($order['email']) ?></span></p>
    </div>
    <span class="reviewed-badge" style="margin-left:auto;flex-shrink:0">
      <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
      Verified Purchase
    </span>
  </div>

  <!-- Validation errors -->
  <?php if (!empty($validationErrors)): ?>
  <div class="val-error">
    <p style="font-weight:700;margin:0 0 .375rem">Please fix the following:</p>
    <?php foreach ($validationErrors as $ve): ?>
    <p><?= htmlspecialchars($ve) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="functions/add.php" enctype="multipart/form-data" id="reviewForm">
    <input type="hidden" name="order_code" value="<?= htmlspecialchars($orderCode) ?>">
    <input type="hidden" name="token"      value="<?= htmlspecialchars($token) ?>">

    <p class="section-label" style="margin-bottom:.75rem">
      <?= count($orderItems) ?> Product<?= count($orderItems) !== 1 ? 's' : '' ?> in this Order
    </p>

    <?php foreach ($orderItems as $item): ?>
    <?php $iid = $item['order_item_id']; ?>
    <div class="review-card" id="card-<?= $iid ?>">
      <div class="card-head">
        <?php if (!empty($item['product_image'])): ?>
          <img src="./uploads/products/<?= htmlspecialchars($item['product_image']) ?>" alt="" class="card-thumb">
        <?php else: ?>
          <div class="card-thumb-placeholder">🐟</div>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <p class="card-product-name"><?= htmlspecialchars($item['product_name']) ?></p>
          <?php if ($item['variant_name']): ?>
          <p class="card-variant"><?= htmlspecialchars($item['variant_name']) ?> · Qty: <?= $item['quantity'] ?></p>
          <?php endif; ?>
        </div>
        <?php if ($item['is_reviewed']): ?>
        <span class="reviewed-badge">
          <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
          Reviewed
        </span>
        <?php endif; ?>
      </div>

      <?php if (!$item['is_reviewed']): ?>
      <div class="card-body">
        <!-- Star rating -->
        <p class="section-label">Your Rating</p>
        <div class="star-group" data-item="<?= $iid ?>">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <label>
            <input type="radio" name="rating_<?= $iid ?>" value="<?= $s ?>" class="star-input" required>
            <svg width="36" height="36" viewBox="0 0 24 24" class="star-svg" data-val="<?= $s ?>" data-item="<?= $iid ?>">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
          </label>
          <?php endfor; ?>
        </div>
        <p class="rating-label" id="rating-label-<?= $iid ?>">Click to rate</p>

        <!-- Feedback -->
        <p class="section-label">Your Review</p>
        <textarea
          name="feedback_<?= $iid ?>"
          class="review-textarea"
          placeholder="What did you like or dislike? Was it fresh? Would you order again? (min. 10 characters)"
          maxlength="1000"
          data-counter="counter-<?= $iid ?>"
          oninput="updateCounter(this)"><?= htmlspecialchars($_POST["feedback_$iid"] ?? '') ?></textarea>
        <p class="char-count" id="counter-<?= $iid ?>">0 / 1000</p>

        <!-- Photos -->
        <p class="section-label" style="margin-top:1rem">Add Photos <span style="font-weight:400;color:#9ca3af">(optional)</span></p>
        <div class="photo-zone" id="zone-<?= $iid ?>"
             onclick="document.getElementById('photos-<?= $iid ?>').click()"
             ondragover="dragOver(event,this)" ondragleave="dragLeave(this)" ondrop="dropFiles(event,<?= $iid ?>)">
          <div style="width:36px;height:36px;background:var(--orange-lt);border-radius:.625rem;display:flex;align-items:center;justify-content:center;margin:0 auto .5rem">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <p style="font-size:.875rem;font-weight:600;color:#374151;margin:0">Upload photos</p>
          <p style="font-size:.75rem;color:#9ca3af;margin:.25rem 0 0">JPG, PNG, WebP — max 5MB each, up to 5 photos</p>
        </div>
        <input type="file" id="photos-<?= $iid ?>" name="photos_<?= $iid ?>[]"
               multiple accept="image/*" class="hidden"
               onchange="handleFiles(this, <?= $iid ?>)">
        <div class="photo-preview" id="preview-<?= $iid ?>"></div>
      </div>
      <?php else: ?>
      <div class="card-body" style="padding:.875rem 1.5rem;color:#6b7280;font-size:.875rem">
        ✓ You've already reviewed this product. Thank you!
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php $hasUnreviewed = array_filter($orderItems, fn($i) => !$i['is_reviewed']); ?>
    <?php if (!empty($hasUnreviewed)): ?>

    <!-- Reviewer context (optional) -->
    <div class="review-card">
      <div class="card-body">
        <p class="section-label">About You <span style="font-weight:400;color:#9ca3af">(optional)</span></p>
        <p style="font-size:.8125rem;color:#6b7280;margin:0 0 1rem">Share a bit about yourself to give your review more context.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.875rem">
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.375rem">Your Title / Role</label>
            <input type="text" name="position" placeholder="e.g. Restaurant Owner"
                   value="<?= htmlspecialchars($_POST['position'] ?? '') ?>"
                   style="width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.75rem;font-family:'Lexend',sans-serif;font-size:.875rem;outline:none;transition:border-color .15s"
                   onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e5e7eb'">
          </div>
          <div>
            <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.375rem">Company / Restaurant</label>
            <input type="text" name="company" placeholder="e.g. Dela Cruz Eatery"
                   value="<?= htmlspecialchars($_POST['company'] ?? '') ?>"
                   style="width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.75rem;font-family:'Lexend',sans-serif;font-size:.875rem;outline:none;transition:border-color .15s"
                   onfocus="this.style.borderColor='#ea580c'" onblur="this.style.borderColor='#e5e7eb'">
          </div>
        </div>
      </div>
    </div>

    <!-- Disclaimer -->
    <p style="font-size:.75rem;color:#9ca3af;text-align:center;margin:0 0 1rem;line-height:1.6">
      By submitting, you confirm this review is based on a genuine purchase and represents your honest experience.
      Reviews are moderated before publishing.
    </p>

    <button type="submit" name="submit_review" class="submit-btn" id="submitBtn">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Submit My Review
    </button>

    <?php endif; ?>
  </form>

  <?php endif; ?>

  <!-- Footer -->
  <div style="text-align:center;padding:2rem 0;font-size:.75rem;color:#9ca3af">
    <img src="./assets/icons/logo.svg" alt="St. Joseph Fish Brokerage" style="height:28px;opacity:.4;margin-bottom:.5rem;display:block;margin-left:auto;margin-right:auto">
    St. Joseph Fish Brokerage Inc. — Navotas City, Philippines
  </div>

</div><!-- /main-wrap -->

<script>
// ── Star rating interactive ────────────────────────────────────────────────────
const ratingLabels = ['','Terrible','Poor','Average','Good','Excellent!'];

document.querySelectorAll('.star-group').forEach(function(group) {
  var itemId = group.dataset.item;
  var stars   = group.querySelectorAll('.star-svg');
  var inputs  = group.querySelectorAll('.star-input');
  var label   = document.getElementById('rating-label-' + itemId);
  var current = 0;

  function fill(n) {
    stars.forEach(function(s, i) {
      s.classList.toggle('filled', i < n);
    });
    if (label) label.textContent = n ? ratingLabels[n] : 'Click to rate';
  }

  stars.forEach(function(star, idx) {
    var val = idx + 1;
    star.parentElement.addEventListener('mouseenter', function() { fill(val); });
    star.parentElement.addEventListener('mouseleave', function() { fill(current); });
    star.parentElement.addEventListener('click', function() {
      current = val;
      inputs[idx].checked = true;
      fill(val);
    });
  });
});

// ── Char counter ───────────────────────────────────────────────────────────────
function updateCounter(el) {
  var counterId = el.dataset.counter;
  var counter = document.getElementById(counterId);
  if (counter) counter.textContent = el.value.length + ' / 1000';
}

// ── Photo upload ───────────────────────────────────────────────────────────────
var photoFiles = {};

function handleFiles(input, itemId) {
  if (!photoFiles[itemId]) photoFiles[itemId] = [];
  var newFiles = Array.from(input.files);
  newFiles.forEach(function(f) {
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
      div.className = 'photo-thumb';
      div.innerHTML = '<img src="' + e.target.result + '" alt=""><button type="button" class="rm-btn" data-idx="' + i + '" data-item="' + itemId + '">×</button>';
      container.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
  // Sync to file input
  var dt = new DataTransfer();
  (photoFiles[itemId] || []).forEach(function(f) { dt.items.add(f); });
  if (input) input.files = dt.files;
}

document.addEventListener('click', function(e) {
  if (e.target.classList.contains('rm-btn')) {
    var idx    = parseInt(e.target.dataset.idx);
    var itemId = e.target.dataset.item;
    photoFiles[itemId].splice(idx, 1);
    var inp = document.getElementById('photos-' + itemId);
    renderPreviews(itemId, inp);
  }
});

function dragOver(e, el)  { e.preventDefault(); el.classList.add('dragover'); }
function dragLeave(el)    { el.classList.remove('dragover'); }
function dropFiles(e, itemId) {
  e.preventDefault();
  document.getElementById('zone-' + itemId).classList.remove('dragover');
  var inp = document.getElementById('photos-' + itemId);
  if (!photoFiles[itemId]) photoFiles[itemId] = [];
  Array.from(e.dataTransfer.files).forEach(function(f) {
    if (photoFiles[itemId].length >= 5) return;
    if (!f.type.startsWith('image/')) return;
    if (f.size > 5 * 1024 * 1024) return;
    photoFiles[itemId].push(f);
  });
  renderPreviews(itemId, inp);
}

// ── Form submit guard ──────────────────────────────────────────────────────────
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
  var btn = document.getElementById('submitBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Submitting…'; }
});
</script>

<style>
  .hidden { display:none }
  .spin { animation: spin .8s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }
</style>

</body>
</html>