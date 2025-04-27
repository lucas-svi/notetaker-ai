<?php
// === CONFIG & BOILERPLATE ===
// Import the API key from the environment variable
include 'api_key.php' ;
if (empty($geminiApiKey)) {
    die('Gemini API key not configured.');
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

// === 3) CALL GEMINI API VIA CURL TO GENERATE QUESTIONS OF VARYING DIFFICULTY ===
$endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';
$headers  = [
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
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $prompt
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 1500
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_ONLY_HIGH'
        ]
    ]
]);

$url = $endpoint . '?key=' . $geminiApiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log('Gemini cURL error: ' . curl_error($ch));
    die('Failed to generate quiz questions. Please try again later.');
}

curl_close($ch);

// === 4) PROCESS THE RESPONSE ===
$data = json_decode($response, true);

// Check if the response contains valid content
if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    error_log('Gemini response error: ' . $response);
    die('Failed to generate valid quiz questions. Please try again later.');
}

// Parse the JSON content containing the questions
$questions_json = $data['candidates'][0]['content']['parts'][0]['text'];

// Clean the response if it contains markdown code blocks
$questions_json = preg_replace('/```json\s*([\s\S]*?)\s*```/', '$1', $questions_json);
$questions_json = preg_replace('/```\s*([\s\S]*?)\s*```/', '$1', $questions_json);

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