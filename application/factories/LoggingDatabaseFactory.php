<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/factories/PDOFactory.php';
    require_once ROOT_PATH . 'application/database/LoggingDatabase.php';

    
    class LoggingDatabaseFactory
    {
        public static $loggingDatabase = null;

        public static function create() : \LoggingDatabase
        {
            if(is_null(LoggingDatabaseFactory::$loggingDatabase))
            {
                LoggingDatabaseFactory::$loggingDatabase = new \LoggingDatabase(\PDOFactory::getConnection());
            }

            return LoggingDatabaseFactory::$loggingDatabase;
        }

    }
