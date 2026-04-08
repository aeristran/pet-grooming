<?php
session_start();

$_SESSION = array();

session_destroy();

setcookie("last_username", "", time() - 3600, "/");

header("location: login.php");
exit;
?>