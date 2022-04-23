<?php
    function doesTaskExist($taskId) {
        global $Link;
        $result = $Link->query("SELECT * FROM task WHERE id='$taskId'")->fetch_assoc();
        if (is_null($result)) return false;
        return true;
    }

    function doesSolutionExist($solutionId) {
        global $Link;
        $result = $Link->query("SELECT * FROM solution WHERE id='$solutionId'")->fetch_assoc();
        if (is_null($result)) return false;
        return true;
    }