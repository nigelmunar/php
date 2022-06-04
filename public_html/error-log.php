<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	
	if(!$isLocal)
	{
		include ROOT_PATH . 'public_html/access-denied.php';
	}


	$pdo = \PDOFactory::getConnection();


    require ROOT_PATH . 'application/config/pageVars.php';
	require ROOT_PATH . 'application/html-includes/admin/header.php';
	
?>
<style>
	#error-datatable tr td:nth-child(3)
	{
		word-break:break-all;
	}
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Errors</h1>
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
    <section class="content error-log">
        <div class="container-fluid">
			<div class="row">
				<div class="col-12">			
					<div class="card">
						<div class="card-content collapse show">
							<div class="card-body">

								<div class="table-responsive">        
					                <table id="error-datatable" class="table filter-sort-table" data="error">
					                    <thead>
					                        <tr>
					                            
					                            <th>Error Code</th>
					                            <th>Error Message</th>
					                            
					                          	
					                          	<th>Page</th>
					                          	<th>Date Time</th>
					                        </tr>
					                    </thead>
					                    <tbody>
					                        
					                    </tbody>
					                </table>
					            </div>
								
								                 
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>


<?php
	require ROOT_PATH . 'application/html-includes/admin/footer.php';