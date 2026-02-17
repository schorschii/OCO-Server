<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION);
} catch(PermissionException $e) {
	http_response_code(403);
	die(LANG('permission_denied'));
} catch(InvalidRequestException $e) {
	http_response_code(400);
	die($e->getMessage());
}
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('client_api_enabled'); ?></th>
		<td><input type='checkbox' name='client-api-enabled' autofocus='true' <?php if($db->settings->get('client-api-enabled')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('client_api_key'); ?></th>
		<td><input type='text' name='client-api-key' placeholder='<?php echo LANG('optional'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_registration_enabled'); ?></th>
		<td><input type='checkbox' name='agent-registration-enabled' <?php if($db->settings->get('agent-self-registration-enabled')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_registration_key'); ?></th>
		<td><input type='text' name='agent-registration-key' placeholder='<?php echo LANG('optional'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('assume_computer_offline_after'); ?></th>
		<td><input type='number' min='0' name='assume-computer-offline-after' value='<?php echo htmlspecialchars($db->settings->get('computer-offline-seconds')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('wol_shutdown_expiry_seconds'); ?></th>
		<td><input type='number' min='0' name='wol-shutdown-expiry' value='<?php echo htmlspecialchars($db->settings->get('wol-shutdown-expiry')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_update_interval'); ?></th>
		<td><input type='number' min='0' name='agent-update-interval' value='<?php echo htmlspecialchars($db->settings->get('agent-update-interval')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_succeeded_jobs_after'); ?></th>
		<td><input type='number' min='0' name='purge-succeeded-jobs-after' value='<?php echo htmlspecialchars($db->settings->get('purge-succeeded-jobs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_failed_jobs_after'); ?></th>
		<td><input type='number' min='0' name='purge-failed-jobs-after' value='<?php echo htmlspecialchars($db->settings->get('purge-failed-jobs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_domain_user_logons_after'); ?></th>
		<td><input type='number' min='0' name='purge-domain-user-logons-after' value='<?php echo htmlspecialchars($db->settings->get('purge-domain-user-logons-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_events_after'); ?></th>
		<td><input type='number' min='0' name='purge-events-after' value='<?php echo htmlspecialchars($db->settings->get('purge-events-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('log_level'); ?></th>
		<td>
			<select name='log-level'>
				<?php foreach(Models\Log::LEVELS as $key => $title) { ?>
					<option value='<?php echo $key; ?>' <?php if($db->settings->get('log-level')==$key) echo 'selected'; ?>><?php echo htmlspecialchars($title); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_logs_after'); ?></th>
		<td><input type='number' min='0' name='purge-logs-after' value='<?php echo htmlspecialchars($db->settings->get('purge-logs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('keep_inactive_screens'); ?></th>
		<td><input type='checkbox' name='computer-keep-inactive-screens' <?php if($db->settings->get('computer-keep-inactive-screens')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('self_service_enabled'); ?></th>
		<td><input type='checkbox' name='self-service-enabled' <?php if($db->settings->get('self-service-enabled')) echo 'checked'; ?>></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
