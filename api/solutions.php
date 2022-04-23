<?php
    function route($method, $urlList, $requestData) {
        global $Link;
        switch ($method) {
            case "GET":
                $solutionsRes = $Link->query("SELECT * FROM solution");
                if ($solutionsRes) {
                    $solutions = [];
                    if ($solutionsRes->num_rows > 0) {
                        while($row = $solutionsRes->fetch_assoc()) {
                            $isMatch = true;
                            if (!is_null($requestData->parameters['task']) && $requestData->parameters['task'] != $row['taskId']) {
                                $isMatch = false;
                            }
                            if (!is_null($requestData->parameters['user']) && $requestData->parameters['user'] != $row['authorId']) {
                                $isMatch = false;
                            }
                            if ($isMatch) {
                                array_push($solutions, $row);
                            }
                        }
                    }
                    setHTTPStatus("200", $solutions);
                }
                else setHTTPStatus("500", ['message' => $Link->error]);
                break;

            case "POST":
                $userId = validateToken();
                if ($userId) {
                    if (isUserAdmin($userId)) {
                        if ($urlList[2] == "postmoderation") {
                            $solutionId = $urlList[1];
                            if (!doesSolutionExist($solutionId)) {
                                setHTTPStatus("409", ["message" => "Solution with id $solutionId does not exist"]);
                                exit;
                            }
                            $verdict = $requestData->body->verdict;
                            if (!$verdict) {
                                setHTTPStatus("409", ["message" => "Verdict must be provided"]);
                                exit;
                            }
                            $res = $Link->query("UPDATE solution SET verdict='$verdict' WHERE id=$solutionId");
                            if ($res) {
                                $task = $Link->query("SELECT * FROM task WHERE id=(SELECT taskId FROM solution WHERE id='$solutionId')")->fetch_assoc();
                                unset($task['input'], $task['output']);
                                setHTTPStatus("200", $task);
                            }
                            else setHTTPStatus("500", ['message' => $Link->error]);
                        }
                        else {
                            setHTTPStatus("400", ['message' => "Bad request. Check URL"]);
                        }
                    }
                    else setHTTPStatus("403", ['message' => "Only admin has access to this method"]);
                }
                break;

            default: 
                setHTTPStatus("405", ['message' => "There is no $method method for /solutions"]);
                break;
        }
    }
?>