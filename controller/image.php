<?php
die("here it coems");
require_once('db.php');
require_once('../model/response.php');
require_once('../model/image.php');
function sendResponse($statusCode, $success, $message = null, $toCache = false, $data = null)
{
    $response = new Response();
    $response->setHttpStatusCode($statusCode);
    $response->setSuccess($success);
    ($message != null ? $response->addMessage($message) : false);
    ($toCache != null ? $response->toCache($toCache) : false);
    ($data != null ? $response->setData($data) : false);
    $response->send();
    exit;
}
function checkAuthStatusAndReturnUserId($writeDB)
{
    if (!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
        $message = null;
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $message = "Access token is missing from the header";
        } else {
            if (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
                $message = "Access token cannot be blank";
            }
        }
        sendResponse(401, false, $message);
    }
    $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

    try {
        $query = $writeDB->prepare('SELECT userid, accesstokenexpiry, loginattempts, useractive FROM tblsessions, tblusers WHERE tblsessions.userid = tblusers.id and accesstoken = :accesstoken');
        $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if ($rowCount === 0) {
            sendResponse(401, false, "Invalid Access Token");
        }

        $row = $query->fetch(PDO::FETCH_ASSOC);
        $returned_userId = $row['userid'];
        $returned_accesstokenexpiry = $row['accesstokenexpiry'];
        $returned_loginattempts = $row['loginattempts'];
        $returned_useractive = $row['useractive'];

        if ($returned_useractive !== 'Y') {
            sendResponse(401, false, "User account is not activated!");
        }

        if ($returned_loginattempts >= 3) {
            sendResponse(401, false, "User account is currently blocked!");
        }

        if (strtotime($returned_accesstokenexpiry) < time()) {
            sendResponse(401, false, "Access token is expired");
        }
        return $returned_userId;
    } catch (PDOException $ex) {
        sendResponse(500, false, "There is an issue authentication, try again later");
    }
}
if(array_key_exists("taskid", $_GET) && array_key_exists("imageid", $_GET) && array_key_exists("attributes", $_GET)){
    $taskid = $_GET['taskid'];
    $imageid = $_GET['imageid'];
    $attributes = $_GET['attributes'];

    if($taskid === '' || !is_numeric($taskid) || $imageid === '' || !is_numeric($imageid)){
        sendResponse(400, false, "Image Id or Task Id cannot be null and must be numeric");
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

    }
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){

    }
    else{
        sendResponse(405, false, "Request method not allowed");
    }
}
elseif(array_key_exists("taskid", $_GET) && array_key_exists("imageid", $_GET)){
    $taskid = $_GET['taskid'];
    $imageid = $_GET['imageid'];

    if($taskid === '' || !is_numeric($taskid) || $imageid === '' || !is_numeric($imageid)){
        sendResponse(400, false, "Image Id or Task Id cannot be null and must be numeric");
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

    }

    if($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    }
    else{
        sendResponse(405, false, "Request method not allowed");
    }
}
elseif(array_key_exists("taskid", $_GET) && !array_key_exists("imageid", $_GET)){
    $taskid = $_GET['taskid'];
    if($taskid === '' || !is_numeric($taskid)){
        sendResponse(400, false, "Task Id cannot be null and must be numeric");
    }
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

    }
    else{
        sendResponse(405, false, "Request method not allowed");
    }
}
else{
    sendResponse(404, false, "Endpoint not found");
}