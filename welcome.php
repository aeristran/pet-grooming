<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

$role = $_SESSION["role"];
$username = $_SESSION["username"];

if ($role === "ADMIN") {
    $dashboard_link = "admin_dashboard.php";
    $dashboard_text = "Go to Admin Dashboard";
    $role_color = "#3b82f6";
    $role_icon = "👑";
} elseif ($role === "EMPLOYEE") {
    $dashboard_link = "employee_dashboard.php";
    $dashboard_text = "Go to Employee Dashboard";
    $role_color = "#22c55e";
    $role_icon = "✂️";
} else {
    $dashboard_link = "customer_dashboard.php";
    $dashboard_text = "Go to Customer Dashboard";
    $role_color = "#14b8a6";
    $role_icon = "🐾";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Pet Grooming System</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f7f2, #fdf6e3);
            min-height: 100vh;
        }

        .page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .welcome-card {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 24px;
            padding: 40px 35px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            text-align: center;
            animation: fadeUp 0.8s ease;
        }

        .welcome-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .welcome-card h1 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 36px;
        }

        .welcome-card p {
            color: #4b5563;
            font-size: 18px;
            line-height: 1.6;
            margin: 8px 0;
        }

        .username {
            color: #111827;
            font-weight: bold;
        }

        .role-badge {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            border-radius: 999px;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .button-group {
            margin-top: 30px;
        }

        .main-btn {
            display: inline-block;
            padding: 14px 26px;
            border-radius: 14px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            font-size: 18px;
            background: <?php echo $role_color; ?>;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .main-btn:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .logout-link {
            display: inline-block;
            margin-top: 22px;
            color: #374151;
            font-weight: bold;
            text-decoration: none;
        }

        .logout-link:hover {
            text-decoration: underline;
        }

        .paw-row {
            margin-top: 25px;
            font-size: 24px;
            opacity: 0.7;
            letter-spacing: 8px;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .welcome-card {
                padding: 30px 20px;
            }

            .welcome-card h1 {
                font-size: 28px;
            }

            .welcome-card p {
                font-size: 16px;
            }

            .main-btn {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="welcome-card">
        <div class="welcome-icon"><?php echo $role_icon; ?></div>

        <h1>Welcome Back</h1>

        <p>Hi, <span class="username"><?php echo htmlspecialchars($username); ?></span></p>
        <p>Welcome to our Pet Grooming System.</p>
        <p>This page is protected, so only authenticated users can access it.</p>

        <div class="role-badge" style="background: <?php echo $role_color; ?>;">
            Your role: <?php echo htmlspecialchars($role); ?>
        </div>

        <div class="button-group">
            <a class="main-btn" href="<?php echo $dashboard_link; ?>">
                <?php echo htmlspecialchars($dashboard_text); ?>
            </a>
        </div>

        <a class="logout-link" href="logout.php">Sign out of your account</a>

        <div class="paw-row">🐾 🐾 🐾</div>
    </div>
</div>

</body>
</html>