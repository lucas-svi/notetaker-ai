<?php 
// Create note code

require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to main page if not logged in
    header('Location: ../?auth=signin');
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['note'])) {
    // Get note, preventing SQL injections
    $note = mysqli_real_escape_string($conn,$_POST['note']);
    if (!empty($note)) {
        // Create query and execute
        $sql = "INSERT INTO notes (username, note) VALUES ('$username', '$note')";
        if (mysqli_query($conn, $sql)) {
            header('Location: dashboard.php');
          exit();
        } else {
            $message = "<p style='color: red;'>An error occurred creating note. Please try again later.</p>";
        }
    } else {
        echo "<p>Please enter a note!</p>";
    }
}

?>