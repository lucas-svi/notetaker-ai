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

    /**
     * Get quiz questions for a specific note
     * 
     * @param int $note_id The ID of the note
     * @return array Array of quiz questions
     * @throws Exception if any database error occurs
     */
    public function getQuiz($note_id)
    {
        // Query the database to get quiz questions
        $sql = "SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_choice, difficulty 
                FROM quiz WHERE note_id = ?
                ORDER BY difficulty ASC";
        
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $note_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            throw new Exception("Failed to retrieve quiz questions");
        }
        
        $questions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $questions[] = [
                'id' => $row['id'],
                'question' => $row['question'],
                'options' => [
                    'A' => $row['choice_a'],
                    'B' => $row['choice_b'],
                    'C' => $row['choice_c'],
                    'D' => $row['choice_d']
                ],
                'correct_choice' => $row['correct_choice'],
                'difficulty' => $row['difficulty']
            ];
        }
        
        mysqli_stmt_close($stmt);
        return $questions;
    }

    /**
     * Submit quiz answers and calculate score and points
     * 
     * @param string $username The username of the user
     * @param int $note_id The ID of the note
     * @param array $answers Array of question_id => answer pairs
     * @return array Results including score, total, points_earned, and detailed results
     * @throws Exception if any database error occurs
     */
    public function submitQuiz($username, $note_id, $answers)
    {
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
        
        foreach ($answers as $question_id => $answer) {
            $total++;
            
            // Verify correct answer and get difficulty
            $sql = "SELECT question, choice_a, choice_b, choice_c, choice_d, correct_choice, difficulty FROM quiz WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
            }
            
            mysqli_stmt_bind_param($stmt, "i", $question_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                mysqli_stmt_close($stmt);
                throw new Exception("Question not found");
            }
            
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
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
        
        // Save quiz results
        $sql = "INSERT INTO quiz_responses (username, note_id, score, total, points_earned) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        
        mysqli_stmt_bind_param($stmt, "siiii", $username, $note_id, $score, $total, $points_earned);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Get user's current quiz points
        $sql = "SELECT quiz_points FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            throw new Exception("User not found");
        }
        
        $user_data = mysqli_fetch_assoc($result);
        $current_points = $user_data['quiz_points'];
        mysqli_stmt_close($stmt);
        
        // Update user's points
        $new_points = $current_points + $points_earned;
        $sql = "UPDATE users SET quiz_points = ? WHERE username = ?";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        
        mysqli_stmt_bind_param($stmt, "is", $new_points, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return [
            'score' => $score,
            'total' => $total,
            'points_earned' => $points_earned,
            'current_points' => $current_points,
            'new_points' => $new_points,
            'percentage' => ($total > 0) ? ($score / $total) * 100 : 0,
            'detailed_results' => $quiz_results
        ];
    }

    /**
     * Get detailed quiz history for a user with summary statistics
     * 
     * @param string $username The username of the user
     * @param int $note_id Optional note ID to filter by
     * @return array Array of quiz history with statistics
     * @throws Exception if any database error occurs
     */
    public function getQuizHistory($username, $note_id = null)
    {
        // Get user's total quiz points
        $sql = "SELECT quiz_points FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->connection, $sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            throw new Exception("User not found");
        }
        
        $user_data = mysqli_fetch_assoc($result);
        $current_points = $user_data['quiz_points'];
        mysqli_stmt_close($stmt);
        
        // Get quiz responses with note previews
        $sql = "SELECT qr.id, qr.note_id, qr.score, qr.total, qr.points_earned, qr.time_taken, 
                      SUBSTRING(n.note, 1, 100) AS note_preview
                FROM quiz_responses qr
                JOIN notes n ON qr.note_id = n.id
                WHERE qr.username = ?";
        
        if ($note_id !== null) {
            $sql .= " AND qr.note_id = ?";
            $stmt = mysqli_prepare($this->connection, $sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
            }
            mysqli_stmt_bind_param($stmt, "si", $username, $note_id);
        } else {
            $sql .= " ORDER BY qr.time_taken DESC";
            $stmt = mysqli_prepare($this->connection, $sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
            }
            mysqli_stmt_bind_param($stmt, "s", $username);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            mysqli_stmt_close($stmt);
            throw new Exception("Failed to retrieve quiz history: " . mysqli_error($this->connection));
        }
        
        // Process quiz history and calculate statistics
        $history_entries = [];
        $total_quizzes = 0;
        $total_points_earned = 0;
        $total_percentage = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $percentage = ($row['score'] / $row['total']) * 100;
            $total_percentage += $percentage;
            $total_points_earned += $row['points_earned'];
            $total_quizzes++;
            
            // Determine score class
            $score_class = '';
            if ($percentage >= 80) {
                $score_class = 'high-score';
            } elseif ($percentage >= 60) {
                $score_class = 'medium-score';
            } else {
                $score_class = 'low-score';
            }
            
            $history_entries[] = [
                'id' => $row['id'],
                'note_id' => $row['note_id'],
                'note_preview' => $row['note_preview'],
                'score' => $row['score'],
                'total' => $row['total'],
                'percentage' => round($percentage),
                'score_class' => $score_class,
                'points_earned' => $row['points_earned'],
                'date_taken' => $row['time_taken']
            ];
        }
        
        mysqli_stmt_close($stmt);
        
        // Calculate summary statistics
        $avg_percentage = $total_quizzes > 0 ? round($total_percentage / $total_quizzes) : 0;
        
        return [
            'current_points' => $current_points,
            'total_quizzes' => $total_quizzes,
            'total_points_earned' => $total_points_earned,
            'avg_percentage' => $avg_percentage,
            'history' => $history_entries
        ];
    }

    /**
     * Get the database connection
     * 
     * @return mysqli Database connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
?>