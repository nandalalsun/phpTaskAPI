<?php
    require_once('db.php');
    require_once('../model/response.php');
    require_once('../model/image.php');

    function sendResponse($statusCode, $success, $message = null, $toCache = false, $data = null){
        $response = new Response();
        $response->setHttpStatusCode($statusCode);
        $response->setSuccess($success);
        ($message != null ? $response->addMessage($message) : false);
        ($toCache != null ? $response->toCache($toCache) : false);
        ($data != null ? $response->setData($data) : false);
        $response->send();
        exit;
    }
    

?>