<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditReportId'></input>
<div class='gallery'>
	<div>
		<select id='sltNewReportGroup' class='resizeVertical' size='10'>
			<?php Html::buildGroupOptions($cl, new Models\ReportGroup()); ?>
		</select>
	</div>
</div>

<div class='controls right'>
	<button onclick='hideDialog()'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='moveReportToGroup(txtEditReportId.value, sltNewReportGroup.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('move'); ?></button>
</div>
