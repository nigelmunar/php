<?php
    declare(strict_types = 1);

    /**
    * Error handler, passes flow over the exception logger with new ErrorException.
    */
    function log_error( $num, $str, $file, $line, $context = null )
    {
        log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
    }

    /**
    * Uncaught exception handler.
    */
    function log_exception($e)
    {
        $message = "Type: " . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()}; Trace: " . $e->getTraceAsString();
       
        if(!defined('ROOT_PATH'))
        {
            define('ROOT_PATH', realpath(__DIR__ . '/../../') . '\\');
        }

        require_once ROOT_PATH . 'application/factories/LoggingDatabaseFactory.php';

        $loggedToDB = false;

        try
        {
            $loggingDB = \LoggingDatabaseFactory::create();
            $pdo       = \PDOFactory::getConnection();

            $requestURI = substr((isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''), 0, 1000);

            $stmt = $pdo->prepare('
                INSERT INTO `error_pages`(`request_url`, `date_time_created`)
                SELECT `new_request_url`, NOW() FROM (SELECT :request_url AS `new_request_url`) AS tbl1 WHERE NOT EXISTS(SELECT 1 FROM `error_pages` AS u WHERE `request_url` = :request_url)');
            
            $stmt->bindValue(':request_url', $requestURI, \PDO::PARAM_STR);

            $stmt->execute();

            $stmt = $pdo->prepare('
                INSERT INTO `errors`(`error_page_id`, `ip_address_id`, `user_agent_id`, `error_code`, `error_message`, `date_time_logged`)
                SELECT `error_page_id`, :ip_address_id, :user_agent_id, 500, :error_message, NOW()
                FROM `error_pages`
                WHERE `request_url` = :request_url');

            $stmt->bindValue(':request_url',    $requestURI,                    \PDO::PARAM_STR);
            $stmt->bindValue(':ip_address_id',  $loggingDB->getIpAddressID(),   \PDO::PARAM_INT);
            $stmt->bindValue(':user_agent_id',  $loggingDB->getUserAgentID(),   \PDO::PARAM_INT);
            $stmt->bindValue(':error_message',  substr($message, 0, 4000),      \PDO::PARAM_STR);

            $stmt->execute();

            $loggedToDB = true;
        }
        catch(\Exception $ex)
        {

        }
        catch(\Throwable $ex)
		{

        }


        if(!$loggedToDB)
        {
            try
            {
                //Log to file instead
                require_once ROOT_PATH . 'application/logging/FileLogger.php';

                if(!file_exists(ROOT_PATH . 'application/logs/'))
                {
                    mkdir(ROOT_PATH . 'application/logs/');
                }

                $fileLogger = new \FileLogger(ROOT_PATH . 'application/logs/error-log', 5);
                $fileLogger->log($message, false);
            }
            catch(\Exception $ex)
            {
    
            }
            catch(\Throwable $ex)
            {
    
            }
        }

        require ROOT_PATH . 'public_html/500.php';
    
        exit();
    }

    /**
    * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
    */
    function check_for_fatal()
    {
        $error = error_get_last();
        
        if(!is_null($error) && $error["type"] === E_ERROR)
        {
            log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
        }
    }

    // register_shutdown_function("check_for_fatal");
    // set_error_handler("log_error");
    // set_exception_handler("log_exception");

    // if($isLocal)
    // {
    //     ini_set("display_errors", "on");
    // }
    // else
    // {
    //     ini_set("display_errors", "off");
    // }
   
    ini_set("display_errors", "on");
    error_reporting(E_ALL);
