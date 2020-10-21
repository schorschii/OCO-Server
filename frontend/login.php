<?php
require_once('../lib/loader.php');

$info = null;
$infoclass = null;

// redirect to setup if setup is not done
if(!$db->existsSchema() || count($db->getAllSystemuser()) == 0) {
	header('Location: setup.php');
	die();
}

// execute login if requested
session_start();
if(isset($_POST['username']) && isset($_POST['password'])) {
	$user = $db->getSystemuserByLogin($_POST['username']);
	if($user === null) {
		sleep(2);
		$info = "Benutzer existiert nicht";
		$infoclass = "red";
	} else {
		if(validatePassword($user, $_POST['password'])) {
			$_SESSION['um_username'] = $user->username;
			$_SESSION['um_userid'] = $user->id;
			header('Location: index.php');
			die();
		} else {
			sleep(2);
			$info = "Anmeldung fehlgeschlagen";
			$infoclass = "red";
		}
	}
}
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['um_username'])) {
		session_destroy();
		$info = "Abmeldung erfolgreich";
		$infoclass = "green";
	}
}

function validatePassword($userObject, $checkPassword) {
	if($userObject->ldap) {
		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) return false;
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 3);
		$ldapbind = @ldap_bind($ldapconn, $userObject->username.'@'.LDAP_DOMAIN, $checkPassword);
		if(!$ldapbind) return false;
		return true;
	} else {
		return password_verify($checkPassword, $userObject->password);
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
			<form method='POST' action='login.php' onsubmit='btnLogin.disabled=true; txtUsername.readOnly=true; txtPassword.readOnly=true;'>
				<h1>Anmeldung</h1>
				<?php if($info !== null) { ?>
					<h3 class='<?php echo $infoclass; ?>'><?php echo $info; ?></h3>
				<?php } ?>
				<input id='txtUsername' type='text' name='username' placeholder='Benutzername' autofocus='true'>
				<input id='txtPassword' type='password' name='password' placeholder='Kennwort'>
				<button id='btnLogin'>Anmelden</button>
			</form>
			<img src='img/logo.svg'>
		</div>
		<div id='login-bg'>

		</div>
	</div>

</div>

</body>
</html>
