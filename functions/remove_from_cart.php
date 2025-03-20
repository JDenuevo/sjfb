<?php
session_start();
include '../conn.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit();
    }

    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($data) {
        return $item['product_id'] != $data['product_id'];
    });

    echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>