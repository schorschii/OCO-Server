<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

$c = null;
try {
	$c = $cl->getJobContainer($_GET['job_container_id'] ?? -1);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('not_found'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('permission_denied'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<input type='hidden' name='job_container_id' value='<?php echo htmlspecialchars($c->id,ENT_QUOTES); ?>'></input>
<input type='hidden' name='job_ids' value='<?php echo htmlspecialchars($_GET['job_ids']??'',ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('mode'); ?></th>
		<td>
			<label><input type='checkbox' name='create_new_job_container' autofocus='true'><?php echo LANG('create_new_job_container'); ?></label>
			<div style='max-width:400px' class='hint'><?php echo LANG('renew_jobs_description'); ?></div>
		</td>
	</tr>
	<tbody class='newJobContainer' style='display:none'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' value='<?php echo htmlspecialchars($c->name.' - '.LANG('renew'),ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea class='fullwidth' rows='4' name='notes'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' name='start_date' value='<?php echo date('Y-m-d'); ?>'></input>
				<input type='time' name='start_time' value='<?php echo date('H:i:s'); ?>'></input>
			</div>
			<div>
				<label><input type='checkbox' name='wol' <?php if(!empty($db->settings->get('default-use-wol'))) echo 'checked'; ?>><?php echo LANG('send_wol'); ?></label>
				<br/>
				<label title='<?php echo LANG('shutdown_waked_after_completion'); ?>'><input type='checkbox' name='shutdown_waked_after_completion' <?php if(!empty($db->settings->get('default-shutdown-waked-after-completion'))) echo 'checked'; else echo 'disabled' ?>><?php echo LANG('shutdown_waked_computers'); ?></label>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td>
			<div class='stretchInput'>
				<input type='date' name='end_date'></input>
				<input type='time' name='end_time'></input>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel'>
				<input name='priority' type='range' min='-10' max='10' value='0'>
				<span class='priority_preview'>0</span>
			</div>
		</td>
	</tr>
	</tbody>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='renew'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('renew'); ?></button>
</div>
