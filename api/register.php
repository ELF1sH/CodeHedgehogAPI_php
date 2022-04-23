<?php
function route($method, $urlList, $requestData) {
    global $Link;
    switch ($method) {
        case 'POST':
            $token = substr(getallheaders()['Authorization'], 7);
            if (empty($token)) {
                $name = $requestData->body->name;
                $surname = $requestData->body->surname;
                $username = $requestData->body->username;
                $password = $requestData->body->password;
                $roleId = 2;
                if (is_null($name) || is_null($surname) || is_null($username) || is_null($password)) {
                    setHTTPStatus("400", ['message' => "Bad Request. Not all data was provided"]);
                }
                else {
                    $resultPassword = validatePassword($password);
                    $resultLogin = validateLogin($username);
                    if ($resultPassword == "OK" && $resultLogin == "OK") {
                        $password = hash("sha1", $password);
                        $registerResult = $Link->query("INSERT INTO user(name, surname, username, password, roleId) VALUES('$name', '$surname', '$username', '$password', '$roleId')");
                        if (!$registerResult) {
                            if ($Link->errno == 1062) {
                                setHTTPStatus("409", ["Error" => "Username '$username' has already been taken"]);
                            }
                            else {
                                setHTTPStatus("400", ["Error" => "Bad Request. " . $Link->error]);
                            }
                        }
                        else {
                            $token = bin2hex(random_bytes(16));
                            $user = $Link->query("SELECT userId FROM user WHERE username='$username' AND password='$password'")->fetch_assoc();
                            $userId = $user['userId'];
                            $tokenResult = $Link->query("INSERT INTO token(value, userId) VALUES('$token', '$userId')");
                            if (!$tokenResult) {
                                setHTTPStatus("400", ["message" => "Bad Request. " . $Link->error]);
                            }
                            else {
                                setHTTPStatus("200", ["token" => $token]);
                            }
                        }
                    }
                    else {
                        setHTTPStatus("400", ["message" => $resultPassword == "OK" ? $resultLogin : $resultPassword]);
                    }
                }
            }
            else {
                setHTTPStatus("403", ["message" => "Permission denied. You already have Authorization token"]);
            }
            break;
        default:
            setHTTPStatus("405", ['message' => "There is no $method method for /register"]);
            break;
    }
}
?>