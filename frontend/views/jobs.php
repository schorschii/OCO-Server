<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<h1><img src='img/job.dyn.svg'><span id='page-title'><?php echo LANG('jobs'); ?></span></h1>

<div class='actionmenu'>
	<a <?php echo explorerLink('views/job-containers.php?selfservice=1'); ?>>&rarr;&nbsp;<?php echo LANG('self_service_job_containers'); ?></a>
	<a <?php echo explorerLink('views/job-containers.php'); ?>>&rarr;&nbsp;<?php echo LANG('system_users_job_containers'); ?></a>
	<a <?php echo explorerLink('views/deployment-rules.php'); ?>>&rarr;&nbsp;<?php echo LANG('deployment_rules'); ?></a>
</div>

