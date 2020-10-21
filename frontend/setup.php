<?php
require_once('../lib/loader.php');

$info = null;
$infoclass = null;

// exit if setup was already done
if($db->existsSchema() && count($db->getAllSystemuser()) > 0) {
	header('Location: index.php');
	die();
}

// create initial admin user
if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['password2'])) {
	if($_POST['password'] !== $_POST['password2']) {
		$info = "Kennwörter stimmen nicht überein";
		$infoclass = "red";
	} else {
		if(
			$db->addSystemuser(
				$_POST['username'], $_POST['username'],
				password_hash($_POST['password'], PASSWORD_DEFAULT),
				0/*ldap-flag*/, null, null, null, 'initial admin user', 0/*locked*/
			)
		) {
			header('Location: login.php');
			die();
		} else {
			$info = "Datenbankfehler. Bitte die Logdateien überprüfen.";
			$infoclass = "red";
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>[Login] <?php echo APP_NAME; ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'><?php echo APP_NAME; ?></span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<form method='POST' action='setup.php' onsubmit='btnFinish.disabled = true'>
				<h1>Setup</h1>
				<?php if($info !== null) { ?>
					<h3 class='<?php echo $infoclass; ?>'><?php echo $info; ?></h3>
				<?php } ?>
				<input type='text' name='username' placeholder='Admin-Benutzername wählen...' autofocus='true'>
				<input type='password' name='password' placeholder='Kennwort wählen...'>
				<input type='password' name='password2' placeholder='Kennwort bestätigen...'>
				<button id='btnFinish'>Fertig stellen</button>
			</form>
			<img src='img/logo.svg'>
		</div>
		<div id='login-bg'>

		</div>
	</div>

</div>

</body>
</html>
