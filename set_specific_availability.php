<?php
session_start();
require_once "config.php";

/* Only EMPLOYEE can access */
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "EMPLOYEE") {
    header("location: login.php");
    exit;
}

$employee_id = $_SESSION["user_id"];
$available_date = $start_time = $end_time = "";
$success_msg = "";
$error_msg = "";

/* Save specific date availability */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $available_date = trim($_POST["available_date"]);
    $start_time = trim($_POST["start_time"]);
    $end_time = trim($_POST["end_time"]);

    if (empty($available_date) || empty($start_time) || empty($end_time)) {
        $error_msg = "Please fill all fields.";
    } elseif ($start_time >= $end_time) {
        $error_msg = "End time must be after start time.";
    } else {

        /* Check if this employee already has this date saved */
        $check_sql = "SELECT date_availability_id
                      FROM employee_date_availability
                      WHERE employee_id = ? AND available_date = ?";

        if ($stmt = mysqli_prepare($link, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "is", $employee_id, $available_date);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);

                /* Update existing date */
                $update_sql = "UPDATE employee_date_availability
                               SET start_time = ?, end_time = ?
                               WHERE employee_id = ? AND available_date = ?";

                if ($stmt2 = mysqli_prepare($link, $update_sql)) {
                    mysqli_stmt_bind_param($stmt2, "ssis", $start_time, $end_time, $employee_id, $available_date);

                    if (mysqli_stmt_execute($stmt2)) {
                        $success_msg = "Specific date availability updated successfully.";
                        $available_date = $start_time = $end_time = "";
                    } else {
                        $error_msg = "Could not update availability.";
                    }

                    mysqli_stmt_close($stmt2);
                }
            } else {
                mysqli_stmt_close($stmt);

                /* Insert new date */
                $insert_sql = "INSERT INTO employee_date_availability
                               (employee_id, available_date, start_time, end_time)
                               VALUES (?, ?, ?, ?)";

                if ($stmt2 = mysqli_prepare($link, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt2, "isss", $employee_id, $available_date, $start_time, $end_time);

                    if (mysqli_stmt_execute($stmt2)) {
                        $success_msg = "Specific date availability saved successfully.";
                        $available_date = $start_time = $end_time = "";
                    } else {
                        $error_msg = "Could not save availability.";
                    }

                    mysqli_stmt_close($stmt2);
                }
            }
        }
    }
}

/* Load saved specific dates */
$dates = [];
$sql_dates = "SELECT date_availability_id, available_date, start_time, end_time
              FROM employee_date_availability
              WHERE employee_id = ?
              ORDER BY available_date ASC";

if ($stmt = mysqli_prepare($link, $sql_dates)) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row;
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
    <title>Set Specific Availability</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
            font-family: Arial, sans-serif;
        }

        .wrapper {
            width: 700px;
            margin: 40px auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.10);
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <h2>Set Specific Date Availability</h2>
        <p>Choose exact dates and working hours.</p>

        <?php if (!empty($success_msg)) { ?>
            <div class="w3-panel w3-green w3-round">
                <p><?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        <?php } ?>

        <?php if (!empty($error_msg)) { ?>
            <div class="w3-panel w3-red w3-round">
                <p><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php } ?>

        <form method="post">
            <div class="w3-margin-bottom">
                <label>Date</label>
                <input type="date" name="available_date" class="w3-input"
                       value="<?php echo htmlspecialchars($available_date); ?>">
            </div>

            <div class="w3-row-padding">
                <div class="w3-half">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="w3-input"
                           value="<?php echo htmlspecialchars($start_time); ?>">
                </div>

                <div class="w3-half">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="w3-input"
                           value="<?php echo htmlspecialchars($end_time); ?>">
                </div>
            </div>

            <div class="w3-margin-top">
                <button type="submit" class="w3-button w3-green w3-round">Save</button>
                <a href="employee_dashboard.php" class="w3-button w3-gray w3-round">Back</a>
            </div>
        </form>

        <hr>

        <h3>Your Saved Specific Dates</h3>

        <?php if (empty($dates)) { ?>
            <p>No specific date availability saved yet.</p>
        <?php } else { ?>
            <table class="w3-table w3-bordered w3-striped">
                <tr class="w3-teal">
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>

                <?php foreach ($dates as $date) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($date["available_date"]); ?></td>
                        <td><?php echo htmlspecialchars($date["start_time"]); ?></td>
                        <td><?php echo htmlspecialchars($date["end_time"]); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</div>

</body>
</html>