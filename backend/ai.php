<?php
// === CONFIG & BOILERPLATE ===
// Make sure you’ve set your API key in the environment:
//    export OPENAI_API_KEY="sk-…"
$openaiApiKey = getenv('OPENAI_API_KEY');
if (empty($openaiApiKey)) {
    die('OpenAI API key not configured.');
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

// === 2) CALL OPENAI VIA CURL ===
$endpoint = 'https://api.openai.com/v1/chat/completions';
$headers  = [
    'Authorization: Bearer ' . $openaiApiKey,
    'Content-Type: application/json',
];

$note_content = mysqli_query($conn, "SELECT note FROM notes WHERE id = {$note_id} AND username = '{$username}'");
$note_content = mysqli_fetch_assoc($note_content);
$note_content = $note_content['note'];

$body = json_encode([
    'model'     => 'gpt-4o-mini',
    'messages'  => [
        ['role'=>'system','content'=>'You are a study assistant that creates detailed, structured summaries for notes.'],
        ['role'=>'user','content'=>"{$note_content}"],
    ],
    'max_tokens'  => 1000,
    'temperature' => 0.4,
]);

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST,           true);
curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS,     $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log('OpenAI cURL error: ' . curl_error($ch));
    $note_text = 'AI summary unavailable at the moment.';
} else {
    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        $note_text = trim($data['choices'][0]['message']['content']);
    } else {
        error_log('OpenAI response error: ' . $response);
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