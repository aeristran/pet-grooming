<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'CUSTOMER') {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-light-grey">
<div class="w3-container w3-white w3-margin w3-padding w3-round-large">
    <h1>Customer Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
    <a class="w3-button w3-green w3-margin-right" href="addPet.php">Add Pet</a>
    <a class="w3-button w3-blue w3-margin-right" href="book_appointment.php">Book Appointment</a>
    <a class="w3-button w3-red" href="logout.php">Logout</a>
</div>
</body>
</html>