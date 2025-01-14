<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtCreateComputerId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('hostname'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateComputerHostname' autofocus='true'></input></td>
	</tr>
	<tr>
		<th></th>
		<td>
			<div class='alert info' style='margin-top:0px;width:350px;min-width:100%'>
				<?php echo LANG('hostname_info'); ?>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtCreateComputerNotes' rows='5'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('individual_agent_key'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtCreateComputerAgentKey' placeholder='<?php echo LANG('optional'); ?>'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnCreateComputer' class='primary' onclick='createComputer(
		txtCreateComputerHostname.value,
		txtCreateComputerNotes.value,
		txtCreateComputerAgentKey.value
		)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
