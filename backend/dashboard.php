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

require 'db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Wassup, <?php echo $username; ?>!</h1>
    <h2>Create new note</h2>
    <form action="create_note.php" method="POST">
        <textarea name="note" rows="10" cols="100" placeholder="Write note here..." required></textarea><br>
        <button type="submit">Create Note</button>
    </form>

</body>
</html>
