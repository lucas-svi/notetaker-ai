<?php
// === CONFIG & BOILERPLATE ===
// Import the API key from the api_key php file
include 'api_key.php' ;
if (empty($geminiApiKey)) {
    die('Gemini API key not configured.');
}

// Include your database connection (adjust path as needed)
require __DIR__ . '/db.php';  // must set up $conn = mysqli_connect(...);

session_start();

// === 1) VALIDATE INPUT & AUTH ===
if ( ! isset($_GET['note_id']) || ! ctype_digit($_GET['note_id']) ) {
    die('Invalid note ID.');
}
$note_id = (int) $_GET['note_id'];

if ( empty($_SESSION['username']) ) {
    die('Not logged in.');
}
$username = $_SESSION['username'];

// === 2) CALL GEMINI API VIA CURL ===
$endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';
$headers  = [
    'Content-Type: application/json',
];

$note_content = mysqli_query($conn, "SELECT note FROM notes WHERE id = {$note_id} AND username = '{$username}'");
$note_content = mysqli_fetch_assoc($note_content);
$note_content = $note_content['note'];

$body = json_encode([
    'contents' => [
        [
            'parts' => [
                [
                    'text' => "You are a study assistant that creates detailed, structured summaries for notes. Here are the notes to summarize:\n\n{$note_content}"
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 1000
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
    $note_text = 'AI summary unavailable at the moment.';
} else {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $note_text = trim($data['candidates'][0]['content']['parts'][0]['text']);
    } else {
        error_log('Gemini response error: ' . $response);
        $note_text = 'AI summary unavailable at the moment.';
    }
}
curl_close($ch);

// === 3) PREPARE & EXECUTE UPDATE ===
$sql = "UPDATE `notes`
        SET `note`     = ?
        WHERE `id`      = ?
          AND `username`= ?";
$stmt = mysqli_prepare($conn, $sql);
if ( ! $stmt ) {
    die('DB prepare failed: ' . mysqli_error($conn));
}

// Must bind variables, not literals:
mysqli_stmt_bind_param($stmt, 'sis', $note_text, $note_id, $username);

if ( ! mysqli_stmt_execute($stmt) ) {
    die('DB execute failed: ' . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

// === 4) REDIRECT BACK ===
header('Location: dashboard.php');
exit();