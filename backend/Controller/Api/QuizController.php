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
}
?>