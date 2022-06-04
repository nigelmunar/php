<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/logging/Logger.php';
    
    class LoggingPDOStatement extends PDOStatement
    {
        private $logEntry;

        protected function __construct()
        {
            $this->logEntry = new \SQLLogEntry();
            $this->logEntry->setText($this->queryString);
            $this->logEntry->setTrace(debug_backtrace());
        }

        public function execute($bound_input_params = NULL) : bool
        {
            $start = microtime(true);
            $result = parent::execute();
            $end = microtime(true);

            $executionTime = $end - $start;
            $this->logEntry->setTimeTaken($executionTime);
            
            \Logger::addLog('sql', $this->logEntry);

            return $result;
        }
        
        public function bindValue($parameter, $value, $data_type = NULL) : bool
        {
            $this->logEntry->addParameter($parameter, $value, $data_type);

            return parent::bindValue($parameter, $value, $data_type);
        }
    }