<?php

require_once('db.php');
require_once('../model/Task.php');
require_once('../model/Response.php');

try{
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
}
catch(PDOException $ex){
    error_log("Connection Error -".$ex, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection Error");
    $response->send();
    exit;
}

if(array_key_exists("taskid",$_GET)){
    $taskid = $_GET['taskid'];
    if($taskid == '' || !is_numeric($taskid)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setHttpStatusCode(false);
        $response->addMessage("Task ID can not be blank or must be numeric");
        $response->send();
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        try{
            $query = $readDB->prepare('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();
            $rowCount = $query->rowCount();

            $taskArray = array();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Task not found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[] = $task->returnTaskAsArray();
            }
            $returnData = array();
            $returnData['rows_return'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log("Database Query Error -".$ex, 0);
            $respose = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to fetch data");
            $response->send();
    exit;
        }
    }
    elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        try{
            $query = $writeDB->prepare('delete from tbltasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Task not found.");
                $response->send();
                exit;
            }
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("Task Deleted");
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->addMessage("Failed to delete the task");
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    }
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
        try{
            require_once('../requirefile/checkjson.php');

            $title_updated = false;
            $description_updated = false;
            $deadline_updated = false;
            $completed_updated = false;

            $queryFields = '';

            if(isset($jsonData->title)){
                $title_updated = true;
                $queryFields .= "title = :title, ";
            }
            if(isset($jsonData->description)){
                $description_upadated = true;
                $queryFields .= "description = :description, ";
            }
            if(isset($jsonData->iscompleted)){
                $deadline_updated = true;
                $queryFields .= "completed = :completed, ";
            }
            if(isset($jsonData->deadline)){
                $deadline_updated = true;
                $queryFields .= "deadline = STR_TO_DATE(:deadline, '%Y:%m:%d %H:%i'), ";
            }

            $queryFields = rtrim("$queryFields", ", ");

            if(strlen($queryFields) < 2){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("No fields are provided to update!");
                $response->send();
                exit;
            }


        }
        catch(PDOException $ex){
            error_log("Database query error- ".$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to update tasks - check you data for info");
            $response->send();
            exit;
        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->addMessage("Request Metod not allowed");
        $response->setSuccess(false);
        $response->send();
        exit;
    }
}
elseif(array_key_exists("page",$_GET)){
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        $page = $_GET['page'];
        if($page == '' || !is_numeric($page)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Page cannot be blank or must be numeric");
            $response->send();
            exit;
        }
        $limitPerPage = 20;
        try{
            $query = $readDB->prepare('select count(id) as totalNumberOfTask from tbltasks');
            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $taskCount = intval($row['totalNumberOfTask']);

            $numberOfPages = ceil($taskCount/$limitPerPage);

            if($numberOfPages == 0){
                $numberOfPages = 1;
            }

            if($page > $numberOfPages || $page == 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Page not found");
                $response->send();
                exit;
            }

            $offset = ($page == 1 ? 0 : ($limitPerPage*($page - 1)));

            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks limit :pglimit offset :offset');
            $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);

                $taskArray[] = $task->returnTaskAsArray();
                $returnData['rows_return'] = $rowCount;
                $returnData['total_rows'] =  $taskCount;
                $returnData['total_page'] = $numberOfPages;
                ($page < $numberOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
                ($page > $numberOfPages ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
                $returnData = $taskArray;

                $response = new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit;   
            }
        }
        catch(TaskException $ex){
            print('Here it comes');
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log("Database query error".$ex, 0);
            $respose = new Response();
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->addMessage("Failed to get task");
            $response->send();
            exit;
        }
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }
}
elseif(array_key_exists("completed",$_GET)){
    $completed = $_GET['completed'];
    if($completed !== 'Y' && $completed !== 'N'){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Completed must be Y or N");
        $response->send();
        exit;
    }
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        try{
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where completed = :completed');
            $query->bindParam(':completed', $completed, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();
            $taskArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[] = $task->returnTaskAsArray();
            }
            $returnData = array();
            $returnData['rows_return'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
        exit;
        }
        catch(PDOException $ex){
            error_log("Database query error- ".$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get Task");
            $response->send();
        exit;
        }
    }else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request Methos not Allowed");
        $response->send();
        exit;
    }
}
elseif(empty($_GET)){
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        try{
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks');
            $query->execute();
            $rowCount = $query->rowCount();
            $taskArray = array();
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[] = $task->returnTaskAsArray();
            }
            $returnData = array();
            $returnData['rows_return'] = $rowCount;
            $returnData['tasksing'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        }
        catch(PDOException $ex){
            error_log("Database query error".$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get task");
            $response->send();
            exit;
        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;
        }
    }
  
    elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
        try{
            if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Content type header is not in JSON format");
                $response->send();
                exit;
            }
            $rawPOSTData = file_get_contents('php://input');

            if(!$jsonData = json_decode($rawPOSTData)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Request body is not valid JSON");
                $response->send();
                exit;
            }
            if(!isset($jsonData->title) || !isset($jsonData->completed)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                (!isset($jsonData->title) ? $response->addMessage("Title field is mendatory and must be provided") : false);
                (!isset($jsonData->completed) ? $response->addMessage("Completed field is mendatory and must be provided") : false);
                $response->send();
                exit;
            }
            $newTask = new Task(null, $jsonData->title, (isset($jsonData->description) ? $jsonData->description : null), 
            (isset($jsonData->deadline) ? $jsonData->deadline : null), $jsonData->completed);
            $title = $newTask->getTitle();
            $description = $newTask->getDescription();
            $deadline = $newTask->getDeadline();
            $completed = $newTask->getCompleted();

            $query = $writeDB->prepare('INSERT into tbltasks (title, description, deadline, completed) values (:title, :description, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
            $query->bindParam(":title", $title, PDO::PARAM_STR);
            $query->bindParam(":description", $description, PDO::PARAM_STR);
            $query->bindParam(":deadline", $deadline, PDO::PARAM_STR);
            $query->bindParam(":completed", $completed, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to create task");
                $response->send();
                exit;
            }

            $lastTaskId = $writeDB->lastInsertId();
            $query = $writeDB->prepare('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
            $query->bindParam(':taskid', $lastTaskId, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();
            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to retrive task after creation");
                $response->send();
                exit;
            }
            $taskArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[] = $task->returnTaskAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;
            
            $response = new Response();
            $response->setHttpStatusCode(201);
            $response->setSuccess(true);
            $response->addMessage("Task Created");
            $response->setData($returnData);
            $response->send();
            exit;


        }
        catch(PDOException $ex){
            error_log("Database query error-".$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to insert task into database- check submitted data for errors");
            $response->send();
            exit;
        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;
        }
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }
}
else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint Not Found");
    $response->send();
    exit;
}


