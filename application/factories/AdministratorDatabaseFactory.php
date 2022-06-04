<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/factories/PDOFactory.php';
    require_once ROOT_PATH . 'application/database/AdministratorDatabase.php';

    class AdministratorDatabaseFactory
    {
        public static $administratorDatabase = null;

        public static function create() : \AdministratorDatabase
        {
            if(is_null(AdministratorDatabaseFactory::$administratorDatabase))
            {
                AdministratorDatabaseFactory::$administratorDatabase = new \AdministratorDatabase(\PDOFactory::getConnection());
            }

            return AdministratorDatabaseFactory::$administratorDatabase;
        }

    }
