<?php
session_start();
require 'db.php';

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

$sql = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p>Invalid Password</p>";
    }
} else {
    echo "<p>User not found</p>";
}
mysqli_close($conn);
?>