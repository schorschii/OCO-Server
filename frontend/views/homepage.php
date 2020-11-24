<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../lib/loader.php');
require_once(__DIR__.'/../session.php');
?>

<div id='homepage'>
	<img src='img/logo.dyn.svg'>
	<p>
		<div class='title'><?php echo LANG['app_name_frontpage']; ?></div>
		<div class='subtitle'><?php echo LANG['app_subtitle']; ?></div>
	</p>
	<p>
		<div class='subtitle2'><?php echo LANG['version'].' '.APP_VERSION; ?></div>
	</p>

	<table class='list fullwidth margintop fixed'>
		<tr>
			<th class='center'><img src='img/users.dyn.svg'><br><?php echo count($db->getAllDomainuser()).' '.LANG['users']; ?></th>
			<th class='center'><img src='img/computer.dyn.svg'><br><?php echo count($db->getAllComputer()).' '.LANG['computer']; ?></th>
			<th class='center'><img src='img/package.dyn.svg'><br><?php echo count($db->getAllPackage()).' '.LANG['packages']; ?></th>
			<th class='center'><img src='img/job.dyn.svg'><br><?php echo count($db->getAllJobcontainer()).' '.LANG['job_container']; ?></th>
			<th class='center'><img src='img/report.dyn.svg'><br><?php echo count($db->getAllReport()).' '.LANG['reports']; ?></th>
		</tr>
	</table>
</div>
