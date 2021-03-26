<?php 
    require_once('../controller/db.php');
    require_once('../model/Response.php');

    if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->addMessage("Access token is missing from header") : false);
        (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->addMessage("Access token cannot be blank") : false);
        $response->send();
        exit;
    }
    $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

try{
    $query = $writeDB->prepare('SELECT userid, accesstokenexpiry, loginattempts, useractive FROM tblsessions, tblusers WHERE tblsessions.userid = tblusers.id and accesstoken = :accesstoken');
    $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();

    if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Invalid Access Token");
        $response->send();
        exit;
    }

    $row = $query->fetch(PDO::FETCH_ASSOC);
    $returned_userId = $row['userid'];
    $returned_accesstokenexpiry = $row['accesstokenexpiry'];
    $returned_loginattempts = $row['loginattempts'];
    $returned_useractive = $row['useractive'];
    
    if($returned_useractive !== 'Y'){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("User account is not activated!");
        $response->send();
        exit;
    }

    if($returned_loginattempts >= 3){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("User account is currently blocked!");
        $response->send();
        exit;
    }

    if(strtotime($returned_accesstokenexpiry) < time()){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access token is expired");
        $response->send();
        exit;
    }
}
catch(PDOException $ex){
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("There is an issue authentication, try again later");
    $response->send();
    exit;
}
