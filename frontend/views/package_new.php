<?php
$SUBVIEW = 1;
require_once('../../lib/loader.php');
require_once('../session.php');

if(isset($_POST['name'])) {
	if(empty($_POST['name']) || empty($_POST['install_procedure'])) {
		header('HTTP/1.1 400 Missing Information');
		die(LANG['please_fill_required_fields']);
	}
	$tmpName = $_FILES['archive']['tmp_name'];
	$mimeType = mime_content_type($_FILES['archive']['tmp_name']);
	if($mimeType != 'application/zip') {
		// create zip with uploaded file
		$tmpName = '/tmp/ocotmparchive.zip';
		$zip = new ZipArchive();
		if(!$zip->open($tmpName, ZipArchive::CREATE)) {
			header('HTTP/1.1 500 Cannot Create Zip Archive');
			die(htmlspecialchars($mimeType));
		}
		$zip->addFile($_FILES['archive']['tmp_name'], '/'.basename($_FILES['archive']['name']));
		$zip->close();
	}
	$insertId = $db->addPackage(
		$_POST['name'],
		$_POST['version'],
		$_POST['author'],
		$_POST['description'],
		$_POST['install_procedure'],
		$_POST['uninstall_procedure']
	);
	if(!$insertId) {
		header('HTTP/1.1 500 Failed');
		die(LANG['database_error']);
	}
	$filename = intval($insertId).'.zip';
	$filepath = PACKAGE_PATH.'/'.$filename;
	if($tmpName == $_FILES['archive']['tmp_name']) {
		$result = move_uploaded_file($tmpName, $filepath);
	} else {
		$result = rename($tmpName, $filepath);
	}
	if(!$result) {
		error_log('Can not move uploaded file to: '.$filepath);
		$db->removePackage($insertId);
		header('HTTP/1.1 500 Can Not Move Uploaded File');
		die();
	}
	die();
}
?>

<h1><?php echo LANG['new_package']; ?></h1>

<table class='form'>
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
		<td><input type='file' id='fleArchive' accept='application/zip, application/x-zip-compressed, multipart/x-zip'></td>
	</tr>
	<tr>
		<th><?php echo LANG['install_procedure']; ?></th>
		<td><input type='text' id='txtInstallProcedure'></td>
	</tr>
	<tr>
		<th><?php echo LANG['uninstall_procedure']; ?></th>
		<td><input type='text' id='txtUninstallProcedure'></td>
	</tr>
	<tr>
		<th></th>
		<td><button id='btnCreatePackage' onclick='createPackage(txtName.value, txtVersion.value, txtAuthor.value, txtDescription.value, fleArchive.files[0], txtInstallProcedure.value, txtUninstallProcedure.value)'><img src='img/send.svg'>&nbsp;<?php echo LANG['send']; ?></button></td>
</table>

<?php echo LANG['package_creation_notes']; ?>
