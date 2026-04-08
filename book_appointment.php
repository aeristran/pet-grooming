<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'CUSTOMER') {
    header("location: login.php");
    exit;
}

$customer_id = $_SESSION["user_id"];

$pet_id = $employee_id = $appointment_date = $start_time = $notes = "";
$pet_id_err = $employee_id_err = $appointment_date_err = $start_time_err = $services_err = "";
$success_msg = "";
$selected_services = [];

/* -----------------------------
   Load pets for logged-in customer
------------------------------ */
$pets = [];
$sql_pets = "SELECT pet_id, pet_name FROM pets WHERE customer_id = ?";
if ($stmt = mysqli_prepare($link, $sql_pets)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $pets[] = $row;
    }
    mysqli_stmt_close($stmt);
}

/* -----------------------------
   Load employees
------------------------------ */
$employees = [];
$sql_employees = "
    SELECT u.user_id, u.first_name, u.last_name
    FROM users u
    INNER JOIN employee_profiles ep ON u.user_id = ep.employee_id
    WHERE u.role = 'EMPLOYEE' AND ep.is_active = 1
";
$result_employees = mysqli_query($link, $sql_employees);
while ($row = mysqli_fetch_assoc($result_employees)) {
    $employees[] = $row;
}

/* -----------------------------
   Load services
------------------------------ */
$services = [];
$sql_services = "SELECT service_id, service_name, duration_minutes, base_price FROM services WHERE is_active = 1";
$result_services = mysqli_query($link, $sql_services);
while ($row = mysqli_fetch_assoc($result_services)) {
    $services[] = $row;
}

