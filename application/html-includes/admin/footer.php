<?php
    declare(strict_types = 1);
?>
            <footer class="main-footer">
                <strong>Copyright &copy; 2022 - <?php echo date("Y"); ?> Nigelmunar, All rights reserved.</strong>
            </footer>
        </div>
        <!-- ./wrapper -->

        <!-- jQuery -->
        <script src="<?php echo $siteURL; ?>plugins/jquery/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="<?php echo $siteURL; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

        <script src="<?php echo $siteURL; ?>plugins/toastr/toastr.min.js"></script>
        <!-- AdminLTE App -->
        <script src="<?php echo $siteURL; ?>dist/js/adminlte.min.js"></script>
        <script src="<?php echo $siteURL; ?>dist/js/cookies.min.js"></script>
        
        <!-- <script src="<?php echo $siteURL; ?>admin/plugins/magnific-popup/jquery.magnific-popup.min.js"></script> -->
        <!-- <script type="text/javascript" src="/admin/assets/js/general.min.js?v=0.1"></script> -->

        <script>
            $(function() {
                <?php
                    if(isset($_SESSION['Page Saved']) && $_SESSION['Page Saved'] === 'true')
                    {
                        echo 'toastr.success(\'Page saved successfully.\'); ' . "\n";

                        unset($_SESSION['Page Saved']);
                    }

                ?>
            });
        </script>

<script>
            $(function() {
                <?php

                    if(isset($_SESSION['Profile Changed']) && $_SESSION['Profile Changed'] === 'true')
                    {
                        echo 'toastr.success(\'Profile picture has been save.\'); ' . "\n";

                        unset($_SESSION['Profile Changed']);
                    }

                    if(isset($_SESSION['Forum Deleted']) && $_SESSION['Forum Deleted'] === 'true')
                    {
                        echo 'toastr.success(\'Forum has been deleted.\'); ' . "\n";

                        unset($_SESSION['Forum Deleted']);
                    }

                    if(isset($_SESSION['Attribute Value']) && $_SESSION['Attribute Value'] === 'true')
                    {
                        echo 'toastr.success(\'Attributes values have been saved.\'); ' . "\n";

                        unset($_SESSION['Attribute Value']);
                    }

                    if(isset($_SESSION['Attribute Value Delete']) && $_SESSION['Attribute Value Delete'] === 'true')
                    {
                        echo 'toastr.success(\'Attribute value have been deleted.\'); ' . "\n";

                        unset($_SESSION['Attribute Value Delete']);
                    }
                ?>
            });
        </script>
        
        <!-- Page specific script -->
        <?php
			for($i = 0; $i < count($scripts); $i++)
			{
				echo '<script src="' . $scripts[$i][0] . '" ';

				for($j = 1; $j < count($scripts[$i]); $j++)
				{
					echo ' ' . $scripts[$i][$j];
				}

				echo '></script>';
			}
			
            include ROOT_PATH . 'application/html-includes/admin-bar.php';
		?>   
        
    </body>
</html>