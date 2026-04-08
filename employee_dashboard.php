<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'EMPLOYEE') {
    header("location: login.php");
    exit;
}

$employee_id = $_SESSION["user_id"];

/* Accept appointment */
if (isset($_GET["accept_id"]) && is_numeric($_GET["accept_id"])) {
    $appointment_id = intval($_GET["accept_id"]);

    $sql = "UPDATE appointments
            SET status = 'ACCEPTED'
            WHERE appointment_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("location: employee_dashboard.php");
    exit;
}

/* Cancel appointment */
if (isset($_GET["cancel_id"]) && is_numeric($_GET["cancel_id"])) {
    $appointment_id = intval($_GET["cancel_id"]);

    $sql = "UPDATE appointments
            SET status = 'CANCELLED'
            WHERE appointment_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("location: employee_dashboard.php");
    exit;
}

/* Complete appointment */
if (isset($_GET["complete_id"]) && is_numeric($_GET["complete_id"])) {
    $appointment_id = intval($_GET["complete_id"]);

    $sql = "UPDATE appointments
            SET status = 'COMPLETED'
            WHERE appointment_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("location: employee_dashboard.php");
    exit;
}

/* Show only SCHEDULED and ACCEPTED so accepted stays visible */
$sql = "
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.start_time,
    a.end_time,
    a.status,
    a.notes,
    p.pet_name,
    p.pet_image,
    u.first_name,
    u.last_name
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.customer_id = u.user_id
WHERE a.employee_id = ?
AND UPPER(TRIM(a.status)) IN ('SCHEDULED', 'ACCEPTED')
ORDER BY a.appointment_date DESC, a.start_time DESC
";

$appointments = [];

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
            font-family: Arial, sans-serif;
        }

        .wrapper {
            width: 1200px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.10);
        }

        .pet-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: bold;
            display: inline-block;
            min-width: 120px;
            text-align: center;
        }

        .scheduled {
            background: #fff3cd;
            color: #856404;
        }

        .accepted {
            background: #d1ecf1;
            color: #0c5460;
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
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper">
    <div class="card">
        <h1>Employee Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>

        <div class="w3-margin-bottom">
           
            <a href="set_specific_availability.php" class="w3-button w3-indigo">Set Specific Dates</a>
        </div>

        <h3>Your Appointments</h3>

        <?php if (empty($appointments)) { ?>
            <p>No appointments assigned yet.</p>
        <?php } else { ?>
            <table class="w3-table w3-bordered w3-striped">
                <tr class="w3-teal">
                    <th>Pet Image</th>
                    <th>Customer</th>
                    <th>Pet</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

                <?php foreach ($appointments as $appt) { ?>
                    <?php $status = strtoupper(trim((string)$appt["status"])); ?>
                    <tr>
                        <td>
                            <?php if (!empty($appt['pet_image'])) { ?>
                                <img src="<?php echo htmlspecialchars($appt['pet_image']); ?>" alt="Pet" class="pet-img">
                            <?php } else { ?>
                                No Image
                            <?php } ?>
                        </td>

                        <td><?php echo htmlspecialchars($appt['first_name'] . " " . $appt['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                        <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($appt['start_time'] . " - " . $appt['end_time']); ?></td>

                        <td>
                            <?php
                            $status_class = "default-status";
                            $status_label = $status;

                            if ($status == "SCHEDULED") {
                                $status_class = "scheduled";
                                $status_label = "SCHEDULED";
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

                        <td>
                            <?php if ($status == "SCHEDULED") { ?>
                                <a href="employee_dashboard.php?accept_id=<?php echo $appt["appointment_id"]; ?>"
                                   class="w3-button w3-green w3-small">
                                   Accept
                                </a>

                                <a href="employee_dashboard.php?cancel_id=<?php echo $appt["appointment_id"]; ?>"
                                   class="w3-button w3-red w3-small"
                                   onclick="return confirm('Cancel this appointment?');">
                                   Reject
                                </a>

                            <?php } elseif ($status == "ACCEPTED") { ?>
                                <a href="employee_dashboard.php?complete_id=<?php echo $appt["appointment_id"]; ?>"
                                   class="w3-button w3-blue w3-small"
                                   onclick="return confirm('Mark this appointment as completed?');">
                                   Complete
                                </a>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <br>
        <a class="w3-button w3-red" href="logout.php">Logout</a>
    </div>
</div>

</body>
</html>