<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$permGeneral = $cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION, false);
if(!$permGeneral) die("<div class='alert warning'>".LANG('permission_denied')."</div>");

$settingValue = $db->settings->get($_GET['key']??'') ?? '';
?>

<table class='fullwidth aligned'>
	<tr>
		<td><input type='text' class='fullwidth monospace' autofocus='true' autocomplete='new-password' id='txtEditSettingKey' placeholder='<?php echo LANG('key'); ?>' value='<?php echo htmlspecialchars($_GET['key']??''); ?>'></input></td>
	</tr>
	<tr>
		<td><textarea class='fullwidth monospace' autocomplete='new-password' id='txtEditSettingValue' placeholder='<?php echo LANG('value'); ?>' rows='8'><?php echo htmlspecialchars($settingValue); ?></textarea></td>
	</tr>
	<tr>
		<td>
			<div class='alert warning' style='margin-top:0px;width:420px;min-width:100%'>
				<?php echo LANG('be_careful_when_manual_editing_settings'); ?>
			</div>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' onclick='editSetting(txtEditSettingKey.value, txtEditSettingValue.value)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('save'); ?></button>
</div>
