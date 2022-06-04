<?php
	declare(strict_types = 1);
?>

<ol class="breadcrumb float-sm-right">
	<li class="breadcrumb-item<?php echo (count($breadcrumb) === 0 ? ' active' : '')?>"><a href="<?php echo $siteURL; ?>/">Home</a></li>
	<?php 
		for($i = 0; $i < count($breadcrumb); $i++)
		{
			echo "<li class=\"breadcrumb-item" . ($i === (count($breadcrumb) - 1) ? ' active' : '') . "\">\n";
			
			if($i < (count($breadcrumb) - 1))
			{
				echo '<a href="' . $siteURL . $breadcrumb[$i][1] . '">' . $breadcrumb[$i][0] . "</a>\n";
			}
			else
			{
				echo $breadcrumb[$i][0];
			}

			echo "</li>\n";
		}
	?>
</ol>