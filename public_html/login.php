<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
    require_once ROOT_PATH . 'application/tools/generalFunctions.php';

    $email      = '';
	$rememberMe = true;
	$errors     = [];

	if($administratorDB->loggedIn())
	{
		header('Location: ' . $siteURL);
		exit();
	}

	if(isset($_POST['txtFormType']) && $_POST['txtFormType'] === 'LOGIN')
	{
		$email      = (isset($_POST['txtEmail'])    ? $_POST['txtEmail'] : '');
		$password   = (isset($_POST['txtPassword']) ? $_POST['txtPassword'] : '');
		$rememberMe = true;

		if(strlen($email) === 0)
		{
			$errors[] = 'Email';
		}

		if(strlen($password) === 0)
		{
			$errors[] = 'Password';
		}

		if(count($errors) === 0)
		{
			if($administratorDB->login($email, $password, $rememberMe, true))
			{
				if(isset($_COOKIE['twofactorsuccess']))
				{
					if($administratorDB->validateTwoFactorSuccessCode($_SESSION['pendingLogin_AdministratorID'], $_COOKIE['twofactorsuccess']))
					{
						$administratorDB->performLogin($_SESSION['pendingLogin_AdministratorID'], false);

						unset($_SESSION['pendingLogin_AdministratorID']);
						unset($_SESSION['pendingLogin_RememberMe']);
						unset($_SESSION['pendingLogin_Secret']);
						unset($_SESSION['pendingLogin_DateTime']);

						if(isset($_GET['from']) && strlen($_GET['from']) > 0)
						{
							header('Location: ' . $_GET['from']);
						}
						else
						{
							header('Location: ' . $siteURL);
						}

						exit();
					}
					else
					{
						setcookie('twofactorsuccess', '', time() - (86400 * 7), '/');
					}
				}

				header('Location: ' . $siteURL . 'login.html' . (isset($_GET['from']) && strlen($_GET['from']) > 0 ? '?from=' . $_GET['from'] : ''));

				exit();
			}
			else
			{
				$errors[] = 'Email';
				$errors[] = 'Password';
				$errors[] = 'Login Failed';
			}
		}
	}


	$validCode = null;

	if(isset($_POST['txtFormType']) && $_POST['txtFormType'] === 'AUTHENTICATE' && isset($_SESSION['pendingLogin_AdministratorID']))
	{
		$secret = $administratorDB->getTwoFactorLogin($_SESSION['pendingLogin_AdministratorID']);

		$code = (isset($_POST['two-factor-code']) ? $_POST['two-factor-code'] : '');
		
		$tfa = new \RobThree\Auth\TwoFactorAuth('NigelMunar');

		if($tfa->verifyCode($secret, $code) === true) 
		{
			$validCode = true;

			$pdo = \PDOFactory::getConnection();

			$stmt = $pdo->prepare('
				UPDATE `administrators`
				SET `two_factor_secret` = :two_factor_secret
				WHERE `administrator_id` = :administrator_id AND `two_factor_secret` IS NULL');

			$stmt->bindValue(':administrator_id', $_SESSION['pendingLogin_AdministratorID'], PDO::PARAM_INT);
			$stmt->bindValue(':two_factor_secret', $secret, PDO::PARAM_STR);

			$stmt->execute();


			$expiryDate = new \DateTime('now', $displayTimezone);
			$expiryDate->add(new \DateInterval('P7D'));

			$stmt = $pdo->prepare('
				INSERT INTO `administrator_two_factor_success_codes`(`administrator_id`, `date_time_created`, `date_time_expires`)
				VALUES(:administrator_id, NOW(), :date_time_expires)');

			$stmt->bindValue(':administrator_id', $_SESSION['pendingLogin_AdministratorID'], PDO::PARAM_INT);
			$stmt->bindValue(':date_time_expires', datetimeToDB($expiryDate)->format('Y-m-d H:i:s'), PDO::PARAM_STR);
			
			$stmt->execute();

			$administratorTwoFactorSuccessCodeID = (int)$pdo->lastInsertId();


			$stmt = $pdo->prepare('
				SELECT `administrator_two_factor_success_code` 
				FROM `administrator_two_factor_success_codes`
				WHERE `administrator_two_factor_success_code_id` = :administrator_two_factor_success_code_id');

			$stmt->bindValue(':administrator_two_factor_success_code_id', $administratorTwoFactorSuccessCodeID, PDO::PARAM_INT);

			$stmt->execute();

			if($row = $stmt->fetch())
			{
				setcookie('twofactorsuccess', (string)$row['administrator_two_factor_success_code'], time() + (86400 * 7), '/');
			}


			$administratorDB->performLogin($_SESSION['pendingLogin_AdministratorID'], false);

			if(strlen($_SESSION['pendingLogin_RememberMe']) > 0)
			{
				setcookie('rememberme', $_SESSION['pendingLogin_RememberMe'], time() + (86400 * 90), '/');
			}

			unset($_SESSION['pendingLogin_AdministratorID']);
			unset($_SESSION['pendingLogin_RememberMe']);
			unset($_SESSION['pendingLogin_Secret']);
			unset($_SESSION['pendingLogin_DateTime']);

			if(isset($_GET['from']) && strlen($_GET['from']) > 0)
			{
				header('Location: ' . $_GET['from']);
			}
			else
			{
				header('Location: ' . $siteURL);
			}

			exit();
		}
		else
		{
			$validCode = false;
		}
	}


	if(isset($_GET['cancel']) && $_GET['cancel'] === 'true')
	{
		unset($_SESSION['pendingLogin_AdministratorID']);
		unset($_SESSION['pendingLogin_RememberMe']);
		unset($_SESSION['pendingLogin_Secret']);
		unset($_SESSION['pendingLogin_DateTime']);

		header('Location: ' . $siteURL . 'login.html' . (isset($_GET['from']) && strlen($_GET['from']) > 0 ? '?from=' . $_GET['from'] : ''));

		exit();
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>NigelMunar | Log in</title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
		<link rel="stylesheet" href="<?php echo $siteURL; ?>dist/css/admin.css">
	</head>
	<body class="hold-transition login-page">
		<div class="login-box">
			<img src="/images/logo.svg" alt="NigelMunar" width="135px" height="121px" class="login-box__img" />
			<?php
				if(isset($_SESSION['pendingLogin_AdministratorID']))
				{
					$secret = $administratorDB->getTwoFactorLogin($_SESSION['pendingLogin_AdministratorID']);

					echo '<div>';

					if(isset($_SESSION['pendingLogin_Secret']) && strlen($_SESSION['pendingLogin_Secret']) > 0)
					{
						$tfa = new \RobThree\Auth\TwoFactorAuth('NigelMunar');

						echo '<p>Open the Google Authenticator App and scan the QR code below.</p>';
					?>

					<div class="login-page__google-app">
						<img src="/images/google-app.png" alt="NigelMunar" class="login-page__google-app-img" />
						<div class="login-page__google-app-title">
							Google Authenticator
						</div>
					</div>

					<?php
						echo '<img src="' . $tfa->getQRCodeImageAsDataUri('Administrator', $secret) . '" />';
					}
					?>

					<form action="<?php echo $siteURL; ?>login.html<?php echo (isset($_GET['from']) && strlen($_GET['from']) > 0 ? '?from=' . urlencode($_GET['from']) : ''); ?>" method="post">
						<p>Please enter your authentication code</p>
						<?php
							if($validCode === false)
							{
								echo '<p class="login-page__app-error">Invalid code, please try again</p>';
							}
						?>
						<input type="text" name="two-factor-code" />
						<input type="hidden" name="txtFormType" value="AUTHENTICATE" />
						<div class="login-page__form-bottom">
							<div class="login-page__cancel-login">
								or <a href="<?php echo $siteURL; ?>login.html?cancel=true<?php echo (isset($_GET['from']) && strlen($_GET['from']) > 0 ? '&from=' . $_GET['from'] : ''); ?>">cancel login</a>
							</div>
							<button type="submit" class="login-page__button">Submit</button> 
						</div>
					</form>
					<?php

					echo '</div>';
				}
				else
				{
					?>
					<form action="<?php echo $siteURL; ?>login.html<?php echo (isset($_GET['from']) && strlen($_GET['from']) > 0 ? '?from=' . urlencode($_GET['from']) : ''); ?>" method="post">
						<input type="email" class="form-control<?php echo (in_array('Email', $errors) ? ' is-invalid' : ''); ?>" id="user-name" placeholder="Enter Email Address" required name="txtEmail" value="<?php echo htmlspecialchars($email); ?>">

						<input type="password" class="form-control<?php echo (in_array('Password', $errors) ? ' is-invalid' : ''); ?>" id="user-password" placeholder="Enter Password" required  name="txtPassword" />
						<div class="login-page__container">
							<a href="<?php echo $siteURL; ?>forgotten-password.html" class="login-page__link">I forgot my password</a>
							<button type="submit" class="login-page__button">Sign In</button>
							<input type="hidden" name="txtFormType" value="LOGIN" />
						</div>
					</form>
					<?php
				}
			?>
		</div>
		<!-- /.login-box -->
		<!-- jQuery -->
		<script src="<?php echo $siteURL; ?>plugins/jquery/jquery.min.js"></script>
		<!-- Bootstrap 4 -->
		<script src="<?php echo $siteURL; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
		<!-- AdminLTE App -->
		<script src="<?php echo $siteURL; ?>dist/js/adminlte.min.js"></script>

		<script src="<?php echo $siteURL; ?>plugins/toastr/toastr.min.js"></script>

		<script>
            $(function() {
                <?php
                    if(isset($_SESSION['Password Set']) && $_SESSION['Password Set'] === 'true')
                    {
                        echo 'toastr.success(\'Password set successfully.\'); ' . "\n";

                        unset($_SESSION['Password Set']);
                    }

					if(isset($_SESSION['Password Changed']) && $_SESSION['Password Changed'] === 'true')
                    {
                        echo 'toastr.success(\'Password changed successfully.\'); ' . "\n";

                        unset($_SESSION['Password Changed']);
                    }
				?>
			});
		</script>
	</body>
</html>