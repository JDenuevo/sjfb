
<?php
// At the VERY TOP of the file - before any output
header('Content-Type: application/json');
session_start();

// Disable error display in production, enable only for debugging
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Process updates
    $updated = false;
    foreach ($data as $update) {
        $productId = filter_var($update['product_id'] ?? 0, FILTER_VALIDATE_INT);
        $variantId = filter_var($update['variant_id'] ?? 0, FILTER_VALIDATE_INT);
        $quantity = filter_var($update['quantity'] ?? 0, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($productId === false || $variantId === false || $quantity === false) {
            continue; // Skip invalid items
        }

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $productId && $item['variant_id'] == $variantId) {
                $item['quantity'] = $quantity;
                $updated = true;
                break;
            }
        }
    }

    if ($updated) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Cart updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No matching items found to update'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;