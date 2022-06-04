<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	require_once ROOT_PATH . 'application/factories/AdministratorDatabaseFactory.php';

	if(!$administrator->hasPermission('Administrators', 'read') && !$administrator->getSuperAdministrator())
	{
		include ROOT_PATH . 'public_html/access-denied.php';
	}

	if(isset($_GET['disable']) && strlen($_GET['disable']) > 0 && $administrator->hasPermission('Administrators', 'update'))
	{
		$administratorDB = \AdministratorDatabaseFactory::create();

		$activeAdministrator = $administratorDB->getAdministrator($_GET['disable']);

		if(!is_null($activeAdministrator))
		{
			if($activeAdministrator->getAdministratorID() !== $administrator->getAdministratorID())
			{
				$activeAdministrator->setIsEnabled(false);

				$administratorDB->saveAdministrator($activeAdministrator);

				\RedisCacher::clearCollection('Administrators');

				$_SESSION['Admin Saved'] = 'true';
			}

			header('Location: ' . $siteURL . 'admin/administrators/');
			exit();
		}
	}

	if(isset($_GET['enable']) && strlen($_GET['enable']) > 0 && $administrator->hasPermission('Administrators', 'update'))
	{
		$administratorDB = \AdministratorDatabaseFactory::create();

		$activeAdministrator = $administratorDB->getAdministrator($_GET['enable']);

		if(!is_null($activeAdministrator))
		{
			if($activeAdministrator->getAdministratorID() !== $administrator->getAdministratorID())
			{
				$activeAdministrator->setIsEnabled(true);

				$administratorDB->saveAdministrator($activeAdministrator);

				\RedisCacher::clearCollection('Administrators');

				$_SESSION['Admin Saved'] = 'true';
			}

			header('Location: ' . $siteURL . 'admin/administrators/');
			exit();
		}
	}


	if(isset($_GET['delete']) && strlen($_GET['delete']) > 0 && $administrator->hasPermission('Administrators', 'delete'))
	{
		$administratorDB = \AdministratorDatabaseFactory::create();

		$activeAdministrator = $administratorDB->getAdministrator($_GET['delete']);

		if(!is_null($activeAdministrator))
		{
			if($activeAdministrator->getAdministratorID() !== $administrator->getAdministratorID())
			{
				$administratorDB->deleteAdministrator($activeAdministrator);

				\RedisCacher::clearCollection('Administrators');

				$_SESSION['Admin Deleted'] = 'true';
			}

			header('Location: ' . $siteURL . 'admin/administrators/');
			exit();
		}
	}

    
    require ROOT_PATH . 'application/config/pageVars.php';
	require ROOT_PATH . 'application/html-includes/admin/header.php';

	if(!$administrator->hasPermission('Administrators', 'delete'))
	{
		echo '<style>#administrator-datatable .delete-link { display:none; }</style>';
	}

	if(!$administrator->hasPermission('Administrators', 'update'))
	{
		echo '<style>#administrator-datatable .edit-link { display:none; }</style>';
	}
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Administrators</h1>
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
				<div class="col-12">
					<div class="lists__top-button">
						<?php
							if($administrator->hasPermission('Administrators', 'create'))
							{
								echo '<a href="' . $siteURL . 'administrators/add.html" class="btn btn-primary">New Administrator</a>';
							}
						?>
					</div>
					<br>
					<div class="card">
						<div class="card-header lists__top">
							<div class="lists__top-title">
								<h4 class="card-title">Administrator List</h4>
							</div>
						</div>
						<div class="card-content collapse show">
							<div class="card-body">
								<div class="table-responsive">
									<table id="administrator-datatable" class="table table-striped">
										<thead>
											<tr>
												<th>Administrator Name</th>
												<th>Email</th>
												<th>Date Added</th>
												<th>&nbsp;</th>
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
<!-- /.content-wrapper -->

<?php
    require ROOT_PATH . 'application/html-includes/admin/footer.php';