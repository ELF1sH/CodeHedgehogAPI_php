<?php
    include_once 'helpers/headers.php';
    include_once "helpers/validation.php";
    include_once "helpers/userValidation.php";
    include_once "helpers/topicValidation.php";
    include_once "helpers/tasksValidation.php";

    header("Content-type: application/json");

    global $UploadDir;
    $UploadDir = "uploads";

    global $Link;
    $Link = mysqli_connect("127.0.0.1", "CodeHedgehog", "password", "CodeHedgehog");
    if (!$Link) {
        setHTTPStatus("500", ["message" => "DB connection error. Error number: " . mysqli_connect_errno() . ". Text error: " . mysqli_connect_error()]);
        exit;
    }

    function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    function getData($method) {
        $data = new stdClass();
        $data->parameters = [];
        foreach ($_GET as $key => $value) {
            if ($key != "q") {
                $data->parameters[$key] = $value;  // url request parameters
            }
        }
        if ($method != "GET") {
            $data->body = json_decode(file_get_contents('php://input'));  // raw body of POST, PUT, PATCH... requests
        }
        return $data;    // { "parametres": {**params from URL**}, "body": {**raw body**} }
    }

    $url = isset($_GET['q']) ? $_GET['q'] : ''; 
    $url = rtrim($url, '/');
    $urlList = explode('/', $url);          // ["users", "34"]
    $router = $urlList[0];                  // ["users"]
    $requestData = getData(getMethod());    // { "parametres": {**params from URL**}, "body": {**raw body**} }                                               
    $method = getMethod();                  // GET, POST, PUT, PATCH ...


    // $_GET  --  {"q": "users/34",  "urlParamKey1": "urlParamValue1",  "urlParamKey2": "urlParamValue2"}
    // $_POST  -  {"form-data-key-1": "form-data-value-1",  "form-data-key-2": "form-data-value-2"}
    // file_get_contents('php://input')  --  {"raw-key-1": "raw-value-1",  "raw-key-2": "raw-value-2"}


    if (file_exists(realpath(dirname(__FILE__)).'/api/' . $router . '.php')) {
        include_once 'api/' . $router . '.php';
        route($method, $urlList, $requestData);
    } else {
        echo realpath(dirname(__FILE__)).'/api/' . $router . '.php';
        echo "ERROR 404";
        http_response_code(404);
    }

    mysqli_close($Link);
?>