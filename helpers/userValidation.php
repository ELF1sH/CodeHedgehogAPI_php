<?php
    function isUserAdmin($userId) {
        global $Link;
        $adminRoleId = $Link->query("SELECT roleId FROM role WHERE name='admin'")->fetch_assoc()['roleId'];
        $userRoleId = $Link->query("SELECT roleId FROM user WHERE userId='$userId'")->fetch_assoc()['roleId'];
        if ($adminRoleId == $userRoleId) return true;
        return false;
    }

    function doesUserExist($userId) {
        global $Link;
        $result = $Link->query("SELECT * FROM user WHERE userId='$userId'")->fetch_assoc();
        if (is_null($result)) return false;
        return true;
    }

    function doesRoleExist($roleId) {
        global $Link;
        $result = $Link->query("SELECT * FROM role WHERE roleId='$roleId'")->fetch_assoc();
        if (is_null($result)) return false;
        return true;
    }
?>