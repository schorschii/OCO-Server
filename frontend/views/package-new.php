<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<h1><img src='img/package-new.dyn.svg'><span id='page-title'><?php echo LANG['new_package']; ?></span></h1>

<datalist id='lstPackageNames'>
	<?php foreach($db->getAllPackageFamily() as $p) { ?>
		<option><?php echo htmlspecialchars($p->name); ?></option>
	<?php } ?>
</datalist>
<datalist id='lstInstallProceduresTemplates'>
	<option>[FILENAME]</option>
	<option>msiexec /quiet /i</option>
	<option>gdebi -n</option>
	<option>msiexec /quiet /i [FILENAME]</option>
	<option>gdebi -n [FILENAME]</option>
</datalist>
<datalist id='lstUninstallProceduresTemplates'>
	<option>[FILENAME]</option>
	<option>msiexec /quiet /x</option>
	<option>apt remove -y</option>
	<option>msiexec /quiet /x [FILENAME]</option>
	<option>apt remove -y [FILENAME]</option>
</datalist>
<datalist id='lstInstallProcedures'>
	<option>msiexec /quiet /i</option>
	<option>gdebi -n</option>
</datalist>
<datalist id='lstUninstallProcedures'>
	<option>msiexec /quiet /x</option>
	<option>apt remove -y</option>
</datalist>
<datalist id='lstOs'>
	<?php
	$mentioned = [];
	foreach($db->getAllComputer() as $c) {
		if(in_array($c->os, $mentioned)) continue;
		$mentioned[] = $c->os;
	?>
		<option><?php echo htmlspecialchars($c->os); ?></option>
	<?php } ?>
</datalist>
<datalist id='lstOsVersion'>
	<?php
	$mentioned = [];
	foreach($db->getAllComputer() as $c) {
		if(in_array($c->os_version, $mentioned)) continue;
		$mentioned[] = $c->os_version;
	?>
		<option><?php echo htmlspecialchars($c->os_version); ?></option>
	<?php } ?>
</datalist>

<table id='frmNewPackage' class='form'>
	<tr class='nospace'>
		<th><?php echo LANG['package_family']; ?></th>
		<td><input type='text' id='txtName' list='lstPackageNames' value='<?php echo htmlspecialchars($_GET['name']??'',ENT_QUOTES); ?>'></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG['version']; ?></th>
		<td><input type='text' id='txtVersion' value='<?php echo htmlspecialchars($_GET['version']??'',ENT_QUOTES); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea id='txtDescription' placeholder='<?php echo LANG['optional_hint']; ?>'><?php echo htmlspecialchars($_GET['description']??'',ENT_QUOTES); ?></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td><input type='file' id='fleArchive' onchange='updatePackageProcedureTemplates()'></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><input type='text' id='txtInstallProcedure' list='lstInstallProcedures' value='<?php echo htmlspecialchars($_GET['install_procedure']??'',ENT_QUOTES); ?>'></td>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input type='text' id='txtInstallProcedureSuccessReturnCodes' title='<?php echo LANG['success_return_codes_comma_separated']; ?>' value='<?php echo htmlspecialchars($_GET['install_procedure_success_return_codes']??'0',ENT_QUOTES); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['after_completion']; ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Package::POST_ACTION_NONE; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 0) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Package::POST_ACTION_RESTART; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 1) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['restart']; ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Package::POST_ACTION_SHUTDOWN; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 2) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['shutdown']; ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' value='<?php echo Package::POST_ACTION_EXIT; ?>' <?php if(($_GET['install_procedure_post_action']??0) == 3) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['restart_agent']; ?></label>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><input type='text' id='txtUninstallProcedure' list='lstUninstallProcedures' placeholder='<?php echo LANG['optional_hint']; ?>' value='<?php echo htmlspecialchars($_GET['uninstall_procedure']??'',ENT_QUOTES); ?>'></td>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input type='text' id='txtUninstallProcedureSuccessReturnCodes' title='<?php echo LANG['success_return_codes_comma_separated']; ?>' value='<?php echo htmlspecialchars($_GET['uninstall_procedure_success_return_codes']??'0',ENT_QUOTES); ?>'></td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG['after_completion']; ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Package::POST_ACTION_NONE; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 0) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Package::POST_ACTION_RESTART; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 1) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['restart']; ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' value='<?php echo Package::POST_ACTION_SHUTDOWN; ?>' <?php if(($_GET['uninstall_procedure_post_action']??0) == 2) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['shutdown']; ?></label>
		</td>
	</tr>
	<tr>
		<th></th>
		<td><label class='inlineblock'><input type='checkbox' id='chkDownloadForUninstall' <?php if($_GET['download_for_uninstall']??true) echo "checked='true'"; ?>>&nbsp;<?php echo LANG['download_for_uninstall']; ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG['compatible_os']; ?></th>
		<td><input type='text' id='txtCompatibleOs' list='lstOs' placeholder='<?php echo LANG['optional_hint']; ?>' value='<?php echo htmlspecialchars($_GET['compatible_os']??'',ENT_QUOTES); ?>'></td>
		<th><?php echo LANG['compatible_os_version']; ?></th>
		<td><input type='text' id='txtCompatibleOsVersion' list='lstOsVersion' placeholder='<?php echo LANG['optional_hint']; ?>' value='<?php echo htmlspecialchars($_GET['compatible_os_version']??'',ENT_QUOTES); ?>'></td>
	</tr>
	<tr>
		<th></th>
		<td colspan='4'>
			<button id='btnCreatePackage' type='button' onclick='createPackage(txtName.value, txtVersion.value, txtDescription.value, fleArchive.files[0], txtInstallProcedure.value, txtInstallProcedureSuccessReturnCodes.value, getCheckedRadioValue("install_post_action"), txtUninstallProcedure.value, txtUninstallProcedureSuccessReturnCodes.value, chkDownloadForUninstall.checked, getCheckedRadioValue("uninstall_post_action"), txtCompatibleOs.value, txtCompatibleOsVersion.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['send']; ?></button>
			<?php echo progressBar(0, 'prgPackageUpload', 'prgPackageUploadContainer', 'prgPackageUploadText', 'width:180px;display:none;'); ?>
		</td>
</table>

<?php echo LANG['package_creation_notes']; ?>
