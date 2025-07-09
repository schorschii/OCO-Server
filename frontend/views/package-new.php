<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<h1><img src='img/package-new.dyn.svg'><span id='page-title'><?php echo LANG('new_package'); ?></span></h1>

<datalist id='lstPackageNames'>
	<?php foreach($cl->getPackageFamilies() as $p) { ?>
		<option><?php echo htmlspecialchars($p->name); ?></option>
	<?php } ?>
</datalist>
<datalist id='lstVersions'>
	<option>$$ProductVersion$$</option>
</datalist>
<datalist id='lstInstallProceduresTemplates'>
	<option>[FILENAME]</option>
	<option>msiexec /quiet /i</option>
	<option>apt install -y</option>
	<option>gdebi -n</option>
	<option>installer -target / -pkg</option>
	<option>msiexec /quiet /i [FILENAME]</option>
	<option>apt install -y ./[FILENAME]</option>
	<option>gdebi -n [FILENAME]</option>
	<option>installer -target / -pkg [FILENAME]</option>
</datalist>
<datalist id='lstUninstallProceduresTemplates'>
	<option>[FILENAME]</option>
	<option>msiexec /quiet /x $$ProductCode$$</option>
	<option>msiexec /quiet /x</option>
	<option>apt remove -y</option>
	<option>msiexec /quiet /x [FILENAME]</option>
	<option>apt remove -y [FILENAME]</option>
</datalist>
<datalist id='lstInstallProcedures'>
	<option>msiexec /quiet /i</option>
	<option>apt install -y</option>
	<option>gdebi -n</option>
	<option>installer -target / -pkg</option>
</datalist>
<datalist id='lstUninstallProcedures'>
	<option>msiexec /quiet /x $$ProductCode$$</option>
	<option>msiexec /quiet /x</option>
	<option>apt remove -y</option>
</datalist>

