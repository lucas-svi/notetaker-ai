<?php
require_once PROJECT_ROOT_PATH . "Controller/Api/BaseController.php";
require_once PROJECT_ROOT_PATH . "/Model/QuizModel.php";
require_once __DIR__ . '/../../api_key.php';

class QuizController extends BaseController
{
    public function generateQuizAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryParams = $_GET;

        if (strtoupper($requestMethod) == 'POST') {
            try {
                // Get note_id from query parameters
                if (!isset($arrQueryParams['note_id']) || !ctype_digit($arrQueryParams['note_id'])) {
                    throw new Exception("Invalid note ID");
                }

                $note_id = intval($arrQueryParams['note_id']);

                // Initialize QuizModel and generate quiz
                $quizModel = new QuizModel();
                $result = $quizModel->generateQuiz($note_id);

                // Send success response
                $this->sendOutput(
                    json_encode([
                        'success' => true,
                        'message' => 'Quiz generated successfully',
                        'note_id' => $note_id
                    ]),
                    ['Content-Type: application/json', 'HTTP/1.1 200 OK']
                );

            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        
        // If there's an error, send a JSON response
        if ($strErrorDesc) {
            $this->sendOutput(
                json_encode(['error' => $strErrorDesc]),
                ['Content-Type: application/json', $strErrorHeader]
            );
        }
    }

    public function getQuizAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryParams = $_GET;

        if (strtoupper($requestMethod) == 'GET') {
            try {
                // Get note_id from query parameters
                if (!isset($arrQueryParams['note_id']) || !ctype_digit($arrQueryParams['note_id'])) {
                    throw new Exception("Invalid note ID");
                }

                $note_id = intval($arrQueryParams['note_id']);

                // Initialize QuizModel and get quiz questions
                $quizModel = new QuizModel();
                $questions = $quizModel->getQuiz($note_id);
                
                // Send success response
                $this->sendOutput(
                    json_encode([
                        'success' => true,
                        'note_id' => $note_id,
                        'questions' => $questions
                    ]),
                    ['Content-Type: application/json', 'HTTP/1.1 200 OK']
                );

            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        
        // If there's an error, send a JSON response
        if ($strErrorDesc) {
            $this->sendOutput(
                json_encode(['error' => $strErrorDesc]),
                ['Content-Type: application/json', $strErrorHeader]
            );
        }
    }

    public function submitQuizAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        
        if (strtoupper($requestMethod) == 'POST') {
            try {
                // Get data from request body
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Check if username is provided
                if (!isset($data['username']) || empty($data['username'])) {
                    throw new Exception("Username is required");
                }
                
                if (!isset($data['note_id']) || !is_numeric($data['note_id'])) {
                    throw new Exception("Invalid note ID");
                }
                
                if (!isset($data['answers']) || !is_array($data['answers']) || empty($data['answers'])) {
                    throw new Exception("No answers provided");
                }
                
                $username = $data['username'];
                $note_id = intval($data['note_id']);
                $answers = $data['answers'];
                
                // Initialize QuizModel and submit quiz
                $quizModel = new QuizModel();
                $results = $quizModel->submitQuiz($username, $note_id, $answers);
                
                // Send success response
                $this->sendOutput(
                    json_encode([
                        'success' => true,
                        'results' => $results
                    ]),
                    ['Content-Type: application/json', 'HTTP/1.1 200 OK']
                );
                
            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        
        // If there's an error, send a JSON response
        if ($strErrorDesc) {
            $this->sendOutput(
                json_encode(['error' => $strErrorDesc]),
                ['Content-Type: application/json', $strErrorHeader]
            );
        }
    }
    
    public function getHistoryAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryParams = $_GET;
        
        if (strtoupper($requestMethod) == 'GET') {
            try {
                // Check if username is provided
                if (!isset($arrQueryParams['username']) || empty($arrQueryParams['username'])) {
                    throw new Exception("Username is required");
                }
                
                $username = $arrQueryParams['username'];
                
                // Get optional note_id from query parameters
                $note_id = null;
                if (isset($arrQueryParams['note_id']) && ctype_digit($arrQueryParams['note_id'])) {
                    $note_id = intval($arrQueryParams['note_id']);
                }
                
                // Initialize QuizModel and get enhanced quiz history
                $quizModel = new QuizModel();
                $historyData = $quizModel->getQuizHistory($username, $note_id);
                
                // Send success response with comprehensive data
                $this->sendOutput(
                    json_encode([
                        'success' => true,
                        'stats' => [
                            'current_points' => $historyData['current_points'],
                            'total_quizzes' => $historyData['total_quizzes'],
                            'total_points_earned' => $historyData['total_points_earned'],
                            'avg_percentage' => $historyData['avg_percentage']
                        ],
                        'history' => $historyData['history']
                    ]),
                    ['Content-Type: application/json', 'HTTP/1.1 200 OK']
                );
                
            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        
        // If there's an error, send a JSON response
        if ($strErrorDesc) {
            $this->sendOutput(
                json_encode(['error' => $strErrorDesc]),
                ['Content-Type: application/json', $strErrorHeader]
            );
        }
    }
}
?>