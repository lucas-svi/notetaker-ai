<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . "/inc/bootstrap.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header('Access-Control-Allow-Credentials: true');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Variable corresponding to how many segments before your controller/action start
$at_start = 3;

$endpoint = $uri[$at_start + 1] ?? null;
$action   = $uri[$at_start + 2] ?? null;

// allow ‘user’, ‘note’ or ‘ai’
if (
    !isset($endpoint)
 || !in_array($endpoint, ['user','note','ai'])
 || !isset($action)
) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

// instantiate the right controller
if ($endpoint === 'note') {
    require PROJECT_ROOT_PATH . "/Controller/Api/NoteController.php";
    $objFeedController = new NoteController();
}
elseif ($endpoint === 'user') {
    require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
    $objFeedController = new UserController();
}
elseif ($endpoint === 'ai') {
    require PROJECT_ROOT_PATH . "Controller/Api/AIController.php";
    $objFeedController = new AIController();
}

// build and invoke method
$strMethodName = $action . 'Action';
if (! method_exists($objFeedController, $strMethodName)) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

try {
    $objFeedController->{$strMethodName}();
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'error' => "An error occurred while processing your request. Please try again later."
    ]);
}
