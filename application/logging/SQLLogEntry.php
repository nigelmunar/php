<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/logging/LogEntry.php';

    class SQLLogEntry extends LogEntry
    {   
        private $_parameters = []; //array

        public function getParameters() : array
        {
            return $this->_parameters;
        }

        public function addParameter(string $parameter, $value, $data_type) : void
        {
            $this->_parameters[] = [ $parameter, $value, $data_type ];
        }
    }