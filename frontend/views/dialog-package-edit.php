<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$package = $cl->getPackage($_GET['id'] ?? -1);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<input type='hidden' id='txtEditPackageId' value='<?php echo htmlspecialchars($package->id); ?>'></input>
<table id='frmEditPackage' class='fullwidth aligned form'>
	<tr>
		<th colspan='2'><h2 class='first'><?php echo LANG('general'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('package_family_name'); ?></th>
		<td>
			<select id='sltEditPackagePackageFamily'>
				<?php foreach($cl->getPackageFamilies() as $family) { ?>
					<option value='<?php echo $family->id; ?>' <?php if($package->package_family_id==$family->id) echo 'selected'; ?>><?php echo htmlspecialchars($family->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('version'); ?></th>
		<td>
			<input type='text' autocomplete='new-password' id='txtEditPackageVersion' autofocus='true' value='<?php echo htmlspecialchars($package->version,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_os'); ?></th>
		<td>
			<select id='sltCompatibleOs' title='<?php echo LANG('optional_hint'); ?>' multiple>
			<?php
			$values = array_map('trim', explode("\n", $package->compatible_os));
			foreach(array_filter(array_unique(array_merge($db->selectAllComputerAttribute('os'), $values))) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_os_version'); ?></th>
		<td>
			<select id='sltCompatibleOsVersion' title='<?php echo LANG('optional_hint'); ?>' multiple>
			<?php
			$values = array_map('trim', explode("\n", $package->compatible_os_version));
			foreach(array_filter(array_unique(array_merge($db->selectAllComputerAttribute('os_version'), $values))) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('compatible_architecture'); ?></th>
		<td>
			<select id='sltCompatibleArchitecture' title='<?php echo LANG('optional_hint'); ?>' size='2' multiple>
			<?php
			$values = array_map('trim', explode("\n",  $package->compatible_architecture));
			foreach(array_filter(array_unique(array_merge($db->selectAllComputerAttribute('architecture'), $values))) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('licenses'); ?></th>
		<td>
			<input type='number' class='fullwidth' autocomplete='new-password' id='txtEditPackageLicenseCount' placeholder='<?php echo LANG('optional_hint'); ?>' min='0' value='<?php echo htmlspecialchars($package->license_count??'',ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td>
			<textarea autocomplete='new-password' id='txtEditPackageNotes'><?php echo htmlspecialchars($package->notes); ?></textarea>
		</td>
	</tr>

	<tr><td colspan='2'><h2><?php echo LANG('package_content'); ?></h2></td></tr>
	<tr>
		<th><label><input type='checkbox' id='chkReplaceArchive' onclick='fleArchive.disabled=!this.checked'>&nbsp;<?php echo LANG('replace_zip_archive'); ?></label></th>
		<td colspan='3' class='fileinputwithbutton'>
			<input type='file' id='fleArchive' multiple='true' onchange='updatePackageProcedureTemplates()' disabled='true'>
			<button onclick='toggleInputDirectory(fleArchive,this)' title='<?php echo LANG('toggle_directory_upload'); ?>'><img src='img/files.dyn.svg'></button>
		</td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG('installation'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('procedure'); ?></th>
		<td class='inputwithbutton'>
			<?php if(strpos($package->install_procedure, "\n") === false) { ?>
				<input class='fullwidth monospace' autocomplete='new-password' id='txtEditPackageInstallProcedure' value='<?php echo htmlspecialchars($package->install_procedure,ENT_QUOTES); ?>'></input>
			<?php } else { ?>
				<textarea class='fullwidth monospace' id='txtEditPackageInstallProcedure'><?php echo htmlspecialchars($package->install_procedure); ?></textarea>
			<?php } ?>
			<button onclick='toggleTextBoxMultiLine(txtEditPackageInstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td>
			<input autocomplete='new-password' id='txtEditPackageInstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>' value='<?php echo htmlspecialchars($package->install_procedure_success_return_codes,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('after_completion'); ?></th>
		<td>
			<?php foreach([
				Models\Package::POST_ACTION_NONE => LANG('no_action'),
				Models\Package::POST_ACTION_RESTART => LANG('restart'),
				Models\Package::POST_ACTION_SHUTDOWN => LANG('shutdown'),
				Models\Package::POST_ACTION_EXIT => LANG('restart_agent'),
			] as $action => $title) { ?>
				<label>
					<input type='radio' name='edit_package_install_procedure_post_action' value='<?php echo $action; ?>' <?php if($package->install_procedure_post_action==$action) echo 'checked'; ?>>&nbsp;<?php echo $title; ?>
				</label>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('upgrade_behavior'); ?></th>
		<td>
			<?php foreach([
				Models\Package::UPGRADE_BEHAVIOR_EXPLICIT_UNINSTALL_JOBS => LANG('create_explicit_uninstall_jobs'),
				Models\Package::UPGRADE_BEHAVIOR_IMPLICIT_REMOVES_PREV_VERSION => LANG('installation_automatically_removes_other_versions'),
				Models\Package::UPGRADE_BEHAVIOR_NONE => LANG('keep_other_versions'),
			] as $behavior => $title) { ?>
				<label class='block'>
					<input type='radio' name='upgrade_behavior' value='<?php echo $behavior; ?>' <?php if($package->upgrade_behavior==$behavior) echo 'checked'; ?>>&nbsp;<?php echo $title; ?>
				</label>
			<?php } ?>
		</td>
	</tr>

	<tr>
		<th colspan='2'><h2><?php echo LANG('uninstallation'); ?></h2></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('procedure'); ?></th>
		<td class='inputwithbutton'>
			<?php if(strpos($package->uninstall_procedure, "\n") === false) { ?>
				<input class='fullwidth monospace' autocomplete='new-password' id='txtEditPackageUninstallProcedure' placeholder='<?php echo LANG('optional_hint'); ?>' value='<?php echo htmlspecialchars($package->uninstall_procedure,ENT_QUOTES); ?>'></input>
			<?php } else { ?>
				<textarea class='fullwidth monospace' id='txtEditPackageUninstallProcedure'><?php echo htmlspecialchars($package->uninstall_procedure); ?></textarea>
			<?php } ?>
			<button onclick='toggleTextBoxMultiLine(txtEditPackageUninstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td>
			<input autocomplete='new-password' id='txtEditPackageUninstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>' value='<?php echo htmlspecialchars($package->uninstall_procedure_success_return_codes,ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('after_completion'); ?></th>
		<td>
			<?php foreach([
				Models\Package::POST_ACTION_NONE => LANG('no_action'),
				Models\Package::POST_ACTION_RESTART => LANG('restart'),
				Models\Package::POST_ACTION_SHUTDOWN => LANG('shutdown'),
			] as $action => $title) { ?>
				<label>
					<input type='radio' name='edit_package_uninstall_procedure_post_action' value='<?php echo $action; ?>' <?php if($package->uninstall_procedure_post_action==$action) echo 'checked'; ?>>&nbsp;<?php echo $title; ?>
				</label>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('options'); ?></th>
		<td>
			<label>
				<input type='checkbox' id='chkEditPackageDownloadForUninstall' <?php if($package->download_for_uninstall) echo 'checked'; ?> />&nbsp;<?php echo LANG('download_for_uninstall'); ?>
			</label>
		</td>
	</tr>
</table>

<div class='controls right'>
	<button id='btnCloseDialog' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<?php echo Html::progressBar(0, 'prgPackageUpload', 'prgPackageUploadText', 'hidden big'); ?>
	<button id='btnEditPackage' class='primary' onclick='editPackage(
		txtEditPackageId.value,
		sltEditPackagePackageFamily.value,
		txtEditPackageVersion.value,
		getSelectedSelectBoxValues("sltCompatibleOs").join("\n"),
		getSelectedSelectBoxValues("sltCompatibleOsVersion").join("\n"),
		getSelectedSelectBoxValues("sltCompatibleArchitecture").join("\n"),
		txtEditPackageLicenseCount.value=="" ? -1 : txtEditPackageLicenseCount.value,
		txtEditPackageNotes.value,
		chkReplaceArchive.checked ? fleArchive.files : null,
		txtEditPackageInstallProcedure.value,
		txtEditPackageInstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_install_procedure_post_action"),
		getCheckedRadioValue("upgrade_behavior"),
		txtEditPackageUninstallProcedure.value,
		txtEditPackageUninstallProcedureSuccessReturnCodes.value,
		getCheckedRadioValue("edit_package_uninstall_procedure_post_action"),
		chkEditPackageDownloadForUninstall.checked,
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('change'); ?></button>
</div>
