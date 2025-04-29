<?php
require_once PROJECT_ROOT_PATH . "Controller/Api/BaseController.php";
require_once PROJECT_ROOT_PATH . "/Model/AIModel.php";
require_once __DIR__ . '/../../api_key.php';

class AIController extends BaseController
{
    /**
     * Endpoint /ai/reformat - Reformat a note using the Gemini API.
     */
    public function reformatAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryParams = $_GET;

        if (strtoupper($requestMethod) == 'GET') {
            try {
                session_start();
                if (empty($_SESSION['username'])) {
                    throw new Exception("User not logged in.");
                }
                if (!isset($arrQueryParams['note_id']) || !ctype_digit($arrQueryParams['note_id'])) {
                    throw new Exception("Invalid note ID.");
                }
                $username = $_SESSION['username'];
                $note_id  = intval($arrQueryParams['note_id']);

                // Instantiate the AIModel with the API key and call reformatNote
                $aiModel = new AIModel($geminiApiKey);
                $aiModel->reformatNote($username, $note_id);

                // Redirect back to dashboard.php after successful reformatting
                header("Location: /notetaker-ai/backend/dashboard.php");
                exit();
            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        
        // If there's an error, send a JSON response.
        if ($strErrorDesc) {
            $this->sendOutput(
                json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
}
?>