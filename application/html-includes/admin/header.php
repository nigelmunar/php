<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>NigelMunar</title>
        <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
        <link href="https://use.typekit.net/eli6ita.css" rel="stylesheet" />
        <script src="https://kit.fontawesome.com/524ac9d949.js" crossorigin="anonymous"></script>
		<!-- Ionicons -->
		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
		<!-- Theme style -->
		<link rel="stylesheet" href="<?php echo $siteURL; ?>dist/css/adminlte.min.css">
        <!-- Toastr -->
        <link rel="stylesheet" href="<?php echo $siteURL; ?>plugins/toastr/toastr.min.css">

        <!-- <link href="<?php echo $siteURL; ?>plugins/magnific-popup/magnific-popup.css" rel="stylesheet" /> -->

        <link rel="stylesheet" href="<?php echo $siteURL; ?>dist/css/style.css" />
        <!-- <link rel="stylesheet" href="<?php echo $siteURL; ?>dist/css/media.css" /> -->

        <!-- NEW ADMIN STYLE -->
        <link rel="stylesheet" href="<?php echo $siteURL; ?>dist/css/admin.css">

        <?php
			for($i = 0; $i < count($styles); $i++)
			{
				echo '<link href="' . $styles[$i][0] . '" rel="stylesheet" ';

				for($j = 1; $j < count($styles[$i]); $j++)
				{
					echo ' ' . $styles[$i][$j];
				}

				echo '/>';
			}
		?>
        <!-- END Custom CSS-->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script src="https://cdn.tiny.cloud/1/4uldo68x4fiascken8eazutqjmjdvvwuvxcuje3zzimsc67b/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <script>var sSiteURL = '<?php echo $siteURL; ?>';</script>
        <style>
            /*.main-sidebar { background: #809d13!important; }*/

            /* .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active, .sidebar-light-primary .nav-sidebar>.nav-item>.nav-link.active { background: #44461b; }
            .sidebar .user-panel  { background: #44461b; } */
            </style>
	</head>
	<body class="hold-transition sidebar-mini">
		<div class="wrapper">
            <!-- Navbar -->
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <!-- Right navbar links -->
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item nav-item--menu">
                        <a class="nav-link" data-widget="pushmenu" href="javascript:void(0)" role="button"><i class="fas fa-bars"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $siteURL; ?>logout.html" role="button">
                            <i class="fas fa-power-off"></i>
                        </a>
                    </li>
                </ul>
            </nav>
		
            <!-- Main Sidebar Container -->
            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <!-- Brand Logo -->
                <a href="<?php echo $siteURL?>" class="main-sidebar__logo">
                    <img src="<?php echo $siteURL?>images/white-logo.svg" alt="NigelMunar" class="main-sidebar__logo-img">
                </a>
                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Sidebar user panel (optional) -->
                    <div class="user-panel">
                        <div class="image">
                            <div class="img-circle"><i class="fas fa-user"></i></div>
                        </div>
                        <div class="info">
                            <a href="<?php echo $siteURL; ?>edit-profile.html" class="d-block"><?php echo trim((isset($_SESSION['firstName']) ? trim($_SESSION['firstName']) : '') . ' ' . (isset($_SESSION['lastName']) ? trim($_SESSION['lastName']) : '')); ?></a>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Add icons to the links using the .nav-icon class
                                with font-awesome or any other icon font library -->
                        <li class="nav-item"><a href="<?php echo $siteURL; ?>" class="nav-link<?php echo (strlen($navName) === 0 || $navName === 'HOME' ? ' active' : ''); ?>"><i class="nav-icon fa fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a></li>

                        <?php 
                         if($administrator->hasPermission('Administrators', 'read') || $administrator->getSuperAdministrator()) 
                         {
                            ?>
                            <li class="nav-item">
                                <a href="<?php echo  $siteURL . 'administrators/" class="nav-link' . ($navName === 'ADMINISTRATORS' ? ' active' : '') ?> "><i class="nav-icon far fa-users"></i>
                                    <p>Administrators</p>
                                </a>
                            </li>

                        <? 
                            } 
                        ?>
                    </ul>
                </nav>

            </aside>
