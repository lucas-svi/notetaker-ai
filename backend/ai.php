<?php
session_start(); // Start the session to manage user authentication
require 'db.php'; // Include database connection
require 'authenticated.php'; // Make sure user is authenticated

$username = $_SESSION['username'];
$message = '';


// AI
if (isset($_GET['note_id'])) {
    $note_id = intval($_GET['note_id']);
    $sql = "UPDATE notes SET note=? WHERE id = ? AND username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $note_text = "AI generated note";
    mysqli_stmt_bind_param($stmt, "sis", $note_text, $note_id, $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: dashboard.php');
    exit();
}
?>