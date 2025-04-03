<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . "/inc/bootstrap.php";
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// Variable corresponding to how many arguments at start before ones to be parsed
// For us, this is 3 (localhost/notetaker-ai/backend/index.php/...)
$at_start = 3;

$endpoint = $uri[$at_start+1];

if ((!isset($endpoint) || ($endpoint != 'user' && $endpoint != 'note') || !isset($uri[$at_start+2]))) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

// Set controller based on endpoint
if ($endpoint == 'note') {
    require PROJECT_ROOT_PATH . "/Controller/Api/NoteController.php";
    $objFeedController = new NoteController();
} else {
    require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
    $objFeedController = new UserController();
}

// Set method name based on URI
$strMethodName = $uri[$at_start+2] . 'Action';

// Check if method exists
if (!method_exists($objFeedController, $strMethodName)) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

try {
    $objFeedController->{$strMethodName}();
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => $e->getMessage()]);
}
?>