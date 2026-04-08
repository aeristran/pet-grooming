<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

$role = $_SESSION["role"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body { font: 14px sans-serif; text-align: center; }
    </style>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body>
    <div class="w3-container">
        <br>
        Hi, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b><br>
        Welcome to our Pet Grooming System.<br>
        This page is protected, so only authenticated users can access it.<br><br>

        <b>Your role is:</b> <?php echo htmlspecialchars($role); ?><br><br>

        <?php if ($role === "ADMIN") { ?>
            <a class="w3-button w3-blue w3-margin" href="admin_dashboard.php">Go to Admin Dashboard</a>
        <?php } elseif ($role === "EMPLOYEE") { ?>
            <a class="w3-button w3-green w3-margin" href="employee_dashboard.php">Go to Employee Dashboard</a>
        <?php } else { ?>
            <a class="w3-button w3-teal w3-margin" href="customer_dashboard.php">Go to Customer Dashboard</a>
        <?php } ?>

        <br><br>
        <a href="logout.php">Sign out of your account</a>
    </div>
</body>
</html>