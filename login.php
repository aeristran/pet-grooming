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
    <title>Pet Grooming Login</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
            min-height: 100vh;
            overflow: hidden;
        }

        .page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
        }

        .login-card {
            width: 380px;
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            z-index: 2;
            animation: fadeInUp 1s ease;
        }

        .login-card h2 {
            text-align: center;
            color: #2d6a4f;
            margin-bottom: 10px;
        }

        .login-card p {
            text-align: center;
            color: #555;
            margin-bottom: 20px;
        }

        .login-card label {
            font-weight: bold;
            color: #333;
        }

        .login-card input[type="text"],
        .login-card input[type="password"] {
            margin-bottom: 8px;
            border-radius: 10px;
            border: 1px solid #ccc;
            padding: 10px;
        }

        .login-btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            background: #2d6a4f;
            color: white;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #1b4332;
            transform: scale(1.02);
        }

        .pet-side {
            position: absolute;
            width: 140px;
            animation: floatPet 3s ease-in-out infinite;
            z-index: 1;
        }

        .pet-left {
            left: 8%;
            bottom: 12%;
        }

        .pet-right {
            right: 8%;
            top: 12%;
            animation-delay: 1s;
        }

        .paw {
            position: absolute;
            font-size: 28px;
            opacity: 0.15;
            animation: drift 10s linear infinite;
        }

        .paw1 { top: 10%; left: 15%; }
        .paw2 { top: 25%; right: 18%; animation-delay: 2s; }
        .paw3 { bottom: 20%; left: 20%; animation-delay: 4s; }
        .paw4 { bottom: 12%; right: 25%; animation-delay: 6s; }

        .link-text {
            text-align: center;
            margin-top: 15px;
        }

        .link-text a {
            color: #2d6a4f;
            font-weight: bold;
            text-decoration: none;
        }

        .link-text a:hover {
            text-decoration: underline;
        }

        @keyframes floatPet {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes drift {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(8deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="paw paw1">🐾</div>
    <div class="paw paw2">🐾</div>
    <div class="paw paw3">🐾</div>
    <div class="paw paw4">🐾</div>

    <img src="dog.png" alt="Dog" class="pet-side pet-left">
    <img src="cat.png" alt="Cat" class="pet-side pet-right">

    <div class="login-card">
        <h2>Pet Grooming Login</h2>
        <p>Welcome back! Sign in to manage your pets and appointments.</p>

        <?php
        if (!empty($login_err)) {
            echo '<div class="w3-panel w3-red w3-round-large">' . htmlspecialchars($login_err) . '</div>';
        }
        ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label>Username</label>
            <input type="text" name="username" class="w3-input" value="<?php echo htmlspecialchars($last_username); ?>">
            <span class="w3-text-red"><?php echo $username_err; ?></span>

            <label>Password</label>
            <input type="password" name="password" class="w3-input">
            <span class="w3-text-red"><?php echo $password_err; ?></span>

            <div style="margin-top: 15px;">
                <input type="submit" class="login-btn" value="Login">
            </div>

            <div class="link-text">
                Don't have an account? <a href="register.php">Sign up now</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

