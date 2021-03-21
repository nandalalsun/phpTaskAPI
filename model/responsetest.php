<?php
require_once('Response.php');

// die("TEst");

$response = new Response();
$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage('Success');

$response-> setData("hello");
 $data = ['name', 'sunil', '18'];
 $response-> setData($data);
$response->send();


