<?php
    function route($method, $urlList, $requestData) {
        global $Link;
        $userId = validateToken();
        if ($userId) {
            switch ($method) {
                case "GET":
                    $roleIdFromURL = $urlList[1];
                    if (!$roleIdFromURL) {
                        $rolesResult = $Link->query("SELECT * FROM role");
                        $roles = [];
                        if ($rolesResult->num_rows > 0) {
                            while($row = $rolesResult->fetch_assoc()) {
                                array_push($roles, ['roleId' => $row['roleId'], 'name' => $row['name']]);
                            }
                        }
                        setHTTPStatus("200", $roles);
                    }
                    else {
                        if (!is_numeric($roleIdFromURL)) {
                            setHTTPStatus("400", ['message' => "roleId must be a number"]);
                        }
                        else {
                            $roleResult = $Link->query("SELECT * FROM role WHERE roleId='$roleIdFromURL'");
                            $role = $roleResult->fetch_assoc();
                            if ($roleResult) {
                                if (!$role) setHTTPStatus("400", ['message' => 'There is no role with such ID']);
                                else setHTTPStatus("200", $role);
                            }
                            else {
                                setHTTPStatus("500", ['message' => $Link->error]);
                            }
                        }
                    }
                    break;
                default: 
                    setHTTPStatus("405", ['message' => "There is no $method method for /roles"]);
                    break;
            }
        }
    }
?>