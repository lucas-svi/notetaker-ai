<?php
$db_servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "app-db";

$conn = mysqli_connect($db_servername, $db_username, $db_password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>