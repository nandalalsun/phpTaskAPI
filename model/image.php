<?php
    class ImageException extends Exception{}
    Class Image{
        private $_id;
        private $_title;
        private $_filename;
        private $_mimetype;
        private $_taskid;
        private $_uploadFolderLocation;

        public function __construct($id, $title, $filename, $mimetype, $taskid){
            $this->setId($id);
            $this->setTitle($title);
            $this->setFilename($filename);
            $this->setMimetype($mimetype);
            $this->setTaskid($taskid);
            $this->_uploadFolderLocation = "../../../taskimages/";
        }

        public function getId(){
            return $this->_id;
        }
        public function getTitle(){
            return $this->_title;
        }
        public function getFilename(){
            return $this->_filename;
        }
        public function getFileExtension(){
            $filenameParts = explode(".", $this->_filename);
            $lastArrayElements = count($filenameParts)-1;
            $fileExtension = $filenameParts[$lastArrayElements];
            return $fileExtension;
        }
        public function getMimetype(){
            return $this->_mimetype;
        }
        public function getTaskid(){
            return $this->_taskid;
        }
        public function getFolderUploadLocation(){
            return $this->_uploadFolderLocation;
        }

        public function setId($id){
            if(($id !== null) && !is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->_id !== null){
                throw new ImageException("Image ID is not a valid ID");
            }
            $this->_id = $id;
        }

        public function setTitle($title){
            if(strlen($title) < 1 || strlen($title) > 255){
                throw new ImageException("Image title cannot valid.");
            }
            $this->_title = $title;
        }
        public function setFilename($filename){
            if(strlen($filename) < 1 || strlen($filename) > 30 || preg_match("/^[a-zA-Z0-9_-]+(.jpg|.jpeg|.png)$/", $filename) != 1){
                throw new ImageException("Image file filename error, it must be 1 to 30 characters only and .jpg .jpeg and .png");
            } 
            return $this->_filename = $filename;
        }
        public function setMimetype($mimetype){
            if(strlen($mimetype) < 1 || strlen($mimetype) > 255){
                throw new ImageException("Image mimetype error");
            }
            return $this->_mimetype = $mimetype;
        }
        public function setTaskid($taskid){
            if(($taskid !== null) && !is_numeric($taskid) || $taskid <= 0 || $taskid > 9223372036854775807 || $this->_taskid !== null){
                throw new ImageException("Image Task ID is not a valid ID");
            }
            return $this->_taskid = $taskid;
        }
        public function getImageUrl(){
            $httpOrHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
            $host = $_SERVER['HTTP_HOST'];
            $url = "/v1/tasks".$this->getTaskid()."/images".$this->getId();
            return $httpOrHttps."://".$host.$url;
        }
        public function returnImageAsArray(){
            $image = array();
            $image['id'] = $this->getId();
            $image['title'] = $this->getTitle();
            $image['filename'] = $this->getfilename();
            $image['mimetype'] = $this->getMimetype();
            $image['taskid'] = $this->getTaskid();
            $image['image_url'] = $this->getImageUrl();
            return $image;
        }
    }
?>