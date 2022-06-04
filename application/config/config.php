<?php
    declare(strict_types = 1);

    $pageStart = microtime(true);

    require_once __DIR__ . '/../vendor/autoload.php';

	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $dotenv->required('DB_HOST')->notEmpty();
    $dotenv->required('DB_SCHEMA')->notEmpty();
    $dotenv->required('DB_USER')->notEmpty();
    $dotenv->required('DB_PASSWORD')->notEmpty();
    $dotenv->required('DB_CHARSET')->notEmpty();
    $dotenv->required('ROOT_PATH')->notEmpty();
    $dotenv->required('REDIS_HOST')->notEmpty();
    $dotenv->required('REDIS_PORT')->notEmpty()->isInteger();
    $dotenv->required('REDIS_ENABLED')->notEmpty()->isBoolean();
	
	$dotenv->required('ADMINISTRATOR_CACHE_SECONDS')->notEmpty()->isInteger();
    
    define('DB_HOST', $_ENV['DB_HOST']);
    define('DB_SCHEMA', $_ENV['DB_SCHEMA']);
    define('DB_USER', $_ENV['DB_USER']);
    define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
    define('DB_CHARSET', $_ENV['DB_CHARSET']);
	define('ROOT_PATH', $_ENV['ROOT_PATH']);
	
    define('REDIS_HOST', $_ENV['REDIS_HOST']);
    define('REDIS_PORT', (int)$_ENV['REDIS_PORT']);
    define('REDIS_ENABLED', (strtolower($_ENV['REDIS_ENABLED']) === 'true' ? true : false));
	
	define('ADMINISTRATOR_CACHE_SECONDS', (int)$_ENV['ADMINISTRATOR_CACHE_SECONDS']);
    
    
    require_once ROOT_PATH . 'application/tools/generalFunctions.php';

    $siteURL         = 'http' . (isset($_SERVER["HTTPS"]) ? 's' : '') . '://' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') . ((isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80') !== '443' && (isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80') !== '80' ? ':' . (isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80') : '') . '/';
    $isLocal         = (getUserIP() === '82.4.185.32' || ((getUserIP() === '::1' || getUserIP() === '127.0.0.1' || getUserIP() === '192.168.33.1' || getUserIP() === '172.26.0.1') || strpos($siteURL, '.local') !== false));

    session_start();
    ob_start();
    
    require_once ROOT_PATH . 'application/logging/ErrorReporting.php';
    require_once ROOT_PATH . 'application/logging/Logger.php';
    

    $dbTimezone      = new \DateTimeZone('UTC');
    $displayTimezone = new \DateTimeZone('Europe/London');


    $page            = 1;
    $maxPages        = 1;
    
    $noLangSiteURL   = $siteURL;
    $scriptName      = strtolower(str_replace(ROOT_PATH . 'public_html/', '', (isset($_SERVER["SCRIPT_FILENAME"]) ? $_SERVER["SCRIPT_FILENAME"] : '')));

    

    $breadcrumb      = [];
    $pageTitle       = '';
    $navName         = '';
    $subNavName      = '';

    $pageURL         = '';
    $nonPagedURL     = '';
    $canonicalURL    = '';
    $canonicalURLQuerystring = '';

    $pageTitle       = '';
	$metaDescription = '';
	
	$scripts		 = [];
	$styles			 = [];
	$ogTags 		 = [];

    $scriptKeys      = [];
    $styleKeys       = [];
    $mapsToLoad      = [];

    require_once ROOT_PATH . 'application/utilities/RedisCacher.php';    


    function enqueueScript(string $key, string $scriptURL)
    {
        global $scriptKeys, $scripts;

        if(!in_array(strtoupper($key), $scriptKeys))
        {
            $scripts[]    = $scriptURL;
            $scriptKeys[] = $key;
        }
    }
    
    function enqueueStyle(string $key, string $styleURL)
    {
        global $styleKeys, $styles;

        if(!in_array(strtoupper($key), $styleKeys))
        {
            $styles[]    = $styleURL;
            $styleKeys[] = $key;
        }
    }
