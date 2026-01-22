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
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15; // Increased limit for more suggestions

// Prepare the search query - improved for single character searches
if (strlen($searchQuery) === 1) {
    // For single character, use starts with matching for better results
    $query = "SELECT DISTINCT 
                p.product_id,
                p.product_name,
                p.product_description,
                c.category_name,
                v.variant_name
              FROM products p
              LEFT JOIN product_categories c ON p.product_category = c.category_id
              LEFT JOIN product_variants v ON p.product_id = v.product_id
              WHERE (p.product_name LIKE ? 
                     OR p.product_description LIKE ? 
                     OR c.category_name LIKE ? 
                     OR v.variant_name LIKE ?)
                AND p.is_deleted = 0
              GROUP BY p.product_id
              ORDER BY 
                CASE 
                  WHEN p.product_name LIKE ? THEN 1
                  WHEN v.variant_name LIKE ? THEN 2
                  WHEN c.category_name LIKE ? THEN 3
                  ELSE 4
                END
              LIMIT ?";
    
    $searchTerm = $searchQuery . "%";
    $searchTermAny = "%" . $searchQuery . "%";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi", 
        $searchTerm,
        $searchTermAny,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $limit
    );
} else {
    $query = "SELECT DISTINCT 
                p.product_id,
                p.product_name,
                p.product_description,
                c.category_name,
                v.variant_name
              FROM products p
              LEFT JOIN product_categories c ON p.product_category = c.category_id
              LEFT JOIN product_variants v ON p.product_id = v.product_id
              WHERE (p.product_name LIKE ? 
                     OR p.product_description LIKE ? 
                     OR c.category_name LIKE ? 
                     OR v.variant_name LIKE ?)
                AND p.is_deleted = 0
              GROUP BY p.product_id
              ORDER BY 
                CASE 
                  WHEN p.product_name LIKE ? THEN 1
                  WHEN v.variant_name LIKE ? THEN 2
                  WHEN c.category_name LIKE ? THEN 3
                  ELSE 4
                END
              LIMIT ?";
    
    $searchTerm = "%" . $searchQuery . "%";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $limit
    );
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // Truncate description for display
    $description = $row['product_description'];
    if (strlen($description) > 100) {
        $description = substr($description, 0, 100) . '...';
    }
    
    $products[] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'description' => $description,
        'category' => $row['category_name'],
        'variant' => $row['variant_name']
    ];
}

// If we got less than 5 results with starts-with, try contains for single character
if (strlen($searchQuery) === 1 && count($products) < 5) {
    $fallbackQuery = "SELECT DISTINCT 
                        p.product_id,
                        p.product_name,
                        p.product_description,
                        c.category_name,
                        v.variant_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.product_category = c.category_id
                      LEFT JOIN product_variants v ON p.product_id = v.product_id
                      WHERE (p.product_name LIKE ? 
                             OR p.product_description LIKE ? 
                             OR c.category_name LIKE ? 
                             OR v.variant_name LIKE ?)
                        AND p.is_deleted = 0
                        AND p.product_id NOT IN (" . implode(',', array_column($products, 'id')) . ")
                      GROUP BY p.product_id
                      LIMIT ?";
    
    $searchTermAny = "%" . $searchQuery . "%";
    $fallbackLimit = 10 - count($products);
    
    if ($fallbackLimit > 0) {
        $fallbackStmt = $conn->prepare($fallbackQuery);
        $fallbackStmt->bind_param("ssssi", 
            $searchTermAny, 
            $searchTermAny, 
            $searchTermAny, 
            $searchTermAny, 
            $fallbackLimit
        );
        $fallbackStmt->execute();
        $fallbackResult = $fallbackStmt->get_result();
        
        while ($row = $fallbackResult->fetch_assoc()) {
            $description = $row['product_description'];
            if (strlen($description) > 100) {
                $description = substr($description, 0, 100) . '...';
            }
            
            $products[] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'description' => $description,
                'category' => $row['category_name'],
                'variant' => $row['variant_name']
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