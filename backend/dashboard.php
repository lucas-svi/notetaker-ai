<?php
// dashboard.php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];

// Get user's notes
$stmt_user_notes = $conn->prepare("
    SELECT id, note, username 
    FROM notes 
    WHERE username = ? 
");
$stmt_user_notes->bind_param("s", $username);
$stmt_user_notes->execute();
$user_notes_result = $stmt_user_notes->get_result();

// Get notes from everyone else
$stmt_other_notes = $conn->prepare("
    SELECT id, note, username 
    FROM notes 
    WHERE username != ? 
");
$stmt_other_notes->bind_param("s", $username);
$stmt_other_notes->execute();
$other_notes_result = $stmt_other_notes->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>

    <h3>Create New Note</h3>
    <form action="note.php" method="POST">
        <textarea name="note" rows="5" cols="100" placeholder="Write note here..." required></textarea><br>
        <button type="submit">Create Note</button>
    </form>

    <!-- Show, Edit, or Delete Your Notes -->
    <h2>Your Notes</h2>
    <?php if ($user_notes_result->num_rows > 0): ?>
        <ul>
            <?php while($row = $user_notes_result->fetch_assoc()): ?>
                <li style="margin-bottom:10px;">
                    <?php echo nl2br(htmlspecialchars($row['note'])); ?>

                    <!-- Show Edit and Delete links only for the logged-in user's notes -->
                    [<a href="note.php?edit=<?php echo $row['id']; ?>">Edit</a>]
                    [<a href="note.php?delete=<?php echo $row['id']; ?>">Delete</a>]
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No notes found.</p>
    <?php endif; ?>

    <!-- Show other notes -->
    <h2>Other People's Notes</h2>
    <?php if ($other_notes_result->num_rows > 0): ?>
        <ul>
            <?php while($row = $other_notes_result->fetch_assoc()): ?>
                <li style="margin-bottom:10px;">
                    <strong>
                    <?php echo htmlspecialchars($row['username']); ?>'s Note:</strong><br>
                    <?php echo nl2br(htmlspecialchars($row['note'])); ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No other notes found.</p>
    <?php endif; ?>

    <form action="logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
</body>
</html>

<?php
$stmt_user_notes->close();
$stmt_other_notes->close();
$conn->close();
?>
