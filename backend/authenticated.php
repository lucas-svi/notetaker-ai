<?php
if (!isset($_SESSION['username'])) {
    // Redirect to main page if not logged in
    header('Location: ../?auth=signin');
    exit;
}
?>