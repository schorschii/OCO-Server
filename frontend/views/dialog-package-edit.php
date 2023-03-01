<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');
?>

<input type='hidden' id='txtEditPackageId'></input>
<table id='frmEditPackage' class='fullwidth aligned form'>
	<tr>
		<th colspan='2'><h2 class='first'><?php echo LANG('general'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('package_family_name'); ?></th>
		<td>
			<select id='sltEditPackagePackageFamily'>
				<?php foreach($cl->getPackageFamilies() as $family) { ?>
					<option value='<?php echo $family->id; ?>'><?php echo htmlspecialchars($family->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('version'); ?></th>
		<td><input type='text' autocomplete='new-password' id='txtEditPackageVersion' autofocus='true'></input></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_os'); ?></th>
		<td><input type='text' autocomplete='new-password' id='txtEditPackageCompatibleOs'></input></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_os_version'); ?></th>
		<td><input autocomplete='new-password' id='txtEditPackageCompatibleOsVersion'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea autocomplete='new-password' id='txtEditPackageNotes'></textarea></td>
	</tr>

	<tr><td colspan='2'><h2><?php echo LANG('package_content'); ?></h2></td></tr>
	<tr>
		<th><label><input type='checkbox' id='chkReplaceArchive' onclick='fleArchive.disabled=!this.checked'>&nbsp;<?php echo LANG('replace_zip_archive'); ?></label></th>
		<td colspan='3' class='fileinputwithbutton'><input type='file' id='fleArchive' multiple='true' onchange='updatePackageProcedureTemplates()' disabled='true'><button onclick='toggleInputDirectory(fleArchive)' title='<?php echo LANG('toggle_directory_upload'); ?>'><img src='img/folder.dyn.svg'></button></td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG('installation'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('procedure'); ?></th>
		<td class='inputwithbutton'>
			<input class='fullwidth monospace' autocomplete='new-password' id='txtEditPackageInstallProcedure'></input>
			<button onclick='toggleTextBoxMultiLine(txtEditPackageInstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td><input autocomplete='new-password' id='txtEditPackageInstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('after_completion'); ?></th>
		<td>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_NONE; ?>'>&nbsp;<?php echo LANG('no_action'); ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_RESTART; ?>'>&nbsp;<?php echo LANG('restart'); ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_SHUTDOWN; ?>'>&nbsp;<?php echo LANG('shutdown'); ?></label>
			<label><input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_EXIT; ?>'>&nbsp;<?php echo LANG('restart_agent'); ?></label>
		</td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG('uninstallation'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('procedure'); ?></th>
		<td class='inputwithbutton'>
			<input class='fullwidth monospace' autocomplete='new-password' id='txtEditPackageUninstallProcedure'></input>
			<button onclick='toggleTextBoxMultiLine(txtEditPackageUninstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td><input autocomplete='new-password' id='txtEditPackageUninstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>'></input></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('after_completion'); ?></th>
		<td>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_NONE; ?>'>&nbsp;<?php echo LANG('no_action'); ?></label>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_RESTART; ?>'>&nbsp;<?php echo LANG('restart'); ?></label>
			<label><input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo Models\Package::POST_ACTION_SHUTDOWN; ?>'>&nbsp;<?php echo LANG('shutdown'); ?></label>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<label><input type='checkbox' id='chkEditPackageDownloadForUninstall'></input>&nbsp;<?php echo LANG('download_for_uninstall'); ?></label>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button id='btnCloseDialog' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<?php echo progressBar(0, 'prgPackageUpload', 'prgPackageUploadText', 'hidden big'); ?>
	<button id='btnEditPackage' class='primary' onclick='editPackage(
		txtEditPackageId.value,
		sltEditPackagePackageFamily.value,
		txtEditPackageVersion.value,
		txtEditPackageCompatibleOs.value,
		txtEditPackageCompatibleOsVersion.value,
		txtEditPackageNotes.value,
		chkReplaceArchive.checked ? fleArchive.files : null,
		txtEditPackageInstallProcedure.value,
		txtEditPackageInstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_install_procedure_post_action"),
		txtEditPackageUninstallProcedure.value,
		txtEditPackageUninstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_uninstall_procedure_post_action"),
		chkEditPackageDownloadForUninstall.checked,
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
