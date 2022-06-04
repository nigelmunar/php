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

	if(isset($_GET['administrator']) && strlen($_GET['administrator']) > 0)
	{
		$activeAdministratorCode = $_GET['administrator'];
	}
	else
	{
		include ROOT_PATH . 'public_html/404.php';
	}

	$administratorDB = \AdministratorDatabaseFactory::create();

	$activeAdministrator = $administratorDB->getAdministrator($activeAdministratorCode);

	if(is_null($activeAdministrator))
	{
		include ROOT_PATH . 'public_html/404.php';
	}

	$pageURL = $siteURL . 'administrators/view.html?administrator=' . $activeAdministrator->getAdministratorCode();
	$canonicalURL = $pageURL;

	$permissions = $administratorDB->getPermissionsForAdministrator($activeAdministrator->getAdministratorID());


    require ROOT_PATH . 'application/config/pageVars.php';
	require ROOT_PATH . 'application/html-includes/admin/header.php';
?>
<style>
	.access { text-align: left; display: inline-block;}
	.access-icon { display: inline;}
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?php echo htmlspecialchars($activeAdministrator->getFirstName() . ' ' . $activeAdministrator->getLastName()); ?></h1>
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
    <section class="content admin">
        <div class="container-fluid">
			<div class="row">
				<div class="col-12 col-lg-3">
					<div class="card">
						<div class="card-body">
							<?php
								if($administrator->hasPermission('Administrators', 'update'))
								{
									echo '<a href="' . $siteURL . 'administrators/edit.html?administrator=' . htmlspecialchars($activeAdministrator->getAdministratorCode()) . '" class="btn btn-primary edit-btn">Edit</a>';
								}
							?>
							
							<div class="category-title pb-1">
								<h6>Name</h6>
							</div>

							<div class="card-text">
								<?php echo htmlspecialchars($activeAdministrator->getFirstName() . ' ' . $activeAdministrator->getLastName()); ?>
							</div>

							<hr>

							<div class="category-title pb-1">
								<h6>Email</h6>
							</div>

							<div class="card-text">
								<?php echo htmlspecialchars($activeAdministrator->getEmail()); ?>
							</div>

							<hr>

							<div class="category-title pb-1">
								<h6>Last Logged In</h6>
							</div>

							<div class="card-text">
								<?php echo (is_null($activeAdministrator->getDateTimeLastLoggedIn()) ? 'Never' : $activeAdministrator->getDateTimeLastLoggedIn()->format('d/m/Y g:ia')); ?>
							</div>

							<hr>
							
							<div class="category-title pb-1">
								<h6>Date Created</h6>
							</div>

							<div class="card-text">
								<?php echo $activeAdministrator->getDateTimeCreated()->format('d/m/Y'); ?>
							</div>

							<hr>
							
							<div class="category-title pb-1">
								<h6>Status</h6>
							</div>

							<div class="card-text">
								<?php echo ($activeAdministrator->getIsEnabled() ? '<span class="status bg-success text-highlight white">Enabled</span>' : '<span class="bg-danger text-highlight white">Disabled</span>'); ?>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-lg-9">
					<div class="row">
						<?php 
							for($i = 0; $i < count($permissions); $i++) 
							{ 
								?>
								<div class="col-xl-6 col-12">
									<div class="card permissions-card">
										<div class="card-header">
											<h4 class="card-title"><?php echo $permissions[$i]['name']; ?></h4>
											<span class="text-medium-1 danger line-height-2 text-uppercase">&nbsp;</span>  
												<div class="heading-elements">
													<ul class="list-inline mb-0 display-block">
														<li>
															<?php
																if($permissions[$i]['name'] === 'Blog Categories' || $permissions[$i]['name'] === 'Product Categories' || $permissions[$i]['name'] === 'Support Articles'  || $permissions[$i]['name'] === 'Stockists' || $permissions[$i]['name'] === 'Products' || $permissions[$i]['name'] === 'Blogs'  || $permissions[$i]['name'] === 'Pages' || $permissions[$i]['name'] === 'Option Pages') 
																{
																	if($permissions[$i]['create'] && $permissions[$i]['read'] && $permissions[$i]['update'] && $permissions[$i]['delete'] && $permissions[$i]['page_layout'])
																	{
																		echo '<a class="btn btn-md btn-success box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Granted</a>';
																	}
																	elseif($permissions[$i]['create'] || $permissions[$i]['read'] || $permissions[$i]['update'] || $permissions[$i]['delete'] || $permissions[$i]['page_layout'])
																	{
																		echo '<a class="btn btn-md btn-warning box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Partial</a>';
																	}
																	else
																	{
																		echo '<a class="btn btn-md btn-danger box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Denied</a>';
																	}
																}
																else
																{
																	if($permissions[$i]['create'] && $permissions[$i]['read'] && $permissions[$i]['update'] && $permissions[$i]['delete'])
																	{
																		echo '<a class="btn btn-md btn-success box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Granted</a>';
																	}
																	elseif($permissions[$i]['create'] || $permissions[$i]['read'] || $permissions[$i]['update'] || $permissions[$i]['delete'])
																	{
																		echo '<a class="btn btn-md btn-warning box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Partial</a>';
																	}
																	else
																	{
																		echo '<a class="btn btn-md btn-danger box-shadow-2 round btn-min-width pull-right permissions-btn-global" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '">Denied</a>';
																	}
																}	
															?>
														</li>
													</ul>
												</div>    
										</div>
										<div class="card-content collapse show">
											<div class="card-body pt-0 pb-1">
												<div class="row mb-1">
													<div class="col-6 col-sm-3 col-md-6 col-lg-3 border-right-blue-grey border-right-lighten-5 text-center">
														<p class="blue-grey lighten-2 mb-0">View</p>
														<p class="font-medium-5 text-bold-400"><?php
																if($permissions[$i]['read'])
																{
																	echo '<a class="permissions-btn text-success" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="r"><i class="fa fa-check-circle"></i></a>';
																}
																else
																{
																	echo '<a class="permissions-btn text-danger" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="r"><i class="fa fa-times-circle"></i></a>';
																}
															?></p>
													</div>
													<div class="col-6 col-sm-3 col-md-6 col-lg-3 border-right-blue-grey border-right-lighten-5 text-center">
														<p class="blue-grey lighten-2 mb-0">Create</p>
														<p class="font-medium-5 text-bold-400"><?php
																if($permissions[$i]['create'])
																{
																	echo '<a class="permissions-btn text-success" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="c"><i class="fa fa-check-circle"></i></a>';
																}
																else
																{
																	echo '<a class="permissions-btn text-danger" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="c"><i class="fa fa-times-circle"></i></a>';
																}
															?></p>
													</div>
													<div class="col-6 col-sm-3 col-md-6 col-lg-3 border-right-blue-grey border-right-lighten-5 text-center">
														<p class="blue-grey lighten-2 mb-0">Edit</p>
														<p class="font-medium-5 text-bold-400"><?php
																if($permissions[$i]['update'])
																{
																	echo '<a class="permissions-btn text-success" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="u"><i class="fa fa-check-circle"></i></a>';
																}
																else
																{
																	echo '<a class="permissions-btn text-danger" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="u"><i class="fa fa-times-circle"></i></a>';
																}
															?></p>
													</div>
													<div class="col-6 col-sm-3 col-md-6 col-lg-3 text-center">
														<p class="blue-grey lighten-2 mb-0">Delete</p>
														<p class="font-medium-5 text-bold-400"><?php
																if($permissions[$i]['delete'])
																{
																	echo '<a class="permissions-btn text-success" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="d"><i class="fa fa-check-circle"></i></a>';
																}
																else
																{
																	echo '<a class="permissions-btn text-danger" href="javascript:void(0);" data-permission="' . htmlspecialchars($permissions[$i]['code']) . '" data-access="d"><i class="fa fa-times-circle"></i></a>';
																}
															?></p>
													</div>
												</div>
												<div class="media d-flex">
													<div class="align-self-center">
														<div class="admin__last-updated">Last Updated: 
															<?php
																if(!is_null($permissions[$i]['lastUpdated']))
																{
																	echo '<span class="blue-grey last-updated-by">' . $permissions[$i]['lastUpdated']->format('jS F Y g:ia') . ' by ' . htmlspecialchars($permissions[$i]['administrator']) . '</span>';
																}
															?>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div><?php } ?>
								
								
								<?php 
							

						echo '<div class="col-12">';
						echo '<pre id="permissions-log">';

						$pdo = \PDOFactory::getConnection();

						$stmt = $pdo->prepare('
							SELECT REPLACE(REPLACE(REPLACE(`log_text`, \'{ACTIONINGADMIN}\', TRIM(CONCAT(u2.`first_name`, \' \', u2.`last_name`))), \'{TARGETADMIN}\', TRIM(CONCAT(u.`first_name`, \' \', u.`last_name`))), \'{PERMISSIONNAME}\', p.`permission_name`) AS `log_text`, l.`date_time_created`
							FROM `permission_log` AS l
							INNER JOIN `permissions` AS p ON l.`permission_id` = p.`permission_id`
							INNER JOIN `administrators` AS u ON l.`target_administrator_id` = u.`administrator_id`
							INNER JOIN `administrators` AS u2 ON l.`actioning_administrator_id` = u2.`administrator_id`
							WHERE `target_administrator_id` = :administrator_id
							ORDER BY `date_time_created` DESC');

						$stmt->bindValue(':administrator_id', $activeAdministrator->getAdministratorID(), \PDO::PARAM_INT);

						$stmt->execute();

						while($row = $stmt->fetch())
						{
							echo datetimeFromDB($row['date_time_created'])->format('[Y-m-d H:i:s] ') . (string)$row['log_text'] . "\n";
						}

						echo '</pre>';
						echo '</div>';
						?>

						<script>
							const monthNames = ["January", "February", "March", "April", "May", "June","July", "August", "September", "October", "November", "December"];

							$(function()
							{
								$('.permissions-btn-global').on('click', 
									function(e)
									{
										e.preventDefault();

										<?php 
											if(($activeAdministrator->getAdministratorID() !== $administrator->getAdministratorID() && $administrator->hasPermission('Administrators', 'update')) || $administrator->getSuperAdministrator()) 
											{
												?>
												$parent = $(this).parents('.permissions-card:first');

												if($(this).hasClass('btn-success'))
												{
													$(this).toggleClass('btn-success btn-danger');
													$(this).html('Denied');

													$parent.find('.fa-check-circle').toggleClass('fa-check-circle fa-times-circle').parent().toggleClass('text-success text-danger');
												}
												else
												{
													$(this).removeClass('btn-danger btn-warning');
													$(this).addClass('btn-success');
													$(this).html('Granted');

													$parent.find('.fa-times-circle').toggleClass('fa-check-circle fa-times-circle').parent().toggleClass('text-success text-danger');
												}

												$.post('/admin/ajax/update-bulk-permissions.html', 
													{ 
														'permission': $(this).data('permission'), 
														'administrator': '<?php echo htmlspecialchars($activeAdministrator->getAdministratorCode()); ?>', 
														'grant' : $(this).hasClass('btn-success')
													},
													function(json)
													{
														$('#permissions-log').html(json.log + $('#permissions-log').html());

														var date = new Date(json.date);

														$parent.find('.last-updated-by').html(date.getDate() + ordinal(date.getDate()) + ' ' + monthNames[date.getMonth()] + ' ' + date.getFullYear() + ' ' + formatAMPM(date) + ' by ' + json.administrator);
													}
												);
												<?php
											}
										?>
											

										return false;
									}
								);

								$('.permissions-btn').on('click', 
									function(e)
									{
										e.preventDefault();

										<?php 
											if(($activeAdministrator->getAdministratorID() !== $administrator->getAdministratorID() && $administrator->hasPermission('Administrators', 'update')) || $administrator->getSuperAdministrator()) 
											{
												?>
												$(this).toggleClass('text-success text-danger');
												$(this).find('i').toggleClass('fa-check-circle fa-times-circle');

												$parent = $(this).parents('.permissions-card:first');

												var granted = $parent.find('.fa-check-circle').length;
												var denied  = $parent.find('.fa-times-circle').length;

												if(granted > 0 && denied == 0)
												{
													$parent.find('.permissions-btn-global').removeClass('btn-danger btn-warning').addClass('btn-success').html('Granted');
												}
												else if(granted == 0 && denied > 0)
												{
													$parent.find('.permissions-btn-global').removeClass('btn-success btn-warning').addClass('btn-danger').html('Denied');
												}
												else
												{
													$parent.find('.permissions-btn-global').removeClass('btn-success btn-danger').addClass('btn-warning').html('Partial');
												}

												$.post('/admin/ajax/update-permissions.html', 
													{ 
														'permission': $(this).data('permission'), 
														'administrator': '<?php echo htmlspecialchars($activeAdministrator->getAdministratorCode()); ?>', 
														'access' : $(this).data('access'), 
														'grant' : $(this).hasClass('text-success')
													},
													function(json)
													{
														$('#permissions-log').html(json.log + $('#permissions-log').html());

														var date = new Date(json.date);

														$parent.find('.last-updated-by').html(date.getDate() + ordinal(date.getDate()) + ' ' + monthNames[date.getMonth()] + ' ' + date.getFullYear() + ' ' + formatAMPM(date) + ' by ' + json.administrator);
													}
												);
												<?php
											}
										?>

										return false;
									}
								);
							});

							
					

							function appendLeadingZeroes(n){
								if(n <= 9){
									return "0" + n;
								}
								return n
							}

							function ordinal(number) {
								var d = number % 10;
								return (~~ (number % 100 / 10) === 1) ? 'th' :
										(d === 1) ? 'st' :
										(d === 2) ? 'nd' :
										(d === 3) ? 'rd' : 'th';
							}

							function formatAMPM(date) {
								var hours = date.getHours();
								var minutes = date.getMinutes();
								var ampm = hours >= 12 ? 'pm' : 'am';
								hours = hours % 12;
								hours = hours ? hours : 12; // the hour '0' should be '12'
								minutes = minutes < 10 ? '0'+minutes : minutes;
								var strTime = hours + ':' + minutes + ampm;
								return strTime;
							}
						</script>

					</div>
				</div>
			</div>
		</div>	
	</section>
</div>

<?php
    require ROOT_PATH . 'application/html-includes/admin/footer.php';