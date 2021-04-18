<?php
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
function uploadImageRoute($readDB, $writeDB, $taskid, $returned_userid){
    try{
        if(!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], "multipart/form-data; boundary=") === false){
            sendResponse(400, false, "Content Type header is not set to multipart/form-data with a boundary");
        }
        $query = $readDB->prepare('SELECT id FROM tbltasks WHERE taskid = :taskid AND userid = :userid');
        $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();
        if($rowCount === 0){
            sendResponse(404, false, "Task not found");
        }
        if(!isset($_POST['attributes'])){
            sendResponse(400, false, "Attributes missing from body header");
        }
        if(!$jsonImageAttributes = json_decode($_POST['attributes'])){
            sendResponse(400, false, "Attributes is not a valid JSON");
        }
        if(!isset($jsonImageAttributes->title) || !isset($jsonImageAttributes->filename) || $jsonImageAttributes->title == '' || $jsonImageAttributes->filename == ''){
            sendResponse(400, false, "Title and filename is mandatory");
        }
        if(strpos($jsonImageAttributes->filename,".") > 0){
            sendResponse(400, false, "Filename must not contain extension");
        }
        if(!isset($_FILES['imagefile']) || $_FILES['imagefile']['error'] !==0 ){
            sendResponse(500, false, "Image file upload unsuccessful - make sure that you selected a file");
        }
        $imageFileDetails = getimagesize($_FILES['imagefile']['temp_name']);

        if(isset($_FILES['imagefile']['size']) && $_FILES['imagefile']['size'] > 5242880){
            sendResponse(400, false, "File must be less than 5MB");
        }
        $allowedImageFileType = array('image/jpg','image/gif','image/png');

        if(!is_array($imageFileDetails['mime'], $allowedImageFileType)){
            sendResponse(400, false, "Image extension is not in supported format");
        }
        $fileExtension = "";
        switch($imageFileDetails['mime']){
            case "image/jpeg":
                $fileExtension = ".jpg";
                break;
            case "image/gif": 
                $fileExtension = "gif";
                break;
            case "image/png":
                $fileExtension = "png";
                break;
            default:
                break;
        }

        if($fileExtension == ""){
            sendResponse(400, false, "No valid file extension found for mimetype");
        }
        $image = new Image(null, $jsonImageAttributes->title, $jsonImageAttributes->filename, $imageFileDetails['mime'], $taskid);
        $title = $image->getTitle();
        $newFileName = $image->getFilename();
        $mimetype = $image->getMimetype();

        $query = $readDB->prepare('SELECT tblimages.id FROM tblimages, tbltasks WHERE tblimages.taskid = tbltask.id and tbltask.id = :taskid and tbltasks.userid = :userid and tblimages.filename = :filename');
        $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
        $query->bindParam('userid', $returned_userid, PDO::PARAM_INT);
        $query->bindParam(':filename', $newFileName);
        $query->execute();

        $rowCount = $query->rowCount();
        if($rowCount !== 0){
            sendResponse(409, false, "A file with that name already exist for this task - try different filename");
        }
        $writeDB->beginTransaction();


    }
    catch(PDOException $ex){
        error_log("Database query error".$ex,0 );
        if($writeDB->inTransaction()){
            
        }
        sendResponse(500, false, "Failed to upload the Image");
    }
    catch(ImageException $ex){
        sendResponse(500, false, $ex->getMessage());
    }
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
$returned_userid = checkAuthStatusAndReturnUserId($writeDB);

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

    elseif($_SERVER['REQUEST_METHOD'] === 'GET'){
        
    }

    elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){

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
        uploadImageRoute($readDB, $writeDB, $taskid, $returned_userid);
    }
    else{
        sendResponse(405, false, "Request method not allowed");
    }
}
else{
    sendResponse(404, false, "Endpoint not found");
}