<?php
    function route($method, $urlList, $requestData) {
        global $Link;
        switch ($method) {
            case "GET":
                $topicId = $urlList[1];
                if (!$topicId) {
                    //    /topics
                    $topicResult = $Link->query("SELECT * FROM topic");
                    if ($topicResult) {
                        $topics = [];
                        if ($topicResult->num_rows > 0) {
                            while($row = $topicResult->fetch_assoc()) {
                                $isMatch = true;
                                if (!is_null($requestData->parameters['parent']) && $requestData->parameters['parent'] != $row['parentId']) {
                                    $isMatch = false;
                                }
                                $name = $requestData->parameters['name'];
                                if (!is_null($name) && !preg_match_all('/' . $name .'/', $row['name'])) {
                                    $isMatch = false;
                                }
                                if ($isMatch) {
                                    array_push($topics, ['id' => $row['id'], 'name' => $row['name'], 'parentId' => $row['parentId']]);
                                }
                            }
                        }
                        setHTTPStatus("200", $topics);
                    }
                    else setHTTPStatus("500", ['message' => $Link->error]);
                }
                else {
                    $childs = $urlList[2];
                    if (!$childs) {
                        //    /topics/{topicId}
                        $topicResult = $Link->query("SELECT * FROM topic WHERE id='$topicId'");
                        $topic = $topicResult->fetch_assoc();
                        if ($topicResult) {
                            if ($topic) {
                                $topic['childs'] = getTopicChilds($topicId);
                                setHTTPStatus("200", $topic);
                            }
                            else setHTTPStatus("400", ['message' => "There is no topic with ID $topicId"]);
                        }
                        else setHTTPStatus("500", ['message' => $Link->error]);
                    }
                    else if ($childs == "childs") {
                        //    /topics/{topicId}/childs
                        $topicResult = $Link->query("SELECT * FROM topic WHERE id='$topicId'");
                        $topic = $topicResult->fetch_assoc();
                        if ($topicResult) {
                            if ($topic) {
                                $childs = getTopicChilds($topicId);
                                setHTTPStatus("200", $childs);
                            }
                            else setHTTPStatus("400", ['message' => "There is no topic with such ID"]);
                        }
                        else setHTTPStatus("500", ['message' => $Link->error]);
                    }
                    else {
                        setHTTPStatus("400", ['message' => "Bad request"]);
                    }
                }
                break;
            case "POST":
                $userId = validateToken();
                if ($userId) {
                    if (isUserAdmin($userId)) {
                        $topicId = $urlList[1];
                        if (!$topicId) {
                            //    //topics
                            $name = $requestData->body->name;
                            $parentId = $requestData->body->parendId;
                            if (is_null($name) || is_null($parentId)) {
                                setHTTPStatus("400", ['message' => 'Not all data were provided']);
                            }
                            else if (!doesTopicExist($parentId)) {
                                setHTTPStatus("409", ['message' => "Topic with this ID does not exist"]);
                            }
                            else {
                                $res = $Link->query("INSERT INTO topic (name, parentId) VALUES ('$name', '$parentId')");
                                if ($res) {
                                    setHTTPStatus("200", ['message' => "OK"]);
                                }   
                                else {
                                    setHTTPStatus("500", ['message' => $Link->error]);
                                }
                            }
                        }
                        else {
                            if (!doesTopicExist($topicId)) {
                                setHTTPStatus("409", ['message' => "Topic with id $topicId does not exist"]);
                            }
                            else if (!is_numeric($topicId) || $urlList[2] != "childs") {
                                setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                            }
                            else if (!is_array($requestData->body)) {
                                setHTTPStatus("400", ['message' => "Body must be an array"]);
                            }
                            else {
                                foreach ($requestData->body as &$childId) {
                                    if (!doesTopicExist($childId)) {
                                        setHTTPStatus("409", ['message' => "Topic with ID $childId does not exist"]);
                                        exit;
                                    }
                                    if ($topicId == $childId) {
                                        setHTTPStatus("409", ['message' => "Topic cannot be child of itself."]);
                                        exit;
                                    }
                                    $res = $Link->query("UPDATE topic SET parentId='$topicId' WHERE id='$childId'");
                                    if (!$res) {
                                        setHTTPStatus("500", ['message' => $Link->error]);
                                        exit;
                                    }
                                }
                                setHTTPStatus("200", ['message' => "OK"]);
                            }
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
                        $topicId = $urlList[1];
                        if (!$topicId) {
                            setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                        }
                        else {
                            if (!doesTopicExist($topicId)) {
                                setHTTPStatus("409", ['message' => "Topic with id $topicId does not exist"]);
                                exit;
                            }
                            $SQLRequest = "UPDATE topic SET ";
                            $needComma = false;
                            $name = $requestData->body->name;
                            if ($name) {
                                $SQLRequest = $SQLRequest . "name='$name'";
                                $needComma = true;
                            }
                            $parentId = $requestData->body->parendId;
                            if ($parentId) {
                                if (!is_numeric($parentId)) {
                                    setHTTPStatus("400", ['message' => 'parendId must be a number']);
                                    exit;
                                }
                                if (!doesTopicExist($parentId)) {
                                    setHTTPStatus("409", ['message' => "Topic with id $parentId does not exist"]);
                                    exit;
                                }
                                if ($parentId == $topicId) {
                                    setHTTPStatus("409", ['message' => "Topic cannot be child of itself"]);
                                    exit;
                                }
                                if ($needComma) $SQLRequest = $SQLRequest . ", ";
                                $SQLRequest = $SQLRequest . "parentId='$parentId' ";
                            }
                            if (!$name && !$parentId) {
                                setHTTPStatus("400", ['message' => "You have provided nothing in request body"]);
                            }
                            else {
                                $SQLRequest = $SQLRequest . " WHERE id='$topicId'";
                                $res = $Link->query($SQLRequest);
                                if ($res) {
                                    setHTTPStatus("200", ['message' => "OK"]);
                                }
                                else {
                                    setHTTPStatus("500", ['message' => $Link->error]);
                                }
                            }
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
                        $topicId = $urlList[1];
                        if (!$topicId) {
                            setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                        }
                        else if (!is_numeric($topicId)){
                            setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                        }
                        else if (!doesTopicExist($topicId)) {
                            setHTTPStatus("409", ['message' => "Topic with id $topicId does not exist"]);
                        }
                        else if (!$urlList[2]) {
                            //    /topics/{topicId}
                            $res = $Link->query("DELETE FROM topic WHERE id='$topicId'");
                            if ($res) {
                                setHTTPStatus("200", ['message' => "OK"]);
                            }
                            else {
                                setHTTPStatus("500", ['message' => $Link->error]);
                            }
                        }
                        else if ($urlList[2] == "childs") {
                            //    /topics/{topicId}/childs
                            if (!is_array($requestData->body)) {
                                setHTTPStatus("400", ['message' => "Body must be an array"]);
                                exit;
                            }
                            foreach ($requestData->body as &$childId) {
                                if (!doesTopicExist($childId)) {
                                    setHTTPStatus("409", ['message' => "Topic with ID $childId does not exist"]);
                                    exit;
                                }
                                if ($topicId == $childId) {
                                    setHTTPStatus("409", ['message' => "Topic cannot be child of itself."]);
                                    exit;
                                }
                                $res = $Link->query("UPDATE topic SET parentId=NULL WHERE id='$childId'");
                                if (!$res) {
                                    setHTTPStatus("500", ['message' => $Link->error]);
                                    exit;
                                }
                            }
                            setHTTPStatus("200", ['message' => "OK"]);
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
                setHTTPStatus("405", ['message' => "There is no $method method for /topics"]);
                break;
        }
    }
