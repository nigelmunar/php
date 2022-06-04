<?php
    declare(strict_types = 1);

    namespace Entities;

    class Error implements \JsonSerializable
    {
    	private $_errorID; //int
    	private $_errorPageID; //int
    	private $_userID; //int
    	private $_errorCode; //int
    	private $_errorMessage; //string
    	private $_dateTimeLogged; //string
    	private $_requestUrl; //string


    	public function getErrorID() : int
        {
            return $this->_errorID;
        }

        public function setErrorID(int $value) : void
        {
            $this->_errorID = $value;
        }

        public function getErrorPageID() : int
        {
            return $this->_errorPageID;
        }

        public function setErrorPageID(int $value) : void
        {
            $this->_errorPageID = $value;
        }

        public function getUserID() : int
        {
            return $this->_userID;
        }

        public function setUserID(int $value) : void
        {
            $this->_userID = $value;
        }

        public function getErrorCode() : int
        {
            return $this->_errorCode;
        }

        public function setErrorCode(int $value) : void
        {
            $this->_errorCode = $value;
        }

        public function getErrorMessage() : string
        {
            return $this->_errorMessage;
        }

        public function setErrorMessage(string $value) : void
        {
            $this->_errorMessage = $value;
        }

        public function getDateTimeLogged() : string
        {
            return $this->_dateTimeLogged;
        }

        public function setDateTimeLogged(string $value) : void
        {
            $this->_dateTimeLogged = $value;
        }

        public function getRequestURL() : string
        {
            return $this->_requestUrl;
        }

        public function setRequestURL(string $value) : void
        {
            $this->_requestUrl = $value;
        }

        public function jsonSerialize() : array
        {
            return [
            	'id' => $this->_errorID,
            	'page_id' => $this->_errorPageID,
            	'user_id' => $this->_userID,
            	'code' => $this->_errorCode,
            	'message' => $this->_errorMessage,
            	'dateLogged' => $this->_dateTimeLogged,
            	'url' => $this->_requestUrl
            ];
        }

        public function jsonDeserialize(string $jsonString) : void
        {
            $json = json_decode($jsonString, true);

            $this->_errorID = $json['id'];
            $this->_errorPageID = $json['page_id'];
            $this->_userID = $json['user_id'];
            $this->_errorCode = $json['code'];
            $this->_errorMessage = $json['message'];
            $this->_requestUrl = $json['url'];
            $this->_dateTimeLogged = null;

            if(!is_null($json['dateLogged']))
            {
                $json['dateLogged'] = new \DateTime($json['dateLogged']['date'], new \DateTimeZone($json['dateLogged']['timezone']));
            } 
        }
    }