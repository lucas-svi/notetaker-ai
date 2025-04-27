<?php
// quiz_history.php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];

// Get user's quiz points
$stmt = $conn->prepare("SELECT quiz_points FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$current_points = $user_data['quiz_points'];
$stmt->close();

// Create quiz_responses table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS quiz_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    note_id INT,
    score INT,
    total INT,
    points_earned INT DEFAULT 0,
    time_taken TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
)");

// Get quiz history
$stmt = $conn->prepare("
    SELECT qr.id, qr.note_id, qr.score, qr.total, qr.points_earned, qr.time_taken, 
           SUBSTRING(n.note, 1, 100) AS note_preview
    FROM quiz_responses qr
    JOIN notes n ON qr.note_id = n.id
    WHERE qr.username = ?
    ORDER BY qr.time_taken DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$history_result = $stmt->get_result();
$stmt->close();

// Calculate total points earned from quizzes
$total_points_earned = 0;
if ($history_result->num_rows > 0) {
    $temp_result = $history_result;
    while ($row = $temp_result->fetch_assoc()) {
        $total_points_earned += $row['points_earned'];
    }
    // Reset the result pointer
    $history_result->data_seek(0);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz History</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
        .history-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .history-table th, .history-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .history-table th {
            background-color: #f9f9f9;
            font-weight: bold;
            color: #333;
        }
        
        .history-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .score-cell {
            font-weight: bold;
        }
        
        .high-score {
            color: #5cb85c;
        }
        
        .medium-score {
            color: #f0ad4e;
        }
        
        .low-score {
            color: #d9534f;
        }
        
        .note-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #5cb85c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .action-button:hover {
            background-color: #4cae4c;
        }
        
        .secondary-button {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .secondary-button:hover {
            background-color: #e0e0e0;
        }
        
        .empty-history {
            text-align: center;
            padding: 50px 0;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .points-display {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 15px;
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            color: #66a3ff;
        }
        
        .points-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .points-summary-item {
            text-align: center;
        }
        
        .points-summary-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .points-summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #66a3ff;
        }
        
        .points-cell {
            color: #66a3ff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="history-container">
        <div class="header-section">
            <h1>Your Quiz History</h1>
            <div>
                <span class="points-display">Total Points: <?php echo $current_points; ?></span>
                <a href="dashboard.php" class="action-button secondary-button" style="margin-left: 15px;">Back to Dashboard</a>
            </div>
        </div>
        
        <?php if ($history_result->num_rows > 0): ?>
            <div class="points-summary">
                <div class="points-summary-item">
                    <div class="points-summary-label">Quizzes Taken</div>
                    <div class="points-summary-value"><?php echo $history_result->num_rows; ?></div>
                </div>
                <div class="points-summary-item">
                    <div class="points-summary-label">Total Points Earned</div>
                    <div class="points-summary-value"><?php echo $total_points_earned; ?></div>
                </div>
                <div class="points-summary-item">
                    <div class="points-summary-label">Average Score</div>
                    <?php
                    $total_percentage = 0;
                    $temp_result = $history_result;
                    while ($row = $temp_result->fetch_assoc()) {
                        $total_percentage += ($row['score'] / $row['total']) * 100;
                    }
                    $avg_percentage = $history_result->num_rows > 0 ? 
                        round($total_percentage / $history_result->num_rows) : 0;
                    $history_result->data_seek(0);
                    ?>
                    <div class="points-summary-value"><?php echo $avg_percentage; ?>%</div>
                </div>
            </div>
            
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Note</th>
                        <th>Score</th>
                        <th>Points</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $history_result->fetch_assoc()): 
                        $percentage = ($row['score'] / $row['total']) * 100;
                        $score_class = '';
                        
                        if ($percentage >= 80) {
                            $score_class = 'high-score';
                        } elseif ($percentage >= 60) {
                            $score_class = 'medium-score';
                        } else {
                            $score_class = 'low-score';
                        }
                    ?>
                        <tr>
                            <td><?php echo date('M j, Y, g:i a', strtotime($row['time_taken'])); ?></td>
                            <td class="note-preview"><?php echo htmlspecialchars($row['note_preview']); ?>...</td>
                            <td class="score-cell <?php echo $score_class; ?>">
                                <?php echo $row['score']; ?>/<?php echo $row['total']; ?> 
                                (<?php echo round($percentage); ?>%)
                            </td>
                            <td class="points-cell">
                                +<?php echo $row['points_earned']; ?>
                            </td>
                            <td>
                                <a href="quiz.php?note_id=<?php echo $row['note_id']; ?>" class="action-button">Take Again</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-history">
                <h2>No quiz history found</h2>
                <p>You haven't taken any quizzes yet. Go back to your notes and try the Quiz feature.</p>
                <a href="dashboard.php" class="action-button" style="margin-top: 20px;">Back to Notes</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?> 