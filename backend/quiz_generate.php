<?php
// === CONFIG & BOILERPLATE ===
$openaiApiKey = getenv('OPENAI_API_KEY');
if (empty($openaiApiKey)) {
    die('OpenAI API key not configured.');
}

// Include database connection
require __DIR__ . '/db.php';

session_start();

// === 1) VALIDATE INPUT & AUTH ===
if (!isset($_GET['note_id']) || !ctype_digit($_GET['note_id'])) {
    die('Invalid note ID.');
}
$note_id = (int) $_GET['note_id'];

if (empty($_SESSION['username'])) {
    die('Not logged in.');
}
$username = $_SESSION['username'];

// === 2) FETCH THE NOTE CONTENT ===
$stmt = $conn->prepare("SELECT note FROM notes WHERE id = ? AND username = ?");
$stmt->bind_param("is", $note_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Note not found or unauthorized access.');
}

$note_data = $result->fetch_assoc();
$note_content = $note_data['note'];
$stmt->close();

// === 3) CALL OPENAI VIA CURL TO GENERATE QUESTIONS OF VARYING DIFFICULTY ===
$endpoint = 'https://api.openai.com/v1/chat/completions';
$headers  = [
    'Authorization: Bearer ' . $openaiApiKey,
    'Content-Type: application/json',
];

$prompt = <<<EOT
Based on the following note, generate 5 multiple choice questions to test knowledge of the content with varying difficulty levels:
- 2 easy questions (basic understanding)
- 2 medium questions (intermediate understanding)
- 1 hard question (advanced understanding/critical thinking)

For each question:
1. Write a clear question
2. Provide 4 options labeled A, B, C, and D
3. Indicate which option is the correct answer
4. Specify the difficulty level as "easy", "medium", or "hard"

Format your response as a JSON array with this structure:
[
  {
    "question": "Question text here?",
    "options": {
      "A": "First option",
      "B": "Second option",
      "C": "Third option",
      "D": "Fourth option"
    },
    "correct": "A",
    "difficulty": "easy"
  },
  ...more questions...
]

Make sure to generate exactly 2 easy, 2 medium, and 1 hard question.

Here is the note content:
$note_content
EOT;

$body = json_encode([
    'model'     => 'gpt-4o-mini',
    'messages'  => [
        ['role' => 'system', 'content' => 'You are a quiz generator that creates multiple choice questions based on note content.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    'max_tokens'  => 1500,
    'temperature' => 0.4,
    'response_format' => ['type' => 'json_object'],
]);

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log('OpenAI cURL error: ' . curl_error($ch));
    die('Failed to generate quiz questions. Please try again later.');
}

curl_close($ch);

// === 4) PROCESS THE RESPONSE ===
$data = json_decode($response, true);

// Check if the response contains valid JSON with the questions
if (!isset($data['choices'][0]['message']['content'])) {
    error_log('OpenAI response error: ' . $response);
    die('Failed to generate valid quiz questions. Please try again later.');
}

// Parse the JSON content containing the questions
$questions_json = $data['choices'][0]['message']['content'];
$questions = json_decode($questions_json, true);

if (!is_array($questions)) {
    error_log('Invalid questions format: ' . $questions_json);
    die('Failed to parse quiz questions. Please try again later.');
}

// Validate question distribution
$difficulty_counts = ['easy' => 0, 'medium' => 0, 'hard' => 0];
foreach ($questions as $q) {
    if (isset($q['difficulty']) && in_array($q['difficulty'], array_keys($difficulty_counts))) {
        $difficulty_counts[$q['difficulty']]++;
    }
}

if ($difficulty_counts['easy'] != 2 || $difficulty_counts['medium'] != 2 || $difficulty_counts['hard'] != 1) {
    error_log('Incorrect distribution of question difficulties: ' . json_encode($difficulty_counts));
    // We'll continue anyway, but log the issue
}

// === 5) SAVE QUESTIONS TO DATABASE ===
// First delete any existing questions for this note
$stmt = $conn->prepare("DELETE FROM quiz WHERE note_id = ?");
$stmt->bind_param("i", $note_id);
$stmt->execute();
$stmt->close();

// Insert the new questions
$stmt = $conn->prepare("INSERT INTO quiz (note_id, question, choice_a, choice_b, choice_c, choice_d, correct_choice, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($questions as $q) {
    $question = $q['question'];
    $optionA = $q['options']['A'];
    $optionB = $q['options']['B'];
    $optionC = $q['options']['C'];
    $optionD = $q['options']['D'];
    $correct = $q['correct'];
    $difficulty = isset($q['difficulty']) ? $q['difficulty'] : 'medium'; // Default to medium if not specified
    
    $stmt->bind_param("isssssss", $note_id, $question, $optionA, $optionB, $optionC, $optionD, $correct, $difficulty);
    $stmt->execute();
}

$stmt->close();

// === 6) REDIRECT BACK ===
header('Location: quiz.php?note_id=' . $note_id);
exit();
?> 