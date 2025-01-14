<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditPackageId'></input>
<div class='gallery'>
	<div>
		<select id='sltNewPackageGroup' class='resizeVertical' size='10' multiple='true'>
			<?php echoPackageGroupOptions($cl); ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button onclick='hideDialog()'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='addPackageToGroup(txtEditPackageId.value, getSelectedSelectBoxValues("sltNewPackageGroup",true))'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
