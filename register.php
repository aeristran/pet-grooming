<?php
session_start();
require_once "config.php";

$username = $password = $confirm_password = $email = $first_name = $last_name = "";
$address_line1 = $address_line2 = $city = $state = $zip_code = "";
$emergency_contact_name = $emergency_contact_phone = "";

$username_err = $password_err = $confirm_password_err = $email_err = $first_name_err = $last_name_err = "";
$address_line1_err = $city_err = $state_err = $zip_code_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = strtolower(trim($_POST["email"]));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        } elseif (!preg_match("/@(ggc\.edu|gmail\.com)$/", $email)) {
            $email_err = "Only @ggc.edu or @gmail.com emails are allowed.";
        } else {
            $sql = "SELECT user_id FROM users WHERE email = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already taken.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    if (empty(trim($_POST["address_line1"]))) {
        $address_line1_err = "Please enter address line 1.";
    } else {
        $address_line1 = trim($_POST["address_line1"]);
    }

    $address_line2 = trim($_POST["address_line2"]);

    if (empty(trim($_POST["city"]))) {
        $city_err = "Please enter city.";
    } else {
        $city = trim($_POST["city"]);
    }

    if (empty(trim($_POST["state"]))) {
        $state_err = "Please enter state.";
    } else {
        $state = trim($_POST["state"]);
    }

    if (empty(trim($_POST["zip_code"]))) {
        $zip_code_err = "Please enter zip code.";
    } else {
        $zip_code = trim($_POST["zip_code"]);
    }

    $emergency_contact_name = trim($_POST["emergency_contact_name"]);
    $emergency_contact_phone = trim($_POST["emergency_contact_phone"]);

    if (
        empty($username_err) && empty($password_err) && empty($confirm_password_err) &&
        empty($email_err) && empty($first_name_err) && empty($last_name_err) &&
        empty($address_line1_err) && empty($city_err) && empty($state_err) && empty($zip_code_err)
    ) {
        mysqli_begin_transaction($link);

        try {
            $sql = "INSERT INTO users (role, first_name, last_name, email, username, password_hash)
                    VALUES (?, ?, ?, ?, ?, ?)";

            if ($stmt = mysqli_prepare($link, $sql)) {
                $role = "CUSTOMER";
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssss",
                    $role,
                    $first_name,
                    $last_name,
                    $email,
                    $username,
                    $hashed_password
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Could not create user.");
                }

                $new_user_id = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception("Could not prepare user insert.");
            }

            $sql_profile = "INSERT INTO customer_profiles
                (customer_id, address_line1, address_line2, city, state, zip_code, emergency_contact_name, emergency_contact_phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt2 = mysqli_prepare($link, $sql_profile)) {
                mysqli_stmt_bind_param(
                    $stmt2,
                    "isssssss",
                    $new_user_id,
                    $address_line1,
                    $address_line2,
                    $city,
                    $state,
                    $zip_code,
                    $emergency_contact_name,
                    $emergency_contact_phone
                );

                if (!mysqli_stmt_execute($stmt2)) {
                    throw new Exception("Could not create customer profile.");
                }

                mysqli_stmt_close($stmt2);
            } else {
                throw new Exception("Could not prepare profile insert.");
            }

            mysqli_commit($link);
            header("location: login.php");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($link);
            echo "<div class='w3-panel w3-red'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign Up</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 500px; padding: 20px; margin: 30px auto; }
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper w3-white w3-border w3-round-large">
    <h2>Customer Sign Up</h2>
    <p>Please fill out this form to create your account.</p>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="w3-container">
            <label>First Name</label>
            <input type="text" class="w3-input" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
            <span class="w3-text-red"><?php echo $first_name_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Last Name</label>
            <input type="text" class="w3-input" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
            <span class="w3-text-red"><?php echo $last_name_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Email</label>
            <input type="email" class="w3-input" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <span class="w3-text-red"><?php echo $email_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Username</label>
            <input type="text" class="w3-input" name="username" value="<?php echo htmlspecialchars($username); ?>">
            <span class="w3-text-red"><?php echo $username_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Password</label>
            <input type="password" class="w3-input" name="password">
            <span class="w3-text-red"><?php echo $password_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Confirm Password</label>
            <input type="password" class="w3-input" name="confirm_password">
            <span class="w3-text-red"><?php echo $confirm_password_err; ?></span>
        </div>

        <hr>

        <div class="w3-container">
            <label>Address Line 1</label>
            <input type="text" class="w3-input" name="address_line1" value="<?php echo htmlspecialchars($address_line1); ?>">
            <span class="w3-text-red"><?php echo $address_line1_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Address Line 2</label>
            <input type="text" class="w3-input" name="address_line2" value="<?php echo htmlspecialchars($address_line2); ?>">
        </div>

        <div class="w3-container">
            <label>City</label>
            <input type="text" class="w3-input" name="city" value="<?php echo htmlspecialchars($city); ?>">
            <span class="w3-text-red"><?php echo $city_err; ?></span>
        </div>

        <div class="w3-container">
            <label>State</label>
            <input type="text" class="w3-input" name="state" value="<?php echo htmlspecialchars($state); ?>">
            <span class="w3-text-red"><?php echo $state_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Zip Code</label>
            <input type="text" class="w3-input" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>">
            <span class="w3-text-red"><?php echo $zip_code_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Emergency Contact Name</label>
            <input type="text" class="w3-input" name="emergency_contact_name" value="<?php echo htmlspecialchars($emergency_contact_name); ?>">
        </div>

        <div class="w3-container">
            <label>Emergency Contact Phone</label>
            <input type="text" class="w3-input" name="emergency_contact_phone" value="<?php echo htmlspecialchars($emergency_contact_phone); ?>">
        </div>

        <div class="w3-container w3-margin-top">
            <input type="submit" class="w3-button w3-green" value="Submit">
            <input type="reset" class="w3-button w3-gray" value="Reset">
        </div>

        <p class="w3-container w3-margin-top">Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>

</body>
</html>