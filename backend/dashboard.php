<?php
// dashboard.php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];

// Fetch notes from database
$stmt = $conn->prepare("SELECT id, note FROM notes WHERE username = ? ORDER BY id ASC");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
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

    <h2>Your Notes:</h2>
    <?php if ($result->num_rows > 0): $note_num = 1;?>
        <ul>
            <?php while($row = $result->fetch_assoc()):?> 
                <li>
                    <strong>Note <?php echo $note_num;?>:</strong> 
                    <?php echo htmlspecialchars($row['note']); ?>
                    [<a href="note.php?edit=<?php echo $row['id']; $note_num++;?>">Edit</a>]
                    [<a href="note.php?delete=<?php echo $row['id'];?>">Delete</a>]
                </li>
        </ul>
    <?php endwhile; ?>
    <?php else: ?>
        <p>No notes found.</p>
    <?php endif; ?>

    <form action="logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>