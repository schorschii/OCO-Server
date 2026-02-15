<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<input type='hidden' name='ids' value='<?php echo htmlspecialchars(implode(',',$_GET['id'])); ?>'></input>
<div class='gallery'>
	<div>
		<select name='mobile_device_group_id' class='resizeVertical' size='10' multiple='true'>
			<?php Html::buildGroupOptions($cl, new Models\MobileDeviceGroup()); ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='assign'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
