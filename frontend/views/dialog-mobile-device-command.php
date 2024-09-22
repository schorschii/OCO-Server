<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtMobileDeviceId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('command'); ?></th>
		<td>
			<select id='sltMobileDeviceCommand' class='fullwidth' onclick='showMobileDeviceCommandParameter(this.selectedOptions[0])'>
				<option value='' selected disabled><?php echo LANG('please_select_placeholder'); ?></option>
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
				<option value='EraseDevice' parameter=''><?php echo LANG('erase_device'); ?></option>
			</select>
		</td>
	</tr>
	<tr id='trCommandParameter' style='display:none'>
		<th id='thCommandParameterName'><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtMobileDeviceCommandParameter' name='parameter'></input></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateDeploymentRule' class='primary' onclick='
	let params = {};
	params[txtMobileDeviceCommandParameter.name] = txtMobileDeviceCommandParameter.value;
	sendMobileDeviceCommand(
		txtMobileDeviceId.value,
		sltMobileDeviceCommand.value,
		params
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateDeploymentRule'><?php echo LANG('send'); ?></span></button>
</div>
