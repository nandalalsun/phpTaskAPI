<?php
require_once('localdb.php');
require_once('../model/response.php');

if (array_key_exists("sessionId", $_GET)) {
    //Getting the sessionId from URL
    $sessionId = $_GET['sessionId'];

    //Validating sessionId whether it is valid or not? If not show error message!
    if ($sessionId === '' || !is_numeric($sessionId)) {
        function _message($response)
        {
            $response->addMessage("Session Id cannot be null");
            $response->send();
            exit;
        }
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        ($sessionId === '' ? _message($response) : false);
        (!is_numeric($sessionId) ? $response->addMessage("Session Id must be numeric value") : false);
        $response->send();
        exit;
    }
    
    //Checking whether accesstoken is provided or not? if not show error message!
    if (!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->addMessage("Access token is missing from header") : false);
        (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->addMessage("Access token can not be null value!") : false);
        $response->send();
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    }
    // If the request method is DELETE then delete the session 
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        //Stroring the provoded access token 
        $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

        //delete session from database table
        $query = $writeDB->prepare('DELETE FROM tblsessions WHERE id = :sessionId and accesstoken = :accesstoken');
        $query->bindParam(':sessionId', $sessionId, PDO::PARAM_INT);
        $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();
        //check whether the query is successfully deleted the seesion or not?
        if ($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("The server is unable proceed logging out request, check your passed in data!");
            $response->send();
            exit;
        }
        $returnData = array();
        $returnData['session_id'] = intval($sessionId);
        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Logged out successfully!");
        $response->setData($returnData);
        $response->send();
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        require_once('../requirefile/checkjson.php');
        if(!isset($jsonData->accesstoken) || !isset($jsonData->refreshtoken)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            (!isset($jsonData->refreshtoken) ? $response->addMessage("Refresh token is missing from header") : false);
            (!isset($jsonData->accesstoken) ? $response->addMessage("Access token is missing from header") : false);
            $response->send();
            exit;
        }
        try{
            $access_token = $jsonData->accesstoken;
            $refresh_token = $jsonData->refreshtoken;
            $query = $readDB->prepare('SELECT tblsessions.id as sessionId, tblsessions.userid as 
            userid, accesstoken, refreshtoken, useractive, loginattempts, accesstokenexpiry, 
            refreshtokenexpiry from tblsessions, tblusers where tblusers.id = tblsessions.userid and 
            tblsessions.id = :sessionId and tblsessions.accesstoken = :accesstoken and 
            tblsessions.refreshtoken = :refreshtoken');
            $query->bindParam('sessionId', $sessionId, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $access_token, PDO::PARAM_STR);
            $query->bindParam(':refreshtoken', $refresh_token, PDO::PARAM_STR);
            $query->execute();
            
            $rowCount = $query->rowCount();
            
            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("Access token or refresh token is incorrect to the session Id");
                $response->send();
                exit;
            }

            $returnData = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $returnData['sessionId'] = $row['sessionId'];
                $returnData['userId'] = $row['userid'];
                $returnData['refresh_token'] = $row['refreshtoken'];
                $returnData['access_token'] = $row['accesstoken'];
                $returnData['useractive'] = $row['useractive'];
                $returnData['loginattempts'] = $row['loginattempts'];
                $returnData['access_token_expiry_in'] = $row['accesstokenexpiry']; 
                $returnData['refresh_token_expiry_in'] = $row['refreshtokenexpiry']; 
            }
            $response = new Response(); 
            $response->setSuccess(true);
            $response->setHttpStatusCode(200);
            $response->addMessage("Success");
            $response->setData($returnData);
            $response->send();
            exit;
            
        }
        catch(PDOException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("There is an issue refreshing access token, try again later");
            $response->send();
            exit;
        }
    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }
} elseif (empty($_GET)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }
    sleep(1);
    require_once('../requirefile/checkjson.php');
    if (!isset($jsonData->username) || !isset($jsonData->password)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->username) ? $response->addMessage("Username is required") : false);
        (!isset($jsonData->password) ? $response->addMessage("Password is required") : false);
        $response->send();
        exit;
    }
    if (strlen($jsonData->username) < 1 || strlen($jsonData->username) > 255 || strlen($jsonData->password) < 1 || strlen($jsonData->password) > 255) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->username) < 1 ? $response->addMessage("Username cannot be blank") : false);
        (strlen($jsonData->username) > 255 ? $response->addMessage("Username cannot be greater than 255 character") : false);

        (strlen($jsonData->password) < 1 ? $response->addMessage("Password cannot be blank") : false);
        (strlen($jsonData->password) > 255 ? $response->addMessage("Password cannot be greater than 255 character") : false);
        $response->send();
        exit;
    }
    try {
        $username = $jsonData->username;
        $password = $jsonData->password;

        $query = $writeDB->prepare('SELECT id, fullname, username, password, useractive, loginattempts from tblusers where username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if ($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Unauthorized login attempts");
            $response->send();
            exit;
        }
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $returned_id = $row['id'];
        $returned_username = $row['username'];
        $returned_fullname = $row['fullname'];
        $returned_password = $row['password'];
        $returned_useractive = $row['useractive'];
        $returned_loginattempts = $row['loginattempts'];

        if ($returned_useractive !== 'Y') {
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("User account is not active");
            $response->send();
            exit;
        }
        if ($returned_loginattempts >= 3) {
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Locked! Too many wrongs attempts, try again after a while");
            $response->send();
            exit;
        }
        if (!password_verify($password, $returned_password)) {
            $query = $writeDB->prepare('update tblusers set loginattempts = loginattempts+1 where id = :id');
            $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
            $query->execute();

            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Username or password is incorrect!");
            $response->send();
            exit;
        }
        $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)) . time());
        $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)) . time());

        $access_token_expiry_second = 1200;
        $refresh_token_expiry_seconds = 1209600;
    } catch (PDOException $ex) {
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("There was an issue logging in");
        $response->send();
        exit;
    }
    try {

        $writeDB->beginTransaction();
        $query = $writeDB->prepare('UPDATE tblusers SET loginattempts = 0 WHERE id = :id');
        $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
        $query->execute();
        $query = $writeDB->prepare('INSERT INTO tblsessions (userid, accesstoken, accesstokenexpiry, refreshtoken, refreshtokenexpiry) 
                values(:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND))');
        $query->bindParam(':userid', $returned_id, PDO::PARAM_INT);
        $query->bindParam(':accesstoken', $accesstoken);
        $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_second, PDO::PARAM_INT);
        $query->bindParam(':refreshtoken', $refreshtoken);
        $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);

        $query->execute();

        $lastSessionId = $writeDB->lastInsertId();
        $writeDB->commit();
        $returnData = array();


        $returnData['session_id'] = intval($lastSessionId);
        $returnData['accesstoken'] = $accesstoken;
        $returnData['access_token_expire_in'] = $access_token_expiry_second / 60 . " Min";
        $returnData['refreshtoken'] = $refreshtoken;
        $returnData['refresh_token_expire_in'] = $refresh_token_expiry_seconds / 3600 / 24 . " Days";
        $returnData['user_id'] = $returned_id;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->setData($returnData);
        $response->send();
        exit;
    } catch (PDOException $ex) {
        $writeDB->rollBack();
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("There was an issue logging in, please try again");
        $response->send();
        exit;
    }
} else {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(true);
    $response->addMessage("Endpoint not found");
    $response->send();
    exit;
}
