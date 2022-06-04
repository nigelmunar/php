<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/logging/LogEntry.php';
    class Logger
    {
        private static $logs = [];

        public static function getLog() : array
        {
            return self::$logs;
        }

        public static function getLogOfType(string $type) : array
        {
            if(array_key_exists(strtolower($type), self::$logs))
            {
                return self::$logs[strtolower($type)];
            }
            
            return [];
        }

        public static function addLog(string $type, LogEntry $entry)
        {
            if(!array_key_exists(strtolower($type), self::$logs))
            {
                self::$logs[strtolower($type)] = [];
            }

            self::$logs[strtolower($type)][] = $entry;
        }
    }