<?php
    class Response{
        private $_success;
        private $_httpStatusCode;
        private $_message = array();
        private $_data;
        private $_toCache = false;
        private $_responseData = array();

        public function setSuccess($success){
            $this->_success = $success;
        }
        
        public function setHttpStatusCode($httpStatusCode){
            $this->_httpStatusCode = $httpStatusCode;
        }

        public function addMessage($message){
            $this->_message[] = $message;
        }

        public function setData($data){
            $this->_data = $data;
        }

        public function toCache($toCache){
            $this->_toCache = $toCache;
        }

        public function send(){
            header('Content-type: application/json; charset:utf-8');
            if($this->_toCache == true){
                header('Cache-control: max-age=60');
            }
            else{
                header('Cache-control: no-cache, no-store');
            }

            if(($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode)){
                http_response_code(500); 
                $this->_responseData['statusCode'] = 500; 
                $this->_responseData['success'] = false;
                $this->addMessage("Response Creation Error");
                $this->_responseData['messages'] = $this->_message;
            }
            else{
                http_response_code($this->_httpStatusCode);
                $this->_responseData['success'] = $this->_success;
                $this->_responseData['statusCode'] = $this->_httpStatusCode;
                $this->_responseData['message'] = $this->_message;
                $this->_responseData['data'] = $this->_data;
                
            }

            echo json_encode($this->_responseData);
        }

    }