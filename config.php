<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');   // change if needed
define('DB_PASSWORD', '');       // change if needed
define('DB_NAME', 'pet_grooming_system');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>