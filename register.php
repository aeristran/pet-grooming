<?php
session_start();
require_once "config.php";

$username = $password = $confirm_password = $email = $first_name = $last_name = "";
$username_err = $password_err = $confirm_password_err = $email_err = $first_name_err = $last_name_err = "";

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
        $sql = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            }
            mysqli_stmt_close($stmt);
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

    if (
        empty($username_err) && empty($password_err) && empty($confirm_password_err) &&
        empty($email_err) && empty($first_name_err) && empty($last_name_err)
    ) {
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

            if (mysqli_stmt_execute($stmt)) {
                $new_user_id = mysqli_insert_id($link);

                $sql_profile = "INSERT INTO customer_profiles (customer_id) VALUES (?)";
                if ($stmt2 = mysqli_prepare($link, $sql_profile)) {
                    mysqli_stmt_bind_param($stmt2, "i", $new_user_id);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                }

                header("location: login.php");
                exit;
            } else {
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 400px; padding: 20px; margin: 40px auto; }
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper w3-white w3-border w3-round-large">
    <h2>Customer Sign Up</h2>
    <p>Please fill out this form to create your account.</p>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="w3-container">
            <label>First Name</label>
            <input type="text" name="first_name" class="w3-input" value="<?php echo htmlspecialchars($first_name); ?>">
            <span class="w3-text-red"><?php echo $first_name_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Last Name</label>
            <input type="text" name="last_name" class="w3-input" value="<?php echo htmlspecialchars($last_name); ?>">
            <span class="w3-text-red"><?php echo $last_name_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Email</label>
            <input type="email" name="email" class="w3-input" value="<?php echo htmlspecialchars($email); ?>">
            <span class="w3-text-red"><?php echo $email_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Username</label>
            <input type="text" name="username" class="w3-input" value="<?php echo htmlspecialchars($username); ?>">
            <span class="w3-text-red"><?php echo $username_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Password</label>
            <input type="password" name="password" class="w3-input">
            <span class="w3-text-red"><?php echo $password_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="w3-input">
            <span class="w3-text-red"><?php echo $confirm_password_err; ?></span>
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