<?php
$SUBVIEW = 1;
require_once('../../../loader.inc.php');
require_once('../../session.inc.php');

try {
	$md = $cl->getMobileDevice($_GET['id'] ?? -1);
} catch(Exception $e) {
	die($e->getMessage());
}
?>

<input type='hidden' name='id' value='<?php echo htmlspecialchars($md->id); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('command'); ?></th>
		<td>
			<select name='command' class='fullwidth'>
				<option value='' selected disabled><?php echo LANG('please_select_placeholder'); ?></option>

				<?php if($md->getOsType() == Models\MobileDevice::OS_TYPE_IOS) { ?>

				<option value='ScheduleOSUpdateScan' parameter=''><?php echo LANG('schedule_os_update_scan'); ?></option>
				<option value='AvailableOSUpdates' parameter=''><?php echo LANG('list_available_os_updates'); ?></option>
				<option value='ScheduleOSUpdate' parameter=''><?php echo LANG('schedule_os_update'); ?></option>
				<option value='OSUpdateStatus' parameter=''><?php echo LANG('get_os_update_status'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='DeviceLock' parameter=''><?php echo LANG('lock_device'); ?></option>
				<option value='ClearPasscode' parameter=''><?php echo LANG('clear_passcode'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='EnableLostMode' parameter='message'><?php echo LANG('enable_lost_mode'); ?></option>
				<option value='PlayLostModeSound' parameter=''><?php echo LANG('play_lost_mode_sound'); ?></option>
				<option value='DisableLostMode' parameter=''><?php echo LANG('disable_lost_mode'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='RestartDevice' parameter=''><?php echo LANG('reboot'); ?></option>
				<option value='ShutDownDevice' parameter=''><?php echo LANG('shutdown'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='EraseDevice' parameter=''><?php echo LANG('erase_device'); ?></option>

				<?php } elseif($md->getOsType() == Models\MobileDevice::OS_TYPE_ANDROID) { ?>

				<option value='LOCK' parameter=''><?php echo LANG('lock_device'); ?></option>
				<option value='RESET_PASSWORD' parameter='password'><?php echo LANG('clear_passcode'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='RELINQUISH_OWNERSHIP' parameter=''><?php echo LANG('relinquish_ownership'); ?></option>
				<option value='CLEAR_APP_DATA' parameter=''><?php echo LANG('clear_app_data'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='START_LOST_MODE' parameter='message'><?php echo LANG('enable_lost_mode'); ?></option>
				<option value='STOP_LOST_MODE' parameter=''><?php echo LANG('disable_lost_mode'); ?></option>
				<option value='' disabled>──────────</option>
				<option value='REBOOT' parameter=''><?php echo LANG('reboot'); ?></option>

				<?php } ?>
			</select>
		</td>
	</tr>
	<tr class='parameter' style='display:none'>
		<th class='parameterName'><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth parameter' autocomplete='new-password' name=''></input></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='send'><img src='img/send.white.svg'>&nbsp;<span><?php echo LANG('send'); ?></span></button>
</div>
