<?php
session_start();
require_once "config.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: welcome.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT user_id, username, password_hash, role, first_name FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $first_name);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {

                            session_regenerate_id(true);

                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            $_SESSION["first_name"] = $first_name;

                            setcookie("last_username", $username, time() + (86400 * 30), "/");

                            header("location: welcome.php");
                            exit;
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}

$last_username = isset($_COOKIE["last_username"]) ? $_COOKIE["last_username"] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 350px; padding: 20px; margin: 40px auto; }
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper w3-white w3-border w3-round-large">
    <h2>Login</h2>
    <p>Please fill in your credentials to login.</p>

    <?php
    if (!empty($login_err)) {
        echo '<div class="w3-panel w3-red w3-padding">' . htmlspecialchars($login_err) . '</div>';
    }
    ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="w3-container">
            <label>Username</label>
            <input type="text" name="username" class="w3-input"
                   value="<?php echo htmlspecialchars($last_username); ?>">
            <span class="w3-text-red"><?php echo $username_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Password</label>
            <input type="password" name="password" class="w3-input">
            <span class="w3-text-red"><?php echo $password_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <input type="submit" class="w3-button w3-green" value="Login">
        </div>

        <p class="w3-container w3-margin-top">Don't have an account? <a href="register.php">Sign up now</a>.</p>
    </form>
</div>

</body>
</html>