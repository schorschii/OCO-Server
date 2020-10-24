<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(!empty($_POST['name'])) {
	$filename = randomString().'.zip';
	$filepath = PACKAGE_PATH.'/'.$filename;
	if(move_uploaded_file($_FILES['archive']['tmp_name'], $filepath)) {
		$db->addPackage(
			$_POST['name'],
			$_POST['version'],
			$_POST['author'],
			$_POST['description'],
			$filename,
			$_POST['install_procedure'],
			$_POST['uninstall_procedure']
		);
	} else {
		error_log('can not move uploaded file');
	}
	die();
}

function randomString($length = 8) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
?>

<h1><?php echo LANG['new_package']; ?></h1>

<table>
	<tr>
		<th><?php echo LANG['name']; ?></th>
		<td><input type='text' id='txtName'></td>
	</tr>
	<tr>
		<th><?php echo LANG['version']; ?></th>
		<td><input type='text' id='txtVersion'></td>
	</tr>
	<tr>
		<th><?php echo LANG['author']; ?></th>
		<td><input type='text' id='txtAuthor' value='<?php echo htmlspecialchars($_SESSION['um_username']); ?>'></td>
	</tr>
	<tr>
		<th><?php echo LANG['description']; ?></th>
		<td><textarea id='txtDescription'></textarea></td>
	</tr>
	<tr>
		<th><?php echo LANG['zip_archive']; ?></th>
		<td><input type='file' id='fleArchive'></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><input type='text' id='txtInstallProcedure'></td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><input type='text' id='txtUninstallProcedure'></td>
	</tr>
</table>

<?php echo LANG['package_creation_notes']; ?>

<p>
	<button id='btnCreatePackage' onclick='createPackage(txtName.value, txtVersion.value, txtAuthor.value, txtDescription.value, fleArchive.files[0], txtInstallProcedure.value, txtUninstallProcedure.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['send']; ?></button>
</p>
