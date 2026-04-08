<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'ADMIN') {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-light-grey">
    <div class="w3-container w3-white w3-margin w3-padding w3-round-large">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>

        <ul>
            <li><a href="addEmployee.php">Create Employee</a></li>
            <li><a href="manage_services.php">Manage Services</a></li>
            <li><a href="view_all_appointments.php">View All Appointments</a></li>
        </ul>

        <a class="w3-button w3-red" href="logout.php">Logout</a>
    </div>
</body>
</html>