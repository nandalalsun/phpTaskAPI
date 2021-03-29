<?php
    require_once('image.php');
    try{
        $image = new Image(1, "title", "jkbs.jpeg", "mimetype/jpeg", 12);
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($image->returnImageAsArray());
    }
    catch(PDOException $ex){
        echo "error: $ex->getMessage()";
    }
?>