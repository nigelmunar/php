<?php
    declare(strict_types = 1);

    require_once ROOT_PATH . 'application/factories/LoggingDatabaseFactory.php';
    require_once ROOT_PATH . 'application/factories/AdministratorDatabaseFactory.php';
    require_once ROOT_PATH . 'application/tools/generalFunctions.php';

    $loggingDB = \LoggingDatabaseFactory::create();

    $administratorDB = \AdministratorDatabaseFactory::create();

    $administrator = $administratorDB->getLoggedInAdministrator();

    if(!is_null($administrator))
    {
        ?>
        <div id="admin-bar"<?php echo (isset($_COOKIE['admin-mini']) && $_COOKIE['admin-mini'] === 'true' ? ' class="admin-mini"' : ''); ?>><ul><li><a href="javascript:void(0);" onclick="$('#admin-bar').toggleClass('admin-mini'); if($('#admin-bar').hasClass('admin-mini')) { createCookie('admin-mini', 'true', 90); } else { createCookie('admin-mini', 'false', -10); }"><i class="fas fa-seedling"></i></a></li><li><a href="<?php echo $noLangSiteURL; ?>"><i class="far fa-tachometer-slow"></i> NigelMunar</a></li><?php
        
        if($isLocal)
        {
            ?><li><a href=""><i class="fas fa-line-height"></i>Log</a><div><?php require ROOT_PATH . 'application/html-includes/logging-output.php'; ?></div></li><li><a href="javacript:void(0);" title="Page Load Time"><i class="fas fa-redo"></i></span><?php echo rtrim(rtrim(number_format($pageTime, 4), '0'), '.') . 's'; ?></a></li><li><a href="javacript:void(0);" title="Logged Time"><i class="fal fa-exchange"></i><?php echo rtrim(rtrim(number_format($totalLogTime, 4), '0'), '.') . 's'; ?></a></li><li><a href="javacript:void(0);" title="SQL Time"><i class="fas fa-server"></i><?php echo rtrim(rtrim(number_format($totalSqlTime, 4), '0'), '.') . 's'; ?></a></li><li><a href="javacript:void(0);" title="Redis Time"><i class="far fa-sync-alt"></i><?php echo rtrim(rtrim(number_format($totalRedisTime, 4), '0'), '.') . 's'; ?></a></li><li><a href="javascript:void(0);" class="flush-cache"><i class="far fa-toilet"></i>Flush Cache</a></li><?php
        }        
        

        ?></ul></div>
        <?php
    }