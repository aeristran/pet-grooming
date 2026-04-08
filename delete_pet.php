<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'CUSTOMER') {
    header("location: login.php");
    exit;
}

if (isset($_GET["id"]) && !empty($_GET["id"])) {
    $pet_id = $_GET["id"];
    $customer_id = $_SESSION["user_id"];

    $sql = "DELETE FROM pets WHERE pet_id = ? AND customer_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $pet_id, $customer_id);

        if (mysqli_stmt_execute($stmt)) {
            header("location: customer_dashboard.php");
            exit;
        } else {
            echo "Something went wrong while deleting the pet.";
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($link);
header("location: customer_dashboard.php");
exit;
?>