<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');
?>

<p style='max-width:450px'><?php echo LANG('uninstall_job_container_will_be_created'); ?></p>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo LANG('uninstall').' '.date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' rows='4' name='notes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' name='start_date' value='<?php echo date('Y-m-d'); ?>'></input>
				<input type='time' class='' name='start_time' value='<?php echo date('H:i:s'); ?>'></input>
			</div>
			<div>
				<label><input type='checkbox' name='wol' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' name='shutdown_waked' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' class='' name='end_date'></input>
				<input type='time' class='' name='end_time'></input>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input name='priority' type='range' min='-10' max='10' value='0'>
				<span class='priorityPreview'>0</span>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('timeout_for_reboot'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input type='number' name='restart_timeout' value='<?php echo htmlspecialchars($db->settings->get('default-restart-timeout')); ?>' min='-1'></input>
				<span><?php echo LANG('minutes'); ?></span>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='uninstall'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('uninstall'); ?></button>
</div>
