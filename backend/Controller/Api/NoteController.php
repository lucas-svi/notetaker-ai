<?php
class NoteController extends BaseController
{
    /** 
* "/note/list" Endpoint - Get list of notes 
*/
    public function listAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $_GET;
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $noteModel = new NoteModel();
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
                $arrUsers = $noteModel->getNotes($intLimit);
                $responseData = json_encode($arrUsers);
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        // send output 
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }


    // Endpoint /note/get?id=X - get one note
    public function getAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $_GET;
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $noteModel = new NoteModel();
                $note_id = intval($arrQueryStringParams['id']);
                if ($note_id > 0) {
                    $arrNote = $noteModel->getOneNote($note_id);
                    $responseData = json_encode($arrNote);
                } else {
                    throw new Exception('Invalid note ID');
                }
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        // send output 
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }


    // Endpoint /note/create - create new note
    public function createAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        if (strtoupper($requestMethod) == 'POST') {
            try {
                $username = $_POST['username'];
                $note = $_POST['note'];

                if (strlen($note) < 1) {
                    throw new Exception("Note cannot be empty.");
                }

                $noteModel = new NoteModel();
                $noteModel->createNote($username, $note);

                $responseData = json_encode(['success' => true, 'message' => "Note created successfully"]);
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        // send output 
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 201 CREATED')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    // Endpoint /note/delete?id=X - delete note
    public function deleteAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        if (strtoupper($requestMethod) == "DELETE") {
            // Delete note
            try {
                $note_id = intval($_GET['id']);

                if ($note_id > 0) {
                    $noteModel = new NoteModel();
                    $noteModel->deleteNote($note_id);
                    $responseData = json_encode(array('message' => 'Note deleted successfully'));
                } else {
                    throw new Exception('Invalid note ID');
                }
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
    // send output 
    if (!$strErrorDesc) {
        $this->sendOutput(
            $responseData,
            array('Content-Type: application/json', 'HTTP/1.1 200 OK')
        );
    } else {
        $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
            array('Content-Type: application/json', $strErrorHeader)
        );
    }
    }

    // Endpoint /note/update - update note
    public function updateAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        if (strtoupper($requestMethod) == "PUT") {
            // Update note
            try {
                parse_str(file_get_contents("php://input"), $_PUT);
                $username = $_PUT['username'];
                $note_id = intval($_PUT['id']);
                $note = $_PUT['note'];

                if ($note_id > 0 && strlen($note) > 0) {
                    $noteModel = new NoteModel();
                    $noteModel->updateNote($username, $note_id, $note);
                    $responseData = json_encode(array('message' => 'Note updated successfully'));
                } else {
                    throw new Exception('Invalid input');
                }
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        // send output 
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
}
}
?>