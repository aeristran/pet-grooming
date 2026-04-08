<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'EMPLOYEE') {
    header("location: login.php");
    exit;
}

$employee_id = $_SESSION["user_id"];

/* -------------------------
   Handle status update
-------------------------- */
if (isset($_GET["action"]) && isset($_GET["id"])) {
    $appointment_id = $_GET["id"];
    $action = $_GET["action"];

    $status = "";

    if ($action == "accept") {
        $status = "CONFIRMED";
    } elseif ($action == "decline") {
        $status = "CANCELLED";
    } elseif ($action == "complete") {
        $status = "COMPLETED";
    }

    if (!empty($status)) {
        $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ? AND employee_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sii", $status, $appointment_id, $employee_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    header("location: employee_dashboard.php");
    exit;
}

/* -------------------------
   Get appointments
-------------------------- */
$sql = "
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.start_time,
    a.end_time,
    a.status,
    a.notes,
    p.pet_name,
    u.first_name,
    u.last_name
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.customer_id = u.user_id
WHERE a.employee_id = ?
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
</head>
<body class="w3-light-grey">

<div class="w3-container w3-white w3-margin w3-padding w3-round-large">
    <h1>Employee Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></p>

    <h3>Your Appointments</h3>

    <?php if (empty($appointments)) { ?>
        <p>No appointments assigned yet.</p>
    <?php } else { ?>
        <table class="w3-table w3-bordered w3-striped">
            <tr class="w3-teal">
                <th>Customer</th>
                <th>Pet</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php foreach ($appointments as $appt) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($appt['first_name'] . " " . $appt['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($appt['pet_name']); ?></td>
                    <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($appt['start_time'] . " - " . $appt['end_time']); ?></td>
                    <td><?php echo htmlspecialchars($appt['status']); ?></td>
                    <td>
                        <?php if ($appt['status'] == "SCHEDULED") { ?>
                            <a class="w3-button w3-green w3-small"
                               href="?action=accept&id=<?php echo $appt['appointment_id']; ?>">Accept</a>

                            <a class="w3-button w3-red w3-small"
                               href="?action=decline&id=<?php echo $appt['appointment_id']; ?>">Decline</a>
                        <?php } ?>

                        <?php if ($appt['status'] == "CONFIRMED") { ?>
                            <a class="w3-button w3-blue w3-small"
                               href="?action=complete&id=<?php echo $appt['appointment_id']; ?>">Complete</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>

        </table>
    <?php } ?>

    <br>
    <a class="w3-button w3-red" href="logout.php">Logout</a>
</div>

</body>
</html>