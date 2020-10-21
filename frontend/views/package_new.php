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
			$_POST['uninstall_procedure'],
			$_POST['procedures']
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

<h1>Neues Paket</h1>

<table>
	<tr>
		<th>Name</th>
		<td><input type='text' id='txtName'></td>
	</tr>
	<tr>
		<th>Version</th>
		<td><input type='text' id='txtVersion'></td>
	</tr>
	<tr>
		<th>Autor</th>
		<td><input type='text' id='txtAuthor' value='<?php echo htmlspecialchars($_SESSION['um_username']); ?>'></td>
	</tr>
	<tr>
		<th>Beschreibung</th>
		<td><textarea id='txtDescription'></textarea></td>
	</tr>
	<tr>
		<th>ZIP-Archiv</th>
		<td><input type='file' id='fleArchive'></td>
	</tr>
	<tr>
		<th>Installations-Prozedur</th>
		<td><input type='text' id='txtInstallProcedure'></td>
	</tr>
	<tr>
		<th>Deinstallations-Prozedur</th>
		<td><input type='text' id='txtUninstallProcedure'></td>
	</tr>
	<tr>
		<th>Prozeduren (getrennt mit Komma)</th>
		<td><input type='text' id='txtProcedures'></td>
	</tr>
</table>

<p>
	Ein Paket besteht aus einem ZIP-Archiv, welches bei der Bereitstellung in ein temporäres Verzeichnis entpackt wird. Anschließend wird ein Kommando (die Prozedur) ausgeführt, um die Installation zu starten.
</p>

<p>
	<button onclick='createPackage(txtName.value, txtVersion.value, txtAuthor.value, txtDescription.value, fleArchive.files[0], txtInstallProcedure.value, txtUninstallProcedure.value, txtProcedures.value)'><img src='img/send.svg'>&nbsp;Senden</button>
</p>
