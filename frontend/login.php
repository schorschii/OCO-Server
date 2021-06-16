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
		$info = LANG['user_does_not_exist'];
		$infoclass = 'error';
	} else {
		if(!$user->locked) {
			if(checkPassword($user, $_POST['password'])) {
				$_SESSION['um_username'] = $user->username;
				$_SESSION['um_userid'] = $user->id;
				header('Location: index.php');
				die();
			} else {
				sleep(2);
				$info = LANG['login_failed'];
				$infoclass = 'error';
			}
		} else {
			sleep(1);
			$info = LANG['user_locked'];
			$infoclass = 'error';
		}
	}
}

// execute logout if requested
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['um_username'])) {
		session_unset();
		session_destroy();
		$info = LANG['log_out_successful'];
		$infoclass = 'success';
	}
}

// redirect to index.php if already logged in
if(!empty($_SESSION['um_username'])) {
	header('Location: index.php');
	die();
}

function checkPassword($userObject, $checkPassword) {
	$result = validatePassword($userObject, $checkPassword);
	if(!$result) {
		error_log('user '.$userObject->username.': authentication failure');
	}
	return $result;
}
function validatePassword($userObject, $checkPassword) {
	if($userObject->ldap) {
		if(empty($checkPassword)) return false;
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
	<title>[<?php echo LANG['login']; ?>] <?php echo LANG['app_name']; ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<a href='#' class='title'><?php echo LANG['app_name']; ?></a>
		</span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<?php if(isIE()) { ?>
				<img src='img/ietroll.png'>
			<?php } else { ?>
				<form method='POST' action='login.php' onsubmit='btnLogin.disabled=true; txtUsername.readOnly=true; txtPassword.readOnly=true;'>
					<h1><?php echo LANG['login']; ?></h1>
					<?php if($info !== null) { ?>
						<div class='alert bold <?php echo $infoclass; ?>'><?php echo $info; ?></div>
					<?php } ?>
					<input id='txtUsername' type='text' name='username' placeholder='<?php echo LANG['username']; ?>' autofocus='true'>
					<input id='txtPassword' type='password' name='password' placeholder='<?php echo LANG['password']; ?>'>
					<button id='btnLogin'><?php echo LANG['log_in']; ?></button>
				</form>
				<img src='img/logo.dyn.svg'>
			<?php } ?>
		</div>

		<div id='login-bg'>
			<a href='https://github.com/schorschii/oco-server' target='_blank'>
				<img id='forkme' src='img/forkme.png'>
			</a>
			<div id='motd'><?php echo MOTD[ rand(0, sizeof(MOTD)-1) ]; ?></div>
		</div>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

</div>

</body>
</html>
