<?php
class UserController extends BaseController
{
    /** 
* "/user/list" Endpoint - Get list of users 
*/
    protected $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * Show the leaderboard page.
     */
    public function leaderboardAction() {
        // fetch top 20 (or pass a GET param for dynamic limits)
        $limit = $_GET['limit'] ?? 20;
        $leaderboard = $this->userModel->getLeaderboard((int)$limit);
    
        // make $leaderboard available to the view
        include __DIR__ . '/../../leaderboard.php';
    }

    public function listAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $_GET;
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $userModel = new UserModel();
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
                $arrUsers = $userModel->getUsers($intLimit);
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

    // Endpoint /user/create - create new user
    public function createAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        if (strtoupper($requestMethod) == 'POST') {
            try {
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                if ($password !== $confirm_password) {
                    throw new Exception("Passwords do not match.");
                }

                if (strlen($password) < 10) {
                    throw new Exception("Password must be at least 10 characters long.");
                }

                $userModel = new UserModel();
                $userModel->createUser($username, $email, $password);
                
                $responseData = json_encode([
                    'success' => true, 
                    'message' => "Signup successful",
                    'user' => [
                        'username' => $username,
                        'email' => $email
                    ]
                ]);

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

    /**
     * "/user/login" Endpoint - Authenticate user
     */
    public function loginAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        if (strtoupper($requestMethod) == 'POST') {
            try {
                // Get login credentials from POST data
                $loginIdentifier = $_POST['username']; // Can be username or email
                $password = $_POST['password'];

                // Validate input
                if (empty($loginIdentifier) || empty($password)) {
                    throw new Exception("Login credentials are required.");
                }

                $userModel = new UserModel();
                $user = $userModel->authenticateUser($loginIdentifier, $password);

                if ($user) {
                    // Authentication successful
                    $responseData = json_encode([
                        'success' => true, 
                        'message' => "Login successful",
                        'user' => [
                            'username' => $user['username'],
                            'email' => $user['email']
                        ]
                    ]);
                } else {
                    // Authentication failed
                    throw new Exception("Invalid username/email or password.");
                }
            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 401 Unauthorized';
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