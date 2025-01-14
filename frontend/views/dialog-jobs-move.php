<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditJobId'></input>
<div class='gallery'>
	<div>
		<select id='sltNewJobContainer' class='resizeVertical' size='10'>
			<?php
			foreach($cl->getJobContainers(false) as $container) {
				if(!$cl->checkPermission($container, PermissionManager::METHOD_WRITE, false)) continue;
			?>
				<option value='<?php echo $container->id; ?>'><?php echo htmlspecialchars($container->name); ?></option>
			<?php } ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button onclick='hideDialog()'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='moveStaticJobToJobContainer(txtEditJobId.value, sltNewJobContainer.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('move'); ?></button>
</div>
