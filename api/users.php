<?php
    function route($method, $urlList, $requestData) {
        global $Link;
        $userId = validateToken();
        if ($userId) {
            switch ($method) {
                case "GET":
                    $userIdFromURL = $urlList[1];
                    if (!$userIdFromURL) {
                        if (isUserAdmin($userId)) {
                            $usersResult = $Link->query("SELECT * FROM user");
                            $users = [];
                            if ($usersResult->num_rows > 0) {
                                while($row = $usersResult->fetch_assoc()) {
                                    array_push($users, ['userId' => $row['userId'], 'username' => $row['username'], 'roleId' => $row['roleId']]);
                                }
                            }
                            setHTTPStatus("200", $users);
                        }
                        else {
                            setHTTPStatus("403", ['message' => "Permission denied. Only admin has access to this method"]);
                        }
                    }
                    else if ($urlList[1] && !is_numeric($userIdFromURL)) {
                        setHTTPStatus("400", ['message' => "userId must be a number"]);
                    }
                    else {
                        if (isUserAdmin($userId) || $userId == $userIdFromURL) {
                            $userResult = $Link->query("SELECT * FROM user WHERE userId='$userIdFromURL'")->fetch_assoc();
                            if (is_null($userResult)) {
                                setHTTPStatus("409", ['message' => "There is no user with such ID"]);
                            }
                            else {
                                unset($userResult['password']);
                                setHTTPStatus("200", $userResult);
                            }
                        }
                        else {
                            setHTTPStatus("403", ['message' => "Permission denied. Only admin and data owner have access to this method"]);
                        }
                    }
                    break;
                case "PATCH":
                    $userIdFromURL = $urlList[1];
                    if (!$userIdFromURL) {
                        setHTTPStatus("400", ['message' => "No userId has been provided in URL"]);
                    }
                    else if ($urlList[1] && !is_numeric($userIdFromURL)) {
                        setHTTPStatus("400", ['message' => "userId must be a number"]);
                    }
                    else {
                        if (isUserAdmin($userId) || $userId == $userIdFromURL) {
                            if (doesUserExist($userIdFromURL)) {
                                $needComma = false;
                                $username = $requestData->body->username;
                                if ($username) {
                                    $mes = validateLogin($username);
                                    if ($mes != "OK") {
                                        setHTTPStatus("400", ['message' => $mes]);
                                        exit;
                                    }
                                }
                                $password = $requestData->body->password;
                                if ($password) {
                                    $mes = validatePassword($password);
                                    if ($mes != "OK") {
                                        setHTTPStatus("400", ['message' => $mes]);
                                        exit;
                                    }
                                }
                                $password = hash("sha1", $requestData->body->password);
                                $name = $requestData->body->name;
                                $surname = $requestData->body->surname;
                                $SQL_query = "UPDATE user SET ";
                                if ($username) {
                                    $SQL_query = $SQL_query . "username = '$username'";
                                    $needComma = true;
                                }
                                if ($password) {
                                    if ($needComma) $SQL_query = $SQL_query . ", ";
                                    else $needComma = true;
                                    $SQL_query = $SQL_query . "password = '$password'";
                                }
                                if ($name) {
                                    if ($needComma) $SQL_query = $SQL_query . ", ";
                                    else $needComma = true;
                                    $SQL_query = $SQL_query . "name = '$name'";
                                }
                                if ($surname) {
                                    if ($needComma) $SQL_query = $SQL_query . ", ";
                                    $SQL_query = $SQL_query . "surname = '$surname";
                                }
                                $SQL_query = $SQL_query . " WHERE userId='$userIdFromURL'";
                                $res = $Link->query($SQL_query);
                                if ($res) {
                                    setHTTPStatus("200", ['message' => "OK"]);
                                }
                                else {
                                    setHTTPStatus("500", ['message' => $Link->error]);
                                }
                            }
                            else {
                                setHTTPStatus("409", ['message' => 'There is no user with such ID']);
                            }
                        }
                        else {
                            setHTTPStatus("403", ['message' => "Permission denied. Only admin and data owner have access to this method"]);
                        }
                    }
                    break;
                case "DELETE":
                    $userIdFromURL = $urlList[1];
                    if (!$userIdFromURL) {
                        setHTTPStatus("400", ['message' => "No userId has been provided in URL"]);
                    }
                    else if ($urlList[1] && !is_numeric($userIdFromURL)) {
                        setHTTPStatus("400", ['message' => "userId must be a number"]);
                    }
                    else {
                        if (isUserAdmin($userId)) {
                            if (doesUserExist($userIdFromURL)) {
                                $res = $Link->query("DELETE FROM user WHERE userId='$userIdFromURL'");
                                if ($res) {
                                    setHTTPStatus("200", ['message' => "OK"]);
                                }
                                else {
                                    setHTTPStatus("500", ['message' => $Link->error]);
                                }
                            }
                            else {
                                setHTTPStatus("409", ['message' => 'There is no user with such ID']);
                            }
                        }
                        else {
                            setHTTPStatus("403", ['message' => "Permission denied. Only admin has access to this method"]);
                        }
                    }
                    break;
                case "POST":
                    $userIdFromURL = $urlList[1];
                    $roleIdFromURL = $urlList[2];
                    if (!$userIdFromURL || !$roleIdFromURL) {
                        setHTTPStatus("400", ['message' => 'Not all data were provided in URL']);
                        exit;
                    }
                    if (!is_numeric($userIdFromURL) || !is_numeric($roleIdFromURL)) {
                        setHTTPStatus("400", ['message' => 'IDs must be numbers']);
                        exit;
                    }
                    if (!isUserAdmin($userId)) {
                        setHTTPStatus("403", ['message' => "Permission denied. Only admin has access to this method"]);
                        exit;
                    }
                    if (!doesUserExist($userIdFromURL)) {
                        setHTTPStatus("409", ['message' => 'There is no user with such ID']);
                        exit;
                    }
                    if (!doesRoleExist($roleIdFromURL)) {
                        setHTTPStatus("409", ['message' => 'There is no role with such ID']);
                        exit;
                    }
                    $res = $Link->query("UPDATE user SET roleId='$roleIdFromURL' WHERE userId='$userIdFromURL'");
                    if ($res) {
                        setHTTPStatus("200", ['message' => "OK"]);
                    }
                    else {
                        setHTTPStatus("500", ['message' => $Link->error]);
                    }
                    break;
                default: 
                    setHTTPStatus("405", ['message' => "There is no $method method for /users"]);
                    break;
            }
        }
    }
?>