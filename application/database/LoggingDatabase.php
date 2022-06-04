<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/utilities/RedisCacher.php';

    class LoggingDatabase
    {
        private $pdo;
        private $_userAgentID; //int
        private $_ipAddressID; //int
        private $_userID; //?int

        public function getUserAgentID() : int
        {
            return $this->_userAgentID;
        }

        public function setUserAgentID(int $value) : void
        {
            $this->_userAgentID = $value;
        }


        public function getIpAddressID() : int
        {
            return $this->_ipAddressID;
        }

        public function setIpAddressID(int $value) : void
        {
            $this->_ipAddressID = $value;
        }


        public function getAdministratorID() : ?int
        {
            return $this->_userID;
        }

        public function setUserID(int $value) : void
        {
            $this->_userID = $value;
        }


        public function __construct(\PDO $pdo)
        {
            $this->pdo = $pdo;

            $this->logUserAgent((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''));
            $this->logIPAddress((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''));

            $this->_userID = null;
        }

        //Log the user agent to the DB and store the id in the session
        private function logUserAgent(string $userAgent) : void
        {
            if(isset($_SESSION['UserAgent_' . md5($userAgent)]))
            {
                $this->_userAgentID = (int)$_SESSION['UserAgent_' . md5($userAgent)];
            }
            else
            {
                while(true)
                {
                    $stmt = $this->pdo->prepare('
                        SELECT `user_agent_id`
                        FROM `user_agents` 
                        WHERE `user_agent` = :user_agent');

                    $stmt->bindValue(':user_agent', substr($userAgent, 0, min(2000, strlen($userAgent))), \PDO::PARAM_STR);

                    $stmt->execute();

                    if($row = $stmt->fetch())
                    {
                        $this->_userAgentID = (int)$row['user_agent_id'];

                        $_SESSION['UserAgent_' . md5($userAgent)] = $this->_userAgentID;

                        break;
                    }


                    $stmt = $this->pdo->prepare('
                        INSERT INTO `user_agents`(`user_agent`) 
                        SELECT `new_user_agent` FROM (SELECT :user_agent AS `new_user_agent`) AS tbl1 WHERE NOT EXISTS(SELECT 1 FROM `user_agents` AS u WHERE `user_agent` = :user_agent)');

                    $stmt->bindValue(':user_agent', substr($userAgent, 0, min(2000, strlen($userAgent))), \PDO::PARAM_STR);

                    $stmt->execute();
                }
            }
        }

        //Log the ip address to the DB and store the id in the session
        private function logIPAddress(string $ipAddress) : void
        {
            
            if(isset($_SESSION['IPAddress_' . md5($ipAddress)]))
            {
                $this->_ipAddressID = (int)$_SESSION['IPAddress_' . md5($ipAddress)];
            }
            else
            {
                while(true)
                {
                    $stmt = $this->pdo->prepare('
                        SELECT `ip_address_id`
                        FROM `ip_addresses` 
                        WHERE `ip_address` = :ip_address');

                    $stmt->bindValue(':ip_address', substr($ipAddress, 0, min(39, strlen($ipAddress))), \PDO::PARAM_STR);

                    $stmt->execute();

                    if($row = $stmt->fetch())
                    {
                        $this->_ipAddressID = (int)$row['ip_address_id'];

                        $_SESSION['IPAddress_' . md5($ipAddress)] = $this->_ipAddressID;
                       
                        break;
                    }


                    $stmt = $this->pdo->prepare('
                        INSERT INTO `ip_addresses`(`ip_address`) 
                        SELECT `new_ip_address` FROM (SELECT :ip_address AS `new_ip_address`) AS tbl1 WHERE NOT EXISTS(SELECT 1 FROM `ip_addresses` AS u WHERE `ip_address` = :ip_address)');

                    $stmt->bindValue(':ip_address', substr($ipAddress, 0, min(39, strlen($ipAddress))), \PDO::PARAM_STR);

                    $stmt->execute();
                }
            }
        }
    }