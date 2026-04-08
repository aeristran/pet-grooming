<?php
session_start();
require_once "config.php";

/* Only ADMIN can access */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'ADMIN') {
    header("location: login.php");
    exit;
}

$service_name = $description = "";
$duration_minutes = $base_price = "";
$service_name_err = $duration_err = $price_err = "";
$success_msg = "";
$error_msg = "";

/* Deactivate service */
if (isset($_GET["deactivate_id"]) && is_numeric($_GET["deactivate_id"])) {
    $deactivate_id = intval($_GET["deactivate_id"]);

    $sql_deactivate = "UPDATE services SET is_active = 0 WHERE service_id = ?";
    if ($stmt = mysqli_prepare($link, $sql_deactivate)) {
        mysqli_stmt_bind_param($stmt, "i", $deactivate_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Service deactivated successfully.";
        } else {
            $error_msg = "Could not deactivate service.";
        }

        mysqli_stmt_close($stmt);
    }
}

/* Activate service */
if (isset($_GET["activate_id"]) && is_numeric($_GET["activate_id"])) {
    $activate_id = intval($_GET["activate_id"]);

    $sql_activate = "UPDATE services SET is_active = 1 WHERE service_id = ?";
    if ($stmt = mysqli_prepare($link, $sql_activate)) {
        mysqli_stmt_bind_param($stmt, "i", $activate_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Service activated successfully.";
        } else {
            $error_msg = "Could not activate service.";
        }

        mysqli_stmt_close($stmt);
    }
}

/* Add new service */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_service"])) {

    if (empty(trim($_POST["service_name"]))) {
        $service_name_err = "Please enter a service name.";
    } else {
        $service_name = trim($_POST["service_name"]);
    }

    $description = trim($_POST["description"]);

    if (empty(trim($_POST["duration_minutes"]))) {
        $duration_err = "Please enter duration.";
    } elseif (!is_numeric($_POST["duration_minutes"]) || intval($_POST["duration_minutes"]) <= 0) {
        $duration_err = "Duration must be a positive number.";
    } else {
        $duration_minutes = intval($_POST["duration_minutes"]);
    }

    if (empty(trim($_POST["base_price"]))) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($_POST["base_price"]) || floatval($_POST["base_price"]) < 0) {
        $price_err = "Price must be a valid number.";
    } else {
        $base_price = floatval($_POST["base_price"]);
    }

    if (empty($service_name_err) && empty($duration_err) && empty($price_err)) {
        $sql = "INSERT INTO services (service_name, description, duration_minutes, base_price, is_active)
                VALUES (?, ?, ?, ?, 1)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssid", $service_name, $description, $duration_minutes, $base_price);

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Service added successfully!";
                $service_name = "";
                $description = "";
                $duration_minutes = "";
                $base_price = "";
            } else {
                $error_msg = "Could not add service.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* Load all services */
$services = [];
$sql_services = "SELECT service_id, service_name, description, duration_minutes, base_price, is_active
                 FROM services
                 ORDER BY service_id ASC";

$result = mysqli_query($link, $sql_services);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            font: 14px Arial, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            width: 1000px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <h2>Manage Services</h2>
        <p>Add and manage grooming services below.</p>

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

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="add_service" value="1">

            <div class="w3-row-padding">
                <div class="w3-half">
                    <label>Service Name</label>
                    <input type="text" name="service_name" class="w3-input" value="<?php echo htmlspecialchars($service_name); ?>">
                    <span class="w3-text-red"><?php echo $service_name_err; ?></span>
                </div>

                <div class="w3-half">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="w3-input" value="<?php echo htmlspecialchars($duration_minutes); ?>">
                    <span class="w3-text-red"><?php echo $duration_err; ?></span>
                </div>
            </div>

            <div class="w3-row-padding w3-margin-top">
                <div class="w3-half">
                    <label>Price</label>
                    <input type="text" name="base_price" class="w3-input" value="<?php echo htmlspecialchars($base_price); ?>">
                    <span class="w3-text-red"><?php echo $price_err; ?></span>
                </div>

                <div class="w3-half">
                    <label>Description</label>
                    <input type="text" name="description" class="w3-input" value="<?php echo htmlspecialchars($description); ?>">
                </div>
            </div>

            <div class="w3-margin-top">
                <input type="submit" class="w3-button w3-green" value="Add Service">
                <a href="admin_dashboard.php" class="w3-button w3-gray">Back</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Current Services</h3>

        <?php if (empty($services)) { ?>
            <p>No services found.</p>
        <?php } else { ?>
            <table class="w3-table w3-bordered w3-striped">
                <tr class="w3-teal">
                    <th>ID</th>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($services as $service) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service["service_id"]); ?></td>
                        <td><?php echo htmlspecialchars($service["service_name"]); ?></td>
                        <td><?php echo htmlspecialchars($service["description"]); ?></td>
                        <td><?php echo htmlspecialchars($service["duration_minutes"]); ?> min</td>
                        <td>$<?php echo htmlspecialchars(number_format((float)$service["base_price"], 2)); ?></td>
                        <td><?php echo $service["is_active"] ? "Yes" : "No"; ?></td>
                        <td>
                            <?php if ($service["is_active"]) { ?>
                                <a class="w3-button w3-orange w3-small"
                                   href="manage_services.php?deactivate_id=<?php echo $service["service_id"]; ?>"
                                   onclick="return confirm('Deactivate this service?');">
                                   Deactivate
                                </a>
                            <?php } else { ?>
                                <a class="w3-button w3-green w3-small"
                                   href="manage_services.php?activate_id=<?php echo $service["service_id"]; ?>"
                                   onclick="return confirm('Activate this service?');">
                                   Activate
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</div>

</body>
</html>