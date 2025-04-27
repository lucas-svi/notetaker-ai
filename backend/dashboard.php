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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes Dashboard</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            width: 100%;
        }
        
        .note-form-container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 800px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .note-form {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .notes-section {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
        }
        
        .section-header {
            border-bottom: 2px solid #5cb85c;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #333;
        }
        
        .notes-list {
            list-style-type: none;
            padding: 0;
        }
        
        .note-item {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #5cb85c;
        }
        
        .note-content {
            margin-bottom: 15px;
            line-height: 1.6;
            text-align: left;
        }
        
        .note-actions {
            text-align: right;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .action-link {
            display: inline-block;
            color: #5cb85c;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .other-note-item {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #66a3ff;
        }
        
        .note-author {
            font-weight: bold;
            color: #66a3ff;
            margin-bottom: 12px;
            text-align: left;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
            font-size: 16px;
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
        }
        
        button {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 12px 50px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            min-width: 200px;
        }
        
        button:hover {
            background-color: #4cae4c;
        }
        
        .logout-button {
            background-color: #f44336;
        }
        
        .logout-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="welcome-header">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
            <div style="display: flex; gap: 10px;">
                <a href="quiz_history.php" style="text-decoration: none;">
                    <button type="button" style="background-color: #66a3ff;">Quiz History</button>
                </a>
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            </div>
        </div>

        <div class="note-form-container">
            <h2 class="section-header">Create New Note</h2>
            <form action="note.php" method="POST" class="note-form">
                <textarea name="note" rows="4" placeholder="Write your note here..." required></textarea>
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit">Create Note</button>
                </div>
            </form>
        </div>

        <div class="notes-section">
            <h2 class="section-header">Your Notes</h2>
            <?php if ($user_notes_result->num_rows > 0): ?>
                <ul class="notes-list">
                    <?php while($row = $user_notes_result->fetch_assoc()): ?>
                        <li class="note-item">
                            <div class="note-content">
                                <?php echo nl2br(htmlspecialchars($row['note'])); ?>
                            </div>
                            <div class="note-actions">
                                <a href="ai.php?note_id=<?php echo $row['id']; ?>" class="action-link">AI</a>
                                <a href="quiz.php?note_id=<?php echo $row['id']; ?>" class="action-link">Quiz</a>
                                <a href="note.php?edit=<?php echo $row['id']; ?>" class="action-link">Edit</a>
                                <a href="note.php?delete=<?php echo $row['id']; ?>" class="action-link">Delete</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>You haven't created any notes yet.</p>
            <?php endif; ?>
        </div>

        <div class="notes-section">
            <h2 class="section-header">Community Notes</h2>
            <?php if ($other_notes_result->num_rows > 0): ?>
                <ul class="notes-list">
                    <?php while($row = $other_notes_result->fetch_assoc()): ?>
                        <li class="other-note-item">
                            <div class="note-author">
                                <?php echo htmlspecialchars($row['username']); ?>
                            </div>
                            <div class="note-content">
                                <?php echo nl2br(htmlspecialchars($row['note'])); ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No community notes found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt_user_notes->close();
$stmt_other_notes->close();
$conn->close();
?>
