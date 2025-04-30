<?php
// quiz_view.php - Uses REST API instead of direct database operations
session_start();
require 'authenticated.php';

$username = $_SESSION['username'];

// Check if note_id is provided
if (!isset($_GET['note_id']) || !ctype_digit($_GET['note_id'])) {
    header('Location: dashboard.php');
    exit();
}

$note_id = (int) $_GET['note_id'];
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

// Fetch the note content from the API
$noteResponse = callAPI("GET", "/note/getNote", ["id" => $note_id]);
$note_content = isset($noteResponse['note']) ? $noteResponse['note'] : "Loading note content...";

// Get user's current quiz points from quiz history API
$historyResponse = callAPI("GET", "/quiz/getHistory", ["username" => $username]);
$current_points = 0;
if (isset($historyResponse['success']) && $historyResponse['success'] && isset($historyResponse['stats']['current_points'])) {
    $current_points = $historyResponse['stats']['current_points'];
}

// Process quiz submission
$quiz_taken = false;
$quiz_score = 0;
$quiz_total = 0;
$quiz_points = 0;
$percentage = 0;
$quiz_detailed_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $answers = [];
    foreach ($_POST as $key => $answer) {
        if (strpos($key, 'question_') === 0) {
            $question_id = substr($key, 9);
            $answers[$question_id] = $answer;
        }
    }
    
    // Submit quiz answers to API
    $response = callAPI("POST", "/quiz/submitQuiz", [
        "username" => $username,
        "note_id" => $note_id,
        "answers" => $answers
    ]);
    
    if (isset($response['success']) && $response['success']) {
        $quiz_taken = true;
        $result_data = $response['results'];
        $quiz_score = $result_data['score'];
        $quiz_total = $result_data['total'];
        $quiz_points = $result_data['points_earned'];
        $percentage = $result_data['percentage'];
        $quiz_detailed_results = $result_data['detailed_results'];
        
        // Update current points with the new value from the quiz submission result
        $current_points = $result_data['new_points'];
    }
}

// Get quiz questions
$questions_result = callAPI("GET", "/quiz/getQuiz", ["note_id" => $note_id]);
$has_questions = isset($questions_result['success']) && $questions_result['success'] && !empty($questions_result['questions']);
$questions = $has_questions ? $questions_result['questions'] : [];

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
        
        <?php if ($quiz_taken): ?>
            <div class="results-card">
                <h2>Quiz Results</h2>
                <div class="score-display">You scored: <?php echo $quiz_score; ?> out of <?php echo $quiz_total; ?></div>
                <div class="percentage"><?php echo round($percentage); ?>%</div>
                <div class="points-earned">+ <?php echo $quiz_points; ?> points earned!</div>
                
                <div class="action-buttons">
                    <a href="quiz_view.php?note_id=<?php echo $note_id; ?>" class="action-button primary-button">Take Quiz Again</a>
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
        <?php elseif ($has_questions): ?>
            <form method="post" action="">
                <?php 
                $question_num = 1;
                foreach ($questions as $question): 
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
                                    A: <?php echo htmlspecialchars($question['options']['A']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="B">
                                    B: <?php echo htmlspecialchars($question['options']['B']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="C">
                                    C: <?php echo htmlspecialchars($question['options']['C']); ?>
                                </label>
                            </li>
                            <li class="option-item">
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="D">
                                    D: <?php echo htmlspecialchars($question['options']['D']); ?>
                                </label>
                            </li>
                        </ul>
                    </div>
                <?php 
                    $question_num++;
                endforeach; 
                ?>
                
                <button type="submit" name="submit_quiz" class="submit-button">Submit Answers</button>
            </form>
        <?php else: ?>
            <div class="no-questions">
                <h2>No quiz questions found for this note</h2>
                <p>Generate multiple choice questions based on your note content.</p>
                <button onclick="generateQuiz()" class="generate-button">Generate Quiz Questions</button>
            </div>
            
            <script>
                function generateQuiz() {
                    // Show loading indicator
                    document.querySelector('.no-questions').innerHTML = '<h2>Generating quiz questions...</h2><p>Please wait while we analyze your note and create questions.</p>';
                    
                    // Make API call to generate quiz
                    fetch('<?php echo $api_base_url; ?>/quiz/generateQuiz?note_id=<?php echo $note_id; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to show the quiz
                            window.location.reload();
                        } else {
                            document.querySelector('.no-questions').innerHTML = '<h2>Error</h2><p>' + (data.error || 'Failed to generate quiz questions. Please try again.') + '</p><button onclick="generateQuiz()" class="generate-button">Try Again</button>';
                        }
                    })
                    .catch(error => {
                        document.querySelector('.no-questions').innerHTML = '<h2>Error</h2><p>Failed to generate quiz questions. Please try again.</p><button onclick="generateQuiz()" class="generate-button">Try Again</button>';
                        console.error('Error:', error);
                    });
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html> 