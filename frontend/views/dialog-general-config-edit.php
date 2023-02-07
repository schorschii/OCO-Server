<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('client_api_enabled'); ?></th>
		<td><input type='checkbox' id='chkClientApiEnabled' autofocus='true' <?php if($db->selectSettingByKey('client-api-enabled')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('client_api_key'); ?></th>
		<td><input type='text' id='txtClientApiKey' placeholder='<?php echo LANG('optional'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_registration_enabled'); ?></th>
		<td><input type='checkbox' id='chkAgentRegistrationEnabled' <?php if($db->selectSettingByKey('agent-self-registration-enabled')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_registration_key'); ?></th>
		<td><input type='text' id='txtAgentRegistrationKey' placeholder='<?php echo LANG('optional'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('assume_computer_offline_after'); ?></th>
		<td><input type='number' min='0' id='txtAssumeComputerOfflineAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('computer-offline-seconds')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('wol_shutdown_expiry_seconds'); ?></th>
		<td><input type='number' min='0' id='txtWolShutdownExpiry' value='<?php echo htmlspecialchars($db->selectSettingByKey('wol-shutdown-expiry')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('agent_update_interval'); ?></th>
		<td><input type='number' min='0' id='txtAgentUpdateInterval' value='<?php echo htmlspecialchars($db->selectSettingByKey('agent-update-interval')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_succeeded_jobs_after'); ?></th>
		<td><input type='number' min='0' id='txtPurgeSucceededJobsAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('purge-succeeded-jobs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_failed_jobs_after'); ?></th>
		<td><input type='number' min='0' id='txtPurgeFailedJobsAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('purge-failed-jobs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_logs_after'); ?></th>
		<td><input type='number' min='0' id='txtPurgeLogsAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('purge-logs-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_domain_user_logons_after'); ?></th>
		<td><input type='number' min='0' id='txtPurgeDomainUserLogonsAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('purge-domain-user-logons-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('purge_events_after'); ?></th>
		<td><input type='number' min='0' id='txtPurgeEventsAfter' value='<?php echo htmlspecialchars($db->selectSettingByKey('purge-events-after')); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('keep_inactive_screens'); ?></th>
		<td><input type='checkbox' id='chkKeepInactiveScreens' <?php if($db->selectSettingByKey('computer-keep-inactive-screens')) echo 'checked'; ?>></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('self_service_enabled'); ?></th>
		<td><input type='checkbox' id='chkSelfServiceEnabled' <?php if($db->selectSettingByKey('self-service-enabled')) echo 'checked'; ?>></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='editGeneralConfig(
		chkClientApiEnabled.checked ? 1 : 0,
		txtClientApiKey.value,
		chkAgentRegistrationEnabled.checked ? 1 : 0,
		txtAgentRegistrationKey.value,
		txtAssumeComputerOfflineAfter.value,
		txtWolShutdownExpiry.value,
		txtAgentUpdateInterval.value,
		txtPurgeSucceededJobsAfter.value,
		txtPurgeFailedJobsAfter.value,
		txtPurgeLogsAfter.value,
		txtPurgeDomainUserLogonsAfter.value,
		txtPurgeEventsAfter.value,
		chkKeepInactiveScreens.checked ? 1 : 0,
		chkSelfServiceEnabled.checked ? 1 : 0
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
