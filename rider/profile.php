<?php
session_start();
require '../conn.php';

// Check if rider is logged in
if (!isset($_SESSION["loggedinasrider"]) || $_SESSION["loggedinasrider"] !== true || !isset($_SESSION['account_id'])) {
    header("Location: ../index.php");
    exit;
}

$account_id = $_SESSION['account_id'];

$query = "SELECT a.*, r.* 
          FROM accounts a
          INNER JOIN riders r ON a.account_id = r.account_id
          WHERE a.account_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riders Profile | St. Joseph Fish Brokerage Inc.</title>

    <!-- Favicons -->
    <link rel="icon" href="../assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
    <link rel="icon" href="../assets/icons/logo.svg" type="image/svg+xml">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link href="../style.css" rel="stylesheet">
    <link href="../output.css" rel="stylesheet">

    <!-- CSS Preline -->
    <link rel="stylesheet" href="https://preline.co/assets/css/main.min.css">
</head>

<body class="bg-gray-100">
    
    <?php include './components/navigation.php'?>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        <?php
            if (!empty($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $alertType = ($message['type'] === 'success') ? 'bg-teal-500 text-white' : 'bg-red-500 text-white';
        
            echo '
            <div class="mt-2 ' . $alertType . ' text-sm rounded-lg p-4" role="alert">
                <span class="font-bold">' . ucfirst($message['type']) . '!</span> ' . $message['text'] . '
            </div>';
        
            // Clear message after displaying it
            unset($_SESSION['message']);
            }
        ?>

        <?php include './components/manage_profile.php'?>
    </div>

    <!-- JS PLUGINS -->
    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>