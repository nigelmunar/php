<?php
	declare(strict_types = 1);

    require_once __DIR__ . '/../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	
	$administratorDB->logout();

	header('Location: ' . $siteURL);
	