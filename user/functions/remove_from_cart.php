<?php
header('Content-Type: application/json');
session_start();

error_reporting(0);
ini_set('display_errors', 0);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    $productId = filter_var($data['product_id'] ?? 0, FILTER_VALIDATE_INT);
    $variantId = filter_var($data['variant_id'] ?? 0, FILTER_VALIDATE_INT);

    if ($productId === false || $variantId === false) {
        throw new Exception('Invalid product or variant ID');
    }

    if (isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId, $variantId) {
            return !($item['product_id'] == $productId && $item['variant_id'] == $variantId);
        });
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from cart'
        ]);
    } else {
        throw new Exception('Cart is empty');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;