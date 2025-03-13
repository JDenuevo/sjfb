<?php
session_start();
include '../conn.php';

if (isset($_POST['product_name']) && isset($_POST['size'])) {
    $product_name = $_POST['product_name'];
    $size = $_POST['size'];

    $query = "SELECT product_description, product_price, discount_price 
              FROM products 
              WHERE product_name = ? AND product_size = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ss", $product_name, $size);
        $stmt->execute();
        $stmt->bind_result($product_description, $product_price, $discount_price);
        $stmt->fetch();
        $stmt->close();

        if (empty($product_description) && empty($product_price)) {
            echo json_encode(['error' => 'No matching product found in the database']);
        } else {
            echo json_encode([
                'product_description' => $product_description,
                'product_price' => $product_price,
                'discount_price' => $discount_price
            ]);
        }
    } else {
        echo json_encode(['error' => 'Query preparation failed']);
    }
} else {
    echo json_encode(['error' => 'Invalid input data']);
}
?>
