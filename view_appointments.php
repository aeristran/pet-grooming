<?php
session_start();
require_once "config.php";

/* Only ADMIN can access */
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "ADMIN") {
    header("location: login.php");
    exit;
}

$appointments = [];

$sql = "SELECT
            a.appointment_id,
            a.appointment_date,
            a.start_time,
            a.end_time,
            a.status,
            a.total_price,
            u.first_name,
            u.last_name,
            p.pet_name,
            p.breed
        FROM appointments a
        LEFT JOIN users u ON a.customer_id = u.user_id
        LEFT JOIN pets p ON a.pet_id = p.pet_id
        ORDER BY a.appointment_date ASC, a.start_time ASC";

$result = mysqli_query($link, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            width: 1100px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        h2 {
            margin-top: 0;
        }

        table {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <h2>All Appointments</h2>
        <p>Admin can view all appointments here.</p>

        <div class="w3-margin-bottom">
            <a href="admin_dashboard.php" class="w3-button w3-gray">Back to Dashboard</a>
        </div>

        <?php if (empty($appointments)) { ?>
            <div class="w3-panel w3-yellow w3-round">
                <p>No appointments found.</p>
            </div>
        <?php } else { ?>
            <table class="w3-table w3-bordered w3-striped w3-white">
                <tr class="w3-teal">
                    <th>ID</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Customer</th>
                    <th>Pet</th>
                    <th>Breed</th>
                    <th>Total Price</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($appointments as $appt) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appt["appointment_id"]); ?></td>
                        <td><?php echo htmlspecialchars($appt["appointment_date"]); ?></td>
                        <td><?php echo htmlspecialchars($appt["start_time"]); ?></td>
                        <td><?php echo htmlspecialchars($appt["end_time"]); ?></td>
                        <td>
                            <?php echo htmlspecialchars($appt["first_name"] . " " . $appt["last_name"]); ?>
                        </td>
                        <td><?php echo htmlspecialchars($appt["pet_name"]); ?></td>
                        <td><?php echo htmlspecialchars($appt["breed"]); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format((float)$appt["total_price"], 2)); ?></td>
                        <td><?php echo htmlspecialchars($appt["status"]); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</div>

</body>
</html>