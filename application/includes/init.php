<?php
    declare(strict_types = 1);


    require_once ROOT_PATH . 'application/factories/LoggingDatabaseFactory.php';
    require_once ROOT_PATH . 'application/factories/AdministratorDatabaseFactory.php';
    require_once ROOT_PATH . 'application/factories/ErrorDatabaseFactory.php';
    require_once ROOT_PATH . 'application/tools/generalFunctions.php';
  
    $loggingDB = \LoggingDatabaseFactory::create();

    $administratorDB = \AdministratorDatabaseFactory::create();

    $administrator = $administratorDB->getLoggedInAdministrator();

  
    // echo '<pre>';
    // var_dump($administrator);

    // exit();

    if(!is_null($administrator))
    {
        $loggingDB->setUserID($administrator->getAdministratorID());
    }
    

    //Kick out non logged in people accessing the admin pages to the login page
    if(startsWith($scriptName, '') && $scriptName !== 'login.php' && $scriptName !== 'forgotten-password.php'  && $scriptName !== 'reset-password.php'&& $scriptName !== 'access-denied.php' && $scriptName !== 'accept-invitation.php')
    {
        if(!$administratorDB->loggedIn())
        {
            header('Location: ' . $siteURL . 'login.html');
            exit();
        }

    }


    $errorDB = \ErrorDatabaseFactory::create();      
    
    
