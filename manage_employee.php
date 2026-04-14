<?php
session_start();
require_once "config.php";

/* Only ADMIN can access */
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "ADMIN") {
    header("location: login.php");
    exit;
}

$employees = [];

$sql = "SELECT user_id, username, first_name, last_name, email, role
        FROM users
        WHERE role = 'EMPLOYEE'
        ORDER BY first_name ASC";

$result = mysqli_query($link, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Employees</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: Arial;
        }

        .wrapper {
            width: 1100px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <h2>All Employees</h2>
        <p>Admin can manage employee accounts here.</p>

        <div class="w3-margin-bottom">
            <a href="admin_dashboard.php" class="w3-button w3-gray">Back</a>
            <a href="addEmployee.php" class="w3-button w3-green">+ Add Employee</a>
        </div>

        <?php if (empty($employees)) { ?>
            <div class="w3-panel w3-yellow w3-round">
                <p>No employees found.</p>
            </div>
        <?php } else { ?>

            <table class="w3-table w3-bordered w3-striped">
                <tr class="w3-teal">
                    <th>ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($employees as $emp) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($emp["user_id"]); ?></td>
                        <td><?php echo htmlspecialchars($emp["username"]); ?></td>
                        <td>
                            <?php echo htmlspecialchars($emp["first_name"] . " " . $emp["last_name"]); ?>
                        </td>
                        <td><?php echo htmlspecialchars($emp["email"]); ?></td>

                        <td>
                            <a href="delete_employee.php?id=<?php echo $emp["user_id"]; ?>"
                               class="w3-button w3-red w3-small"
                               onclick="return confirm('Delete this employee?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php } ?>

            </table>

        <?php } ?>
    </div>
</div>

</body>
</html>