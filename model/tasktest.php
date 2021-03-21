<?php

require_once('Task.php');
require_once('history.php');

try{
    $history = new History(1, "task", "Category", "Tags", "10/10/2020 12:15", null, null, "", "userId");
    header('Content-type: application/json;charset:UTF-8');
    echo json_encode($history->returnHistoryAsArray());
}
catch(HistoryException $ex){
    echo "Error occured: ".$ex->getMessage();
}