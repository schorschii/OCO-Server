<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtManagedAppId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('groups'); ?></th>
		<td>
			<select id='sltNewMobileDeviceGroup' class='fullwidth' size='5' multiple='true' autofocus='true'>
				<?php echoMobileDeviceGroupOptions($cl); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('options'); ?></th>
		<td>
			<label><input type='checkbox' id='chkRemovable' checked='true'></input><?php echo LANG('removable'); ?></label>
			<br>
			<label><input type='checkbox' id='chkDisableCloudBackup'></input><?php echo LANG('disable_cloud_backup'); ?></label>
			<br>
			<label><input type='checkbox' id='chkRemoveOnMdmRemove' checked='true'></input><?php echo LANG('remove_when_leaving_mdm'); ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('app_config_json'); ?></th>
		<td>
			<textarea id='txtManagedAppConfig' class='fullwidth'></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog()'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='assignManagedAppToGroup(
		txtManagedAppId.value,
		getSelectedSelectBoxValues("sltNewMobileDeviceGroup",true),
		chkRemovable.checked ? 1 : 0,
		chkDisableCloudBackup.checked ? 1 : 0,
		chkRemoveOnMdmRemove.checked ? 1 : 0,
		txtManagedAppConfig.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
