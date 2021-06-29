<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');

if(isset($_POST['name'])) {
	try {
		// no payload by default
		$tmpFilePath = null;
		$tmpFileName = null;
		if(!empty($_FILES['archive'])) {
			// use file from user upload
			$tmpFilePath = $_FILES['archive']['tmp_name'];
			$tmpFileName = $_FILES['archive']['name'];
		}
		// create package
		$insertId = $cl->createPackage($_POST['name'], $_POST['version'], $_POST['description'] ?? '', $_SESSION['um_username'] ?? '',
			$_POST['install_procedure'], $_POST['install_procedure_success_return_codes'] ?? '', $_POST['install_procedure_restart'] ?? null, $_POST['install_procedure_shutdown'] ?? null,
			$_POST['uninstall_procedure'] ?? '', $_POST['uninstall_procedure_success_return_codes'] ?? '', $_POST['download_for_uninstall'], $_POST['uninstall_procedure_restart'] ?? null, $_POST['uninstall_procedure_shutdown'] ?? null,
			$_POST['compatible_os'] ?? null, $_POST['compatible_os_version'] ?? null, $tmpFilePath, $tmpFileName
		);
		die(strval(intval($insertId)));
	} catch(Exception $e) {
		header('HTTP/1.1 400 Unable To Perform Requested Action');
		die($e->getMessage());
	}
}
?>

<h1><?php echo LANG['new_package']; ?></h1>

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

<table class='form'>
	<tr>
		<th><?php echo LANG['package_family']; ?></th>
		<td><input type='text' id='txtName' list='lstPackageNames'></td>
	</tr>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><input type='text' id='txtVersion'></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea id='txtDescription'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td><input type='file' id='fleArchive' onchange='updatePackageProcedureTemplates()'></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><input type='text' id='txtInstallProcedure' list='lstInstallProcedures'></td>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input type='text' id='txtInstallProcedureSuccessReturnCodes' title='<?php echo LANG['success_return_codes_comma_separated']; ?>' value='0'></td>
	</tr>
	<tr>
		<th><?php echo LANG['after_completion']; ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionNone' checked='true'>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionRestart'>&nbsp;<?php echo LANG['restart']; ?></label>
			<label class='inlineblock'><input type='radio' name='install_post_action' id='rdoInstallPostActionShutdown'>&nbsp;<?php echo LANG['shutdown']; ?></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><input type='text' id='txtUninstallProcedure' list='lstUninstallProcedures' placeholder='<?php echo LANG['optional_hint']; ?>'></td>
		<th><?php echo LANG['success_return_codes']; ?></th>
		<td><input type='text' id='txtUninstallProcedureSuccessReturnCodes' title='<?php echo LANG['success_return_codes_comma_separated']; ?>' value='0'></td>
	</tr>
	<tr>
		<th><?php echo LANG['after_completion']; ?></th>
		<td colspan='3'>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionNone' checked='true'>&nbsp;<?php echo LANG['no_action']; ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionRestart'>&nbsp;<?php echo LANG['restart']; ?></label>
			<label class='inlineblock'><input type='radio' name='uninstall_post_action' id='rdoUninstallPostActionShutdown'>&nbsp;<?php echo LANG['shutdown']; ?></label>
		</td>
	</tr>
	<tr>
		<th></th>
		<td><label class='inlineblock'><input type='checkbox' id='chkDownloadForUninstall' checked='true'>&nbsp;<?php echo LANG['download_for_uninstall']; ?></label></td>
	</tr>
	<tr>
		<th><?php echo LANG['compatible_os']; ?></th>
		<td><input type='text' id='txtCompatibleOs' list='lstOs' placeholder='<?php echo LANG['optional_hint']; ?>'></td>
		<th><?php echo LANG['compatible_os_version']; ?></th>
		<td><input type='text' id='txtCompatibleOsVersion' list='lstOsVersion' placeholder='<?php echo LANG['optional_hint']; ?>'></td>
	</tr>
	<tr>
		<th></th>
		<td colspan='4'>
			<button id='btnCreatePackage' onclick='createPackage(txtName.value, txtVersion.value, txtDescription.value, fleArchive.files[0], txtInstallProcedure.value, txtInstallProcedureSuccessReturnCodes.value, rdoInstallPostActionRestart.checked, rdoInstallPostActionShutdown.checked, txtUninstallProcedure.value, txtUninstallProcedureSuccessReturnCodes.value, chkDownloadForUninstall.checked, rdoUninstallPostActionRestart.checked, rdoUninstallPostActionShutdown.checked, txtCompatibleOs.value, txtCompatibleOsVersion.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['send']; ?></button>
			<?php echo progressBar(0, 'prgPackageUpload', 'prgPackageUploadContainer', 'prgPackageUploadText', 'width:180px;display:none;'); ?>
		</td>
</table>

<?php echo LANG['package_creation_notes']; ?>
