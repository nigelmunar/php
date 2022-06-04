<?php
    declare(strict_types = 1);

    require_once __DIR__ . '/../../application/config/config.php';
	require_once ROOT_PATH . 'application/includes/init.php';
    require_once ROOT_PATH . 'application/tools/dateFunctions.php';
    require_once ROOT_PATH . 'application/tools/filterFunctions.php';
	require_once ROOT_PATH . 'application/tools/generalFunctions.php';
	
	http_response_code(404);
?>
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
	<!-- BEGIN: Head-->
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
		<meta name="description" content="Chameleon Admin is a modern Bootstrap 4 webapp &amp; admin dashboard html template with a large number of components, elegant design, clean and organized code.">
		<meta name="author" content="ThemeSelect">
		<title>Business Growth Forum - 404 Not Found</title>
		<link rel="apple-touch-icon" href="<?php echo $siteURL; ?>admin/theme-assets/images/ico/apple-icon-120.png">
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo $siteURL; ?>admin/theme-assets/images/ico/favicon.ico">
		<link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
		<link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
		<!-- BEGIN VENDOR CSS-->
		<link rel="stylesheet" type="text/css" href="<?php echo $siteURL; ?>admin/theme-assets/css/vendors.css">
		<!-- END VENDOR CSS-->
		<!-- BEGIN CHAMELEON  CSS-->
		<link rel="stylesheet" type="text/css" href="<?php echo $siteURL; ?>admin/theme-assets/css/app-lite.css">
		<!-- END CHAMELEON  CSS-->
		<!-- BEGIN Page Level CSS-->
		<link rel="stylesheet" type="text/css" href="<?php echo $siteURL; ?>admin/theme-assets/css/core/colors/palette-gradient.css">
		<!-- END Page Level CSS-->
		<!-- BEGIN Custom CSS-->
        <link rel="stylesheet" type="text/css" href="<?php echo $siteURL; ?>admin/assets/css/style.css">
        <!-- END Custom CSS-->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script>var sSiteURL = '<?php echo $siteURL; ?>';</script>
	</head>
	<!-- END: Head-->
	<!-- BEGIN: Body-->
	<body class="vertical-layout vertical-menu 1-column  bg-gradient-directional-danger blank-page blank-page" data-open="click" data-menu="vertical-menu" data-color="bg-gradient-x-purple-blue" data-col="1-column">
		<!-- BEGIN: Content-->
		<div class="app-content content">
			<div class="content-wrapper">
				<div class="content-wrapper-before"></div>
				<div class="content-header row">
				</div>
				<div class="content-body">
					<section class="flexbox-container bg-hexagons-danger">
						<div class="col-12 d-flex align-items-center justify-content-center">
							<div class="col-lg-4 col-md-6 col-10 p-0">
								<div class="card-header bg-transparent border-0">
									<h2 class="error-code text-center mb-2 white">404</h2>
									<h3 class="text-uppercase text-center">Page Not Found !</h3>
								</div>
								<div class="card-content">
									<div class="row py-2 text-center">
										<div class="col-12">
											<a href="<?php echo $siteURL; ?>admin/" class="btn btn-white danger box-shadow-4"><i class="ft-home"></i> Back to Home</a>
										</div>
									</div>
								</div>
								<div class="card-footer bg-transparent">
									<div class="row">
										<p class="text-muted text-center col-12 py-1 white">© <span class="year"><?php echo date("Y"); ?></span> <a href="javascript:void(0)" class="white text-bold-700">VegTrug </a></p>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>
			</div>
		</div>
		<!-- END: Content-->
		<!-- END: Content-->
		<!-- BEGIN: Vendor JS-->
		<!-- BEGIN Vendor JS-->
		<!-- BEGIN: Page Vendor JS-->
		<!-- END: Page Vendor JS-->
		<!-- BEGIN: Theme JS-->
		<!-- END: Theme JS-->
		<!-- BEGIN: Page JS-->
		<!-- END: Page JS-->
	</body>
	<!-- END: Body-->
</html>
<?php
	exit();