<?php
/**
 * functions/fetch_products.php
 * Called by AJAX from product_process.js
 * Returns only the product card HTML (no layout, no sidebar).
 */
session_start();
include '../conn.php';

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/sjfbi-js/';
$fp_search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ── Build query ───────────────────────────────────────────────────────────────
$fp_query = "SELECT 
        p.product_id, p.product_name, p.product_unit, p.product_nickname,
        pi.image_path, 
        v.variant_id, v.variant_name, v.variant_price, v.discount_price,
        v.unit_type, v.minimum_order, v.order_increment, v.stock_quantity,
        GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS category_names
      FROM products p
      LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
      LEFT JOIN product_variants v ON p.product_id = v.product_id AND v.is_deleted = 0
      LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
      LEFT JOIN product_categories c ON pcl.category_id = c.category_id AND c.is_active = 1
      WHERE p.is_deleted = 0 AND p.is_hidden = 0";

$fp_params = []; $fp_types = '';

// ── Category filter (slug-based) ─────────────────────────────────────────────
if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
    $slugs = array_filter(array_map('trim', explode(',', $_GET['category'])));
    if (!empty($slugs)) {
        $fp_slugStr = implode(',', array_fill(0, count($slugs), '?'));
        $fp_idQuery = "SELECT category_id FROM product_categories WHERE category_slug IN ($fp_slugStr) AND is_active = 1
                       UNION
                       SELECT pc2.category_id FROM product_categories pc2
                       INNER JOIN product_categories pc1 ON pc2.parent_id = pc1.category_id
                       WHERE pc1.category_slug IN ($fp_slugStr) AND pc2.is_active = 1";
        $fp_idStmt = $conn->prepare($fp_idQuery);
        $fp_allSlugs = array_merge($slugs, $slugs);
        $fp_idStmt->bind_param(str_repeat('s', count($fp_allSlugs)), ...$fp_allSlugs);
        $fp_idStmt->execute();
        $fp_idRes = $fp_idStmt->get_result();
        $fp_catIds = [];
        while ($r = $fp_idRes->fetch_assoc()) $fp_catIds[] = intval($r['category_id']);
        $fp_idStmt->close();

        if (!empty($fp_catIds)) {
            $fp_idPH = implode(',', array_fill(0, count($fp_catIds), '?'));
            $fp_query .= " AND p.product_id IN (SELECT product_id FROM product_category_links WHERE category_id IN ($fp_idPH))";
            $fp_types .= str_repeat('i', count($fp_catIds));
            $fp_params = array_merge($fp_params, $fp_catIds);
        } else {
            $fp_query .= " AND 1=0"; // slug not found → no results
        }
    }
}

// ── Price filter ──────────────────────────────────────────────────────────────
if (!empty($_GET['price'])) {
    switch ($_GET['price']) {
        case 'under200': $fp_query .= " AND v.variant_price < 200"; break;
        case '200-400':  $fp_query .= " AND v.variant_price BETWEEN 200 AND 400"; break;
        case '400-600':  $fp_query .= " AND v.variant_price BETWEEN 400 AND 600"; break;
        case 'over600':  $fp_query .= " AND v.variant_price > 600"; break;
    }
}

// ── Search filter ─────────────────────────────────────────────────────────────
if (!empty($fp_search)) {
    $fp_query .= " AND (p.product_name LIKE ? OR p.product_unit LIKE ? OR c.category_name LIKE ? OR v.variant_name LIKE ? OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)";
    $fp_st = '%' . $fp_search . '%';
    $fp_types .= 'sssss';
    $fp_params = array_merge($fp_params, [$fp_st, $fp_st, $fp_st, $fp_st, $fp_search]);
}

$fp_query .= " GROUP BY p.product_id, v.variant_id ORDER BY p.created_at DESC";

$fp_stmt = $conn->prepare($fp_query);
if (!empty($fp_params)) $fp_stmt->bind_param($fp_types, ...$fp_params);
$fp_stmt->execute();
$fp_result = $fp_stmt->get_result();

// ── Group rows into products array ────────────────────────────────────────────
$fp_products = [];
while ($row = $fp_result->fetch_assoc()) {
    $pid = $row['product_id'];
    if (!isset($fp_products[$pid])) {
        $fp_products[$pid] = [
            'product_name'     => $row['product_name'],
            'product_unit'     => $row['product_unit'],
            'product_nickname' => $row['product_nickname'],
            'image_url'        => !empty($row['image_path'])
                ? $baseUrl . 'uploads/products/' . $row['image_path']
                : $baseUrl . 'uploads/products/default.png',
            'category_names'   => $row['category_names'],
            'variants'         => [],
            'has_stock'        => false,
        ];
    }
    if (!empty($row['variant_id'])) {
        $sq  = intval($row['stock_quantity'] ?? 0);
        $hsk = $sq > 0;
        $fp_products[$pid]['variants'][] = [
            'variant_id'      => $row['variant_id'],
            'variant_name'    => $row['variant_name'],
            'variant_price'   => $row['variant_price'],
            'discount_price'  => $row['discount_price'],
            'unit_type'       => $row['unit_type'] ?? 'piece',
            'minimum_order'   => $row['minimum_order'] ?? 1,
            'order_increment' => $row['order_increment'] ?? 1,
            'stock_quantity'  => $sq,
            'has_stock'       => $hsk,
        ];
        if ($hsk) $fp_products[$pid]['has_stock'] = true;
    }
}
$fp_stmt->close();
$conn->close();
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
<?php include dirname(__DIR__) . '/components/products_card.php'; ?>

</div>