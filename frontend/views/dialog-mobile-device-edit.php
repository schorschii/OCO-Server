<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtEditMobileDeviceId'>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td>
			<textarea id='txtEditMobileDeviceNotes' class='fullwidth' autofocus='true'></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditMobileDevice' class='primary' onclick='editMobileDevice(txtEditMobileDeviceId.value, txtEditMobileDeviceNotes.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
</div>
