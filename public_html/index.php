<?php
    declare(strict_types = 1);
    
    require_once __DIR__ . '/../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';



    require ROOT_PATH . 'application/config/pageVars.php';
	require ROOT_PATH . 'application/html-includes/admin/header.php';
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Dashboard</h1>
                </div>
                <div class="col-sm-6">
                   
                    <?php
                        require ROOT_PATH . 'application/html-includes/admin/breadcrumb.php';
                    ?>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>   

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                
            </div>
        </div>
    </section>
</div>

<?php
    require ROOT_PATH . 'application/html-includes/admin/footer.php';