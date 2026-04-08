<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'CUSTOMER') {
    header("location: login.php");
    exit;
}

$customer_id = $_SESSION["user_id"];
$pets = [];

$sql = "SELECT pet_id, pet_name, species, breed, pet_image FROM pets WHERE customer_id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $pets[] = $row;
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        .pet-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .pet-card {
            width: 220px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .pet-card img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    background: #f5f5f5;
}
        .pet-card-body {
            padding: 12px;
        }

        .pet-card img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 10px;
}
    </style>
</head>
<body class="w3-light-grey">
<div class="w3-container w3-white w3-margin w3-padding w3-round-large">
    <h1>Customer Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>

    <a class="w3-button w3-green w3-margin-right" href="addPet.php">Add Pet</a>
    <a class="w3-button w3-blue w3-margin-right" href="book_appointment.php">Book Appointment</a>
    <a class="w3-button w3-red" href="logout.php">Logout</a>

    <hr>

    <h3>Your Pets</h3>

    <?php if (empty($pets)) { ?>
        <p>No pets added yet.</p>
    <?php } else { ?>
        <div class="pet-grid">
            <?php foreach ($pets as $pet) { ?>
                <div class="pet-card">
                    <?php if (!empty($pet["pet_image"])) { ?>
                        <img src="<?php echo htmlspecialchars($pet["pet_image"]); ?>" alt="Pet Image">
                    <?php } else { ?>
                        <img src="https://via.placeholder.com/220x180?text=No+Image" alt="No Image">
                    <?php } ?>
                    <div class="pet-card-body">
                        <h4><?php echo htmlspecialchars($pet["pet_name"]); ?></h4>
                        <p><?php echo htmlspecialchars($pet["species"]); ?></p>
                        <p><?php echo htmlspecialchars($pet["breed"]); ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
</body>
</html>