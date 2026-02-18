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
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

// Prepare the search query - now uses product_category_links
if (strlen($searchQuery) === 1) {
    // For single character, prioritize starts with
    $query = "SELECT DISTINCT 
                p.*,
                GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') as category_names,
                v.variant_name,
                p.product_nickname
              FROM products p
              LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
              LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
              LEFT JOIN product_variants v ON p.product_id = v.product_id
              WHERE (p.product_name LIKE ? 
                     OR p.product_unit LIKE ? 
                     OR pc.category_name LIKE ? 
                     OR v.variant_name LIKE ?
                     OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)
                AND p.is_deleted = 0
              GROUP BY p.product_id
              ORDER BY 
                CASE 
                  WHEN p.product_name LIKE ? THEN 1
                  WHEN v.variant_name LIKE ? THEN 2
                  WHEN JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL THEN 3
                  WHEN pc.category_name LIKE ? THEN 4
                  ELSE 5
                END
              LIMIT ?";
    
    $searchTerm = $searchQuery . "%";
    $searchTermAny = "%" . $searchQuery . "%";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssi", 
        $searchTerm,
        $searchTermAny,
        $searchTerm,
        $searchTerm,
        $searchQuery,
        $searchTerm,
        $searchTerm,
        $searchQuery,
        $searchTerm,
        $limit
    );
} else {
    // For multi-character, use contains
    $query = "SELECT DISTINCT 
                p.*,
                GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') as category_names,
                v.variant_name,
                p.product_nickname
              FROM products p
              LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
              LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
              LEFT JOIN product_variants v ON p.product_id = v.product_id
              WHERE (p.product_name LIKE ? 
                     OR p.product_unit LIKE ? 
                     OR pc.category_name LIKE ? 
                     OR v.variant_name LIKE ?
                     OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL
                AND p.is_deleted = 0
              GROUP BY p.product_id
              ORDER BY 
                CASE 
                  WHEN p.product_name LIKE ? THEN 1
                  WHEN v.variant_name LIKE ? THEN 2
                  WHEN JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL THEN 3
                  WHEN pc.category_name LIKE ? THEN 4
                  ELSE 5
                END
              LIMIT ?";
    
    $searchTerm = "%" . $searchQuery . "%";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssssi",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchQuery,
        $searchTerm,
        $searchTerm,
        $searchQuery,
        $searchTerm,
        $limit
    );
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // Truncate unit for display
    $unit = $row['product_unit'] ?? '';
    if (strlen($unit) > 100) {
        $unit = substr($unit, 0, 100) . '...';
    }
    
    // Format nickname tags
    $tags = [];
    if (!empty($row['product_nickname'])) {
        $nicknameData = json_decode($row['product_nickname'], true);
        if (is_array($nicknameData)) {
            // Limit to first 3 tags for display
            $tags = array_slice($nicknameData, 0, 3);
        }
    }
    
    // Determine match type for single character searches
    $matchType = '';
    $matchClass = '';
    if (strlen($searchQuery) === 1) {
        if (stripos($row['product_name'], $searchQuery) === 0) {
            $matchType = 'Name starts with';
            $matchClass = 'bg-green-100 text-green-800';
        } elseif (!empty($row['variant_name']) && stripos($row['variant_name'], $searchQuery) === 0) {
            $matchType = 'Variant starts with';
            $matchClass = 'bg-blue-100 text-blue-800';
        } elseif (!empty($tags) && preg_grep('/^' . preg_quote($searchQuery, '/') . '/i', $tags)) {
            $matchType = 'Tag starts with';
            $matchClass = 'bg-purple-100 text-purple-800';
        }
    }
    
    $products[] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'unit' => $unit,
        'category' => $row['category_names'] ?? 'General',
        'variant' => $row['variant_name'] ?? '',
        'tags' => $tags,
        'match_type' => $matchType,
        'match_class' => $matchClass
    ];
}

// If we got less than 5 results with current method, try contains for all fields
if (count($products) < 5) {
    $fallbackQuery = "SELECT DISTINCT 
                        p.*,
                        GROUP_CONCAT(DISTINCT pc.category_name SEPARATOR ', ') as category_names,
                        v.variant_name,
                        p.product_nickname
                      FROM products p
                      LEFT JOIN product_category_links pcl ON p.product_id = pcl.product_id
                      LEFT JOIN product_categories pc ON pcl.category_id = pc.category_id
                      LEFT JOIN product_variants v ON p.product_id = v.product_id
                      WHERE (p.product_name LIKE ? 
                             OR p.product_unit LIKE ? 
                             OR pc.category_name LIKE ? 
                             OR v.variant_name LIKE ?
                             OR JSON_SEARCH(LOWER(p.product_nickname), 'all', LOWER(?)) IS NOT NULL)
                        AND p.is_deleted = 0
                        AND p.product_id NOT IN (" . implode(',', array_column($products, 'id')) . ")
                      GROUP BY p.product_id
                      LIMIT ?";
    
    $searchTermAny = "%" . $searchQuery . "%";
    $fallbackLimit = 10 - count($products);
    
    if ($fallbackLimit > 0) {
        $fallbackStmt = $conn->prepare($fallbackQuery);
        $fallbackStmt->bind_param("sssssi", 
            $searchTermAny, 
            $searchTermAny, 
            $searchTermAny, 
            $searchTermAny, 
            $searchQuery,
            $fallbackLimit
        );
        $fallbackStmt->execute();
        $fallbackResult = $fallbackStmt->get_result();
        
        while ($row = $fallbackResult->fetch_assoc()) {
            $unit = $row['product_unit'] ?? '';
            if (strlen($unit) > 100) {
                $unit = substr($unit, 0, 100) . '...';
            }
            
            // Format nickname tags
            $tags = [];
            if (!empty($row['product_nickname'])) {
                $nicknameData = json_decode($row['product_nickname'], true);
                if (is_array($nicknameData)) {
                    $tags = array_slice($nicknameData, 0, 3);
                }
            }
            
            $products[] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'unit' => $unit,
                'category' => $row['category_names'] ?? 'General',
                'variant' => $row['variant_name'] ?? '',
                'tags' => $tags,
                'match_type' => '',
                'match_class' => ''
            ];
        }
        $fallbackStmt->close();
    }
}

echo json_encode($products);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>