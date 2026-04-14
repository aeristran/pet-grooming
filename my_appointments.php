<?php
session_start();
require_once "config.php";

/* Only CUSTOMER can access */
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "CUSTOMER") {
    header("location: login.php");
    exit;
}

/* Make sure user_id exists in session */
if (!isset($_SESSION["user_id"])) {
    die("User ID not found in session. Please log in again.");
}

$user_id = $_SESSION["user_id"];
$appointments = [];

$sql = "SELECT
            a.appointment_id,
            a.appointment_date,
            a.start_time,
            a.end_time,
            a.status,
            a.total_price,
            p.pet_name,
            p.breed
        FROM appointments a
        LEFT JOIN pets p ON a.pet_id = p.pet_id
        WHERE a.customer_id = ?
        ORDER BY a.appointment_date ASC, a.start_time ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
            min-height: 100vh;
        }

        .page {
            padding: 40px 20px;
        }

        .card {
            max-width: 1100px;
            margin: auto;
            background: rgba(255,255,255,0.96);
            border-radius: 24px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            padding: 30px;
        }
        .accepted {
        background: #d1ecf1;
        color: #0c5460;
        }

        .header h1 {
            margin: 0;
            font-size: 38px;
        }

        .header p {
            margin-top: 10px;
            font-size: 17px;
        }

        .body {
            padding: 30px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .scheduled {
            background: #fff3cd;
            color: #856404;
        }

        .completed {
            background: #d4edda;
            color: #155724;
        }

        .cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .default-status {
            background: #e2e3e5;
            color: #383d41;
        }

        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="card">
        <div class="header">
            <h1>My Appointments</h1>
            <p>View the status of your pet grooming appointments.</p>
        </div>

        <div class="body">
            <a href="customer_dashboard.php" class="w3-button w3-gray back-btn">Back to Dashboard</a>

            <?php if (empty($appointments)) { ?>
                <div class="w3-panel w3-yellow w3-round">
                    <p>You have no appointments yet.</p>
                </div>
            <?php } else { ?>
                <table class="w3-table w3-bordered w3-striped w3-white">
                    <tr class="w3-teal">
                        <th>ID</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
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
                            <td><?php echo htmlspecialchars($appt["pet_name"]); ?></td>
                            <td><?php echo htmlspecialchars($appt["breed"]); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format((float)$appt["total_price"], 2)); ?></td>
                           <td>
    <?php
        $status = strtoupper(trim($appt["status"]));
        $status_class = "default-status";
        $status_label = $status;

        if ($status == "SCHEDULED") {
            $status_class = "scheduled";
            $status_label = "PENDING";
        } elseif ($status == "ACCEPTED") {
            $status_class = "accepted";
            $status_label = "ACCEPTED";
        } elseif ($status == "COMPLETED") {
            $status_class = "completed";
            $status_label = "COMPLETED";
        } elseif ($status == "CANCELLED") {
            $status_class = "cancelled";
            $status_label = "CANCELLED";
        }
    ?>
    <span class="status-badge <?php echo $status_class; ?>">
        <?php echo htmlspecialchars($status_label); ?>
    </span>
</td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

</body>
</html>