<?php
    declare(strict_types = 1);

    namespace Entities;

    class Administrator implements \JsonSerializable 
    {
        private $_administratorID; //int
        private $_administratorCode; //string
        private $_email; //string
        private $_firstName; //string
        private $_lastName; //string
        private $_activated; //bool
        private $_isEnabled; //bool
        private $_dateTimeCreated; //\DateTime
        private $_dateTimeLastLoggedIn; //?\DateTime
        private $_permissions; //?array
        private $_superAdministrator; //bool
        
        
        private $permissionCache = [];

        public function getAdministratorID() : int
        {
            return $this->_administratorID;
        }


        public function setAdministratorID(int $value) : void
        {
            $this->_administratorID = $value;
        }


        public function getAdministratorCode() : string
        {
            return $this->_administratorCode;
        }

        public function setAdministratorCode(string $value) : void
        {
            $this->_administratorCode = $value;
        }


        public function getEmail() : string
        {
            return $this->_email;
        }

        public function setEmail(string $value) : void
        {
            $this->_email = $value;
        }


        public function getFirstName() : string
        {
            return $this->_firstName;
        }

        public function setFirstName(string $value) : void
        {
            $this->_firstName = $value;
        }


        public function getLastName() : string
        {
            return $this->_lastName;
        }

        public function setLastName(string $value) : void
        {
            $this->_lastName = $value;
        }


        public function getActivated() : bool
        {
            return $this->_activated;
        }

        public function setActivated(bool $value) : void
        {
            $this->_activated = $value;
        }


        public function getIsEnabled() : bool
        {
            return $this->_isEnabled;
        }

        public function setIsEnabled(bool $value) : void
        {
            $this->_isEnabled = $value;
        }


        public function getDateTimeCreated() : \DateTime
        {
            return $this->_dateTimeCreated;
        }

        public function setDateTimeCreated(\DateTime $value) : void
        {
            $this->_dateTimeCreated = $value;
        }


        public function getDateTimeLastLoggedIn() : ?\DateTime
        {
            return $this->_dateTimeLastLoggedIn;
        }

        public function setDateTimeLastLoggedIn(?\DateTime $value) : void
        {
            $this->_dateTimeLastLoggedIn = $value;
        }


        public function getPermissions() : ?array
        {
            return $this->_permissions;
        }

        public function setPermissions(?array $value) : void
        {
            $this->_permissions = $value;
        }

        public function getSuperAdministrator() : bool
        {
            return $this->_superAdministrator;
        }

        public function setSuperAdministrator(bool $value) : void
        {
            $this->_superAdministrator = $value;
        }

        public function hasPermission(string $permission, string $accessType) : bool
        {
            $permission = strtolower($permission);
            $accessType = strtolower($accessType);

            $key = $permission . '|' . $accessType;

            if(!array_key_exists($key, $this->permissionCache))
            {
                $this->permissionCache[$key] = false;

                if(!is_null($this->_permissions))
                {
                    for($i = 0; $i < count($this->_permissions); $i++)
                    {
                        if(strtolower($this->_permissions[$i]['name']) === $permission)
                        {
                            switch($accessType)
                            {
                                case 'create':
                                case 'c':
                                    $this->permissionCache[$key] = $this->_permissions[$i]['create'];

                                    break;
                                case 'read':
                                case 'r':
                                    $this->permissionCache[$key] = $this->_permissions[$i]['read'];

                                    break;
                                case 'update':
                                case 'u':
                                    $this->permissionCache[$key] = $this->_permissions[$i]['update'];

                                    break;
                                case 'delete':
                                case 'd':
                                    $this->permissionCache[$key] = $this->_permissions[$i]['delete'];

                                    break;
                                case 'page_layout':
                                case 't':
                                    $this->permissionCache[$key] = $this->_permissions[$i]['page_layout'];
                                    
                                    break;
                                case 'all':
                                case 'full':
                                    $this->permissionCache[$key] = ($this->_permissions[$i]['create'] && $this->_permissions[$i]['read'] && $this->_permissions[$i]['update'] && $this->_permissions[$i]['delete']);

                                    break;
                                case 'any':
                                    $this->permissionCache[$key] = ($this->_permissions[$i]['create'] || $this->_permissions[$i]['read'] || $this->_permissions[$i]['update'] || $this->_permissions[$i]['delete']);

                                    break; 
                                
                            }

                            break;
                        }
                    }
                }
            }

            return $this->permissionCache[$key];
        }



        public function jsonSerialize() : array
        {
            return [
                'id' => $this->_administratorID,
                'code' => $this->_administratorCode,
                'firstName' => $this->_firstName,
                'lastName' => $this->_lastName,
                'email' => $this->_email,
                'activated' => $this->_activated,
                'enabled' => $this->_isEnabled,
                'dateTimeCreated' => $this->_dateTimeCreated,
                'dateTimeLastLoggedIn' => $this->_dateTimeLastLoggedIn,
                'superAdministrator' => $this->_superAdministrator,  
            ];
        }


        public function jsonDeserialize(string $jsonString) : void
        {
            $json = json_decode($jsonString, true);

            $this->_administratorID = $json['id'];
            $this->_administratorCode = $json['code'];
            $this->_firstName = $json['firstName'];
            $this->_lastName = $json['lastName'];
            $this->_email = $json['email'];
            $this->_activated = $json['activated'];
            $this->_isEnabled = $json['enabled'];
            $this->_superAdministrator = $json['superAdministrator'];
            $this->_dateTimeCreated = new \DateTime($json['dateTimeCreated']['date'], new \DateTimeZone($json['dateTimeCreated']['timezone']));

            $this->_dateTimeLastLoggedIn = null;

            if(!is_null($json['dateTimeLastLoggedIn']))
            {
                $this->_dateTimeLastLoggedIn = new \DateTime($json['dateTimeLastLoggedIn']['date'], new \DateTimeZone($json['dateTimeLastLoggedIn']['timezone']));
            }
        }
    }