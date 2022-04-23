<?php
    function route($method, $urlList, $requestData) {
        global $Link;
        switch ($method) {
            case 'POST':
                $token = substr(getallheaders()['Authorization'], 7);
                if (empty($token)) {
                    $username = $requestData->body->username;
                    $password = $requestData->body->password;
                    if (is_null($username) || is_null($password)) {
                        setHTTPStatus("400", ['message' => "Bad Request. Not all data was provided"]);
                    }
                    else {
                        $resultPassword = validatePassword($password);
                        $resultLogin = validateLogin($username);
                        if ($resultPassword == "OK" && $resultLogin == "OK") {
                            $password = hash("sha1", $password);
                            $loginResult = $Link->query("SELECT userId FROM user WHERE username='$username' AND password='$password'")->fetch_assoc();
                            if (!is_null($loginResult)) {
                                $userId = $loginResult['userId'];
                                $token = bin2hex(random_bytes(16));
                                $Link->query("INSERT INTO token(value, userId) VALUES('$token', '$userId')");
                                setHTTPStatus("200", ['token' => $token]);
                            }
                            else {
                                setHTTPStatus("400", ['message' => "Bad request. Input data are incorrect"]);
                            }
                        }
                        else {
                            setHTTPStatus("403", ["message" => $resultPassword == "OK" ? $resultLogin : $resultPassword]);
                        }
                    }
                }
                else {
                    setHTTPStatus("403", ['message' => "Permission denied. You already have Authorization token"]);
                }
                break;
            default: 
                setHTTPStatus("405", ['message' => "There is no $method method for /login"]);
                break;
        }
    }
?>