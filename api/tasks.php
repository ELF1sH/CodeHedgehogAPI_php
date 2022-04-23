<?php
    function route($method, $urlList, $requestData) {
        global $Link, $UploadDir;
        switch ($method) {
            case "GET":
                $taskId = $urlList[1];
                if (!$taskId) {
                    //    /tasks
                    $tasksResult = $Link->query("SELECT * FROM task");
                    if ($tasksResult) {
                        $tasks = [];
                        if ($tasksResult->num_rows > 0) {
                            while($row = $tasksResult->fetch_assoc()) {
                                $isMatch = true;
                                if (!is_null($requestData->parameters['topic']) && $requestData->parameters['topic'] != $row['topicId']) {
                                    $isMatch = false;
                                }
                                $name = $requestData->parameters['name'];
                                if (!is_null($name) && !preg_match_all('/' . $name .'/', $row['name'])) {
                                    $isMatch = false;
                                }
                                if ($isMatch) {
                                    array_push($tasks, ['id' => $row['id'], 'name' => $row['name'], 'topicId' => $row['topicId']]);
                                }
                            }
                        }
                        setHTTPStatus("200", $tasks);
                    }
                    else setHTTPStatus("500", ['message' => $Link->error]);
                }
                else {
                    $userId = validateToken();
                    if ($userId) {
                        switch ($urlList[2]) {
                            case null:
                                //    /tasks/{taskId}
                                $taskResult = $Link->query("SELECT * FROM task WHERE id='$taskId'");
                                if ($taskResult) {
                                    $task = $taskResult->fetch_assoc();
                                    if ($task) {
                                        unset($task['input'], $task['output']);
                                        if ($task['isDraft']) $task['isDraft'] == 0 ? $task['isDraft'] = false : true;
                                        setHTTPStatus("200", $task);
                                    }
                                    else {
                                        setHTTPStatus("200", ['message' => "There is no task with id $taskId"]);
                                    }
                                }
                                else setHTTPStatus("500", ['message' => $Link->error]);
                                break;
                            case "input":
                            case "output":
                                //    /tasks/{taskId}/input    /tasks/{taskId}/output
                                $taskResult = $Link->query("SELECT $urlList[2] FROM task WHERE id='$taskId'");
                                if ($taskResult) {
                                    $task = $taskResult->fetch_assoc();
                                    if ($task) setHTTPStatus("200", $task);
                                    else setHTTPStatus("200", ['message' => "There is no task with id $taskId"]);
                                }
                                else setHTTPStatus("500", ['message' => $Link->error]);
                                break;
                            default:
                                setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                                break;
                        }
                    }
                }
                break;
            
            case "POST":
                $userId = validateToken();
                if ($userId) {
                    if ($urlList[2] == 'solution') {
                        $taskId = $urlList[1];
                        if (!doesTaskExist($taskId)) {
                            setHTTPStatus("409", ["message" => "Task with id $taskId does not exist"]);
                            exit;
                        }
                        $sourceCode = $requestData->body->sourceCode;
                        $programmingLanguage = $requestData->body->programmingLanguage;
                        if (!$sourceCode || !$programmingLanguage) {
                            setHTTPStatus("400", ['message' => "Not all data were provided"]);
                            exit;
                        }
                        $res = $Link->query("INSERT INTO solution (sourceCode, programmingLanguage, authorId, taskId) VALUES ('$sourceCode', '$programmingLanguage', '$userId', '$taskId')");
                        if ($res) {
                            $solution = $Link->query("SELECT * FROM solution WHERE id = (SELECT MAX(id) FROM solution)")->fetch_assoc();
                            setHTTPStatus("200", $solution);
                        }
                        else setHTTPStatus("500", ['message' => $Link->error]);
                    }
                    else if (isUserAdmin($userId)) {
                        $taskId = $urlList[1];
                        if (!$taskId) {
                            //    /tasks
                            $name = $requestData->body->name;
                            $topicId = $requestData->body->topicId;
                            $description = addcslashes($requestData->body->description, "'\"");
                            $price = $requestData->body->price;
                            if (is_null($name) || is_null($topicId) || is_null($description) || is_null($price)) {
                                setHTTPStatus("400", ['message' => "Not all data were provided"]);
                            }
                            else {
                                $res = $Link->query("INSERT INTO task (name, topicId, description, price) VALUES ('$name', '$topicId', '$description', '$price')");
                                if ($res) {
                                    $task = $Link->query("SELECT * FROM task WHERE id = (SELECT MAX(id) FROM task)")->fetch_assoc();
                                    unset($task['input'], $task['output']);
                                    setHTTPStatus("200", $task);
                                }
                                else setHTTPStatus("500", ['message' => $Link->error]);
                            }
                        }
                        else {
                            if ($urlList[2] == 'input' || $urlList[2] == 'output') {
                                //    /tasks/{taskId}/input    /tasks/{taskId}/output
                                if (!doesTaskExist($taskId)) {
                                    setHTTPStatus("409", ["message" => "Task with id $taskId does not exist"]);
                                    exit;
                                }
                                $file = $_FILES['input'];
                                if (!$file) {
                                    setHTTPStatus("400", ['message' => "File has not been provided"]);
                                    exit;
                                }
                                if ($file['type'] != "text/plain") {
                                    setHTTPStatus("403", ['message' => "Wrong file type"]);
                                    exit;
                                }
                                $pathToUpload = $UploadDir . "/upload_" . time() . "_" . basename($file['name']);
                                move_uploaded_file($file['tmp_name'], $pathToUpload);
                                $uploadRes = $Link->query("UPDATE task SET $urlList[2]='$pathToUpload' WHERE id='$taskId'");
                                if ($uploadRes) {
                                    $task = $Link->query("SELECT * FROM task WHERE id='$taskId'")->fetch_assoc();
                                    unset($task['input'], $task['output']);
                                    setHTTPStatus("200", ['message' => $task]);
                                }
                                else setHTTPStatus("500", ['message' => $Link->error]);
                            }
                            else setHTTPStatus("400", ["message" => "Bad request. Check URL"]);
                        }
                    }
                    else {
                        setHTTPStatus("403", ['message' => "Only admin has access to this method"]);
                    }
                }
                break;

            case "PATCH":
                $userId = validateToken();
                if ($userId) {
                    if (isUserAdmin($userId)) {
                        $taskId = $urlList[1];
                        if (!$taskId) setHTTPStatus("400", ['message' => 'Bad request. TaskId must be provided']);
                        else {
                            if (!doesTaskExist($taskId)) {
                                setHTTPStatus("409", ['message' => "Task with id $taskId does not exist"]);
                                exit;
                            }
                            $SQL_query = "UPDATE task SET ";
                            $needComma = false;
                            $name = $requestData->body->name;
                            if ($name) {
                                $SQL_query = $SQL_query . "name='$name'";
                                $needComma = true;
                            }
                            $topicId = $requestData->body->topicId;
                            if ($topicId) {
                                if ($needComma) $SQL_query = $SQL_query . ", ";
                                else $needComma = true;
                                $SQL_query = $SQL_query . "topicId=$topicId";
                            }
                            $description = $requestData->body->description;
                            if ($description) {
                                if ($needComma) $SQL_query = $SQL_query . ", ";
                                else $needComma = true;
                                $SQL_query = $SQL_query . "description='$description'";
                            }
                            $price = $requestData->body->price;
                            if ($price) {
                                if ($needComma) $SQL_query = $SQL_query . ", ";
                                else $needComma = true;
                                $SQL_query = $SQL_query . "price=$price";
                            }
                            $SQL_query = $SQL_query . " WHERE id='$taskId'";
                            $updateRes = $Link->query($SQL_query);
                            if ($updateRes) {
                                $task = $Link->query("SELECT * FROM task WHERE id='$taskId'")->fetch_assoc();
                                unset($task['input'], $task['output']);
                                setHTTPStatus("200", ['message' => $task]);
                            }
                            else setHTTPStatus("500", ['message' => $Link->error]);
                        }
                    }
                    else {
                        setHTTPStatus("403", ['message' => "Only admin has access to this method"]);
                    }
                }
                break;

            case "DELETE":
                $userId = validateToken();
                if ($userId) {
                    if (isUserAdmin($userId)) {
                        $taskId = $urlList[1];
                        if (!$taskId) setHTTPStatus("400", ['message' => 'Bad request. TaskId must be provided']);
                        else if (!doesTaskExist($taskId)) {
                            setHTTPStatus("409", ['message' => "Task with id $taskId does not exist"]);
                        }
                        else if (!$urlList[2]) {
                            //    /tasks/{taskId}
                            $deleteRes = $Link->query("DELETE FROM task WHERE id='$taskId'");
                            if ($deleteRes) setHTTPStatus("200", ['message' => "OK"]);
                            else setHTTPStatus("500", ['message' => $Link->error]);
                        }
                        else if ($urlList[2] == "input" || $urlList[2] == "output") {
                            //    /tasks/{taskId}/input    /tasks/{taskId}/output
                            $deleteRes = $Link->query("UPDATE task SET $urlList[2]=NULL WHERE id='$taskId'");
                            if ($deleteRes) setHTTPStatus("200", ['message' => "OK"]);
                            else setHTTPStatus("500", ['message' => $Link->error]);
                        }
                        else {
                            setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                        }
                    }
                    else {
                        setHTTPStatus("403", ['message' => "Only admin has access to this method"]);
                    }
                }
                break;

            default: 
                setHTTPStatus("405", ['message' => "There is no $method method for /tasks"]);
                break;
        }
    }
