<?php
session_start();
require_once __DIR__ . '/../../conn.php';

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

// Add Rider - ENHANCED VERSION with debugging
if (isset($_POST['add_rider'])) {
    // Get all POST data with proper sanitization
    $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_plate_number = trim($_POST['vehicle_plate_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    
    // Enhanced validation with specific error messages
    $errors = [];
    
    if ($account_id <= 0) {
        $errors[] = "Please select a valid account";
    }
    if (empty($vehicle_type)) {
        $errors[] = "Vehicle type is required";
    }
    if (empty($license_number)) {
        $errors[] = "License number is required";
    }
    
    if (!empty($errors)) {
        $errorMessage = implode(", ", $errors);
        redirectWithMessage("../riders.php", $errorMessage, "error");
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        $checkAccountQuery = "SELECT role FROM accounts WHERE account_id = ?";
        $accountStmt = $conn->prepare($checkAccountQuery);
        $accountStmt->bind_param("i", $account_id);
        $accountStmt->execute();
        $accountResult = $accountStmt->get_result();
        
        if ($accountResult->num_rows === 0) {
            throw new Exception("Selected account does not exist.");
        }
        
        $account = $accountResult->fetch_assoc();
        if (in_array($account['role'], ['admin', 'super_admin'])) {
            throw new Exception("Admin accounts cannot be made riders.");
        }
        
        // Check if account is already a rider
        $checkRiderQuery = "SELECT * FROM riders WHERE account_id = ?";
        $riderStmt = $conn->prepare($checkRiderQuery);
        $riderStmt->bind_param("i", $account_id);
        $riderStmt->execute();
        $riderResult = $riderStmt->get_result();
        
        if ($riderResult->num_rows > 0) {
            throw new Exception("This account is already registered as a rider.");
        }
        
        // Update account role to rider
        $updateRoleQuery = "UPDATE accounts SET role = 'rider' WHERE account_id = ?";
        $updateStmt = $conn->prepare($updateRoleQuery);
        $updateStmt->bind_param("i", $account_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update account role.");
        }
        
        // Insert new rider
        $insertQuery = "INSERT INTO riders (account_id, vehicle_type, vehicle_plate_number, license_number, is_available) 
                        VALUES (?, ?, ?, ?, 1)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("isss", $account_id, $vehicle_type, $vehicle_plate_number, $license_number);
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to create rider record.");
        }
        
        // Commit transaction
        $conn->commit();
        redirectWithMessage("../riders.php", "Rider added successfully!", "success");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        redirectWithMessage("../riders.php", $e->getMessage(), "error");
    }
}

// Edit Rider
elseif (isset($_POST['edit_rider'])) {
    $rider_id = (int)$_POST['rider_id'];
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_plate_number = trim($_POST['vehicle_plate_number'] ?? '');
    $license_number = trim($_POST['license_number']);
    $is_available = (int)$_POST['is_available'];
    
    // Validate inputs
    if (empty($rider_id) || empty($vehicle_type) || empty($license_number)) {
        redirectWithMessage("../riders.php", "All required fields must be filled.", "error");
    }
    
    // Validate availability status change
    if ($is_available == 0) {
        // Check if rider has active deliveries before marking as unavailable
        $checkActiveQuery = "SELECT COUNT(*) as active_count FROM orders 
                            WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'";
        $checkStmt = $conn->prepare($checkActiveQuery);
        $checkStmt->bind_param("i", $rider_id);
        $checkStmt->execute();
        $activeCount = $checkStmt->get_result()->fetch_assoc()['active_count'];
        
        if ($activeCount > 0) {
            redirectWithMessage("../riders.php", 
                "Cannot mark rider as unavailable while they have active deliveries. Please wait for deliveries to complete.", 
                "error");
        }
    }
    
    // Update rider
    $updateQuery = "UPDATE riders 
                    SET vehicle_type = ?, vehicle_plate_number = ?, license_number = ?, 
                        is_available = ?, updated_at = NOW() 
                    WHERE rider_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssii", $vehicle_type, $vehicle_plate_number, $license_number, $is_available, $rider_id);
    
    if ($stmt->execute()) {
        redirectWithMessage("../riders.php", "Rider updated successfully!", "success");
    } else {
        redirectWithMessage("../riders.php", "Failed to update rider.", "error");
    }
}

// Remove Rider
elseif (isset($_GET['delete_rider'])) {
    $rider_id = (int)$_GET['delete_rider'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if rider has active deliveries
        $checkOrdersQuery = "SELECT COUNT(*) as order_count FROM orders 
                            WHERE assigned_rider_id = ? AND order_status = 'OutForDelivery'";
        $checkStmt = $conn->prepare($checkOrdersQuery);
        $checkStmt->bind_param("i", $rider_id);
        $checkStmt->execute();
        $orderCount = $checkStmt->get_result()->fetch_assoc()['order_count'];
        
        if ($orderCount > 0) {
            throw new Exception("Cannot remove rider with active deliveries. Please wait for all deliveries to complete.");
        }
        
        // Get account ID before deleting rider
        $getAccountQuery = "SELECT account_id FROM riders WHERE rider_id = ?";
        $getStmt = $conn->prepare($getAccountQuery);
        $getStmt->bind_param("i", $rider_id);
        $getStmt->execute();
        $result = $getStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Rider not found.");
        }
        
        $account_id = $result->fetch_assoc()['account_id'];
        
        // Remove rider from riders table
        $deleteQuery = "DELETE FROM riders WHERE rider_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $rider_id);
        
        if (!$deleteStmt->execute()) {
            throw new Exception("Failed to remove rider record.");
        }
        
        // Update account role back to customer (or guest if they never completed profile)
        $updateRoleQuery = "UPDATE accounts SET role = 'guest' WHERE account_id = ?";
        $updateStmt = $conn->prepare($updateRoleQuery);
        $updateStmt->bind_param("i", $account_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update account role.");
        }
        
        // Commit transaction
        $conn->commit();
        redirectWithMessage("../riders.php", "Rider removed successfully! Account role reverted to guest.", "success");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        redirectWithMessage("../riders.php", $e->getMessage(), "error");
    }
}

// Invalid request
else {
    redirectWithMessage("../riders.php", "Invalid request.", "error");
}

$conn->close();
?>