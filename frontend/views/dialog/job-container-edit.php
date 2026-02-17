<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$jc = $cl->getJobContainer($_GET['id'] ?? -1);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	http_response_code(404);
	die(LANG('not_found'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($jc->id,ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo htmlspecialchars($jc->name,ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('state'); ?></th>
		<td><label><input type='checkbox' name='enabled' <?php if($jc->enabled) echo 'checked'; ?>></input>&nbsp;<?php echo LANG('enabled'); ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG('start'); ?></th>
		<td class='dualInput'>
			<input type='date' class='fullwidth' name='start_date' value='<?php echo htmlspecialchars(explode(' ',$jc->start_time)[0],ENT_QUOTES); ?>'></input>
			<input type='time' class='fullwidth' name='start_time' step='1' value='<?php echo htmlspecialchars(explode(' ',$jc->start_time)[1]??'',ENT_QUOTES); ?>'></input>
			<button class='small invisible' disabled='true' title='<?php echo LANG('remove_end_time'); ?>'><img src='img/close.dyn.svg'></button>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('end'); ?></th>
		<td class='dualInput'>
			<input type='date' class='fullwidth' name='end_date' value='<?php echo htmlspecialchars(explode(' ',$jc->end_time)[0],ENT_QUOTES); ?>'></input>
			<input type='time' class='fullwidth' name='end_time' step='1' value='<?php echo htmlspecialchars(explode(' ',$jc->end_time)[1]??'',ENT_QUOTES); ?>'></input>
			<button class='small' title='<?php echo LANG('remove_end_time'); ?>'><img src='img/close.dyn.svg'></button>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' name='notes'><?php echo htmlspecialchars($jc->notes); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_ip_range'); ?></th>
		<td>
			<input type='text' class='fullwidth' name='agent_ip_ranges' placeholder='<?php echo LANG('example').':'; ?> 192.168.2.0/24, 10.0.0.0/8' value='<?php echo htmlspecialchars($jc->agent_ip_ranges,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('time_frame'); ?></th>
		<td>
			<input type='text' class='fullwidth' name='time_frames' placeholder='<?php echo LANG('example').':'; ?> 6:00-8:00, SUN 0:00-23:59' value='<?php echo htmlspecialchars($jc->time_frames,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('priority'); ?></th>
		<td>
			<div class='inputWithLabel' title='<?php echo LANG('priority_description'); ?>'>
				<input name='priority' type='range' min='-10' max='10' value='<?php echo htmlspecialchars($jc->priority,ENT_QUOTES); ?>'>
				<div class='priority_preview'><?php echo htmlspecialchars($jc->priority); ?></div>
			</div>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('sequence_mode'); ?></th>
		<td>
			<label>
				<input type='checkbox' name='sequence_mode' name='sequence_mode' value='<?php echo Models\JobContainer::SEQUENCE_MODE_ABORT_AFTER_FAILED; ?>' <?php if($jc->sequence_mode) echo 'checked'; ?>>&nbsp;<?php echo LANG('abort_after_failed'); ?>
			</label>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
