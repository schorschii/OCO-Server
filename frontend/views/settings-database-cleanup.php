<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('database_cleanup'); ?></span></h1>
</div>

<div class='actionmenu'>
	<button onclick='cleanupRecognizedSoftware(this)'>
		<img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('delete_recognized_software_without_installations'); ?>
	</button>
	<button onclick='cleanupDomainUsers(this)'>
		<img src='img/refresh.dyn.svg'>&nbsp;<?php echo LANG('delete_domain_users_without_logons'); ?>
	</button>
</div>
