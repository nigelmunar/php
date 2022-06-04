<?php
    declare(strict_types = 1);
    
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/entities/Administrator.php';
    // require_once ROOT_PATH . 'application/factories/EmailDatabaseFactory.php';
 
    class AdministratorDatabase
    {
        private $pdo;
        private $administratorCount = [];
        private $administratorResults = [];
        private $administrators = [];


        //########## BEGIN LOGIN FUNCTIONS ##########//

        public function __construct(\PDO $pdo)
        {
            global $displayTimezone;

            $this->pdo = $pdo;

            if(isset($_SESSION['pendingLogin_DateTime']))
            {
                if(!is_a($_SESSION['pendingLogin_DateTime'], 'DateTime'))
                {
                    unset($_SESSION['pendingLogin_AdministratorID']);
                    unset($_SESSION['pendingLogin_RememberMe']);
                    unset($_SESSION['pendingLogin_Secret']);
                    unset($_SESSION['pendingLogin_DateTime']);
                }
                else
                {
                    $start_date = $_SESSION['pendingLogin_DateTime'];
                    $since_start = $start_date->diff(new \DateTime('now', $displayTimezone));

                    $minutes = $since_start->days * 24 * 60;
                    $minutes += $since_start->h * 60;
                    $minutes += $since_start->i;

                    if($minutes >= 5)
                    {
                        unset($_SESSION['pendingLogin_AdministratorID']);
                        unset($_SESSION['pendingLogin_RememberMe']);
                        unset($_SESSION['pendingLogin_Secret']);
                        unset($_SESSION['pendingLogin_DateTime']);
                    }
                }
            }
            else
            {
                unset($_SESSION['pendingLogin_AdministratorID']);
                unset($_SESSION['pendingLogin_RememberMe']);
                unset($_SESSION['pendingLogin_Secret']);
                unset($_SESSION['pendingLogin_DateTime']);
            }

            if(!($this->loggedIn() && $this->validateLogin($_SESSION['administratorID'])))
            {
                if(!$this->attemptCookieLogin())
                {
                    $this->logout();
                }
            }
        }


        public function getLoggedInAdministrator() : ?Entities\Administrator
        {
            if(!$this->loggedIn())
            {
                return null;
            }

            $administrator = new Entities\Administrator();

            $administrator->setAdministratorID($_SESSION['administratorID']);
            $administrator->setAdministratorCode($_SESSION['administratorCode']);
            $administrator->setFirstName($_SESSION['firstName']);
            $administrator->setLastName($_SESSION['lastName']);
            $administrator->setEmail($_SESSION['email']);
            $administrator->setDateTimeCreated($_SESSION['dateTimeCreated']);
            $administrator->setSuperAdministrator($_SESSION['superAdministrator']);
            
            $administrator->setPermissions($this->getPermissionsForAdministrator($administrator->getAdministratorID()));

            return $administrator;
        }


        public function loggedIn() : bool
        {
            if(isset($_SESSION['administratorID']))
            {
                return true;
            }

            return false;
        }


        public function logout() : void
        {
            if(isset($_SESSION['administratorID']))
            {
                unset($_SESSION['administratorID']);
            }

            if(isset($_SESSION['administratorCode']))
            {
                unset($_SESSION['administratorCode']);
            }

            if(isset($_SESSION['firstName']))
            {
                unset($_SESSION['firstName']);
            }

            if(isset($_SESSION['lastName']))
            {
                unset($_SESSION['lastName']);
            }

            if(isset($_SESSION['email']))
            {
                unset($_SESSION['email']);
            }

            if(isset($_SESSION['dateTimeCreated']))
            {
                unset($_SESSION['dateTimeCreated']);
            }

            if(isset($_SESSION['superAdministrator']))
            {
                unset($_SESSION['superAdministrator']);
            }


            if(isset($_COOKIE['rememberme']))
            {
                $this->expireRememberMeCode($_COOKIE['rememberme']);
                
                setcookie('rememberme', '', time() - (86400), '/');
            }
        }


        private function extendRememberMeCode(string $rememberMeCode) : void
        {
            $stmt = $this->pdo->prepare('UPDATE `remember_me_codes` SET `expiry_date` = DATE_ADD(NOW(), INTERVAL 90 DAY) WHERE `remember_me_code` = :remember_me_code');

            $stmt->bindValue(':remember_me_code', $rememberMeCode, PDO::PARAM_STR);

            $stmt->execute();

            setcookie('rememberme', $rememberMeCode, time() + (86400 * 90), '/');
        }


        private function expireRememberMeCode(string $rememberMeCode) : void
        {
            $stmt = $this->pdo->prepare('DELETE FROM `remember_me_codes` WHERE `remember_me_code` = :remember_me_code');

            $stmt->bindValue(':remember_me_code', $rememberMeCode, PDO::PARAM_STR);

            $stmt->execute();
        }


        private function expireRememberMeCodes() : void
        {
            $stmt = $this->pdo->prepare('DELETE FROM `remember_me_codes` WHERE `expiry_date` < NOW()');

            $stmt->execute();
        }


        private function attemptCookieLogin() : bool
        {
            if(isset($_COOKIE['rememberme']))
            {
                $this->expireRememberMeCodes();

                $stmt = $this->pdo->prepare('SELECT `administrator_id` 
                    FROM `remember_me_codes` 
                    WHERE `remember_me_code` = :remember_me_code AND `administrator_id` in (SELECT `administrator_id` FROM `administrators` WHERE `enabled` = 1 AND `live` = 1)');

                $stmt->bindValue(':remember_me_code', $_COOKIE['rememberme'], PDO::PARAM_STR);

                $stmt->execute();

                if($row = $stmt->fetch())
                {
                    $this->performLogin((int)$row['administrator_id'], true);
                    
                    $this->extendRememberMeCode($_COOKIE['rememberme']);

                    return true;
                }
            }

            return false;
        }


        //Only validate login every 60 seconds
        private function validateLogin(int $administratorID) : bool
        {
            global $displayTimezone;

            $lastValidatedLogin = new \DateTime('now', $displayTimezone);

            if(!isset($_SESSION['lastValidatedLogin']))
            {
                $lastValidatedLogin->sub(new DateInterval('P1D'));

                $_SESSION['lastValidatedLogin'] = $lastValidatedLogin;
            }

            $lastValidatedLogin = $_SESSION['lastValidatedLogin'];

            $diff = $lastValidatedLogin->diff(new \DateTime('now', $displayTimezone));

            $intervalInSeconds = (new \DateTime('now', $displayTimezone))->setTimeStamp(0)->add($diff)->getTimeStamp();

            if($intervalInSeconds >= 60)
            {
                $stmt = $this->pdo->prepare('SELECT `administrator_id` FROM `administrators` WHERE `administrator_id` = :administrator_id AND `enabled` = 1 AND `live` = 1');

                $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);

                $stmt->execute();
   
                if($row = $stmt->fetch())
                {
                    $_SESSION['lastValidatedLogin'] = new \DateTime('now', $displayTimezone);

                    //refresh permissions in case they have changed


                    return true;
                }

                return false;
            }

            return true;    
        }


        public function login(string $email, string $password, bool $rememberMe) : bool
        {
            global $displayTimezone;

            if(!$this->loggedIn())
            {
                $stmt = $this->pdo->prepare('
                    SELECT `administrator_id`, `password` 
                    FROM `administrators` 
                    WHERE `email` = :email AND `password` IS NOT NULL AND `enabled` = 1 AND `live` = 1');

                $stmt->bindValue(':email', $email, PDO::PARAM_STR);

                $stmt->execute();

                
                if($row = $stmt->fetch())
                {
                    if(password_verify($password, $row['password']))
                    {
                        $administratorID = (int)$row['administrator_id'];

                        //$this->performLogin($administratorID, false);

                        $_SESSION['pendingLogin_AdministratorID'] = $administratorID;
                        $_SESSION['pendingLogin_RememberMe']      = '';
                        $_SESSION['pendingLogin_Secret']          = '';
                        $_SESSION['pendingLogin_DateTime']        = new \DateTime('now', $displayTimezone);

                        if($rememberMe)
                        {
                            $stmt = $this->pdo->prepare('INSERT INTO `remember_me_codes`(`administrator_id`, `expiry_date`)
                                VALUES(:administrator_id, DATE_ADD(NOW(), INTERVAL 90 DAY))');

                            $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);

                            $stmt->execute();


                            $stmt = $this->pdo->prepare('SELECT `remember_me_code`
                                FROM `remember_me_codes`
                                WHERE `remember_me_code_id` = :remember_me_code_id');

                            $stmt->bindValue(':remember_me_code_id', $this->pdo->lastInsertId(), PDO::PARAM_INT);

                            $stmt->execute();

                            if($row = $stmt->fetch())
                            {
                                //setcookie('rememberme', $row['remember_me_code'], time() + (86400 * 90), '/');
                                $_SESSION['pendingLogin_RememberMe']      = $row['remember_me_code'];
                            }
                        }

                        return true;
                    }
                }
            
                return false;
            }

            return true;
        }


        public function getTwoFactorLogin(int $administratorID) : string
        {
            if(!$this->loggedIn())
            {
                if(!isset($_SESSION['pendingLogin_Secret']) || (isset($_SESSION['pendingLogin_Secret']) && strlen($_SESSION['pendingLogin_Secret']) === 0))
                {
                    $stmt = $this->pdo->prepare('
                        SELECT `two_factor_secret` 
                        FROM `administrators` 
                        WHERE `administrator_id` = :administrator_id AND `live` = 1');

                    $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);

                    $stmt->execute();
    
                    if($row = $stmt->fetch())
                    {
                        if(!is_null($row['two_factor_secret']))
                        {
                            return $row['two_factor_secret'];
                        }

                        //New secret, save in session for later
                        $tfa = new \RobThree\Auth\TwoFactorAuth('NigelMunar');

                        $secret = $tfa->createSecret();

                        $_SESSION['pendingLogin_Secret'] = $secret;

                        return $_SESSION['pendingLogin_Secret'];
                    }
                }
                else
                {
                    return $_SESSION['pendingLogin_Secret'];
                }
            }

            return '';
        }

        public function validateTwoFactorSuccessCode(int $administratorID, string $administratorTwoFactorSuccessCode) : bool
        {
            $stmt = $this->pdo->prepare('
                SELECT `administrator_two_factor_success_code_id`
                FROM `administrator_two_factor_success_codes` 
                WHERE `administrator_id` = :administrator_id AND `administrator_two_factor_success_code` = :administrator_two_factor_success_code');

            $stmt->bindValue(':administrator_id',                       $administratorID,                   \PDO::PARAM_INT);
            $stmt->bindValue(':administrator_two_factor_success_code',  $administratorTwoFactorSuccessCode, \PDO::PARAM_STR);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return true;
            }

            return false;
        }


        public function performLogin(int $administratorID, bool $cookie, bool $updateLoginDate = true) : void
        {
            $stmt = $this->pdo->prepare('
                SELECT `administrator_id`, `administrator_code`, `first_name`, `last_name`, `email`, `date_time_created`, `super_administrator`
                FROM `administrators` 
                WHERE `administrator_id` = :administrator_id AND `enabled` = 1 AND `live` = 1');

            $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $_SESSION['administratorID']          = (int)$row['administrator_id'];
                $_SESSION['administratorCode']        = (string)$row['administrator_code'];
                $_SESSION['firstName']       = (string)$row['first_name'];
                $_SESSION['lastName']        = (string)$row['last_name'];
                $_SESSION['email']           = (string)$row['email'];
                $_SESSION['dateTimeCreated'] = datetimeFromDB($row['date_time_created']);
                $_SESSION['superAdministrator']        = (bool)$row['super_administrator'];
            }

            if($updateLoginDate) 
            {
                $stmt = $this->pdo->prepare('UPDATE `administrators` SET `date_time_last_logged_in` = NOW() WHERE `administrator_id` = :administrator_id');

                $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);

                $stmt->execute();

                $stmt = $this->pdo->prepare('INSERT INTO `administrator_logins`(`administrator_id`, `ip_address`, `user_agent`, `login_date_time`, `login_type`) 
                    VALUES(:administrator_id, :ip_address, :user_agent, NOW(), :login_type)');

                $stmt->bindValue(':administrator_id', $administratorID, PDO::PARAM_INT);
                $stmt->bindValue(':ip_address', substr(((isset($_SERVER['REMOTE_ADDR']) && !is_null($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : ''), 0, 50), PDO::PARAM_STR);
                $stmt->bindValue(':user_agent', substr(((isset($_SERVER['HTTP_USER_AGENT']) && !is_null($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : ''), 0, 2000), PDO::PARAM_STR);
                $stmt->bindValue(':login_type', (($cookie) ? 'COOKIE' : 'NORMAL'), PDO::PARAM_STR);
                
                $stmt->execute();
            }
        }


        public function createLogin(string $email, string $password, string $firstName, string $lastName) : int
        {
            $stmt = $this->pdo->prepare('
                SELECT 1 AS `dummy_col` 
                FROM `administrators`
                WHERE `email` = :email');
            
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                //Email already exists, abort
                return -1;
            }


            $stmt = $this->pdo->prepare('
                INSERT INTO `administrators`(`email`, `password`, `first_name`, `last_name`, `date_time_created`, `enabled`, `live`)
                VALUES(:email, :password, :first_name, :last_name, NOW(), 1, 1)');
            
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt->bindValue(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindValue(':last_name', $lastName, PDO::PARAM_STR);

            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        }


        private function expirePasswordResetRequests() : void
        {
            $stmt = $this->pdo->prepare('UPDATE `password_reset_requests` SET `expired` = 1 WHERE `used` = 0 AND `expired` = 0 AND `date_time_expires` < NOW()');

            $stmt->execute();
        }


        //Returns true if a request was created, false otherwise
        public function createPasswordResetRequest(string $email) : bool
        {
            global $siteURL, $displayTimezone;

            $this->expirePasswordResetRequests();

            //Already logged in, you don't need a password reset
            if($this->loggedIn())
            {
                return false;
            }

            unset($_SESSION['PasswordResetTimeout']);

            $administratorID = -1;

            $stmt = $this->pdo->prepare('
                SELECT `administrator_id`, `first_name`, `email`
                FROM `administrators`
                WHERE `email` = :email AND `live` = 1
                LIMIT 1');

            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $administratorID     = (int)$row['administrator_id'];
                $firstName           = (string)$row['first_name'];
                $email               = (string)$row['email'];
            }

            if($administratorID === -1)
            {
                //Didn't find the email address in the database
                return false;
            }


            $stmt = $this->pdo->prepare('
                SELECT `date_time_expires`
                FROM `password_reset_requests`
                WHERE `administrator_id` = :administrator_id AND `used` = 0 AND `expired` = 0
                LIMIT 1');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $_SESSION['PasswordResetTimeout'] = datetimeFromDB($row['date_time_expires']);

                //Request in progress, don't create another
                return false;
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO `password_reset_requests`(`administrator_id`, `date_time_added`, `date_time_expires`, `used`, `expired`)
                VALUES(:administrator_id, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0, 0)');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            //Go get the reset code for email purposes
            $stmt = $this->pdo->prepare('SELECT `password_reset_request_code` FROM `password_reset_requests` WHERE `password_reset_request_id` = :password_reset_request_id');

            $stmt->bindValue(':password_reset_request_id', $this->pdo->lastInsertId(), \PDO::PARAM_INT);

            $stmt->execute();

            $passwordResetRequestCode = '';

            if($row = $stmt->fetch())
            {
                $passwordResetRequestCode = (string)$row['password_reset_request_code'];

                $emailDB = \EmailDatabaseFactory::create();

                $emailToSend = $emailDB->createEmail();

                $emailToSend->setEmailType('Administrator Forgotten Password');
                $emailToSend->setSubject('VegTrug Password Reset Request.');

                $emailToSend->addTag('FirstName', $firstName);
                $emailToSend->addTag('PasswordResetLink', $siteURL . 'admin/reset-password.html?code=' . $passwordResetRequestCode);

                $emailToSend->addRecipient($email, 'to');
                $emailToSend->addRecipient('andy.mayes@revive.digital', 'bcc');

		        $emailToSend = $emailDB->addEmail($emailToSend);

                $_SESSION['PasswordResetTimeout'] = (new \DateTime('now', $displayTimezone))->add(new \DateInterval('PT10M'));
            }

            return true;
        }


        public function validatePasswordResetRequestCode(string $passwordResetRequestCode) : bool
        {
            if(strlen($passwordResetRequestCode) > 0)
            {
                $this->expirePasswordResetRequests();

                $stmt = $this->pdo->prepare('
                    SELECT `password_reset_request_id`
                    FROM `password_reset_requests`
                    WHERE `password_reset_request_code` = :password_reset_request_code AND `used` = 0 AND `expired` = 0
                    LIMIT 1');

                $stmt->bindValue(':password_reset_request_code', $passwordResetRequestCode, \PDO::PARAM_STR);

                $stmt->execute();

                if($row = $stmt->fetch())
                {
                    return true;
                }
            }

            return false;
        }


        public function resetPassword(string $passwordResetRequestCode, string $newPassword) : bool
        {
            if(strlen($passwordResetRequestCode) > 0 && strlen($newPassword) > 0)
            {
                $passwordResetRequestID = -1;
                $administratorID                 = -1;

                $this->expirePasswordResetRequests();

                $stmt = $this->pdo->prepare('
                    SELECT `password_reset_request_id`, u.`administrator_id`, `email`, `first_name`
                    FROM `password_reset_requests` AS prr
                    INNER JOIN `administrators` AS u ON prr.`administrator_id` = u.`administrator_id`
                    WHERE `password_reset_request_code` = :password_reset_request_code AND `used` = 0 AND `expired` = 0
                    LIMIT 1');

                $stmt->bindValue(':password_reset_request_code', $passwordResetRequestCode, \PDO::PARAM_STR);

                $stmt->execute();

                if($row = $stmt->fetch())
                {
                    $passwordResetRequestID = (int)$row['password_reset_request_id'];
                    $administratorID                 = (int)$row['administrator_id'];
                    $email                  = (string)$row['email'];
                    $firstName              = (string)$row['first_name'];
                }


                if($passwordResetRequestID !== -1)
                {
                    //Update the administrator's password
                    $stmt = $this->pdo->prepare('
                        UPDATE `administrators`
                        SET `password` = :password
                        WHERE `administrator_id` = :administrator_id');

                    $stmt->bindValue(':password', password_hash($newPassword, PASSWORD_DEFAULT), \PDO::PARAM_STR);
                    $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

                    $stmt->execute();



                    //Mark the password reset request as used
                    $stmt = $this->pdo->prepare('
                        UPDATE `password_reset_requests`
                        SET `used` = 1 
                        WHERE `password_reset_request_id` = :password_reset_request_id');

                    $stmt->bindValue(':password_reset_request_id', $passwordResetRequestID, \PDO::PARAM_INT);

                    $stmt->execute();


                    $emailDB = \EmailDatabaseFactory::create();

                    $emailToSend = $emailDB->createEmail();

                    $emailToSend->setEmailType('Administrator Password Changed');
                    $emailToSend->setSubject('VegTrug Password Changed.');

                    $emailToSend->addTag('FirstName', $firstName);

                    $emailToSend->addRecipient($email, 'to');
                    $emailToSend->addRecipient('andy.mayes@revive.digital', 'bcc');

                    $emailToSend = $emailDB->addEmail($emailToSend);


                    unset($_SESSION['PasswordResetTimeout']);

                    return true;
                }
            }

            return false;
        }

        //########## END LOGIN FUNCTIONS ##########//

        public function getAdministrator(string $administratorCode, int $administratorID = -1) : ?\Entities\Administrator
        {
            if(strlen($administratorCode) > 0)
            {
                $key = 'AdministratorCode_' . $administratorCode;
            }
            else
            {
                $key = 'AdministratorID_' . $administratorID;
            }

            if(!array_key_exists($key, $this->administrators))
            {
                $cachedValue = \RedisCacher::getCache($key);

                if(is_null($cachedValue))
                {
                    $this->administrators[$key] = null;

                    $stmt = $this->pdo->prepare('
                        SELECT `administrator_id`, `administrator_code`, `email`, `first_name`, `last_name`, `password`, `enabled`, `date_time_created`, `date_time_last_logged_in`, `super_administrator`
                        FROM `administrators`
                        WHERE `live` = 1'
                        . (strlen($administratorCode) > 0 ? ' AND `administrator_code` = :administrator_code' : ' AND `administrator_id` = :administrator_id'));

                    if(strlen($administratorCode) > 0)
                    {   
                        $stmt->bindValue(':administrator_code', $administratorCode, \PDO::PARAM_STR);
                    }
                    else
                    {
                        $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);
                    }

                    $stmt->execute();

                    $administrator = null;

                    if($row = $stmt->fetch())
                    {
                        $administrator = new \Entities\Administrator();

                        $administrator->setAdministratorID((int)$row['administrator_id']);
                        $administrator->setAdministratorCode((string)$row['administrator_code']);
                        $administrator->setEmail((string)$row['email']);
                        $administrator->setFirstName((string)$row['first_name']);
                        $administrator->setLastName((string)$row['last_name']);
                        $administrator->setActivated(!is_null($row['password']));
                        $administrator->setIsEnabled((bool)$row['enabled']);
                        $administrator->setSuperAdministrator((bool)$row['super_administrator']);
                        $administrator->setDateTimeCreated(datetimeFromDB($row['date_time_created']));

                        if(is_null($row['date_time_last_logged_in']))
                        {
                            $administrator->setDateTimeLastLoggedIn(null);
                        }
                        else
                        {
                            $administrator->setDateTimeLastLoggedIn(datetimeFromDB($row['date_time_last_logged_in']));
                        }


                        $this->administrators[$key] = $administrator;

                        \RedisCacher::setCache($key, json_encode($administrator), ADMINISTRATOR_CACHE_SECONDS, 'Administrators');

                        //Cache under the other key as well
                        if(strlen($administratorCode) > 0)
                        {
                            $key = 'AdministratorID_' . $administrator->getAdministratorID();  
                        }
                        else
                        {
                            $key = 'AdministratorCode_' . $administrator->getAdministratorCode();
                        }

                        $this->administrators[$key] = $administrator;

                        \RedisCacher::setCache($key, json_encode($administrator), ADMINISTRATOR_CACHE_SECONDS, 'Administrators');
                    }
                }
                else
                {
                    $administrator = new Entities\Administrator();
                    $administrator->jsonDeserialize($cachedValue);

                    $this->administrators[$key] = $administrator;
                }
            }

            return $this->administrators[$key];
        }


        public function getAdministratorCount(string $nameSearch = '', string $emailSearch = '', string $status = '') : int
        {
            $key = 'AdministratorCount_' . $nameSearch . '_' . $emailSearch . '_' . $status;

            if(!array_key_exists($key, $this->administratorCount))
            {
                $cachedValue = \RedisCacher::getCache($key);

                if(is_null($cachedValue))
                {
                    $this->administratorCount[$key] = 0;

                    $stmt = $this->pdo->prepare('
                        SELECT COUNT(1) AS `administrator_count`
                        FROM `administrators`
                        WHERE `administrator_id` > 0 AND `live` = 1' 
                        . ($status === 'enabled' ? ' AND `enabled` = 1 AND `password` IS NOT NULL' : ($status === 'disabled' ? ' AND `enabled` = 0' : ($status === 'not-activated' ? ' AND `enabled` = 1 AND `password` IS NULL' : '')))
                        . (strlen($nameSearch) > 0 ? ' AND CONCAT(`first_name`, \' \', `last_name`) LIKE CONCAT(\'%\', :administrator_name, \'%\') ' : '')
                        . (strlen($emailSearch)    > 0 ? ' AND `email` LIKE CONCAT(\'%\', :email, \'%\') ' : ''));


                    if(strlen($nameSearch) > 0)
                    {
                        $stmt->bindValue(':administrator_name', $nameSearch, \PDO::PARAM_STR);
                    }

                    if(strlen($emailSearch) > 0)
                    {
                        $stmt->bindValue(':email', $emailSearch, \PDO::PARAM_STR);
                    }

                    $stmt->execute();

                    if($row = $stmt->fetch())
                    {
                        $this->administratorCount[$key] = (int)$row['administrator_count'];
                    }

                    \RedisCacher::setCache($key, (string)$this->administratorCount[$key], ADMINISTRATOR_CACHE_SECONDS, 'Administrators');
                }
                else
                {
                    $this->administratorCount[$key] = (int)$cachedValue;
                }
            }

            return $this->administratorCount[$key];
        }


        public function getAdminAdministratorList(int $start, int $length, array $sortOrder = [], string $nameSearch = '', string $emailSearch = '', string $status = '') : array
        {
            $key = 'AdminAdministratorList_' . $start . '_' . $length . '_' . json_encode($sortOrder) . '_' . $nameSearch . '_' . $emailSearch . '_' . $status;

            if(!array_key_exists($key, $this->administratorResults))
            {
                $cachedValue = \RedisCacher::getCache($key);

                if(is_null($cachedValue))
                {
                    
                    $unfilteredCount = $this->getAdministratorCount();
                    $filteredCount   = $this->getAdministratorCount($nameSearch, $emailSearch, $status);

                    $orderString = '';

                    for($i = 0; $i < count($sortOrder); $i++)
                    {
                        $orderPart = $this->getOrderStringPart($sortOrder[$i]);
                        
                        if(strlen($orderString) > 0 && strlen($orderPart) > 0)
                        {
                            $orderString .= ', ';
                        }

                        $orderString .= $orderPart;
                    }

                    if(strlen($orderString) === 0)
                    {
                        $orderString = '`first_name` ASC, `last_name` ASC';
                    }

                    $stmt = $this->pdo->prepare('
                        SELECT `administrator_id`, `administrator_code`, `email`, CONCAT(`first_name`, \' \', `last_name`) AS `administrator_name`, `password`, `enabled`, `date_time_created`, `super_administrator`
                        FROM `administrators`
                        WHERE `administrator_id` > 0 AND `live` = 1' 
                        . ($status === 'enabled' ? ' AND `enabled` = 1 AND `password` IS NOT NULL' : ($status === 'disabled' ? ' AND `enabled` = 0' : ($status === 'not-activated' ? ' AND `enabled` = 1 AND `password` IS NULL' : '')))
                        . (strlen($nameSearch) > 0 ? ' AND CONCAT(`first_name`, \' \', `last_name`) LIKE CONCAT(\'%\', :administrator_name, \'%\') ' : '')
                        . (strlen($emailSearch)    > 0 ? ' AND `email` LIKE CONCAT(\'%\', :email, \'%\') ' : '') . '
                        ORDER BY ' . $orderString . '
                        LIMIT :offset, :length');

                    $stmt->bindValue(':offset', $start,  \PDO::PARAM_INT);
                    $stmt->bindValue(':length', $length, \PDO::PARAM_INT);

                    if(strlen($nameSearch) > 0)
                    {
                        $stmt->bindValue(':administrator_name', $nameSearch, \PDO::PARAM_STR);
                    }
                    
                    if(strlen($emailSearch) > 0)
                    {
                        $stmt->bindValue(':email', $emailSearch, \PDO::PARAM_STR);
                    }

                    $stmt->execute();

                    $results = [ 
                        'recordsTotal' => $unfilteredCount,
                        'recordsFiltered' => $filteredCount,
                        'data' => [] 
                    ];

                    $ids = [];

                    while($row = $stmt->fetch())
                    {
                        $ids[] = (int)$row['administrator_id'];

                        $results['data'][] = [ 
                            'name'              => (string)$row['administrator_name'], 
                            'id'                => (int)$row['administrator_id'], 
                            'code'              => (string)$row['administrator_code'], 
                            'email'             => (string)$row['email'], 
                            'enabled'           => (bool)$row['enabled'], 
                            'activated'         => !is_null($row['password']),
                            'date_added'        => datetimeFromDB($row['date_time_created'])->format('d/m/Y g:ia'),
                            'superAdministrator'=> (bool)$row['super_administrator']
                        ];
                    }

                    $this->administratorResults[$key] = $results;

                    \RedisCacher::setCache($key, json_encode($this->administratorResults[$key]), ADMINISTRATOR_CACHE_SECONDS, 'Administrators');
                }
                else
                {
                    $json = json_decode($cachedValue, true);

                    $this->administratorResults[$key] = $json;
                }
            }

            return $this->administratorResults[$key];
        }


        private function getOrderStringPart(array $orderPart) : string
        {
            $orderString = '';

            if(count($orderPart) === 2)
            {
                switch($orderPart[0])
                {
                    case 'administrator_name':
                        $orderString .= '`first_name`';

                        if(strlen($orderString) > 0)
                        {
                            if($orderPart[1] === 'asc')
                            {
                                $orderString .= ' ASC';
                            }
                            else
                            {
                                $orderString .= ' DESC';
                            }
                        }

                        $orderString .= ', `last_name`';

                        break;
                    case 'email':
                        $orderString .= '`email`';

                        break;
                    case 'date_added':
                        $orderString .= '`date_time_created`';

                        break;
                    case 'status':
                        $orderString .= '`enabled`';

                        if(strlen($orderString) > 0)
                        {
                            if($orderPart[1] === 'asc')
                            {
                                $orderString .= ' ASC';
                            }
                            else
                            {
                                $orderString .= ' DESC';
                            }
                        }

                        $orderString .= ', CASE WHEN `password` IS NULL THEN 1 ELSE 0 END';

                        break;
                }

                if(strlen($orderString) > 0)
                {
                    if($orderPart[1] === 'asc')
                    {
                        $orderString .= ' ASC';
                    }
                    else
                    {
                        $orderString .= ' DESC';
                    }
                }
            }

            return $orderString;
        }


        public function saveAdministrator(\Entities\Administrator $administrator) : void
        {
            $stmt = $this->pdo->prepare('
                UPDATE `administrators` SET
                `first_name` = :first_name,
                `last_name` = :last_name,
                `email` = :email,
                `enabled` = :enabled
                WHERE `administrator_id` = :administrator_id');

            $stmt->bindValue(':first_name',         $administrator->getFirstName(),          \PDO::PARAM_STR);
            $stmt->bindValue(':last_name',          $administrator->getLastName(),           \PDO::PARAM_STR);
            $stmt->bindValue(':email',              $administrator->getEmail(),              \PDO::PARAM_STR);
            $stmt->bindValue(':enabled',            $administrator->getIsEnabled(),          \PDO::PARAM_INT);
            $stmt->bindValue(':administrator_id',   $administrator->getAdministratorID(),    \PDO::PARAM_INT);

            $stmt->execute();
        }


        //Add the administrator to the database, then add an invitation link to be send out in order for a password to be chosen by the administrator
        public function addAdministrator(\Entities\Administrator $administrator, bool $sendInvitationEmail = true) : \Entities\Administrator
        {
            global $siteURL;

            $stmt = $this->pdo->prepare('
                INSERT INTO `administrators`(`email`, `first_name`, `last_name`, `date_time_created`, `enabled`, `live`, `super_administrator`)
                SELECT `email`, `first_name`, `last_name`, NOW(), 1, 1, 0
                FROM (SELECT :email AS `email`, :first_name AS `first_name`, :last_name AS `last_name`) AS tbl1
                WHERE NOT EXISTS(SELECT 1 FROM `administrators` WHERE `email` = :email AND `live` = 1)');

            $stmt->bindValue(':email',      $administrator->getEmail(),      \PDO::PARAM_STR);
            $stmt->bindValue(':first_name', $administrator->getFirstName(),  \PDO::PARAM_STR);
            $stmt->bindValue(':last_name',  $administrator->getLastName(),   \PDO::PARAM_STR);

            $stmt->execute();

            $administrator->setAdministratorID((int)$this->pdo->lastInsertId());

            if($administrator->getAdministratorID() <= 0)
            {
                return $administrator;
            }
            

            $stmt = $this->pdo->prepare('
                SELECT `administrator_code`
                FROM `administrators`
                WHERE `administrator_id` = :administrator_id');

            $stmt->bindValue(':administrator_id', $administrator->getAdministratorID(), \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $administrator->setAdministratorCode((string)$row['administrator_code']);

                /* Set permissions*/

                $this->getPermissionsForAdministrator($administrator->getAdministratorID()); 
                $this->getAdministratorStorefrontPermissions($administrator->getAdministratorID());
                $this->getAdministratorLanguagePermissions($administrator->getAdministratorID());
                $this->getAdministratorDistributionPermissions($administrator->getAdministratorID());


            }

            //Queue the email
            if($sendInvitationEmail)
            {
                $this->sendActivationEmail($administrator);
            }

            return $administrator;
        }


        public function sendActivationEmail(\Entities\Administrator $administrator) : void
        {
            global $siteURL;

            $administratorInvitationLinkID   = -1;
            $administratorInvitationLinkCode = '';

            $stmt = $this->pdo->prepare('
                SELECT `administrator_invitation_link_id`, `administrator_invitation_link_code`
                FROM `administrator_invitation_links`
                WHERE `administrator_id` = :administrator_id AND `used` = 0 AND `invalid` = 0
                LIMIT 1');

            $stmt->bindValue(':administrator_id', $administrator->getAdministratorID(), \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $administratorInvitationLinkID   = (int)$row['administrator_invitation_link_id'];
                $administratorInvitationLinkCode = (string)$row['administrator_invitation_link_code'];
            }
            else
            {
                //Add the invitation link
                $stmt = $this->pdo->prepare('
                    INSERT INTO `administrator_invitation_links`(`administrator_id`, `used`, `invalid`, `date_time_created`)
                    VALUES(:administrator_id, 0, 0, NOW())');

                $stmt->bindValue(':administrator_id', $administrator->getAdministratorID(), \PDO::PARAM_INT);

                $stmt->execute();

                $administratorInvitationLinkID   = $this->pdo->lastInsertId();
                $administratorInvitationLinkCode = '';

                $stmt = $this->pdo->prepare('SELECT `administrator_invitation_link_code` FROM `administrator_invitation_links` WHERE `administrator_invitation_link_id` = :administrator_invitation_link_id');

                $stmt->bindValue(':administrator_invitation_link_id', $administratorInvitationLinkID, \PDO::PARAM_INT);

                $stmt->execute();

                if($row = $stmt->fetch())
                {
                    $administratorInvitationLinkCode = (string)$row['administrator_invitation_link_code'];
                }
            }

            
            $emailDB = \EmailDatabaseFactory::create();

            $emailToSend = $emailDB->createEmail();

            $emailToSend->setEmailType('Administrator Invitation');
            $emailToSend->setSubject('Your VegTrug account has been created.');

            $emailToSend->addTag('FirstName', $administrator->getFirstName());
            $emailToSend->addTag('AcceptInvitationLink', $siteURL . 'admin/accept-invitation.html?invitation=' . $administratorInvitationLinkCode);

            $emailToSend->addRecipient($administrator->getEmail(), 'to');
            $emailToSend->addRecipient('andy.mayes@revive.digital', 'bcc');

            $emailToSend = $emailDB->addEmail($emailToSend);
        } 


        public function deleteAdministrator(\Entities\Administrator $administrator) : void
        {
            $stmt = $this->pdo->prepare('
                UPDATE `administrators` SET
                `live` = 0
                WHERE `administrator_id` = :administrator_id AND `live` = 1');

            $stmt->bindValue(':administrator_id',    $administrator->getAdministratorID(),    \PDO::PARAM_INT);

            $stmt->execute();
        }


        public function getAdministratorByInvitationCode(string $invitationCode) : ?\Entities\Administrator
        {
            $administrator   = null;
            $administratorID = -1;

            $stmt = $this->pdo->prepare('
                SELECT `administrator_id`
                FROM `administrator_invitation_links`
                WHERE `administrator_invitation_link_code` = :administrator_invitation_link_code AND `used` = 0 AND `invalid` = 0');

            $stmt->bindValue(':administrator_invitation_link_code', $invitationCode, \PDO::PARAM_STR);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                $administratorID = (int)$row['administrator_id'];
            }


            if($administratorID > 0)
            {
                $administrator = $this->getAdministrator('', $administratorID);
            }
            
            return $administrator;
        }


        public function acceptInvitation(string $invitationCode, string $password) : bool
        {
            $administrator = $this->getAdministratorByInvitationCode($invitationCode);

            if(is_null($administrator))
            {
                return false;
            }

            $stmt = $this->pdo->prepare('
                UPDATE `administrators` SET 
                `password` = :password
                WHERE `administrator_id` = :administrator_id');

            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), \PDO::PARAM_STR);
            $stmt->bindValue(':administrator_id',  $administrator->getAdministratorID(),                         \PDO::PARAM_INT);

            $stmt->execute();

            $stmt = $this->pdo->prepare('UPDATE `administrator_invitation_links` SET `used` = 1, `date_time_used` = NOW() WHERE `administrator_invitation_link_code` = :administrator_invitation_link_code');

            $stmt->bindValue(':administrator_invitation_link_code', $invitationCode, \PDO::PARAM_STR);

            $stmt->execute();

            return true;
        }


        public function getPermissionsForAdministrator(int $administratorID) : array
        {
            $permissions        = [];
            $permissionIDsToAdd = [];

            $stmt = $this->pdo->prepare('
                SELECT p.`permission_id`, `permission_code`, `permission_name`, up.`administrator_id`, `first_name`, `last_name`, `create_access`, `read_access`, `update_access`, `delete_access`, `date_time_last_updated`, `page_layout_access`
                FROM `permissions` AS p
                LEFT JOIN `administrator_permissions` AS up ON p.`permission_id` = up.`permission_id` AND up.`administrator_id` = :administrator_id
                LEFT JOIN `administrators` AS u ON up.`actioning_administrator_id` = u.`administrator_id`
                ORDER BY `permission_name`');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                $permissions[] = [ 
                    'id'                => (int)$row['permission_id'], 
                    'code'              => (string)$row['permission_code'], 
                    'name'              => (string)$row['permission_name'], 
                    'administrator'     => (is_null($row['administrator_id']) ? 'Unknown' : trim((string)$row['first_name'] . ' ' . (string)$row['last_name'])), 
                    'create'            => (is_null($row['administrator_id']) ? false : (bool)$row['create_access']), 
                    'read'              => (is_null($row['administrator_id']) ? false : (bool)$row['read_access']), 
                    'update'            => (is_null($row['administrator_id']) ? false : (bool)$row['update_access']), 
                    'delete'            => (is_null($row['administrator_id']) ? false : (bool)$row['delete_access']), 
                    'lastUpdated'       => (is_null($row['administrator_id']) ? null  : datetimeFromDB($row['date_time_last_updated'])),
                    'page_layout'       => (is_null($row['administrator_id']) ? false  : (bool)$row['page_layout_access']) ];
                    

                if(is_null($row['administrator_id']))
                {
                    $permissionIDsToAdd[] = (int)$row['permission_id'];
                }
            }


            //Grant the missing permissions by default 
            if(count($permissionIDsToAdd) > 0)
            {
                $stmt = $this->pdo->prepare('
                    INSERT INTO `administrator_permissions`(`administrator_id`, `permission_id`, `actioning_administrator_id`, `create_access`, `read_access`, `update_access`, `delete_access`, `page_layout_access`, `date_time_last_updated`)
                    SELECT :administrator_id, `permission_id`, 1, 1, 1, 1, 1, 1, NOW()
                    FROM `permissions` AS p
                    WHERE `permission_id` IN (' . implode(',', $permissionIDsToAdd) . ') AND NOT EXISTS(SELECT 1 FROM `administrator_permissions` AS up WHERE `administrator_id` = :administrator_id AND p.`permission_id` = up.`permission_id`)');

                $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

                $stmt->execute();

                //Log that the system added the permissions
                $stmt = $this->pdo->prepare('
                    INSERT INTO `permission_log`(`actioning_administrator_id`, `target_administrator_id`, `permission_id`, `log_text`, `date_time_created`)
                    SELECT 0, :administrator_id, `permission_id`, :log_text, NOW()
                    FROM `permissions` 
                    WHERE `permission_id` IN (' . implode(',', $permissionIDsToAdd) . ')');

                $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':log_text', '{ACTIONINGADMIN} granted {TARGETADMIN} \'{PERMISSIONNAME}\' view, create, edit, delete and page layout rights.', \PDO::PARAM_STR);

                $stmt->execute();


                //Fetch the permissions again to update the permissions object
                $stmt = $this->pdo->prepare('
                    SELECT `permission_id`, `first_name`, `last_name`, `create_access`, `read_access`, `update_access`, `delete_access`, `page_layout_access`, `date_time_last_updated`
                    FROM `administrator_permissions` AS up
                    INNER JOIN `administrators` AS u ON up.`actioning_administrator_id` = u.`administrator_id`
                    WHERE up.`administrator_id`= :administrator_id');

                $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

                $stmt->execute();

                while($row = $stmt->fetch())
                {
                    for($i = 0; $i < count($permissions); $i++)
                    {
                        if($permissions[$i]['id'] === (int)$row['permission_id'])
                        {
                            $permissions[$i]['create'] 	= (bool)$row['create_access'];
                            $permissions[$i]['read'] 	= (bool)$row['read_access'];
                            $permissions[$i]['update'] 	= (bool)$row['update_access'];
                            $permissions[$i]['delete'] 	= (bool)$row['delete_access'];
                            $permissions[$i]['page_layout'] = (bool)$row['page_layout_access'];
                            $permissions[$i]['lastUpdated'] = datetimeFromDB($row['date_time_last_updated']);
                            $permissions[$i]['administrator']  		= trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);

                        }
                    }
                }
            }

            return $permissions;
        }

        public function getAdministratorDistributionPermissions(int $administratorID) : array
        {
            $permissions = [];
            $dist = [];

            $stmt = $this->pdo->prepare('
                SELECT d.`distribution_centre_id`, `distribution_centre_code`, `distribution_centre_name`, `administrator_id` 
                FROM `distribution_centres` AS d
                LEFT JOIN (SELECT `administrator_id`, `distribution_centre_id`
                        FROM `administrator_distribution_centre_permissions`
                        WHERE `administrator_id` = :administrator_id) AS ad ON ad.`distribution_centre_id` = d.`distribution_centre_id`
                WHERE d.`live` = 1');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                $permissions [] = 
                [
                    'id'                        => (int)$row['distribution_centre_id'],
                    'distribution_centre_name'  => (string)$row['distribution_centre_name'],
                    'distribution_centre_code'  => (string)$row['distribution_centre_code'],
                    'permission'                => (is_null($row['administrator_id']) ? false : true)
                ];

                $dist [] = 
                [
                    'id' => (int)$row['distribution_centre_id'],
                    'name' => (string)$row['distribution_centre_name'],
                ];
            }

            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM `distribution_centre_permission_log`
                WHERE `target_administrator_id` = :administrator_id');
            
            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            if(!$stmt->fetch())
            {
                $distText = '';
            
                for($i = 0; $i < count($dist); $i++)
                {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO `administrator_distribution_centre_permissions` (`administrator_id`, `distribution_centre_id`, `date_time_created`, `actioning_administrator_id`)
                        VALUES (:administrator_id, :distribution_centre_id, NOW(), 0)');

                    $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);
                    $stmt->bindValue(':distribution_centre_id', $dist[$i]['id'], \PDO::PARAM_INT);
                    $stmt->execute();

                    if($i === count($dist) - 2)
                    {
                        $distText .= $dist[$i]['name'] . ' and ';
                    }
                    else
                    {
                        $distText  .= $dist[$i]['name'] . ', ';
                    }
                }

                $distText = substr($distText, 0, -2);

                $stmt = $this->pdo->prepare('
                    INSERT INTO `distribution_centre_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `distribution_centre_id`, `log_text`, `date_time_created`)
                    VALUES(0, :target_administrator_id, :distribution_centre_id, :log_text, NOW())');
    
                $stmt->bindValue(':target_administrator_id', $administratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':distribution_centre_id', 1 , \PDO::PARAM_INT);
                $stmt->bindValue(':log_text', '{ACTIONINGADMIN} granted {TARGETADMIN} ' . $distText . ' distribution centre access rights.', \PDO::PARAM_STR);
    
                $stmt->execute();

            }

            $stmt = $this->pdo->prepare('
                SELECT d.`distribution_centre_id`, `distribution_centre_code`, `distribution_centre_name`, `administrator_id` 
                FROM `distribution_centres` AS d
                LEFT JOIN (SELECT `administrator_id`, `distribution_centre_id`
                        FROM `administrator_distribution_centre_permissions`
                        WHERE `administrator_id` = :administrator_id) AS ad ON ad.`distribution_centre_id` = d.`distribution_centre_id`
                WHERE d.`live` = 1');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                for($i = 0; $i < count($permissions); $i++)
                {
                    if($permissions[$i]['id'] === (int)$row['distribution_centre_id'])
                    {
                        $permissions[$i]['distribution_centre_name'] 	= (string)$row['distribution_centre_name'];
                        $permissions[$i]['distribution_centre_code'] 	= (string)$row['distribution_centre_code'];
                        $permissions[$i]['permission']      	        = (is_null($row['administrator_id']) ? false : true);
                    }
                }

            }


            return $permissions;
        }



        public function getAdministratorStorefrontPermissions(int $administratorID) : array
        {
            $permissions = [];
            $storefronts = [];

            $stmt = $this->pdo->prepare('
                SELECT s.`storefront_id`, `storefront_code`, `storefront_name`, `administrator_id` 
                FROM `storefronts` AS s
                LEFT JOIN (SELECT `administrator_id`, `storefront_id`
                        FROM `administrator_storefront_permissions`
                        WHERE `administrator_id` = :administrator_id) AS u ON u.`storefront_id` = s.`storefront_id`
                WHERE s.`live` = 1');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                $permissions [] = 
                [
                    'id'                        => (int)$row['storefront_id'],
                    'storefront_name'           => (string)$row['storefront_name'],
                    'storefront_code'           => (string)$row['storefront_code'],
                    'permission'                => (is_null($row['administrator_id']) ? false : true)
                ];

                $storefronts [] = 
                [
                    'id' => (int)$row['storefront_id'],
                    'name' => (string)$row['storefront_name'],
                ];
            }


            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM `storefront_permission_log`
                WHERE `target_administrator_id` = :administrator_id');
            
            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            if(!$stmt->fetch())
            {
                $storefrontText = '';
            
                for($i = 0; $i < count($storefronts); $i++)
                {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO `administrator_storefront_permissions` (`administrator_id`, `storefront_id`, `date_time_created`, `actioning_administrator_id`)
                        VALUES (:administrator_id, :storefront_id, NOW(), 0)');

                    $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);
                    $stmt->bindValue(':storefront_id', $storefronts[$i]['id'], \PDO::PARAM_INT);
                    $stmt->execute();

                    if($i === count($storefronts) - 2)
                    {
                        $storefrontText .= $storefronts[$i]['name'] . ' and ';
                    }
                    else
                    {
                        $storefrontText  .= $storefronts[$i]['name'] . ', ';
                    }
                }

                $storefrontText = substr($storefrontText, 0, -2);

                $stmt = $this->pdo->prepare('
                    INSERT INTO `storefront_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `storefront_id`, `log_text`, `date_time_created`)
                    VALUES(0, :target_administrator_id, :storefront_id, :log_text, NOW())');
    
                $stmt->bindValue(':target_administrator_id', $administratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':storefront_id', 1 , \PDO::PARAM_INT);
                $stmt->bindValue(':log_text', '{ACTIONINGADMIN} granted {TARGETADMIN} ' . $storefrontText . ' storefront access rights.', \PDO::PARAM_STR);
    
                $stmt->execute();

            }
            
            $stmt = $this->pdo->prepare('
                SELECT s.`storefront_id`, `storefront_code`, `storefront_name`, `administrator_id` 
                FROM `storefronts` AS s
                LEFT JOIN (SELECT `administrator_id`, `storefront_id`
                        FROM `administrator_storefront_permissions`
                        WHERE `administrator_id` = :administrator_id) AS u ON u.`storefront_id` = s.`storefront_id`
                WHERE s.`live` = 1');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                for($i = 0; $i < count($permissions); $i++)
                {
                    if($permissions[$i]['id'] === (int)$row['storefront_id'])
                    {
                        $permissions[$i]['storefront_name'] 	= (string)$row['storefront_name'];
                        $permissions[$i]['storefront_code'] 	= (string)$row['storefront_code'];
                        $permissions[$i]['permission']      	= (is_null($row['administrator_id']) ? false : true);
                    }
                }
            }

            return $permissions;
        }


        public function getAdministratorLanguagePermissions(int $administratorID) : array
        {
            $permissions = [];
            $languages = [];

            $stmt = $this->pdo->prepare('
                SELECT l.`language_id`, `language_code`, `language_name`, `administrator_id`, `language_iso_code` 
                FROM `languages` AS l
                LEFT JOIN (SELECT `administrator_id`, `language_id`
                    FROM `administrator_language_permissions`
                    WHERE `administrator_id` = :administrator_id) AS u ON u.`language_id` = l.`language_id`
                WHERE l.`live` = 1 ORDER BY l.`is_default` DESC, `language_name` ASC');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                $permissions [] = 
                [
                    'id'                        => (int)$row['language_id'],
                    'language_name'             => (string)$row['language_name'],
                    'language_code'             => (string)$row['language_code'],
                    'language_iso_code'         => (string)$row['language_iso_code'],
                    'permission'                => (is_null($row['administrator_id']) ? false : true)
                ];

                $languages [] = 
                [
                    'id' => (int)$row['language_id'],
                    'name' => (string)$row['language_name'],
                ];
            }

            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM `language_permission_log`
                WHERE `target_administrator_id` = :administrator_id');
            
            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            if(!$stmt->fetch())
            {
                $languageText = '';
            
                for($i = 0; $i < count($languages); $i++)
                {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO `administrator_language_permissions` (`administrator_id`, `language_id`, `date_time_created`, `actioning_administrator_id`)
                        VALUES (:administrator_id, :language_id, NOW(), 0)');
                    
                    $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);
                    $stmt->bindValue(':language_id', $languages[$i]['id'], \PDO::PARAM_INT);
                    $stmt->execute();

                    if($i === count($languages) - 2)
                    {
                        $languageText .= $languages[$i]['name'] . ' and ';
                    }
                    else
                    {
                        $languageText  .= $languages[$i]['name'] . ', ';
                    }
                }

                $languageText = substr($languageText, 0, -2);

                $stmt = $this->pdo->prepare('
                    INSERT INTO `language_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `language_id`, `log_text`, `date_time_created`)
                    VALUES(0, :target_administrator_id, :language_id, :log_text, NOW())');

                $stmt->bindValue(':target_administrator_id', $administratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':language_id', 1 , \PDO::PARAM_INT);
                $stmt->bindValue(':log_text', '{ACTIONINGADMIN} granted {TARGETADMIN} ' . $languageText . ' language access rights.', \PDO::PARAM_STR);

                $stmt->execute();

            }
            
            $stmt = $this->pdo->prepare('
                SELECT l.`language_id`, `language_code`, `language_name`, `administrator_id`, `language_iso_code` 
                FROM `languages` AS l
                LEFT JOIN (SELECT `administrator_id`, `language_id`
                    FROM `administrator_language_permissions`
                    WHERE `administrator_id` = :administrator_id) AS u ON u.`language_id` = l.`language_id`
                WHERE l.`live` = 1 ORDER BY l.`is_default` DESC, `language_name` ASC');

            $stmt->bindValue(':administrator_id', $administratorID, \PDO::PARAM_INT);

            $stmt->execute();

            while($row = $stmt->fetch())
            {
                for($i = 0; $i < count($permissions); $i++)
                {
                    if($permissions[$i]['id'] === (int)$row['language_id'])
                    {
                        $permissions[$i]['language_name'] 	= (string)$row['language_name'];
                        $permissions[$i]['language_code'] 	= (string)$row['language_code'];
                        $permissions[$i]['permission']      = (is_null($row['administrator_id']) ? false : true);

                    }
                }
            }

            return $permissions;
        }


        public function updatePermission(int $targetAdministratorID, int $actioningAdministratorID, int $permissionID, string $access, bool $grant) : string
        {

            if($access === 'c' || $access === 'r' || $access === 'u' || $access === 'd' || $access === 'p')
            {
                $stmt = $this->pdo->prepare('
                    UPDATE `administrator_permissions` SET 
                    `actioning_administrator_id` = :actioning_administrator_id, 
                    `' . ($access === 'c' ? 'create' : ($access === 'p' ? 'page_layout' : ($access === 'r' ? 'read' : ($access === 'u' ? 'update' : 'delete')))) . '_access` = :access,
                    `date_time_last_updated` = NOW()
                    WHERE `administrator_id` = :administrator_id AND `permission_id` = :permission_id');

                $stmt->bindValue(':access', ($grant ? 1 : 0), \PDO::PARAM_INT);
                $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':permission_id', $permissionID, \PDO::PARAM_INT);

                $stmt->execute();

                $stmt = $this->pdo->prepare('
                    INSERT INTO `permission_log`(`actioning_administrator_id`, `target_administrator_id`, `permission_id`, `log_text`, `date_time_created`)
                    VALUES(:actioning_administrator_id, :administrator_id, :permission_id, :log_text, NOW())');

                $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
                $stmt->bindValue(':permission_id', $permissionID, \PDO::PARAM_INT);
                $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} \'{PERMISSIONNAME}\' ' . ($access === 'c' ? 'create' : ($access === 'p' ? 'page layout' : ($access === 'r' ? 'view' : ($access === 'u' ? 'edit' : 'delete')))) . ' rights.', \PDO::PARAM_STR);

                $stmt->execute();

                $permissionLogID = (int)$this->pdo->lastInsertId();

                $stmt = $this->pdo->prepare('
                    SELECT REPLACE(REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))), \'{PERMISSIONNAME}\', p.`permission_name`) AS `log_text`, l.`date_time_created`
                    FROM `permission_log` AS l
                    INNER JOIN `permissions` AS p ON l.`permission_id` = p.`permission_id`
                    INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                    INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                    WHERE `permission_log_id` = :permission_log_id');

                $stmt->bindValue(':permission_log_id', $permissionLogID, \PDO::PARAM_INT);

                $stmt->execute();

                if($row = $stmt->fetch())
                {
                    return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
                }
            }

            return '';
        }

        public function updateStorefrontPermission(int $targetAdministratorID, int $actioningAdministratorID, int $storefrontID, string $storefrontName, bool $grant) : string
        {
            if($grant)
            {
                $stmt = $this->pdo->prepare('
                    INSERT INTO `administrator_storefront_permissions` (`administrator_id`, `storefront_id`, `date_time_created`, `actioning_administrator_id`)
                    VALUES (:administrator_id, :storefront_id, NOW(), :actioning_administrator_id)');

                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->bindValue(':storefront_id', $storefrontID, \PDO::PARAM_INT);

                $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_storefront_permissions`
                    WHERE `administrator_id` = :administrator_id AND `storefront_id` = :storefront_id');
                
                    $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                    $stmt->bindValue(':storefront_id', $storefrontID, \PDO::PARAM_INT);
    
                    $stmt->execute();
            }

            $stmt = $this->pdo->prepare('
                    INSERT INTO `storefront_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `storefront_id`, `log_text`, `date_time_created`)
                    VALUES(:actioning_administrator_id, :target_administrator_id, :storefront_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':storefront_id', $storefrontID, \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $storefrontName . ' storefront access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $storefrontPerminssionID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                    SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                    FROM `storefront_permission_log` AS l
                    INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                    INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                    WHERE `storefront_permission_log_id` = :storefront_permission_log_id');

            $stmt->bindValue(':storefront_permission_log_id', $storefrontPerminssionID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

            return '';

        }


        public function updateDistributionCentrePermission(int $targetAdministratorID, int $actioningAdministratorID, int $distributionCentreID, string $distributionCentreName, bool $grant) : string
        {
            if($grant)
            {
                $stmt = $this->pdo->prepare('
                    INSERT INTO `administrator_distribution_centre_permissions` (`administrator_id`, `distribution_centre_id`, `date_time_created`, `actioning_administrator_id`)
                    VALUES (:administrator_id, :distribution_centre_id, NOW(), :actioning_administrator_id)');

                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->bindValue(':distribution_centre_id', $distributionCentreID, \PDO::PARAM_INT);

                $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_distribution_centre_permissions`
                    WHERE `administrator_id` = :administrator_id AND `distribution_centre_id` = :distribution_centre_id');
                
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->bindValue(':distribution_centre_id', $distributionCentreID, \PDO::PARAM_INT);

                $stmt->execute();
            }


            $stmt = $this->pdo->prepare('
                    INSERT INTO `distribution_centre_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `distribution_centre_id`, `log_text`, `date_time_created`)
                    VALUES(:actioning_administrator_id, :target_administrator_id, :distribution_centre_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':distribution_centre_id', $distributionCentreID, \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $distributionCentreName . ' distribution centre access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $distributionCentrePermissionID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                FROM `distribution_centre_permission_log` AS l
                INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                WHERE `distribution_centre_permission_log_id` = :distribution_centre_permission_log_id');

            $stmt->bindValue(':distribution_centre_permission_log_id', $distributionCentrePermissionID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

            return '';
        }

        public function updateLanguagePermission(int $targetAdministratorID, int $actioningAdministratorID, int $languageID, string $languageName, bool $grant) : string
        {
            if($grant)
            {
                $stmt = $this->pdo->prepare('
                    INSERT INTO `administrator_language_permissions` (`administrator_id`, `language_id`, `date_time_created`, `actioning_administrator_id`)
                    VALUES (:administrator_id, :language_id, NOW(), :actioning_administrator_id)');

                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->bindValue(':language_id', $languageID, \PDO::PARAM_INT);

                $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_language_permissions`
                    WHERE `administrator_id` = :administrator_id AND `language_id` = :language_id');
                
                    $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                    $stmt->bindValue(':language_id', $languageID, \PDO::PARAM_INT);
    
                    $stmt->execute();
            }

            $stmt = $this->pdo->prepare('
                    INSERT INTO `language_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `language_id`, `log_text`, `date_time_created`)
                    VALUES(:actioning_administrator_id, :target_administrator_id, :language_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':language_id', $languageID, \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $languageName . ' language access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $languagePermissionID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                    SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                    FROM `language_permission_log` AS l
                    INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                    INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                    WHERE `language_permission_log_id` = :language_permission_log_id');

            $stmt->bindValue(':language_permission_log_id', $languagePermissionID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

            return '';

        }



        public function updateBulkPermission(int $targetAdministratorID, int $actioningAdministratorID, int $permissionID, bool $grant) : string
        {
            // var_dump($grant);
            $stmt = $this->pdo->prepare('
                UPDATE `administrator_permissions` SET 
                `actioning_administrator_id` = :actioning_administrator_id, 
                `create_access`           = :access,
                `read_access`             = :access,
                `update_access`           = :access,
                `delete_access`           = :access,
                `page_layout_access`      = :access,
                `date_time_last_updated`  = NOW()
                WHERE `administrator_id`  = :administrator_id AND `permission_id` = :permission_id');

            $stmt->bindValue(':access', ($grant ? 1 : 0), \PDO::PARAM_INT);
            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':permission_id', $permissionID, \PDO::PARAM_INT);

            $stmt->execute();

            $stmt = $this->pdo->prepare('
                INSERT INTO `permission_log`(`actioning_administrator_id`, `target_administrator_id`, `permission_id`, `log_text`, `date_time_created`)
                VALUES(:actioning_administrator_id, :administrator_id, :permission_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':permission_id', $permissionID, \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} \'{PERMISSIONNAME}\' view, create, edit, delete and page layout rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $permissionLogID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                SELECT REPLACE(REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))), \'{PERMISSIONNAME}\', p.`permission_name`) AS `log_text`, l.`date_time_created`
                FROM `permission_log` AS l
                INNER JOIN `permissions` AS p ON l.`permission_id` = p.`permission_id`
                INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                WHERE `permission_log_id` = :permission_log_id');

            $stmt->bindValue(':permission_log_id', $permissionLogID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

        }


        public function updateBulkStorefrontPermission(int $targetAdministratorID, int $actioningAdministratorID, bool $grant) : string
        {
            $storefronts = '';

            $administratorStorefrontPermissions = $this->getAdministratorStorefrontPermissions($targetAdministratorID);

            if($grant)
            {
                for($i = 0; $i < count($administratorStorefrontPermissions); $i++)
                {
                    if($administratorStorefrontPermissions[$i]['permission'] === false)
                    {
                        $stmt = $this->pdo->prepare('
                            INSERT INTO `administrator_storefront_permissions` (`administrator_id`, `storefront_id`, `date_time_created`, `actioning_administrator_id`)
                            VALUES (:administrator_id, :storefront_id, NOW(), :actioning_administrator_id)');
    
                        $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
        
                        $stmt->bindValue(':storefront_id', (int)$administratorStorefrontPermissions[$i]['id'], \PDO::PARAM_INT);
        
                        $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
        
                        $stmt->execute();
                    }
                }

            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_storefront_permissions`
                    WHERE `administrator_id` = :administrator_id');
            
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }

            $count = count($administratorStorefrontPermissions);

            for($i = 0; $i < count($administratorStorefrontPermissions); $i++)
            {
                if($i === $count - 2)
                {
                    $storefronts .= $administratorStorefrontPermissions[$i]['storefront_name'] . ' and ';
                }
                else
                {
                    $storefronts .= $administratorStorefrontPermissions[$i]['storefront_name'] . ', ';
                }
               
            }

            $storefronts = substr($storefronts, 0, -2);


            $stmt = $this->pdo->prepare('
                INSERT INTO `storefront_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `storefront_id`, `log_text`, `date_time_created`)
                VALUES(:actioning_administrator_id, :target_administrator_id, :storefront_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':storefront_id', 1 , \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $storefronts . ' storefront access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $storefrontID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                FROM `storefront_permission_log` AS l
                INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                WHERE `storefront_permission_log_id` = :storefront_permission_log_id');

            $stmt->bindValue(':storefront_permission_log_id', $storefrontID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

        }


        public function updateBulkLanguagePermission(int $targetAdministratorID, int $actioningAdministratorID, bool $grant) : string
        {
            $languages = '';

            $administratorLanguagePermissions = $this->getAdministratorLanguagePermissions($targetAdministratorID);

            if($grant)
            {
                for($i = 0; $i < count($administratorLanguagePermissions); $i++)
                {
                    if($administratorLanguagePermissions[$i]['permission'] === false)
                    {
                        $stmt = $this->pdo->prepare('
                            INSERT INTO `administrator_language_permissions` (`administrator_id`, `language_id`, `date_time_created`, `actioning_administrator_id`)
                            VALUES (:administrator_id, :language_id, NOW(), :actioning_administrator_id)');
    
                        $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
        
                        $stmt->bindValue(':language_id', (int)$administratorLanguagePermissions[$i]['id'], \PDO::PARAM_INT);
        
                        $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
        
                        $stmt->execute();
                    }
                }

            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_language_permissions`
                    WHERE `administrator_id` = :administrator_id');
            
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }

            $count = count($administratorLanguagePermissions);

            for($i = 0; $i < count($administratorLanguagePermissions); $i++)
            {
                if($i === $count - 2)
                {
                    $languages .= $administratorLanguagePermissions[$i]['language_name'] . ' and ';
                }
                else
                {
                    $languages .= $administratorLanguagePermissions[$i]['language_name'] . ', ';
                }
               
            }

            $languages = substr($languages, 0, -2);


            $stmt = $this->pdo->prepare('
                INSERT INTO `language_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `language_id`, `log_text`, `date_time_created`)
                VALUES(:actioning_administrator_id, :target_administrator_id, :language_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':language_id', 1 , \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $languages . ' language access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $languagePermissionID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                FROM `language_permission_log` AS l
                INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                WHERE `language_permission_log_id` = :language_permission_log_id');

            $stmt->bindValue(':language_permission_log_id', $languagePermissionID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

        }

        public function updateBulkDistributionCentrePermission(int $targetAdministratorID, int $actioningAdministratorID, bool $grant) : string
        {
            $distributionCentres = '';

            $administratorDistributionCentrePermissions = $this->getAdministratorDistributionPermissions($targetAdministratorID);

            if($grant)
            {
                for($i = 0; $i < count($administratorDistributionCentrePermissions); $i++)
                {
                    if($administratorDistributionCentrePermissions[$i]['permission'] === false)
                    {
    
                        $stmt = $this->pdo->prepare('
                            INSERT INTO `administrator_distribution_centre_permissions` (`administrator_id`, `distribution_centre_id`, `date_time_created`, `actioning_administrator_id`)
                            VALUES (:administrator_id, :distribution_centre_id, NOW(), :actioning_administrator_id)');

                        $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                        $stmt->bindValue(':distribution_centre_id', (int)$administratorDistributionCentrePermissions[$i]['id'], \PDO::PARAM_INT);

                        $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);

                        $stmt->execute();
                    }
                }

            }
            else
            {
                $stmt = $this->pdo->prepare('
                    DELETE FROM `administrator_distribution_centre_permissions`
                    WHERE `administrator_id` = :administrator_id');
            
                $stmt->bindValue(':administrator_id', $targetAdministratorID, \PDO::PARAM_INT);

                $stmt->execute();
            }

            $count = count($administratorDistributionCentrePermissions);

            for($i = 0; $i < count($administratorDistributionCentrePermissions); $i++)
            {
                if($i === $count - 2)
                {
                    $distributionCentres .= $administratorDistributionCentrePermissions[$i]['distribution_centre_name'] . ' and ';
                }
                else
                {
                    $distributionCentres .= $administratorDistributionCentrePermissions[$i]['distribution_centre_name'] . ', ';
                }
               
            }

            $distributionCentres = substr($distributionCentres, 0, -2);

            $stmt = $this->pdo->prepare('
                INSERT INTO `distribution_centre_permission_log`(`actioning_administrator_id`, `target_administrator_id`, `distribution_centre_id`, `log_text`, `date_time_created`)
                VALUES(:actioning_administrator_id, :target_administrator_id, :distribution_centre_id, :log_text, NOW())');

            $stmt->bindValue(':actioning_administrator_id', $actioningAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':target_administrator_id', $targetAdministratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':distribution_centre_id', 1, \PDO::PARAM_INT);
            $stmt->bindValue(':log_text', '{ACTIONINGADMIN} ' . ($grant ? 'granted' : 'denied') . ' {TARGETADMIN} ' . $distributionCentres . ' distribution centre access rights.', \PDO::PARAM_STR);

            $stmt->execute();

            $distributionCentrePermissionID = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                    SELECT REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))) AS `log_text`, l.`date_time_created`
                    FROM `distribution_centre_permission_log` AS l
                    INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
                    INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
                    WHERE `distribution_centre_permission_log_id` = :distribution_centre_permission_log_id');

            $stmt->bindValue(':distribution_centre_permission_log_id', $distributionCentrePermissionID, \PDO::PARAM_INT);

            $stmt->execute();

            if($row = $stmt->fetch())
            {
                return datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
            }

            return '';


        
        }


        //########## END MANAGEMENT FUNCTIONS ##########//
    }