<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditComputerId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('hostname'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditComputerHostname' autofocus='true'></input></td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert warning' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('new_hostname_warning'); ?>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditComputerNotes' rows='5'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateComputer' class='primary' onclick='editComputer(txtEditComputerId.value, txtEditComputerHostname.value, txtEditComputerNotes.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
