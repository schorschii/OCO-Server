<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtMobileDeviceCommandId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtMobileDeviceCommandName' autofocus='true' value='<?php echo date('Y-m-d H:i:s'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('mobile_device'); ?></th>
		<td>
			<select id='sltEditMobileDeviceCommandMobileDeviceId' class='fullwidth'>
				<?php foreach($db->selectAllMobileDevice() as $md) { ?>
					<option value='<?php echo $md->id; ?>'><?php echo htmlspecialchars($md->serial); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('command'); ?></th>
		<td>
			<select id='sltEditMobileDeviceCommandCommand' class='fullwidth'>
				<option>InstallProfile</option>
				<option>DeviceLock</option>
				<option>ClearPasscode</option>
				<option>EnableLostMode</option>
				<option>DisableLostMode</option>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('payload'); ?></th>
		<td>
			<input type='file' id='fleEditMobileDeviceCommandPayload' class='fullwidth'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('notes'); ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtMobileDeviceCommandNotes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnUpdateDeploymentRule' class='primary' onclick='editMobileDeviceCommand(
		txtMobileDeviceCommandId.value,
		txtMobileDeviceCommandName.value,
		sltEditMobileDeviceCommandMobileDeviceId.value,
		sltEditMobileDeviceCommandCommand.value,
		fleEditMobileDeviceCommandPayload.files,
		txtMobileDeviceCommandNotes.value,
	)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnUpdateDeploymentRule'><?php echo LANG('change'); ?></span></button>
</div>
