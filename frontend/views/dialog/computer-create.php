<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('hostname'); ?></th>
		<td>
			<input type='text' name='hostname' class='fullwidth' autocomplete='new-password' autofocus='true'></input>
		</td>
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
		<td>
			<textarea name='notes' class='fullwidth' autocomplete='new-password' rows='5'></textarea>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('individual_agent_key'); ?></th>
		<td>
			<input type='text' name='agent_key' class='fullwidth' autocomplete='new-password' placeholder='<?php echo LANG('optional'); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('individual_server_key'); ?></th>
		<td>
			<input type='text' name='server_key' class='fullwidth' autocomplete='new-password' placeholder='<?php echo LANG('optional'); ?>'></input>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='create'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
