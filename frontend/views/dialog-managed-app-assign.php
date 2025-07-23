<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$configs = null;
try {
	if(empty($_GET['id']) || !is_array($_GET['id']))
		throw new Exception('GET id[] missing');
	$ma = $db->selectManagedApp($_GET['id'][0]);
	if(count($_GET['id']) === 1) {
		$configs = $ma->getConfigurations();
	}
} catch(Exception $e) {
	die($e->getMessage());
}
?>

<input type='hidden' id='txtManagedAppId' value='<?php echo htmlspecialchars(implode(',',$_GET['id'])); ?>'></input>
<table class='fullwidth aligned'>
	<tr>
		<th><?php echo LANG('groups'); ?></th>
		<td>
			<select id='sltNewMobileDeviceGroup' class='fullwidth' size='5' multiple='true' autofocus='true'>
				<?php Html::buildGroupOptions($cl, new Models\MobileDeviceGroup()); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('options'); ?></th>
		<td>
			<?php if($ma->type == Models\ManagedApp::TYPE_IOS) { ?>
				<label><input type='checkbox' id='chkRemovable' checked='true'></input><?php echo LANG('removable'); ?></label>
				<br>
				<label><input type='checkbox' id='chkDisableCloudBackup'></input><?php echo LANG('disable_cloud_backup'); ?></label>
				<br>
				<label><input type='checkbox' id='chkRemoveOnMdmRemove' checked='true'></input><?php echo LANG('remove_when_leaving_mdm'); ?></label>
			<?php } elseif($ma->type == Models\ManagedApp::TYPE_ANDROID) { ?>
				<select id='sltInstallType' class='fullwidth'>
					<?php foreach(Models\ManagedApp::ANDROID_INSTALL_TYPES as $key => $title) { ?>
						<option value='<?php echo htmlspecialchars($key,ENT_QUOTES); ?>'><?php echo LANG($title); ?></option>
					<?php } ?>
				</select>
			<?php } ?>
		</td>
	</tr>
	<?php if($configs) { ?>
	<tr>
		<th><?php echo LANG('app_config'); ?></th>
		<td>
			<select id='sltManagedAppConfig' class='fullwidth'>
				<option value=''>-</option>
				<?php foreach($configs as $cId => $cName) { ?>
					<option value='<?php echo htmlspecialchars($cId,ENT_QUOTES); ?>'><?php echo htmlspecialchars($cName); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<th><?php echo LANG('app_config_json'); ?></th>
		<td>
			<textarea id='txtManagedAppConfig' class='fullwidth' placeholder='<?php echo LANG('optional_hint'); ?>'></textarea>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog()'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='assignManagedAppToGroup(
		txtManagedAppId.value,
		getSelectedSelectBoxValues("sltNewMobileDeviceGroup",true),
		typeof chkRemovable !== "undefined" && chkRemovable.checked ? 1 : 0,
		typeof chkDisableCloudBackup !== "undefined" && chkDisableCloudBackup.checked ? 1 : 0,
		typeof chkRemoveOnMdmRemove !== "undefined" && chkRemoveOnMdmRemove.checked ? 1 : 0,
		typeof sltInstallType !== "undefined" ? sltInstallType.value : "",
		typeof sltManagedAppConfig !== "undefined" ? sltManagedAppConfig.value : "",
		txtManagedAppConfig.value
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>
