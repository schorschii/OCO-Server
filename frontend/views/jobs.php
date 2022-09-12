<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<h1><img src='img/job.dyn.svg'><span id='page-title'><?php echo LANG('jobs'); ?></span></h1>

<div class='controls'>
	<button onclick='refreshContentExplorer("views/job-containers.php")'><img src='img/container.dyn.svg'>&nbsp;<?php echo LANG('job_containers'); ?></button>
	<span class='filler'></span>
	<button onclick='refreshContentExplorer("views/deployment-rules.php")'><img src='img/rule.dyn.svg'>&nbsp;<?php echo LANG('deployment_rules'); ?></button>
</div>
