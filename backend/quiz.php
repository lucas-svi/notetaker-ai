<?php
// quiz.php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];

// Check if note_id is provided
if (!isset($_GET['note_id']) || !ctype_digit($_GET['note_id'])) {
    header('Location: dashboard.php');
    exit();
}

$note_id = (int) $_GET['note_id'];

// Check if the note belongs to the user
$stmt = $conn->prepare("SELECT note FROM notes WHERE id = ? AND username = ?");
$stmt->bind_param("is", $note_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$note_data = $result->fetch_assoc();
$note_content = $note_data['note'];
$stmt->close();

// Get user's current quiz points
$stmt = $conn->prepare("SELECT quiz_points FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$current_points = $user_data['quiz_points'];
$stmt->close();

// Get quiz questions
$stmt = $conn->prepare("SELECT * FROM quiz WHERE note_id = ? ORDER BY difficulty ASC");
$stmt->bind_param("i", $note_id);
$stmt->execute();
$questions_result = $stmt->get_result();
$stmt->close();

// Process quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 0;
    $points_earned = 0;
    $difficulty_points = [
        'easy' => 10,
        'medium' => 20,
        'hard' => 50
    ];
    
    // Track user's answers and correct answers for each question
    $quiz_results = [];
    
    foreach ($_POST as $key => $answer) {
        if (strpos($key, 'question_') === 0) {
            $question_id = substr($key, 9);
            $total++;
            
            // Verify correct answer and get difficulty
            $stmt = $conn->prepare("SELECT question, choice_a, choice_b, choice_c, choice_d, correct_choice, difficulty FROM quiz WHERE id = ?");
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $question_difficulty = $row['difficulty'];
                $is_correct = ($answer === $row['correct_choice']);
                
                // Add to results array
                $quiz_results[] = [
                    'question' => $row['question'],
                    'user_answer' => $answer,
                    'correct_answer' => $row['correct_choice'],
                    'is_correct' => $is_correct,
                    'difficulty' => $question_difficulty,
                    'options' => [
                        'A' => $row['choice_a'],
                        'B' => $row['choice_b'],
                        'C' => $row['choice_c'],
                        'D' => $row['choice_d']
                    ]
                ];
                
                if ($is_correct) {
                    $score++;
                    // Award points based on difficulty
                    $points_earned += $difficulty_points[$question_difficulty];
                }
            }
            
            $stmt->close();
        }
    }
    
    // Save quiz results
    $stmt = $conn->prepare("INSERT INTO quiz_responses (username, note_id, score, total, points_earned) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiii", $username, $note_id, $score, $total, $points_earned);
    $stmt->execute();
    $stmt->close();
    
    // Update user's points
    $new_points = $current_points + $points_earned;
    $stmt = $conn->prepare("UPDATE users SET quiz_points = ? WHERE username = ?");
    $stmt->bind_param("is", $new_points, $username);
    $stmt->execute();
    $stmt->close();
    
    // Show results
    $quiz_taken = true;
    $quiz_score = $score;
    $quiz_total = $total;
    $quiz_points = $points_earned;
    $percentage = ($score / $total) * 100;
    $quiz_detailed_results = $quiz_results;
} else {
    $quiz_taken = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
        .quiz-container {
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
        
        .quiz-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .question-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .options-list {
            list-style-type: none;
            padding: 0;
        }
        
        .option-item {
            margin-bottom: 15px;
        }
        
        .option-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.2s;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        
        .option-label:hover {
            background-color: #f1f1f1;
        }
        
        input[type="radio"] {
            margin-right: 10px;
        }
        
        .submit-button {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin: 30px auto 0;
            min-width: 200px;
        }
        
        .submit-button:hover {
            background-color: #4cae4c;
        }
        
        .results-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .score-display {
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .percentage {
            font-size: 48px;
            font-weight: bold;
            color: #5cb85c;
            margin: 20px 0;
        }
        
        .points-earned {
            font-size: 20px;
            color: #66a3ff;
            margin-bottom: 25px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .action-button {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        
        .primary-button {
            background-color: #5cb85c;
            color: white;
        }
        
        .secondary-button {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .note-preview {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #5cb85c;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .no-questions {
            text-align: center;
            padding: 50px 0;
        }
        
        .generate-button {
            background-color: #66a3ff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin: 20px auto;
            min-width: 250px;
        }
        
        .generate-button:hover {
            background-color: #4d8ae5;
        }
        
        .difficulty-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .easy-tag {
            background-color: #5cb85c;
            color: white;
        }
        
        .medium-tag {
            background-color: #f0ad4e;
            color: white;
        }
        
        .hard-tag {
            background-color: #d9534f;
            color: white;
        }
        
        .detailed-results {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: left;
        }
        
        .result-item {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #eee;
        }
        
        .correct-answer {
            color: #5cb85c;
            font-weight: bold;
        }
        
        .wrong-answer {
            color: #d9534f;
            font-weight: bold;
        }
        
        .points-display {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 5px 10px;
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            color: #66a3ff;
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <div class="header-section">
            <h1>Quiz on Your Note</h1>
            <div>
                <span class="points-display">Your Points: <?php echo $current_points; ?></span>
                <a href="dashboard.php" class="action-button secondary-button" style="margin-left: 15px;">Back to Dashboard</a>
            </div>
        </div>
        
        <div class="note-preview">
            <h3>Based on your note:</h3>
            <p><?php echo nl2br(htmlspecialchars(substr($note_content, 0, 300))); ?>
            <?php if (strlen($note_content) > 300): ?>
                <span>...</span>
            <?php endif; ?>
            </p>
        </div>
        
        <?php if ($quiz_taken): ?>
            <div class="results-card">
                <h2>Quiz Results</h2>
                <div class="score-display">You scored: <?php echo $quiz_score; ?> out of <?php echo $quiz_total; ?></div>
                <div class="percentage"><?php echo round($percentage); ?>%</div>
                <div class="points-earned">+ <?php echo $quiz_points; ?> points earned!</div>
                
                <div class="action-buttons">
                    <a href="quiz.php?note_id=<?php echo $note_id; ?>" class="action-button primary-button">Take Quiz Again</a>
                    <a href="dashboard.php" class="action-button secondary-button">Back to Dashboard</a>
                </div>
                
                <div class="detailed-results">
                    <h3>Detailed Results:</h3>
                    <?php foreach ($quiz_detailed_results as $index => $result): ?>
                        <div class="result-item">
                            <p><strong>Q<?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($result['question']); ?>
                                <span class="difficulty-tag <?php echo $result['difficulty']; ?>-tag"><?php echo ucfirst($result['difficulty']); ?></span>
                            </p>
                            <p>Your answer: 
                                <span class="<?php echo $result['is_correct'] ? 'correct-answer' : 'wrong-answer'; ?>">
                                    <?php echo $result['user_answer']; ?>: <?php echo htmlspecialchars($result['options'][$result['user_answer']]); ?>
                                </span>
                            </p>
                            <?php if (!$result['is_correct']): ?>
                                <p>Correct answer: 
                                    <span class="correct-answer">
                                        <?php echo $result['correct_answer']; ?>: <?php echo htmlspecialchars($result['options'][$result['correct_answer']]); ?>
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($questions_result->num_rows > 0): ?>
            <form method="post" action="">
                <?php 
                $question_num = 1;
                while ($question = $questions_result->fetch_assoc()): 
                    $difficulty_class = $question['difficulty'] . '-tag';
                ?>
                    <div class="quiz-card">
                        <div class="question-text">
                            <strong>Question <?php echo $question_num; ?>:</strong> 
                            <?php echo htmlspecialchars($question['question']); ?>
                            <span class="difficulty-tag <?php echo $difficulty_class; ?>"><?php echo ucfirst($question['difficulty']); ?></span>
                        </div>
                        <ul class="options-list">
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="A" required>
                                    A: <?php echo htmlspecialchars($question['choice_a']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="B">
                                    B: <?php echo htmlspecialchars($question['choice_b']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="C">
                                    C: <?php echo htmlspecialchars($question['choice_c']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="D">
                                    D: <?php echo htmlspecialchars($question['choice_d']); ?>
                                </label>
                            </li>
                        </ul>
                    </div>
                <?php 
                    $question_num++;
                endwhile; 
                ?>
                
                <button type="submit" name="submit_quiz" class="submit-button">Submit Answers</button>
            </form>
        <?php else: ?>
            <div class="no-questions">
                <h2>No quiz questions found for this note</h2>
                <p>Generate multiple choice questions based on your note content.</p>
                <a href="quiz_generate.php?note_id=<?php echo $note_id; ?>" class="generate-button">Generate Quiz Questions</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?> 