<?php
require_once('../lib/Loader.php');

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
	try {
		$user = $cl->login($_POST['username'], $_POST['password']);
		if(empty($user)) throw new Exception(LANG['unknown_error']);
		// login successful
		$db->addLogEntry(Log::LEVEL_INFO, $user->username, 'oco.webfrontend.authentication', 'Login Successful');
		$_SESSION['um_last_login'] = $user->last_login;
		$_SESSION['um_username'] = $user->username;
		$_SESSION['um_userid'] = $user->id;
		header('Location: index.php');
		die();
	} catch(Exception $e) {
		$db->addLogEntry(Log::LEVEL_WARNING, $_POST['username'], 'oco.webfrontend.authentication', 'Login Failed');

		$info = $e->getMessage();
		$infoclass = 'error';
	}
}

// execute logout if requested
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['um_username'])) {
		$db->addLogEntry(Log::LEVEL_INFO, $_SESSION['um_username'], 'oco.webfrontend.authentication', 'Logout Successful');
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
			<div id='motd'><?php echo LOGIN_SCREEN_QUOTES[ rand(0, sizeof(LOGIN_SCREEN_QUOTES)-1) ]; ?></div>
		</div>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

</div>

</body>
</html>
