<?php
    declare(strict_types = 1);

    $breadcrumb      = [];
    $pageTitle       = '';
    $navName         = 'HOME';
    $subNavName      = '';
    $pageTitle       = 'Nigelmunar';
    $metaDescription = 'Nigelmunar.';


    switch($scriptName)
    {
        case 'index.php':
            $navName    = 'HOME';

            break;

        case 'administrators/index.php':
            $breadcrumb = [['Administators', 'administrators/']];
            
            $navName    = 'ADMINISTRATORS';

            $scripts[] = [ 'https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.js' ];
            $scripts[] = [ $siteURL . 'build/js/administrators-datatable.js' ];

            break;
        case 'administrators/add.php':
            $breadcrumb = [['Administators', 'administrators/'], [ 'Add Administator', 'administrators/add.html' ]];
            
            $navName    = 'ADMINISTRATORS';

            break;
        case 'administrators/view.php':
            $breadcrumb = [['Administators', 'administrators/'], [ htmlspecialchars($activeAdministrator->getFirstName() . ' ' . $activeAdministrator->getLastName()), 'administrators/view.html?administrator=' . htmlspecialchars($activeAdministrator->getAdministratorCode()) ]];
            
            $navName    = 'ADMINISTRATORS';

            //$scripts[] = [ $siteURL . 'admin/dist/js/permissions.min.js' ];

            break;
        case 'administrators/edit.php':
            $breadcrumb = [['Administators', 'administrators/'], [ htmlspecialchars($activeAdministrator->getFirstName() . ' ' . $activeAdministrator->getLastName()), 'administrators/view.html?administrator=' . htmlspecialchars($activeAdministrator->getAdministratorCode()) ], [ 'Edit', 'administrators/edit.html?administrator=' . htmlspecialchars($activeAdministrator->getAdministratorCode())]];
            
            $navName    = 'ADMINISTRATORS';

            break;
    }
