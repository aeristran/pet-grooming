<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'CUSTOMER') {
    header("location: login.php");
    exit;
}

function buildAllSlotsWithStatus($available_start, $available_end, $duration_minutes, $booked_ranges) {
    $slots = [];

    $current = strtotime($available_start);
    $end_boundary = strtotime($available_end);

    while (($current + ($duration_minutes * 60)) <= $end_boundary) {
        $slot_start = date("H:i:s", $current);
        $slot_end = date("H:i:s", $current + ($duration_minutes * 60));

        $booked = false;

        foreach ($booked_ranges as $range) {
            if ($slot_start < $range["end_time"] && $slot_end > $range["start_time"]) {
                $booked = true;
                break;
            }
        }

        $slots[] = [
            "time" => $slot_start,
            "booked" => $booked
        ];

        $current = strtotime("+30 minutes", $current);
    }

    return $slots;
}

$customer_id = $_SESSION["user_id"];

$pet_id = "";
$employee_id = "";
$appointment_date = "";
$start_time = "";
$notes = "";

$pet_id_err = "";
$employee_id_err = "";
$appointment_date_err = "";
$start_time_err = "";
$services_err = "";
$availability_err = "";

$success_msg = "";
$selected_services = [];
$available_slots = [];
$total_duration_preview = 30;

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
   Handle form data