<table id='frmNewPackage' class='form fullwidth'>
	<tr><td colspan='2'><h2><?php echo LANG('general'); ?></h2></td></tr>
	<tr class='nospace'>
		<th><?php echo LANG('package_family_name'); ?></th>
		<td>
			<input type='text' id='txtName' list='lstPackageNames' value='<?php echo htmlspecialchars($_GET['name']??'',ENT_QUOTES); ?>'>
		</td>
		<th><?php echo LANG('version'); ?></th>
		<td>
			<input type='text' id='txtVersion' list='lstVersions' value='<?php echo htmlspecialchars($_GET['version']??'',ENT_QUOTES); ?>'>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_os'); ?></th>
		<td>
			<select id='sltCompatibleOs' title='<?php echo LANG('optional_hint'); ?>' multiple>
			<?php
			$values = array_filter(array_map('trim', explode(',', $_GET['compatible_os']??'')));
			foreach(array_unique(array_merge($db->selectAllComputerAttribute('os'), $values)) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
		<th><?php echo LANG('compatible_os_version'); ?></th>
		<td>
			<select id='sltCompatibleOsVersion' title='<?php echo LANG('optional_hint'); ?>' multiple>
			<?php
			$values = array_filter(array_map('trim', explode(',', $_GET['compatible_os_version']??'')));
			foreach(array_unique(array_merge($db->selectAllComputerAttribute('os_version'), $values)) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('compatible_architecture'); ?></th>
		<td>
			<select id='sltCompatibleArchitecture' title='<?php echo LANG('optional_hint'); ?>' size='2' multiple>
			<?php
			$values = array_filter(array_map('trim', explode(',', $_GET['compatible_architecture']??'')));
			foreach(array_unique(array_merge($db->selectAllComputerAttribute('architecture'), $values)) as $v) {
			?>
				<option value='<?php echo htmlspecialchars($v,ENT_QUOTES); ?>' <?php if(in_array($v, $values)) echo 'selected'; ?>><?php echo htmlspecialchars($v); ?></option>
			<?php } ?>
			</select>
		</td>
		<th><?php echo LANG('licenses'); ?></th>
		<td>
			<input type='number' class='fullwidth' autocomplete='new-password' id='txtLicenseCount' placeholder='<?php echo LANG('optional_hint'); ?>' min='0' value='<?php echo htmlspecialchars($_GET['license_count']??'',ENT_QUOTES); ?>'></input>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td colspan='3'>
			<textarea id='txtNotes' placeholder='<?php echo LANG('optional_hint'); ?>'><?php echo htmlspecialchars($_GET['description']??'',ENT_QUOTES); ?></textarea>
		</td>
	</tr>

	<tr><td colspan='2'><h2><?php echo LANG('package_content'); ?></h2></td></tr>
	<tr>
		<th><?php echo LANG('zip_archive'); ?></th>
		<td colspan='3' class='fileinputwithbutton'>
			<input type='file' id='fleArchive' multiple='true' onchange='updatePackageProcedureTemplates()'>
			<button onclick='toggleInputDirectory(fleArchive,this)' title='<?php echo LANG('toggle_directory_upload'); ?>'><img src='img/files.dyn.svg'></button>
		</td>
	</tr>

	<tr><td colspan='2'><h2><?php echo LANG('installation'); ?></h2></td></tr>
	<tr class='nospace'>
		<th><?php echo LANG('install_procedure'); ?></th>
		<td colspan='3' class='inputwithbutton'>
			<?php if(strpos($_GET['install_procedure']??'', "\n") === false) { ?>
				<input type='text' id='txtInstallProcedure' class='monospace' list='lstInstallProcedures' value='<?php echo htmlspecialchars($_GET['install_procedure']??'',ENT_QUOTES); ?>'>
			<?php } else { ?>
				<textarea id='txtInstallProcedure' class='monospace' list='lstInstallProcedures'><?php echo htmlspecialchars($_GET['install_procedure']??''); ?></textarea>
			<?php } ?>
			<button onclick='toggleTextBoxMultiLine(txtInstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td><input type='text' id='txtInstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>' value='<?php echo htmlspecialchars($_GET['install_procedure_success_return_codes']??'0',ENT_QUOTES); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG('after_completion'); ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Models\Package::POST_ACTION_NONE; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 0) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('no_action'); ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Models\Package::POST_ACTION_RESTART; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 1) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('restart'); ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Models\Package::POST_ACTION_SHUTDOWN; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 2) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('shutdown'); ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Models\Package::POST_ACTION_EXIT; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 3) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('restart_agent'); ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('upgrade_behavior'); ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='upgrade_behavior' value='<?php echo Models\Package::UPGRADE_BEHAVIOR_EXPLICIT_UNINSTALL_JOBS; ?>' <?php if(($_GET['upgrade_behavior']??$db->settings->get('default-upgrade-behavior')) == 2) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('create_explicit_uninstall_jobs'); ?></label><br>
			<label class='inlineblock'><input type='radio' name='upgrade_behavior' value='<?php echo Models\Package::UPGRADE_BEHAVIOR_IMPLICIT_REMOVES_PREV_VERSION; ?>' <?php if(($_GET['upgrade_behavior']??$db->settings->get('default-upgrade-behavior')) == 1) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('installation_automatically_removes_other_versions'); ?></label><br>
			<label class='inlineblock'><input type='radio' name='upgrade_behavior' value='<?php echo Models\Package::UPGRADE_BEHAVIOR_NONE; ?>' <?php if(($_GET['upgrade_behavior']??$db->settings->get('default-upgrade-behavior')) == 0) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('keep_other_versions'); ?></label>
		</td>
	</tr>

	<tr><td colspan='2'><h2><?php echo LANG('uninstallation'); ?></h2></td></tr>
	<tr class='nospace'>
		<th><?php echo LANG('uninstall_procedure'); ?></th>
		<td colspan='3' class='inputwithbutton'>
			<?php if(strpos($_GET['install_procedure']??'', "\n") === false) { ?>
				<input type='text' id='txtUninstallProcedure' class='monospace' list='lstUninstallProcedures' placeholder='<?php echo LANG('optional_hint'); ?>' value='<?php echo htmlspecialchars($_GET['uninstall_procedure']??'',ENT_QUOTES); ?>'>
			<?php } else { ?>
				<textarea id='txtUninstallProcedure' class='monospace' list='lstUninstallProcedures' placeholder='<?php echo LANG('optional_hint'); ?>'><?php echo htmlspecialchars($_GET['uninstall_procedure']??'',ENT_QUOTES); ?></textarea>
			<?php } ?>
			<button onclick='toggleTextBoxMultiLine(txtUninstallProcedure)' title='<?php echo LANG('toggle_multi_line'); ?>'><img src='img/textbox.dyn.svg'></button>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('success_return_codes'); ?></th>
		<td><input type='text' id='txtUninstallProcedureSuccessReturnCodes' title='<?php echo LANG('success_return_codes_comma_separated'); ?>' value='<?php echo htmlspecialchars($_GET['uninstall_procedure_success_return_codes']??'0',ENT_QUOTES); ?>'></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('after_completion'); ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Models\Package::POST_ACTION_NONE; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 0) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('no_action'); ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Models\Package::POST_ACTION_RESTART; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 1) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('restart'); ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Models\Package::POST_ACTION_SHUTDOWN; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 2) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('shutdown'); ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('options'); ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='checkbox' id='chkDownloadForUninstall' <?php if($_GET['download_for_uninstall']??$db->settings->get('default-download-for-uninstall')) echo "checked='true'"; ?>>&nbsp;<?php echo LANG('download_for_uninstall'); ?></label>
		</td>
	</tr>
	<tr>
		<td colspan='4'>
		<div class='content-foot'>
			<div class='filler'></div>
			<?php echo progressBar(0, 'prgPackageUpload', 'prgPackageUploadText', 'hidden big'); ?>
			<button id='btnCreatePackage' type='button' class='primary' onclick='createPackage(
				txtName.value,
				txtVersion.value,
				txtLicenseCount.value=="" ? -1 : txtLicenseCount.value,
				txtNotes.value,
				fleArchive.files,
				txtInstallProcedure.value,
				txtInstallProcedureSuccessReturnCodes.value,
				getCheckedRadioValue("install_post_action"),
				getCheckedRadioValue("upgrade_behavior"),
				txtUninstallProcedure.value,
				txtUninstallProcedureSuccessReturnCodes.value,
				chkDownloadForUninstall.checked,
				getCheckedRadioValue("uninstall_post_action"),
				getSelectedSelectBoxValues("sltCompatibleOs").join(", "),
				getSelectedSelectBoxValues("sltCompatibleOsVersion").join(", "),
				getSelectedSelectBoxValues("sltCompatibleArchitecture").join(", "))
			'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create_package'); ?></button>
		</div>
		</td>
	</tr>
</table>

<div class='alert info'>
	<?php echo LANG('package_creation_notes'); ?>
</div>
