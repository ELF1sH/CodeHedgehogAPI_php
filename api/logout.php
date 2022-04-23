<?php
function route($method, $urlList, $requestData) {
    global $Link;
    switch ($method) {
        case 'POST':
            $userId = validateToken();
            if (!is_null($userId)) {
                $Link->query("DELETE FROM token WHERE userId='$userId'");
                setHTTPStatus("200", ['message' => "OK"]);
            }
            break;
        default:
            setHTTPStatus("405", ['message' => "There is no $method method for /logout"]);
            break;
    }
}