------------------------------ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_id = isset($_POST["pet_id"]) ? trim($_POST["pet_id"]) : "";
    $employee_id = isset($_POST["employee_id"]) ? trim($_POST["employee_id"]) : "";
    $appointment_date = isset($_POST["appointment_date"]) ? trim($_POST["appointment_date"]) : "";
    $start_time = isset($_POST["start_time"]) ? trim($_POST["start_time"]) : "";
    $notes = isset($_POST["notes"]) ? trim($_POST["notes"]) : "";
    $selected_services = isset($_POST["services"]) ? $_POST["services"] : [];

    /* Preview total duration for slot generation */
    if (!empty($selected_services)) {
        $total_duration_preview = 0;

        foreach ($selected_services as $service_id) {
            $sql_duration = "SELECT duration_minutes FROM services WHERE service_id = ?";
            if ($stmt = mysqli_prepare($link, $sql_duration)) {
                mysqli_stmt_bind_param($stmt, "i", $service_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($row = mysqli_fetch_assoc($result)) {
                    $total_duration_preview += (int)$row["duration_minutes"];
                }

                mysqli_stmt_close($stmt);
            }
        }

        if ($total_duration_preview <= 0) {
            $total_duration_preview = 30;
        }
    }

    /* -----------------------------
       Load available slots from exact date availability
    ------------------------------ */
    if (!empty($employee_id) && !empty($appointment_date) && !empty($selected_services)) {
        $sql_availability = "SELECT start_time, end_time
                             FROM employee_date_availability
                             WHERE employee_id = ? AND available_date = ?";

        if ($stmt = mysqli_prepare($link, $sql_availability)) {
            mysqli_stmt_bind_param($stmt, "is", $employee_id, $appointment_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($availability = mysqli_fetch_assoc($result)) {
                $booked_ranges = [];

                $sql_booked = "SELECT start_time, end_time
                               FROM appointments
                               WHERE employee_id = ?
                               AND appointment_date = ?
                               AND status <> 'CANCELLED'";

                if ($stmt2 = mysqli_prepare($link, $sql_booked)) {
                    mysqli_stmt_bind_param($stmt2, "is", $employee_id, $appointment_date);
                    mysqli_stmt_execute($stmt2);
                    $result2 = mysqli_stmt_get_result($stmt2);

                    while ($booked = mysqli_fetch_assoc($result2)) {
                        $booked_ranges[] = $booked;
                    }

                    mysqli_stmt_close($stmt2);
                }

                $available_slots = buildAllSlotsWithStatus(
                    $availability["start_time"],
                    $availability["end_time"],
                    $total_duration_preview,
                    $booked_ranges
                );

                if (empty($available_slots)) {
                    $availability_err = "No available time slots for this groomer on the selected date.";
                }
            } else {
                $availability_err = "This groomer is not available on the selected date.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    /* -----------------------------
       Final booking submit
    ------------------------------ */
    if (isset($_POST["book_appointment"])) {

        if (empty($pet_id)) {
            $pet_id_err = "Please select a pet.";
        }

        if (empty($employee_id)) {
            $employee_id_err = "Please select a groomer.";
        }

        if (empty($appointment_date)) {
            $appointment_date_err = "Please choose a date.";
        }

        if (empty($start_time)) {
            $start_time_err = "Please choose a start time.";
        }

        if (empty($selected_services)) {
            $services_err = "Please select at least one service.";
        }

        if (
            empty($pet_id_err) &&
            empty($employee_id_err) &&
            empty($appointment_date_err) &&
            empty($start_time_err) &&
            empty($services_err)
        ) {
            /* Get selected service details */
            $total_duration = 0;
            $total_price = 0.00;
            $service_details = [];

            foreach ($selected_services as $service_id) {
                $sql_one_service = "SELECT service_id, service_name, duration_minutes, base_price
                                    FROM services
                                    WHERE service_id = ?";

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

            $sql_availability_check = "SELECT start_time, end_time
                                       FROM employee_date_availability
                                       WHERE employee_id = ? AND available_date = ?";

            $allowed_to_book = false;

            if ($stmt = mysqli_prepare($link, $sql_availability_check)) {
                mysqli_stmt_bind_param($stmt, "is", $employee_id, $appointment_date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($availability = mysqli_fetch_assoc($result)) {
                    $start_datetime = strtotime($appointment_date . " " . $start_time);
                    $end_datetime = strtotime("+$total_duration minutes", $start_datetime);

                    $formatted_start_time = date("H:i:s", $start_datetime);
                    $end_time = date("H:i:s", $end_datetime);

                    if (
                        $formatted_start_time >= $availability["start_time"] &&
                        $end_time <= $availability["end_time"]
                    ) {
                        $sql_conflict = "SELECT appointment_id
                                         FROM appointments
                                         WHERE employee_id = ?
                                         AND appointment_date = ?
                                         AND status <> 'CANCELLED'
                                         AND (? < end_time AND ? > start_time)";

                        if ($stmt2 = mysqli_prepare($link, $sql_conflict)) {
                            mysqli_stmt_bind_param(
                                $stmt2,
                                "isss",
                                $employee_id,
                                $appointment_date,
                                $formatted_start_time,
                                $end_time
                            );
                            mysqli_stmt_execute($stmt2);
                            $result2 = mysqli_stmt_get_result($stmt2);

                            if (mysqli_num_rows($result2) == 0) {
                                $allowed_to_book = true;
                            } else {
                                $start_time_err = "That time is already booked for this groomer.";
                            }

                            mysqli_stmt_close($stmt2);
                        }
                    } else {
                        $start_time_err = "Selected time is outside this groomer's availability.";
                    }
                } else {
                    $start_time_err = "This groomer is not available on the selected date.";
                }

                mysqli_stmt_close($stmt);
            }

            if ($allowed_to_book) {
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

                        $pet_id = "";
                        $employee_id = "";
                        $appointment_date = "";
                        $start_time = "";
                        $notes = "";
                        $selected_services = [];
                        $available_slots = [];
                        $total_duration_preview = 30;
                    } else {
                        $start_time_err = "Something went wrong while creating the appointment.";
                    }

                    mysqli_stmt_close($stmt);
                }
            }
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
        body {
            font: 14px sans-serif;
        }

        .wrapper {
            width: 560px;
            padding: 20px;
            margin: 40px auto;
        }
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
            <button type="submit" name="load_times" value="1" class="w3-button w3-blue">
                Show Available Times
            </button>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Start Time</label>
            <select name="start_time" class="w3-select">
                <option value="">Choose a time</option>
                <?php foreach ($available_slots as $slot) { ?>
                    <?php if ($slot["booked"]) { ?>
                        <option value="" disabled>
                            <?php echo date("g:i A", strtotime($slot["time"])); ?> - Unavailable
                        </option>
                    <?php } else { ?>
                        <option value="<?php echo $slot["time"]; ?>" <?php if ($start_time == $slot["time"]) echo "selected"; ?>>
                            <?php echo date("g:i A", strtotime($slot["time"])); ?>
                        </option>
                    <?php } ?>
                <?php } ?>
            </select>
            <span class="w3-text-red"><?php echo $start_time_err; ?></span>
            <span class="w3-text-red"><?php echo $availability_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <label>Notes</label>
            <textarea name="notes" class="w3-input"><?php echo htmlspecialchars($notes); ?></textarea>
        </div>

        <div class="w3-container w3-margin-top">
            <input type="submit" name="book_appointment" class="w3-button w3-green" value="Book Appointment">
            <a href="customer_dashboard.php" class="w3-button w3-gray">Back</a>
        </div>

    </form>
</div>

</body>
</html>