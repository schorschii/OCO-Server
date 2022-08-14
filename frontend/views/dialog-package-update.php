<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditPackageId'></input>
<table class='fullwidth aligned'>
	<tr>
		<th colspan='2'><h2 class='first'><?php echo LANG['general']; ?></h2></td>
	</tr>
	<tr>
		<th><?php echo LANG['package_family_name']; ?></th>
		<td><input type='text' class='fullwidth' id='txtEditPackageFamilyName' disabled='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPackageVersion' autofocus='true'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['compatible_os']; ?></th>
		<td><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditPackageCompatibleOs'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['compatible_os_version']; ?></th>
		<td><input class='fullwidth' autocomplete='new-password' id='txtEditPackageCompatibleOsVersion'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea class='fullwidth' autocomplete='new-password' id='txtEditPackageNotes'></textarea></td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG['installation']; ?></h2></td>
	</tr>
	<tr>
		<th><?php echo LANG['procedure']; ?></th>
		<td><input class='fullwidth' autocomplete='new-password' id='txtEditPackageInstallProcedure'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input class='fullwidth' autocomplete='new-password' id='txtEditPackageInstallProcedureSuccessReturnCodes'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['after_completion']; ?></th>
		<td>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Package::POST_ACTION_NONE; ?>'>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Package::POST_ACTION_RESTART; ?>'>&nbsp;<?php echo LANG['restart']; ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Package::POST_ACTION_SHUTDOWN; ?>'>&nbsp;<?php echo LANG['shutdown']; ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Package::POST_ACTION_EXIT; ?>'>&nbsp;<?php echo LANG['restart_agent']; ?></label>
		</td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG['uninstallation']; ?></h2></td>
	</tr>
	<tr>
		<th><?php echo LANG['procedure']; ?></th>
		<td><input class='fullwidth' autocomplete='new-password' id='txtEditPackageUninstallProcedure'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input class='fullwidth' autocomplete='new-password' id='txtEditPackageUninstallProcedureSuccessReturnCodes'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG['after_completion']; ?></th>
		<td>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Package::POST_ACTION_NONE; ?>'>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Package::POST_ACTION_RESTART; ?>'>&nbsp;<?php echo LANG['restart']; ?></label>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Package::POST_ACTION_SHUTDOWN; ?>'>&nbsp;<?php echo LANG['shutdown']; ?></label>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<label><input type='checkbox' id='chkEditPackageDownloadForUninstall'></input>&nbsp;<?php echo LANG['download_for_uninstall']; ?></label>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button onclick="hideDialog();showLoader(false);showLoader2(false);"><img src="img/close.dyn.svg">&nbsp;<?php echo LANG['close']; ?></button>
	<button id='btnUpdatePackage' class='primary' onclick='editPackage(
		txtEditPackageId.value,
		txtEditPackageVersion.value,
		txtEditPackageCompatibleOs.value,
		txtEditPackageCompatibleOsVersion.value,
		txtEditPackageNotes.value,
		txtEditPackageInstallProcedure.value,
		txtEditPackageInstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_install_procedure_post_action"),
		txtEditPackageUninstallProcedure.value,
		txtEditPackageUninstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_uninstall_procedure_post_action"),
		chkEditPackageDownloadForUninstall.checked,
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG['change']; ?></button>
</div>
