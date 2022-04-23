<?php
    function doesTopicExist($topicId) {
        global $Link;
        $result = $Link->query("SELECT * FROM topic WHERE id='$topicId'")->fetch_assoc();
        if (is_null($result)) return false;
        return true;
    }

    function getTopicChilds($topicId) {
        global $Link;
        $topicResult = $Link->query("SELECT * FROM topic WHERE parentId='$topicId'");
        $childs = [];
        if ($topicResult->num_rows > 0) {
            while($row = $topicResult->fetch_assoc()) {
                array_push($childs, ['id' => $row['id'], 'name' => $row['name'], 'parentId' => $row['parentId']]);
            }
        }
        return $childs;
    }
?>