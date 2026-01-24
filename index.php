<?php
ini_set("display_errors", 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once("tools/DB.php");

require_once(__DIR__."/MainController.php");
require_once(__DIR__."/../vendor/autoload.php");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

$requestMethod = $_SERVER["REQUEST_METHOD"];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$path = isset($uri[1]) ? $uri[1] : null;
$identifier = isset($uri[2]) ? $uri[2] : null;

$allowed_origins = [
    'https://levendelaiscinema.fr',
    'https://gestion.levendelaiscinema.fr',
    'http://gestion.cinema.local'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$data = json_decode(file_get_contents(__DIR__."/data.json"), true);

if($path && array_key_exists($path, $data))
{
    $currentApi = $data[$path];
    if(array_key_exists("useCustomClass", $currentApi) && $currentApi["useCustomClass"] && array_key_exists("customClassFile", $currentApi) && file_exists(__DIR__."/CustomClasses/".$currentApi["customClassFile"]))
    {
        ob_start();
        require_once(__dir__."/MainController.php");
        require_once(__DIR__."/CustomClasses/".$currentApi["customClassFile"]);

        $class = new CustomController($requestMethod, $identifier, $currentApi);
    }
    else{
        $class = new Controller($requestMethod, $identifier, $currentApi);
    }
}
?>
