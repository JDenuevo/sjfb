<?php
session_start();
include '../../conn.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION["loggedinassupadmin"]) || $_SESSION["loggedinassupadmin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

if ($variant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
    exit;
}

$conn->begin_transaction();

try {
    // Step 1: Delete variant categories first (FK constraint)
    $stmt = $conn->prepare("DELETE FROM product_variants_categories WHERE variant_id = ?");
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $stmt->close();

    // Step 2: Delete the variant itself
    $stmt = $conn->prepare("DELETE FROM product_variants WHERE variant_id = ?");
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Variant not found or already deleted.");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Variant deleted successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>