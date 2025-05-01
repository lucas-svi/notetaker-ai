<?php
// quiz_history_view.php - Uses REST API instead of direct database operations
session_start();
require 'authenticated.php';

$username = $_SESSION['username'];
$api_base_url = "http://{$_SERVER['HTTP_HOST']}/notetaker-ai/backend/index.php";

// Function to make API calls
function callAPI($method, $endpoint, $data = false) {
    global $api_base_url;
    $url = $api_base_url . $endpoint;
    
    $curl = curl_init();
    
    switch ($method) {
        case "GET":
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
            break;
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        default:
            break;
    }
    
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    
    $result = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($result, true);
}

// Get quiz history data from API
$history_data = callAPI("GET", "/quiz/getHistory", ["username" => $username]);

// Check if we have valid data
$has_history = isset($history_data['success']) && $history_data['success'] && !empty($history_data['history']);

// Extract data
$current_points = isset($history_data['stats']['current_points']) ? $history_data['stats']['current_points'] : 0;
$total_quizzes = isset($history_data['stats']['total_quizzes']) ? $history_data['stats']['total_quizzes'] : 0;
$total_points_earned = isset($history_data['stats']['total_points_earned']) ? $history_data['stats']['total_points_earned'] : 0;
$avg_percentage = isset($history_data['stats']['avg_percentage']) ? $history_data['stats']['avg_percentage'] : 0;
$history_entries = $has_history ? $history_data['history'] : [];

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
        
        <?php if ($has_history): ?>
            <div class="points-summary">
                <div class="points-summary-item">
                    <div class="points-summary-label">Quizzes Taken</div>
                    <div class="points-summary-value"><?php echo $total_quizzes; ?></div>
                </div>
                <div class="points-summary-item">
                    <div class="points-summary-label">Total Points Earned</div>
                    <div class="points-summary-value"><?php echo $total_points_earned; ?></div>
                </div>
                <div class="points-summary-item">
                    <div class="points-summary-label">Average Score</div>
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
                    <?php foreach ($history_entries as $entry): ?>
                        <tr>
                            <td><?php echo date('M j, Y, g:i a', strtotime($entry['date_taken'])); ?></td>
                            <td class="note-preview"><?php echo htmlspecialchars($entry['note_preview']); ?>...</td>
                            <td class="score-cell <?php echo $entry['score_class']; ?>">
                                <?php echo $entry['score']; ?>/<?php echo $entry['total']; ?> 
                                (<?php echo $entry['percentage']; ?>%)
                            </td>
                            <td class="points-cell">
                                +<?php echo $entry['points_earned']; ?>
                            </td>
                            <td>
                                <a href="quiz_view.php?note_id=<?php echo $entry['note_id']; ?>" class="action-button">Take Again</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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