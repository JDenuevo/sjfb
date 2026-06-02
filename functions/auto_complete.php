<?php
// auto_complete.php
session_start();
require_once '../conn.php';

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit();
}

$searchQuery = trim($_GET['query']);
$limit       = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

if (empty($searchQuery)) {
    echo json_encode([]);
    exit();
}

// ── Build search terms ────────────────────────────────────────────────────────
$termStart = $searchQuery . '%';
$termAny   = '%' . $searchQuery . '%';

// ── Single shared query — works for both 1-char and multi-char ────────────────
// Priority: exact name start > any name match > variant > nickname/tag > category
$sql = "
    SELECT DISTINCT
        p.product_id,
        p.product_name,
        p.product_unit,
        p.product_nickname,
        p.is_hidden,
        GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') AS category_names,
        MIN(v.variant_name) AS variant_name
    FROM products p
    LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
    LEFT JOIN product_categories pc      ON pcl.category_id = pc.category_id
    LEFT JOIN product_variants v         ON p.product_id = v.product_id AND v.is_deleted = 0
    WHERE (
        p.product_name   LIKE ?
        OR p.product_name   LIKE ?
        OR p.product_unit   LIKE ?
        OR pc.category_name LIKE ?
        OR v.variant_name   LIKE ?
        OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL
    )
    AND p.is_deleted = 0
    AND p.is_hidden  = 0
    GROUP BY p.product_id
    ORDER BY
        CASE
            WHEN p.product_name   LIKE ? THEN 1
            WHEN p.product_name   LIKE ? THEN 2
            WHEN v.variant_name   LIKE ? THEN 3
            WHEN JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL THEN 4
            WHEN pc.category_name LIKE ? THEN 5
            ELSE 6
        END,
        p.product_name ASC
    LIMIT ?
";

// WHERE binds:    termStart, termAny, termAny, termAny, termAny, searchQuery
// ORDER BY binds: termStart, termAny, termAny, searchQuery, termAny
// LIMIT bind:     limit
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param(
    'sssssssssssi',
    $termStart,   // WHERE name LIKE 'x%'
    $termAny,     // WHERE name LIKE '%x%'
    $termAny,     // WHERE product_unit
    $termAny,     // WHERE category_name
    $termAny,     // WHERE variant_name
    $searchQuery, // WHERE JSON_SEARCH nickname
    $termStart,   // ORDER name starts
    $termAny,     // ORDER name contains
    $termAny,     // ORDER variant
    $searchQuery, // ORDER nickname
    $termAny,     // ORDER category
    $limit        // LIMIT
);

$stmt->execute();
$result = $stmt->get_result();

// ── Format results ────────────────────────────────────────────────────────────
$products = [];

while ($row = $result->fetch_assoc()) {

    $unit = $row['product_unit'] ?? '';
    if (strlen($unit) > 100) $unit = substr($unit, 0, 100) . '...';

    // Parse nickname JSON tags
    $tags = [];
    if (!empty($row['product_nickname'])) {
        $decoded = json_decode($row['product_nickname'], true);
        if (is_array($decoded)) {
            $tags = array_slice($decoded, 0, 3);
        }
    }

    // Match type label for UI hints
    $matchType  = '';
    $matchClass = '';
    $nameLower  = strtolower($row['product_name']);
    $qLower     = strtolower($searchQuery);

    if (strpos($nameLower, $qLower) === 0) {
        $matchType  = 'Name match';
        $matchClass = 'bg-green-100 text-green-800';
    } elseif (!empty($row['variant_name']) && stripos($row['variant_name'], $searchQuery) !== false) {
        $matchType  = 'Variant match';
        $matchClass = 'bg-blue-100 text-blue-800';
    } elseif (!empty($tags) && preg_grep('/' . preg_quote($searchQuery, '/') . '/i', $tags)) {
        $matchType  = 'Tag match';
        $matchClass = 'bg-purple-100 text-purple-800';
    } elseif (!empty($row['category_names']) && stripos($row['category_names'], $searchQuery) !== false) {
        $matchType  = 'Category match';
        $matchClass = 'bg-yellow-100 text-yellow-800';
    }

    $products[] = [
        'id'         => $row['product_id'],
        'name'       => $row['product_name'],
        'unit'       => $unit,
        'category'   => $row['category_names'] ?? 'General',
        'variant'    => $row['variant_name']   ?? '',
        'tags'       => $tags,
        'match_type' => $matchType,
        'match_class' => $matchClass,
    ];
}

$stmt->close();
$conn->close();

echo json_encode($products);
?>