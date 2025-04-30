<?php
include_once __DIR__ . '/../api_key.php'; // Now loads from "/notetaker-ai/backend/api_key.php"

class QuizModel extends Database
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

    public function generateQuiz($note_id)
    {
        // 1) Fetch the note content
        $sql = "SELECT note FROM notes WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        mysqli_stmt_bind_param($stmt, "i", $note_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            throw new Exception("Note not found");
        }
        
        $row = mysqli_fetch_assoc($result);
        $note_content = $row['note'];
        mysqli_stmt_close($stmt);

        // 2) Prepare the Gemini API request
        $endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';
        $headers = ['Content-Type: application/json'];
        
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

        // 3) Call the Gemini API
        $url = $endpoint . '?key=' . $this->apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('Gemini cURL error: ' . curl_error($ch));
            curl_close($ch);
            throw new Exception("Failed to generate quiz questions. Please try again later.");
        }

        curl_close($ch);

        // 4) Process the response
        $data = json_decode($response, true);
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('Gemini response error: ' . $response);
            throw new Exception("Failed to generate valid quiz questions. Please try again later.");
        }

        $questions_json = $data['candidates'][0]['content']['parts'][0]['text'];
        $questions_json = preg_replace('/```json\s*([\s\S]*?)\s*```/', '$1', $questions_json);
        $questions_json = preg_replace('/```\s*([\s\S]*?)\s*```/', '$1', $questions_json);
        $questions = json_decode($questions_json, true);

        if (!is_array($questions)) {
            error_log('Invalid questions format: ' . $questions_json);
            throw new Exception("Failed to parse quiz questions. Please try again later.");
        }

        // 5) Delete existing questions for this note
        $delete_sql = "DELETE FROM quiz WHERE note_id = ?";
        $delete_stmt = mysqli_prepare($this->connection, $delete_sql);
        if (!$delete_stmt) {
            throw new Exception("Failed to prepare delete statement: " . mysqli_error($this->connection));
        }
        mysqli_stmt_bind_param($delete_stmt, "i", $note_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // 6) Insert new questions
        $insert_sql = "INSERT INTO quiz (note_id, question, choice_a, choice_b, choice_c, choice_d, correct_choice, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($this->connection, $insert_sql);
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare insert statement: " . mysqli_error($this->connection));
        }

        foreach ($questions as $q) {
            $question = $q['question'];
            $optionA = $q['options']['A'];
            $optionB = $q['options']['B'];
            $optionC = $q['options']['C'];
            $optionD = $q['options']['D'];
            $correct = $q['correct'];
            $difficulty = isset($q['difficulty']) ? $q['difficulty'] : 'medium';
            
            mysqli_stmt_bind_param($insert_stmt, "isssssss", $note_id, $question, $optionA, $optionB, $optionC, $optionD, $correct, $difficulty);
            mysqli_stmt_execute($insert_stmt);
        }

        mysqli_stmt_close($insert_stmt);
        return true;
    }
}
?>