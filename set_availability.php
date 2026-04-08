<?php
session_start();
require_once "config.php";

/* Only EMPLOYEE */
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "EMPLOYEE") {
    header("location: login.php");
    exit;
}

$employee_id = $_SESSION["user_id"];
$success_msg = "";
$error_msg = "";

/* SAVE availability */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $day = $_POST["day_of_week"];
    $start = $_POST["start_time"];
    $end = $_POST["end_time"];

    if (!empty($day) && !empty($start) && !empty($end)) {

        /* Check if already exists */
        $check_sql = "SELECT availability_id FROM employee_availability 
                      WHERE employee_id = ? AND day_of_week = ?";

        if ($stmt = mysqli_prepare($link, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "is", $employee_id, $day);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                /* UPDATE */
                $update_sql = "UPDATE employee_availability 
                               SET start_time = ?, end_time = ? 
                               WHERE employee_id = ? AND day_of_week = ?";

                if ($stmt2 = mysqli_prepare($link, $update_sql)) {
                    mysqli_stmt_bind_param($stmt2, "ssis", $start, $end, $employee_id, $day);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                    $success_msg = "Availability updated!";
                }

            } else {
                /* INSERT */
                $insert_sql = "INSERT INTO employee_availability 
                               (employee_id, day_of_week, start_time, end_time)
                               VALUES (?, ?, ?, ?)";

                if ($stmt2 = mysqli_prepare($link, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt2, "isss", $employee_id, $day, $start, $end);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                    $success_msg = "Availability added!";
                }
            }

            mysqli_stmt_close($stmt);
        }

    } else {
        $error_msg = "Fill all fields.";
    }
}

/* LOAD availability */
$availability = [];

$sql = "SELECT day_of_week, start_time, end_time 
        FROM employee_availability 
        WHERE employee_id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $availability[$row["day_of_week"]] = $row;
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set Availability</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
        }

        .wrapper {
            width: 600px;
            margin: 40px auto;
        }
    </style>
</head>
<body>

<div class="wrapper w3-white w3-padding w3-round-large">

    <h2>Set Your Availability</h2>

    <?php if ($success_msg) { ?>
        <div class="w3-green w3-padding"><?php echo $success_msg; ?></div>
    <?php } ?>

    <?php if ($error_msg) { ?>
        <div class="w3-red w3-padding"><?php echo $error_msg; ?></div>
    <?php } ?>

    <form method="post">

        <label>Day</label>
        <select name="day_of_week" class="w3-input w3-margin-bottom">
            <option>Monday</option>
            <option>Tuesday</option>
            <option>Wednesday</option>
            <option>Thursday</option>
            <option>Friday</option>
            <option>Saturday</option>
            <option>Sunday</option>
        </select>

        <label>Start Time</label>
        <input type="time" name="start_time" class="w3-input w3-margin-bottom">

        <label>End Time</label>
        <input type="time" name="end_time" class="w3-input w3-margin-bottom">

        <button class="w3-button w3-green w3-round">Save</button>
        <a href="employee_dashboard.php" class="w3-button w3-gray">Back</a>
    </form>

    <hr>

    <h3>Your Current Schedule</h3>

    <?php if (empty($availability)) { ?>
        <p>No availability set yet.</p>
    <?php } else { ?>
        <table class="w3-table w3-bordered">
            <tr class="w3-teal">
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
            </tr>

            <?php foreach ($availability as $day => $row) { ?>
                <tr>
                    <td><?php echo $day; ?></td>
                    <td><?php echo $row["start_time"]; ?></td>
                    <td><?php echo $row["end_time"]; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

</div>

</body>
</html>