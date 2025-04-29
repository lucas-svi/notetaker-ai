<?php
include_once __DIR__ . '/../api_key.php'; // Now loads from "/notetaker-ai/backend/api_key.php"

class AIModel extends Database
{
    private $apiKey;

    public function __construct(string $apiKey = null)
    {
        parent::__construct();
        // Use the provided apiKey or fall back to $geminiApiKey loaded from api_key.php
        if ($apiKey !== null) {
            $this->apiKey = $apiKey;
        } else {
            // pull in the global defined in api_key.php
            global $geminiApiKey;
            if (empty($geminiApiKey) || !is_string($geminiApiKey)) {
                throw new \Exception("Gemini API key not configured");
            }
            $this->apiKey = $geminiApiKey;
        }
    }

    /**
     * Reformat a note by retrieving its contents, sending it to the Gemini API,
     * and updating the note in the database with the AI-generated summary.
     *
     * @param string $username The username of the note owner.
     * @param int $note_id The ID of the note to reformat.
     * @return bool Returns true on success.
     * @throws Exception if any database or API error occurs.
     */
    public function reformatNote(string $username, int $note_id): bool
    {
        // 1) Retrieve the note content using a prepared statement.
        $sql = "SELECT note FROM notes WHERE id=? AND username=?";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare retrieval statement: " . mysqli_error($this->connection));
        }
        mysqli_stmt_bind_param($stmt, "is", $note_id, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            throw new Exception("Note not found.");
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        $note_content = $row['note'];

        // 2) Prepare the API request payload as in ai.php.
        $endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';
        $url = $endpoint . '?key=' . $this->apiKey;
        $headers = ['Content-Type: application/json'];
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

        // 3) Call the Gemini API via cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Gemini cURL error: ' . curl_error($ch));
            curl_close($ch);
            $summary = 'AI summary UNAVAILABLE at the moment.';
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $summary = trim($data['candidates'][0]['content']['parts'][0]['text']);
            } elseif (isset($data['candidates'][0]['output'])) {
                $summary = trim($data['candidates'][0]['output']);
            } else {
                error_log('Gemini response error: ' . $response);
                $summary = 'AI sum unavailable at the moment.';
            }
        }

        // 4) Update the note with the AI-generated summary using a prepared UPDATE statement.
        $update_sql = "UPDATE notes SET note=? WHERE id=? AND username=?";
        $update_stmt = mysqli_prepare($this->connection, $update_sql);
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update statement: " . mysqli_error($this->connection));
        }
        mysqli_stmt_bind_param($update_stmt, "sis", $summary, $note_id, $username);
        if (!mysqli_stmt_execute($update_stmt)) {
            mysqli_stmt_close($update_stmt);
            throw new Exception("Failed to update note: " . mysqli_stmt_error($update_stmt));
        }
        mysqli_stmt_close($update_stmt);

        return true;
    }
}
?>