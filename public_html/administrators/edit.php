<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	require_once ROOT_PATH . 'application/factories/AdministratorDatabaseFactory.php';

	if(!$administrator->hasPermission('Administrators', 'read') || !$administrator->hasPermission('Administrators', 'update'))
	{
		include ROOT_PATH . 'public_html/admin/access-denied.php';
	}

	if(isset($_GET['administrator']) && strlen($_GET['administrator']) > 0)
	{
		$activeAdministratorCode = $_GET['administrator'];
	}
	else
	{
		include ROOT_PATH . 'public_html/admin/404.php';
	}

	$activeAdministratorDB = \AdministratorDatabaseFactory::create();

	$activeAdministrator = $activeAdministratorDB->getAdministrator($activeAdministratorCode);

	if(is_null($activeAdministrator))
	{
		include ROOT_PATH . 'public_html/admin/404.php';
	}

	$pageURL = $siteURL . 'administrators/edit.html?administrator=' . $activeAdministrator->getAdministratorCode();
	$canonicalURL = $pageURL;

	$firstName = $activeAdministrator->getFirstName();
	$lastName  = $activeAdministrator->getLastName();
	$email     = $activeAdministrator->getEmail();
	$enabled   = $activeAdministrator->getIsEnabled();

	$errors    = [];

	if(isset($_POST['txtFormType']) && $_POST['txtFormType'] === "SAVEADMIN")
	{
		$firstName = (isset($_POST['txtFirstName']) ? trim(mb_substr(trim($_POST['txtFirstName']), 0, 200)) : '');
		$lastName  = (isset($_POST['txtLastName']) ? trim(mb_substr(trim($_POST['txtLastName']), 0, 200)) : '');
		$email     = (isset($_POST['txtEmail']) ? trim(mb_substr(trim($_POST['txtEmail']), 0, 200)) : '');

		if($administrator->getAdministratorID() === $activeAdministrator->getAdministratorID())
		{
			//Cannot disable yourself
			$enabled = true;
		}
		else
		{
			$enabled = (isset($_POST['chkEnabled']) && $_POST['chkEnabled'] === 'Y');
		}

		if(strlen($firstName) === 0)
		{
			$errors[] = 'FirstName';
			$errors[] = 'FirstNameBlank';
		}

		if(strlen($lastName) === 0)
		{
			$errors[] = 'LastName';
			$errors[] = 'LastNameBlank';
		}

		if(strlen($email) === 0)
		{
			$errors[] = 'Email';
			$errors[] = 'EmailBlank';
		}
		elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$errors[] = 'Email';
			$errors[] = 'EmailInvalid';
		}
		else
		{
			$pdo = \PDOFactory::getConnection();

			$stmt = $pdo->prepare('SELECT 1 FROM `administrators` WHERE `email` = :email AND `administrator_id` <> :administrator_id AND `live` = 1');

			$stmt->bindValue(':email',   			$email, 									 \PDO::PARAM_STR);
			$stmt->bindValue(':administrator_id', 	$activeAdministrator->getAdministratorID(),  \PDO::PARAM_INT);

			$stmt->execute();

			if($row = $stmt->fetch())
			{
				$errors[] = 'Email';
				$errors[] = 'EmailInUse';
			}
		}

		if(count($errors) === 0)
		{
			$activeAdministrator->setFirstName($firstName);
			$activeAdministrator->setLastName($lastName);
			$activeAdministrator->setEmail($email);
			$activeAdministrator->setIsEnabled($enabled);

			$administratorDB->saveAdministrator($activeAdministrator);

			\RedisCacher::clearCollection('Administrators');

			$_SESSION['Administrator Saved'] = 'true';

			header('Location: ' . $siteURL . 'administrators/view.html?administrator=' . $activeAdministrator->getAdministratorCode());
			exit();
		}
	}


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
                    <h1>Editing <?php echo htmlspecialchars($activeAdministrator->getFirstName() . ' ' . $activeAdministrator->getLastName()); ?></h1>
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
				<div class="col-sm-12">
					<div class="card">
						<div class="card-body">
							<?php
								require ROOT_PATH . 'application/forms/admin/administrator.php';
							?>
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