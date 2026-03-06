<?php
/**
 * share.php — Open Graph Proxy for Product Sharing
 * 
 * HOW IT WORKS:
 * 1. Facebook/crawlers hit this URL: https://fishbrokers.net/share.php?pid=42&slug=bangus
 * 2. This page outputs the correct per-product og:image, og:title, og:description
 * 3. Human visitors (non-crawlers) are immediately redirected to the real product page
 * 
 * WHY THIS IS NEEDED:
 * Facebook scrapes OG tags from the URL you pass to sharer.php.
 * If you pass the item page directly (item/bangus), Facebook reads THAT page's
 * <head> which only has the site's default/static OG tags → shows wrong image.
 * This proxy page outputs the correct product-specific OG tags.
 * 
 * PLACE THIS FILE AT: /var/www/html/share.php  (your site root, next to index.php)
 */
require_once 'conn.php'; // adjust path if needed — same as your other pages

$product_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$slug       = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Defaults (fallback if product not found)
$og_title       = 'St. Joseph Fish Brokerage Inc.';
$og_description = 'Fresh seafood delivered to your table. Isda sa Hapag ng Bawat Isa.';
$og_image       = 'https://fishbrokers.net/assets/icons/logo.svg'; // your default OG image
$og_url         = 'https://fishbrokers.net/';
$redirect_url   = 'https://fishbrokers.net/';

if ($product_id > 0) {
    // Fetch product details from DB
    $stmt = $conn->prepare("
        SELECT 
            p.product_name,
            p.product_unit,
            p.product_nickname,
            pi.image_path,
            MIN(CASE WHEN pv.discount_price > 0 THEN pv.discount_price ELSE pv.variant_price END) as min_price
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE p.product_id = ? AND p.is_deleted = 0
        GROUP BY p.product_id
        LIMIT 1
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        $product_name = $product['product_name'];
        $product_unit = $product['product_unit'];
        $min_price    = $product['min_price'];

        // Build OG values
        $og_title = $product_name . ' — St. Joseph Fish Brokerage Inc.';
        
        $og_description = 'Fresh ' . $product_name;
        if (!empty($product_unit)) $og_description .= ' (' . $product_unit . ')';
        if ($min_price > 0)        $og_description .= ' starting at ₱' . number_format($min_price, 2);
        $og_description .= '. Order now at fishbrokers.net!';

        // Product image — use primary image or fallback
        if (!empty($product['image_path'])) {
            $og_image = 'https://fishbrokers.net/uploads/products/' . $product['image_path'];
        } else {
            $og_image = 'https://fishbrokers.net/uploads/products/default.png';
        }

        // The real product page humans land on
        $product_slug = !empty($slug) ? $slug : strtolower(str_replace(' ', '-', $product_name));
        $og_url      = 'https://fishbrokers.net/item/' . urlencode($product_slug);
        $redirect_url = $og_url;
    }
}

// ─── Bot / Crawler detection ──────────────────────────────────────────────────
// Crawlers (Facebook, Twitter, Telegram, WhatsApp, etc.) DO NOT get redirected —
// they need to read the OG tags from this page.
// Human visitors are redirected immediately to the real product page.
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
$crawlers = [
    'facebookexternalhit',  // Facebook
    'facebot',              // Facebook
    'twitterbot',           // Twitter/X
    'telegrambot',          // Telegram
    'whatsapp',             // WhatsApp
    'linkedinbot',          // LinkedIn
    'slackbot',             // Slack
    'discordbot',           // Discord
    'googlebot',            // Google
    'bingbot',              // Bing
    'applebot',             // Apple
    'ia_archiver',          // Alexa
    'embedly',              // Embedly
];

$is_crawler = false;
foreach ($crawlers as $bot) {
    if (strpos($ua, $bot) !== false) {
        $is_crawler = true;
        break;
    }
}

// Redirect human visitors immediately to the real product page
if (!$is_crawler && $redirect_url !== 'https://fishbrokers.net/') {
    header('HTTP/1.1 302 Found');
    header('Location: ' . $redirect_url);
    exit;
}

// For crawlers (and fallback for direct access), output the OG-tagged HTML page
$og_title_safe       = htmlspecialchars($og_title,       ENT_QUOTES, 'UTF-8');
$og_description_safe = htmlspecialchars($og_description, ENT_QUOTES, 'UTF-8');
$og_image_safe       = htmlspecialchars($og_image,       ENT_QUOTES, 'UTF-8');
$og_url_safe         = htmlspecialchars($og_url,         ENT_QUOTES, 'UTF-8');
$redirect_url_safe   = htmlspecialchars($redirect_url,   ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $og_title_safe ?></title>

    <!-- ═══ PRIMARY META ════════════════════════════════════════════════════ -->
    <meta name="title"       content="<?= $og_title_safe ?>">
    <meta name="description" content="<?= $og_description_safe ?>">

    <!-- ═══ OPEN GRAPH / FACEBOOK ══════════════════════════════════════════ -->
    <meta property="og:type"        content="product">
    <meta property="og:url"         content="<?= $og_url_safe ?>">
    <meta property="og:title"       content="<?= $og_title_safe ?>">
    <meta property="og:description" content="<?= $og_description_safe ?>">
    <meta property="og:image"       content="<?= $og_image_safe ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name"   content="St. Joseph Fish Brokerage Inc.">
    <meta property="og:locale"      content="en_PH">

    <!-- ═══ TWITTER CARD ════════════════════════════════════════════════════ -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:url"         content="<?= $og_url_safe ?>">
    <meta name="twitter:title"       content="<?= $og_title_safe ?>">
    <meta name="twitter:description" content="<?= $og_description_safe ?>">
    <meta name="twitter:image"       content="<?= $og_image_safe ?>">

    <!-- ═══ CANONICAL ═══════════════════════════════════════════════════════ -->
    <link rel="canonical" href="<?= $og_url_safe ?>">

    <!-- Redirect humans after a tiny delay (crawlers ignore JS & meta refresh) -->
    <meta http-equiv="refresh" content="0;url=<?= $redirect_url_safe ?>">
</head>
<body>
    <p>Redirecting to product page... 
       <a href="<?= $redirect_url_safe ?>">Click here if not redirected</a>.
    </p>
    <script>
        // JS redirect as extra safety for humans
        window.location.replace("<?= addslashes($redirect_url) ?>");
    </script>
</body>
</html>