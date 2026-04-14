<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'ADMIN') {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #dff6f0, #fef6e4);
            min-height: 100vh;
        }

        .page {
            padding: 40px 20px;
        }

        .dashboard-card {
            max-width: 1100px;
            margin: auto;
            background: rgba(255,255,255,0.96);
            border-radius: 24px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            padding: 30px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 42px;
            font-weight: bold;
        }

        .dashboard-header p {
            margin-top: 10px;
            font-size: 18px;
            opacity: 0.95;
        }

        .dashboard-body {
            padding: 30px;
        }

        .section-title {
            margin-bottom: 20px;
            color: #2d6a4f;
            font-size: 24px;
            font-weight: bold;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .action-card {
            display: block;
            text-decoration: none;
            background: #f8fafc;
            border-radius: 18px;
            padding: 25px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: 0.25s ease;
            border-left: 6px solid #52b788;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 18px rgba(0,0,0,0.12);
            background: #ffffff;
        }

        .action-card h3 {
            margin: 0 0 10px 0;
            color: #1b4332;
            font-size: 22px;
        }

        .action-card p {
            margin: 0;
            color: #555;
            font-size: 15px;
            line-height: 1.5;
        }

        .bottom-bar {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .welcome-box {
            background: #edfdf5;
            border-left: 5px solid #40916c;
            padding: 14px 18px;
            border-radius: 12px;
            color: #1b4332;
            font-size: 16px;
        }

        .logout-btn {
            background: #e76f51 !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px 22px !important;
            font-size: 16px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #d95d39 !important;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="dashboard-card">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Manage employees, services, and appointments from one place.</p>
        </div>

        <div class="dashboard-body">
            <div class="section-title">Quick Actions</div>

            <div class="card-grid">
                <a href="manage_employee.php" class="action-card">
                    <h3>Manage Employees</h3>
                    <p>View and manage existing employee accounts.</p>
                </a>

                <a href="manage_services.php" class="action-card">
                    <h3>Manage Services</h3>
                    <p>Add, activate, deactivate, and organize grooming services.</p>
                </a>

                <a href="view_appointments.php" class="action-card">
                    <h3>View Appointments</h3>
                    <p>See all scheduled appointments and customer booking details.</p>
                </a>
            </div>

            <div class="bottom-bar">
                <div class="welcome-box">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>
                </div>

                <a class="w3-button logout-btn" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>