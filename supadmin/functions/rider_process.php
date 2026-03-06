<?php
// ==================== admin/functions/rider_process.php ====================
session_start();
require_once __DIR__ . '/../../conn.php';
require_once 'activity_log_helper.php'; // ← shared logger

function redirectWithMessage($location, $message, $type) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: $location");
    exit();
}

['userId' => $actorId, 'userType' => $actorType] = getActorFromSession();

// ── ADD RIDER ─────────────────────────────────────────────────────────────────
if (isset($_POST['add_rider'])) {
    $account_id          = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
    $vehicle_type        = trim($_POST['vehicle_type'] ?? '');
    $vehicle_plate_number= trim($_POST['vehicle_plate_number'] ?? '');
    $license_number      = trim($_POST['license_number'] ?? '');

    $errors = [];
    if ($account_id <= 0)        $errors[] = "Please select a valid account";
    if (empty($vehicle_type))    $errors[] = "Vehicle type is required";
    if (empty($license_number))  $errors[] = "License number is required";
    if (!empty($errors)) redirectWithMessage("../riders.php", implode(", ", $errors), "error");

    $conn->begin_transaction();
    try {
        $ck = $conn->prepare("SELECT role FROM accounts WHERE account_id=?");
        $ck->bind_param("i",$account_id); $ck->execute();
        $acc = $ck->get_result()->fetch_assoc(); $ck->close();
        if (!$acc) throw new Exception("Selected account does not exist.");
        if (in_array($acc['role'],['admin','super_admin'])) throw new Exception("Admin accounts cannot be made riders.");

        $cr = $conn->prepare("SELECT * FROM riders WHERE account_id=?");
        $cr->bind_param("i",$account_id); $cr->execute();
        if ($cr->get_result()->num_rows > 0) throw new Exception("This account is already registered as a rider.");
        $cr->close();

        $ur = $conn->prepare("UPDATE accounts SET role='rider' WHERE account_id=?");
        $ur->bind_param("i",$account_id);
        if (!$ur->execute()) throw new Exception("Failed to update account role.");

        $ir = $conn->prepare("INSERT INTO riders (account_id,vehicle_type,vehicle_plate_number,license_number,is_available) VALUES (?,?,?,?,1)");
        $ir->bind_param("isss",$account_id,$vehicle_type,$vehicle_plate_number,$license_number);
        if (!$ir->execute()) throw new Exception("Failed to create rider record.");
        $new_rider_id = $conn->insert_id;

        // Fetch name for log
        $na = $conn->prepare("SELECT first_name, last_name, email FROM accounts WHERE account_id=?");
        $na->bind_param("i",$account_id); $na->execute();
        $nameData = $na->get_result()->fetch_assoc(); $na->close();

        logActivity($conn,'rider',$new_rider_id,'Rider created',
            null,
            json_encode(['vehicle_type'=>$vehicle_type,'plate'=>$vehicle_plate_number,'license'=>$license_number]),
            "Rider profile created for account ID {$account_id} ({$nameData['first_name']} {$nameData['last_name']}). Vehicle: {$vehicle_type}",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../riders.php","Rider added successfully!","success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../riders.php",$e->getMessage(),"error");
    }
}

// ── EDIT RIDER ────────────────────────────────────────────────────────────────
elseif (isset($_POST['edit_rider'])) {
    $rider_id            = intval($_POST['rider_id']);
    $vehicle_type        = trim($_POST['vehicle_type']);
    $vehicle_plate_number= trim($_POST['vehicle_plate_number'] ?? '');
    $license_number      = trim($_POST['license_number']);
    $is_available        = intval($_POST['is_available']);

    if (empty($rider_id)||empty($vehicle_type)||empty($license_number)) {
        redirectWithMessage("../riders.php","All required fields must be filled.","error");
    }

    if ($is_available == 0) {
        $ca = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE assigned_rider_id=? AND order_status='OutForDelivery'");
        $ca->bind_param("i",$rider_id); $ca->execute();
        $actCnt = $ca->get_result()->fetch_assoc()['cnt']; $ca->close();
        if ($actCnt > 0) redirectWithMessage("../riders.php","Cannot mark rider as unavailable while they have active deliveries.","error");
    }

    // Fetch old values for log
    $ov = $conn->prepare("SELECT vehicle_type, vehicle_plate_number, license_number, is_available FROM riders WHERE rider_id=?");
    $ov->bind_param("i",$rider_id); $ov->execute();
    $oldRider = $ov->get_result()->fetch_assoc(); $ov->close();

    $stmt = $conn->prepare("UPDATE riders SET vehicle_type=?,vehicle_plate_number=?,license_number=?,is_available=?,updated_at=NOW() WHERE rider_id=?");
    $stmt->bind_param("sssii",$vehicle_type,$vehicle_plate_number,$license_number,$is_available,$rider_id);
    if ($stmt->execute()) {
        $availText = $is_available ? 'available' : 'unavailable';
        logActivity($conn,'rider',$rider_id,'Rider updated',
            json_encode($oldRider),
            json_encode(['vehicle_type'=>$vehicle_type,'plate'=>$vehicle_plate_number,'is_available'=>$is_available]),
            "Rider ID {$rider_id} updated. Availability: {$availText} | Vehicle: {$vehicle_type} ({$vehicle_plate_number})",
            $actorId, $actorType
        );
        redirectWithMessage("../riders.php","Rider updated successfully!","success");
    } else {
        redirectWithMessage("../riders.php","Failed to update rider.","error");
    }
}

// ── REMOVE RIDER ──────────────────────────────────────────────────────────────
elseif (isset($_GET['delete_rider'])) {
    $rider_id = intval($_GET['delete_rider']);

    $conn->begin_transaction();
    try {
        $co = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE assigned_rider_id=? AND order_status='OutForDelivery'");
        $co->bind_param("i",$rider_id); $co->execute();
        $oc = $co->get_result()->fetch_assoc()['cnt']; $co->close();
        if ($oc > 0) throw new Exception("Cannot remove rider with active deliveries.");

        $ga = $conn->prepare("SELECT r.account_id, a.first_name, a.last_name, a.email, r.vehicle_type FROM riders r JOIN accounts a ON r.account_id=a.account_id WHERE r.rider_id=?");
        $ga->bind_param("i",$rider_id); $ga->execute();
        $riderInfo = $ga->get_result()->fetch_assoc(); $ga->close();
        if (!$riderInfo) throw new Exception("Rider not found.");
        $account_id = $riderInfo['account_id'];

        $dr = $conn->prepare("DELETE FROM riders WHERE rider_id=?");
        $dr->bind_param("i",$rider_id);
        if (!$dr->execute()) throw new Exception("Failed to remove rider record.");

        $ur = $conn->prepare("UPDATE accounts SET role='guest' WHERE account_id=?");
        $ur->bind_param("i",$account_id);
        if (!$ur->execute()) throw new Exception("Failed to update account role.");

        logActivity($conn,'rider',$rider_id,'Rider removed',
            json_encode(['rider_id'=>$rider_id,'name'=>"{$riderInfo['first_name']} {$riderInfo['last_name']}",'vehicle'=>$riderInfo['vehicle_type']]),
            null,
            "Rider ID {$rider_id} ({$riderInfo['first_name']} {$riderInfo['last_name']}) removed. Account ID {$account_id} role reverted to guest.",
            $actorId, $actorType
        );

        $conn->commit();
        redirectWithMessage("../riders.php","Rider removed successfully! Account role reverted to guest.","success");
    } catch (Exception $e) {
        $conn->rollback();
        redirectWithMessage("../riders.php",$e->getMessage(),"error");
    }
} else {
    redirectWithMessage("../riders.php","Invalid request.","error");
}

$conn->close();