<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/logging/LoggingPDOStatement.php';
    require_once ROOT_PATH . 'application/logging/SQLLogEntry.php';
    
    class PDOFactory
    {
        private static $pdo = null;

        public static function getConnection() : PDO
        {
            if(is_null(self::$pdo))
            {
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

                try 
                {
                    $logEntry = new \SQLLogEntry();
                    $logEntry->setText('DB Connection');

                    $connectionStart = microtime(true);

                    self::$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_SCHEMA . ';charset=' . DB_CHARSET, DB_USER, DB_PASSWORD, $options);
                    self::$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array ('\LoggingPDOStatement', array()));

                    $connectionFinish = microtime(true);

                    $connectionTime = $connectionFinish - $connectionStart;

                    $logEntry->setTimeTaken($connectionTime);

                    \Logger::addLog('sql', $logEntry);
                } 
                catch (PDOException $e) 
                {
                    throw new PDOException($e->getMessage(), (int)$e->getCode());
                }
            }

            return self::$pdo;
        }
    }