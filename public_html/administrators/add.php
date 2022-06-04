<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	require_once ROOT_PATH . 'application/factories/AdministratorDatabaseFactory.php';

	if(!$administrator->hasPermission('Administrators', 'read') || !$administrator->hasPermission('Administrators', 'create'))
	{
		include ROOT_PATH . 'public_html/access-denied.php';
	}

	$pageURL = $siteURL . 'administrators/add.html';
	$canonicalURL = $pageURL;

	$firstName = '';
	$lastName  = '';
	$email     = '';
	$enabled   = true;
	$upgradeAdministratorCode = '';

	$recruiterChk = 'N';

	$errors    = [];


	if(isset($_POST['txtFormType']) && $_POST['txtFormType'] === "ADDADMIN")
	{
		$firstName = 	(isset($_POST['txtFirstName']) ? trim(mb_substr(trim($_POST['txtFirstName']), 0, 200)) : '');
		$lastName  = 	(isset($_POST['txtLastName']) ? trim(mb_substr(trim($_POST['txtLastName']), 0, 200)) : '');
		$email     = 	(isset($_POST['txtEmail']) ? trim(mb_substr(trim($_POST['txtEmail']), 0, 200)) : '');
		$recruiterChk  = 	(isset($_POST['recruiterChk']) ? $_POST['recruiterChk'] : 'N');

	
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

			$stmt = $pdo->prepare('SELECT `administrator_code` FROM `administrators` WHERE `email` = :email AND `live` = 1');

			$stmt->bindValue(':email', $email, \PDO::PARAM_STR);

			$stmt->execute();

			if($row = $stmt->fetch())
			{
				$errors[] = 'Email';
				$errors[] = 'EmailInUse';
			}
		}


		if(count($errors) === 0)
		{
			$activeAdministrator = new \Entities\Administrator();

			$activeAdministrator->setFirstName($firstName);
			$activeAdministrator->setLastName($lastName);
			$activeAdministrator->setEmail($email);
			$activeAdministrator->setIsEnabled(true);


			$activeAdministrator = $administratorDB->addAdministrator($activeAdministrator);

		
			\RedisCacher::clearCollection('Administrators');

			$_SESSION['Administrator Created'] = 'true';

			header('Location: ' . $siteURL . 'admin/administrators/view.html?administrator=' . $activeAdministrator->getAdministratorCode());
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
                    <h1>Add Administrator</h1>
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

<?php
    require ROOT_PATH . 'application/html-includes/admin/footer.php';