/* -----------------------------
   Handle form submit
------------------------------ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["pet_id"])) {
        $pet_id_err = "Please select a pet.";
    } else {
        $pet_id = $_POST["pet_id"];
    }

    if (empty($_POST["employee_id"])) {
        $employee_id_err = "Please select a groomer.";
    } else {
        $employee_id = $_POST["employee_id"];
    }

    if (empty(trim($_POST["appointment_date"]))) {
        $appointment_date_err = "Please choose a date.";
    } else {
        $appointment_date = trim($_POST["appointment_date"]);
    }

    if (empty(trim($_POST["start_time"]))) {
        $start_time_err = "Please choose a start time.";
    } else {
        $start_time = trim($_POST["start_time"]);
    }

    if (!empty($_POST["services"])) {
        $selected_services = $_POST["services"];
    } else {
        $services_err = "Please select at least one service.";
    }

    $notes = trim($_POST["notes"]);

    if (
        empty($pet_id_err) &&
        empty($employee_id_err) &&
        empty($appointment_date_err) &&
        empty($start_time_err) &&
        empty($services_err)
    ) {

        /* -----------------------------------------
           Get selected service details from database
        ------------------------------------------ */
        $total_duration = 0;
        $total_price = 0.00;
        $service_details = [];

        foreach ($selected_services as $service_id) {
            $sql_one_service = "SELECT service_id, service_name, duration_minutes, base_price FROM services WHERE service_id = ?";
            if ($stmt = mysqli_prepare($link, $sql_one_service)) {
                mysqli_stmt_bind_param($stmt, "i", $service_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($service = mysqli_fetch_assoc($result)) {
                    $service_details[] = $service;
                    $total_duration += (int)$service["duration_minutes"];
                    $total_price += (float)$service["base_price"];
                }

                mysqli_stmt_close($stmt);
            }
        }

        /* -----------------------------------------
           Calculate end time
        ------------------------------------------ */
        $start_datetime = strtotime($appointment_date . " " . $start_time);
        $end_datetime = strtotime("+$total_duration minutes", $start_datetime);
        $end_time = date("H:i:s", $end_datetime);
        $formatted_start_time = date("H:i:s", $start_datetime);

        /* -----------------------------------------
           Insert appointment
        ------------------------------------------ */
        $sql_insert_appt = "INSERT INTO appointments
            (customer_id, pet_id, employee_id, appointment_date, start_time, end_time, status, notes, total_price)
            VALUES (?, ?, ?, ?, ?, ?, 'SCHEDULED', ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql_insert_appt)) {
            mysqli_stmt_bind_param(
                $stmt,
                "iiissssd",
                $customer_id,
                $pet_id,
                $employee_id,
                $appointment_date,
                $formatted_start_time,
                $end_time,
                $notes,
                $total_price
            );

            if (mysqli_stmt_execute($stmt)) {
                $appointment_id = mysqli_insert_id($link);

                /* -----------------------------------------
                   Insert appointment_services
                ------------------------------------------ */
                foreach ($service_details as $service) {
                    $sql_insert_appt_service = "INSERT INTO appointment_services
                        (appointment_id, service_id, quantity, price_at_time)
                        VALUES (?, ?, 1, ?)";

                    if ($stmt2 = mysqli_prepare($link, $sql_insert_appt_service)) {
                        mysqli_stmt_bind_param(
                            $stmt2,
                            "iid",
                            $appointment_id,
                            $service["service_id"],
                            $service["base_price"]
                        );
                        mysqli_stmt_execute($stmt2);
                        mysqli_stmt_close($stmt2);
                    }
                }

                $success_msg = "Appointment booked successfully!";

                $pet_id = $employee_id = $appointment_date = $start_time = $notes = "";
                $selected_services = [];
            } else {
                echo "Something went wrong while creating the appointment.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 500px; padding: 20px; margin: 40px auto; }
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper w3-white w3-border w3-round-large">
    <h2>Book Appointment</h2>
    <p>Fill out the form below to schedule a grooming appointment.</p>

    <?php if (!empty($success_msg)) { ?>
        <div class="w3-panel w3-green w3-padding">
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php } ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

        <div class="w3-container">
            <label>Select Pet</label>
            <select name="pet_id" class="w3-select">
                <option value="">Choose your pet</option>
                <?php foreach ($pets as $pet) { ?>
                    <option value="<?php echo $pet['pet_id']; ?>" <?php if ($pet_id == $pet['pet_id']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($pet['pet_name']); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="w3-text-red"><?php echo $pet_id_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Select Groomer</label>
            <select name="employee_id" class="w3-select">
                <option value="">Choose a groomer</option>
                <?php foreach ($employees as $employee) { ?>
                    <option value="<?php echo $employee['user_id']; ?>" <?php if ($employee_id == $employee['user_id']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($employee['first_name'] . " " . $employee['last_name']); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="w3-text-red"><?php echo $employee_id_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Appointment Date</label>
            <input type="date" name="appointment_date" class="w3-input" value="<?php echo htmlspecialchars($appointment_date); ?>">
            <span class="w3-text-red"><?php echo $appointment_date_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Start Time</label>
            <input type="time" name="start_time" class="w3-input" value="<?php echo htmlspecialchars($start_time); ?>">
            <span class="w3-text-red"><?php echo $start_time_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Select Service(s)</label>
            <?php foreach ($services as $service) { ?>
                <p>
                    <input class="w3-check" type="checkbox" name="services[]"
                           value="<?php echo $service['service_id']; ?>"
                           <?php if (in_array($service['service_id'], $selected_services)) echo "checked"; ?>>
                    <label>
                        <?php
                        echo htmlspecialchars($service['service_name'])
                             . " - $" . htmlspecialchars($service['base_price'])
                             . " (" . htmlspecialchars($service['duration_minutes']) . " min)";
                        ?>
                    </label>
                </p>
            <?php } ?>
            <span class="w3-text-red"><?php echo $services_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Notes</label>
            <textarea name="notes" class="w3-input"><?php echo htmlspecialchars($notes); ?></textarea>
        </div>

        <div class="w3-container w3-margin-top">
            <input type="submit" class="w3-button w3-green" value="Book Appointment">
            <a href="customer_dashboard.php" class="w3-button w3-gray">Back</a>
        </div>

    </form>
</div>

</body>
</html>