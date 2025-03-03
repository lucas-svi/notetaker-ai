<?php
// dashboard.php
session_start();

// Check if the user is logged in by verifying the username session variable exists
if (!isset($_SESSION['username'])) {
    // Redirect to main page if not logged in
    header('Location: ../?auth=signin');
    exit;
}

// Now we can retrieve and output the username if alrdy in DB
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Wassup, <?php echo $username; ?>!</h1>
</body>
</html>
