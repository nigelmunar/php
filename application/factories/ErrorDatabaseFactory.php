<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/factories/PDOFactory.php';
    require_once ROOT_PATH . 'application/database/ErrorDatabase.php';

    class ErrorDatabaseFactory
    {
        public static $errorDatabase = null;

        public static function create() : \ErrorDatabase
        {
            if(is_null(ErrorDatabaseFactory::$errorDatabase))
            {
                ErrorDatabaseFactory::$errorDatabase = new \ErrorDatabase(\PDOFactory::getConnection());
            }

            return ErrorDatabaseFactory::$errorDatabase;
        }

    }
